<?php

namespace WebImage\Node\Service\Db;

use WebImage\Core\Dictionary;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\Node;
use WebImage\Node\Properties\MultiValuePropertyValue;
use WebImage\Node\Properties\Property;
use WebImage\Node\Query\Filter;
use WebImage\Node\Query\Property As QueryProperty;
use WebImage\Node\Query\Query;
use WebImage\Db\QueryBuilder As DbQueryBuilder;

class NodeQueryService
{
	private $nodeService;

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
		$tables = [];

		$cm = $this->nodeService->getConnectionManager();
		$qb = $cm->createQueryBuilder();

		$qb->select('n.*')->from('nodes', 'n'); // $search
		$qb->where('n.status = :node_status');
		$qb->setParameter(':node_status', NodeService::NODE_STATUS_ACTIVE);

		$this->configureSelect($qb, $query);
		$this->configureJoins($qb, $query);
		$this->configureFilterAssociationValues($qb, $query);
		$this->configureFilters($qb, $query);
		$this->configureKeywords($qb, $query);
		$this->configureFilterTypeQNames($qb, $query);
		$this->configureSorts($qb, $query);

		// Check if we need to paginate results:
		if (null !== $query->getCurrentPage()) $qb->setFirstResult($query->getCurrentPage() * $query->getResultsPerPage() - $query->getResultsPerPage());
		if (null !== $query->getResultsPerPage()) $qb->setMaxResults($query->getResultsPerPage());

		$results = $qb->execute()->fetchAll();

		$nodes = $this->convertResultsToNodes($results);

		return $nodes;
	}

	/**
	 * @return NodeService
	 */
	public function getNodeServices()
	{
		return $this->nodeService;
	}

	/**
	 * @return \WebImage\Node\Service\Repository
	 */
	public function getRepository()
	{
		return $this->getNodeServices()->getRepository();
	}

	private function convertResultsToNodes(array $results)
	{
		$nodes = [];
		$node = null;

		foreach($results as $result) {
			$node = $this->convertResultToNode($result);
			if (null === $node) continue;

			$nodes[] = $node;
		}

		return $nodes;
	}

	private function convertResultToNode(array $result)
	{
		$repository = $this->getRepository();
		$dataService = $repository->getDataTypeService();
		$typeService = $repository->getNodeTypeService();

		$node = new Node($result['type_qname']);
		$node->setRepository($repository);

		$nodeRef = new NodeRef($result['uuid'], $result['version'], $result['id']);
		$node->setNodeRef($nodeRef);

		$type = $typeService->getNodeTypeByTypeQName($node->getTypeQName());

		if (null === $type) return;

		$typeStack = $type->getTypeStack();

		foreach($typeStack as $stackType) {

			$typeDef = $stackType->getDef();

			if (!($typeDef instanceof NodeTypeRef)) continue;

			$propertyDefs = $typeDef->getProperties();

			foreach($propertyDefs as $propertyDef) {

				$propertyTableKey = null; // Defined below
				$propertyKey = $propertyDef->getKey();

				$propertyDataType = $dataService->getDataType($propertyDef->getQName());

				/**
				 * Get Property Definition Original Table
				 */
				$propertyDefTypeQName = $propertyDef->getNodeTypeTypeQName();

				$propertyDefType = $typeService->getNodeTypeByTypeQName($propertyDefTypeQName);

				if (null === $propertyDefType) continue;

				$propertyDefTypeDef = $propertyDefType->getDef();

				// Make sure this is a database definition, because we need the getTableKey() method
				$propertyTableKey = $propertyDefTypeDef->getTableKey();
				$column = sprintf('%s_%s', $propertyTableKey, $propertyKey);

				if ($propertyDataType->isSimpleStorage()) {
					if (isset($result[$column])) {
						$property = null;

						if ($propertyDef->isMultiValued()) {
							$property = new MultiValuePropertyValue();
							#if ($defaultValue = $def->getDefault()) $nodeProperty->addValue($nodeProperty->setValue($def->getDefault()));
						} else {
							$property = new Property();
							$property->setValue($result[$column]);
						}
						$property->setDef($propertyDef);
						$node->addProperty($propertyKey, $property);
					}
				} else {
					die(__FILE__.':'.__LINE__.PHP_EOL);
					$dataTypeModelFields = $propertyDataType->getModelFields();

					$d = new Dictionary();

					foreach($dataTypeModelFields as $dataTypeModelField) {
						$fieldName = $propertyKey . '_' . $dataTypeModelField->getName();

						if (isset($result[$fieldName])) {
							$d->set($dataTypeModelField->getName(), $result[$fieldName]);
						}
					}

					if ($propertyDef->isMultiValued()) {
						$property = new MultiValueProperty(); // CWI_CNODE_NodeMultiProperty();
						#if ($default_value = $def->getDefault()) $nodeProperty->addValue($nodeProperty->setValue($def->getDefault()));
					} else {
						$property = new Property();
						$property->setValue($d);
					}

					$property->setDef($propertyDef);
					$node->addProperty($propertyKey, $property);
				}
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

//	private function getFilters(Query $query)
//	{
//		$filters = [];
//		/**
//		 * Add filters
//		 */
//
//		foreach($columns as $column) {
//
//			$tableKey = $column['tableKey'];
//			$object = $column['object'];
//
//			$filter = array(
//				'queryFilter' => $object,
//				'tableKey' => $tableKey
//			);
//
//			$filters[] = $filter;
//		}
//
//		return $filters;
//	}

	private function getSorts(Query $query)
	{
		if (count($query->getSorts()) > 0) throw new \Exception(__METHOD__ . ' not yet implemented. ' . __FILE__ . ':' . __LINE__);
		$sorts = [];
		/**
		 * Add sorts
		 */
		$propertiesInfo = $this->getColumnsForProperties($query, $query->getSorts());

		foreach($propertiesInfo as $propertyInfo) {

			$tableKey = $propertyInfo['tableKey'];
			$object = $propertyInfo['object'];

			$sort = array(
				'querySort' => $object,
				'tableKey' => $tableKey
			);
			$sorts[] = $sort;
		}

		return $sorts;
	}

	private function configureSelect(DbQueryBuilder $qb, Query $query)
	{
		/**
		 * Add fields
		 */
		$propertiesInfo = $this->getColumnsForProperties($query, $query->getProperties());
		$colFormat = '%s.`%s` AS %1$s_%2$s';

		if (count($propertiesInfo) > 0) {
			foreach ($propertiesInfo as $propertyInfo) {
				$tableKey = $propertyInfo['tableKey'];

				foreach ($propertyInfo['columns'] as $column) {
					$qb->addSelect(sprintf($colFormat, $tableKey, $column));
				}
			}
		} else {
			/**
			 * Add all fields from all tables
			 */
			$typeService = $this->getRepository()->getNodeTypeService();
			// Add all columns to results
			foreach($query->getFilterTypeQNames() as $typeQName) {
				$type = $typeService->getNodeTypeByTypeQName($typeQName);
				$def = $type->getDef();
				if (!($def instanceof NodeTypeRef)) continue; // Skip types that do not have table information

				$tableKey = $def->getTableKey();
				$properties = $def->getProperties();
				/** @var NodeTypePropertyDef $property */
				foreach($properties as $property) {
					if ($property->isMultiValued()) continue;
					$column = $property->getKey();
					$qb->addSelect(sprintf($colFormat, $tableKey, $column));
				}
			}
		}
	}
	private function configureJoins(DbQueryBuilder $qb, Query $query)
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
				$typeDef = $type->getDef();
				if (!($typeDef instanceof NodeTypeRef)) continue;
				$tables[] = $typeDef->getTableKey();
			}
		}

		foreach($tables as $tableKey) {
			$joinConditions = sprintf('%s.node_id = n.id AND %1$s.node_version = n.version', $tableKey);
			$qb->leftJoin('n', $tableKey, $tableKey, $joinConditions);
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

			$joinKeys = sprintf('%s.src_node_id = n.node_id AND %s.src_node_version = n.version', $tableAlias, $tableAlias);

			$qb->join('n', $tableKey, $tableAlias, $joinKeys);


			// Setup where clause for association
			$associationValue = $associationValues[$i]->getValue();
			$associationTypeQName = $associationValues[$i]->getQName();

			die(__FILE__.':'.__LINE__.PHP_EOL);
			if (is_array($associationValue)) { // Search for multiple values using an array of possible vlaues

				$valueSearchField = new DAOSearchFieldValues($tableAlias, 'tgt_node_id', $associationValue);
				// Since we are search multiple values, we should make sure that only distince Nodes are returned (since a Node could potentially match more than one associations and create duplicate results
				$search->makeDistinct(true);

			} else {
				$valueSearchField = new DAOSearchField($tableAlias, 'tgt_node_id', $associationValue);
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

	private function configureFilterTypeQNames(DbQueryBuilder $qb, Query $query)
	{
		// Filter search by type_names (e.g. {WebImage.Node.Types.Content)
		if (count($query->getFilterTypeQNames()) > 0) {
			$or = [];

			foreach($query->getFilterTypeQNames() as $ix => $typeQName) {
				$paramName = sprintf(':queryfiltertypeqname%d', $ix);
				$or[] = sprintf('n.type_qname = %s', $paramName);
				$qb->setParameter($paramName, $typeQName);
			}

			$qb->andWhere('(' . implode(' OR ', $or) . ')');
		}
	}

	private function configureSorts(DbQueryBuilder $qb, Query $query)
	{
		$sorts = $this->getSorts($query);

		foreach($sorts as $sort) {
			$query_filter = $sort['querySort'];
			$sort_direction = null;

			if ($query_filter->getSortDirection() == CWI_CNODE_QUERY_Query::SORT_ASC) {
				$sort_direction = DAOSearch::SORT_ASC;
			} else if ($query_filter->getSortDirection() == CWI_CNODE_QUERY_Query::SORT_DESC) {
				$sort_direction = DAOSearch::SORT_DESC;
			}

			$search->addSort($sort['tableKey'], $sort['querySort']->getField(), $sort_direction);
		}
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

					if ($dataType = $dataTypeService->getDataType($propertyDef->getQName())) {
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
}