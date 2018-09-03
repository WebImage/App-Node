<?php

namespace WebImage\Node\Service;

interface RepositoryAwareInterface {
	/**
	 * Get the repository
	 *
	 * @return mixed
	 */
	public function getRepository();

	/**
	 * Set the repository
	 *
	 * @param RepositoryInterface $repository
	 * @return mixed
	 */
	public function setRepository(RepositoryInterface $repository);
}