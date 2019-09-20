<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;
use WebImage\Node\Defs\NodeTypePropertyDef;

abstract class AbstractProperty implements PropertyInterface {
	/**
	 * @var NodeTypePropertyDef
	 */
	private $def;

	public function __construct()
	{
		$this->reset();
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
	 * @inheritdoc
	 */
	abstract public function reset();
}