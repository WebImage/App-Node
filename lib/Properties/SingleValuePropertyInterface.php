<?php

namespace WebImage\Node\Properties;

use WebImage\Core\ImmutableDictionary;

interface SingleValuePropertyInterface
{
	/**
	 * Returns the value for a given property...
	 *
	 * @param string $subKey
	 *
	 * @return string|ImmutableDictionary
	 */
	public function getValue($subKey='');

	/**
	 * Returns a Dictionary representation of the value (useful for structured
	 *
	 * @return ImmutableDictionary
	 */
	public function getValueDictionary();

	/**
	 * Set the property value
	 *
	 * @param string|int|array[string]mixed|Dictionary $value
	 * @param string $forSubKey If this is a complex (i.e. Dictionary) value, then set the value for a specific dictionary value
	 */
	public function setValue($value, $forSubKey='');
}