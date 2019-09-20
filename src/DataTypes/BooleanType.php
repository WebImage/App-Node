<?php

namespace WebImage\Node\DataTypes;

class BooleanType extends Type
{
	public function getTypeName()
	{
		return Type::BOOLEAN;
	}

	public function getName()
	{
		return 'Boolean';
	}
}