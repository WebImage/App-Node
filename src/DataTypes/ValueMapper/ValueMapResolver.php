<?php

namespace WebImage\Node\DataTypes\ValueMapper;

use WebImage\TypeResolver\TypeResolver;

class ValueMapResolver extends TypeResolver
{
	public function resolveTyped(string $key, $configurator = null): ValueMapperInterface
	{
		return parent::resolve($key, $configurator);
	}
}