<?php

namespace WebImage\Node\DataTypes;

class DateTimeType extends DataType
{
	public function getTypeName()
	{
		return DataType::DATETIME;
	}

	public function getName()
	{
		return 'Date/Time';
	}
}