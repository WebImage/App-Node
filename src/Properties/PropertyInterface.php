<?php

namespace WebImage\Node\Properties;

use WebImage\Node\Defs\NodeTypePropertyDef;

interface PropertyInterface {
	/**
	 * Get the definition for an entity
	 *
	 * @return NodeTypePropertyDef
	 */
	public function getDef();

	/**
	 * Set the definition for an entity
	 *
	 * @param $def
	 */
	public function setDef($def);

	/**
	 * Reset the property value to its original state
	 */
	public function reset();
}