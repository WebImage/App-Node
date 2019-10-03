<?php

namespace WebImage\Node\Entities;

use WebImage\Node\Properties\InvalidPropertyException;
use WebImage\Node\Properties\MultiValueProperty;
use WebImage\Node\Properties\MultiValuePropertyInterface;
use WebImage\Node\Properties\Property;
use WebImage\Node\Properties\PropertyInterface;
use WebImage\Node\Properties\SingleValuePropertyInterface;

class Node extends AbstractRepositoryEntity {
	/**
	 * @var string
	 */
	private $typeQName;
	/**
	 * @var long
	 */
//	private $version;
	/**
	 * @var NodeRefInterface
	 */
	private $parentNodeRef;
	/**
	 * @var NodeRefInterface
	 */
	private $nodeRef;
	/**
	 * @var array
	 */
	private $properties = [];
	/**
	 * @var array
	 */
	private $associations = [];
	/**
	 * @var array
	 */
	private $extensions = [];
	/**
	 * @var bool
	 */
	private $isChanged = false;

	/**
	 * Node constructor.
	 * @param string $typeQName
	 */
	public function __construct($typeQName)
	{
		$this->typeQName = $typeQName;
	}

	/**
	 * Get the reference used by repository
	 *
	 * @return NodeRefInterface
	 */
	public function getNodeRef()
	{
		return $this->nodeRef;
	}

	/**
	 * Get the unique identifier for this node
	 *
	 * @return string
	 */
	public function getUuid()
	{
		$ref = $this->getNodeRef();

		if (null === $ref) return;

		return $ref->getUuid();
	}

	/**
	 * Get the current version node
	 *
	 * @return long
	 */
	public function getVersion()
	{
		$ref = $this->getNodeRef();

		if (null === $ref) return;

		return $ref->getVersion();
	}

	/**
	 * Get the parent node ref
	 *
	 * @return NodeRefInterface
	 */
	public function getParentNodeRef()
	{
		return $this->parentNodeRef;
	}

	/**
	 * Get the properties associated with the node
	 *
	 * @return PropertyInterface[]|SingleValuePropertyInterface[]|MultiValuePropertyInterface[]
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Get a specific property
	 *
	 * @param $name
	 * @return PropertyInterface|SingleValuePropertyInterface|MultiValuePropertyInterface|null
	 */
	public function getProperty($name)
	{
		return isset($this->properties[$name]) ? $this->properties[$name] : null;
	}

	/**
	 * Get the value for a given property
	 *
	 * @param $name
	 * @return mixed
	 */
	public function getRawPropertyValue($name)
	{
		$property = $this->getProperty($name);

		if (null == $property) return;

		if ($property instanceof MultiValuePropertyInterface) {
			throw new InvalidPropertyException('Requesting single value on a multi-valued property');
		}

		return $property->getValue();
	}

	/**
	 * Get the value for a given property
	 *
	 * @param $name
	 * @return mixed
	 */
	public function getPropertyValue($name)
	{
//		$property = $this->getProperty($name);
		$value = $this->getRawPropertyValue($name);

//		$dataType = $this->getRepository()->getDictionaryService()->getDataType($property->getDef()->getQName());
//		return $dataType->translateValue($value);

		return $value;
	}

	/**
	 * @return string
	 */
	public function getTypeQName()
	{
		return $this->typeQName;
	}

	/**
	 * Set the node's repository reference
	 * @param NodeRefInterface
	 */
	public function setNodeRef(NodeRefInterface $ref)
	{
		$this->nodeRef = $ref;
	}

	/**
	 * Add a property to the node. Used to add properties initially without triggering an object changed
	 *
	 * @param string $name
	 * @param PropertyInterface $property
	 */
	public function addProperty($name, PropertyInterface $property)
	{
		$this->properties[$name] = $property;
	}

	/**
	 * Set a node for a property
	 *
	 * @param $name
	 * @param PropertyInterface $property
	 */
	public function setProperty($name, PropertyInterface $property)
	{
		$this->changed(true);
		$this->properties[$name] = $property;
	}

	/**
	 * Set the property value
	 *
	 * @param string $name
	 * @param string $value
	 */
	public function setPropertyValue($name, $value)
	{
		$this->changed(true);
		$this->getProperty($name)->setValue($value);
	}

	/**
	 * Set's the node's type
	 *
	 * @param string $typeQName
	 */
	public function setTypeQName($typeQName)
	{
		$this->typeQName = $typeQName;
	}

	/**
	 * Set multiple properties
	 * @param array $properties
	 */
	private function setProperties(array $properties)
	{
		foreach($properties as $name => $property) {
			$this->setProperty($name, $property);
		}
	}

	/**
	 * Set the version for the node
	 * @param long $version
	 */
	public function setVersion(long $version)
	{
		$this->version = $version;
	}

	/**
	 * Save the node
	 *
	 * @return Node
	 */
	public function save()
	{
		return $this->getRepository()->getNodeService()->save($this);
	}

	/**
	 * Delete the node
	 *
	 * @return Node
	 */
	public function delete()
	{
		return $this->getRepository()->getNodeService()->delete($this);
	}

	/**
	 * Getter / setter indicating whether the node has been changed
	 *
	 * @param bool|null $trueFalse
	 *
	 * @return bool|void
	 */
	public function changed($trueFalse = null)
	{
		if (null === $trueFalse) {
			return $this->isChanged;
		} else if (!is_bool($trueFalse)) {
			throw new \InvalidArgumentException('changed() was expecting a boolean value');
		} else {
			$this->isChanged = $trueFalse;
		}
	}

	/**
	 * Get the node refs for associated nodes
	 *
	 * @param $assocQName
	 *
	 * @return NodeRefInterface[]
	 */
	public function getAssociatedNodeRefs($assocQName=null)
	{
		$associations = $this->getRepository()->getNodeService()->getAssociatedNodeRefs($this, $assocQName);

		return $associations;
	}

	/**
	 * Associate a node with this node
	 *
	 * @param string $assocQName
	 * @param Node $dstNode
	 */
	public function addAssociation($assocQName, Node $dstNode)
	{
		$ref = $this->getNodeRef();


		$association = new NodeAssociation($assocQName, $this, $dstNode);
		$this->associations->add($association);
		$this->changed(true);

	}

	/**
	 * Remove a node association
	 *
	 * @param string $assocQName
	 * @param Node $dstNode
	 */
	public function removeAssociation($assocQName, Node $dstNode)
	{
		$ref = $this->getNodeRef();

		if (null !== $ref) {
			$this->getRepository()->getNodeService()->removeAssociation($assocQName, $this, $dstNode);
		}
	}

	/**
	 * Get all associations for this node
	 *
	 * @return array
	 */
	public function getAssociations()
	{
		return $this->associations;
	}
}