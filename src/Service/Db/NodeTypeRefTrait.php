<?php

namespace WebImage\Node\Service\Db;

trait NodeTypeRefTrait
{
	private $tableKey;

	public function getTableKey(): ?string
	{
		return $this->tableKey;
	}

	public function setTableKey(string $key)
	{
		$this->tableKey = $key;
	}
}