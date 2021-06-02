<?php

namespace WebImage\Node\Service;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Node\Defs\DataType;
use WebImage\Node\Defs\DataTypeModelField;
use WebImage\Node\Defs\NodeTypeDef;
use WebImage\Node\Defs\NodeAssociationDef;
use WebImage\Node\Defs\NodeTypeDefInterface;
use WebImage\Node\Defs\NodeTypeExtensionDef;

//use WebImage\Node\Service\Db\NodeTypeDef;
//use WebImage\Node\Service\Db\DataType;
//use WebImage\Node\Service\Db\NodeTypePropertyDef;
//use WebImage\Node\Defs\InputElementDefDictionary;
//use WebImage\Node\Defs\NodeAssociationDef;
//use WebImage\Node\Defs\NodeTypeAssociationDef;
use WebImage\Node\Service\Db\NodeTypeRefInterface;
use WebImage\Node\Service\JsonNodeTypeConverter;

class DictionaryService implements RepositoryAwareInterface
{
	use RepositoryAwareTrait;

	const DEFAULT_LOCAL_NAMESPACE = 'App';

	private static $TYPE_STANDARD = 1;
	private static $TYPE_EXTENSION = 2;

	/**
	 * @property Dictionary of NodeTypeDef or NodeTypeExtensionDef
	 */
	private $types;
	/**
	 * @property array[string]DataType Dictionary of DataType
	 */
	private $dataTypes;
	/**
	 * @property InputElementDefDictionary of InputElementDef
	 */
	private $inputElementDefs;
	/**
	 * @property Dictionary of NodeAssociationDef
	 */
	private $associations;
	/**
	 * @var Dictionary
	 */
	private $namespaces;
	/**
	 * The local namespace to use for types, e.g. "App" might result in a type namespace of App.Type
	 *
	 * @var string
	 */
	private $localNamespace;

	public function __construct($localNamespace=null)
	{
		/**
		 * Instantiate namespaces object
		 */
		$this->namespaces = new Dictionary();
		/**
		 * Instantiate association defs
		 */
		$this->associations = new Dictionary();
		/**
		 * Instantiate type object
		 */
		$this->types = new Dictionary();
		/**
		 * Instantiate data types object
		 */
		$this->dataTypes = new Dictionary();
		/**
		 * Instatiate data type input elements
		 */
//		$this->inputElementDefs = new CWI_CNODE_DICTIONARY_InputElementDefDictionary();
		/**
		 * Override the default "App" namespace
		 */
		$this->localNamespace = $localNamespace;
	}

	/**
	 * Get the URL namespace for the local site
	 *
	 * @return string
	 */
	public function getLocalNamespace()
	{
		return null === $this->localNamespace ? self::DEFAULT_LOCAL_NAMESPACE : $this->localNamespace;
	}

	/**
	 * Get the URL namespace for the local site and append a value to the end to create the total namespace, e.g. passing "/myvalue" would return "http://{DOMAIN}/myvalue"
	 *
	 * @return string
	 */
	public function getLocalNamespaceByAppendingValue($value)
	{
		$value = '.' . ltrim($value, '.');

		return self::getLocalNamespace() . $value;
	}

	/**
	 * Create a machine friendly key from a user friendly string
	 *
	 * @param $friendlyName
	 *
	 * @return string
	 */
	public function createKeyFromFriendlyName($friendlyName)
	{
		$machine_name = preg_replace('#[^a-z0-9_ ]+#', '', strtolower($friendlyName)); // Remove special characters
		$machine_name = str_replace(' ', '_', $machine_name); // Replace spaces with underscores
		$machine_name = preg_replace('#_{2,}#', '_', $machine_name); // Make sure that there is not more than one underscore in a row

		return $machine_name;
	}

	/**
	 * Get the prefix for a namespace URI
	 *
	 * @param $forNamespaceUri
	 *
	 * @return string|null
	 */
	public function getPrefix($forNamespaceUri)
	{ // http://www.cwimage.com/model/core => cwi
		$namespaces = $this->namespaces->getAll();
		while ($namespace = $namespaces->getNext()) {
			$prefix = $namespace->getKey();
			$ns = $namespace->getDef();
			if ($ns == $forNamespaceUri) {
				return $prefix;
			}
		}

		return null;
	}

	/**
	 * Create a NodeType (or NodeTypeExtension) definition
	 *
	 * @param int $whichType
	 * @param $type
	 *
	 * @return null|NodeTypeDef|NodeTypeExtensionDef
	 */
	private function processTypeOrExtension($whichType, Config $type)
	{
		if (!in_array($whichType, [self::$TYPE_STANDARD, self::$TYPE_EXTENSION])) throw new Exception('Unsupported type');

		$converter = new JsonNodeTypeConverter($this->getRepository());
		$aType = $type->toArray();
		$aType['isExtension'] = $whichType == self::$TYPE_EXTENSION;

		#$type = $converter->fromArray($aType);
		$def = $converter->typeDefFromArray($aType);

		return $def;

//		if ($xml_model = $type->getPathSingle('model')) {
//
//			if ($param_name = $xml_model->getParam('name')) {
//
//				$type_def->setTableKey($param_name);
//
//				if ($model = CWI_MANAGER_ModelManager::getModel($param_name)) {
//					$model_fields = $model->getFields();
//
//					foreach ($model_fields as $field) {
//
//						//__construct($key, $name, $type, $required=false, $default=null, $is_multi_valued=false, $sortorder=null)
//						$field_name = $field->getName();
//						$field_type = null;
//
//						switch ($field->getType()) {
//							case 'int':
//								$field_type = 'd:int';
//								break;
//							case 'varchar':
//								$field_type = 'd:singleline';
//								break;
//							case 'date':
//								$field_type = 'd:date';
//								break;
//							case 'datetime':
//								$field_type = 'd:datetime';
//								break;
//							case 'tinyint':
//								$field_type = 'd:boolean';
//								break;
//							case 'text':
//							default:
//								$field_type = 'd:text';
//						}
//
//						$property_friendly_name = null;
//						$property_read_only = true;
//						$property_searchable = false;
//						// Check if property specific values are specified
//						if ($xml_property = $type->getPathSingle("properties/property[@name='" . $field_name . "']")) {
//							if ($use_friendly_name = $xml_property->getParam('friendlyName')) {
//								$property_friendly_name = $use_friendly_name;
//							}
//							if ($use_searchable = $xml_property->getParam('searchable')) {
//								$property_searchable = ($use_searchable == 'true');
//							}
//							$property_read_only = false;
//						}
//
//
//						$node_property_def = new NodeTypePropertyDef($type_def->getQName()->toString(), $field_name, $property_friendly_name, $field_type);
//						$node_property_def->isReadOnly($property_read_only);
//						$node_property_def->isSearchable($property_searchable);
//						/*
//						$field->getType();
//						$field->isRequired();
//						$field->getSize();
//						$field->getScale();
//						$field->getDefault();
//						$field->isPrimaryKey();
//						$field->isAutoIncrement();
//						*/
//						$type_def->setProperty($field_name, $node_property_def);
//					}
//				} else {
//					throw new Exception('The model ' . $param_name . ' was not found');
//				}
//				#$type_def->setModelName($param_name);
//			}
//		}

//		return $type_def;
	}

	public function addConfig(Config $config)
	{
		$this->addNamespaces($config);
		$this->addTypes($config);
		$this->addExtensions($config);
		$this->addDataTypes($config);
	}

	/**
	 * Namespaces
	 * @var Config $type
	 */
	private function addNamespaces(Config $config)
	{
		/**
		 * Namespaces
		 * @var Config $type
		 */
		foreach($config->get('namespaces', []) as $namespace) {
			throw new \RuntimeException('Adding namespaces is not currently supported');
			$prefix = $namespace->get('prefix');
			$uri = $namespace->get('uri');
			$this->namespaces->set($prefix, $uri);
		}
	}

	/**
	 * Process Types
	 * @param Config $config
	 */
	private function addTypes(Config $config)
	{
		/**
		 * @var Config $type
		 */
		foreach ($config->get('types', []) as $type) {
			$typeDef = self::processTypeOrExtension(self::$TYPE_STANDARD, $type);
			$this->types->set($typeDef->getQName(), $typeDef);
		}
	}

	/**
	 * Process Extensions
	 * @param Config $config
	 */
	private function addExtensions(Config $config)
	{
		/**
		 * @var Config $extension
		 */
		foreach ($config->get('extensions', []) as $extension) {
			$extension_def = self::processTypeOrExtension(self::$TYPE_EXTENSION, $extension);
			$extension_def->setReadOnly(true); // Make sure that changes can't be made to these...
			$extension_def->setFinal(true); // Extensions are not currently extendable
			$this->types->set($extension_def->getQName(), $extension_def);
		}
	}

	/**
	 * Process Data Types
	 * @param Config $config
	 */
	private function addDataTypes(Config $config)
	{
		/**
		 * @var Config $dataType
		 */
		foreach($config->get('dataTypes', []) as $dataType) {

			$type = $dataType->get('type');
			$name = $dataType->get('name');
			$mapper = $dataType->get('mapper');
			$view = $dataType->get('view');

			$typeLabel = $type ?: 'Unknown';

			foreach(['type', 'name'] as $requiredVar) {
				if (!$dataType->has($requiredVar)) {
					$hasKeys = count($dataType) > 0 ? implode(', ', $dataType->keys()) : 'None';
					throw new \RuntimeException(sprintf('Data type (%s) is missing required var: %s (%s)', $typeLabel, $requiredVar, $hasKeys));
				}
			}
			if ($dataType->has('modelFields') && null === $mapper) throw new \RuntimeException(sprintf('A mapper must be defined when there are multiple fields'));

			$dtype = new DataType($type, $name, $mapper, $view);
			$this->addDataTypeModelFields($typeLabel, $dtype, $dataType);

			$this->dataTypes->set($dtype->getType(), $dtype);
		}
	}

	private function addDataTypeModelFields(string $typeLabel, DataType $type, Config $dataType)
	{
		if ($dataType->has('modelFields') && $dataType->has('modelField')) throw new \RuntimeException(sprintf('Data type (%s) must define modelField or modelFields, but not both', $typeLabel));

		$requiredVars = [];
		$modelFields = [];

		if ($dataType->has('modelFields')) {
			$requiredVars = ['type', 'key', 'name'];
			$modelFields = $dataType->get('modelFields');
		} else if ($dataType->has('modelField')) {
			$requiredVars = ['type'];
			$modelFields = [ $dataType->get('modelField') ];
		} else {
			throw new \RuntimeException(sprintf('Data type (%s) must define modelField or modelFields', $typeLabel));
		}

		/**
		 * Ensure that each model field has all required configuration vars
		 * @var Config $modelField
		 */
		foreach($modelFields as $modelField) {
			foreach ($requiredVars as $requiredVar) {
				if (!$modelField->has($requiredVar)) throw new \RuntimeException(sprintf('Data type (%s) model fields must define %s', $typeLabel, $requiredVar));
			}
		}

		/**
		 * Field types
		 * @var Config $modelField
		 */
		foreach ($modelFields as $modelField) {
			$type->addModelField(
				DataTypeModelField::createFromConfig($modelField)
			);
		}
	}

	/**
	 * Get a type by QName string
	 *
	 * @param $typeQNameStr
	 *
	 * @return mixed|null
	 */
	public function getType($typeQNameStr): ?NodeTypeDefInterface
	{
		return $this->types->get($typeQNameStr);
	}

	/**
	 * @return Dictionary|NodeTypeDef[] A dictionary of defined types
	 */
	public function getTypes()
	{
		return $this->types;
	}

	/**
	 * @return Dictionary<string, DataType> A dictionary of defined data types
	 */
	public function getDataTypes()
	{
		return $this->dataTypes;
	}

	/**
	 * @param $type
	 *
	 * @return DataType[string]|null
	 */
	public function getDataType($type)
	{
		return $this->dataTypes->get($type);
	}

	public function getInputElementDef($inputElementClass)
	{
		return $this->inputElementDefs->get($inputElementClass);
	}

	public function getInputElementDefs()
	{
		return $this->inputElementDefs;
	}

	public function getAssociation($assocTypeQName)
	{
		if ($association = $this->associations->get($assocTypeQName)) {
			return $association;
		} else {
			return null;
		}
	}

	public function getParentStack($typeQName)
	{
		$parents = array();
		$type = $this->getType($typeQName);
		$parent_type_qname = $type->getParent();

		if (!empty($parent_type_qname)) {
			$stack = $this->getParentStack($parent_type_qname);
			$parents = array_merge($parents, $stack);
			array_push($parents, $parent_type_qname);
		}

		return $parents;
	}

	public function addType(NodeTypeDef $nodeTypeDef)
	{
		$this->types->set($nodeTypeDef->getQName(), $nodeTypeDef);
	}

	public function addAssociation($associationDef)
	{
		$this->associations->set($associationDef->getQName(), $associationDef);
	}

	public function addInputElementDef($inputElementDef)
	{
		$this->inputElementDefs->set($inputElementDef->getClassName(), $inputElementDef);
	}

	public function setType(NodeTypeDef $def)
	{
		$this->types->set($def->getQName(), $def);
	}

	public function setAssociation(NodeAssociationDef $def)
	{
		$this->associations->set($def->getQName(), $def);
	}
}