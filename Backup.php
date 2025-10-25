<?php
/**
 * Tarifador Backup Class for FreePBX 17+
 *
 * This file contains the Backup class for the Tarifador module.
 * It handles the backup process for the module's data as part of the
 * FreePBX Backup & Restore module integration.
 *
 * @package     FreePBX\modules\Tarifador
 * @license     GPLv3
 */
namespace FreePBX\modules\Tarifador;

use FreePBX\modules\Backup\BackupBase;

/**
 * Tarifador Backup Class.
 *
 * Implements the necessary methods to integrate with the FreePBX Backup module
 * for versions 15 and higher. This class defines what data from the
 * Tarifador module should be included in a system backup.
 */
class Backup extends BackupBase {

	/**
	 * Executes the backup process for the Tarifador module.
	 *
	 * This method is called by the FreePBX Backup & Restore module during a backup operation.
	 * It is responsible for backing up the module's database tables and advanced settings.
	 *
	 * @param string $id The unique identifier for the current backup job.
	 * @param object $transaction The transaction object associated with the backup process.
	 */
	public function runBackup($id, $transaction) {
		$configs = [
            "tables"    => $this->dumpTables(),
            'settings'  => $this->dumpAdvancedSettings(),
        ];
        $this->addConfigs($configs);
	}
}