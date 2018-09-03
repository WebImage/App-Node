<?php

namespace WebImage\Node\Service;

use RuntimeException;

class Repository implements RepositoryInterface
{
	/** @var NodeServiceInterface */
	private $nodeService;
	/** @var NodeTypeServiceInterface */
	private $nodeTypeService;
	/** @var  DictionaryService */
	private $dictionaryService;
	/** @var  DataTypeServiceInterface */
	private $dataTypeService;

	/**
	 * Get the node service
	 *
	 * @return NodeServiceInterface
	 */
	public function getNodeService()
	{
		if (null === $this->nodeService) {
			throw new RuntimeException('The Node Service is unavailable');
		}

		return $this->nodeService;
	}
	/**
	 * Get the node type service
	 * @return NodeTypeServiceInterface
	 */
	public function getNodeTypeService()
	{
		if (null === $this->nodeTypeService) {
			throw new RuntimeException('The Node Type Service is unavailable');
		}

		return $this->nodeTypeService;
	}

	/**
	 * Get the dictionary service
	 *
	 * @return DictionaryService
	 */
	public function getDictionaryService()
	{
		if (null === $this->dictionaryService) {
			throw new RuntimeException('The Dictionary service is unavailable');
		}

		return $this->dictionaryService;
	}

	/**
	 * Get the data type service
	 *
	 * @return DataTypeServiceInterface
	 */
	public function getDataTypeService()
	{
		if (null === $this->dataTypeService) {
			throw new RuntimeException('The Data Type Service is unavailable');
		}

		return $this->dataTypeService;
	}

	/**
	 * Set the node service
	 * @param NodeServiceInterface $service
	 */
	public function setNodeService(NodeServiceInterface $service)
	{
		$this->nodeService = $service;
	}

	/**
	 * Set the node type service
	 *
	 * @param NodeTypeServiceInterface $service
	 */
	public function setNodeTypeService(NodeTypeServiceInterface $service)
	{
		$this->nodeTypeService = $service;
	}

	/**
	 * Set the dictionary service
	 *
	 * @param DictionaryService $service
	 */
	public function setDictionaryService(DictionaryService $service)
	{
		$this->dictionaryService = $service;
	}

	/**
	 * Set the data type service
	 *
	 * @param DataTypeServiceInterface $dataTypeService
	 */
	public function setDataTypeService(DataTypeServiceInterface $dataTypeService)
	{
		$this->dataTypeService = $dataTypeService;
	}
}