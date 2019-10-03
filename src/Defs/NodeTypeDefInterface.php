<?php

namespace WebImage\Node\Defs;

use WebImage\Config\Config;

interface NodeTypeDefInterface
{
	/**
	 * A friendly name for the type
	 * @return null|string
	 */
	public function getName(): ?string;

	/**
	 * A pluralized friendly name for the type
	 * @return string|null
	 */
	public function getPluralName(): ?string;

	/**
	 * A machine friendly qualified name for the type
	 * @return QName|null
	 */
	public function getQName(): ?string;

	/**
	 * A unique identifier for the node behind the type
	 * @return string|null
	 */
	public function getUuid(): ?string;

	/**
	 * The type's version
	 * @return string|null
	 */
	public function getVersion(): ?string;

	/**
	 * The types configuration
	 * @return Config|null
	 */
	public function getConfig(): ?Config;

	/**
	 * An array of properties for the type
	 * @return \WebImage\Node\Defs\NodeTypePropertyDictionary|\WebImage\Node\Defs\NodeTypePropertyDef[]
	 */
	public function getProperties(): NodeTypePropertyDictionary;

	/**
	 * @param $key
	 *
	 * @return mixed|null
	 */
	public function getProperty($key);

	/**
	 * @return string|null
	 */
	public function getParent(): ?string;

	/**
	 * @return string[]
	 */
	public function getAssociations(): array;

	/**
	 * @return string[]
	 */
	public function getExtensions(): array;

	/**
	 * @return bool
	 */
	public function isExtension(): bool;

	/**
	 * Whether nodes can be created of this type, or whether this just provides a basis for other types to build from
	 * @return bool
	 */
	public function isAbstract(): bool;

	/**
	 * Whether changes can be made to this type
	 * @return bool
	 */
	public function isReadOnly(): bool;

	/**
	 * Whether other types can extend this type
	 * @return bool
	 */
	public function isFinal(): bool;

	public function setAbstract(bool $isAbstract);
	public function setReadOnly(bool $isReadOnly);
	public function setFinal(bool $isFinal);

	public function setName(string $name);

	/**
	 * @param string $pluralName
	 */
	public function setPluralName(string $pluralName);

	/**
	 * @param string $qname
	 */
	public function setQName(string $qname);

	/**
	 * @param string $uuid
	 */
	public function setUuid(string $uuid);

	/**
	 * @param int $version
	 */
	public function setVersion(int $version);

	/**
	 * @param Config $config
	 */
	public function setConfig(Config $config);

	/**
	 * @param string $key
	 * @param NodeTypePropertyDef $definition
	 */
	public function setProperty(string $key, NodeTypePropertyDef $definition);

	/**
	 * @param NodeTypePropertyDictionary $dictionary
	 */
	public function setProperties(NodeTypePropertyDictionary $dictionary);

	/**
	 * @param string $parent The name of the types parent
	 */
	public function setParent(string $parent);

	/**
	 * @param string $association
	 */
	public function addAssociation(string $association);

	/**
	 * @param string $typeQName
	 */
	public function addExtension(string $typeQName);
}