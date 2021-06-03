<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\DataTypes\Type;
use Doctrine\DBAL\Types\Types as DoctrineTypes;

class DoctrineTypeMap {
	protected $typeMap = [
		Type::BOOLEAN => DoctrineTypes::BOOLEAN,
		Type::DATETIME => DoctrineTypes::DATETIME,
		Type::DATE => DoctrineTypes::DATE,
		Type::DECIMAL => DoctrineTypes::DECIMAL,
		Type::INTEGER => DoctrineTypes::INTEGER,
		Type::STRING => DoctrineTypes::STRING,
		Type::TEXT => DoctrineTypes::TEXT,
	];

	public function hasTypeMapping($nodePropertyType) {
		return (in_array($nodePropertyType, array_keys($this->typeMap)));
	}
	public function register(Type $type, DoctrineType $doctrineType) {
		if ($this->hasTypeMapping($type->getTypeName())) {
			throw new \RuntimeException(sprintf('The type %s was already registered', $type->getTypeName()));
		}
		$this->typeMap[$type->getTypeName()] = $doctrineType->getName();
	}

	public function getDoctrineType($nodePropertyType) {
		if (!$this->hasTypeMapping($nodePropertyType)) {
			throw new \RuntimeException(sprintf('The type %s does not exist', $nodePropertyType));
		}

		return $this->typeMap[$nodePropertyType];
	}
}
