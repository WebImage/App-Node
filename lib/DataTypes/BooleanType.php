<?php

namespace WebImage\Node\DataTypes;

class BooleanType extends DataType
{
	public function getTypeName()
	{
		return DataType::BOOLEAN;
	}

	public function getName()
	{
		return 'Boolean';
	}
}