<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;

class MultiValueProperty extends AbstractProperty implements MultiValuePropertyInterface
{
	/**
	 * @property Dictionary[] $values a collection of dictionary objects that hold all possible facets of a value
	 **/
	private $values = [];


	/**
	 * @inheritdoc
	 */
	public function getValues()
	{
		return $this->values;
	}

	/**
	 * @inheritdoc
	 */
	public function setValues(array $values)
	{
		$this->values = [];

		// Pass values to addValue(...) to enforce proper class typing
		foreach ($values as $value) {
			$this->addValue($value);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function addValue($value)
	{
		if (is_object($value) && !($value instanceof SingleValuePropertyInterface)) {
			throw new Exception(sprintf('%s was expecting a ', __METHOD__, SingleValuePropertyInterface::class));
		} else if (is_string($value)) { // Convert to dictionary object
			$v = new MultiValuePropertyValue();
			$v->setValue($value);
			$value = $v;
		}

		$this->values[] = $value;
	}

	public function reset() {
		$this->values = [];
	}
}