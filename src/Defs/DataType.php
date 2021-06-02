<?php

namespace WebImage\Node\Defs;

use Exception;
use WebImage\Node\DataTypes\ValueMapper;

/**
 * Represents a data types
 *
 * Class DataType
 * @package WebImage\Node\Defs
 */
class DataType {
	private $type;
	private $name; /* Friendly Name */
	/** @var string $valueMapper A mappers that converts dictionary values to a class */
	private $valueMapper;
	/** @var DataTypeModelField[] */
	private $modelFields = [];
	/** @var string A name resolvable to an input element **/
	private $defaultFormElement;
	/** @var string $view */
	private $view;

	function __construct($type, $name, string $valueMapper=null, string $view=null)
	{
		$this->setType($type);
		$this->setName($name);
		if (null !== $valueMapper) $this->setValueMapper($valueMapper);
		if (null !== $view) $this->setView($view);
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
	public function getValueMapper(): ?string
	{
		return $this->valueMapper;
	}

	/**
	 * @return string
	 */
	public function getView(): string
	{
		return $this->view;
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
	 * A valuable resolvable to a ValueMapper
	 * @param $valueMapper
	 */
	public function setValueMapper(string $valueMapper)
	{
		$this->valueMapper = $valueMapper;
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

	/**
	 * @param string $view
	 */
	public function setView(string $view)
	{
		$this->view = $view;
	}
}