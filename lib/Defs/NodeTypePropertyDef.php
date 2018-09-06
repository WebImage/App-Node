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
	private $qname;
	/**
	 * @var bool
	 */
	private $isRequired;
	/**
	 * @var mixed
	 */
	private $default;
	/**
	 * @var int
	 */
	private $sortorder;
	/**
	 * @var string
	 */
	private $nodeTypeTypeQName; // fully qualified qname for the type that the property is being attached to, e.g. {http://www.cwimage.com/model}base (rather than a QName object)
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
	 * @param string $nodeTypeTypeQName
	 * @param string $key
	 * @param string $name
	 * @param string $qname
	 * @param bool $required
	 * @param mixed $default
	 * @param bool $isMultiValued
	 * @param int $sortorder
	 * @param Config $config
	 *
	 * @throws \Exception When invalid property configuration is specified
	 */
	function __construct($nodeTypeTypeQName, $key, $name, $qname, $required = false, $default = null, $isMultiValued = false, $sortorder = null, Config $config = null)
	{
		$this->setNodeTypeTypeQName($nodeTypeTypeQName);
		$this->setKey($key);
		$this->setName($name);
		$this->setQName($qname);
		$this->isRequired($required);
		$this->setDefault($default);
		$this->isMultiValued($isMultiValued);
		$this->setSortorder($sortorder);

		if (null === $config) $config = new Dictionary();
		else if (!($config instanceof Dictionary)) throw new \Exception('Invalid property configuration');
		$this->setConfig($config);
	}

	// Getters
	public function getNodeTypeTypeQName()
	{
		return $this->nodeTypeTypeQName;
	}

	public function getKey()
	{
		return $this->key;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getQName()
	{
		return $this->qname;
	}

	public function getDefault()
	{
		return $this->default;
	}

	public function getSortorder()
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
	public function setNodeTypeTypeQName($qnameStr)
	{
		$this->nodeTypeTypeQName = $qnameStr;
	}

	public function setKey($key)
	{
		$this->key = $key;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setQName($qname)
	{
		$this->qname = $qname;
	}

	public function setDefault($default)
	{
		$this->default = $default;
	}

	public function setSortorder($sortorder)
	{
		$this->sortorder = $sortorder;
	}

	public function setConfig(Dictionary $config)
	{
		$this->config = $config;
	}

	// Dual purpose getters/setters
	public function isRequired($trueFalse = null)
	{
		if (null === $trueFalse) { // Getter
			return $this->isRequired;
		} else if (!is_bool($trueFalse)) {
			throw new \InvalidArgumentException(sprintf('%s was expecting a boolean value', __METHOD__));
		} else { // Setter
			$this->isRequired = $trueFalse;
		}
	}

	public function isMultiValued($trueFalse = null)
	{
		if (null === $trueFalse) { // Getter
			return $this->isMultiValued;
		} else if (!is_bool($trueFalse)) {
			throw new \InvalidArgumentException(sprintf('%s was expecting a boolean value', __METHOD__));
		} else { // Setter
			$this->isMultiValued = $trueFalse;
		}
	}

	public function isReadOnly($trueFalse = null)
	{
		if (null === $trueFalse) { // Getter
			return $this->readOnly;
		} else if (!is_bool($trueFalse)) {
			throw new \InvalidArgumentException(sprintf('%s was expecting a boolean value', __METHOD__));
		} else {
			$this->readOnly = $trueFalse;
		}
	}

	public function isSearchable($trueFalse = null)
	{
		if (null === $trueFalse) { // Getter
			return $this->searchable;
		} else if (!is_bool($trueFalse)) {
			throw new \InvalidArgumentException(sprintf('%s was expecting a boolean value', __METHOD__));
		} else {
			$this->searchable = $trueFalse;
		}
	}
}