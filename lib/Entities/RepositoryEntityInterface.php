<?php

namespace WebImage\Node\Entities;

use WebImage\Node\Service\RepositoryInterface;

interface RepositoryEntityInterface {
    /**
     * @return Repository
     */
	public function getRepository();

    /**
     * @param RepositoryInterface $repository
     *
     * @return void
     */
	public function setRepository(RepositoryInterface $repository);
	/**
	 * Commit the entity to the repository
	 *
	 * @return void
	 */
	public function save();
}