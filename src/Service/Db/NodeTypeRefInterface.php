<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\NodeTypeDefInterface;

interface NodeTypeRefInterface extends NodeTypeDefInterface
{
	public function getTableKey(): ?string;
	public function setTableKey(string $key);
}