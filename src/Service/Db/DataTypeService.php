<?php

namespace WebImage\Node\Service\Db;

use WebImage\Core\Dictionary;
use WebImage\Node\DataTypes\ValueMapper\ValueMapperInterface;
use WebImage\Node\DataTypes\ValueMapper\ValueMapperNotFoundException;
use WebImage\Node\DataTypes\ValueMapper\ValueMapResolver;
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
	private $NOMAPPER = 'NOMAPPER';
	private $valueMappers = [];
	/** @var ValueMapResolver */
	private $valueMapResolver;

	/**
	 * DataTypeService constructor.
	 */
	public function __construct(ValueMapResolver $valueMapResolver)
	{
		$this->valueMappers = new Dictionary();
		$this->valueMapResolver = $valueMapResolver;
	}


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
	 * @return DataType
	 */
	public function getDataType($dataTypeName): DataType
	{
		return $this->getRepository()->getDictionaryService()->getDataType($dataTypeName);
	}

	/**
	 * @return DataType[]|Dictionary<string, DataType> A dictionary of defined data types
	 */
	public function getDataTypes()
	{
		return $this->getRepository()->getDictionaryService()->getDataTypes();
	}

	/**
	 * @inheritdoc
	 */
	public function valueForStorage(string $dataTypeName, $value)
	{
		$mapper = $this->getDataValueMapper($dataTypeName);

		return null === $mapper ? $value : $mapper->forStorage($value);
	}

	/**
	 * @inheritdoc
	 */
	public function valueForProperty(string $dataTypeName, $value)
	{
		$mapper = $this->getDataValueMapper($dataTypeName);

		return null === $mapper ? $value : $mapper->forProperty($value);
	}

	private function getDataValueMapper(string $dataTypeName): ?ValueMapperInterface
	{
		if (!$this->valueMappers->has($dataTypeName)) {
			$dataType = $this->getDataType($dataTypeName);
			$valueMapper = $dataType->getValueMapper();

			if (null === $valueMapper) {
				$this->valueMappers->set($dataTypeName, $this->NOMAPPER);
				return null;
			}

			$this->valueMappers->set($dataTypeName, $this->valueMapResolver->resolve($valueMapper));
		}

		$mapper = $this->valueMappers->get($dataTypeName);

		return $mapper == $this->NOMAPPER ? null : $mapper;
	}
}