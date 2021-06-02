<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Properties\MultiValueProperty;
use WebImage\Node\Properties\Property;
use WebImage\Node\Properties\PropertyInterface;
use WebImage\Node\Service\DataTypeServiceInterface;

class ResultHelper
{
	/**
	 * @var DataTypeServiceInterface
	 */
	private $dataTypeService;
	/**
	 * @var TableNameHelper
	 */
	private $tableNameHelper;

	/**
	 * ResultHelper constructor.
	 * @param DataTypeServiceInterface $dataTypeService
	 * @param TableNameHelper $tableNameHelper
	 */
	public function __construct(DataTypeServiceInterface $dataTypeService, TableNameHelper $tableNameHelper)
	{
		$this->dataTypeService = $dataTypeService;
		$this->tableNameHelper = $tableNameHelper;
	}

	/**
	 * Create a Property from a result row that can be added to a Node
	 * @param string $typeTableKey The base table for the property value (used to form the result alias)
	 * @param NodeTypePropertyDef $def
	 * @param array $data A single result
	 * @return PropertyInterface
	 */
	public function createPropertyFromData(string $typeTableKey, NodeTypePropertyDef $def, array $data): PropertyInterface
	{

		/** @var PropertyInterface $property */
		$property = null;

		if ($def->isMultiValued()) {

			// Setup initial property for node.  Values will be added elsewhere
			$property = new MultiValueProperty();
			$property->setDef($def);

		} else {

			$dataType = $this->dataTypeService->getDataType($def->getDataType());

			$property = new Property();
			$property->setDef($def);
			$columnKey = $def->getKey();

			$value = null;
			$modelFields = $dataType->getModelFields();

			if (null === $dataType || (count($modelFields) == 1 && null == $modelFields[0]->getKey())) {
				$key = $this->tableNameHelper->getColumnNameAlias($typeTableKey, $columnKey);
				$value = isset($data[$key]) ? $data[$key] : null;
			} else {
				$value = [];

				foreach($dataType->getModelFields() as $field) {
					$key = $this->tableNameHelper->getColumnNameAlias($typeTableKey, $columnKey, $field->getKey());
					$keyValue = isset($data[$key]) ? $data[$key] : null;
					$value[$field->getKey()] = $keyValue;
				}
			}

			// Map DB value to
			$value = $this->dataTypeService->valueForProperty($def->getDataType(), $value);

			$property->setValue($value);
		}

		return $property;
	}
}