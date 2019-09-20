<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Service\Repository;
use WebImage\Node\Service\DictionaryService;
use WebImage\ServiceManager\ServiceManagerAwareInterface;
use WebImage\ServiceManager\ServiceManagerInterface;

class DELETERepositoryFactory implements ServiceManagerAwareInterface {
	/** @property ServiceManagerInterface */
	private $serviceManager;

	public function getServiceManager()
	{
		// TODO: Implement getServiceManager() method.
	}

	public function setServiceManager(ServiceManagerInterface $sm)
	{
		// TODO: Implement setServiceManager() method.
	}


	public static function createRepository() {
		static $repository;
		
		if (null === $repository) {
			/**
			 * Setup the repository
			 */
			$repository = new Repository();

			/**
			 * Add node service
			 */
			$node_service = new NodeService();
			$node_service->setRepository($repository);
			$repository->setNodeService($node_service);
			/**
			 * Add node type service
			 */
			$node_type_service = new NodeTypeService();
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
			$dictionary_service = new DictionaryService();
			// Probably not the best way to do this, but this will work for now:
//			$config_file = dirname(dirname(dirname(dirname(__FILE__)))) . '/config/maindictionary.xml';
//
//			 Get contents of XML file
//			$raw_xml = file_get_contents($config_file);
//			 Try to parse
//			try {
//				$xml_dictionary = CWI_XML_Compile::compile($raw_xml);
//				$dictionary_service->addConfig($xml_dictionary);
//			} catch (Exception $e) {
//				 Do something else...
//				#throw new Exception('Unable to create dictionary: ' . $e->getMessage());
//			}
//
			$dictionary_service->setRepository($repository);
			$repository->setDictionaryService($dictionary_service);
		}
		
		return $repository;
	}
}