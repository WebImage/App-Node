<?php

namespace WebImage\Node\Query;

class AssociationValue
{
	/**
	 * @var string
	 */
	private $typeQName;
	/**
	 * @var string
	 */
	private $value;

	/**
	 * AssociationValue constructor.
	 *
	 * @param $typeQName
	 * @param $value
	 */
	public function __construct($typeQName, $value)
	{
		$this->typeQName = $typeQName;
		$this->value = $value;
	}

	/**
	 * Get the association type QName
	 *
	 * @return string
	 */
	public function getAssociationTypeQName()
	{
		return $this->typeQName;
	}

	/**
	 * Get the association value
	 *
	 * @return string
	 */
	public function getValue()
	{
		return $this->value;
	}
}