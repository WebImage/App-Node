<?php

namespace WebImage\Node\DataTypes\ValueMapper;

use WebImage\Node\Service\NodeRef;

class NodeRefMapper implements ValueMapperInterface
{
	public function forStorage($value)
	{
		return $this->valueFromNodeRef($value);
	}

	private function valueFromNodeRef(NodeRef $nodeRef=null): ?array
	{
		$uuid = null;
		$version = null;

		if (null !== $nodeRef) {
			$uuid = $nodeRef->getUuid();
			$version = $nodeRef->getVersion();
		}

		return [
			'uuid' => $uuid,
			'version' => $version
		];
	}

	public function forProperty($value)
	{
		if (empty($value['uuid'])) return null;

		return new NodeRef($value['uuid'], $value['version']);
	}
}