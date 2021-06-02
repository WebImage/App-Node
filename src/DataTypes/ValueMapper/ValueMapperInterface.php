<?php

namespace WebImage\Node\DataTypes\ValueMapper;

use WebImage\Core\Dictionary;

interface ValueMapperInterface
{
	/**
	 * Takes a value as returned from a property and converts it to a value that can be used for storage
	 * If the value is scalar, it should be returned as such.  If the value is more complex then it should be returned as a Dictionary
	 * @param int|float|string|boolean|Dictionary $value
	 *
	 * @return mixed
	 */
	public function forStorage($value);

	/**
	 * Takes a value as returned from storage and converts it into a scalar value or specific object type (e.g. NodeRef)
	 * @param int|float|string|boolean|Object $value
	 *
	 * @return mixed
	 */
	public function forProperty($value);
}