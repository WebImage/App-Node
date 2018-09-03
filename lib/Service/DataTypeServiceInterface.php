<?php

namespace WebImage\Node\Service;
use WebImage\Node\Defs\DataType;

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
}