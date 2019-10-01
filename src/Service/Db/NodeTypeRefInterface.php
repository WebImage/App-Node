<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\NodeTypeDefInterface;

interface NodeTypeRefInterface extends NodeTypeDefInterface
{
	public function getNodeId(): ?int;
	public function getTableKey(): ?string;

	public function setNodeId(int $id);
	public function setTableKey(string $key);
}