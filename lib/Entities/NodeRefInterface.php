<?php

namespace WebImage\Node\Entities;
/**
 * Interface NodeRefInterface
 *
 * An open-ended interface that is used to identify an ID within a specific repository
 */
interface NodeRefInterface {
	/**
	 * Get the unique identifier for the node
	 *
	 * @return string
	 */
	public function getUuid();
}