<?php

use WebImage\Node\DataTypes\ValueMapper\ValueMapResolverServiceProvider;
use WebImage\Node\DataTypes\ValueMapper\NodeRefMapper;
use WebImage\Node\DataTypes\ValueMapper\DateTimeMapper;

return [
	'nodes' => [
		'dataValueMappers' => [
			'node-ref' => NodeRefMapper::class,
			'datetime' => DateTimeMapper::class
		]
	],
	'serviceManager' => [
		'providers' => [
			ValueMapResolverServiceProvider::class
		]
	]
];