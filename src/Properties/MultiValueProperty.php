<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;

class MultiValueProperty extends AbstractProperty implements MultiValuePropertyInterface
{
	/**
	 * @property array $values An array of values
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

		foreach ($values as $value) {
			$this->addValue($value);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function addValue($value)
	{
		$this->values[] = $value;
	}

	public function reset() {
		$this->values = [];
	}
}