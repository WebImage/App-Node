<?php

namespace WebImage\Node\DataTypes\ValueMapper;

use WebImage\Node\DataTypes\ValueMapper\ValueMapperInterface;
use WebImage\TypeResolver\TypeResolver;

class ValueMapResolver extends TypeResolver
{
	public function resolveTyped(string $key, $configurator = null): PropertyValueMapperInterface
	{
		return parent::resolve($key, $configurator);
	}
}