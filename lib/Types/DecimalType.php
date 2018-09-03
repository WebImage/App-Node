<?php

namespace WebImage\Node\Types;

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