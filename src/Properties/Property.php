<?php

namespace WebImage\Node\Properties;

use WebImage\Core\Dictionary;
use WebImage\Core\ImmutableDictionary;
use WebImage\Node\Defs\NodeTypePropertyDef;

class Property extends AbstractProperty implements SingleValuePropertyInterface
{
	use SingleValuePropertyTrait;
}