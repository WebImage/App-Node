<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;

interface MultiValuePropertyInterface
{
	/**
	 * Return values
	 *
	 * @return SingleValuePropertyInterface[]
	 */
	public function getValues();

	/**
	 * Sets the root values
	 *
	 * @param SingleValuePropertyInterface[] $values
	 */
	public function setValues(array $values);

	/**
	 * Add a value to the internal collection
	 *
	 * @param string|SingleValuePropertyInterface $value
	 */
	public function addValue($value);

	/**
	 * Reset everything to a blank slate
	 */
	public function reset();
}