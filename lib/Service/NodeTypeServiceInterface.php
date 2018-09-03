<?php

namespace WebImage\Node\Service;

use WebImage\Config\Config;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\Association;
use WebImage\Node\Entities\NodeType;

/**
 * An interface for a NodeTypeService
 */
interface NodeTypeServiceInterface extends RepositoryAwareInterface {
	public function getNodeTypes();

	/**
	 * @param string $typeQName
	 *
	 * @return NodeType|null
	 */
	public function getNodeTypeByTypeQName($typeQName);
//	public function createNodeType($parent, $friendlyName, $pluralFriendlyName, $qname=null);

	/**
	 * Create a new NodeType
	 *
	 * @param string $parent The QName of the parent - required
	 * @param string $friendlyName The name that the user will see when working with this cnode type
	 * @param string $pluralFriendlyName Pluralized version of friendly name
	 * @param string|null $qname A QName to use for this Node Type.  If left blank then the QName will be created automatically based on the friendly name provided - generally this is probably the best way to go.
	 *
	 * @return NodeType
	 */
	public function create($parent, $friendlyName, $pluralFriendlyName, $qname=null);

	/**
	 * Save a NodeType
	 *
	 * @access public
	 * @param NodeType The NodeType to save
	 *
	 * @throws Exception
	 *
	 * @return NodeType Includes any modifications that were made as a result of the save
	 */
	public function save(NodeType $type);

	public function createAssociation($friendlyName, $assocTypeQName, $primaryType, $associatedType);
	public function saveAssociation(Association $association);

//	public function createNodeTypePropertyDef($qname_str, $key, $name, $type, $required, $default, $is_multi_valued, $sortorder, $config);

	/**
	 * @param string $qnameStr
	 * @param string $key
	 * @param string $name
	 * @param string $type
	 * @param bool $required
	 * @param mixed $default
	 * @param bool $isMultiValued
	 * @param int $sortorder
	 * @param Config $config
	 *
	 * @return NodeTypePropertyDef
	 */
	public function createPropertyDef($qnameStr, $key, $name, $type, $required, $default, $isMultiValued, $sortorder, Config $config=null);
}