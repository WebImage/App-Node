<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\NodeTypePropertyDef;

class NodeTypePropertyRef extends NodeTypePropertyDef {
	private $nodeTypeId;
	// Getters
	public function getNodeTypeId() { return $this->nodeTypeId; }
	// Setters
	public function setNodeTypeId($nodeTypeId) { $this->nodeTypeId = $nodeTypeId; }
}