<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\NodeTypeDefInterface;
use WebImage\Node\Entities\NodeType;
use WebImage\Node\Service\NodeTypeServiceInterface;

class TableNameHelper
{
	/**
	 * Generate a table name based on the type definition
	 * @param NodeTypeDefInterface $def
	 * @return string
	 */
	public function getTableKeyFromDef(NodeTypeDefInterface $def): string
	{
		if ($def instanceof NodeTypeRefInterface && null !== $def->getTableKey() && strlen($def->getTableKey()) > 0) return $def->getTableKey(); // Table key already defined
		if ($def->getConfig()->has('modelKey')) return $def->getConfig()->get('modelKey'); // Table key defined in config as modelKey

		$tablePrefix = $def->isExtension() ? 'nx' : 'nt'; // nt = node type; nx = node extension
		$tableName = $def->getPluralName();
		if (empty($tableName)) $tableName = $this->generateTableKey($def);
		$tableName = strtolower($tableName); // Lower case
		$tableName = preg_replace('/[^0-9a-z_]+/', '', $tableName);
		$tableName = $tablePrefix . '_' . $tableName;

		return $tableName;
	}

	public function shouldDefHavePhysicalTable(NodeTypeDefInterface $def): bool
	{
		return count($def->getProperties()) > 0;
	}

	/**
	 * Get the base node table to use for queries (the "FROM" table)
	 * @param NodeTypeServiceInterface $typeService
	 * @param string[] $typeQNames
	 *
	 * @return string
	 */
	public function getRootTableKey(NodeTypeServiceInterface $typeService, array $typeQNames): string
	{
		foreach($typeQNames as $typeQName) {
			$type = $typeService->getNodeTypeByTypeQName($typeQName);

			$typeStack = $type->getTypeStack();

			foreach($typeStack as $type) {
				$tableKey = $this->getTableKeyFromDef($type->getDef());
				if (!$this->shouldDefHavePhysicalTable($type->getDef())) continue;

				return $tableKey;
			}
		}

		// Should not make it this far
		throw new \RuntimeException(sprintf('No root node found for type(s): %s', implode(', ', $typeQNames)));
	}

	/**
	 * Get the base node QName that should be considered the root for database purposes
	 * @param NodeTypeServiceInterface $typeService
	 * @param string[] $typeQNames
	 *
	 * @throws \RuntimeException When no root is found
	 *
	 * @return string
	 */
	public function getRootTypeQName(NodeTypeServiceInterface $typeService, array $typeQNames): string
	{
		foreach($typeQNames as $typeQName) {
			$type = $typeService->getNodeTypeByTypeQName($typeQName);

			$root = $this->getRootType($typeService, $type->getTypeStack());

			if (null !== $root) {
				return $root->getDef()->getQName();
			}
		}

		// Should not make it this far
		throw new \RuntimeException(sprintf('No root node found for type(s): %s', implode(', ', $typeQNames)));
	}

	/**
	 * Get the base node QName that should be considered the root for database purposes
	 * @param NodeTypeServiceInterface $typeService
	 * @param NodeType[] $typeStack
	 *
	 * @return NodeType
	 */
	public function getRootType(NodeTypeServiceInterface $typeService, array $typeStack): NodeType
	{
		foreach($typeStack as $type) {
			if ($this->shouldDefHavePhysicalTable($type->getDef())) return $type;
		}
	}

	/**
	 * Convenience method for formatting column alias
	 * @param string $tableKey
	 * @param string $column
	 * @param string $propName (complex dataTypes types will have "child" columns names, where $column becomes $column__$property)
	 *
	 * @return string
	 */
	public function getColumnNameAlias(string $tableKey, string $column, string $propName=null): string
	{
		$format = '%s__%s';
		if (null !== $propName) $column = sprintf($format, $column, $propName);

		$alias = sprintf($format, $tableKey, $column);

		return $alias;
	}

	/**
	 * Convenience method for formatting column
	 * @param string $tableKey
	 * @param string $column
	 *
	 * @return string
	 */
	public function getColumnName(string $tableKey, string $column, string $propName=null): string
	{
		if (null !== $propName) $column = sprintf('%s_%s', $column, $propName);

		return sprintf('`%s`.`%s`', $tableKey, $column);
	}

	/**
	 * Generates a table name that can be used when plural name is not set
	 *
	 * @param NodeTypeDefInterface $def
	 * @return string
	 */
	private function generateTableKey(NodeTypeDefInterface $def): string
	{
		$parts = explode('.', $def->getQName());

		return array_pop($parts);
	}
}