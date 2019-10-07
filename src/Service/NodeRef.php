<?php

namespace WebImage\Node\Service;

use WebImage\Node\Entities\NodeRefInterface;

class NodeRef implements NodeRefInterface, EntityRefInterface
{
	/**
	 * @var string
	 */
	private $uuid;
	/**
	 * @var string
	 */
	private $version;

	public function __construct(string $uuid, string $version)
	{
		$this->uuid = $uuid;
		$this->version = $version;
	}

	/**
	 * Get the unique identifier
	 * @return string
	 */
	public function getUuid(): string
	{
		return $this->uuid;
	}

	/**
	 * Get the version of the NodeRef
	 *
	 * @return string
	 */
	public function getVersion(): string
	{
		return $this->version;
	}
}