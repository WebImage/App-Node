<?php

namespace WebImage\Node\Service;

use WebImage\Config\Config;
use WebImage\Core\Dictionary;
use WebImage\Node\Defs\NodeTypeDef;
use WebImage\Node\Defs\NodeTypeExtensionDef;
use WebImage\Node\Defs\NodeTypePropertyDef;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Service\RepositoryInterface;

class JsonNodeTypeConverter
{
	private $repository;

	/**
	 * JsonTypeImporter constructor.
	 * @param $repository
	 */
	public function __construct(RepositoryInterface $repository)
	{
		$this->repository = $repository;
	}

	public function fromFile($file): NodeType
	{
		return $this->fromJson(file_get_contents($file));
	}
	/**
	 * Creates a NodeType from a JSON string
	 * @param string $json
	 * @return NodeType
	 */
	public function fromJson(string $json): NodeType {
		$decodedJson = json_decode($json, true);
		if (!$decodedJson) {
			throw new \RuntimeException('Malformed JSON body');
		}

		return $this->fromArray($decodedJson);
	}

	/**
	 * Creates a NodeType from its array representation
	 * @param array $json
	 *
	 * @return NodeType
	 */
	public function fromArray(array $json): NodeType
	{
		$data = new Config($json);
		$parent = $data->get('parent');
		$name = $data->get('name');
		$pluralName = $data->get('pluralName', $data->get('name'));

		$this->assertHasRequiredTypeVars($data);

		$repository = $this->getRepository();
		$typeService = $repository->getNodeTypeService();

		$type = $typeService->create($parent, $name, $pluralName);
		$type->setDef($this->typeDefFromArray($json));

		return $type;
	}

	/**
	 * Create a NodeTypeDef from array values
	 * @param array $json
	 * @return NodeTypeDef
	 */
	public function typeDefFromArray(array $json): NodeTypeDef
	{
		$data = new Config($json);

		$this->assertHasRequiredTypeVars($data);

		$parent = $data->get('parent');
		$name = $data->get('name');
		$pluralName = $data->get('pluralName');
		$qname = $data->get('qname');
		$isReadOnly = $data->get('isReadOnly');

		$config = $data->get('config');

		$isExtension = $data->get('isExtension');
		if (null !== $isExtension && !is_bool($isExtension)) throw new \RuntimeException('Invalid isExtension value.  Expecting boolean.');
//		$version = $data->get('version');
		$properties = $data->get('properties', []);
		$associations = $data->get('associations', []);
//		$extensions = $data->get('extensions');
		$isExtension = $data->get('isExtension', false) !== false;
		$isFinal = $data->get('isFinal', null);
		$isAbstract = $data->get('isAbstract');

		if ($isExtension === true) {
			$def = new NodeTypeExtensionDef($parent, $name, $pluralName, $qname);
		} else {
			$def = new NodeTypeDef($parent, $name, $pluralName, $qname);
		}
		if (null !== $config) $def->setConfig($config);
		if (null !== $isFinal) $def->setFinal($isFinal);
		if (null !== $isReadOnly) $def->isReadOnly($isReadOnly);
		if (null !== $isAbstract) $def->isAbstract($isAbstract);


		$this->attachJsonPropertiesToTypeDef($def, $properties);

		return $def;
	}

	/**
	 * Converts a NodeType to its JSON representation
	 * @param NodeType $type
	 * @return string
	 */
	public function toJson(NodeType $type): string
	{
		return json_encode($this->toArray($type));
	}

	/**
	 * Converts a NodeType to its array representation
	 * @param NodeType $type
	 * @return array
	 */
	public function toArray(NodeType $type): array
	{
		$def = $type->getDef();

		$properties = [];
		foreach($type->getDef()->getProperties() as $propDef) {
			$properties[] = [
				'qname' => $propDef->getQName(),
				'name' => $propDef->getName(),
				'isReadOnly' => $propDef->isReadOnly(),
				'config' => $propDef->getConfig()->toArray(),
				'key' => $propDef->getKey(),
				'isMultiValued' => $propDef->isMultiValued(),
				'default' => $propDef->getDefault(),
				'sortorder' => $propDef->getSortorder(),
				'required' => $propDef->isRequired(),
				'isSearchable' => $propDef->isSearchable()
			];
		}

		return [
			'name' => $def->getName(),
			'pluralName' => $def->getPluralName(),
			'parent' => $def->getParent(),
			'qname' => $def->getQName(),
			'isReadOnly' => $def->isReadOnly(),
			'config' => $def->getConfig()->toArray(),
			'isExtension' => $def->isExtension(),
			'isAbstract' => $def->isAbstract(),
			'uuid' => $def->getUuid(),
			'version' => $def->getVersion(),
			'properties' => $properties,
			'associations' => $def->getAssociations(),
			'extensions' => $def->getExtensions()
		];
	}

	/**
	 * @return RepositoryInterface
	 */
	public function getRepository(): RepositoryInterface
	{
		return $this->repository;
	}

	/**
	 *
	 * @param NodeTypeDef $def
	 * @param $properties
	 * @throws \Exception
	 */
	private function attachJsonPropertiesToTypeDef(NodeTypeDef $def, iterable $properties)
	{
		/** @var Dictionary|array $property */
		foreach($properties as $property) {
			$key = $property->get('key');
			$name = $property->get('name');
			$propertyType = $property->get('type');
			$required = $property->get('required', false);
			$default = $property->get('default');
			$isMultiValued = $property->get('isMultiValued', false);
			$isReadOnly = $property->get('isReadOnly', false);
			$sortorder = $property->get('sortorder');
			$config = null; // $property->get('config');

			// Check name
			if (empty($name)) throw new \Exception(sprintf('Property at index %d is missing property "name"', $ix));

			// Verify boolean values
			foreach(['required', 'isMultiValued', 'isReadOnly'] as $requiredBool) {
				$boolVal = $$requiredBool;
				if (null !== $boolVal && !is_bool($boolVal)) throw new \Exception(sprintf('Property "%s" at index %d must be a boolean value', $requiredBool, $ix));
			}

			// Verify integer values
			foreach(['sortorder'] as $requiredInt) {
				$intVal = $$requiredInt;
				if (null !== $intVal && !is_int($intVal)) throw new \Exception(sprintf('Property "%s" at index %d must be an integer value', $requiredInt, $ix));
			}

			$propertyDef = $this->getRepository()->getNodeTypeService()->createPropertyDef($def->getQName(), $key, $name, $propertyType, $required, $default, $isMultiValued, $sortorder, $config);
			if (null !== $isReadOnly) $propertyDef->isReadOnly($isReadOnly);

			$def->setProperty($key, $propertyDef);
		}
	}

	private function assertHasRequiredTypeVars(Dictionary $data)
	{
		$requiredVars = ['qname'];

		$missing = [];
		$descMissing = [];
		foreach($requiredVars as $requiredVar) {

			$value = '[not set]';

			if ($data->has($requiredVar)) {
				$value = is_string($data->get($requiredVar)) ? $data->get($requiredVar) : '[not string]';
			} else {
				$missing[] = $requiredVar;
			}
			$descMissing[] = sprintf('%s = %s', $requiredVar, $value);
		}

		if (count($missing) > 0) throw new \RuntimeException(sprintf('Missing required value(s): %s (%s)', implode(', ', $missing), implode('; ', $descMissing)));
	}
}