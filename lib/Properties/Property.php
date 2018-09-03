<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;
use WebImage\Node\Defs\NodeTypePropertyDef;

class Property implements PropertyInterface {
	/**
	 * @property Dictionary
	 */
	private $value;
	/**
	 * @var NodeTypePropertyDef
	 */
	private $def;

	/**
	 * Property constructor.
	 */
	public function __construct()
	{
		$this->value = new Dictionary();
	}

	/**
	 * Get the entity's def
	 *
	 * @return NodeTypePropertyDef
	 */
	public function getDef()
	{
		return $this->def;
	}

	/**
	 * Set the entity's definition
	 *
	 * @param NodeTypePropertyDef $def
	 */
	public function setDef($def)
	{
		$this->def = $def;
	}

	/**
	 * @return bool
	 */
	public function isMultiValued()
	{
		return false;
	}

	/**
	 * Returns a string friendly version of this property
	 *
	 * @return bool
	 */
	public function getValue($subKey='')
	{
		return $this->value->get($subKey);
	}

	/**
	 * Returns native storage vars
	 */
	public function getValueDictionary()
	{
		return new ImmutableDictionary($this->value->toArray());
	}

	/**
	 * Set the property value
	 *
	 * @param string|int|array[string]mixed|Dictionary
	 */
	public function setValue($value)
	{
		if (null !== $value) {
			if (is_object($value)) {
				if (!($value instanceof Dictionary)) throw new \InvalidArgumentException(sprintf('%s was expecting a Dictionary', __METHOD__));
			} else if (is_array($value)) {
				$value = new Dictionary($value);
			} else if (is_string($value) || is_numeric($value)) {
				$value = new Dictionary(['' => $value]);
			} else {
				throw new \InvalidArgumentException(sprintf('%s was expecting a string value', __METHOD__));
			}
		}

		$this->value = $value;
	}
}