<?php

namespace WebImage\Node\Query;

class Query
{
	const SORT_ASC = 'ASC';
	const SORT_DESC = 'DESC';

	/**
	 * @var array
	 */
	private $fields;
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
	 * @var array
	 */
	private $keywords;
	/**
	 * @var int
	 */
	private $currentPage;
	/**
	 * @var int
	 */
	private $resultsPerPage;

	public function __construct()
	{
		$this->fields = array();
		$this->filters = array();
		$this->sorts = array();
		$this->filterTypeQNames = array();
		$this->filterAssociationValues = array();
		$this->keywords = array();
	}

	/**
	 * Get query fields
	 *
	 * @return array
	 */
	public function getFields()
	{
		return $this->fields;
	}

	/**
	 * Get the query filters
	 *
	 * @return array
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
	 * @return int
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
	 * Add a field that needs to be added to the query results.
	 *
	 * There are two ways to construct the addField method...
	 * 1. $query->addField($type_qname, $field)
	 * 2. $query->addField($field_name) <-- If the second value is omitted then we'll assume this is the method we want
	 */
	public function addField($typeQName, $field = null)
	{
		if (null === $field) { // If we are missing $field then assume that addField($field) was called and convert the call accordingly...
			// Shift field variable values
			$field = $typeQName;
			$typeQName = null;
		}
		$this->fields[] = new Field($typeQName, $field);
	}

	/**
	 * Add a filter that will be used to filter the query results
	 *
	 * There are two ways to construct the addFilter method...
	 * 1. $query->addFilter($type_qname, $field, $value, $operator)
	 * 2. $query->addFilter($field_name, $value, $operator) <-- If the second value is omitted then we'll assume this is the method we want
	 */
	public function addFilter($typeQName, $field, $value, $operator = null)
	{
		if (null === $operator) {
			// Shift field variable values
			$operator = $value;
			$value = $field;
			$field = $typeQName;
			$typeQName = null;
		}
		$this->filters[] = new Filter($typeQName, $field, $value, $operator);
	}

	/**
	 * Add a sorting field that will be used to filter the query results
	 *
	 * There are two ways to construct the addSort method...
	 * 1. $query->addSort($type_qname, $field, $sort_direction)
	 * 2. $query->addSort($field, $sort_direction) <!-- If the third value is omitted then we'll assume this is the method we want
	 */
	public function addSort($typeQName, $field = null, $sortDirection = null)
	{
		// If sort_direction is null, but field equals SORT_ASC or SORT_DESC then the called method was $query->addSort($field, $sort_direction)
		if (null === $sortDirection && (null === $field || (null !== $field && $field == Query::SORT_ASC || $field == Query::SORT_DESC))) {
			// Shift field variable values
			$sortDirection = $field;
			$field = $typeQName;
			$typeQName = null;
		}
		// Set default value for sort direction if not defined;
		if (null === $sortDirection) {
			$sortDirection = Query::SORT_ASC;
		}

		$this->sorts[] = new Sort($typeQName, $field, $sortDirection);
	}

	/**
	 * Add a keyword to the query
	 *
	 * @param $keyword
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