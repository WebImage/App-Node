<?php

namespace WebImage\Node\Service;
use WebImage\Core\Dictionary;
use WebImage\Node\Defs\DataType;
use WebImage\Node\Entities\Node;

/**
 * An interface for managing DataTypes and InputElementDefs (and probably display elements)
 */
interface DataTypeServiceInterface extends RepositoryAwareInterface
{
	/**
	 * Methods related to InputElementDefs
	 */
	/**
	 * Get all input element defs
	 * @return InputElementDictionary of collected input element defs
	 */
	public function getInputElementDefs();

	/**
	 * Get a specific input element def by class name (the key)
	 * @return InputElementDef
	 */
	public function getInputElementDef($className);

	/**
	 * Creates an input element def object
	 * @return InputElementDef
	 */
	public function createInputElementDef($className, $classFile, $nameDescription);

	/**
	 * Saves an input element def
	 * @return InputElementDef
	 */
//	public function saveInputElementDef(CWI_CNODE_DICTIONARY_InputElementDef $inputElementDef);

	/**
	 * @param string $dataTypeName
	 *
	 * @return DataType
	 */
	public function getDataType($dataTypeName);

	/**
	 * Get all datatypes
	 * @return DataType[]
	 */
	public function getDataTypes();

	/**
	 * Converts a value to a dictionary for use in storage
	 * @param string $dataTypeName
	 * @param $value
	 * @return mixed
	 */
	public function valueForStorage(string $dataTypeName, $value);

	/**
	 * Converts a dictionary to an value to be added to a Node
	 * @param DataType $dataTypeName
	 * @param Dictionary $dictionary
	 * @return mixed
	 */
	public function valueForProperty(string $dataTypeName, $value);
}