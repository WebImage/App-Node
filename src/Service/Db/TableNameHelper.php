<?php

namespace WebImage\Node\Service\Db;

use WebImage\Node\Defs\NodeTypeDefInterface;

class TableNameHelper
{
	/**
	 * Generate a table name based on the type definition
	 * @param NodeTypeDefInterface $def
	 * @return string
	 */
	public function getTableNameFromDef(NodeTypeDefInterface $def): string
	{
		if ($def instanceof NodeTypeRefInterface && null !== $def->getTableKey() && strlen($def->getTableKey()) > 0) return $def->getTableKey(); // Table key already defined
		if ($def->getConfig()->has('modelKey')) return $def->getConfig()->get('modelKey'); // Table key defined in config as modelKey

		$tablePrefix = $def->isExtension() ? 'nx' : 'nt'; // nt = node type; nx = node extension
		$tableName = $def->getPluralName();
		if (empty($tableName)) $tableName = $this->generateTableName($def);
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
	 * Generates a table name that can be used when plural name is not set
	 *
	 * @param NodeTypeDefInterface $def
	 * @return string
	 */
	private function generateTableName(NodeTypeDefInterface $def): string
	{
		$parts = explode('.', $def->getQName());

		return array_pop($parts);
	}
}