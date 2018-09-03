<?php

namespace WebImage\Node\Query;

class Field
{
	/**
	 * @var string
	 */
	private $typeQName;
	/**
	 * @var mixed
	 */
	private $field;

	/**
	 * Field constructor.
	 *
	 * @param $typeQName
	 * @param $field
	 */
	public function __construct($typeQName, $field)
	{
		$this->typeQName = $typeQName;
		$this->field = $field;
	}

	/**
	 * Get the type qname
	 *
	 * @return string
	 */
	public function getTypeQName()
	{
		return $this->typeQName;
	}

	/**
	 * Get the field
	 *
	 * @return mixed
	 */
	public function getField()
	{
		return $this->field;
	}
}