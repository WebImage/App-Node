<?php

/**
 * Heavily borrowed from Doctrine's Type::getType()
 */
namespace WebImage\Node\Types;

abstract class Type {
	const BOOLEAN = 'boolean';
	const DATE = 'date';
	const DATETIME = 'datetime';
	const DECIMAL = 'decimal';
	const INTEGER = 'integer';
	const STRING = 'string';
	const TEXT = 'text';

	private static $_resolvedTypes = [];

	private static $_typeMap = [
		self::BOOLEAN => 'WebImage\Node\Types\BooleanType',
		self::DATE => 'WebImage\Node\Types\DateType',
		self::DATETIME => 'WebImage\Node\Types\DateTimeType',
		self::DECIMAL => 'WebImage\Node\Types\Decimal',
		self::INTEGER => 'WebImage\Node\Types\IntegerType',
		self::STRING => 'WebImage\Node\Types\StringType',
		self::TEXT => 'WebImage\Node\Types\TextType'
	];

	/**
	 * Force us of getType() factory method.
	 */
	final private function __construct() {}

	/**
	 * Factory method to instantiate a specific type by name
	 *
	 * @param string $name
	 *
	 * @return Type
	 */
	public static function getType($name)
	{
		if (!isset(self::$_resolvedTypes[$name])) {
			if (!self::hasType($name)) {
				throw new \InvalidArgumentException(sprintf('Type not found: %s.', $name));
			}
			self::$_resolvedTypes[$name] = new self::$_typeMap[$name];
		}

		return self::$_resolvedTypes[$name];
	}

	/**
	 * The internal name of the type, equal to the key in $_typeMap
	 *
	 * @return string
	 */
	abstract public function getTypeName();

	/**
	 * Get the structure of the underlying data type..
	 *
	 * Generally this will be the same as getTypeName()
	 * unless new functionality is added to a base type,
	 *
	 * @return string
	 */
	public function getDataTypeName()
	{
		return $this->getTypeName();
	}

	/**
	 * Get the user friendly name associated with the type
	 *
	 * @return string
	 */
	abstract public function getName();

	/**
	 * Add an additional supported type.
	 *
	 * @param $name
	 * @param $className
	 *
	 * @return null
	 */
	public static function addType($name, $className)
	{
		if (self::hasType($name)) {
			throw new \RuntimeException(sprintf('Type already exists: %s.', $name));
		}

		self::$_typeMap[$name] = $className;
	}

	/**
	 * Check if a type exists.
	 *
	 * @param string $name
	 *
	 * @return bool
	 */
    public static function hasType($name)
    {
    	return isset(self::$_typeMap[$name]);
    }

	/**
	 * Override an existing type.
	 *
	 * @param $name
	 * @param $className
	 */
    public static function overrideType($name, $className)
    {
    	if (!self::hasType($name)) {
    		throw new \RuntimeException(sprintf('Type not found: %s', $name));
	    }

	    if (isset(self::$_resolvedTypes[$name])) {
    		unset(self::$_resolvedTypes[$name]);
	    }

	    self::addType($name, $className);
    }

	/**
	 * Get the internal type map
	 *
	 * @return array
	 */
    public static function getTypesMap()
    {
		return self::$_typeMap;
    }
}