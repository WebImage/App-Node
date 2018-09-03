<?php

namespace WebImage\Node\Types;

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