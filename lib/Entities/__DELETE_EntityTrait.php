<?php

namespace WebImage\Node\Entities;

use WebImage\Node\Defs\NodeTypeDef;

trait EntityTrait {
	private $_def;

	/**
	 * Get the entity's def
	 *
	 * @return NodeTypeDef
	 */
	public function getDef()
	{
		return $this->_def;
	}

	/**
	 * Set the entity's definition
	 *
	 * @param NodeTypeDef $entityDef
	 */
	public function setDef($entityDef) {
		$this->_def = $entityDef;
	}
}