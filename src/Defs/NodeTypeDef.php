<?php

namespace WebImage\Node\Defs;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Node\Defs\NodeTypePropertyDictionary;

class NodeTypeDef implements NodeTypeDefInterface {
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
	/**
	 * @var bool $isAbstract Whether nodes can instantiate instances of this type or not
	 */
	private $isAbstract = false;

	public function __construct($parent=null, $name=null, $pluralName=null, $qname=null, Config $config=null, $uuid=null, $version=null) {
		if (null === $config) $config = new Config();
		$this->properties = new NodeTypePropertyDictionary();
		if (null !== $parent) $this->setParent($parent);
		$this->setName($name);
		$this->setPluralName(null === $pluralName ? $name : $pluralName);
		$this->setQName($qname);
		$this->setUuid($uuid);
		$this->setVersion($version);
		$this->setConfig($config);
		$this->setReadOnly(false);
		$this->setFinal(false);
	}

	/**
	 * @return string|null
	 */
	public function getName(): ?string
	{
		return $this->name;
	}

	/**
	 * @return string|null
	 */
	public function getPluralName(): ?string
	{
		return $this->pluralName;
	}

	/**
	 * @return QName|null
	 */
	public function getQName(): ?string
	{
		return $this->qname;
	}

	/**
	 * @return string|null
	 */
	public function getUuid(): ?string
	{
		return $this->uuid;
	}

	/**
	 * @return string|null
	 */
	public function getVersion(): ?string
	{
		return $this->version;
	}

	/**
	 * @return Config|null
	 */
	public function getConfig(): ?Config
	{
		return $this->config;
	}

	/**
	 * @return \WebImage\Node\Defs\NodeTypePropertyDictionary|\WebImage\Node\Defs\NodeTypePropertyDef[]
	 */
	public function getProperties(): NodeTypePropertyDictionary
	{
		return $this->properties;
	}

	/**
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function getProperty($key)
	{
		return $this->properties->get($key);
	}

	/**
	 * @return string|null
	 */
	public function getParent(): ?string
	{
		return $this->parent;
	}

	/**
	 * @return string[]
	 */
	public function getAssociations(): array
	{
		return $this->associations;
	}

	/**
	 * @return array
	 */
	public function getExtensions(): array
	{
		return $this->extensions;
	}

	/**
	 * @return bool
	 */
	public function isExtension(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getQName()));

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

	public function setProperty($key, NodeTypePropertyDef $definition)
	{
		$this->properties->set($key, $definition);
	}

	public function setProperties(NodeTypePropertyDictionary $dictionary)
	{
		$this->properties = $dictionary;
	}

	/**
	 * Sets the types parent.
	 * $parent should NEVER be null, as this would indicate a ROOT node type, and there
	 * should already have been one defined (when the constructor value would be NULL)
	 *
	 * @param string $parent The name of the types parent
	 */
	public function setParent(string $parent)
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

	/**
	 * @inheritdoc
	 */
	public function isReadOnly(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getQName()));

		return $this->readOnly;
	}

	/**
	 * @inheritdoc
	 */
	public function isFinal(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getQName()));

		return $this->isFinal;
	}

	/**
	 * @inheritdoc
	 */
	public function isAbstract(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getQName()));

		return $this->isAbstract;
	}

	/**
	 * @inheritdoc
	 */
	public function setReadOnly($readOnly)
	{
		$this->readOnly = $readOnly;
	}

	/**
	 * @inheritdoc
	 */
	public function setFinal(bool $isFinal)
	{
		$this->isFinal = $isFinal;
	}

	/**
	 * @inheritdoc
	 */
	public function setAbstract(bool $isAbstract)
	{
		$this->isAbstract = $isAbstract;
	}
}