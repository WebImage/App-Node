<?php

namespace WebImage\Node\Defs;

use Exception;
use WebImage\Node\PropertyValueMapper\PropertyValueMapperInterface;

/**
 * Represents a data types
 *
 * Class DataType
 * @package WebImage\Node\Defs
 */
class DataType {
	private $type;
	private $name; /* Friendly Name */
	/** @var string $propertyValueMapper A mappers that converts dictionary values to a class */
	private $propertyValueMapper;
	/** @var DataTypeModelField[] */
	private $modelFields = [];
	/** @property string A name resolvable to an input element **/
	private $defaultFormElement;

	function __construct($type, $name, string $propertyValueMapper=null, string $formElement=null)
	{
		$this->setType($type);
		$this->setName($name);
		if (null !== $propertyValueMapper) $this->setPropertyValueMapper($propertyValueMapper);
		if (null !== $formElement) $this->setDefaultFormElement($formElement);
	}

	/**
	 * @return string
	 */
	public function getType(): string
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName(): string
	{
		return $this->name;
	}

	/**
	 * @return string
	 */
	public function getPropertyValueMapper(): string
	{
		return $this->propertyValueMapper;
	}

	/**
	 * @return DataTypeModelField[]
	 */
	public function getModelFields()
	{
		return $this->modelFields;
	}

	/**
	 * @return mixed
	 */
	public function getDefaultFormElement()
	{
		return $this->defaultFormElement;
	}

	/**
	 * @param string $type
	 */
	public function setType(string $type)
	{
		$this->type = $type;
	}

	/**
	 * @param string $name
	 */
	public function setName(string $name)
	{
		$this->name = $name;
	}

	/**
	 * Sets a reference to a resolvable input element
	 * @param string $inputElement
	 * @return string
	 */
	public function setDefaultFormElement(string $inputElement)
	{
		return $this->defaultFormElement;
	}

	/**
	 * A valuable resolvable to a PropertyValueMapper
	 * @param $propertyValueMapper
	 */
	public function setPropertyValueMapper(string $propertyValueMapper)
	{
		$this->propertyValueMapper = $propertyValueMapper;
	}

	/**
	 * @param DataTypeModelField $model_field
	 */
	public function addModelField(DataTypeModelField $model_field)
	{
		$this->modelFields[] = $model_field;
	}

	/**
	 * Whether or not this data type contains a simple single-column storage field (which must not have a name)
	 *
	 * @return bool
	 */
	public function isSimpleStorage()
	{
		return (count($this->modelFields) == 1 && strlen($this->modelFields[0]->getKey()) == 0);
	}
}