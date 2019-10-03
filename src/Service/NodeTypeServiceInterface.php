<?php

namespace WebImage\Node\Service;

use WebImage\Config\Config;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Entities\NodeTypeAssociation;

/**
 * An interface for a NodeTypeService
 */
interface NodeTypeServiceInterface extends RepositoryAwareInterface {
	/** @return NodeType[] */
	public function getTypes();

	/**
	 * @param string $typeQName
	 *
	 * @return NodeType|null
	 */
	public function getNodeTypeByTypeQName(string $typeQName);
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
	public function create($parent, $friendlyName, $pluralFriendlyName, $qname=null, $isExtension=false);

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

	/**
	 * Delete NodeType
	 *
	 * @param NodeType $type
	 */
	public function delete(NodeType $type);

	/**
	 * @return NodeTypeAssociation[]
	 */
	public function getAssociations();

	/**
	 * @param string $assocQName
	 *
	 * @return NodeTypeAssociation
	 */
	public function getAssociationByQName($assocQName);

	/**
	 * @param $friendlyName
	 * @param NodeType $sourceType
	 * @param NodeType $targetType
	 * @param string|null $assocTypeQName
	 * @param bool $allowDuplicates
	 * @param int|null $sourceMin
	 * @param int|null $sourceMax
	 * @param bool $sourceStrict
	 * @param int|null $targetMin
	 * @param int|null $targetMax
	 * @param bool $targetStrict
	 *
	 * @return NodeTypeAssociation
	 */
	public function createAssociation(
		$friendlyName,
		NodeType $sourceType,
		NodeType $targetType,
		$assocTypeQName = null,
		$allowDuplicates = true,
		$sourceMin = null,
		$sourceMax = null,
		$sourceStrict = false,
		$targetMin = null,
		$targetMax = null,
		$targetStrict = false
	);

	/**
	 * Save an association definition
	 */
	public function saveAssociation(NodeTypeAssociation $assoc);

	/**
	 * @param NodeTypeAssociation $association
	 */
	public function deleteAssociation(NodeTypeAssociation $association);


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
	public function createPropertyDef(string $nodeTypeQName, string $key, string $name, string $dataType, bool $required = false, $default = null, bool $isMultiValued = false, int $sortorder = null, Config $config = null);
}