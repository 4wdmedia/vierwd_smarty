<?php

namespace Vierwd\VierwdSmarty\Install\Updates;

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Updates\AbstractUpdate;

/**
 * Migrate "vierwd_smarty" static template location. "Old" location was "Configuration/TypoScript/v8". New location is default "Configuration/TypoScript"
 */
class MigrateFscStaticTemplateUpdate extends AbstractUpdate {

	/**
	 * @var string
	 */
	protected $title = 'Migrate "vierwd_smarty" static template location';

	/**
	 * Checks if an update is needed
	 *
	 * @param string &$description The description for the update
	 * @return bool Whether an update is needed (TRUE) or not (FALSE)
	 */
	public function checkForUpdate(&$description) {
		if ($this->isWizardDone() || !class_exists(ConnectionPool::class)) {
			return false;
		}

		$queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_template');
		$queryBuilder->getRestrictions()->removeAll();
		$elementCount = $queryBuilder->count('uid')
			->from('sys_template')
			->where(
				$queryBuilder->expr()->orX(
					$queryBuilder->expr()->like(
						'constants',
						$queryBuilder->createNamedParameter('%EXT:vierwd_smarty/Configuration/TypoScript/v8%', \PDO::PARAM_STR)
					),
					$queryBuilder->expr()->like(
						'config',
						$queryBuilder->createNamedParameter('%EXT:vierwd_smarty/Configuration/TypoScript/v8%', \PDO::PARAM_STR)
					),
					$queryBuilder->expr()->like(
						'include_static_file',
						$queryBuilder->createNamedParameter('%EXT:vierwd_smarty/Configuration/TypoScript/v8%', \PDO::PARAM_STR)
					)
				)
			)
			->execute()->fetchColumn(0);
		if ($elementCount) {
			$description = 'Static templates have been relocated to EXT:vierwd_smarty/Configuration/TypoScript/';
		}
		return (bool)$elementCount;
	}

	/**
	 * Performs the database update
	 *
	 * @param array &$databaseQueries Queries done in this update
	 * @param string &$customMessage Custom message
	 * @return bool
	 */
	public function performUpdate(array &$databaseQueries, &$customMessage) {
		$connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('sys_template');
		$queryBuilder = $connection->createQueryBuilder();
		$queryBuilder->getRestrictions()->removeAll();
		$statement = $queryBuilder->select('uid', 'include_static_file', 'constants', 'config')
			->from('sys_template')
			->where(
				$queryBuilder->expr()->orX(
					$queryBuilder->expr()->like(
						'constants',
						$queryBuilder->createNamedParameter('%EXT:vierwd_smarty/Configuration/TypoScript/v8%', \PDO::PARAM_STR)
					),
					$queryBuilder->expr()->like(
						'config',
						$queryBuilder->createNamedParameter('%EXT:vierwd_smarty/Configuration/TypoScript/v8%', \PDO::PARAM_STR)
					),
					$queryBuilder->expr()->like(
						'include_static_file',
						$queryBuilder->createNamedParameter('%EXT:vierwd_smarty/Configuration/TypoScript/v8%', \PDO::PARAM_STR)
					)
				)
			)
			->execute();
		while ($record = $statement->fetch()) {
			$search = 'EXT:vierwd_smarty/Configuration/TypoScript/v8';
			$replace = 'EXT:vierwd_smarty/Configuration/TypoScript';
			$record['include_static_file'] = str_replace($search, $replace, $record['include_static_file']);
			$record['constants'] = str_replace($search, $replace, $record['constants']);
			$record['config'] = str_replace($search, $replace, $record['config']);
			$queryBuilder = $connection->createQueryBuilder();
			$queryBuilder->update('sys_template')
				->where(
					$queryBuilder->expr()->eq(
						'uid',
						$queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
					)
				)
				->set('include_static_file', $record['include_static_file'])
				->set('constants', $record['constants'])
				->set('config', $record['config']);
			$databaseQueries[] = $queryBuilder->getSQL();
			$queryBuilder->execute();
		}
		$this->markWizardAsDone();
		return true;
	}
}
