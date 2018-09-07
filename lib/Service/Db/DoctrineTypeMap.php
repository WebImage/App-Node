<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\DataTypes\Type;
use Doctrine\DBAL\Types\Type as DoctrineType;

class DoctrineTypeMap {
	protected $typeMap = [
		Type::BOOLEAN => DoctrineType::BOOLEAN,
		Type::DATETIME => DoctrineType::DATETIME,
		Type::DATE => DoctrineType::DATE,
		Type::DECIMAL => DoctrineType::DECIMAL,
		Type::INTEGER => DoctrineType::INTEGER,
		Type::STRING => DoctrineType::STRING,
		Type::TEXT => DoctrineType::TEXT,
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