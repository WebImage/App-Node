<?php

namespace WebImage\Node\DataTypes;

class IntegerType extends DataType
{
	/**
	 * @inheritdoc
	 */
	public function getTypeName()
	{
		return DataType::INTEGER;
	}

	/**
	 * @inheritdoc
	 */
	public function getName()
	{
		return 'Integer';
	}
}