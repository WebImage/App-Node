<?php

namespace WebImage\Node\Service\Db;

use League\Container\Definition\ClassDefinition;
use League\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Application\ApplicationInterface;
use WebImage\Config\Config;
use WebImage\Db\ConnectionManager;
use WebImage\Node\Service\DictionaryService;
use WebImage\Node\Service\Repository;
use WebImage\Node\Service\RepositoryInterface;

class RepositoryServiceProvider extends AbstractServiceProvider
{
	protected $provides = [
		RepositoryInterface::class
	];

	public function register()
	{
		$container = $this->getContainer();

		$container->share(RepositoryInterface::class, function() use ($container) {

			/** @var ApplicationInterface $app */
			$app = $container->get(ApplicationInterface::class);

			/** @var ConnectionManager $connectionManager */
			$connectionManager = $container->get(ConnectionManager::class);

			$repository = new Repository();

			$nodeService = new NodeService($connectionManager);
			$nodeService->setRepository($repository);
			$repository->setNodeService($nodeService);

			/**
			 * Add node type service
			 */
			$nodeTypeService = new NodeTypeService($connectionManager);
			$nodeTypeService->setRepository($repository);
			$repository->setNodeTypeService($nodeTypeService);
			/**
			 * Add data type service
			 */
			$dataTypeService = new DataTypeService();
			$dataTypeService->setRepository($repository);
			$repository->setDataTypeService($dataTypeService);
			/**
			 * Add dictionary
			 */
			$localNamespace = $app->getConfig()->get('nodes.localNamespace');
			$dictionary = new DictionaryService($localNamespace);
			$dictionary->setRepository($repository);
			$repository->setDictionaryService($dictionary);

			$config_file = __DIR__ . '/../../../config/dictionary.php';
			$config = new Config(require($config_file));
			$dictionary->addConfig($config);

			return $repository;
		});
	}
}