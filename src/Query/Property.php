<?php

namespace WebImage\Node\Query;

class Property
{
	/**
	 * @var string
	 */
	private $typeQName;
	/**
	 * @var string
	 */
	private $property;

	/**
	 * Property constructor.
	 *
	 * @param string $sProperty
	 */
	public function __construct($sProperty)
	{
		list($typeQName, $property) = $this->parseProperty($sProperty);
		$this->typeQName = $typeQName;
		$this->property = $property;
	}

	private function parseProperty($str)
	{
		$parts = explode('.', $str);
		$property = array_pop($parts);
		$typeQName = count($parts) > 0 ? implode('.', $parts) : null;

		return [$typeQName, $property];
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
	 * Get the property
	 *
	 * @return string
	 */
	public function getProperty()
	{
		return $this->property;
	}
}