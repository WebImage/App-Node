<?php

namespace WebImage\Node\Entities;

use Exception;
use RuntimeException;
use WebImage\Core\Dictionary;
use WebImage\Node\Defs\NodeTypeDef;
use WebImage\Node\Defs\NodeTypePropertyDef;

class NodeType extends AbstractRepositoryEntity
{
	/** @var NodeTypeDef */
	private $def;
	/**
	 * Get the parents for the node type
	 *
	 * @throws Exception
	 * @throws RuntimeException
	 *
	 * @return NodeType[]
	 */
	public function getParents()
	{
		$parent_stack = array();
		$parent_str = $this->getDef()->getParent();

		$count = 0;

		while (!empty($parent_str)) {

			$parent_type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($parent_str);

			if (null === $parent_type) throw new RuntimeException('Unable to locate parent type ' . $parent_str);

			array_unshift($parent_stack, $parent_type); // Add to the bottom of the stack so that we can process lower levels first

			// Setup next iteration
			$parent_str = $parent_type->getDef()->getParent();

			$count++;
			if ($count > 50) throw new Exception('Max number of parent NodeTypes exceeded');
		}

		return $parent_stack;
	}

	/**
	 * Gets all parents and adds $this type to bottom
	 *
	 * @param bool $includeExtensions Determines whether all extensions should be included in the stack; true by default
	 * @param array of NodeType
	 *
	 * @return NodeType[]
	 */
	public function getTypeStack($includeExtensions = true)
	{
		// Setup return array
		$types = array();

		// Get parent types
		$parents = $this->getParents();

		/**
		 * Iterate through each parent and add to stack
		 * @var NodeType $parent
		 */
		foreach ($parents as $parent) {
			// Add parent to type stack
			$types[] = $parent;

			// Include extensions, if requested
			if ($includeExtensions) {
				// Get type parent extensions
				$parent_extensions = $parent->getDef()->getExtensions();
				// Iterate through available extensions for this type
				foreach($parent_extensions as $extension) {
					// Get extension type
					$extension_type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($extension);
					// Make sure the extension type is actually defined
					if (null !== $extension_type) {
						// Get the type stack for the extension type...
						$extension_type_type_stack = $extension_type->getTypeStack();
						// ... and merge with our current type stack
						$types = array_merge($types, $extension_type_type_stack);
					}
				}
			}
		}

		// Add this object to the type stack
		$types[] = $this;
		// Include extension for this type, if requested
		if ($includeExtensions) {
			// Get type extensions
			$this_extensions = $this->getDef()->getExtensions();
			// Iterate through available extensions for this type

			foreach($this_extensions as $extension) {
				// Get extension type
				$extension_type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($extension);
				// Make sure the extension type is actually defined
				if (null !== $extension_type) {
					// Get the type stack for the extension type...
					$extension_type_type_stack = $extension_type->getTypeStack();
					// ... and merge with our current type stack
					$types = array_merge($types, $extension_type_type_stack);
				}
			}
		}

		return $types;
	}

	/**
	 * Save the node type to the service
	 */
	public function save()
	{
		$this->getRepository()->getNodeTypeService()->save($this);
	}

	/**
	 * Delete the node type from the service
	 */
	public function delete()
	{
		$this->getRepository()->getNodeTypeService()->delete($this);
	}

	/**
	 * Creates a property definition and adds it to the list of properties
	 *
	 * @throws Exception
	 *
	 * @return NodeTypePropertyDef of the created object
	 */
	public function createProperty($key, $name, $type, $required=false, $default=null, $isMultiValued=false, $sortorder=null, $config=null)
	{
		if (empty($key)) $key = $this->getRepository()->getDictionaryService()->createKeyFromFriendlyName($name);

		// Check if property already exists with this key
		if ($property = $this->getDef()->getProperty($key)) {
			throw new Exception('Property already exists');
		}

		$qname = $this->getDef()->getQName();

		$def = $this->getRepository()->getNodeTypeService()->createPropertyDef($qname, $key, $name, $type, $required, $default, $isMultiValued, $sortorder, $config);

		$this->getDef()->setProperty($key, $def);

		return $def;
	}

	/**
	 * Retrieves a fresh copy of the underlying Node.  Should not cache, as the underlying Node may have changed and needs to be refreshed.
	 * @return null|Node
	 */
	public function getNode()
	{
		return $this->getRepository()->getNodeService()->getNodeByUuid($this->getDef()->getUuid());
	}

	public function createAssociation($friendlyName, $associatedTypeQName, $assocTypeQName)
	{
		$associatedType = $associated_type = $this->getRepository()->getNodeTypeService()->getNodeTypeByTypeQName($associatedTypeQName);

		return $this->getRepository()->getNodeTypeService()->createAssociation($friendlyName, $this, $associatedType, $assocTypeQName);
	}

	/**
	 * Retrieves all properties from this type's definition, all parent type definitions, and all extension definitions (as opposed to $this->getDef()->getProperties() which only returns properties for this properties definition
	 *
	 * @return NodeTypePropertyDef[]|Dictionary of property definitions
	 */
	public function getProperties()
	{
		// Get all associated types for this type
		$typeStack = $this->getTypeStack();
		// Instantiate return object
		$properties = new Dictionary();

		// Iterate through types and add properties to Dictionary
		foreach ($typeStack as $type) {

			foreach($type->getDef()->getProperties() as $key => $property) {
				$properties->set($key, $property);
			}
		}

		return $properties;
	}

	/**
	 * @return NodeTypeDef
	 */
	public function getDef()
	{
		return $this->def;
	}

	/**
	 * @param NodeTypeDef $def
	 */
	public function setDef(NodeTypeDef $def)
	{
		$this->def = $def;
	}
}