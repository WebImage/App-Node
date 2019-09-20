<?php

namespace WebImage\Node\DataTypes;

class DateTimeType extends Type
{
	public function getTypeName()
	{
		return Type::DATETIME;
	}

	public function getName()
	{
		return 'Date/Time';
	}
}