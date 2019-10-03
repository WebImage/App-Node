<?php

namespace WebImage\Node\Defs;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;

class NodeTypePropertyDef
{
	/**
	 * @var string
	 */
	private $key;
	/**
	 * @var sring
	 */
	private $name;
	/**
	 * @var string
	 */
	private $dataType;
	/**
	 * @var bool
	 */
	private $isRequired;
	/**
	 * @var
	 */
	private $isMultiValued;
	/**
	 * @var mixed
	 */
	private $default;
	/**
	 * @var int
	 */
	private $sortorder = 0;
	/**
	 * @var string
	 */
	private $nodeTypeQName; // fully qualified qname for the type that the property is being attached to, e.g. WebImage.Types.Base
	/**
	 * @var bool
	 */
	private $readOnly = false;
	/**
	 * @var bool
	 */
	private $searchable = false;
	/**
	 * @property Config $config Allows configuration values to be stored in a key-value fashion
	 */
	private $config;

	/**
	 * NodeTypePropertyDef constructor.
	 *
	 * @param string $nodeTypeQName
	 * @param string $key
	 * @param string $name
	 * @param string $dataType
	 * @param bool $required
	 * @param mixed $default
	 * @param bool $isMultiValued
	 * @param int $sortorder
	 * @param Config $config
	 *
	 * @throws \Exception When invalid property configuration is specified
	 */
	function __construct(string $nodeTypeQName,
	                     string $key, string $name,
	                     string $dataType,
	                     bool $required = false,
	                     $default = null,
	                     bool $isMultiValued = false,
	                     int $sortorder = null,
	                     Config $config = null)
	{
		$this->setNodeTypeQName($nodeTypeQName);
		$this->setKey($key);
		$this->setName($name);
		$this->setDataType($dataType);
		$this->setRequired($required);
		$this->setDefault($default);
		$this->setMultiValued($isMultiValued);
		if (null !== $sortorder) $this->setSortorder($sortorder);

		if (null === $config) $config = new Dictionary();
		else if (!($config instanceof Dictionary)) throw new \Exception('Invalid property configuration');

		$this->setConfig($config);
	}

	// Getters
	public function getNodeTypeQName()
	{
		return $this->nodeTypeQName;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getDataType(): string
	{
		return $this->dataType;
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function getSortorder(): int
	{
		return $this->sortorder;
	}

	/**
	 * @return Config
	 */
	public function getConfig()
	{
		return $this->config;
	}

	// Setters
	public function setNodeTypeQName(string $qnameStr)
	{
		$this->nodeTypeQName = $qnameStr;
	}

	public function setKey(string $key)
	{
		$this->key = $key;
	}

	public function setName(string $name)
	{
		$this->name = $name;
	}

	public function setDataType(string $qname)
	{
		$this->dataType = $qname;
	}

	public function setDefault($default)
	{
		$this->default = $default;
	}

	public function setSortorder(int $sortorder)
	{
		$this->sortorder = $sortorder;
	}

	public function setConfig(Dictionary $config)
	{
		$this->config = $config;
	}

	// Dual purpose getters/setters
	public function isRequired(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getNodeTypeQName()));

		return $this->isRequired;
	}

	public function setRequired(bool $required)
	{
		$this->isRequired = $required;
	}

	public function isMultiValued(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getNodeTypeQName()));

		return $this->isMultiValued;
	}

	public function setMultiValued(bool $multiValued)
	{
		$this->isMultiValued = $multiValued;
	}

	public function isReadOnly(): bool
	{
		if (func_num_args() > 0) throw new \InvalidArgumentException(sprintf('%s (%s) does not accept any arguments', __METHOD__, $this->getNodeTypeQName()));

		return $this->readOnly;
	}

	public function setReadOnly(bool $readOnly)
	{
		$this->readOnly = $readOnly;
	}

	public function isSearchable(): bool
	{
		return $this->searchable;
	}

	public function setSearchable(bool $searchable)
	{
		$this->searchable = $searchable;
	}
}