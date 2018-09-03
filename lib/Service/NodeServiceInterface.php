<?php

namespace WebImage\Node\Service;

use WebImage\Core\Dictionary;
use WebImage\Node\Entities\Node;
use WebImage\Node\Entities\NodeAssociation;
use WebImage\Node\Entities\NodeRefInterface;
use WebImage\Node\Query\Query;

interface NodeServiceInterface extends RepositoryAwareInterface
{
	/**
	 * Get a node by its unique id
	 *
	 * @param $uuid
	 * @return Node|null
	 */
	public function getNodeByUuid($uuid);

	/**
	 * Get multiple nodes by their unique ids
	 *
	 * @param array $uuids
	 * @return Dictionary
	 */
	public function getNodesByUuids(array $uuids);

	/**
	 * Create a node of a specific type
	 *
	 * @param string $qnameStr
	 *
	 * @return Node
	 */
	public function create($qnameStr);

	/**
	 * Save a node
	 *
	 * @param Node $node
	 * @return Node
	 */
	public function save(Node $node);

	/**
	 * Save a node reference
	 *
	 * @param string $typeQName
	 * @param NodeRefInterface $ref
	 *
	 * @return mixed
	 */
	public function saveNodeRef($typeQName, NodeRefInterface $ref);

	/**
	 * Save a node association
	 *
	 * @param NodeAssociation $def
	 *
	 * @return mixed
	 */
	public function saveNodeAssociation(NodeAssociation $nodeAssociation);

	/**
	 * Query nodes
	 *
	 * @param Query $query
	 * @return mixed
	 */
	public function query(Query $query);

	/**
	 * Set the repository
	 *
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function setRepository(RepositoryInterface $repository);
	// Associations
//	public function createAssociation($friendlyName, $assocTypeQName, $primaryNodeRef, $associatedNodeRef);

	/**
	 * Create a node association
	 *
	 * @param $assocTypeQName
	 * @param Node $srcNode
	 * @param Node $dstNode
	 *
	 * @return mixed
	 */
	public function createNodeAssociation($assocTypeQName, Node $srcNode, Node $dstNode);

	/**
	 * Get associated node refs
	 *
	 * @param Node $node
	 * @param string|null $assocTypeQName
	 *
	 * @return NodeRef[]
	 */
	public function getAssociatedNodeRefs(Node $node, $assocTypeQName=null);
}