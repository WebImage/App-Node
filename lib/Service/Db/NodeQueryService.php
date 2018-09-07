<?php

namespace WebImage\Node\Service\Db;

use WebImage\Core\Dictionary;
use WebImage\Node\Entities\Node;
use WebImage\Node\Properties\MultiValuePropertyValue;
use WebImage\Node\Properties\Property;
use WebImage\Node\Query\Query;

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
		$fields = [];
		$filters = [];
		$sorts = [];
		$keywords = [];

		$tables = [];

		/**
		 * Add fields
		 */
		$info = $this->getPropertyDefsForTypeQName($query->getFields(), $tables);

		$tables = $info['tables']; // Updates tables
		$propertiesInfo = $info['properties_info'];

		foreach ($propertiesInfo as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];

			if (!isset($fields[$tableKey])) $fields[$tableKey] = array();

			foreach ($property_info['columns'] as $column) {
				$fields[$tableKey][] = $column;
			}
		}

		/*
		 * Generate keywords
		 */
		$filterKeywords = array();

		if (count($query->getKeywords()) > 0) {

			// Get all available NodeTypes
			$nodeTypes = $this->getRepository()->getNodeTypeService()->getNodeTypes();
			// Iterate through NodeTypes
			foreach($nodeTypes as $nodeType) {
				// Retrieve list of NodeTypeProperties for the current definition
				$nodePropDefs = $nodeType->getDef()->getProperties();
				// Iterate through properties
				foreach($nodePropDefs as $nodePropDef) {
					// Check if property is searchable
					if ($nodePropDef->isSearchable()) {
						// Add search fields for all keywords
						foreach ($query->getKeywords() as $queryKeyword) {
							$filterKeywords[] = new Filter($nodeType->getDef()->getQName(), $nodePropDef->getKey(), $queryKeyword, 'LIKE');
						}
					}
				}
			}
		}

		$info = $this->getPropertyDefsForTypeQName($filterKeywords, $tables);

		$tables = $info['tables'];
		$propertiesInfo = $info['properties_info'];

		foreach ($propertiesInfo as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];
			$field_name = $object->getField();
			$field_value = $object->getValue();

			if (!isset($keywords[$tableKey])) $keywords[$tableKey] = array();
			if (!isset($keywords[$tableKey][$field_name])) $keywords[$tableKey][$field_name] = array();

			$keywords[$tableKey][$field_name][] = $field_value;
		}

		/**
		 * Add filters
		 */
		$info = $this->getPropertyDefsForTypeQName($query->getFilters(), $tables);

		$tables = $info['tables']; // Updates tables
		$propertiesInfo = $info['properties_info'];

		foreach ($propertiesInfo as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];

			$filter = array(
				'query_filter' => $object,
				'table_key' => $tableKey
			);

			$filters[] = $filter;
		}

		/**
		 * Add sorts
		 */
		$info = $this->getPropertyDefsForTypeQName($query->getSorts(), $tables);
		$tables = $info['tables']; // Updates tables
		$propertiesInfo = $info['properties_info'];

		foreach ($propertiesInfo as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];

			$sort = array(
				'query_sort' => $object,
				'table_key' => $tableKey
			);
			$sorts[] = $sort;
		}

		$cm = $this->nodeService->getConnectionManager();
		$qb = $cm->createQueryBuilder();
		$qb->select('*')->from('nodes', 'n'); // $search
		$qb->where('n.status = :node_status');
		$qb->setParameter(':node_status', NodeService::NODE_STATUS_ACTIVE);
		
		foreach ($tables as $tableKey) {
			$selectFields = array();
			if (isset($fields[$tableKey])) $selectFields = $fields[$tableKey];

			$joinConditions = sprintf('%s.node_id = n.id %s.version = n.version', $tableKey, $tableKey);

//			if (in_array($tableKey, $innerJoinTables)) $qb->join('n', $tableKey, $tableKey, $joinConditions);
//			else
			$qb->leftJoin('n', $tableKey, $tableKey, $joinConditions);
		}

		$association_values = $query->getFilterAssociationValues();

		for ($i = 0, $j = count($association_values); $i < $j; $i++) {
			die(__FILE__.':'.__LINE__.PHP_EOL);
			// Setup join for related tables
			$tableKey = 'node_assocs';
			$tableAlias = 'node_assocs_' . $i;

			$joinKeys = sprintf('%s.src_node_id = n.node_id AND %s.src_node_version = n.version', $tableAlias, $tableAlias);

			$qb->join('n', $tableKey, $tableAlias, $joinKeys);

			// Setup where clause for association
			$associationValue = $association_values[$i]->getValue();
			$associationTypeQName = $association_values[$i]->getAssociationTypeQName();

			die(__FILE__.':'.__LINE__.PHP_EOL);
			if (is_array($associationValue)) { // Search for multiple values using an array of possible vlaues

				$valueSearchField = new DAOSearchFieldValues($tableAlias, 'dst_node_id', $associationValue);
				// Since we are search multiple values, we should make sure that only distince Nodes are returned (since a Node could potentially match more than one associations and create duplicate results
				$search->makeDistinct(true);

			} else {
				$valueSearchField = new DAOSearchField($tableAlias, 'dst_node_id', $associationValue);
			}

			$search->addSearchField($valueSearchField);

			// Filter by association type qname, but only if a value is actually defined (otherwise it would be considered a "wildcard" search for any type of association with another node
			if (!empty($associationTypeQName)) {

				$search->addSearchField(new DAOSearchField($tableAlias, 'assoc_type_qname', $associationTypeQName));

			}
			#getAssociationTypeQName()
			#getValue()
#$group->addSearchField( new DAOSearchField('nodes', 'type_qname', $query_filter_type_qname) );
		}

		foreach ($filters as $filter) {
			die(__FILE__.':'.__LINE__.PHP_EOL);
			$tableKey = $filter['table_key'];
			$query_filter = $filter['query_filter'];
			$field = $query_filter->getField();
			$value = $query_filter->getValue();
			$operator = strtoupper($query_filter->getOperator());

			$search_field = null;

			switch ($operator) {
				case 'LIKE':
					$search_field = new DAOSearchFieldWildcard($tableKey, $field, $value);
					break;
				case '!=':
					$search_field = new DAOSearchFieldNot($tableKey, $field, $value);
					break;
				case '=':
				default:
					$search_field = new DAOSearchField($tableKey, $field, $value);
					break;
			}

			if (null !== $search_field) $search->addSearchField($search_field);

		}

		// Add keywords to search
		if (count($keywords) > 0) {

			// Build list of searchable fields

			$keyword_group = new DAOSearchOrGroup();

			foreach ($keywords as $tableKey => $fields) {

				foreach ($fields as $name => $values) {

					foreach ($values as $value) {

						$keyword_group->addSearchField(new DAOSearchFieldWildcard($tableKey, $name, $value));

					}
				}
			}

			$search->addSearchField($keyword_group);
		}

		// Filter search by type_names (e.g. {WebImage.Node.Types.Content)
		if (count($query->getFilterTypeQNames()) > 0) {
die(__FILE__.':'.__LINE__.PHP_EOL);
			$or = [];

			foreach ($queryFilterTypeQNames as $ix => $typeQName) {
				$paramName = sprintf(':queryfiltertypeqname%d', $ix);
				$or[] = sprintf('n.type_qname = %s', $paramName);
				$qb->setParameter($paramName, $typeQName);
			}

			$qb->andWhere('(' . implode(' OR ', $or) . ')');
		}
die(__FILE__.':'.__LINE__.PHP_EOL);
		foreach ($sorts as $sort) {
			die(__FILE__.':'.__LINE__.PHP_EOL);
			$query_filter = $sort['query_sort'];
			$sort_direction = null;

			if ($query_filter->getSortDirection() == CWI_CNODE_QUERY_Query::SORT_ASC) {

				$sort_direction = DAOSearch::SORT_ASC;

			} else if ($query_filter->getSortDirection() == CWI_CNODE_QUERY_Query::SORT_DESC) {

				$sort_direction = DAOSearch::SORT_DESC;

			}

			$search->addSort($sort['table_key'], $sort['query_sort']->getField(), $sort_direction);
		}

		// Check if we need to paginate results:
		if (null !== $query->getCurrentPage()) $qb->setFirstResult($query->getCurrentPage() * $query->getResultsPerPage() - $query->getResultsPerPage());
		if (null !== $query->getResultsPerPage()) $qb->setMaxResults($query->getResultsPerPage());

//		$results = $qb->execute()->fetchAll(\PDO::FETCH_NAMED);
		$results = $qb->execute()->fetchAll();
echo '<pre>';print_r($results);echo '<hr />' . __FILE__ .':'.__LINE__;exit;
		$nodes = [];

		foreach($results as $result) {

			$node = new Node($result['type_qname']);
			$node->setRepository($this->nodeService->getRepository());

			$nodeRef = new NodeRef($result['uuid'], $result['version'], $result['id']);
			$node->setNodeRef($nodeRef);

			$type = $this->nodeService->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($node->getTypeQName());

			if (null === $type) continue;

			$typeStack = $type->getTypeStack();

			foreach ($typeStack as $stack_type) {

				$typeDef = $stack_type->getDef();

				if (!($typeDef instanceof NodeTypeRef)) continue;

				$propertyDefs = $typeDef->getProperties();

				foreach($propertyDefs as $propertyDef) {

					$propertyTableKey = null; // Defined below
					$propertyKey = $propertyDef->getKey();

					$propertyDataType = $this->nodeService->getRepository()->getDataTypeService()->getDataType($propertyDef->getQName());

					/**
					 * Get Property Definition Original Table
					 */
					$propertyDefTypeQName = $propertyDef->getNodeTypeTypeQName();

					$propertyDefType = $this->nodeService->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($propertyDefTypeQName);

					if (null === $propertyDefType) continue;

					$propertyDefTypeDef = $propertyDefType->getDef();

					// Make sure this is a database definition, because we need the getTableKey() method

					$propertyTableKey = $propertyDefTypeDef->getTableKey();

					/**
					 * Make sure that the table to which this node type's date should be pulled from is available
					 */
//					if (isset($result->$propertyTableKey)) {

						$fields = array();

						if ($propertyDataType->isSimpleStorage()) {

							if (isset($result[$propertyKey])) {

								$property = null;

								if ($propertyDef->isMultiValued()) {
									$property = new MultiValuePropertyValue();
									#if ($default_value = $def->getDefault()) $node_property->addValue($node_property->setValue($def->getDefault()));
								} else {
									$property = new Property();
									$property->setValue($result[$propertyKey]);
								}
								$property->setDef($propertyDef);
								$node->addProperty($propertyKey, $property);

							}

						} else {

							$fields = array();

							$dataTypeModelFields = $propertyDataType->getModelFields();

							$d = new Dictionary();

							foreach($dataTypeModelFields as $dataTypeModelField) {
								$field_name = $propertyKey . '_' . $dataTypeModelField->getName();

								if (isset($result[$field_name])) {
									$d->set($dataTypeModelField->getName(), $result[$field_name]);
								}
							}

							if ($propertyDef->isMultiValued()) {
								$property = new MultiValueProperty(); // CWI_CNODE_NodeMultiProperty();
								#if ($default_value = $def->getDefault()) $node_property->addValue($node_property->setValue($def->getDefault()));
							} else {
								$property = new Property();
								$property->setValue($d);
							}

							$property->setDef($propertyDef);
							$node->addProperty($propertyKey, $property);
						}
//					} /* else: What should we do if the table is not found in the results */
				}
			}

			$nodes[] = $node;
		}

		return $nodes;
	}

	/**
	 * @return array a keyed array, one value for 'tables' and one for 'properties'
	 */
	private function getPropertyDefsForTypeQName(array $objects, array $tables)
	{
		$propertiesInfo = array();
		$columnsInfo = array();

		foreach ($objects as $object) {

			$objectTypeQName = $object->getTypeQName();
			$objectFieldName = $object->getField();

			$useTypeDefs = array();

			// If this value is null then assume we are dealing with a wildcard requested where we want to grab all instances of a field across all NodeType's

			if (null === $objectTypeQName) {

				$all_node_types = $this->getRepository()->getNodeTypeService()->getNodeTypes();

				while ($nodeType = $all_node_types->getNext()) {

					$nodeTypeDef = $nodeType->getDef();

					// Check if property exists in this node type
					if ($property = $nodeTypeDef->getProperty($objectFieldName)) {
						$useTypeDefs[] = $nodeTypeDef;
					}
				}

				//Otherwise, assume a specific type is being referenced for the field inclusion
			} else {

				if ($nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($objectTypeQName)) {
					$nodeTypeDef = $nodeType->getDef();
					$useTypeDefs[] = $nodeTypeDef;
				}
			}

			// Iterate through each of the found type_defs (there will be multiple if $object_type_qname is null, and one if $object_type_qname is defined
			foreach ($useTypeDefs as $use_type_def) {

				if (is_a($use_type_def, 'NodeTypeDef')) {

					$tableKey = $use_type_def->getTableKey();

					// Make sure that the table is included in our listed of queriable tables
					if ($tableKey != 'nodes' && !in_array($tableKey, $tables)) {
						$tables[] = $tableKey;
					}

					// Make sure field is actually part of the type in question
					/** @var NodeTypePropertyDef $propertyDef */
					if ($propertyDef = $use_type_def->getProperty($objectFieldName)) {
						// Only handle single value properties for now

						if (!$propertyDef->isMultiValued()) {

							$columns = array();

							if ($dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getQName())) {

								if ($dataType->isSimpleStorage()) {

									$columnsInfo[] = $propertyDef->getKey();
									$columns[] = $propertyDef->getKey();

								} else {

									$model_fields = $dataType->getModelFields();

									while ($model_field = $model_fields->getNext()) {

										$field_name = $propertyDef->getKey() . '_' . $model_field->getName();

										$columnsInfo[] = [
											'table_key' => $tableKey,
											'object' => $object,
											'field_name' => $field_name
										];
										$columns[] = $field_name;

									}

								}

							}

							$propertiesInfo[] = [
								'table_key' => $tableKey,
								'property_def' => $propertyDef,
								'object' => $object,
								'columns' => $columns
							];
						}
					}
				}
			}
		}

		return array(
			'tables' => $tables,
			'properties_info' => $propertiesInfo,
			'columns_info' => $columnsInfo
		);
	}
}