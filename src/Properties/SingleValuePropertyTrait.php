<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;

trait SingleValuePropertyTrait
{
	/**
	 * @property mixed
	 */
	private $value;

	public function reset()
	{
		$this->value = null;
	}

	/**
	 * @inheritdoc
	 */
	public function getValue()
	{
		return $this->value;
	}

	/**
	 * @inheritdoc
	 */
	public function setValue($value)
	{
		$this->value = $value;
	}
}