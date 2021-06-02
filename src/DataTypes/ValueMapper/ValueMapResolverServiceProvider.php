<?php

namespace WebImage\Node\DataTypes\ValueMapper;

use WebImage\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Node\DataTypes\ValueMapper\ValueMapResolver;

class ValueMapResolverServiceProvider extends AbstractServiceProvider
{
	protected $provides = [
		ValueMapResolver::class
	];

	public function register()
	{
		$this->registerDataValueMappers();
	}
	private function registerDataValueMappers() {
		$config = $this->getApplicationConfig();
		$mappers = $config->get('nodes.dataValueMappers', []);

		$mapper = new ValueMapResolver();
		foreach($mappers as $key => $class) {
			$mapper->register($class, $key);
		}

		$this->getContainer()->share(ValueMapResolver::class, $mapper);
	}
}