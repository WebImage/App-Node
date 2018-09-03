<?php

namespace WebImage\Node\Service\Db;

use Doctrine\DBAL\Schema\Comparator;
use Exception;
use InvalidArgumentException;
use RuntimeException;
use WebImage\Config\Config;
use WebImage\Db\Manager;
use WebImage\Node\Defs\DataType;
use WebImage\Node\Defs\DataTypeModelField;
use WebImage\Node\Defs\NodeAssociationDef;
use WebImage\Node\Defs\NodeTypeDef;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\Association;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Service\Db\DoctrineTypeMap;
use WebImage\Node\Service\Db\NodeTypePropertyRef;
use WebImage\Node\Service\Db\NodeTypeRef;
use WebImage\Node\Service\NodeTypeServiceInterface;
use WebImage\Node\Service\QName;
use WebImage\Node\Service\RepositoryAwareTrait;
use Doctrine\DBAL\Types\Type;

//use WebImage\Node\Service\Transfer\Importer;
//use WebImage\Xml\Traversal;

class NodeTypeService implements NodeTypeServiceInterface
{
	use RepositoryAwareTrait;

	/** @var \Doctrine\DBAL\Connection */
	private $connectionManager;
	private $doctrineTypeMap;
	private $retrievedDbTypes = false;

	public function __construct(Manager $connectionManager, DoctrineTypeMap $doctrineTypeMap=null)
	{
		$this->connectionManager = $connectionManager;

		$doctrineTypeMap = $doctrineTypeMap ?: new DoctrineTypeMap();
		$this->doctrineTypeMap = $doctrineTypeMap;
	}

	public function saveAssociation(Association $association)
	{
		throw new Exception(sprintf('%s Not implemented', __METHOD__));
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
				$nodeTypeDef->getQName()->toString(),
				$property['key'],
				$property['name'],
				$property['type'],
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
		$qname = QName::createQName($data['qname']);

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
		return [
			'node_id' => $def->getNodeId(),
			'config' => json_encode($def->getConfig()->toArray()),
			'is_extension' => ($def->isExtension() ? 1 : 0),
			'name' => $def->getName(),
			'plural_name' => $def->getPluralName(),
			'parent' => $def->getParent(),
			'qname' => $def->getQName()->toString(),
			'table_key' => $def->getTableKey(),
//			'uuid' => $def->getUUID(),
//			'node_version' => $def->getVersion()
		];
	}
	public function getNodeTypes()
	{
		return $this->buildNodeTypes();
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
				->leftJoin('nt', 'nodes', 'n', 'n.id = nt.node_id')
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
			$existing = $qb->select('nt.*, n.uuid, n.version as node_version')
				->from('node_types', 'nt')
				->leftJoin('nt', 'nodes', 'n', 'n.id = nt.node_id')
				->where('nt.qname = ?')
				->setParameter(0, $typeQName)
				->execute()
				->fetch();

			if ($existing) {

				$nodeTypeDef = self::createNodeTypeRefFromData($existing);

				$qb = $this->getConnectionManager()->createQueryBuilder();
				$associations = $qb->select('d.allow_duplicates, d.dst_has_many, d.dst_required, d.dst_strict, d.name, d.src_has_many, d.src_required, d.src_strict')
					->addSelect('a.assoc_type_qname, a.dst_node_id, a.src_node_id')
					->from('node_associations', 'a')
					->leftJoin('a', 'node_association_defs', 'd', 'd.assoc_type_qname = a.assoc_type_qname')
					->leftJoin('a', 'nodes', 'src_node', 'src_node.id = a.src_node_id')
					->leftJoin('a', 'nodes', 'dst_node', 'dst_node.id = a.dst_node_id')
					->where('a.src_node_id = ?')
					->setParameter(0, $nodeTypeDef->getNodeId())
					->execute()
				->fetchAll();

				foreach($associations as $association) {
					$associationDef = new NodeAssociationDef(
						$association['name'],
						$association['assoc_type_qname'],
						$association['allow_duplicates'] == 1,
						$association['dst_has_many'] == 1,
						$association['dst_required'] == 1,
						$association['dst_strict'] == 1,
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
	private function createQNameFromFriendlyName($friendlyName)
	{
//		$machineName = preg_replace('#[^a-z0-9_ \-]+#', '', strtolower($friendlyName)); // Remove special characters
//		$machineName = str_replace(' ', '-', $machineName); // Replace spaces with underscores
//		$machineName = preg_replace('#-{2,}#', '-', $machineName); // Make sure that there is not more than one underscore in a row

		$machineName = ucwords($friendlyName);
		$machineName = preg_replace('#[^a-zA-Z0-9_ \-]+#', '', $machineName); // Remove special characters
		$machineName = str_replace(' ', '', $machineName);

		$namespace = $this->getRepository()->getDictionaryService()->getLocalNamespaceByAppendingValue('node.type');

		$qname = QName::createQName($namespace, $machineName);

		return $qname;
	}

//	public function createNodeType($parent, $friendlyName, $pluralFriendlyName, $qname = null)
	/**
	 * @inheritdoc
	 */
	public function create($parent, $friendlyName, $pluralFriendlyName, $qname = null)
	{
		if (null === $qname) $qname = self::createQNameFromFriendlyName($friendlyName);
		else $qname = QName::createQName($qname); // Make sure we are working with an object and not just a string

		$def = new NodeTypeRef($parent, $friendlyName, $pluralFriendlyName, $qname);

		$type = new NodeType();
		$type->setRepository($this->getRepository());
		$type->setDef($def);

		return $type;
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
			'required' => $propertyDef->isRequired() ? 1 : 0,
			'sortorder' => $propertyDef->getSortorder(),
			'type' => $propertyDef->getType()
		];
	}

	public function createAssociation($friendlyName, $assocTypeQName, $primaryType, $associatedType)
	{
	}

	/**
	 * @inheritdoc
	 */
	public function save(NodeType $type)
	{
		// Type Definition
		$def = $type->getDef();

		// Make sure we are not trying to save a read-only cnode type
		if ($def->isReadOnly()) throw new Exception('Cannot save readonly types');

		// Set/override dictionary object to make sure that any subsequence requests to this NodeType are returned with the updated definition
//		$this->getRepository()->getDictionaryService()->addType($def);
//echo '<pre>';print_r($this->getRepository()->getDictionaryService());echo '<hr />' . __FILE__ .':'.__LINE__;exit;
		$properties = $def->getProperties();

		// It may be possible, especially on importing objects, that an object could have a UUID defined, but not a Node ID.  In this cases we'll need to check if we can fill in the missing Node ID
		if ($def instanceof NodeTypeRef && null === $def->getNodeId() && null !== $def->getUUID()) {
			/**
			 * @TODO Check if node exists under the referenced UUID
			 */
//			 SELECT node_id FROM nodes WHERE uuid = ''
//			$def->setNodeId($result->node_id);
		}

		$isNew = ($def instanceof NodeTypeRef && null === $def->getNodeId());

		if ($isNew) { // Check and see if this type has already been created

			// Should return null unless this type has already been created
			$existingNodeType = self::getNodeTypeByTypeQName($def->getQName()->toString());

			if ($existingNodeType) {
				/**
				 * Uncomment this for production
				 */
//				throw new \Exception('There is already a content type with this name');
//
//				$nodeTypeArray = self::createNodeTypeArrayFromNodeTypeRef($existingNodeType->getDef());
			}
		}

		if ($isNew) {

//			$QNAME_TYPE = '{com.webimage.node.system}'; // 'com.webimage.system.Type'; // {http://www.cwimage.com/system}type';
			$QNAME_TYPE = '{com.webimage.node.system}Type'; // 'com.webimage.system.Type'; // {http://www.cwimage.com/system}type';
//			$QNAME_TYPE = // 'com.webimage.system.Type'; // {http://www.cwimage.com/system}type
			$QNAME_EXTENSION = '{com.webimage.node.system}Extension'; // {http://www.cwimage.com/system}extension

			$nodeService = $this->getRepository()->getNodeService();
			$node = $nodeService->create($QNAME_TYPE);
			$node->save();
			/** @var NodeRef $nodeRef */
			$nodeRef = $node->getNodeRef();

			$def->setNodeId($nodeRef->getNodeId());

			$tablePrefix = $def->isExtension() ? 'nx' : 'nt'; // nt = node type; nx = node extension
			$tableKey = $def->getPluralName();
			$tableKey = strtolower($tableKey); // Lower case
			$tableKey = preg_replace('/[^0-9a-z_]+/', '', $tableKey);
			#$tableKey = $tablePrefix . '_' . $cnode->id . '_' . $tableKey;
			$tableKey = $tablePrefix . '_' . $tableKey;

			$def->setTableKey($tableKey);
		}

		$nodeTypeData = self::createNodeTypeDataFromNodeTypeRef($def);

		$qb = $this->getConnectionManager()->createQueryBuilder();
		$qb->insert('node_types')
			->values(array_map(function($val) {
				return '?';
				}, $nodeTypeData))
			->setParameters(array_values($nodeTypeData))
			->execute();

		/**
		 * Create the required table(s)
		 */
		if (strlen($def->getTableKey()) > 0) {

			$cm = $this->getConnectionManager();
			$conn = $this->getConnectionManager()->getConnection();
			$sm = $conn->getSchemaManager();
			$schema = $sm->createSchema();
			$tableName = $cm->getTableName($def->getTableKey());

			$existingTable = $schema->hasTable($tableName) ? $schema->getTable($tableName) : null;
			$table = $existingTable ? clone $existingTable : $schema->createTable($tableName);

			//$schema->hasTable($tableName) ? $schema->getTable($tableName) : $schema->createTable($tableName);

//				echo '<pre>';print_r($schema);echo '<hr />' . __FILE__ .':'.__LINE__;exit;
			if (!$table->hasColumn('node_id')) $table->addColumn('node_id', Type::BIGINT, array('notnull' => true, 'unsigned' => true));
			if (!$table->hasColumn('node_version')) $table->addColumn('node_version', Type::INTEGER, array('notnull' => true, 'unsigned' => true, 'default' => 1));
			if (!$table->hasPrimaryKey()) $table->setPrimaryKey(array('node_id', 'node_version'));

			/**
			 *  Add properties (only supports simple types for now)
			 * @var NodeTypePropertyRef $property
			 */
			foreach($properties as $property) {

				// By default, all properties will be attached as columns to the main type table
				$workingTable = $table;
				$existingPropertyTable = null; // Used for isMultiValued()

				// If multi-valued, a separate table will be created to store the data for the property
				if ($property->isMultiValued()) {

					// Setup name to be used for property table
					$propertyTableKey = $def->getTableKey() . '_p_' . $property->getKey(); // 'cnt_prop_' . $property->getKey();
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
				if ($dataType = $this->getRepository()->getDictionaryService()->getDataType($property->getType())) {

					$modelFields = $dataType->getModelFields();

					if (count($modelFields) == 0) {
						throw new Exception($property->getType() . ' does not have defined database fields');
					}
					/** @var DataTypeModelField $modelField */
					foreach($modelFields as $modelField) {
						/**
						 * By default, $modelFieldName will be empty, in which case the property name is assumed to already be defined
						 *
						 * However, if a value is defined then it will be appended to the property name in the format: {$propertyFieldName}_{$modelFieldName}.  This will generally happen if the model_field definition includes multiple fields
						 */
						$modelFieldName = $modelField->getName(); // My default this will be empty

						$propertyFieldName = $property->getKey();
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

				} else {
					throw new Exception('Unsupported type: ' . $property->getType());
				}

				// We have to save the working table (the property specific table) separately here to make sure that it is attached correctly to the main table model
				if ($property->isMultiValued()) {

					if ($existingPropertyTable) {
						$comparator = new Comparator();
						$diff = $comparator->diffTable($existingPropertyTable, $propertyTable);
						if ($diff) $sm->alterTable($diff);
					} else {
						$sm->createTable($workingTable);
					}
				}
				echo 'Working table: ' . $workingTable->getName().'<br>';
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
		 * Create property entries
		 */
//		$nodeTypeArray = self::createNodeTypeDataFromNodeTypeRef($def);
//		echo '<pre>';print_r($nodeTypeArray);echo '<hr />' . __FILE__ .':'.__LINE__;exit;

		/**
		 * Save property database records
		 * @var NodeTypePropertyRef $propertyDef
		 */
		foreach($properties as $property => $propertyDef) {

			$isPropertyNew = strlen($propertyDef->getNodeTypeId()) == 0;
			$propertyDef->setNodeTypeID($def->getNodeId());

			$nodeTypePropertyData = self::createNodeTypePropertyDataFromNodeTypePropertyDef($propertyDef);

			$qb = $cm->createQueryBuilder();
			$query = null;

			if ($isPropertyNew) { // New property
				$query = $qb->insert('node_type_properties');
			} else { // Update existing property
				$query = $qb->update('node_type_properties');
				$query->where(
					[
						'node_type_id' => $propertyDef->getNodeTypeId(),
						'key' => $propertyDef->getKey()
					]
				);
			}

			$columns = array_map(function($c) {
				return '`' . $c . '`';
				}, array_keys($nodeTypePropertyData)
			);

			$query->values(array_map(function () {
				return '?';
			}, array_flip($columns)))
				->setParameters(array_values($nodeTypePropertyData))
				->execute();
		}
	}

	/**
	 * Exports a node type to xml format (via CWI_XML_Traversal) and adds any required dependencies to exporter
	 * @param CWI_NODE_SERVICE_TRANSFER_Exporter $exporter An object used to build dependies
	 * @param string $typeQName The string version of a CWI_NODE_SERVICE_QName for the type to be exported
	 * @param string $version The version of the NodeType to be exported - as the time of this documentation NodeTypes do not even support versioning, so this is purely put in now to plan for the future
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
		$exporter->addRequiredNodeType($nodeType->getDef()->getQName()->toString(), $nodeType->getDef()->getNodeVersion());
		// Export the Node XML object (CWI_XML_Traversal)
		$xmlTraversal = $nodeType->export($exporter);
		// Finally, update the exporter with the returned XML Object

		$exporter->setNodeTypeXmlTraversal($nodeType->getDef()->getQName()->toString(), $nodeType->getDef()->getNodeVersion(), $xmlTraversal);
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
	 * @return Manager
	 */
	public function getConnectionManager()/*: Manager */
	{
		return $this->connectionManager;
	}
}