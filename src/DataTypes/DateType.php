<?php

namespace WebImage\Node\DataTypes;

class DateType extends Type
{
	public function getTypeName()
	{
		return Type::DATE;
	}

	public function getName()
	{
		return 'Date';
	}
}