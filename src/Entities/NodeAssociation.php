<?php

namespace WebImage\Node\Entities;
/**
 * 1. Capture the physical association between 2 nodes
 * 2. Capture the definition of an available association
 * 3. Capture commitable definition of an available association
 */
class NodeAssociation extends AbstractRepositoryEntity
{
	/**
	 * @var string
	 */
	private $qName;
	/** @var Node */
	private $srcNode;
	/** @var Node */
	private $tgtNode;
	/** @var int */
	private $sortorder;
	/** @var bool */
	private $isNew = true;

	/**
	 * NodeAssociation constructor.
	 *
	 * @param string $qName
	 * @param Node $srcNode
	 * @param Node $tgtNode
	 * @param int $sortorder
	 */
	function __construct($qName, Node $srcNode, Node $tgtNode, $sortorder=null)
	{
		$this->qName = $qName;
		$this->srcNode = $srcNode;
		$this->tgtNode = $tgtNode;
		$this->setSortOrder($sortorder);
	}

	public function getQName()
	{
		return $this->qName;
	}

	public function getSourceNode()
	{
		return $this->srcNode;
	}

	public function getTargetNode()
	{
		return $this->tgtNode;
	}

	public function getSortOrder()
	{
		return $this->sortorder;
	}

	public function isNew($isNew = null)
	{
		return $this->isNew = $this->getBoolDefault($isNew, $this->isNew);
	}

	private function getBoolDefault($val, $default=false)
	{
		if (null !== $val && !(is_bool($val))) {
			throw new \InvalidArgumentException('Expecting a boolean');
		}

		return null === $val ? $default : $val;
	}

	public function setSortOrder($sortorder)
	{
		$this->sortorder = $sortorder;
	}

	public function save()
	{
		return $this->getRepository()->getNodeService()->saveAssociation($this);
	}
}