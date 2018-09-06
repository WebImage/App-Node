<?php

namespace WebImage\Node\DataTypes;

class TextType extends DataType
{
	public function getTypeName()
	{
		return DataType::TEXT;
	}

	public function getName()
	{
		return 'Text';
	}
}