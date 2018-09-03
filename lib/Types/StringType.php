<?php

namespace WebImage\Node\Types;

class StringType extends Type
{
	public function getTypeName()
	{
		return Type::STRING;
	}

	public function getName()
	{
		return 'String';
	}
}