<?php

use \WebImage\Node\DataTypes\ValueMapper\NodeRefMapper;

class NodeRefMapperTest extends \PHPUnit\Framework\TestCase
{

	public function testForStorage()
	{
		$uuid = 'abc-123';
		$version = 1;

		$nodeRef = new \WebImage\Node\Service\NodeRef($uuid, $version);
		$mapper = new NodeRefMapper();
		$result = $mapper->forStorage($nodeRef);

		$expected = [
			'uuid' => $uuid,
			'version' => $version
		];

		$this->assertEquals($expected, $result);
	}

	public function testForStorageWithEmptyNodeRef()
	{
		$expected = [
			'uuid' => null,
			'version' => null
		];

		$nodeRef = null;
		$mapper = new NodeRefMapper();
		$result = $mapper->forStorage($nodeRef);

		$this->assertEquals($expected, $result, 'Empty NodeRef should result in array with empty values for uuid and version');
	}

	public function testForRender()
	{
		$uuid = 'abc-123';
		$version = 1;

		$data = [
			'uuid' => $uuid,
			'version' => $version
		];

		$mapper = new NodeRefMapper();
		$nodeRef = $mapper->forProperty($data);

		$this->assertInstanceOf(\WebImage\Node\Service\NodeRef::class, $nodeRef);
		$this->assertEquals($uuid, $nodeRef->getUuid());
		$this->assertEquals($version, $nodeRef->getVersion());
	}

	public function testForRenderWithEmptyValues()
	{
		$data = [
			'uuid' => null,
			'version' => null
		];

		$mapper = new NodeRefMapper();
		$nodeRef = $mapper->forProperty($data);

		$this->assertNull($nodeRef, 'Mapper should return NULL when values are not specified');
	}
}