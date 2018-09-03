<?php

namespace WebImage\Node\Query;

class Filter extends Field
{
	/**
	 * @var string
	 */
	private $value;
	/**
	 * @var string
	 */
	private $operator;

	/**
	 * Filter constructor.
	 *
	 * @param string $typeQName
	 * @param string $field
	 * @param mixed $value
	 * @param string $operator
	 */
	public function __construct($typeQName, $field, $value, $operator = '=')
	{
		parent::__construct($typeQName, $field);

		$this->value = $value;
		$this->operator = $operator;
	}

	public function getValue()
	{
		return $this->value;
	}

	public function getOperator()
	{
		return $this->operator;
	}
}