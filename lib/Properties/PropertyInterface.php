<?php

namespace WebImage\Node\Properties;

interface PropertyInterface {
	/**
	 * Whether a property has multiple values
	 *
	 * @return bool
	 */
	public function isMultiValued();
	/**
	 * Get the definition for an entity
	 *
	 * @return mixed
	 */
	public function getDef();

	/**
	 * Set the definition for an entity
	 *
	 * @param $def
	 */
	public function setDef($def);
}