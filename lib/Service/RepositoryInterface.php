<?php

namespace WebImage\Node\Service;

interface RepositoryInterface
{
	/**
	 * Get the node service
	 *
	 * @return NodeServiceInterface
	 */
	public function getNodeService();

	/**
	 * Get the node type service
	 * @return NodeTypeServiceInterface
	 */
	public function getNodeTypeService();

	/**
	 * Get the dictionary service
	 *
	 * @return DictionaryService
	 */
	public function getDictionaryService();

	/**
	 * Get the data type service
	 *
	 * @return DataTypeServiceInterface
	 */
	public function getDataTypeService();

	/**
	 * Set the node service
	 * @param NodeServiceInterface $service
	 */
	public function setNodeService(NodeServiceInterface $service);

	/**
	 * Set the node type service
	 *
	 * @param NodeTypeServiceInterface $service
	 */
	public function setNodeTypeService(NodeTypeServiceInterface $service);

	/**
	 * Set the dictionary service
	 *
	 * @param DictionaryService $service
	 */
	public function setDictionaryService(DictionaryService $service);

	/**
	 * Set the data type service
	 *
	 * @param DataTypeServiceInterface $dataTypeService
	 */
	public function setDataTypeService(DataTypeServiceInterface $dataTypeService);
}