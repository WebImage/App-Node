<?php

namespace WebImage\Node\Service\Db;

use WebImage\Db\ConnectionManager;

trait ConnectionManagerTrait {
	/** @var \Doctrine\DBAL\Connection */
	private $connectionManager;

	/**
	 * @return ConnectionManager
	 */
	public function getConnectionManager()/*: Manager */
	{
		return $this->connectionManager;
	}

	public function setConnectionManager(ConnectionManager $connectionManager)
	{
		$this->connectionManager = $connectionManager;
	}

	private function insertRecord($tableName, array $data)
	{
		$qb = $this->getConnectionManager()
			->createQueryBuilder()
			->insert($tableName);

		foreach($data as $key => $val) {
			$qb->setValue('`' . $key . '`', ':'  . $key);
			$qb->setParameter(':' . $key, $val);
		}

		return $qb->execute();
	}

	private function updateRecord($tableName, array $data, array $where)
	{
		$qb = $this->getConnectionManager()
			->createQueryBuilder()
			->update($tableName);

		foreach($data as $key => $val) {
			$qb->set('`' . $key . '`', ':' . $key);
			$qb->setParameter(':' . $key, $val);
		}

		foreach($where as $key => $val) {
			$qb->andWhere('`' . $key . '` = :' . $key);
			$qb->setParameter(':' . $key, $val);
		}

		return $qb->execute();
	}

	private function deleteRecord($tableName, array $where)
	{
		$qb = $this->getConnectionManager()
			->createQueryBuilder()
			->delete($tableName);

		foreach($where as $key => $val) {
			$qb->andWhere('`' . $key . '` = :' . $key);
			$qb->setParameter(':' . $key, $val);
		}

		return $qb->execute();
	}
}