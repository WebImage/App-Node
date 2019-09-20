<?php

namespace WebImage\Node\Query;

class AssociationValue
{
	/**
	 * @var string
	 */
	private $qName;
	/**
	 * @var string
	 */
	private $value;

	/**
	 * AssociationValue constructor.
	 *
	 * @param $qName
	 * @param $value
	 */
	public function __construct($qName, $value)
	{
		$this->qName = $qName;
		$this->value = $value;
	}

	/**
	 * Get the association type QName
	 *
	 * @return string
	 */
	public function getQName()
	{
		return $this->qName;
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