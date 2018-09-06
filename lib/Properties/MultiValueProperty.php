<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;

class MultiProperty extends Property
{
	/**
	 * @property Dictionary[] $values a collection of dictionary objects that hold all possible facets of a value
	 **/
	private $values = [];

	function __construct()
	{
		$this->resetValues();
	}

	public function isMultiValued()
	{
		return true;
	}

	/**
	 * Return values
	 * @return array
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * Returns native storage vars
	 */
	public function getValueDictionary()
	{
		return array_map(function($value) {
			return ImmutableDictionary($value);
		}, $this->values);
	}

	public function setValues(array $values)
	{
		$this->values = [];

		foreach ($values as $value) {
			$this->addValue($value);
		}
	}

	public function addValue($value)
	{
		if (is_object($value) && !($value instanceof Dictionary)) {
			throw new Exception('Invalid type for setValue');
//		} else if (is_string($value)) { // Convert to dictionary object
//			$d = new Dictionary();
//			$d->set('', $value);
//			$value = $d;
		}

		$this->values[] = $value;
	}

	public function resetValues() {
		$this->values = [];
	}
}