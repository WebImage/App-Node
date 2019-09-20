<?php

namespace WebImage\Node\Defs;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;

class DataTypeModelField {
	/** @var string One of WebImage\Node\Types\Type::CONSTANTS */
	private $type;
	/** @var string */
	private $name;
	/** @var Dictionary */
	private $options;

	/**
	 * DataTypeModelField constructor.
	 *
	 * @param string $type
	 * @param string|null $name
	 * @param Dictionary|null $options
	 */
	public function __construct($type, $name=null, Dictionary $options=null)
	{
		$this->type = $type;
		$this->name = $name;
		$this->options = null === $options ? new Dictionary() : $options;
	}

	/**
	 * @return string
	 */
	public function getType()/*: string */
	{
		return $this->type;
	}

	/**
	 * @return string
	 */
	public function getName()/*: string */
	{
		return $this->name;
	}

	/**
	 * @return Dictionary|null
	 */
	public function getOptions()
	{
		return $this->options;
	}

	public static function createFromConfig(Config $config)
	{
		return new static($config->get('type'), $config->get('name'), $config->get('options'));
	}
}