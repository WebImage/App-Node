<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\DataTypes\Type;
use Doctrine\DBAL\Types\Types as DoctrineType;

class DoctrineTypeMap {
	protected $typeMap = [
		Type::BOOLEAN => DoctrineTypesBOOLEAN,
		Type::DATETIME => DoctrineTypesDATETIME,
		Type::DATE => DoctrineTypesDATE,
		Type::DECIMAL => DoctrineTypesDECIMAL,
		Type::INTEGER => DoctrineTypesINTEGER,
		Type::STRING => DoctrineTypesSTRING,
		Type::TEXT => DoctrineTypesTEXT,
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
