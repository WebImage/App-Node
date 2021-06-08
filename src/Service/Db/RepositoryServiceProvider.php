<?php

namespace WebImage\Node\Service\Db;

use League\Container\Definition\CallableDefinition;
use League\Container\Definition\ClassDefinition;
use League\Container\Definition\ClassDefinitionInterface;
use League\Container\Definition\DefinitionInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ContainerInterface;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Db\ConnectionManager;
use WebImage\Node\DataTypes\ValueMapper\ValueMapResolver;
use WebImage\Node\Service\DataTypeServiceInterface;
use WebImage\Node\Service\DictionaryService;
use WebImage\Node\Service\NodeServiceInterface;
use WebImage\Node\Service\NodeTypeServiceInterface;
use WebImage\Node\Service\Repository;
use WebImage\Node\Service\RepositoryInterface;

class RepositoryServiceProvider extends AbstractServiceProvider
{
	protected $provides = [
		RepositoryInterface::class,
		NodeServiceInterface::class,
		NodeTypeServiceInterface::class,
		DataTypeServiceInterface::class
	];

	public function register()
	{
		$container = $this->getContainer();

		/** @var ClassDefinitionInterface $repositoryDef */
		#$repositoryDef = $container->share(RepositoryInterface::class);
		$this->registerRepository($container);
		$this->registerNodeService($container);
		$this->registerNodeTypeService($container);
		$this->registerDataTypeService($container);
		$this->registerDictionaryService($container);
	}

	protected function registerRepository(ContainerInterface $container)
	{
		$def = $container->share(RepositoryInterface::class, function() use ($container) {
			$repository = new Repository();
			$repository->setNodeService($container->get(NodeServiceInterface::class));
			$repository->setNodeTypeService($container->get(NodeTypeServiceInterface::class));
			$repository->setDataTypeService($container->get(DataTypeServiceInterface::class));

			$localNamespace = $container->get(ApplicationInterface::class)->getConfig()->get('nodes.localNamespace');

			$dictionary = new DictionaryService($localNamespace);
			$dictionary->setRepository($repository);
			$repository->setDictionaryService($dictionary);

			$config_file = __DIR__ . '/../../../config/dictionary.php';

			$config = new Config(require($config_file));
			$dictionary->addConfig($config);

			return $repository;
		});
	}

	protected function registerNodeService(ContainerInterface $container)
	{
		/** @var CallableDefinition $def */
		$def = $container->share(NodeServiceInterface::class, function () use ($container) {
			return new NodeService($container->get(ConnectionManager::class));
		});
	}

	protected function registerNodeTypeService(ContainerInterface $container)
	{
		/** @var ClassDefinitionInterface $def */
		$def = $container->share(NodeTypeServiceInterface::class, function() use ($container) {
			return new NodeTypeService($container->get(Connectionmanager::class));
		});
	}

	protected function registerDataTypeService(ContainerInterface $container)
	{
		/** @var ClassDefinitionInterface $def */
		$def = $container->share(DataTypeServiceInterface::class, function() use ($container) {
			$valueMapResolver = $container->get(ValueMapResolver::class);
			return new DataTypeService($valueMapResolver);
		});
	}

	protected function registerDictionaryService(ContainerInterface $container)
	{
		/** @var ClassDefinitionInterface $def */
		$def = $container->share(DictionaryService::class, function() use ($container) {
			$localNamespace = $container->get(ApplicationInterface::class)->getConfig()->get('nodes.localNamespace');
			$dictionary = new DictionaryService($localNamespace);
			$config_file = __DIR__ . '/../../../config/dictionary.php';

			$config = new Config(require($config_file));
			$dictionary->addConfig($config);

			return $dictionary;
		});
	}
}