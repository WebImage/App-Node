<?php

namespace WebImage\Node\Defs;

class NodeAssociationDef
{
	// Whether this type of association can have duplicate entries
	private $allowDuplicates;
	private $associationTypeQName;
	private $dstHasMany;
	private $dstRequired;
	private $dstStrict;
	private $name;
	private $srcHasMany;
	private $srcRequired;
	private $srcStrict;

	public function __construct(
		$friendlyName,
		$assocTypeQName,
		$allowDuplicates = true,
		$dstHasMany = true,
		$dstRequired = false,
		$dstStrict = false,
		$srcHasMany = true,
		$srcRequired = false,
		$srcStrict = false)
	{
		$this->setAllowDuplicates($allowDuplicates);
		$this->setAssociationTypeQName($assocTypeQName);
		$this->setDstHasMany($dstHasMany);
		$this->setDstRequired($dstRequired);
		$this->setDstStrict($dstStrict);
		$this->setName($friendlyName);
		$this->setSrcHasMany($srcHasMany);
		$this->setSrcRequired($srcRequired);
		$this->setSrcStrict($srcStrict);
	}

	public function getAllowDuplicates()
	{
		return $this->allowDuplicates;
	}

	public function getAssociationTypeQName()
	{
		return $this->associationTypeQName;
	}

	public function getDstHasMany()
	{
		return $this->dstHasMany;
	}

	public function getDstRequired()
	{
		return $this->dstRequired;
	}

	public function getDstStrict()
	{
		return $this->dstStrict;
	}

	public function getName()
	{
		return $this->name;
	}

	public function getSrcHasMany()
	{
		return $this->srcHasMany;
	}

	public function getSrcRequired()
	{
		return $this->srcRequired;
	}

	public function getSrcStrict()
	{
		return $this->srcStrict;
	}

	public function setAllowDuplicates($allowDuplicates)
	{
		$this->allowDuplicates = $allowDuplicates;
	}

	public function setAssociationTypeQName($assocTypeQName)
	{
		$this->associationTypeQName = $assocTypeQName;
	}

	public function setDstHasMany($dstHasMany)
	{
		$this->dstHasMany = $dstHasMany;
	}

	public function setDstRequired($dstRequired)
	{
		$this->dstRequired = $dstRequired;
	}

	public function setDstStrict($dstStrict)
	{
		$this->dstStrict = $dstStrict;
	}

	public function setName($name)
	{
		$this->name = $name;
	}

	public function setSrcHasMany($srcHasMany)
	{
		$this->srcHasMany = $srcHasMany;
	}

	public function setSrcRequired($srcRequired)
	{
		$this->srcRequired = $srcRequired;
	}

	public function setSrcStrict($srcStrict)
	{
		$this->srcStrict = $srcStrict;
	}
}