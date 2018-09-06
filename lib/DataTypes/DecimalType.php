<?php

namespace WebImage\Node\DataTypes;

class DecimalType extends DataType
{
	public function getTypeName()
	{
		return DataType::DECIMAL;
	}

	public function getName()
	{
		return 'Decimal';
	}
}