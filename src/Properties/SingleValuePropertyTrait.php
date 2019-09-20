<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;

trait SingleValuePropertyTrait
{
	/**
	 * @property Dictionary
	 */
	private $value;

	public function reset()
	{
		$this->value = new Dictionary();
	}

	/**
	 * @inheritdoc
	 */
	public function getValue($subKey='')
	{
		return $this->value->get($subKey);
	}

	/**
	 * @inheritdoc
	 */
	public function getValueDictionary()
	{
		return new ImmutableDictionary($this->value->toArray());
	}

	/**
	 * @inheritdoc
	 */
	public function setValue($value, $forSubKey='')
	{
		if (!is_string($forSubKey)) {
			throw new \InvalidArgumentException('forSubKey must be a string value');
		}

		if (is_object($value)) {
			if (!($value instanceof Dictionary)) throw new \InvalidArgumentException(sprintf('%s was expecting a Dictionary', __METHOD__));
			if (!empty($forSubKey)) throw new \InvalidArgumentException(sprintf('%s should not receive a forSubKey value when specifying a Dictionary for $value'));
		} else if (is_array($value)) {
			if (!empty($forSubKey)) throw new \InvalidArgumentException(sprintf('%s should not receive a forSubKey value when specifying an array for $value'));
			$value = new Dictionary($value);
		} else if (is_string($value) || is_numeric($value) || null === $value) {
			$newValue = $value;
			$value = null === $this->value ? new Dictionary() : $this->value;
			$value->set($forSubKey, $newValue);
		} else {
			throw new \InvalidArgumentException(sprintf('%s was expecting a scalar value', __METHOD__));
		}

		$this->value = $value;
	}
}