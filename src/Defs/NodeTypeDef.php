<?php

namespace WebImage\Node\Defs;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Node\Defs\NodeTypePropertyDictionary;
use WebImage\Node\Service\Db\NodeTypePropertyRef;

class NodeTypeDef {
	/**
	 * @property Dictionary $config Allows configuration values to be stored in a key-value fashion
	 */
	private $config;
	/**
	 * @property string The human readable name for the type
	 */
	private $name;
	/**
	 * @property string The human readable name for the type
	 */
	private $pluralName;
	/**
	 * @property string The internal naming mechanism for types
	 */
	private $qname;
	/**
	 * @property string $uuid a unique internal identifier that is associated with the related node object
	 */
//	private $uuid;
	/**
	 * @property string $version
	 */
//	private $version;
	/**
	 * @property string $parent the parent's internal naming mechanism
	 */
	private $parent;
	/**
	 * @property NodeTypePropertyDictionary $properties a collection of properties
	 */
	private $properties;
	/**
	 * @property string[] $associations a collection of qnames that can be used to reference AssociationDef's
	 */
	private $associations = [];
	/**
	 * @property array $extensions a collection of qnames that can be used to reference NodeTypeExtensionDef's
	 */
	private $extensions = [];
	/**
	 * @property boolean $readOnly Whether this type can be modified
	 */
	private $readOnly;
	/**
	 * @property boolean $subClassable Whether other types can extend this type
	 */
	private $subClassable;

	public function __construct($parent=null, $name=null, $pluralName=null, $qname=null, Config $config=null, $uuid=null, $version=null) {
		if (null === $config) $config = new Config();
		$this->properties = new NodeTypePropertyDictionary();
		$this->setParent($parent);
		$this->setName($name);
		$this->setPluralName(null === $pluralName ? $name : $pluralName);
		$this->setQName($qname);
		$this->setUuid($uuid);
		$this->setVersion($version);
		$this->setConfig($config);
		$this->isReadOnly(false);
		$this->isSubClassable(true);
	}

	/**
	 * @return string|null
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getPluralName()
	{
		return $this->pluralName;
	}

	/**
	 * @return QName|null
	 */
	public function getQName()
	{
		return $this->qname;
	}

	/**
	 * @return string|null
	 */
	public function getUuid()
	{
		return $this->uuid;
	}

	/**
	 * @return string|null
	 */
	public function getVersion()
	{
		return $this->version;
	}

	/**
	 * @return Config|null
	 */
	public function getConfig()
	{
		return $this->config;
	}

	/**
	 * @return \WebImage\Node\Defs\NodeTypePropertyDictionary|\WebImage\Node\Defs\NodeTypePropertyDef[]
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * @param $name
	 *
	 * @return mixed|null
	 */
	public function getProperty($name)
	{
		return $this->properties->get($name);
	}

	/**
	 * @return string|null
	 */
	public function getParent()
	{
		return $this->parent;
	}

	/**
	 * @return string[]
	 */
	public function getAssociations()
	{
		return $this->associations;
	}

	/**
	 * @return array
	 */
	public function getExtensions()
	{
		return $this->extensions;
	}

	/**
	 * @return bool
	 */
	public function isExtension()
	{
		return false;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param string $pluralName
	 */
	public function setPluralName($pluralName)
	{
		$this->pluralName = $pluralName;
	}

	/**
	 * @param string $qname
	 */
	public function setQName($qname = null)
	{
		$this->qname = $qname;
	}

	public function setUuid($uuid)
	{
		$this->uuid = $uuid;
	}

	public function setVersion($version)
	{
		$this->version = $version;
	}

	public function setConfig(Config $config)
	{
		$this->config = $config;
	}

	public function setProperty($name, NodeTypePropertyRef $definition)
	{
		$this->properties->set($name, $definition);
	}

	public function setProperties(NodeTypePropertyDictionary $dictionary)
	{
		$this->properties = $dictionary;
	}

	/**
	 * @param string $parent The name of the types parent
	 */
	public function setParent($parent)
	{
		$this->parent = $parent;
	}

	public function addAssociation($association)
	{
		$this->associations[] = $association;
	}

	public function addExtension($typeQName)
	{
		$this->extensions[] = $typeQName;
	}

	// Combined getters/setters
	public function isReadOnly($trueFalse = null)
	{
		if (null === $trueFalse) { // Getter
			return $this->readOnly;
		} else {
			$this->readOnly = $trueFalse;
		}
	}

	public function isSubClassable($trueFalse = null)
	{
		if (null === $trueFalse) { // Getter
			return $this->subClassable;
		} else {
			$this->subClassable = $trueFalse;
		}
	}
}