<?php

namespace WebImage\Node\Service\Db;

use Doctrine\DBAL\Connection;
use WebImage\Core\Dictionary;
use WebImage\Db\ConnectionManager;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\Node;
use WebImage\Node\Properties\MultiValuePropertyInterface;
use WebImage\Node\Properties\MultiValuePropertyValue;
use WebImage\Node\Properties\Property;
use WebImage\Node\Query\Filter;
use WebImage\Node\Query\Property As QueryProperty;
use WebImage\Node\Query\Query;
use WebImage\Db\QueryBuilder As DbQueryBuilder;

class NodeQueryService
{
	private $nodeService;
	/** @var TableNameHelper */
	private $tableNameHelper;

	public function __construct(NodeService $nodeService)
	{
		$this->nodeService = $nodeService;
	}

	/**
	 * Retrieve nodes for a query
	 *
	 * @param Query $query
	 *
	 * @return Node[]
	 */
	public function query(Query $query)
	{
		$qb = $this->getConnectionManager()->createQueryBuilder();

		$typeService = $this->getRepository()->getNodeTypeService();
		$typeQNames = $query->getFilterTypeQNames();
		$rootTableKey = $this->getTableNameHelper()->getRootTableKey($typeService, $typeQNames);

		$this->configureSelect($qb, $query);
		$this->configureTables($qb, $query);
		$this->configureFilterAssociationValues($qb, $query);
		$this->configureFilters($qb, $query);
		$this->configureKeywords($qb, $query);
		$this->configureFilterTypeQNames($qb, $query, $rootTableKey);
		$this->configureSorts($qb, $query);
		$this->configureStatus($qb, $query, $rootTableKey);

		// Check if we need to paginate results:
		if (null !== $query->getCurrentPage()) $qb->setFirstResult($query->getCurrentPage() * $query->getResultsPerPage() - $query->getResultsPerPage());
		if (null !== $query->getResultsPerPage()) $qb->setMaxResults($query->getResultsPerPage());

		$results = $qb->execute()->fetchAll();
		$nodes = $this->convertResultsToNodes($results, $rootTableKey);

		return $nodes;
	}

	/**
	 * @return NodeService
	 */
	public function getNodeService()
	{
		return $this->nodeService;
	}

	/**
	 * @return \WebImage\Node\Service\Repository
	 */
	public function getRepository()
	{
		return $this->getNodeService()->getRepository();
	}

	private function convertResultsToNodes(array $results, string $rootTableKey)
	{
		$nodes = [];
		$node = null;

		foreach($results as $result) {
			$node = $this->convertResultToNode($result, $rootTableKey);
			if (null === $node) continue;

			$nodes[] = $node;
		}

		return $nodes;
	}

	private function convertResultToNode(array $result, string $rootTableKey)
	{
		$repository = $this->getRepository();
		$dataTypeService = $repository->getDataTypeService();
		$typeService = $repository->getNodeTypeService();
		$tableNameHelper = $this->getTableNameHelper();

		$typeQNameColumn = $tableNameHelper->getColumnNameAlias($rootTableKey, 'type_qname');
		$uuidColumn = $tableNameHelper->getColumnNameAlias($rootTableKey, 'node_uuid');
		$versionColumn = $tableNameHelper->getColumnNameAlias($rootTableKey, 'node_version');

		$node = new Node($result[$typeQNameColumn]);
		$node->setRepository($repository);

		$nodeRef = new NodeRef($result[$uuidColumn], $result[$versionColumn]);
		$node->setNodeRef($nodeRef);

		$type = $typeService->getNodeTypeByTypeQName($node->getTypeQName());

		if (null === $type) return;

		$typeStack = $type->getTypeStack();
		$columns = array_keys($result);

		foreach($typeStack as $stackType) {

			$typeDef = $stackType->getDef();

			$propertyDefs = $typeDef->getProperties();

			foreach($propertyDefs as $propertyDef) {

				$propertyTableKey = null; // Defined below
				$propertyKey = $propertyDef->getKey();

				$propertyDataType = $dataTypeService->getDataType($propertyDef->getDataType());

				/**
				 * Get Property Definition Original Table
				 */
				$typeQName = $propertyDef->getNodeTypeQName();
				$propParentType = $typeService->getNodeTypeByTypeQName($typeQName);

				if (null === $propParentType) continue;

				$propParentTypeDef = $propParentType->getDef();

				$propertyTableKey = $this->getTableNameHelper()->getTableKeyFromDef($propParentTypeDef);

				$property = null;

				if ($propertyDef->isMultiValued()) throw new \Exception('Multi valued properties are not yet supported'); // $property = new MultiValuePropertyValue();

				$property = new Property();

				$d = new Dictionary();
				$property->setValue($d);

				foreach($propertyDataType->getModelFields() as $field) {

					$key = null === $field->getKey() ? '' : $field->getKey();
					$column = $tableNameHelper->getColumnName($propertyTableKey, $propertyKey, $field->getKey());
					$alias = $tableNameHelper->getColumnNameAlias($propertyTableKey, $propertyKey, $field->getKey());

					if (!in_array($alias, $columns)) continue; // isset($result[$column]) does not work for nulls

					$d->set($key, $result[$alias]);
				}

				$property->setDef($propertyDef);
				$node->addProperty($propertyKey, $property);
			}
		}

		return $node;
	}

	private function getKeywords(Query $query)
	{
		if (count($query->getKeywords()) > 0) throw new Exception(__METHOD__ . ' not yet implemented. ' . __FILE__ . ':' . __LINE__);
		$keywords = [];
		/**
		 * Generate keywords
		 */
		$filterKeywords = $this->createFiltersFromQuery($query);

		$propertiesInfo = $this->getColumnsForProperties($query, $filterKeywords);

		foreach($propertiesInfo as $propertyInfo) {

			$tableKey = $propertyInfo['tableKey'];
			$object = $propertyInfo['object'];
			$fieldName = $object->getProperty();
			$field_value = $object->getValue();

			if (!isset($keywords[$tableKey])) $keywords[$tableKey] = array();
			if (!isset($keywords[$tableKey][$fieldName])) $keywords[$tableKey][$fieldName] = array();

			$keywords[$tableKey][$fieldName][] = $field_value;
		}

		return $keywords;
	}
	/**
	 * Get filters for query
	 * @param Query $query
	 *
	 * @return array
	 */
	private function createFiltersFromQuery(Query $query)
	{
		$filterKeywords = [];
		$typeService = $this->getRepository()->getNodeTypeService();

		foreach($query->getFilters() as $keyword) {
			foreach($query->getFilterTypeQNames() as $typeQName) {
				$nodeType = $typeService->getNodeTypeByTypeQName($typeQName);
				foreach($nodeType->getDef()->getProperties() as $propertyDef) {
					if ($propertyDef->isSearchable()) $filterKeywords[] = new Filter($typeQName, $propertyDef->getKey, $keyword, 'LIKE');
				}
			}
		}

		return $filterKeywords;
	}

	private function configureSelect(DbQueryBuilder $qb, Query $query)
	{
		/**
		 * Add fields
		 */
		$propertiesInfo = $this->getColumnsForProperties($query, $query->getProperties());

		if (count($propertiesInfo) > 0) throw new \RuntimeException('Selecting specific columns is not currently supported');

		/**
		 * Add all fields from all tables
		 */
		$typeService = $this->getRepository()->getNodeTypeService();
		$dataTypeService = $this->getRepository()->getDataTypeService();
		$tableNameHelper = $this->getTableNameHelper();

		// Add all columns to results
		$uniqueColumns = [];
		foreach($query->getFilterTypeQNames() as $typeQName) {

			$type = $typeService->getNodeTypeByTypeQName($typeQName);
			if ($type === null) throw new \RuntimeException(sprintf('Unknown type for filter type: %s', $typeQName));

			foreach($type->getTypeStack() as $type) {
				$tableKey = $this->getTableNameHelper()->getTableKeyFromDef($type->getDef());
				if (!$this->getTableNameHelper()->shouldDefHavePhysicalTable($type->getDef())) continue;

				foreach($type->getDef()->getProperties() as $property) {
					if ($property->isMultiValued()) continue; // Multi-valued properties are handled elsewhere

					$dataType = $dataTypeService->getDataType($property->getDataType());

					foreach($dataType->getModelFields() as $field) {
						$column = $tableNameHelper->getColumnName($tableKey, $property->getKey(), $field->getKey());
						$alias = $tableNameHelper->getColumnNameAlias($tableKey, $property->getKey(), $field->getKey());

						if (!in_array($alias, $uniqueColumns)) {
							$uniqueColumns[] = $alias;
							$qb->addSelect(sprintf('%s AS %s', $column, $alias));
						}
					}
				}
			}
		}
	}

	private function configureTables(DbQueryBuilder $qb, Query $query)
	{
		$typeService = $this->getRepository()->getNodeTypeService();
		$tables = [];

		foreach($query->getFilterTypeQNames() as $typeQName) {
			$type = $typeService->getNodeTypeByTypeQName($typeQName);
			if (null === $type) {
				echo 'NULL: ' . $typeQName .'<br>';
				die(__FILE__.':'.__LINE__.PHP_EOL);
			}

			foreach($type->getTypeStack() as $type) {
				$tableKey = $this->getTableNameHelper()->getTableKeyFromDef($type->getDef());
				if (!$this->getTableNameHelper()->shouldDefHavePhysicalTable($type->getDef())) continue;

				$tables[] = $tableKey;
			}
		}

		// Setup from table
		$rootTableKey = array_shift($tables); // The first table is the "FROM"
		$rootTableName = $this->getConnectionManager()->getTableName($rootTableKey);
		$qb->from($rootTableName, $rootTableKey);

		// Setup joins
		foreach($tables as $tableKey) {
			$joinConditions = sprintf('%s.node_uuid = %s.node_uuid AND %1$s.node_version = %1$s.node_version', $tableKey, $rootTableKey);
			$tableName = $this->getConnectionManager()->getTableName($tableKey);
			$qb->leftJoin($rootTableKey, $tableName, $tableKey, $joinConditions);
		}
	}

	private function configureFilterAssociationValues(DbQueryBuilder $qb, Query $query)
	{
		$associationValues = $query->getFilterAssociationValues();

		for ($i = 0, $j = count($associationValues); $i < $j; $i++) {
			die(__FILE__.':'.__LINE__.PHP_EOL);
			// Setup join for related tables
			$tableKey = 'node_assocs';
			$tableAlias = 'node_assocs_' . $i;

			$joinKeys = sprintf('%s.src_node_uuid = n.node_uuid AND %s.src_node_version = n.node_version', $tableAlias, $tableAlias);

			$qb->join('n', $tableKey, $tableAlias, $joinKeys);


			// Setup where clause for association
			$associationValue = $associationValues[$i]->getValue();
			$associationTypeQName = $associationValues[$i]->getQName();

			die(__FILE__.':'.__LINE__.PHP_EOL);
			if (is_array($associationValue)) { // Search for multiple values using an array of possible vlaues

				$valueSearchField = new DAOSearchFieldValues($tableAlias, 'tgt_node_uuid', $associationValue);
				// Since we are search multiple values, we should make sure that only distince Nodes are returned (since a Node could potentially match more than one associations and create duplicate results
				$search->makeDistinct(true);

			} else {
				$valueSearchField = new DAOSearchField($tableAlias, 'tgt_node_uuid', $associationValue);
			}

			$search->addSearchField($valueSearchField);

			// Filter by association type qname, but only if a value is actually defined (otherwise it would be considered a "wildcard" search for any type of association with another node
			if (!empty($associationTypeQName)) {

				$search->addSearchField(new DAOSearchField($tableAlias, 'assocTypeQName', $associationTypeQName));

			}
		}
	}

	private function configureFilters(DbQueryBuilder $qb, Query $query)
	{
		foreach($query->getFilters() as $filter) {
			$propertyColumns = $this->getColumnsForProperty($query, $filter);

			foreach($propertyColumns as $propertyColumn) {
				foreach($propertyColumn->getColumns() as $column) {
					$alias = sprintf('%s_%s', $propertyColumn->getTableKey(), $column);
					$operator = null;

					switch ($filter->getOperator()) {
						case Filter::OPERATOR_LIKE:
							$operator = 'LIKE';
							break;
						case Filter::OPERATOR_NOT_EQUALS:
							$operator = 'NOT LIKE';
							break;
						default:
							$operator = $filter->getOperator();
							break;
					}
					$qb->andWhere(sprintf('%s.`%s` %s :%s', $propertyColumn->getTableKey(), $column, $operator, $alias));
					$qb->setParameter($alias, $filter->getValue());
				}
			}
		}
	}

	private function configureKeywords(DbQueryBuilder $qb, Query $query)
	{
		$keywords = $this->getKeywords($query);

		// Add keywords to search
		if (count($keywords) > 0) {
			// Build list of searchable fields
			$keyword_group = new DAOSearchOrGroup();

			foreach($keywords as $tableKey => $fields) {
				foreach($fields as $name => $values) {
					foreach($values as $value) {
						$keyword_group->addSearchField(new DAOSearchFieldWildcard($tableKey, $name, $value));
					}
				}
			}

			$search->addSearchField($keyword_group);
		}
	}

	private function configureFilterTypeQNames(DbQueryBuilder $qb, Query $query, string $rootTableKey)
	{
		// Filter search by type_names (e.g. {WebImage.Node.Types.Content)
		if (count($query->getFilterTypeQNames()) > 0) {
			$typeQNameColumn = $this->getTableNameHelper()->getColumnName($rootTableKey, 'type_qname');

			// @TODO Auto-include children of Type QNames
			$qb->andWhere($typeQNameColumn . ' IN (:type_qname)');
			$qb->setParameter(':type_qname', $query->getFilterTypeQNames(), Connection::PARAM_STR_ARRAY);
		}
	}

	private function configureSorts(DbQueryBuilder $qb, Query $query)
	{
		foreach($query->getSorts() as $sort) {
			$propertyColumns = $this->getColumnsForProperty($query, $sort);
			foreach($propertyColumns as $sortColumn) {
				foreach($sortColumn->getColumns() as $column) {
					$qb->orderBy(sprintf('`%s`.`%s`', $sortColumn->getTableKey(), $column), $sort->getSortDirection());
				}
			}
		}
	}

	private function configureStatus(DbQueryBuilder $qb, Query $query, string $rootTableKey)
	{
		$qb->andWhere($rootTableKey . ' .status = :node_status');
		$qb->setParameter(':node_status', NodeService::NODE_STATUS_ACTIVE);
	}

	/**
	 * Get the property definitions for the supplied objects
	 *
	 * @param Query $query
	 * @param QueryProperty[]|Filter[] $properties
	 *
	 * @return PropertyTableColumns[] The columns used for a table query
	 */
	private function getColumnsForProperties(Query $query, array $properties)
	{
		$columns = array();

		/** @var QueryProperty $property */

		foreach($properties as $propertyColumns) {
			foreach($this->getColumnsForProperty($query, $propertyColumns) as $propertyColumn) {
				$columns[] = $propertyColumn;
			}
		}

		return $columns;
	}

	/**
	 * @param Query $query
	 * @param QueryProperty $property
	 *
	 * @return PropertyTableColumns[]
	 */
	private function getColumnsForProperty(Query $query, QueryProperty $property)
	{
		$repository = $this->nodeService->getRepository();
		$nodeTypeService = $repository->getNodeTypeService();
		$dataTypeService = $repository->getDataTypeService();

		$columns = [];

		$objectFieldName = $property->getProperty();

		$useTypeDefs = array();

		// If this value is null then assume we are dealing with a wildcard requested where we want to grab all instances of a field across all NodeType's
		if (null === $property->getTypeQName()) {
			foreach($query->getFilterTypeQNames() as $typeQName) {
				$def = $nodeTypeService->getNodeTypeByTypeQName($typeQName)->getDef();
				if (null !== $def->getProperty($property->getProperty())) $useTypeDefs[] = $def;
			}
			//Otherwise, assume a specific type is being referenced for the field inclusion
		} else {
			if ($nodeType = $nodeTypeService->getNodeTypeByTypeQName($property->getTypeQName())) {
				$nodeTypeDef = $nodeType->getDef();
				$useTypeDefs[] = $nodeTypeDef;
			}
		}

		// Iterate through each of the found type_defs (there will be multiple if $objectTypeQNname is null, and one if $objectTypeQNname is defined
		foreach($useTypeDefs as $useTypeDef) {
			if (!($useTypeDef instanceof NodeTypeRef)) continue;

			$tableKey = $useTypeDef->getTableKey();
			$propertyColumns = new PropertyTableColumns($tableKey);

			// Make sure field is actually part of the type in question
			/** @var NodeTypePropertyDef $propertyDef */
			if ($propertyDef = $useTypeDef->getProperty($objectFieldName)) {
				// Only handle single value properties for now

				if (!$propertyDef->isMultiValued()) {

					if ($dataType = $dataTypeService->getDataType($propertyDef->getDataType())) {
						if ($dataType->isSimpleStorage()) {
							$propertyColumns->addColumn($propertyDef->getKey());
						} else {
							$modelFields = $dataType->getModelFields();

							while ($modelField = $modelFields->getNext()) {
								$fieldName = $propertyDef->getKey() . '_' . $modelField->getName();

								$propertyColumns->addColumn($fieldName);
							}
						}
					}

					$columns[] = $propertyColumns;
				}
			}
		}

		return $columns;
	}

	/**
	 * Create table helper
	 *
	 * @return TableNameHelper
	 */
	private function getTableNameHelper(): TableNameHelper
	{
		if (null === $this->tableNameHelper) $this->tableNameHelper = new TableNameHelper();

		return $this->tableNameHelper;
	}

	/**
	 * Convenience method to return ConnectionManager
	 *
	 * @return ConnectionManager
	 */
	private function getConnectionManager(): ConnectionManager
	{
		return $this->nodeService->getConnectionManager();
	}

}