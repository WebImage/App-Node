<?php

namespace WebImage\Node\Query;

use WebImage\Core\Dictionary;
use WebImage\Node\Entities\Node;
use WebImage\Node\Service\NodeServiceInterface;

class QueryBuilder
{
	/**
	 * @var Dictionary
	 */
	private $aliases;
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
		$this->aliases = new Dictionary();
	}

	/**
	 * @param mixed|array|string $properties
	 *
	 * @return $this
	 */
	public function select($properties)
	{
		$properties = $this->normalizeProperties($properties);

		foreach($properties as $property) {
			list($typeQName, $property) = $property;

			if (null === $typeQName) {
				$this->query->addProperty($property);
			} else {
				$this->query->addProperty($typeQName, $property);
			}
		}

		return $this;
	}

	/**
	 * Takes a comma-delimited list of properties, or an array of properties
	 * @param mixed|array|string $properties
	 *
	 * @return Property[]
	 */
	private function normalizeProperties($properties)
	{
		$properties = is_array($properties) ? $properties : [$properties];
		$return = [];

		foreach ($properties as $property) {
			$tProperties = preg_split('/, */', $property);
			foreach($tProperties as $sProperty) {
				$return[] = new Property($sProperty);
			}
		}

		return $return;
	}

	/**
	 * Sets the node types to SELECT
	 *
	 * @example from('App.Types.Person', 'p')
	 * @example from(['App.Types.Person' => 'p', 'App.Type.Contact' => 'c'])
	 *
	 * @param string|array $typeQNames
	 * @param string|null $alias
	 *
	 * @return $this
	 */
	public function from($typeQNames, $alias = null)
	{
		$typeQNames = $this->normalizeFrom($typeQNames, $alias);

		foreach ($typeQNames as $typeQName => $alias) {
			$this->query->addTypeQNameFilter($typeQName);
			if (null !== $alias) $this->aliases->set($alias, $typeQName);
		}

		return $this;
	}

	private function normalizeFrom($typeQNames, $alias=null)
	{
		if (is_array($typeQNames) && null !== $alias) {
			throw new \InvalidArgumentException('$alias should be null when $typeQNames is specified as an array');
		}
		if (!is_array($typeQNames)) return [$typeQNames => $alias];

		$return = [];

		foreach($typeQNames as $ix => $typeQName) {
			$qName = is_numeric($ix) ? $typeQName : $ix;
			$alias = is_numeric($ix) ? null : $ix;
			$return[$qName] = $alias;
		}

		return $return;
	}

	public function where($property, $value, $operator=Filter::OPERATOR_EQUALS)
	{
		$this->query->addFilter(new Filter($property, $value, $operator));

		return $this;
	}

	public function X() {
		$nodeService = new NodeService();
		$qb = $nodeService->createQueryBuilder();

		$qb->select('contact, address')
			->select('contact', 'address')
			->from('App.Types.Property')
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