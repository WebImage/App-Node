<?php

namespace WebImage\Node\Defs;

class NodeTypeAssociationDef
{
	// Whether this type of association can have duplicate entries
	/** @var boolean */
	private $allowDuplicates;
	/** @var string */
	private $qName;
	/** @var string */
	private $name;
	/** @var int */
	private $srcMin;
	/** @var int */
	private $srcMax;
	/** @var string */
	private $srcTypeQName;
	/** @var boolean */
	private $isSourceStrict;
	/** @var int */
	private $targetMin;
	/** @var int */
	private $targetMax;
	/** @var boolean */
	private $isTargetStrict;
	/** @var string */
	private $targetTypeQName;
	/** @var bool */
	private $propagateTimestamp;

	/**
	 * NodeAssociationDef constructor.
	 *
	 * @param string $friendlyName
	 * @param string|null $assocTypeQName
	 * @param bool $allowDuplicates
	 * @param int $sourceMin
	 * @param int $sourceMax
	 * @param bool $sourceStrict
	 * @param int $targetMin
	 * @param int $targetMax
	 * @param bool $targetStrict
	 */
	public function __construct(
		$friendlyName,
		$sourceTypeQName,
		$targetTypeQName,
		$assocTypeQName = null,
		$allowDuplicates = true,
		$sourceMin = null,
		$sourceMax = null,
		$sourceStrict = false,
		$targetMin = null,
		$targetMax = null,
		$targetStrict = false,
		$propagateTimestamp = false
	) {
		$this->setAllowDuplicates($allowDuplicates);
		$this->setQName($assocTypeQName);
		$this->setName($friendlyName);
		$this->setSourceMin($sourceMin);
		$this->setSourceMax($sourceMax);
		$this->setSourceStrict($sourceStrict);
		$this->setSourceTypeQName($sourceTypeQName);
		$this->setTargetMin($targetMin);
		$this->setTargetMax($targetMax);
		$this->setTargetStrict($targetStrict);
		$this->setTargetTypeQName($targetTypeQName);
		$this->setPropagateTimestamp($propagateTimestamp);
	}

	/**
	 * @return bool
	 */
	public function doesAllowDuplicates()
	{
		return $this->allowDuplicates;
	}

	/**
	 * @return string
	 */
	public function getQName()
	{
		return $this->qName;
	}

	/**
	 * @return string
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @return int
	 */
	public function getSourceMin()
	{
		return $this->srcMin;
	}

	/**
	 * @return int
	 */
	public function getSourceMax()
	{
		return $this->srcMax;
	}

	/**
	 * @return bool
	 */
	public function isSourceRequired()
	{
		return ($this->getSourceMin() > 0);
	}

	/**
	 * @return bool
	 */
	public function isSourceStrict()
	{
		return $this->isSourceStrict;
	}

	/**
	 * @return string
	 */
	public function getSourceTypeQName()
	{
		return $this->srcTypeQName;
	}

	/**
	 * @return int
	 */
	public function getTargetMin()
	{
		return $this->targetMin;
	}

	/**
	 * @return int
	 */
	public function getTargetMax()
	{
		return $this->targetMax;
	}

	/**
	 * @return bool
	 */
	public function isTargetRequired()
	{
		return ($this->getTargetMin() > 0);
	}

	/**
	 * @return bool
	 */
	public function isTargetStrict()
	{
		return $this->isTargetStrict;
	}

	/**
	 * @return string
	 */
	public function getTargetTypeQName()
	{
		return $this->targetTypeQName;
	}

	/**
	 * @return bool
	 */
	public function shouldPropagateTimestamp()
	{
		return $this->propagateTimestamp;
	}

	/**
	 * @param bool $allowDuplicates
	 */
	public function setAllowDuplicates($allowDuplicates)
	{
		$this->allowDuplicates = $allowDuplicates;
	}

	/**
	 * @param string $assocTypeQName
	 */
	public function setQName($assocTypeQName)
	{
		$this->qName = $assocTypeQName;
	}

	/**
	 * @param int $min
	 */
	public function setTargetMin($min)
	{
		$this->targetMin = $min;
	}

	/**
	 * @param int $max
	 */
	public function setTargetMax($max)
	{
		$this->targetMax = $max;
	}

	/**
	 * @param bool $targetStrict
	 */
	public function setTargetStrict($targetStrict)
	{
		$this->isTargetStrict = $targetStrict;
	}

	/**
	 * @param string $qname
	 */
	public function setTargetTypeQName($qname)
	{
		$this->targetTypeQName = $qname;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param int $min
	 */
	public function setSourceMin($min)
	{
		$this->srcMin = $min;
	}

	/**
	 * @param int $max
	 */
	public function setSourceMax($max)
	{
		$this->srcMax = $max;
	}

	/**
	 * @param bool $srcStrict
	 */
	public function setSourceStrict($srcStrict)
	{
		$this->isSourceStrict = $srcStrict;
	}

	/**
	 * @param string $qname
	 */
	public function setSourceTypeQName($qname)
	{
		$this->srcTypeQName = $qname;
	}

	/**
	 * @param bool $bool
	 */
	public function setPropagateTimestamp($bool)
	{
		$this->propagateTimestamp = $bool;
	}
}