<?php

namespace WebImage\Node\Service\Db;

trait NodeTypeRefTrait
{
	private $nodeId;
	private $tableKey;

	public function getNodeId(): ?int
	{
		return $this->nodeId;
	}

	public function getTableKey(): ?string
	{
		return $this->tableKey;
	}

	public function setNodeId(int $id)
	{
		$this->nodeId = $id;
	}

	public function setTableKey(string $key)
	{
		$this->tableKey = $key;
	}
}