<?php

namespace WebImage\Node\Service;

trait RepositoryAwareTrait {
	private $_repository;

	/**
	 * Getter for the current repository object
	 *
	 * @return Repository
	 * @throws \RuntimeException if the repository has not been set
	 */
	public function getRepository()
	{
		if (null === $this->_repository) {
			throw new \RuntimeException('The repository is not available');
		}

		return $this->_repository;
	}

	/**
	 * Set the repository
	 *
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function setRepository(RepositoryInterface $repository)
	{
		$this->_repository = $repository;
	}
}