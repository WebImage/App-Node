<?php

namespace WebImage\Node\DataTypes;

class StringType extends DataType
{
	public function getTypeName()
	{
		return DataType::STRING;
	}

	public function getName()
	{
		return 'String';
	}
}