<?php

namespace WebImage\Node\Service\Db;

/**
 * A properties associated table and column names
 * Class TablePropertyColumn
 * @package WebImage\Node\Service\Db
 */
class PropertyTableColumns
{
	/** @var string */
	private $tableKey;
	/** @var string[] */
	private $columns = [];

	/**
	 * TablePropertyColumn constructor.
	 *
	 * @param string $tableKey
	 */
	public function __construct($tableKey)
	{
		$this->tableKey = $tableKey;
	}

	/**
	 * @return string
	 */
	public function getTableKey(): string
	{
		return $this->tableKey;
	}

	public function addColumn(string $column)
	{
		$this->columns[] = $column;
	}

	/**
	 * @return string[]
	 */
	public function getColumns(): array
	{
		return $this->columns;
	}
}