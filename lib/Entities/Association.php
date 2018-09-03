<?php

namespace WebImage\Node\Entities;

use WebImage\Node\Service\RepositoryAwareInterface;
use WebImage\Node\Service\RepositoryAwareTrait;

class Association extends AbstractRepositoryEntity implements RepositoryAwareInterface, RepositoryEntityInterface
{
	use RepositoryAwareTrait;

	/**
	 * Save the association definition
	 * @return mixed
	 */
	public function save()
	{
		return $this->getRepository()->getNodeTypeService()->saveAssociation();
	}
}