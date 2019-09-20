<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Service\NodeRef as NodeRefBase;

class NodeRef extends NodeRefBase {
	/**
	 * @var int
	 */
	private $nodeId;

	/**
	 * NodeRef constructor.
	 *
	 * @param string $uuid
	 * @param string $version
	 * @param int|null $nodeId
	 */
	public function __construct($uuid, $version, $nodeId = null)
	{
		parent::__construct($uuid, $version);
		$this->setNodeId($nodeId);
	}

	/**
	 * @return int
	 */
	public function getNodeId()
	{
		return $this->nodeId;
	}
	/**
	 * Setter for NodeId
	 * Note: changed access to public because the importation process needs a way of starting with a uuid and then generating a new NodeId
	 * @return null
	 */
	public function setNodeId($nodeId)
	{
		$this->nodeId = $nodeId;
	}
}