<?php

namespace WebImage\Node\Query;

class Sort extends Field
{
	/**
	 * @var string
	 */
	private $sortDirection;

	/**
	 * Sort constructor.
	 *
	 * @param string $typeQName
	 * @param string $field
	 * @param null $sortDirection
	 */
	public function __construct($typeQName, $field, $sortDirection = null)
	{
		parent::__construct($typeQName, $field);
		$this->sortDirection = $sortDirection;
	}

	public function getSortDirection()
	{
		return $this->sortDirection;
	}
}