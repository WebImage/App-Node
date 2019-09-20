<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\DataType;
use WebImage\Node\Defs\InputElementDef;
use WebImage\Node\Service\DataTypeServiceInterface;
use WebImage\Node\Service\RepositoryAwareTrait;

/**
 * Manages DataTypes and InputElementDefs (and probably display elements)
 */
class DataTypeService implements DataTypeServiceInterface
{
	use RepositoryAwareTrait;

	/**
	 * Get all input element defs
	 * @return InputElementDictionary of collected input element defs (as InputElementDef
	 */
	public function getInputElementDefs()
	{
		static $already_queries_database;

		if (null === $already_queries_database) $already_queries_database = false;

		// If we have not already queries the database for a list of types, then do so now and add them to the dictionary
		if (!$already_queries_database) {

			// Load required logic to load input element defs

			// Retrieve InputElementDefDictionary
			$rs_defs = NodeDataTypeLogic::getInputElementDefs()->getAll();

			// Iterate through list of types
			while ($element_def = $rs_defs->getNext()) {

				// Add the NodeTypeDef to the dictionary
				$this->getRepository()->getDictionaryService()->setInputElementDef($element_def->getDef());
			}
		}

		// Retrieve all input elements from the dictionary
		return $this->getRepository()->getDictionaryService()->getInputElementDefs();
	}

	/**
	 * Get a specific input element def by class name (the key)
	 *
	 * @return InputElementDef
	 */
	public function getInputElementDef($className)
	{
		return self::getInputElementDefs()->get($className);
	}

	/**
	 * Creates an input element def object
	 *
	 * @param string $className The PHP class name to be used for the input element def
	 * @param string $classFile The file where the PHP class is located
	 * @param string $nameDescription The name the end-user will see
	 *
	 * @return InputElementDef
	 */
	public function createInputElementDef($className, $classFile, $nameDescription)
	{
		return new InputElementDef($className, $classFile, $nameDescription);
	}

	/**
	 * Saves an input element def
	 *
	 * @return InputElementDef
	 */
//	public function saveInputElementDef(InputElementDef $inputElementDef)
//	{
//		 Load required logic to save input element defs
//		NodeDataTypeLogic::registerOrUpdateInputElementDef($inputElementDef);
//		self::getInputElementDefs()->set($inputElementDef->getClassName(), $inputElementDef);
//	}

	/**
	 * @param $dataTypeName
	 *
	 * @return array[string]DataType
	 */
	public function getDataType($dataTypeName)
	{
		return $this->getRepository()->getDictionaryService()->getDataType($dataTypeName);
	}

	/**
	 * @return Dictionary<string, DataType> A dictionary of defined data types
	 */
	public function getDataTypes()
	{
		return $this->getRepository()->getDictionaryService()->getDataTypes();
	}

}