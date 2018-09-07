<?php

namespace WebImage\Node\Service\Db;

use League\Container\Definition\ClassDefinition;
use League\Container\ServiceProvider\AbstractServiceProvider;
use WebImage\Application\ApplicationInterface;
use WebImage\Db\Manager;
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

			/** @var Manager $connectionManager */
			$connectionManager = $container->get(Manager::class);

			$repository = new Repository();

			$node_service = new NodeService($connectionManager);
			$node_service->setRepository($repository);
			$repository->setNodeService($node_service);

			/**
			 * Add node type service
			 */
			$node_type_service = new NodeTypeService($connectionManager);
			$node_type_service->setRepository($repository);
			$repository->setNodeTypeService($node_type_service);
			/**
			 * Add data type service
			 */
			$data_type_service = new DataTypeService();
			$data_type_service->setRepository($repository);
			$repository->setDataTypeService($data_type_service);
			/**
			 * Add dictionary
			 */
			$localNamespace = $app->getConfig()->get('nodes.localNamespace');
			$dictionary_service = new DictionaryService($localNamespace);
			$dictionary_service->setRepository($repository);
			$repository->setDictionaryService($dictionary_service);

			return $repository;
		});
	}
}