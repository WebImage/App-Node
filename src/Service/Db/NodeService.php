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
			'src_node_id' => $srcRef->getNodeId(),
			'src_node_version' => $srcRef->getVersion(),
			'tgt_node_id' => $tgtRef->getNodeId(),
			'tgt_node_version' => $tgtRef->getVersion(),
			'sortorder' => $sortorder
		]);
	}

	private function getNextAssocSortOrder(NodeAssociation $assoc, NodeRef $srcRef, NodeRef $tgtRef)
	{
		$sortorder = $this->getConnectionManager()
			->createQueryBuilder()
			->select('MAX(sortorder) AS sortorder')
			->from('node_associations')
			->where('assoc_qname = :assoc_qname')
			->andWhere('src_node_id = :src_node_id')
			->andWhere('src_node_version = :src_node_version')
			->andWhere('tgt_node_id = :tgt_node_id')
			->andWhere('tgt_node_version = :tgt_node_version')
			->setParameters([
				'assoc_qname' => $assoc->getQName(),
				'src_node_id' => $srcRef->getNodeId(),
				'src_node_version' => $srcRef->getVersion(),
				'tgt_node_id' => $tgtRef->getNodeId(),
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
		else if (!($srcRef instanceof NodeRef)) throw new \RuntimeException('Source node has not been committed locally');

		if (null === $tgtRef) throw new \RuntimeException('Target must be saved before an association can be created');
		else if (!($tgtRef instanceof NodeRef)) throw new \RuntimeException('Target node has not been committed locally');

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
			->join('a', 'nodes', 'n', 'n.node_id = a.src_node_id')
			->join('n', 'node_types', 't', 't.node_id = n.node_id')
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

	public function saveNodeRef($typeQName, NodeRefInterface $nodeRef)
	{
		throw new \Exception(sprintf('%s not yet implemented', __METHOD__));

		$odeStruct = new NodeStruct();
		$odeStruct->type_qname = $typeQName;
		$odeStruct->uuid = $nodeRef->getUuid();
		$odeStruct->version = $nodeRef->getNodeVersion();
		NodeLogic::save($odeStruct);

		#$nodeRef = new NodeRef($node_struct->uuid, $node_struct->version, $node_struct->node_id);
		// Update any required values from the save operation
		$nodeRef->setNodeId($odeStruct->node_id);

		return $nodeRef;
	}

	/**
	 * @inheritdoc
	 */
	public function save(Node $node)
	{
		// Make sure that the object being passed contains a correct reference type
		$nodeRef = $node->getNodeRef();

		// Make sure that locale always contains a value
//		if ($locale_property = $node->getProperty('locale')) {
//			if (strlen($locale_property->getValue()) == 0) $locale_property->setValue('<all>');
//		}

		// Make sure that profile always contains a value
//		if ($profile_property = $node->getProperty('profile')) {
//			if (strlen($profile_property->getValue()) == 0) $profile_property->setValue('<all>');
//		}

		if (null !== $nodeRef && !($nodeRef instanceof NodeRef)) {
			throw new Exception(get_class($nodeRef) . ' is currently not a supported node reference');
		}

		$new_node = (null === $node->getUuid());

		$nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($node->getTypeQName());

		$types = $nodeType->getParents();

		$types[] = $nodeType;
//		array_shift($types); // Remove base: nodes

		$createdBy = $node->getPropertyValue('created_by');
		$created = $node->getPropertyValue('created');

		// Make sure created as an actual value, otherwise set it to null so that the DataAccessObject will know to auto-set its date
		if (empty($created)) $created = null;

		if ($new_node) {

			$qb = $this->getConnectionManager()->createQueryBuilder();
echo 'Name: ' . $node->getPropertyValue('name') . '<br>';die(__FILE__.':'.__LINE__.PHP_EOL);

			$data = [
				'type_qname' => $nodeType->getDef()->getQName(),
				'created' => $created,
				'created_by' => $createdBy,
				'uuid' => Uuid::v4(),
				'version' => 1
			];

			$qb->insert('nodes')
				->values(array_map(function() { return '?'; }, $data))
				->setParameters(array_values($data))
				->execute();

			$nodeRef = new NodeRef($data['uuid'], $data['version'], $qb->getConnection()->lastInsertId());
			$node->setNodeRef($nodeRef);

		} else {
			die(__FILE__.':'.__LINE__.PHP_EOL);
			$node_struct = NodeLogic::getNodeByUuid($node->getUuid());
			$node_struct->created = $created;
			$node_struct->created_by = $createdBy;

			NodeLogic::save($node_struct);
		}

		/** @var NodeType $type */
		foreach ($types as $type) {
			if (!($type->getDef() instanceof NodeTypeRef)) continue;

			$typeDef = $type->getDef();
			$typeQName = $typeDef->getQName();
			$tableKey = $typeDef->getTableKey();
			$properties = $typeDef->getProperties();

			#if (!$model = CWI_MANAGER_ModelManager::getModel($typeDef->getTableKey(), false, false)) throw new Exception('Unable to locate model for ' . $typeDef->getTableKey());
			#$model_fields = $model->getFields();
			$typeData = [];
			$primaryKeys = [];

			$this->getConnectionManager()->getTableName($tableKey);

			// Iterate through properties for this type and attach to model
			/**
			 * @var string $fieldName
			 */
			foreach($properties as $fieldName => $propertyDef) {

				// Check if this should be considered a primary key
				if (in_array($fieldName, array('node_id', 'version'/*, 'profile', 'locale'*/))) {

					$primaryKeys[] = $fieldName;

					switch ($fieldName) {
						case 'node_id':
							$typeData['node_id'] = $nodeRef->getNodeId();
							break;
						case 'version':
							$typeData['version'] = $nodeRef->getVersion();
							break;
//						case 'profile': // Not currently supported, so just assume that it applies to <all>
//							if ($property_profile = $node->getProperty('profile')) $typeData['profile'] = $property_profile->getValue();
//							break;
//						case 'locale': // Not currently supported, so just assume that it applies to <all>
//							if ($property_locale = $node->getProperty('locale')) $typeData['locale'] = $property_locale->getValue();
//							break;
					}

				// Otherwise add it to the list of values to update
				} else {
					/** @var Property|MultiValueProperty $property */
					$property = $node->getProperty($fieldName);

					$propertyType = $propertyDef->getQName();

					$dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyType);
					if (null === $dataType) {
						throw new \RuntimeException('Invalid data type: ' . $propertyType);
					}

					$dataTypeModelFields = $dataType->getModelFields();

					$simpleType = $dataType->isSimpleStorage();

					if ($propertyDef->isMultiValued()) {
						// Do something
						// $typeData[$fieldName] = $node_property->getValues();

//						$propertyTableKey = 'cnt_prop_' . $fieldName;
						$propertyTableKey = $typeDef->getTableKey() . '_p_' . $fieldName; //'cnt_prop_' . $fieldName;

						$qb = $this->getConnectionManager()->createQueryBuilder();

						$existingValues = $qb->select('*')
							->from($propertyTableKey)
							->where('node_id = :nodeid')
							->setParameters([
								':nodeid' => $nodeRef->getNodeId()
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

										if ($simpleType) {
											$propertyValueFieldKey = $fieldName;

										} else {
											$propertyValueFieldKey = $fieldName . '_' . $propertyValueField->getKey();
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
								$propertyData = [
									'node_id' => ':nodeid',
									'node_version' => ':version'
								];
								$propertyParams = [
									':nodeid' => $nodeRef->getNodeId(),
									':version' => $nodeRef->getVersion()
								];

								if ($simpleType) {

									$value = $propertyValue->get('');
									$propertyData[$fieldName] = ':field';
									$propertyParams[':field'] = $value;

								} else { // complex, multiple value
die(__FILE__.':'.__LINE__.PHP_EOL);
									while ($propertyValueField = $propertyValueFields->getNext()) {

										$propertyValueFieldKey = $fieldName . '_' . $propertyValueField->getKey();
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

						if ($simpleType) {

							$typeData[$fieldName] = $property->getValue();

						} else {

							$complexPropertyValue = $property->getValueDictionary();

							foreach($dataTypeModelFields as $dataTypeModelField) {

								$compositeFieldName = $fieldName . '_' . $dataTypeModelField->getName();

								$typeData[$compositeFieldName] = $complexPropertyValue->get($dataTypeModelField->getName());
							}
						}
					}
				}
			}

			// Check if this is a new record so that we can create it if necessary
			$qb = $this->getConnectionManager()->createQueryBuilder();
			$qb->select('COUNT(*) AS total')
				->from($tableKey);

			// Make sure that at a minimum, node_id and version are defined as primary keys
			if (count($primaryKeys) == 0) {
				$typeData['node_id'] = $nodeRef->getNodeId();
				$typeData['node_version'] = $nodeRef->getVersion();
				$primaryKeys = array('node_id', 'node_version');
			}

			foreach ($primaryKeys as $primaryKey) {
				$qb->andWhere($primaryKey . ' = :' . $primaryKey);
				$qb->setParameter(':' . $primaryKey, $typeData[$primaryKey]);
			}

			$result = $qb->execute()->fetch();

			if ($result['total'] == 0) {
				$this->getConnectionManager()
					->createQueryBuilder()
					->insert($tableKey)
					->values(array_map(function() { return '?'; }, $typeData))
					->setParameters(array_values($typeData))
					->execute();
			} else {
				$updateQb = $this->getConnectionManager()
					->createQueryBuilder()
					->update($tableKey)
					->values(array_map(function() { return '?'; }, $typeData))
					->setParameters(array_values($typeData));
				foreach($primaryKeys as $primaryKey) {
					$qb->andWhere($primaryKey . ' = : ' . $primaryKey);
					$qb->setParameter(':' . $primaryKey, $typeData[$primaryKey]);
				}
				$updateQb->execute();
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
			->where('uuid = :uuid')
			->setParameter(':status', self::NODE_STATUS_DELETED)
			->setParameter(':uuid', $node->getNodeRef()->getUuid())
			->execute();
	}

	private function _getNodeIdValue($node)
	{
		return $node->getNodeRef()->getNodeId();
	}

	private function _getDateValue($node, $format)
	{
		$created = strtotime($node->getPropertyValue('created'));
		return CWI_STRING_Helper::strToSefKey(date($format, $created));
	}

	private function _getNodeTitleValue($node)
	{
		return CWI_STRING_Helper::strToSefKey($node->getPropertyValue('title'));
	}

	private function _getNodeTypeNameValue($nodeType)
	{
		return CWI_STRING_Helper::strToSefKey($nodeType->getDef()->getName());
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
					$node_property = new MultiValueProperty();
					if (null !== $def->getDefault()) $node_property->addValue($def->getDefault());
				} else {
					$node_property = new Property();
					$node_property->setValue($def->getDefault());
				}

				$node_property->setDef($def);
				$node->addProperty($fieldName, $node_property);
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
		$nodes = new Dictionary(); // [uuid] => Node
		$nodeUuidLookup = new Dictionary(); // [nodeId] => uuid

		$qb = $this->getConnectionManager()->createQueryBuilder();

		$nodesData = $qb->select('*')
			->from('nodes')
			->where('uuid IN (:uuid) AND status = :status')
			->setParameter(':uuid', $uuids, Connection::PARAM_STR_ARRAY)
			->setParameter(':status', self::NODE_STATUS_ACTIVE)
			->execute()
			->fetchAll();

		$allTypeQNames = new Dictionary();

		foreach($nodesData as $nodeData) {

			// Build Node
			$node = new Node($nodeData['type_qname']);
			$nodeRef = new NodeRef($nodeData['uuid'], $nodeData['version'], $nodeData['node_id']);
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
			$nodeUuidLookup->set(
				$node->getNodeRef()->getNodeId(),
				$node->getNodeRef()->getUuid()
			);

			///////////// CONTINUE HERE //////////////
		}

		$this->addPropertiesToNode($nodes, $nodeUuidLookup, $allTypeQNames);

		return $nodes;
	}

	/**
	 * Add properties
	 *
	 * @param Dictionary $nodes Dictionary<string, Node>
	 * @param Dictionary $allTypeQNames<string, NodeType[]>
	 */
	private function addPropertiesToNode(Dictionary $nodes, Dictionary $nodeUuidLookup, Dictionary $allTypeQNames)
	{
		/**
		 * Iterate through primary node types and build query
		 * @var string $typeQName
		 * @var NodeType[] $typeStack
		 */
		try {
			foreach ($allTypeQNames as $typeQName => $typeStack) {
				echo 'TYPE: ' . $typeQName . '<br>';
				$this->addSimpleNodeProperties($nodes, $nodeUuidLookup, $typeStack);
				$this->addMultiNodeProperties($nodes, $nodeUuidLookup, $typeStack);
			}
		} catch (\PDOException $e) {
			die(__FILE__ . ':' . __LINE__ . PHP_EOL);
		} catch (PDOException $e) {
			die(__FILE__.':'.__LINE__.PHP_EOL);
		} catch (\Doctrine\DBAL\Exception\SyntaxErrorException $e) {
			foreach($e->getTrace() as $data) {
				if (!isset($count)) $count = 0;
				echo ++$count . ') ' . $data['file'] . ':' . $data['line'] . '<br>';
			}
			die(__FILE__.':'.__LINE__.PHP_EOL);
		}
	}

	/**
	 * @param Dictionary $nodes
	 * @param Dictionary $nodeUuidLookup
	 * @param array $typeStack
	 *
	 * @todo Convert multi properties to be lazy loading?
	 */
	private function addMultiNodeProperties(Dictionary $nodes, Dictionary $nodeUuidLookup, array $typeStack)
	{
		$nodeIds = $nodeUuidLookup->keys();

		/** @var NodeType $type */
		foreach($typeStack as $type) {
			$typeDef = $type->getDef();
			$propertyDefs = $typeDef->getProperties();

			/** @var NodeTypePropertyDef $propertyDef */
			foreach($propertyDefs as $propertyDef) {
				if (!$propertyDef->isMultiValued()) continue;

				$propertyTableKey = $typeDef->getTableKey() . '_p_' . $propertyDef->getKey();
				$dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getQName());

				$qb = $this->getConnectionManager()
					->createQueryBuilder()
					->select('node_id')
					->from($propertyTableKey)
					->where('node_id IN (:nodeid)')
					->setParameter(':nodeid', $nodeIds, Connection::PARAM_INT_ARRAY);

				$propertyKey = $propertyDef->getKey();

				if ($dataType->isSimpleStorage()) {
					$qb->addSelect($propertyKey);
				} else {
					foreach($dataType->getModelFields() as $modelField) {
						$fieldKey = $propertyKey . '_' . $modelField->getName();
						$qb->addSelect($fieldKey);
					}
				}

				$results = $qb->execute()->fetchAll();

				foreach($results as $result) {
					$uuid = $nodeUuidLookup->get($result['node_id']);

					/** @var Node $node */
					$node = $nodes->get($uuid);
					$propertyKey = $propertyDef->getKey();
					/** @var MultiValueProperty $property */
					$property = $node->getProperty($propertyKey);

					if ($dataType->isSimpleStorage()) {
						$property->addValue($result[$propertyKey]);
					} else {
						$d = new Dictionary();
						foreach($dataType->getModelFields() as $modelField) {
							$fieldKey = $propertyKey . '_' . $modelField->getName();
							$d->set($modelField->getName(), $result[$fieldKey]);
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
	 * @param Dictionary $nodeUuidLookup Dictionary<id:int, uuid:string>
	 * @param Type[] $typeStack
	 */
	private function addSimpleNodeProperties(Dictionary $nodes, Dictionary $nodeUuidLookup, array $typeStack)
	{
		$nodeIds = $this->extractNodeIdsForPrimaryType($nodes, $typeStack[0]->getDef()->getQName());

		$results = $this->getNodeDataResultsForTypes($nodeIds, $typeStack);

		foreach($results as $result) {

			$uuid = $nodeUuidLookup->get($result['node_id']);
			/** @var Node $node */
			$node = $nodes->get($uuid);

			/** @var NodeType $type */
			foreach($typeStack as $type) {

				/** @var NodeTypeDef|NodeTypeRef $typeDef */
				$typeDef = $type->getDef();

				$properties = $typeDef->getProperties();
				/** @var NodeTypePropertyDef $propertyDef */
				foreach($properties as $propertyDef) {

					if ($propertyDef->isMultiValued()) {

						$property = new MultiValueProperty();
						$property->setDef($propertyDef);
						$node->addProperty($propertyDef->getKey(), $property);

					} else {

						$dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getQName());

						$resultKey = sprintf('%s__%s', $typeDef->getTableKey(), $propertyDef->getKey());

						$property = new Property();
						$property->setDef($propertyDef);

						if (null === $dataType) { // This probably shouldn't happen, because $dataType should be valid... but just in case let's see if we can salvage some sort of value if the key exists in the table result set

							$value = isset($result[$resultKey]) ? $result[$resultKey] : null;
							$property->setValue($value);
							$node->addProperty($propertyDef->getKey(), $property);

						} else {

							// Whether the value is stored in a single column...
							if ($dataType->isSimpleStorage()) {

								$value = isset($result[$resultKey]) ? $result[$resultKey] : null;
								$property->setValue($value);

								// Or multiple columns
							} else {

								$dataTypeModelFields = $dataType->getModelFields();

								$d = new Dictionary();

								foreach ($dataTypeModelFields as $dataTypeModelField) {
									// Database field name
									$fieldName = $resultKey . '_' . $dataTypeModelField->getName();
									$value = isset($result[$fieldName]) ? $result[$fieldName] : null;
									$d->set($dataTypeModelField->getName(), $value);
								}

								$property->setValue($d);
							}
							$node->addProperty($propertyDef->getKey(), $property);
						}
					}
				}
			}
		}
	}

	/**
	 * Get Node IDs that apply to a node type qname
	 *
	 * @param Dictionary $nodes
	 * @param $primaryTypeQName
	 *
	 * @return array
	 */
	private function extractNodeIdsForPrimaryType(Dictionary $nodes, $primaryTypeQName)
	{
		$nodesOfType = $this->filterNodesByPrimaryType($nodes, $primaryTypeQName);

		$nodeIds = array_map(
			function(Node $node) {
				return $node->getNodeRef()->getNodeId();
			},
			$nodesOfType
		);

		return $nodeIds; // uuid => nodeId
	}

	/**
	 * Filters nodes by a given primary type qname
	 *
	 * @param Dictionary $nodes
	 * @param string $primaryTypeQName
	 *
	 * @return array
	 */
	private function filterNodesByPrimaryType(Dictionary $nodes, $primaryTypeQName)
	{
		return array_filter(
			$nodes->toArray(),
			function(Node $node) use ($primaryTypeQName) {
				return ($node->getTypeQName() == $primaryTypeQName);
			}
		);
	}

	/**
	 * Build and execute query to return node data by type
	 *
	 * @param array $nodeIds
	 * @param array $typeStack
	 *
	 * @return array
	 */
	private function getNodeDataResultsForTypes(array $nodeIds, array $typeStack)
	{
		$qb = $this->getConnectionManager()->createQueryBuilder();
		$anyProps = false;

		/**
		 * Build primary type query
		 * @var int $ix
		 * @var NodeType $type
		 */
		foreach ($typeStack as $ix => $type) {

			/** @var NodeTypeDef|NodeTypeRef $typeDef */
			$typeDef = $type->getDef();

			$props = $typeDef->getProperties();
			if (count($props) > 0) $anyProps = true;

			if ($typeDef instanceof NodeTypeRef) {
				if ($ix == 0) {
					$qb->select($typeDef->getTableKey() . '.node_id');
					$qb->from($typeDef->getTableKey(), $typeDef->getTableKey());
					$qb->where($typeDef->getTableKey() . '.node_id IN (:nodeid)');
					$qb->setParameter(':nodeid', $nodeIds, Connection::PARAM_INT_ARRAY);
				} else {
					$qb->leftJoin(
						$typeStack[0]->getDef()->getTableKey(),
						$typeDef->getTableKey(),
						$typeDef->getTableKey(),
						$typeDef->getTableKey() . '.node_id = ' . $typeStack[0]->getDef()->getTableKey() . '.node_id AND ' . $typeDef->getTableKey() . '.node_version = ' . $typeStack[0]->getDef()->getTableKey() . '.node_version'
					);
				}
				/** @var NodeTypePropertyDef $propertyDef */
				foreach ($props as $propertyDef) {
					if ($propertyDef->isMultiValued()) continue;

					$propDataType = $this->getRepository()->getDictionaryService()->getDataType($propertyDef->getQName());
					if (null === $propDataType) throw new \Exception(sprintf('Invalid property type: %s', $propertyDef->getQName()));

					$modelFields = $propDataType->getModelFields();
					/** @var DataTypeModelField $modelField */
					foreach ($modelFields as $modelField) {
						$selectField = sprintf($propDataType->isSimpleStorage() ? '%s.%s' : '%s.%s_%s', $typeDef->getTableKey(), $propertyDef->getKey(), $modelField->getName());
						$selectAlias = sprintf($propDataType->isSimpleStorage() ? '%s__%s' : '%s__%s_%s', $typeDef->getTableKey(), $propertyDef->getKey(), $modelField->getName());
						$qb->addSelect(sprintf('%s AS %s', $selectField, $selectAlias));
					}
				}
//			} else {
//				echo '<pre>';print_r($typeDef);echo '<hr />' . __FILE__ .':'.__LINE__;exit;
			}
		}
if ($anyProps) {
	echo $qb->getSQL() . '<br>';
	exit;
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

		if (!($srcRef instanceof NodeRef) || !($tgtRef instanceof NodeRef)) return;

		$assocDef = $this->getRepository()->getNodeTypeService()->getAssociationByQName($assocQName);
		if (null === $assocDef) throw new \RuntimeException(sprintf('Invalid association type: %s', $assocQName));

		$association = new NodeAssociation($assocQName, $srcNode, $tgtNode);
		$association->setRepository($this->getRepository());

		// Check if the association already exists, if not create it
//		if (!$assoc_struct = NodeAssociationLogic::getNodeAssociation($assocQName, $srcRef->getNodeId(), $srcRef->getNodeVersion(), $dstRef->getNodeId(), $dstRef->getNodeVersion())) {
//			$assoc_struct = NodeAssociationLogic::createNodeAssociation(
//				$assocQName,
//				$srcRef->getNodeId(),
//				$srcRef->getNodeVersion(),
//				$dstRef->getNodeId(),
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

		if (!($srcRef instanceof NodeRef)) throw new \InvalidArgumentException('Source node is not managed by this repository');
		if (!($tgtRef instanceof NodeRef)) throw new \InvalidArgumentException('Target node is not managed by this repository');

		$where = [
			'assoc_qname' => $assocQName,
			'src_node_id' => $srcRef->getNodeId(),
			'src_node_version' => $srcRef->getVersion(),
			'tgt_node_id' => $tgtRef->getNodeId(),
			'tgt_node_version' => $tgtRef->getVersion()
		];

		if (null !== $sortorder) $where['sortorder'] = $sortorder;

		$this->deleteRecord('node_associations', $where);
	}

//	public function getAssociationsNodeRefsByAssociationTypeQName($node, $assocTypeQNname) {}

	/**
	 * @return NodeRef[]
	 */
	public function getAssociatedNodeRefs(Node $node, $assocQName = null)
	{
		$ref = $node->getNodeRef();
		if (!($ref instanceof NodeRef)) return [];

		$nodeRefsData = $this->getConnectionManager()
			->createQueryBuilder()
			->select('n.node_id, n.uuid, n.version')
			->from('node_associations', 'a')
			->join('a', 'nodes', 'n', 'n.node_id = a.tgt_node_id AND n.version = a.tgt_node_version')
			->where('a.src_node_id = :id AND a.src_node_version = :version')
			->setParameter(':id', $ref->getNodeId())
			->setParameter(':version', $ref->getVersion())
			->execute()
			->fetchAll();

		return array_map(function($data) {
			return new NodeRef($data['uuid'], $data['version'], $data['node_id']);
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

		$rs_associations = NodeAssociationLogic::getNodeAssociations(null, $node->getNodeRef()->getNodeId(), $node->getNodeRef()->getNodeVersion());

		$xml_association_group = new CWI_XML_Traversal('associationGroup');

		while ($association = $rs_associations->getNext()) {

			$src_node_ref = new NodeRef($association->src_node_uuid, $association->src_node_version, $association->src_node_id);
			$tgt_node_ref = new NodeRef($association->tgt_node_uuid, $association->tgt_node_version, $association->tgt_node_id);
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
}