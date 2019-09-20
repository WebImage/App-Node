<?php

namespace WebImage\Node\DataTypes;

class TextType extends Type
{
	public function getTypeName()
	{
		return Type::TEXT;
	}

	public function getName()
	{
		return 'Text';
	}
}