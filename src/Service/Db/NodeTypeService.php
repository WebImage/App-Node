<?php

namespace WebImage\Node\Service\Db;

use Doctrine\DBAL\Schema\Comparator;
use Exception;
use InvalidArgumentException;
use WebImage\Config\Config;
use WebImage\Db\ConnectionManager;
use WebImage\Db\QueryBuilder;
use WebImage\Node\Defs\DataType;
use WebImage\Node\Defs\DataTypeModelField;
use WebImage\Node\Defs\NodeAssociationDef;
use WebImage\Node\Defs\NodeTypeAssociationDef;
use WebImage\Node\Defs\NodeTypeDef;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Entities\NodeTypeAssociation;
use WebImage\Node\Service\NodeTypeServiceInterface;
use WebImage\Node\Service\QName;
use WebImage\Node\Service\RepositoryAwareTrait;
use Doctrine\DBAL\Types\Type;

class NodeTypeService implements NodeTypeServiceInterface
{
	use RepositoryAwareTrait, ConnectionManagerTrait;

	private $doctrineTypeMap;
	private $retrievedDbTypes = false;

	public function __construct(ConnectionManager $connectionManager, DoctrineTypeMap $doctrineTypeMap=null)
	{
		$this->setConnectionManager($connectionManager);
		$doctrineTypeMap = $doctrineTypeMap ?: new DoctrineTypeMap();

		$this->doctrineTypeMap = $doctrineTypeMap;
	}

	/**
	 * @inheritdoc
	 */
	public function create($parent, $friendlyName, $pluralFriendlyName, $qname = null)
	{
		if (null === $qname) $qname = self::createQNameFromFriendlyName('Node.Types', $friendlyName);

		$def = new NodeTypeRef($parent, $friendlyName, $pluralFriendlyName, $qname);

		$type = new NodeType();
		$type->setRepository($this->getRepository());
		$type->setDef($def);

		return $type;
	}

	/**
	 * Returns all supported nodes, filterable to only those that can be created (i.e. those with names)
	 * @return NodeType[]
	 */
	public function getNodeTypes()
	{
		return array_filter($this->buildNodeTypes(), function(NodeType $type) {
			return strlen($type->getDef()->getName()) > 0;
		});
	}
	/**
	 * Builds a list of available node types
	 *
	 * @access public
	 * @return Collection of NodeType
	 */
	protected function buildNodeTypes()
	{
		/**
		 * Load types from database
		 */
		if (!$this->retrievedDbTypes) {
			$this->retrievedDbTypes = true;

			$qb = $this->getConnectionManager()->createQueryBuilder();

			$types = $qb->select('nt.*')
				->addSelect('n.uuid, n.version')
				->from('node_types', 'nt')
				->join('nt', 'nodes', 'n', 'n.id = nt.node_id')
				->where('n.status = :status')
				->setParameter(':status', NodeService::NODE_STATUS_ACTIVE)
				->orderBy('nt.name')
				->execute()
				->fetchAll();

			foreach($types as $type) {
				// Build a NodeType from the database data
				$nodeType = self::createNodeTypeFromData($type);
				// Add the NodeTypeRef to the dictionary
				$this->getRepository()->getDictionaryService()->setType($nodeType->getDef());
			}
		}

		// Retrieve all types from the database
		$nodeTypes = array_map(
			function(NodeTypeDef $def) {
				return self::createNodeTypeFromNodeTypeDef($def);
			},
			array_values(
				$this->getRepository()->getDictionaryService()->getTypes()->toArray()
			)
		);

		/** @var NodeType $nodeType */
		usort($nodeTypes, function(NodeType $a, NodeType $b) {
			return strcmp($a->getDef()->getName(), $b->getDef()->getName());
		});

		return $nodeTypes;
	}

	/**
	 * Retrieves a NodeType for a given type qname
	 *
	 * @param string $typeQName A string representation of a fully qualified qname
	 *
	 * @throws InvalidArgumentException
	 *
	 * @return NodeType|null
	 */
	public function getNodeTypeByTypeQName($typeQName)
	{
		if (!is_string($typeQName)) throw new InvalidArgumentException('getNodeTypeByTypeQName called with non-string value');
		$nodeTypeDef = self::_getNodeTypeRefByTypeQName($typeQName);

		if (null !== $nodeTypeDef) {
			return self::createNodeTypeFromNodeTypeDef($nodeTypeDef);
		}
	}

	/**
	 * @param string $friendlyName a standard text phrase that will be converted to a machine friendly name
	 * @return QName
	 */
	private function createQNameFromFriendlyName($namespace, $friendlyName)
	{
		$machineName = ucwords($friendlyName);
		$machineName = preg_replace('#[^a-zA-Z0-9_ \-]+#', '', $machineName); // Remove special characters
		$machineName = str_replace(' ', '', $machineName);

		$namespace = $this->getRepository()->getDictionaryService()->getLocalNamespaceByAppendingValue($namespace);
		$qname = sprintf('%s.%s', $namespace, $machineName);

		return $qname;
	}

	/**
	 * Create a property definition
	 *
	 * @param string $qnameStr
	 * @param string $key
	 * @param string $name
	 * @param string $type
	 * @param bool $required
	 * @param mixed $default
	 * @param bool $isMultiValued
	 * @param int $sortorder
	 * @param Config $config
	 *
	 * @return NodeTypePropertyRef
	 */
	public function createPropertyDef($qnameStr, $key, $name, $type, $required, $default, $isMultiValued, $sortorder, Config $config=null)
	{
		return new NodeTypePropertyRef($qnameStr, $key, $name, $type, $required, $default, $isMultiValued, $sortorder, $config);
	}

	/**
	 * Creates a NodeTypePropertyStruct from an existing NodeTypePropertyDef
	 *
	 * @return array
	 */
	private function createNodeTypePropertyDataFromNodeTypePropertyDef(NodeTypePropertyRef $propertyDef)
	{
		return [
			'node_type_id' => $propertyDef->getNodeTypeId(),
			'config' => json_encode($propertyDef->getConfig()->toArray()),
			'default' => $propertyDef->getDefault(),
			'key' => $propertyDef->getKey(),
			'multiple' => $propertyDef->isMultiValued() ? 1 : 0,
			'name' => $propertyDef->getName(),
			'qname' => $propertyDef->getQName(),
			'required' => $propertyDef->isRequired() ? 1 : 0,
			'sortorder' => $propertyDef->getSortorder()
		];
	}

	/**
	 * @inheritdoc
	 */
	public function save(NodeType $type)
	{
		// Type Definition
		$def = $this->getNodeTypeDef($type);

		// Make sure we are not trying to save a read-only cnode type
		if ($def->isReadOnly()) throw new Exception('Cannot save readonly types');

		// It may be possible, especially on importing objects, that an object could have a UUID defined, but not a Node ID.  In this cases we'll need to check if we can fill in the missing Node ID
		if ($def instanceof NodeTypeRef && null === $def->getNodeId() && null !== $def->getUuid()) {
			/**
			 * @TODO Check if node exists under the referenced UUID
			 */
			// $def->setNodeId($result->node_id);
		}

		$this->createNodeTypeRecord($type);
		$this->createPhysicalTable($def);
		$this->createTablePropertyRecords($def);
	}

	/**
	 * Add a type definition to the Node Type table
	 * @param NodeType $type
	 */
	private function createNodeTypeRecord(NodeType $type)
	{
		/** @var NodeTypeRef $def */
		$def = $this->getNodeTypeDef($type);
		if (null !== $def->getNodeId()) return; // This type has already been created

		// Should return null unless this type has already been created
		$existingType = self::getNodeTypeByTypeQName($def->getQName());

		if ($existingType) {
			// Should probably prevent nodes from being saved as new if they already exist
//			throw new \Exception
			$existingDef = $this->getNodeTypeDef($existingType);

			if (null === $def->getTableKey()) $def->setTableKey($existingDef->getTableKey());
			if (null === $def->getUuid()) $def->setUuid($existingDef->getUuid());
			if (null === $def->getNodeId()) {
				$def->setNodeId($existingDef->getNodeId());
				$def->setVersion($existingDef->getVersion());
			}
		}

		/* @TODO Change QNAME_TYPE and QNAME_EXTENSION to be defined elsewhere? */
		$QNAME_TYPE = 'WebImage.Node.Types.Type'; // 'com.webimage.system.Type'; // {http://www.cwimage.com/system}type';
		$QNAME_EXTENSION = 'WebImage.Node.Types.Extension'; // {http://www.cwimage.com/system}extension

		if (null === $type->getNode()) {
			$nodeService = $this->getRepository()->getNodeService();
			$node = $nodeService->create($QNAME_TYPE);
			$node->save();

			/** @var NodeRef $nodeRef */
			$nodeRef = $node->getNodeRef();

			$def->setNodeId($nodeRef->getNodeId());
		}

		$nodeTypeData = self::createNodeTypeDataFromNodeTypeRef($def);

		$existing = $this->getConnectionManager()->createQueryBuilder()
			->select('COUNT(*) AS total')
			->from('node_types')
			->where('qname = :qname')
			->setParameter(':qname', $def->getQName())
			->execute()
			->fetch();

		if ($existing['total'] > 0) {
			$this->updateRecord('node_types', $nodeTypeData, ['qname' => $def->getQName()]);
		} else {
			$this->insertRecord('node_types', $nodeTypeData);
		}
	}

	/**
	 * Create the physical database table
	 * @param NodeTypeRef $def
	 * @throws Exception
	 */
	private function createPhysicalTable(NodeTypeRef $def)
	{
		/**
		 * Create the required table(s)
		 */
		if (strlen($def->getTableKey()) == 0) return;

		$cm = $this->getConnectionManager();
		$conn = $this->getConnectionManager()->getConnection();
		$sm = $conn->getSchemaManager();
		$schema = $sm->createSchema();
		$tableName = $cm->getTableName($def->getTableKey());

		$existingTable = $schema->hasTable($tableName) ? $schema->getTable($tableName) : null;
		$table = $existingTable ? clone $existingTable : $schema->createTable($tableName);

		if (!$table->hasColumn('node_id')) $table->addColumn('node_id', Type::BIGINT, array('notnull' => true, 'unsigned' => true));
		if (!$table->hasColumn('node_version')) $table->addColumn('node_version', Type::INTEGER, array('notnull' => true, 'unsigned' => true, 'default' => 1));
		if (!$table->hasPrimaryKey()) $table->setPrimaryKey(array('node_id', 'node_version'));

		/**
		 *  Add properties (only supports simple types for now)
		 */
		foreach ($def->getProperties() as $propertyDef) {

			// By default, all properties will be attached as columns to the main type table
			$workingTable = $table;
			$existingPropertyTable = null; // Used for MultiValueProperty

			// If multi-valued, a separate table will be created to store the data for the property
			if ($propertyDef->isMultiValued()) {

				// Setup name to be used for property table
				$propertyTableKey = $def->getTableKey() . '_p_' . $propertyDef->getKey(); // 'cnt_prop_' . $propertyDef->getKey();
				$propertyTableName = $this->getConnectionManager()->getTableName($propertyTableKey);
				$existingPropertyTable = $schema->hasTable($propertyTableName) ? $schema->getTable($propertyTableName) : null;
				$propertyTable = $existingPropertyTable ? clone $existingPropertyTable : $schema->createTable($propertyTableName);

				// Add node reference (defined above)
				if (!$propertyTable->hasColumn('node_id')) $propertyTable->addColumn('node_id', Type::BIGINT, array('notnull' => true, 'unsigned' => true));
				if (!$propertyTable->hasColumn('node_version')) $propertyTable->addColumn('node_version', Type::BIGINT, array('notnull', true, 'unsigned' => true, 'default' => 1));

				// Change the working table, since all associated fields will be stored to this separate table
				$workingTable = $propertyTable;
			}

			// Add values for property to table

			/** @var DataType $dataType */
			if (!($dataType = $this->getRepository()->getDictionaryService()->getDataType($propertyDef->getQName()))) throw new Exception('Unsupported type: ' . $propertyDef->getQName());

			$modelFields = $dataType->getModelFields();

			if (count($modelFields) == 0) throw new Exception($propertyDef->getQName() . ' does not have defined database fields');

			/** @var DataTypeModelField $modelField */
			foreach ($modelFields as $modelField) {
				/**
				 * By default, $modelFieldName will be empty, in which case the property name is assumed to already be defined
				 *
				 * However, if a value is defined then it will be appended to the property name in the format: {$propertyFieldName}_{$modelFieldName}.  This will generally happen if the model_field definition includes multiple fields
				 */
				$modelFieldName = $modelField->getName(); // My default this will be empty

				$propertyFieldName = $propertyDef->getKey();
				// Append $modelFieldName to property
				if (!empty($modelFieldName)) $propertyFieldName .= '_' . $modelFieldName;

				// Clone the definition provided by the datatype definition, then set the properties for this specific property
				$doctrineType = $this->doctrineTypeMap->getDoctrineType($modelField->getType());

				// Add the created property to the table
				$arr = $modelField->getOptions()->toArray();


				if ($workingTable->hasColumn($propertyFieldName)) {
					$workingTable->changeColumn($propertyFieldName, $modelField->getOptions()->toArray());
				} else {
					$workingTable->addColumn($propertyFieldName, $doctrineType, $modelField->getOptions()->toArray());
				}
			}

			// We have to save the working table (the property specific table) separately here to make sure that it is attached correctly to the main table model
			if ($propertyDef->isMultiValued()) {
				if ($existingPropertyTable) {
					$comparator = new Comparator();
					$diff = $comparator->diffTable($existingPropertyTable, $propertyTable);
					if ($diff) $sm->alterTable($diff);
				} else {
					$sm->createTable($workingTable);
				}
			}
		}

		if ($existingTable) {
			$comparator = new Comparator();
			$diff = $comparator->diffTable($existingTable, $table);
			if ($diff) $sm->alterTable($diff);
		} else {
			$sm->createTable($table);
		}
	}

	/**
	 * Add node type property definitions to the Node Type Property table
	 * @param NodeTypeRef $def
	 */
	private function createTablePropertyRecords(NodeTypeRef $def)
	{
		/**
		 * Save property database records
		 * @var NodeTypePropertyRef $propertyDef
		 */
		foreach($def->getProperties() as $property => $propertyDef) {

			$isPropertyNew = strlen($propertyDef->getNodeTypeId()) == 0;
			$propertyDef->setNodeTypeID($def->getNodeId());

			$nodeTypePropertyData = self::createNodeTypePropertyDataFromNodeTypePropertyDef($propertyDef);

			if ($isPropertyNew) { // New property
				$this->insertRecord('node_type_properties', $nodeTypePropertyData);
			} else { // Update existing property
				$this->updateRecord('node_type_properties', $nodeTypePropertyData, [
					'node_type_id' => $propertyDef->getNodeTypeId(),
					'key' => $propertyDef->getKey()
				]);
			}
		}
	}

	/**
	 * Create a unique table name for the type
	 * @param NodeTypeRef $def
	 * @return string The generated table key
	 */
	private function getTableKeyForDef(NodeTypeRef $def): string
	{
		$tablePrefix = $def->isExtension() ? 'nx' : 'nt'; // nt = node type; nx = node extension
		$tableNameBase = $def->getPluralName();
		$tableNameBase = strtolower($tableNameBase); // Lower case
		$tableNameBase = preg_replace('/[^0-9a-z_]+/', '', $tableNameBase);
		$tableNameBase = $tablePrefix . '_' . $tableNameBase;

		$cm = $this->getConnectionManager();
		$conn = $this->getConnectionManager()->getConnection();

		$sm = $conn->getSchemaManager();
		$tableKey = null;
		$count = 1;

		do {
			$tableKey = $tableNameBase . ($count == 1 ? '' : $count);
			$qb = $cm->createQueryBuilder();
			$existingCount = $qb->select('*')
				->from('node_types')
				->where('qname != :qname')
				->andWhere('table_key = :tableName')
				->setParameters([
					':qname' => $def->getQName(),
					':tableName' => $tableKey
				])
				->execute()
				->rowCount();
		} while ($count++ && $existingCount > 0);

		return $tableKey;
	}

	/**
	 * @inheritdoc
	 */
	public function delete(NodeType $type)
	{
		// Type Definition
		$def = $type->getDef();

		// Make sure we are not trying to save a read-only cnode type
		if ($def->isReadOnly()) throw new Exception('Cannot delete readonly types');

		return $type->getNode()->delete();
	}

	public function getAssociations()
	{
		$associationsData = $this->getConnectionManager()
			->createQueryBuilder()
			->select('d.*')
			->from('node_type_associations', 'd')
			->orderBy('d.name')
			->execute()
			->fetchAll();

		return array_map(function($data) {
			$def = $this->createNodeTypeAssociationDefFromData($data);
			$src = $this->getNodeTypeByTypeQName($def->getSourceTypeQName());
			$tgt = $this->getNodeTypeByTypeQName($def->getTargetTypeQName());
			$assoc = new NodeTypeAssociation($src, $tgt, $def);
			$assoc->isNew(false);

			return $assoc;
		}, $associationsData);
	}

	/**
	 * @param string $qname
	 *
	 * @return void|NodeTypeAssociation
	 */
	public function getAssociationByQName($qname)
	{
		$data = $this->getConnectionManager()
			->createQueryBuilder()
			->select('d.*')
			->from('node_type_associations', 'd')
			->where('d.qname = :qname')
			->setParameter(':qname', $qname)
			->orderBy('d.name')
			->execute()
			->fetch();

		if (!$data) return;

		$def = $this->createNodeTypeAssociationDefFromData($data);

		$src = $this->getNodeTypeByTypeQName($data['source_type_qname']);
		$tgt = $this->getNodeTypeByTypeQName($data['source_type_qname']);

		$assoc = new NodeTypeAssociation($src, $tgt, $def);
		$assoc->isNew(false);
		$assoc->setRepository($this->getRepository());

		return $assoc;
	}

	private function createNodeTypeAssociationDefFromData(array $data)
	{
		return new NodeTypeAssociationDef(
			$data['name'],
			$data['source_type_qname'],
			$data['target_type_qname'],
			$data['qname'],
			$data['allow_duplicates'] == 1,
			$data['source_min'],
			$data['source_max'],
			$data['source_strict'] == 1,
			$data['target_min'],
			$data['target_max'],
			$data['target_strict'] == 1,
			$data['propagate_timestamp'] == 1
		);
	}

	/**
	 * @inheritdoc
	 */
	public function createAssociation(
		$friendlyName,
		NodeType $sourceType,
		NodeType $targetType,
		$assocQName = null,
		$allowDuplicates = true,
		$sourceMin = null,
		$sourceMax = null,
		$sourceStrict = false,
		$targetMin = null,
		$targetMax = null,
		$targetStrict = false
	) {
		if (null === $assocQName) $assocQName = self::createQNameFromFriendlyName('Node.Associations', $friendlyName);

		$def = new NodeTypeAssociationDef($friendlyName, $sourceType->getDef()->getQName(), $targetType->getDef()->getQName(), $assocQName, $allowDuplicates, $sourceMin, $sourceMax, $sourceStrict, $targetMin, $targetMax, $targetStrict);
		$association = new NodeTypeAssociation($sourceType, $targetType, $def);
		$association->setRepository($this->getRepository());

		return $association;
	}

	/**
	 * @inheritdoc
	 */
	public function saveAssociation(NodeTypeAssociation $assoc)
	{
		if (strlen($assoc->getDef()->getQName()) == 0) {
			throw new \RuntimeException('Association is missing association type qname');
		}

		if (strlen($assoc->getDef()->getName()) == 0) {
			throw new \RuntimeException('Association is missing name');
		}

		$defData = $this->createAssociationDefData($assoc);

		$assocQName = $assoc->getDef()->getQName();

		if ($assoc->isNew()) {
			/**
			 * Check if record exists
			 */
			$existing = $this->getConnectionManager()
				->createQueryBuilder()
				->select('*')
				->from('node_type_associations')
				->where('qname = :qname')
				->setParameter(':qname', $assocQName)
				->execute()
				->fetch();

			if ($existing) {
				throw new \RuntimeException(sprintf('An association with the name %s already exists', $assocQName));
			}

			$this->insertRecord('node_type_associations', $defData);

			$assoc->isNew(false);
		} else {
			$this->updateRecord('node_type_associations', $defData, ['qname' => $defData['qname']]);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function deleteAssociation(NodeTypeAssociation $association)
	{
		$this->deleteRecord('node_type_associations', ['qname' => $association->getDef()->getQName()]);
	}

	/**
	 * Retrieves a NodeTypeRef using a type qname string
	 *
	 * @access public
	 * @param string $typeQName a string representation of a type, e.g. {http://www.domain.com/types}type_name
	 * @return NodeTypeRef
	 */
	private function _getNodeTypeRefByTypeQName($typeQName)
	{
		$this->buildNodeTypes();

		$dictionaryService = $this->getRepository()->getDictionaryService();

		$nodeTypeDef = $dictionaryService->getType($typeQName);

		if (null === $nodeTypeDef) {

			$qb = $this->getConnectionManager()->createQueryBuilder();
			$existing = $qb->select('nt.*')
				->addSelect('n.uuid, n.version')
				->from('node_types', 'nt')
				->join('nt', 'nodes', 'n', 'n.id = nt.node_id')
				->where('nt.qname = :qname AND n.status = :status')
				->setParameter(':qname', $typeQName)
				->setParameter(':status', NodeService::NODE_STATUS_ACTIVE)
				->execute()
				->fetch();

			if ($existing) {

				$nodeTypeDef = self::createNodeTypeRefFromData($existing);

				$qb = $this->getConnectionManager()->createQueryBuilder();
				$associations = $qb->select('d.allow_duplicates, d.tgt_has_many, d.tgt_required, d.tgt_strict, d.name, d.src_has_many, d.src_required, d.src_strict')
					->addSelect('a.assoc_type_qname, a.tgt_node_id, a.src_node_id')
					->from('node_associations', 'a')
					->leftJoin('a', 'node_type_associations', 'd', 'd.assoc_type_qname = a.assoc_type_qname')
					->leftJoin('a', 'nodes', 'src_node', 'src_node.id = a.src_node_id')
					->leftJoin('a', 'nodes', 'tgt_node', 'tgt_node.id = a.tgt_node_id')
					->where('a.src_node_id = ?')
					->setParameter(0, $nodeTypeDef->getNodeId())
					->execute()
					->fetchAll();

				foreach($associations as $association) {
					$associationDef = new NodeAssociationDef(
						$association['name'],
						$association['assoc_type_qname'],
						$association['allow_duplicates'] == 1,
						$association['tgt_has_many'] == 1,
						$association['tgt_required'] == 1,
						$association['tgt_strict'] == 1,
						$association['src_has_many'] == 1,
						$association['src_required'] == 1,
						$association['src_strict'] == 1
					);

					$dictionaryService->setAssociation($associationDef);

					$nodeTypeDef->addAssociation($association['assoc_type_qname']);
				}

				// Cache cnode type def in dictionary
				$dictionaryService->setType($nodeTypeDef);
			}
		}

		return $nodeTypeDef;
	}

	private function createAssociationDefData(NodeTypeAssociation $assoc)
	{
		$def = $assoc->getDef();

		return [
			'allow_duplicates' => $def->doesAllowDuplicates() ? 1 : 0,
			'qname' => $def->getQName(),
			'name' => $def->getName(),
			'source_min' => $def->getSourceMin(),
			'source_max' => $def->getSourceMax(),
			'source_strict' => $def->isSourceStrict() ? 1 : 0,
			'source_type_qname' => $def->getSourceTypeQName(),
			'target_min' => $def->getTargetMin(),
			'target_max' => $def->getTargetmin(),
			'target_strict' => $def->isTargetStrict() ? 1 : 0,
			'target_type_qname' => $def->getTargetTypeQName(),
			'propagate_timestamp' => $def->shouldPropagateTimestamp() ? 1 : 0
		];
	}

	private function createAssociationData(NodeTypeAssociation $assoc)
	{
		$sourceTypeId = $this->getNodeTypeIdFromQName($assoc->getSourceTypeQName());

		return [
			'assoc_qname' => $assoc->getDef()->getQName(),
			'sortorder' => $assoc->getSortOrder(),
			'source_type_id' => $sourceTypeId
		];
	}

	private function getNodeTypeIdFromQName($qname)
	{
		$nodeTypeService = $this->getRepository()->getNodeTypeService();
		$type = $nodeTypeService->getNodeTypeByTypeQName($qname);
		/** @var NodeRef $targetNodeRef */
		$nodeRef = $type->getNode()->getNodeRef();

		return $nodeRef->getNodeId();
	}

	/**
	 * Take an existing NodeType object and add associated properties definitions
	 *
	 * @param NodeTypeRef $nodeTypeDef A NodeTypeRef object to lookup and add properties to
	 * @throws Exception
	 *
	 * @return void
	 **/
	private function loadNodeTypePropertiesWithNodeTypeRef(NodeTypeRef $nodeTypeDef)
	{
		$nodeTypeId = $nodeTypeDef->getNodeId();

		if (empty($nodeTypeId)) throw new Exception('Missing node id');

		$qb = $this->getConnectionManager()->createQueryBuilder();
		$properties = $qb->select('*')
			->from('node_type_properties')
			->where('node_type_id = ?')
			->setParameter(0, $nodeTypeId)
			->execute()
			->fetchAll();

		foreach($properties as $property) {

			$config = empty($property['config']) ? [] : json_decode($property['config']);
			$config = new Config($config);

			$nodeTypeProperty = new NodeTypePropertyRef(
				$nodeTypeDef->getQName(),
				$property['key'],
				$property['name'],
				$property['qname'],
				$property['required'] == 1,
				$property['default'],
				$property['multiple'] == 1,
				$property['sortorder'],
				$config
			);
			$nodeTypeProperty->setNodeTypeID($nodeTypeId);

			$nodeTypeDef->setProperty($nodeTypeProperty->getKey(), $nodeTypeProperty);
		}
	}

	/**
	 * Takes an existing database record and turns it into a NodeTypePropertyDef
	 *
	 * @param array $data The record to convert
	 *
	 * @return NodeTypeRef a created definition
	 **/
	private function createNodeTypeRefFromData(array $data)
	{
		$qname = $data['qname'];
		$config = null;
		if (!empty($cnodeTypeStruct['config'])) $config = new Config(json_decode($data['config']));

		$nodeTypeDef = new NodeTypeRef($data['parent'], $data['name'], $data['plural_name'], $qname, $config, $data['uuid'], $data['version']);
		$nodeTypeDef->setTableKey($data['table_key']);
		$nodeTypeDef->setNodeId($data['node_id']);

		self::loadNodeTypePropertiesWithNodeTypeRef($nodeTypeDef);

		return $nodeTypeDef;
	}

	/**
	 * Create a node type from a node type definition
	 *
	 * @access private
	 * @param NodeTypeRef $cnodeTypeDef A definition to be converted into a NodeTYpe
	 * @return NodeType a NodeType based on a NodeTypeRef
	 **/
	private function createNodeTypeFromNodeTypeDef(NodeTypeDef $nodeTypeDef)
	{
		$cnodeType = new NodeType();
		$cnodeType->setRepository($this->getRepository());
		$cnodeType->setDef($nodeTypeDef);

		return $cnodeType;
	}

	/**
	 * Create node type from a struct
	 *
	 * @return NodeType
	 **/
	private function createNodeTypeFromData($data)
	{
		$def = self::createNodeTypeRefFromData($data);
		$type = self::createNodeTypeFromNodeTypeDef($def);

		return $type;
	}

	/**
	 * Create NodeTypeStruct from NodeTypeRef
	 *
	 * @access private
	 *
	 * @return NodeTypeStruct
	 */
	private function createNodeTypeDataFromNodeTypeRef(NodeTypeRef $def)
	{
		// Create a table key if it does not exist
		if (null === $def->getTableKey()) {
			$def->setTableKey( $this->getTableKeyForDef($def) );
		}

		return [
			'node_id' => $def->getNodeId(),
			'config' => json_encode($def->getConfig()->toArray()),
			'is_extension' => ($def->isExtension() ? 1 : 0),
			'name' => $def->getName(),
			'plural_name' => $def->getPluralName(),
			'parent' => $def->getParent(),
			'qname' => $def->getQName(),
			'table_key' => $def->getTableKey()
		];
	}

	/**
	 * Exports a node type to xml format (via CWI_XML_Traversal) and adds any required dependencies to exporter
	 *
	 * @param CWI_NODE_SERVICE_TRANSFER_Exporter $exporter An object used to build dependies
	 * @param string $typeQName The string version of a CWI_NODE_SERVICE_QName for the type to be exported
	 * @param string $version The version of the NodeType to be exported - as the time of this documentation NodeTypes do not even support versioning, so this is purely put in now to plan for the future
	 *
	 * @throws Exception When node type could not be found
	 *
	 * @return CWI_XML_Traversal
	 */
	public function exportNodeType(CWI_NODE_SERVICE_TRANSFER_Exporter $exporter, $typeQName, $version = null)
	{
		$nodeType = self::getNodeTypeByTypeQname($typeQName);
		#echo '<pre>';
		#$nodeType->setRepository(null);
		#print_r($nodeType);exit;
		if (is_null($nodeType)) throw new Exception('Export NodeType failed because ' . $typeQName . ' could not be found');

		// Let the exporter know that we will be exporting a Node with the following UUID so that sub requests to things like associations and properties do not try to re-include a self referencing Node, which would result in a circular loop
		$exporter->addRequiredNodeType($nodeType->getDef()->getQName(), $nodeType->getDef()->getNodeVersion());
		// Export the Node XML object (CWI_XML_Traversal)
		$xmlTraversal = $nodeType->export($exporter);
		// Finally, update the exporter with the returned XML Object

		$exporter->setNodeTypeXmlTraversal($nodeType->getDef()->getQName(), $nodeType->getDef()->getNodeVersion(), $xmlTraversal);
	}

	/**
	 * Imports a node types from XML format (CWI_XML_Traversal)
	 * @return CWI_NODE_SERVICE_DB_NodeType
	 */
	public function importNodeType(Importer $importer, Traversal $xmlNodeType)
	{
		return CWI_NODE_SERVICE_NodeType::import($importer, $xmlNodeType);
	}

	/**
	 * Convenience method to type NodeTypeRef
	 * @param NodeType $type
	 * @return NodeTypeRef
	 */
	private function getNodeTypeDef(NodeType $type): NodeTypeRef
	{
		return $type->getDef();
	}
}