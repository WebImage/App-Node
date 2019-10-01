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

	public function where($property, $value, $operator=Filter::OPERATOR_EQUALS)
	{
		$this->query->addFilter(new Filter($property, $value, $operator));

		return $this;
	}

	public function sort(string $field, string $sortDirection=Query::SORT_ASC)
	{
		if (!in_array($sortDirection, [Query::SORT_ASC, Query::SORT_DESC])) throw new \InvalidArgumentException('Expecting ' . Query::SORT_ASC . ' or ' . Query::SORT_DESC . ' for sort');

		$this->query->addSort(new Sort($field, $sortDirection));

		return $this;
	}

	/**
	 * Set the current page number
	 * @param int $pageNum
	 */
	public function page(int $pageNum)
	{
		$this->query->setCurrentPage($pageNum);

		return $this;
	}

	/**
	 * Set the number of results to return per page
	 * @param int $rpp
	 */
	public function resultsPerPage(int $rpp)
	{
		$this->query->setResultsPerPage($rpp);

		return $this;
	}

	/**
	 * @return Node[]
	 */
	public function execute()
	{
		return $this->nodeService->query($this->query);
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
}