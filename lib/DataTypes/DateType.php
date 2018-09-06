<?php

namespace WebImage\Node\DataTypes;

class DateType extends DataType
{
	public function getTypeName()
	{
		return DataType::DATE;
	}

	public function getName()
	{
		return 'Date';
	}
}