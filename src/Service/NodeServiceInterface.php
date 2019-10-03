<?php

namespace WebImage\Node\Service;

use WebImage\Core\Dictionary;
use WebImage\Node\Entities\Node;
use WebImage\Node\Entities\NodeAssociation;
use WebImage\Node\Entities\NodeRefInterface;
use WebImage\Node\Query\Query;
use WebImage\Node\Query\QueryBuilder;

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
	 * Delete a node
	 *
	 * @param Node $node
	 * @return
	 */
	public function delete(Node $node);

	/**
	 * Save a node reference
	 *
	 * @param string $typeQName
	 * @param NodeRefInterface $ref
	 *
	 * @return mixed
	 */
//	public function saveNodeRef($typeQName, NodeRefInterface $ref);

	/**
	 * Save a node association
	 *
	 * @param NodeAssociation $def
	 *
	 * @return mixed
	 */
	public function saveAssociation(NodeAssociation $nodeAssociation);

	/**
	 * Query nodes
	 *
	 * @param Query $query
	 *
	 * @return Node[]
	 */
	public function query(Query $query);

	/**
	 * Create query builder
	 *
	 * @return QueryBuilder
	 */
	public function createQueryBuilder();

	/**
	 * Set the repository
	 *
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function setRepository(RepositoryInterface $repository);
	// Associations
//	public function createAssociation($friendlyName, $assocQName, $primaryNodeRef, $associatedNodeRef);

	/**
	 * Create a node association
	 *
	 * @param string $assocQName
	 * @param Node $srcNode
	 * @param Node $dstNode
	 *
	 * @return NodeAssociation
	 */
	public function createAssociation(string $assocQName, Node $srcNode, Node $dstNode);

	/**
	 * Remove a node association
	 *
	 * @param string $assocQName
	 * @param Node $src
	 * @param Node $dst
	 * @param int|null $limit NULL should delete ALL associations of type between the src and tgt
	 *
	 * @return mixed
	 */
	public function removeAssociation(string $assocQName, Node $src, Node $tgt, int $limit=1);
	/**
	 * Get associated node refs
	 *
	 * @param Node $node
	 * @param string|null $assocTypeQName
	 *
	 * @return NodeRef[]
	 */
	public function getAssociatedNodeRefs(Node $node, $assocQName=null);
}