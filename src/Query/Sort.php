<?php

namespace WebImage\Node\Query;

class Sort extends Property
{
	/**
	 * @var string
	 */
	private $sortDirection;

	/**
	 * Sort constructor.
	 *
	 * @param string $field (preferably fully-qualified field name with type qname, e.g. App.System.Types.SomeType.propertyKey
	 * @param null $sortDirection
	 */
	public function __construct(string $field, $sortDirection = null)
	{
		parent::__construct($field);
		$this->sortDirection = $sortDirection;
	}

	public function getSortDirection()
	{
		return $this->sortDirection;
	}
}