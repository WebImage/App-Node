<?php

namespace WebImage\Node\Service\Db;

use Doctrine\DBAL\Connection;
use Exception;
use WebImage\Core\Dictionary;
use WebImage\Db\Manager;
use WebImage\Node\Defs\DataType;
use WebImage\Node\Defs\DataTypeModelField;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\NodeAssociation;
use WebImage\Node\Entities\Node;
use WebImage\Node\Entities\NodeRefInterface;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Properties\MultiProperty;
use WebImage\Node\Properties\Property;
use WebImage\Node\Properties\PropertyInterface;
use WebImage\Node\Query\Query;
use WebImage\Node\Service\NodeServiceInterface;
use WebImage\Node\Service\QName;
use WebImage\Node\Service\RepositoryAwareTrait;
use WebImage\String\Uuid;

class NodeService implements NodeServiceInterface
{
	use RepositoryAwareTrait;

	/** @var Manager */
	private $connectionManager;

	public function __construct(Manager $connectionManager)
	{
		$this->connectionManager = $connectionManager;
	}
	/**
	 * @inheritdoc
	 */
	public function saveNodeAssociation(NodeAssociation $nodeAssociation)
	{
		// TODO: Implement saveNodeAssociation() method.
		throw new \Exception(sprintf('%s not yet implemented', __METHOD__));
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

			$data = [
				'type_qname' => $nodeType->getDef()->getQName()->toString(),
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
			$typeQName = $typeDef->getQName()->toString();
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
			 * @var NodeTypePropertyRef $property
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
					/** @var Property|MultiProperty $property */
					$property = $node->getProperty($fieldName);

					$propertyType = $propertyDef->getType();

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
										array_push($columns, "`" . $column . "` = '" . $dao_property->safeString($value) . "'");
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
				$this->createNodeAssociation($association->getAssociationTypeQName(), $association->getSourceNode(), $association->getDestinationNode());
			}
		}

		// Let other objects know that the Node has been saved
//		$event_args = new CWI_CNODE_EVENT_NodeServiceSavingEventArgs($node);
//		CWI_EVENT_Manager::trigger($this, 'saved', $event_args);

		return $node;
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

	public function saveNodeAssociationDef($nodeAssociationDef)
	{
		$struct = new NodeAssocDefStruct();
		$struct->allow_duplicates = ($nodeAssociationDef->getAllowDuplicates() ? 1 : 0);
		$struct->assoc_type_qname = $nodeAssociationDef->getAssociationTypeQName();
		$struct->dst_has_many = ($nodeAssociationDef->getDstHasMany() ? 1 : 0);
		$struct->dst_required = ($nodeAssociationDef->getDstRequired() ? 1 : 0);
		$struct->dst_strict = ($nodeAssociationDef->getDstStrict() ? 1 : 0);
		$struct->friendly_name = $nodeAssociationDef->getName();
		$struct->src_has_many = ($nodeAssociationDef->getSrcHasMany() ? 1 : 0);
		$struct->src_required = ($nodeAssociationDef->getSrcRequired() ? 1 : 0);
		$struct->src_strict = ($nodeAssociationDef->getSrcStrict() ? 1 : 0);

		// Turn database caching off
		$DATABASE_CACHE_RESULTS = ConfigurationManager::get('DATABASE_CACHE_RESULTS');
		ConfigurationManager::set('DATABASE_CACHE_RESULTS', false);

		// Create the database entry
		if (NodeAssociationLogic::getNodeAssociationDef($struct->assoc_type_qname)) {
			$struct = NodeAssociationLogic::saveNodeAssociationDef($struct);
		} else {
			$struct = NodeAssociationLogic::createNodeAssociationDef($struct->friendly_name, $struct->assoc_type_qname, $struct->allow_duplicates, $struct->dst_has_many, $struct->dst_required, $struct->dst_strict, $struct->src_has_many, $struct->src_required, $struct->src_strict);
		}

		// Restore database caching setting
		ConfigurationManager::set('DATABASE_CACHE_RESULTS', $DATABASE_CACHE_RESULTS);

		return $nodeAssociationDef;
	}

	/**
	 * @inheritdoc
	 */
	public function create($qnameStr)
	{
		$qname = QName::createQName($qnameStr);
		$nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($qname->toString());
		if (null === $nodeType) throw new Exception('Unable to locate type ' . $qname->toString());
		$node = new Node($qname->toString());
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
					$node_property = new MultiProperty();
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
			->where('uuid IN (:uuid)')
			->setParameter(':uuid', $uuids, Connection::PARAM_STR_ARRAY)
			->execute()
			->fetchAll();

		$allTypeQNames = new Dictionary();

		foreach($nodesData as $nodeData) {

			// Build Node
			$node = new Node($nodeData['type_qname']);
			$nodeRef = new NodeRef($nodeData['uuid'], $nodeData['version'], $nodeData['id']);
			$node->setNodeRef($nodeRef);
			$node->setRepository($this->getRepository());

			$nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($nodeData['type_qname']);

			/** @var NodeType[] $typeStack */
			$typeStack = $nodeType->getTypeStack();

			if (count($typeStack) == 0) continue;

			/** @var Nodetype $type */
			$typeQName = $nodeType->getDef()->getQName()->toString();

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
		foreach($allTypeQNames as $typeQName => $typeStack) {
			$this->addSimpleNodeProperties($nodes, $nodeUuidLookup, $typeStack);
			$this->addMultiNodeProperties($nodes, $nodeUuidLookup, $typeStack);
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
				$dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getType());

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
					/** @var MultiProperty $property */
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
		$nodeIds = $this->extractNodeIdsForPrimaryType($nodes, $typeStack[0]->getDef()->getQName()->toString());

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

						$property = new MultiProperty();
						$property->setDef($propertyDef);
						$node->addProperty($propertyDef->getKey(), $property);

					} else {

						$dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getType());

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

		/**
		 * Build primary type query
		 * @var int $ix
		 * @var NodeType $type
		 */
		foreach ($typeStack as $ix => $type) {

			/** @var NodeTypeDef|NodeTypeRef $typeDef */
			$typeDef = $type->getDef();

			$props = $typeDef->getProperties();

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

					$propDataType = $this->getRepository()->getDictionaryService()->getDataType($propertyDef->getType());
					$modelFields = $propDataType->getModelFields();
					/** @var DataTypeModelField $modelField */
					foreach ($modelFields as $modelField) {
						$selectField = sprintf($propDataType->isSimpleStorage() ? '%s.%s' : '%s.%s_%s', $typeDef->getTableKey(), $propertyDef->getKey(), $modelField->getName());
						$selectAlias = sprintf($propDataType->isSimpleStorage() ? '%s__%s' : '%s__%s_%s', $typeDef->getTableKey(), $propertyDef->getKey(), $modelField->getName());
						$qb->addSelect(sprintf('%s AS %s', $selectField, $selectAlias));
					}
				}
			}
		}

		return $qb->execute()->fetchAll();
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

	/**
	 * @return array a keyed array, one value for 'tables' and one for 'properties'
	 */
	private function getPropertyDefsForTypeQName(array $objects, array $tables)
	{

		static $lookup_cache;
		static $total_time = 0;

		$time0 = FrameworkManager::getTime();

		if (null === $lookup_cache) $lookup_cache = new Dictionary();

		$properties_info = array();
		$columns_info = array();

		foreach ($objects as $object) {

			$object_type_qname = $object->getTypeQName();
			$object_field_name = $object->getField();

			$use_type_defs = array();

			// If this value is null then assume we are dealing with a wildcard requested where we want to grab all instances of a field across all NodeType's

			if (null === $object_type_qname) {

				$all_node_types = $this->getRepository()->getNodeTypeService()->getNodeTypes();

				while ($nodeType = $all_node_types->getNext()) {

					$nodeTypeDef = $nodeType->getDef();

					// Check if property exists in this node type
					if ($property = $nodeTypeDef->getProperty($object_field_name)) {
						array_push($use_type_defs, $nodeTypeDef);
					}
				}

				//Otherwise, assume a specific type is being referenced for the field inclusion
			} else {

				if ($nodeType = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($object_type_qname)) {
					$nodeTypeDef = $nodeType->getDef();
					array_push($use_type_defs, $nodeTypeDef);
				}
			}

			// Iterate through each of the found type_defs (there will be multiple if $object_type_qname is null, and one if $object_type_qname is defined
			foreach ($use_type_defs as $use_type_def) {

				if (is_a($use_type_def, 'NodeTypeDef')) {

					$tableKey = $use_type_def->getTableKey();

					// Make sure that the table is included in our listed of queriable tables
					if ($tableKey != 'nodes' && !in_array($tableKey, $tables)) {
						array_push($tables, $tableKey);
					}

					// Make sure field is actually part of the type in question
					if ($propertyDef = $use_type_def->getProperty($object_field_name)) {
						// Only handle single value properties for now

						if (!$propertyDef->isMultiValued()) {

							$columns = array();

							if ($dataType = $this->getRepository()->getDataTypeService()->getDataType($propertyDef->getType())) {

								if ($dataType->isSimpleStorage()) {

									array_push($columns_info, $propertyDef->getKey());
									array_push($columns, $propertyDef->getKey());

								} else {

									$model_fields = $dataType->getModelFields();

									while ($model_field = $model_fields->getNext()) {

										$field_name = $propertyDef->getKey() . '_' . $model_field->getName();

										array_push($columns_info, array(
											'table_key' => $tableKey,
											'object' => $object,
											'field_name' => $field_name
										));
										array_push($columns, $field_name);

									}

								}

							}

							$property_info = array(
								'table_key' => $tableKey,
								'property_def' => $propertyDef,
								'object' => $object,
								'columns' => $columns
							);

							array_push($properties_info, $property_info);
						}
					}
				}
			}
		}

		return array(
			'tables' => $tables,
			'properties_info' => $properties_info,
			'columns_info' => $columns_info
		);
	}

	/**
	 * Create a NodeAssociationDef
	 */
	public function createNodeAssociationDef($friendlyName, $assocTypeQName = null, $allowDuplicates = true, $dstHasMany = true, $dstRequired = false, $dstStrict = false, $srcHasMany = true, $srcRequired = false, $srcStrict = false)
	{
		// Make sure we have a valid assoc_type_qname, otherwise create one
		if (empty($assocTypeQName)) {
			$namespace = $this->getRepository()->getDictionaryService()->getLocalNamespaceByAppendingValue('nodeassociation');
			$key = 'assoc-' . $this->getRepository()->getDictionaryService()->createKeyFromFriendlyName($friendlyName);

			$qname = QName::createQName($namespace, $key);
			$assocTypeQName = $qname->toString();
		}

		// Create the database entry
		# Dont' save here since it will be saved as part of $this->saveNodeAssociationDef()... $assoc_def_struct = NodeAssociationLogic::createNodeAssociationDef($friendly_name, $assoc_type_qname, $allow_duplicates, $dst_has_many, $dst_required, $dst_strict, $src_has_many, $src_required, $src_strict);

		// Create the association def object
		$association_def = new CWI_CNODE_SERVICE_NodeAssociationDef(
			$friendlyName,
			$assocTypeQName,
			$allowDuplicates,
			$dstHasMany,
			$dstRequired,
			$dstStrict,
			$srcHasMany,
			$srcRequired,
			$srcStrict);

		$association_def->setRepository($this->getRepository());

		return $association_def;
	}

	public function getNodeAssociationDef($assocTypeQNname)
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
	 * Inconsisten naming.... in other methods "createXYZ" does not actually do anything.... currently CWI_CNODE_SERVICE_TRANSFER_Importer (and possibly others) relies on this method actually creating the actually node association
	 */
	public function createNodeAssociation($assocTypeQName, Node $srcNode, Node $dstNode)
	{
		die(__FILE__.':'.__LINE__.PHP_EOL);
		$srcRef = $srcNode->getNodeRef();
		$dstRef = $dstNode->getNodeRef();

		if (!is_a($srcRef, 'NodeRef') || !is_a($dstRef, 'NodeRef')) return; // Need to log this somehow

		try {
			$node_association_def = $this->getNodeAssociationDef($assocTypeQName);
		} catch (Exception $e) {
			throw new Exception('Invalid association type: ' . $e->getMessage());
		}

		$node_association = new CWI_CNODE_DICTIONARY_NodeAssociation($assocTypeQName, $srcNode, $dstNode);

		// Check if the association already exists, if not create it
		if (!$assoc_struct = NodeAssociationLogic::getNodeAssociation($assocTypeQName, $srcRef->getNodeId(), $srcRef->getNodeVersion(), $dstRef->getNodeId(), $dstRef->getNodeVersion())) {

			$assoc_struct = NodeAssociationLogic::createNodeAssociation(
				$assocTypeQName,
				$srcRef->getNodeId(),
				$srcRef->getNodeVersion(),
				$dstRef->getNodeId(),
				$dstRef->getNodeVersion()
			);

		}

		return $node_association;
	}

	/**
	 * Removes a node association
	 *
	 * @return void
	 */
	public function removeNodeAssociation($assocTypeQName, $srcNode, $dstNode)
	{
		$srcRef = $srcNode->getNodeRef();
		$dstRef = $dstNode->getNodeRef();

		if (!is_a($srcRef, 'NodeRef') || !is_a($dstRef, 'NodeRef')) return; // Need to log this somehow

		$assoc_struct = NodeAssociationLogic::deleteNodeAssociation(
			$assocTypeQName,
			$srcRef->getNodeId(),
			$srcRef->getNodeVersion(),
			$dstRef->getNodeId(),
			$dstRef->getNodeVersion()
		);
	}

//	public function getAssociationsNodeRefsByAssociationTypeQName($node, $assocTypeQNname) {}

	/**
	 * @return Collection of NodeRefs
	 */
	public function getAssociatedNodeRefs(Node $node, $assocTypeQName = null)
	{
		$return = new Collection();

		$rs_associations = NodeAssociationLogic::getNodeAssociations($assocTypeQName, $node->getNodeRef()->getNodeId(), $node->getNodeRef()->getNodeVersion());

		while ($association = $rs_associations->getNext()) {
			$nodeRef = new NodeRef($association->dst_node_uuid, $association->dst_node_version, $association->dst_node_id);
			$return->add($nodeRef);
		}

		return $return;
	}

	public function query(Query $query)
	{
		$query_fields = $query->getFields();
		$query_filters = $query->getFilters();
		$query_filter_type_qnames = $query->getFilterTypeQNames();
		$query_sorts = $query->getSorts();
		$query_keywords = $query->getKeywords();

		$fields = array();
		$filters = array();
		$sorts = array();
		$keywords = array();

		$typeQNames = array();
		$tables = array('node_base');
		$inner_join_tables = array('node_base');

		/**
		 * Add fields
		 */
		$info = $this->getPropertyDefsForTypeQName($query_fields, $tables);

		$tables = $info['tables']; // Updates tables
		$properties_info = $info['properties_info'];
		foreach ($properties_info as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];

			if (!isset($fields[$tableKey])) $fields[$tableKey] = array();

			#array_push($fields[$tableKey], $object->getField());
			foreach ($property_info['columns'] as $column) {

				array_push($fields[$tableKey], $column);

			}
		}

		/*
		 * Generate keywords
		 */
		$filter_keywords = array();

		if (count($query_keywords) > 0) {

			// Get all available NodeTypes
			$nodeTypes = $this->getRepository()->getNodeTypeService()->getNodeTypes();
			// Iterate through NodeTypes
			while ($nodeType = $nodeTypes->getNext()) {
				// Retrieve list of NodeTypeProperties for the current definition
				$node_properties = $nodeType->getDef()->getProperties()->getAll();
				// Iterate through properties
				while ($node_property = $node_properties->getNext()) {

					// Get property definition
					$node_property_def = $node_property->getDef();
					// Check if property is searchable
					if ($node_property_def->isSearchable()) {
						// Add search fields for all keywords
						foreach ($query_keywords as $query_keyword) {
							$filter_keywords[] = new CWI_CNODE_QUERY_Filter($nodeType->getDef()->getQName()->toString(), $node_property_def->getKey(), $query_keyword, 'LIKE');
						}
					}
				}
			}
		}

		$info = $this->getPropertyDefsForTypeQName($filter_keywords, $tables);

		$tables = $info['tables'];
		$properties_info = $info['properties_info'];
		foreach ($properties_info as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];
			$field_name = $object->getField();
			$field_value = $object->getValue();

			if (!isset($keywords[$tableKey])) $keywords[$tableKey] = array();
			if (!isset($keywords[$tableKey][$field_name])) $keywords[$tableKey][$field_name] = array();

			array_push($keywords[$tableKey][$field_name], $field_value);
		}


		/**
		 * Add filters
		 */

		$info = $this->getPropertyDefsForTypeQName($query_filters, $tables);

		$tables = $info['tables']; // Updates tables
		$properties_info = $info['properties_info'];

		foreach ($properties_info as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];

			$filter = array(
				'query_filter' => $object,
				'table_key' => $tableKey
			);
			array_push($filters, $filter);

		}

		/**
		 * Add sorts
		 */
		$info = $this->getPropertyDefsForTypeQName($query_sorts, $tables);
		$tables = $info['tables']; // Updates tables
		$properties_info = $info['properties_info'];

		foreach ($properties_info as $property_info) {

			$tableKey = $property_info['table_key'];
			$object = $property_info['object'];

			$sort = array(
				'query_sort' => $object,
				'table_key' => $tableKey
			);
			array_push($sorts, $sort);
		}

		// DAO Search
		$search = new DAOSearch('nodes');

		foreach ($tables as $tableKey) {
			$join_keys = array(
				$tableKey . '.node_id' => 'nodes.node_id',
				$tableKey . '.version' => 'nodes.version'
			);
			$select_fields = array();
			if (isset($fields[$tableKey])) $select_fields = $fields[$tableKey];

			$join_type = DAOJoin::JOIN_LEFT;
			if (in_array($tableKey, $inner_join_tables)) $join_type = DAOJoin::JOIN_INNER;

			$join = new DAOJoin($tableKey, $join_type, $join_keys, $select_fields);
			$search->addJoin($join);
		}
		$association_values = $query->getFilterAssociationValues();
		for ($i = 0, $j = count($association_values); $i < $j; $i++) {

			// Setup join for related tables
			$tableKey = 'node_assocs';
			$table_alias = 'node_assocs_' . $i;

			$join_keys = array(
				$table_alias . '.src_node_id' => 'nodes.node_id',
				$table_alias . '.src_node_version' => 'nodes.version'
			);
			$join_type = DAOJoin::JOIN_INNER;
			$join = new DAOJoin(array($tableKey, $table_alias), $join_type, $join_keys);

			$search->addJoin($join);

			// Setup where clause for association

			$association_value = $association_values[$i]->getValue();
			$association_type_qname = $association_values[$i]->getAssociationTypeQName();

			if (is_array($association_value)) { // Search for multiple values using an array of possible vlaues

				$value_search_field = new DAOSearchFieldValues($table_alias, 'dst_node_id', $association_value);

				// Since we are search multiple values, we should make sure that only distince Nodes are returned (since a Node could potentially match more than one associations and create duplicate results
				$search->makeDistinct(true);

			} else {
				$value_search_field = new DAOSearchField($table_alias, 'dst_node_id', $association_value);
			}

			$search->addSearchField($value_search_field);

			// Filter by association type qname, but only if a value is actually defined (otherwise it would be considered a "wildcard" search for any type of association with another node
			if (!empty($association_type_qname)) {

				$search->addSearchField(new DAOSearchField($table_alias, 'assoc_type_qname', $association_type_qname));

			}
			#getAssociationTypeQName()
			#getValue()
#$group->addSearchField( new DAOSearchField('nodes', 'type_qname', $query_filter_type_qname) );
		}

		foreach ($filters as $filter) {
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

		// Filter search by type_names (e.g. {http://www.cwimage.com/model/core}content)
		if (count($query_filter_type_qnames) > 0) {

			$group = new DAOSearchOrGroup();

			foreach ($query_filter_type_qnames as $query_filter_type_qname) {

				$group->addSearchField(new DAOSearchField('nodes', 'type_qname', $query_filter_type_qname));

			}

			$search->addSearchField($group);
		}


		foreach ($sorts as $sort) {

			$query_filter = $sort['query_sort'];
			$sort_direction = null;

			if ($query_filter->getSortDirection() == CWI_CNODE_QUERY_Query::SORT_ASC) {

				$sort_direction = DAOSearch::SORT_ASC;

			} else if ($query_filter->getSortDirection() == CWI_CNODE_QUERY_Query::SORT_DESC) {

				$sort_direction = DAOSearch::SORT_DESC;

			}

			$search->addSort($sort['table_key'], $sort['query_sort']->getField(), $sort_direction);

		}

		$dao = new DataAccessObject();
		$dao->separateResultTables(true);

		// Check if we need to paginate results:
		$current_page = $query->getCurrentPage();
		$results_per_page = $query->getResultsPerPage();

		if (!empty($current_page) && !empty($results_per_page)) $dao->paginate($current_page, $results_per_page);

		$results = $dao->search($search);

		$rs_return_nodes = new ResultSet();
		$rs_return_nodes->setTotalResults($results->getTotalResults());
		$rs_return_nodes->setCurrentPage($results->getCurrentPage());
		$rs_return_nodes->setResultsPerPage($results->getResultsPerPage());


		while ($result = $results->getNext()) {

			$typeQName = $result->nodes->type_qname;
			$node = new CWI_CNODE_SERVICE_Node($typeQName);
			$node->setRepository($this->getRepository());

			$nodeRef = new NodeRef($result->nodes->uuid, $result->nodes->version, $result->nodes->node_id);
			$node->setNodeRef($nodeRef);

			if ($type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($typeQName)) {

				$typeStack = $type->getTypeStack();

				foreach ($typeStack as $stack_type) {

					$typeDef = $stack_type->getDef();

					$propertyDefs = $typeDef->getProperties()->getAll();

					while ($prop = $propertyDefs->getNext()) {

						$propertyDef = $prop->getDef();
						$propertyTableKey = null; // Defined below
						$property_key = $propertyDef->getKey();

						$propertyDataType = $this->getRepository()->getDataTypeService()->getDataType($prop->getDef()->getType());

						/**
						 * Get Property Definition Original Table
						 */
						$propertyDefTypeQName = $propertyDef->getNodeTypeTypeQName();

						if ($propertyDef_type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($propertyDefTypeQName)) {

							$propertyDef_type_def = $propertyDef_type->getDef();

							// Make sure this is a database definition, because we need the getTableKey() method
							if (is_a($propertyDef_type_def, 'INodeTypeDef')) {

								$propertyTableKey = $propertyDef_type_def->getTableKey();

								/**
								 * Make sure that the table to which this node type's date should be pulled from is available
								 */

								if (isset($result->$propertyTableKey)) {

									// The object for this particular table
									$result_table = $result->$propertyTableKey;

									$result_table_keys = get_object_vars($result_table);


									$fields = array();

									if ($propertyDataType->isSimpleStorage()) {

										if (array_key_exists($property_key, $result_table_keys)) {

											$node_property = null;

											if ($propertyDef->isMultiValued()) {
												$node_property = new CWI_CNODE_NodeMultiProperty();
												#if ($default_value = $def->getDefault()) $node_property->addValue($node_property->setValue($def->getDefault()));
											} else {
												$node_property = new CWI_CNODE_NodeProperty();
												$node_property->setValue($result_table->$property_key);
											}
											$node_property->setDef($propertyDef);
											$node->addProperty($property_key, $node_property);

										} /* else: What should we do if a properties value is not available in the query results.... */
										else {
											#echo 'Missing: ' . $propertyTableKey . '-> ' . $property_key . '<br />';
										}

									} else {

										$fields = array();

										$dataTypeModelFields = $propertyDataType->getModelFields();

										$d = new Dictionary();

										while ($dataTypeModelField = $dataTypeModelFields->getNext()) {

											$field_name = $property_key . '_' . $dataTypeModelField->getName();

											if (array_key_exists($field_name, $result_table_keys)) {

												$d->set($dataTypeModelField->getName(), $result_table->$field_name);

											}

										}

										if ($propertyDef->isMultiValued()) {

											$node_property = new CWI_CNODE_NodeMultiProperty();
											#if ($default_value = $def->getDefault()) $node_property->addValue($node_property->setValue($def->getDefault()));

										} else {

											$node_property = new CWI_CNODE_NodeProperty();
											$node_property->setValue($d);

										}

										$node_property->setDef($propertyDef);
										$node->addProperty($property_key, $node_property);

									}


								} /* else: What should we do if the table is not found in the results */
							}

						} /* else: could not find the qname for the property */
					}
				}
			}

			$rs_return_nodes->add($node);

		}

		return $rs_return_nodes;
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
	public function exportNodeAssociationDef(CWI_CNODE_SERVICE_TRANSFER_Exporter $exporter, $assocTypeQName)
	{

		$node_association_def = $this->getNodeAssociationDef($assocTypeQName);
		// Let the exporter know that we will be exporting a NodeAsociationDef so that sub reqeusts do not try to include this node association def multiple times
		$exporter->addRequiredNodeAssociationDef($node_association_def->getAssociationTypeQName(), 1);
		// Export Node Association XML object (CWI_XML_Traversal)
		$xml_traversal = $node_association_def->export($exporter);
		// Update the exporter with the returned XML object
		$exporter->setNodeAssociationDefXmlTraversal($node_association_def->getAssociationTypeQName(), 1, $xml_traversal);

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
			$dst_node_ref = new NodeRef($association->dst_node_uuid, $association->dst_node_version, $association->dst_node_id);
			#$return->add($nodeRef);

			$xml_source = new CWI_XML_Traversal('source');
			$xml_src_node = $src_node_ref->export($exporter);
			$xml_source->addChild($xml_src_node);

			$xml_destination = new CWI_XML_Traversal('destination');
			$xml_dst_node = $dst_node_ref->export($exporter);
			$xml_destination->addChild($xml_dst_node);

			$xml_association = new CWI_XML_Traversal('association');
			$xml_association->addChild(new CWI_XML_Traversal('assocTypeQName', $association->assoc_type_qname));
			$xml_association->addChild($xml_source);
			$xml_association->addChild($xml_destination);

			// Add required Association Def and destination Nodes
			$exporter->addRequiredNodeAssociationDef($association->assoc_type_qname);
			$exporter->addRequiredNode($dst_node_ref->getUuid(), $dst_node_ref->getNodeVersion());

			$xml_association_group->addChild($xml_association);
		}

		$exporter->setNodeAssociationXmlTraversal($uuid, $version, $xml_association_group);

		return $xml_association_group;
	}

	/**
	 * @return Manager
	 */
	public function getConnectionManager()/*: Manager */
	{
		return $this->connectionManager;
	}
}