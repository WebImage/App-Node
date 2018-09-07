<?php

namespace WebImage\Node\Query;

use WebImage\Node\Entities\Node;
use WebImage\Node\Service\NodeServiceInterface;

class QueryBuilder
{
	/**
	 * @var NodeServiceInterface
	 */
	private $nodeService;
	/** @var Query */
	private $query;
	public function __construct(NodeServiceInterface $nodeService)
	{
		$this->nodeService = $nodeService;
		$this->query = new Query();
	}

	public function select($fields) {
		$this->query->addField($fields);

		return $this;
	}

	public function from($typeQNames) {
		$this->query->addTypeQNameFilter($typeQNames);

		return $this;
	}

	public function X() {
		$nodeService = new NodeService();
		$qb = $nodeService->createQueryBuilder();

		$qb->select('contact, address')
			->select('contact', 'address')
			->from('App.Node.Types.Property')
			->where('contact.name', 'Robert Jones')
//			->orWhere([])
			->setFirstResult(10)
			->setMaxResults(20)
			;
	}

	/**
	 * @return Node[]
	 */
	public function execute()
	{
		return $this->nodeService->query($this->query);
	}
}