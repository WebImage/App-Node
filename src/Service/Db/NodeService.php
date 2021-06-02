<?php

namespace WebImage\Node\Service\Db;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Driver\PDOException;
use Exception;
use WebImage\Core\Dictionary;
use WebImage\Db\ConnectionManager;
use WebImage\Node\Defs\DataTypeModelField;
use WebImage\Node\Defs\NodeTypeAssociationDef;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\NodeAssociation;
use WebImage\Node\Entities\Node;
use WebImage\Node\Entities\NodeRefInterface;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Entities\NodeTypeAssociation;
use WebImage\Node\Properties\MultiValueProperty;
use WebImage\Node\Properties\Property;
use WebImage\Node\Query\Query;
use WebImage\Node\Query\QueryBuilder;
use WebImage\Node\Service\NodeServiceInterface;
use WebImage\Node\Service\NodeRef;
use WebImage\Node\Service\QName;
use WebImage\Node\Service\RepositoryAwareTrait;
use WebImage\String\Uuid;

class NodeService implements NodeServiceInterface
{
	use RepositoryAwareTrait, ConnectionManagerTrait;

	const NODE_STATUS_ACTIVE = 'A';
	const NODE_STATUS_DELETED = 'D';

	public function __construct(ConnectionManager $connectionManager)
	{
		$this->setConnectionManager($connectionManager);
	}
	/**
	 * @inheritdoc
	 */
	public function saveAssociation(NodeAssociation $association)
	{
		$src = $association->getSourceNode();
		$srcRef = $src->getNodeRef();
		$tgt = $association->getTargetNode();
		$tgtRef = $tgt->getNodeRef();
		$typeAssoc = $this->getRepository()->getNodeTypeService()->getAssociationByQName($association->getQName());

		$this->assertAssociationAllowed($typeAssoc, $src, $tgt);

		$sortorder = null === $association->getSortOrder() ? $this->getNextAssocSortOrder($association, $srcRef, $tgtRef) : $association->getSortOrder();

		$this->insertRecord('node_associations', [
			'assoc_qname' => $association->getQName(),
			'src_node_uuid' => $srcRef->getUuid(),
			'src_node_version' => $srcRef->getVersion(),
			'tgt_node_uuid' => $tgtRef->getUuid(),
			'tgt_node_version' => $tgtRef->getVersion(),
			'sortorder' => $sortorder
		]);
	}

	private function getNextAssocSortOrder(NodeAssociation $assoc, NodeRefInterface $srcRef, NodeRefInterface $tgtRef)
	{
		$sortorder = $this->getConnectionManager()
			->createQueryBuilder()
			->select('MAX(sortorder) AS sortorder')
			->from('node_associations')
			->where('assoc_qname = :assoc_qname')
			->andWhere('src_node_uuid = :src_node_uuid')
			->andWhere('src_node_version = :src_node_version')
			->andWhere('tgt_node_uuid = :tgt_node_uuid')
			->andWhere('tgt_node_version = :tgt_node_version')
			->setParameters([
				'assoc_qname' => $assoc->getQName(),
				'src_node_uuid' => $srcRef->getUuid(),
				'src_node_version' => $srcRef->getVersion(),
				'tgt_node_uuid' => $tgtRef->getUuid(),
				'tgt_node_version' => $tgtRef->getVersion(),
			])
			->execute()
			->fetch(\PDO::FETCH_COLUMN);

		return empty($sortorder) ? 1 : $sortorder + 1;
	}

	/**
	 * Ensures that the connection between two nodes is allowed
	 *
	 * @param NodeTypeAssociation $assoc
	 * @param Node $src
	 * @param Node $tgt
	 */
	private function assertAssociationAllowed(NodeTypeAssociation $assoc, Node $src, Node $tgt)
	{
		$tgtRef = $tgt->getNodeRef();
		$srcRef = $src->getNodeRef();
		$typeAssocDef = $assoc->getDef();
		$this->assertNodeInstanceOf($src, $typeAssocDef->getSourceTypeQName(), $typeAssocDef->isSourceStrict(), $assoc->getDef()->getQName());
		$this->assertNodeInstanceOf($tgt, $typeAssocDef->getTargetTypeQName(), $typeAssocDef->isTargetStrict(), $assoc->getDef()->getQName());

		if (null === $srcRef) throw new \RuntimeException('Source must be saved before an association can be created');
//		else if (!($srcRef instanceof NodeRefInterface)) throw new \RuntimeException('Source node has not been committed locally');

		if (null === $tgtRef) throw new \RuntimeException('Target must be saved before an association can be created');
//		else if (!($tgtRef instanceof NodeRefInterface)) throw new \RuntimeException('Target node has not been committed locally');

		$assocSrcCount = $this->getSourceAssocCount($src, $typeAssocDef);
		$assocTgtCount = $this->getTargetAssocCount($tgt, $typeAssocDef);
	}

	private function getTargetAssocCount(Node $node, NodeTypeAssociationDef $typeAssocDef) {}

	private function getSourceAssocCount(Node $node, NodeTypeAssociationDef $typeAssocDef)
	{
		$typeQNames = [$node->getTypeQName()];
		$type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($node->getTypeQName());

		if (!$typeAssocDef->isSourceStrict()) {
			$parentTypeQNames = array_map(function(NodeType $type) {
				return $type->getDef()->getQName();
			}, $type->getParents());
			$typeQNames = array_merge($parentTypeQNames, $typeQNames);
		}

		$qb = $this->getConnectionManager()->createQueryBuilder();
		$count = $qb->select('COUNT(*)')
			->from('node_associations', 'a')
			->join('a', 'nodes', 'n', 'n.node_uuid = a.src_node_uuid')
			->join('n', 'node_types', 't', 't.node_uuid = n.node_uuid')
			->where('a.assoc_qname = :assoc_qname AND t.qname IN (:qnames)')
			->setParameter(':assoc_qname', $typeAssocDef->getQName())
			->setParameter(':qnames', $typeQNames, Connection::PARAM_STR_ARRAY)
//		echo $count->getSQL() . '<br>';
//		echo '<pre>';print_r($count->getParameters());echo '<hr />' . __FILE__ .':'.__LINE__;exit;
			->execute()
			->fetch(\PDO::FETCH_COLUMN);
//		echo 'Count: ' . $count;exit;
	}

	/**
	 * Ensure that node is an instance of $typeQName - or any of $typeQNames parents/associations if $isStrict is false
	 *
	 * @param Node $node
	 * @param string $typeQName
	 * @param bool $isStrict
	 *
	 * @throws \RuntimeException
	 */
	public function assertNodeInstanceOf(Node $node, string $typeQName, bool $isStrict, $assocQName)
	{
		$typeService = $this->getRepository()->getNodeTypeService();
		$type = $typeService->getNodeTypeByTypeQName($node->getTypeQName());

		if ($node->getTypeQName() == $typeQName) return;

		$allowedTypeQNames = array_map(function(NodeType $type) {
			return $type->getDef()->getQName();
		}, $type->getTypeStack());

		if (in_array($node->getTypeQName(), $allowedTypeQNames)) return;

		throw new \RuntimeException(sprintf('Nodes of type %s are not allowed in the association %s', $node->getTypeQName(), $assocQName));
	}

	/**
	 * @inheritdoc
	 */
	public function save(Node $node)
	{
		// Make sure that the object being passed contains a correct reference type
		$nodeRef = $node->getNodeRef();

		$new_node = (null === $node->getUuid());

		$nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($node->getTypeQName());

		$types = $nodeType->getParents();

		$types[] = $nodeType;

		// Make sure created as an actual value, otherwise set it to null so that the DataAccessObject will know to auto-set its date
		$updated = new \DateTime();// date('Y-m-d H:i:s');
		$updatedBy = 0;
		$typeService = $this->getRepository()->getNodeTypeService();
		$dataTypeService = $this->getRepository()->getDataTypeService();
		$tableNameHelper = $this->getTableNameHelper();

		$baseType = $tableNameHelper->getRootType($typeService, $types);
		$baseTableKey = $tableNameHelper->getTableKeyFromDef($baseType->getDef());

		if (null === $baseType) throw new \RuntimeException('Unable to find node root table');

		if ($new_node) {

			$node->setPropertyValue('node_uuid', Uuid::v4());
			$node->setPropertyValue('created', $updated);
			$node->setPropertyValue('created_by', $updatedBy);
			$node->setPropertyValue('updated', $updated);
			$node->setPropertyValue('updated_by', $updatedBy);
			$node->setPropertyValue('type_qname', $nodeType->getDef()->getQName());

			$data = [];

			foreach($baseType->getDef()->getProperties() as $key => $property) {
				$dataType = $property->getDataType();
				$value = $node->getPropertyValue($key);
				$value = $dataTypeService->valueForStorage($dataType, $value);
				$data[$key] = $value;
			}

			$this->insertRecord($baseTableKey, $data);

			$nodeRef = new NodeRef($data['node_uuid'], $data['node_version']);
			$node->setNodeRef($nodeRef);

		} else {

			$node->setPropertyValue('updated', $updated);
			$node->setPropertyValue('updated_by', $updatedBy);
			$node->setPropertyValue('type_qname', $nodeType->getDef()->getQName());

			$data = [];
			foreach($baseType->getDef()->getProperties() as $key => $property) {
				$dataType = $property->getDataType();
				$value = $node->getPropertyValue($key);
				$value = $dataTypeService->valueForStorage($dataType, $value);
				$data[$key] = $value;
			}

			$this->updateRecord($baseTableKey, $data, ['node_uuid' => $node->getUuid(), 'node_version' => $node->getVersion()]);
		}

		/** @var NodeType $type */
		foreach ($types as $type) {
			if (!($type->getDef() instanceof NodeTypeRef)) continue;
			if (!$tableNameHelper->shouldDefHavePhysicalTable($type->getDef())) continue;

			$typeDef = $type->getDef();
			$typeQName = $typeDef->getQName();
			$tableKey = $typeDef->getTableKey();
			$properties = $typeDef->getProperties();

			$typeData = [];
			$primaryKeys = [];

			$this->getConnectionManager()->getTableName($tableKey);

			/**
			 * Iterate through properties for this type and attach to model
			 * @var string $propertyKey
			 */
			foreach($properties as $propertyKey => $propertyDef) {

				// Check if this should be considered a primary key
				if (in_array($propertyKey, array('node_uuid', 'node_version'/*, 'profile', 'locale'*/))) {

					$primaryKeys[] = $propertyKey;

					switch ($propertyKey) {
						case 'node_uuid':
							$typeData['node_uuid'] = $nodeRef->getUuid();
							break;
						case 'node_version':
							$typeData['node_version'] = $nodeRef->getVersion();
							break;
					}

				// Otherwise add it to the list of values to update
				} else {
					/** @var Property|MultiValueProperty $property */
					$property = $node->getProperty($propertyKey);

					$propertyType = $propertyDef->getDataType();

					$dataType = $dataTypeService->getDataType($propertyType);
					if (null === $dataType) {
						throw new \RuntimeException('Invalid data type: ' . $propertyType);
					}

					if ($propertyDef->isMultiValued()) {
						die(__FILE__.':'.__LINE__.PHP_EOL);
						$propertyTableKey = $typeDef->getTableKey() . '_p_' . $propertyKey; //'cnt_prop_' . $fieldName;

						$qb = $this->getConnectionManager()->createQueryBuilder();

						$existingValues = $qb->select('*')
							->from($propertyTableKey)
							->where('node_uuid = :node_uuid')
							->setParameters([
								':node_uuid' => $nodeRef->getUuid()
//								':version' => $nodeRef->getVersion()
							])
							->execute()
							->fetchAll();

						// Flag all existing records and mark to be deleted by default
						for($i=0, $j=count($existingValues); $i < $j; $i++) {
							$existingValues[$i]['_keep'] = 0;
						}

						foreach($property->getValues() as $propertyValue) {

							$found = false;

							// Check all existing values to see if we can find a match
							foreach($existingValues as $i => $existingValue) {

								if ($existingValue['_keep'] == 0) { // Make sure that we are only working with records that have not already been marked as keep

									$all_match = true; // Assume that all match, then invalidate

									die(__FILE__.':'.__LINE__.PHP_EOL);
									while ($propertyValueField = $propertyValueFields->getNext()) {

										if ($dataType->isSimpleStorage()) {
											$propertyValueFieldKey = $propertyKey;

										} else {
											$propertyValueFieldKey = $propertyKey . '_' . $propertyValueField->getKey();
											#echo 'Complex check: ' . $propertyValueFieldKey . "\n";
										}

										$propertyValueFieldValue = $propertyValueField->getDef();

										// If the values do not match then there is not point iterating through the rest of the results since $all_match will evaluate to false anyway
										if ($existing_value->$propertyValueFieldKey != $propertyValueFieldValue) {
											$all_match = false;
											$propertyValueFields->resetIndex();
											break;
										}
									}

									if ($all_match) {
										$existing_value->_keep = 1;
										$found = true;
										$query_existing_values->resetIndex();
										break;
									}
								}
							}

							if (!$found) {

//								$qb = $this->getConnectionManager()->createQueryBuilder();
//								$qb->insert($propertyTableKey)
//									->values(['']
								echo 'Update to use convenience methods ->insertRecord and updateRecord<br>';
								die(__FILE__.':'.__LINE__.PHP_EOL);
								$propertyData = [
									'node_uuid' => ':node_uuid',
									'node_version' => ':version'
								];
								$propertyParams = [
									':node_uuid' => $nodeRef->getUuid(),
									':version' => $nodeRef->getVersion()
								];

								if ($dataType->isSimpleStorage()) {

									$value = $propertyValue->get('');
									$propertyData[$propertyKey] = ':field';
									$propertyParams[':field'] = $value;

								} else { // complex, multiple value
									die(__FILE__.':'.__LINE__.PHP_EOL);
									while ($propertyValueField = $propertyValueFields->getNext()) {

										$propertyValueFieldKey = $propertyKey . '_' . $propertyValueField->getKey();
										$propertyValueFieldValue = $propertyValueField->getDef();

										$struct->$propertyValueFieldKey = $propertyValueFieldValue;
										$dao_new->addUpdateField($propertyValueFieldKey);

									}
								}

								$qb = $this->getConnectionManager()->createQueryBuilder();
								$qb->insert($propertyTableKey)
									->values($propertyData)
									->setParameters($propertyParams)
									->execute();
								echo '<pre>';print_r($propertyData);
							}
						}

						// Remove any values that should no longer be associated with the node

						if (count($existingValues) > 0) die(__FILE__.':'.__LINE__.PHP_EOL);

						foreach($existingValues as $existingValue) {
							if ($existingValue['_keep'] == 0) {

								$test = get_object_vars($existingValue);

								$columns = array();
								foreach ($test as $column => $value) {
									if (substr($column, 0, 1) != '_') {
										$columns[] = "`" . $column . "` = '" . $dao_property->safeString($value) . "'";
									}
								}

								if (count($columns) > 0) {
									$sql_delete = "
										DELETE
										FROM `" . $propertyTable . "`
										WHERE 
											" . implode(' AND ', $columns);

									$dao_property->commandQuery($sql_delete);
								}
							}
						}

					} else {

						$value = $dataTypeService->valueForStorage($propertyDef->getDataType(), $property->getValue());

						if ($dataType->isSimpleStorage()) {
							$typeData[$propertyKey] = $value;
						} else {
							foreach($dataType->getModelFields() as $field) {
								$compositeFieldName = $propertyKey . '_' . $field->getKey();
								$typeData[$compositeFieldName] = $value[$field->getKey()];
							}
						}
					}
				}
			}

			// Check if this is a new record so that we can create it if necessary
			$qb = $this->getConnectionManager()->createQueryBuilder();
			$qb->select('COUNT(*) AS total')
				->from($tableKey);

			// Make sure that at a minimum, node_uuid and version are defined as primary keys
			if (count($primaryKeys) == 0) {
				$typeData['node_uuid'] = $nodeRef->getUuid();
				$typeData['node_version'] = $nodeRef->getVersion();
				$primaryKeys = array('node_uuid', 'node_version');
			}

			foreach ($primaryKeys as $primaryKey) {
				$qb->andWhere($primaryKey . ' = :' . $primaryKey);
				$qb->setParameter(':' . $primaryKey, $typeData[$primaryKey]);
			}

			$result = $qb->execute()->fetch();

			if ($result['total'] == 0) {
				$this->insertRecord($tableKey, $typeData);
			} else {
				$where = [];
				foreach($primaryKeys as $primaryKey) {
					$where[$primaryKey] = $typeData[$primaryKey];
				}

				$this->updateRecord($tableKey, $typeData, $where);
			}
		}

		/**
		 * Save any new associations
		 */
		$associations = $node->getAssociations();

		foreach($associations as $association) {
			if ($association->isNew()) {
				$this->createAssociation($association->getQName(), $association->getSourceNode(), $association->getDestinationNode());
			}
		}

		// Let other objects know that the Node has been saved
//		$event_args = new CWI_CNODE_EVENT_NodeServiceSavingEventArgs($node);
//		CWI_EVENT_Manager::trigger($this, 'saved', $event_args);

		return $node;
	}

	/**
	 * @inheritdoc
	 */
	public function delete(Node $node)
	{
		$qb = $this->getConnectionManager()->createQueryBuilder();

		$qb->update('nodes')
			->set('status', ':status')
			->where('node_uuid = :uuid')
			->setParameter(':status', self::NODE_STATUS_DELETED)
			->setParameter(':uuid', $node->getNodeRef()->getUuid())
			->execute();
	}

	/**
	 * @inheritdoc
	 */
	public function create($qname)
	{
		$nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($qname);
		if (null === $nodeType) throw new Exception('Unable to locate type ' . $qname);
		$node = new Node($qname);
		$node->setRepository($this->getRepository());

		$types = $nodeType->getParents();
		$types[] = $nodeType;
		/**
		 * Add properties from type and parents
		 * @var NodeType $type
		 */
		foreach ($types as $type) {
			$properties = $type->getDef()->getProperties();
			/**
			 * @var string $fieldName
			 * @var NodeTypePropertyDef $def
			 */
			foreach($properties as $fieldName => $def) {

				if ($def->isMultiValued()) {
					$nodeProperty = new MultiValueProperty();
					if (null !== $def->getDefault()) $nodeProperty->addValue($def->getDefault());
				} else {
					$nodeProperty = new Property();
					$nodeProperty->setValue($def->getDefault());
				}

				$nodeProperty->setDef($def);
				$node->addProperty($fieldName, $nodeProperty);
			}
		}

		return $node;
	}


	public function export($uuid)
	{
	}

	/**
	 * This is the lazy way to do this, but it will suffice for now.  It needs to be made more effecient by querying all Nodes in one operation, rather than individually
	 * @return Dictionary
	 */
	public function getNodesByUuids(array $uuids)
	{
		/** @var Node[] $nodes */
		$nodes = new Dictionary(); // [uuid] => Node

		$qb = $this->getConnectionManager()->createQueryBuilder();

		$nodesData = $qb->select('*')
			->from('nodes')
			->where('node_uuid IN (:uuid) AND status = :status')
			->setParameter(':uuid', $uuids, Connection::PARAM_STR_ARRAY)
			->setParameter(':status', self::NODE_STATUS_ACTIVE)
			->execute()
			->fetchAll();

		$allTypeQNames = new Dictionary();

		foreach($nodesData as $nodeData) {

			// Build Node
			$node = new Node($nodeData['type_qname']);
			$nodeRef = new NodeRef($nodeData['node_uuid'], $nodeData['node_version']);
			$node->setNodeRef($nodeRef);
			$node->setRepository($this->getRepository());

			$nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($nodeData['type_qname']);

			/** @var NodeType[] $typeStack */
			$typeStack = $nodeType->getTypeStack();

			if (count($typeStack) == 0) continue;

			/** @var Nodetype $type */
			$typeQName = $nodeType->getDef()->getQName();

			if (!$allTypeQNames->has($typeQName)) {
				$allTypeQNames->set($typeQName, $typeStack);
			}

			$nodes->set($node->getNodeRef()->getUuid(), $node);
		}

		$this->addPropertiesToNode($nodes, $allTypeQNames);

		return $nodes;
	}

	/**
	 * Add properties
	 *
	 * @param Dictionary $nodes Dictionary<string, Node>
	 * @param Dictionary $allTypeQNames<string, NodeType[]>
	 */
	private function addPropertiesToNode(Dictionary $nodes, Dictionary $allTypeQNames)
	{
		/**
		 * Iterate through primary node types and build query
		 * @var string $typeQName
		 * @var NodeType[] $typeStack
		 */
		foreach ($allTypeQNames as $typeQName => $typeStack) {
			$this->addSimpleNodeProperties($nodes, $typeStack);
			$this->addMultiNodeProperties($nodes, $typeStack);
		}
	}

	/**
	 * @param Dictionary $nodes
	 * @param array $typeStack
	 *
	 * @todo Convert multi properties to be lazy loading?
	 */
	private function addMultiNodeProperties(Dictionary $nodes, array $typeStack)
	{
		$uuids = $nodes->keys();

		/** @var NodeType $type */
		foreach($typeStack as $type) {
			$typeDef = $type->getDef();
			$propertyDefs = $typeDef->getProperties();

			/** @var NodeTypePropertyDef $propertyDef */
			foreach($propertyDefs as $propertyDef) {
				if (!$propertyDef->isMultiValued()) continue;

				$propertyTableKey = $typeDef->getTableKey() . '_p_' . $propertyDef->getKey();
				$dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getDataType());

				$qb = $this->getConnectionManager()
					->createQueryBuilder()
					->select('node_uuid')
					->from($propertyTableKey)
					->where('node_uuid IN (:uuid)')
					->setParameter(':uuid', $uuids, Connection::PARAM_STR_ARRAY);

				$propertyKey = $propertyDef->getKey();

				if ($dataType->isSimpleStorage()) {
					$qb->addSelect($propertyKey);
				} else {
					foreach($dataType->getModelFields() as $modelField) {
						$fieldKey = $propertyKey . '_' . $modelField->getKey();
						$qb->addSelect($fieldKey);
					}
				}

				$results = $qb->execute()->fetchAll();

				foreach($results as $result) {
					$uuid = $result['node_uuid'];

					/** @var Node $node */
					$node = $nodes->get($uuid);
					$propertyKey = $propertyDef->getKey();
					/** @var MultiValueProperty $property */
					$property = $node->getProperty($propertyKey);

					if ($dataType->isSimpleStorage()) {
						$property->addValue($result[$propertyKey]);
					} else {
						die(__FILE__.':'.__LINE__.PHP_EOL);
						$d = new Dictionary();
						foreach($dataType->getModelFields() as $modelField) {
							$fieldKey = $propertyKey . '_' . $modelField->getKey();
							$d->set($modelField->getKey(), $result[$fieldKey]);
						}
						$property->addValue($d);
					}
				}
			}
		}
	}

	/**
	 * Add primary properties that are related to non multi-value property
	 *
	 * @param Dictionary $nodes Dictionary<string, Node>
	 * @param Type[] $typeStack
	 */
	private function addSimpleNodeProperties(Dictionary $nodes, array $typeStack)
	{
		$nodeUuids = $nodes->keys();

		$results = $this->getNodeDataResultsForTypes($nodeUuids, $typeStack);
		$typeService = $this->getRepository()->getNodeTypeService();
		$dataTypeService = $this->getRepository()->getDataTypeService();
		$tableNameHelper = $this->getTableNameHelper();
		$rootType = $tableNameHelper->getRootType($typeService, $typeStack);
		$rootTableKey = $tableNameHelper->getTableKeyFromDef($rootType->getDef());
		$uuidAlias = $tableNameHelper->getColumnNameAlias($rootTableKey, 'node_uuid');
		$resultHelper = $this->getResultHelper();

		foreach($results as $result) {

			$uuid = $result[$uuidAlias];
			/** @var Node $node */
			$node = $nodes->get($uuid);

			/** @var NodeType $type */
			foreach($typeStack as $type) {

				/** @var NodeTypeDef|NodeTypeRef $typeDef */
				$typeDef = $type->getDef();
				if (!$tableNameHelper->shouldDefHavePhysicalTable($typeDef)) continue; // No sense in trying to get values from a table that does not exist
				$typeTableKey = $tableNameHelper->getTableKeyFromDef($typeDef);

				$properties = $typeDef->getProperties();

				/** @var NodeTypePropertyDef $propertyDef */
				foreach($properties as $propertyDef) {
					$property = $resultHelper->createPropertyFromData($typeTableKey, $propertyDef, $result);
					$node->addProperty($propertyDef->getKey(), $property);
				}
			}
		}
	}

	/**
	 * Build and execute query to return node data by type
	 *
	 * @param string[] $nodeUuids
	 * @param NodeType[] $typeStack
	 *
	 * @throws \Exception If invalid property type is discovered
	 *
	 * @return array
	 */
	private function getNodeDataResultsForTypes(array $nodeUuids, array $typeStack)
	{
		$cm = $this->getConnectionManager();
		$qb = $cm->createQueryBuilder();
		$anyProps = false;
		$isRoot = true;
		$rootTableKey = null;
		$tableNameHelper = $this->getTableNameHelper();
		/**
		 * Build primary type query
		 * @var int $ix
		 * @var NodeType $type
		 */
		foreach ($typeStack as $ix => $type) {

			/** @var NodeTypeDef|NodeTypeRef $typeDef */
			$typeDef = $type->getDef();
			$tableKey = $tableNameHelper->getTableKeyFromDef($typeDef);

			$props = $typeDef->getProperties();

			if (!$tableNameHelper->shouldDefHavePhysicalTable($typeDef)) continue;
			if (count($props) > 0) $anyProps = true;

			$tableName = $cm->getTableName($tableKey);

			$uuidColumn = $tableNameHelper->getColumnName($tableKey, 'node_uuid');
			$uuidAlias = $tableNameHelper->getColumnNameAlias($tableKey, 'node_uuid');
			$versionColumn = $tableNameHelper->getColumnName($tableKey, 'node_version');

			if ($isRoot) {
				$qb->select(sprintf('%s AS %s', $uuidColumn, $uuidAlias));
				$qb->from($tableName, $tableKey);
				$qb->where($uuidColumn . ' IN (:uuid)');
				$qb->setParameter(':uuid', $nodeUuids, Connection::PARAM_STR_ARRAY);
				$rootTableKey = $tableKey;
				$isRoot = false;
			} else {
				$rootUuidColumn = $tableNameHelper->getColumnName($rootTableKey, 'node_uuid');
				$rootVersionColumn = $tableNameHelper->getColumnName($rootTableKey, 'node_version');
				$qb->leftJoin(
					$rootTableKey, //$typeStack[0]->getDef()->getTableKey(),
					$tableName, //$typeDef->getTableKey(),
					$tableKey, //$typeDef->getTableKey(),
					$uuidColumn . ' = ' . $rootUuidColumn . ' AND ' . $versionColumn . ' = ' . $rootVersionColumn
				);
			}
			/** @var NodeTypePropertyDef $propertyDef */
			foreach ($props as $propertyDef) {
				if ($propertyDef->isMultiValued()) continue;

				$propDataType = $this->getRepository()->getDictionaryService()->getDataType($propertyDef->getDataType());
				if (null === $propDataType) throw new \Exception(sprintf('Invalid property type: %s', $propertyDef->getDataType()));

				$modelFields = $propDataType->getModelFields();
				/** @var DataTypeModelField $modelField */
				foreach ($modelFields as $modelField) {
					$selectField = $tableNameHelper->getColumnName(
						$tableKey,
						$propertyDef->getKey(),
						$propDataType->isSimpleStorage() ? null : $modelField->getKey()
						);
					$selectAlias = $tableNameHelper->getColumnNameAlias(
						$tableKey,
						$propertyDef->getKey(),
						$propDataType->isSimpleStorage() ? null : $modelField->getKey()
						);

					$qb->addSelect(sprintf('%s AS %s', $selectField, $selectAlias));
				}
			}
		}

		return $anyProps ? $qb->execute()->fetchAll() : [];
	}

	/**
	 * Get a node by its UUID
	 *
	 * @param string $uuid
	 * @param string $version - not actually used at this time - included for forward compatability
	 *
	 * @return void|Node
	 *
	 * @throws Exception
	 */
	public function getNodeByUuid($uuid, $version = null)
	{
		if (null !== $version) throw new \Exception('version not yet supported');

		$nodes = $this->getNodesByUuids([$uuid]);

		return $nodes->get($uuid);
	}

	public function getAssociationDef($assocTypeQNname)
	{
		// Create the database entry
		if ($assoc_def_struct = NodeAssociationLogic::getNodeAssociationDef($assocTypeQNname)) {

			// Create the association def object
			$association_def = new CWI_CNODE_DICTIONARY_NodeAssociationDef(
				$assoc_def_struct->name,
				$assoc_def_struct->assoc_type_qname,
				$assoc_def_struct->allow_duplicates,
				$assoc_def_struct->dst_has_many,
				$assoc_def_struct->dst_required,
				$assoc_def_struct->dst_strict,
				$assoc_def_struct->src_has_many,
				$assoc_def_struct->src_required,
				$assoc_def_struct->src_strict);
		} else {
			throw new Exception('Unable to find association def for ' . $assocTypeQNname);
		}

		return $association_def;
	}

	/**
	 * @inheritdoc
	 */
	public function createAssociation(string $assocQName, Node $srcNode, Node $tgtNode)
	{
		$srcRef = $srcNode->getNodeRef();
		$tgtRef = $tgtNode->getNodeRef();

		if (!($srcRef instanceof NodeRefInterface) || !($tgtRef instanceof NodeRefInterface)) return;

		$assocDef = $this->getRepository()->getNodeTypeService()->getAssociationByQName($assocQName);
		if (null === $assocDef) throw new \RuntimeException(sprintf('Invalid association type: %s', $assocQName));

		$association = new NodeAssociation($assocQName, $srcNode, $tgtNode);
		$association->setRepository($this->getRepository());

		// Check if the association already exists, if not create it
//		if (!$assoc_struct = NodeAssociationLogic::getNodeAssociation($assocQName, $srcRef->getUuid(), $srcRef->getNodeVersion(), $dstRef->getUuid(), $dstRef->getNodeVersion())) {
//			$assoc_struct = NodeAssociationLogic::createNodeAssociation(
//				$assocQName,
//				$srcRef->getUuid(),
//				$srcRef->getNodeVersion(),
//				$dstRef->getUuid(),
//				$dstRef->getNodeVersion()
//			);
//		}
//
		return $association;
	}

	/**
	 * Removes a node association
	 *
	 * @return void
	 */
	public function removeAssociation(string $assocQName, Node $src, Node $tgt, int $sortorder=null)
	{
		$srcRef = $src->getNodeRef();
		$tgtRef = $tgt->getNodeRef();

		$where = [
			'assoc_qname' => $assocQName,
			'src_node_uuid' => $srcRef->getUuid(),
			'src_node_version' => $srcRef->getVersion(),
			'tgt_node_uuid' => $tgtRef->getUuid(),
			'tgt_node_version' => $tgtRef->getVersion()
		];

		if (null !== $sortorder) $where['sortorder'] = $sortorder;

		$this->deleteRecord('node_associations', $where);
	}

//	public function getAssociationsNodeRefsByAssociationTypeQName($node, $assocTypeQNname) {}

	/**
	 * @return NodeRefInterface[]
	 */
	public function getAssociatedNodeRefs(Node $node, $assocQName = null)
	{
		$ref = $node->getNodeRef();

		$nodeRefsData = $this->getConnectionManager()
			->createQueryBuilder()
			->select('n.node_uuid, n.node_version')
			->from('node_associations', 'a')
			->join('a', 'nodes', 'n', 'n.node_uuid = a.tgt_node_uuid AND n.node_version = a.tgt_node_version')
			->where('a.src_node_uuid = :uuid AND a.src_node_version = :version')
			->setParameter(':uuid', $ref->getUuid())
			->setParameter(':version', $ref->getVersion())
			->execute()
			->fetchAll();

		return array_map(function($data) {
			return new NodeRef($data['node_uuid'], $data['node_version']);
		}, $nodeRefsData);
	}

	/**
	 * @inheritdoc
	 */
	public function query(Query $query)
	{
		$queryService = new NodeQueryService($this);

		return $queryService->query($query);
	}

	/**
	 * @inheritdoc
	 */
	public function createQueryBuilder()
	{
		return new QueryBuilder($this);
	}

	/**
	 * Exports a Node to an CWI_XML_Traversal object
	 * This will export CWI_XML_Traversal and also add any requirements to the $exporter object
	 * @return CWI_XML_Traversal An XML [object] representation of a Node
	 */
	public function exportNode(CWI_CNODE_SERVICE_TRANSFER_Exporter $exporter, $uuid, $version = null)
	{

		$node = self::getNodeByUuid($uuid);

		if (null === $node) throw new Exception('Invalid UUID ' . $uuid);

		// Let the exporter know that we will be exporting a Node with the following UUID so that sub requests to things like associations and properties do not try to re-include a self referencing Node, which would result in a circular loop
		$exporter->addRequiredNode($node->getNodeRef()->getUuid(), $node->getNodeRef()->getNodeVersion());
		// Export the Node XML object (CWI_XML_Traversal)

		$xml_traversal = $node->export($exporter);

		// Finally, update the exporter with the returned XML Object
		$exporter->setNodeXmlTraversal($node->getUuid(), $node->getNodeRef()->getNodeVersion(), $xml_traversal);

		return $xml_traversal;
	}

	/**
	 * Exports a NodeAssociationDef to an CWI_XML_Traversal object
	 * This will export CWI_XML_Traversal and also add any requirements to the $exporter object
	 * @return CWI_XML_Traversal An XML [object] representation of a NodeAssociationDef
	 */
	public function exportAssociationDef(CWI_CNODE_SERVICE_TRANSFER_Exporter $exporter, $assocQName)
	{

		$node_association_def = $this->getNodeAssociationDef($assocQName);
		// Let the exporter know that we will be exporting a NodeAsociationDef so that sub reqeusts do not try to include this node association def multiple times
		$exporter->addRequiredNodeAssociationDef($node_association_def->getQName(), 1);
		// Export Node Association XML object (CWI_XML_Traversal)
		$xml_traversal = $node_association_def->export($exporter);
		// Update the exporter with the returned XML object
		$exporter->setNodeAssociationDefXmlTraversal($node_association_def->getQName(), 1, $xml_traversal);

		return $xml_traversal;

	}

	/**
	 * Exports all node associations for a Node
	 * @return CWI_XML_Traversal
	 */
	public function exportAssociationsForNode(CWI_CNODE_SERVICE_TRANSFER_Exporter $exporter, $uuid, $version)
	{
		//Let the exporter know that we will be expoerting a NodeAssociationDef so that sub requests do not try to include this node association multiple times

		$exporter->addRequiredNodeAssociation($uuid, $version);

		$node = $this->getNodeByUuid($uuid, $version);
		#$refs = $this->getAssociatedNodeRefs($node, '{http://www.ivn.us/nodeassociation}assoc-placement');
		#$refs = $this->getAssociatedNodeRefs($node);

		$rs_associations = NodeAssociationLogic::getNodeAssociations(null, $node->getNodeRef()->getUuid(), $node->getNodeRef()->getNodeVersion());

		$xml_association_group = new CWI_XML_Traversal('associationGroup');

		while ($association = $rs_associations->getNext()) {

			$src_node_ref = new NodeRef($association->src_node_uuid, $association->src_node_version, $association->src_node_uuid);
			$tgt_node_ref = new NodeRef($association->tgt_node_uuid, $association->tgt_node_version, $association->tgt_node_uuid);
			#$return->add($nodeRef);

			$xml_source = new CWI_XML_Traversal('source');
			$xml_src_node = $src_node_ref->export($exporter);
			$xml_source->addChild($xml_src_node);

			$xml_destination = new CWI_XML_Traversal('destination');
			$xml_tgt_node = $tgt_node_ref->export($exporter);
			$xml_destination->addChild($xml_tgt_node);

			$xml_association = new CWI_XML_Traversal('association');
			$xml_association->addChild(new CWI_XML_Traversal('assocQName', $association->assoc_type_qname));
			$xml_association->addChild($xml_source);
			$xml_association->addChild($xml_destination);

			// Add required Association Def and destination Nodes
			$exporter->addRequiredNodeAssociationDef($association->assoc_type_qname);
			$exporter->addRequiredNode($tgt_node_ref->getUuid(), $tgt_node_ref->getNodeVersion());

			$xml_association_group->addChild($xml_association);
		}

		$exporter->setNodeAssociationXmlTraversal($uuid, $version, $xml_association_group);

		return $xml_association_group;
	}

	private function getTableNameHelper(): TableNameHelper
	{
		return new TableNameHelper();
	}

	public function getResultHelper(): ResultHelper
	{
		return new ResultHelper(
			$this->getRepository()->getDataTypeService(),
			$this->getTableNameHelper()
		);
	}
}