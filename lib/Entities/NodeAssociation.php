<?php

namespace WebImage\Node\Entities;
/**
 * 1. Capture the physical association between 2 nodes
 * 2. Capture the definition of an available association
 * 3. Capture commitable definition of an available association
 */
class NodeAssociation extends AbstractRepositoryEntity
{
	private $assocTypeQName;
	private $srcNode, $dstNode;
	private $isNew = false;

	function __construct($assocTypeQName, $srcNode, $dstNode, $isNew = false)
	{
		$this->assocTypeQName = $assocTypeQName;
		$this->srcNode = $srcNode;
		$this->dstNode = $dstNode;
		$this->isNew = $isNew;
	}

	public function getAssociationTypeQName()
	{
		return $this->assocTypeQName;
	}

	public function getSourceNode()
	{
		return $this->srcNode;
	}

	public function getDestinationNode()
	{
		return $this->dstNode;
	}

	public function isNew()
	{
		return $this->isNew;
	}

	public function save()
	{
		return $this->getRepository()->getNodeService()->saveNodeAssociation($this);
	}
}