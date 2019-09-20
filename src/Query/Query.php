<?php

namespace WebImage\Node\Query;

class Query
{
	const SORT_ASC = 'ASC';
	const SORT_DESC = 'DESC';

	/**
	 * @var array
	 */
	private $properties;
	/**
	 * @var array
	 */
	private $filters;
	/**
	 * @var array
	 */
	private $sorts;
	/**
	 * @var array
	 */
	private $filterTypeQNames;
	/**
	 * @var array
	 */
	private $filterAssociationValues;
	/**
	 * @var string[]
	 */
	private $keywords;
	/**
	 * @var int|null
	 */
	private $currentPage;
	/**
	 * @var int
	 */
	private $resultsPerPage;

	public function __construct()
	{
		$this->properties = array();
		$this->filters = array();
		$this->sorts = array();
		$this->filterTypeQNames = array();
		$this->filterAssociationValues = array();
		$this->keywords = array();
	}

	/**
	 * Get query properties
	 *
	 * @return array
	 */
	public function getProperties()
	{
		return $this->properties;
	}

	/**
	 * Get the query filters
	 *
	 * @return Filter[]
	 */
	public function getFilters()
	{
		return $this->filters;
	}

	/**
	 * Get the query sorts
	 *
	 * @return array
	 */
	public function getSorts()
	{
		return $this->sorts;
	}

	/**
	 * Get the query filter type qnames
	 * @return array
	 */
	public function getFilterTypeQNames()
	{
		return $this->filterTypeQNames;
	}

	/**
	 * Get the query filter association values
	 *
	 * @return array
	 */
	public function getFilterAssociationValues()
	{
		return $this->filterAssociationValues;
	}

	/**
	 * Get the query keywords
	 *
	 * @return array
	 */
	public function getKeywords()
	{
		return $this->keywords;
	}

	/**
	 * Get the current page
	 *
	 * @return int|null
	 */
	public function getCurrentPage()
	{
		return $this->currentPage;
	}

	/**
	 * Get results per page
	 *
	 * @return int
	 */
	public function getResultsPerPage()
	{
		return $this->resultsPerPage;
	}

	/**
	 * Set the current page
	 *
	 * @param int $currentPage
	 */
	public function setCurrentPage($currentPage)
	{
		$this->currentPage = $currentPage;
	}

	/**
	 * Set the results per page
	 *
	 * @param int $resultsPerPage
	 */
	public function setResultsPerPage($resultsPerPage)
	{
		$this->resultsPerPage = $resultsPerPage;
	}

	/**
	 * Add a property that needs to be added to the query results.
	 *
	 * There are two ways to construct the addField method...
	 * 1. $query->addField($type_qname, $field)
	 * 2. $query->addField($field_name) <-- If the second value is omitted then we'll assume this is the method we want
	 */
	public function addProperty(Property $property)
	{
		$this->properties[] = $property;
	}

	/**
	 * Add a filter that will be used to filter the query results
	 *
	 * @param Filter $filter
	 */
	public function addFilter(Filter $filter)
	{
		$this->filters[] = $filter;
	}

	/**
	 * Add a sorting field that will be used to filter the query results
	 *
	 * @param Sort $sort
	 */
	public function addSort(Sort $sort)
	{
		$this->sorts[] = $sort;
	}

	/**
	 * Add a keyword to the query
	 *
	 * @param string $keyword
	 */
	public function addKeyword($keyword)
	{
		$this->keywords[] = $keyword;
	}

	/**
	 * Add a type qname filter
	 *
	 * @param $typeQName
	 */
	public function addTypeQNameFilter($typeQName)
	{
		$this->filterTypeQNames[] = $typeQName;
	}

	/**
	 * Add a filter to check if that a Node has an association with another Node
	 *
	 * There are two ways to construct the addAssociationValueFilter method...
	 * 1. $query->addAssociationValueFilter($association_type_qname, $value)
	 * 2. $query->addAssociationValueFilter($value) <!-- assumed if the second parameter is left blank
	 */
	public function addAssociationValueFilter($associationTypeQName, $value = null)
	{
		// If $value is null, then shift values over and assume second version of method
		if (null === $value) {
			$value = $associationTypeQName;
			$associationTypeQName = null;
		}
		$this->filterAssociationValues[] = new AssociationValue($associationTypeQName, $value);
	}

	/**
	 * Setup the current page and results per page
	 *
	 * @param int $currentPage
	 * @param int $resultsPerPage
	 */
	public function paginate($currentPage, $resultsPerPage)
	{
		$this->setCurrentPage($currentPage);
		$this->setResultsPerPage($resultsPerPage);
	}
}