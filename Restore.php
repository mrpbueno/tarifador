<?php
/**
 * Tarifador Restore Class for FreePBX 17+
 *
 * This file contains the Restore class for the Tarifador module.
 * It handles the restoration of the module's data as part of the
 * FreePBX Backup & Restore module integration.
 *
 * @package     FreePBX\modules\Bosssec
 * @license     GPLv3
 */
namespace FreePBX\modules\Tarifador;

use FreePBX\modules\Backup\RestoreBase;

/**
 * Tarifador Restore Class.
 *
 * Implements the necessary methods to integrate with the FreePBX Restore process
 * for versions 15 and higher. This class handles both modern (v17+) and legacy
 * backup formats.
 */
class Restore extends RestoreBase {

	/**
	 * Restores data from a modern (v17+) backup.
	 *
	 * This method is called by the FreePBX Backup & Restore module when restoring
	 * a backup created on FreePBX 17 or newer. It restores advanced settings
	 * and database tables from the backup data.
	 */
	public function runRestore() {
		$configs = $this->getConfigs();
		if (!empty($configs['settings']) && is_array($configs['settings'])) {
			$this->importAdvancedSettings($configs['settings']);
		}
		if (!empty($configs['tables']) && is_array($configs['tables'])) {
			$this->importTables($configs['tables']);
		}
	}

	/**
	 * Processes and restores data from a legacy (pre-v15) backup.
	 *
	 * This method is responsible for handling backups created on older versions
	 * of FreePBX. It receives a PDO connection to a temporary database containing
	 * the legacy data and restores the database tables from it.
	 *
	 * @param \PDO   $pdo           The PDO connection object to the temporary backup database.
	 * @param array  $data          An array containing legacy backup data.
	 * @param array  $tables        An array of table names found in the legacy backup.
	 * @param array  $unknownTables An array of table names that are not recognized by the current module.
	 */
	public function processLegacy($pdo, $data, $tables, $unknownTables) {
		$this->restoreLegacyDatabase($pdo);
	}
}