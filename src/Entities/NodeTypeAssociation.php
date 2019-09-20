<?php

namespace WebImage\Node\Entities;

use WebImage\Node\Defs\NodeTypeAssociationDef;

class NodeTypeAssociation extends AbstractRepositoryEntity
{
	/** @var NodeType */
	private $sourceType;
	/** @var NodeType */
	private $targetType;
	/** @var NodeTypeAssociationDef */
	private $def;
	/** @var bool Whether this association is new, or retrieved from a source (existing) */
	private $isNew = true;

	public function __construct(NodeType $sourceType, NodeType $targetType, NodeTypeAssociationDef $def)
	{
		$this->sourceType = $sourceType;
		$this->targetType = $targetType;
		$this->setDef($def);
	}

	/**
	 * @return NodeTypeAssociationDef
	 */
	public function getDef()
	{
		return $this->def;
	}

	/**
	 * @return NodeType
	 */
	public function getSourceType()
	{
		return $this->sourceType;
	}

	/**
	 * @return NodeType
	 */
	public function getTargetType()
	{
		return $this->sourceType;
	}

	/**
	 * @param NodeTypeAssociationDef $def
	 */
	public function setDef(NodeTypeAssociationDef $def)
	{
		$this->def = $def;
	}

	/**
	 * Getter/setting for isNew
	 * @param bool|null $isNew
	 *
	 * @return bool
	 */
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

	/**
	 * Save the association definition
	 */
	public function save()
	{
		$this->getRepository()->getNodeTypeService()->saveAssociation($this);
	}
	/**
	 * Delete the association definition
	 */
	public function delete()
	{
		$this->getRepository()->getNodeTypeService()->deleteAssociation($this);
	}
}