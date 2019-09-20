<?php

namespace WebImage\Node\DataTypes;

class DecimalType extends Type
{
	public function getTypeName()
	{
		return Type::DECIMAL;
	}

	public function getName()
	{
		return 'Decimal';
	}
}