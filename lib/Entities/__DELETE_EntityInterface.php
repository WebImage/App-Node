<?php

namespace WebImage\Node\Entities;

interface EntityInterface {
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