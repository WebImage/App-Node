<?php

namespace WebImage\Node\Types;

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