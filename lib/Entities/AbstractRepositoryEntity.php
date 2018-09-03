<?php

namespace WebImage\Node\Entities;

use WebImage\Node\Service\RepositoryAwareInterface;
use WebImage\Node\Service\RepositoryAwareTrait;

abstract class AbstractRepositoryEntity implements RepositoryAwareInterface, RepositoryEntityInterface
{
	use RepositoryAwareTrait;

	/**
	 * @inheritdoc
	 */
	abstract public function save();
}