<?php

namespace WebImage\Node\Service\Db;

trait NodeTypeRefTrait
{
	private $nodeId;
	private $tableKey;

	public function getNodeId() { return $this->nodeId; }
	public function getTableKey() { return $this->tableKey; }

	public function setNodeId($id) { $this->nodeId = $id; }
	public function setTableKey($key) { $this->tableKey = $key; }
}