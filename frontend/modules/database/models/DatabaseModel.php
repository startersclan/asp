<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

use System\Collections\Dictionary;
use System\Database;
use System\Database\SqlFileParser;
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\Version;

/**
 * Database Model
 *
 * @package Models
 * @subpackage Database
 */
class DatabaseModel
{
    /**
     * @var array A list of tables we backup and restore from
     */
    protected static $BackupTables = [
        'army', 'kit', 'vehicle', 'weapon', 'unlock', 'map', 'server', 'round',
        'player', 'player_army', 'player_award', 'player_weapon', 'player_kit', 'player_kill',
        'player_map', 'player_rank_history', 'player_vehicle', 'player_unlock',
        'player_army_history', 'player_round_history', 'player_kill_history', 'player_kit_history',
        'player_weapon_history', 'player_vehicle_history', 'battlespy_report', 'battlespy_message'
    ];

    /**
     * @var array A list of tables that we can clear. Reverse order is important here (foreign keys)!
     */
    protected static $ClearTables = [
        'battlespy_message', 'battlespy_report', 'player_army_history', 'player_round_history', 'player_kill_history',
        'player_kit_history', 'player_weapon_history', 'player_vehicle_history', 'risingstar', 'player_unlock',
        'player_vehicle', 'player_weapon', 'player_rank_history', 'player_map', 'player_kill',
        'player_kit', 'player_award', 'player_army', 'player', 'round', 'server', 'map'
    ];

    /**
     * @var int The maximum rows to pull per query (used for backups)
     */
    const MAX_PAGE_SIZE = 1000;

    /**
     * Fetches the table status of the specified tables
     *
     * @param string[] $tables An array of tables to fetch the status for
     *
     * @return array
     */
    public function getTableStatus($tables)
    {
        $pdo = Database::GetConnection('stats');
        $return = [];

        // Get table sizes
        $q = $pdo->query("SHOW TABLE STATUS");
        while ($row = $q->fetch())
        {
            // Skip tables we don't care about
            if (!in_array($row['Name'], $tables))
                continue;

            // Get an accurate row count with InnoDB, since it returns an estimate in STATUS
            $rowCount = (strtolower($row['Engine']) == 'innodb')
                ? (int)$pdo->query("SELECT COUNT(*) FROM ". $row['Name'])->fetchColumn(0)
                : $row['Rows'];

            // Determine size, and output data
            $size = $row["Data_length"] + $row["Index_length"];
            $return[] = [
                'name' => $row['Name'],
                'size' => $this->toFilesize($size),
                'rows' => number_format($rowCount),
                'avg_row_length' => $this->toFilesize($row['Avg_row_length']),
                'engine' => $row['Engine']
            ];
        }

        return $return;
    }

    /**
     * Creates a backup of all the stats related tables into csv files.
     *
     * @param string $path The full directory path where the backup files are to be stored
     *
     * @return void
     */
    public function createStatsBackup($path)
    {
        // Delete directory for sub path if it does exist already
        if (Directory::Exists($path))
            Directory::Delete($path, true);

        // Create directory
        Directory::CreateDirectory($path, 0775);

        // Grab database
        $pdo = Database::GetConnection('stats');
        $pageSize = DatabaseModel::MAX_PAGE_SIZE;

        // Perform backups
        // Process each upgrade only if the version is newer
        foreach (self::$BackupTables as $table)
        {
            $backupFile = $path . DS . $table . ".csv";
            $file = File::OpenWrite($backupFile);

            // Check Table Exists
            $result = $pdo->query("SHOW TABLES LIKE '" . $table . "'");
            if (!empty($result->fetchAll()))
            {
                /** @noinspection SqlResolve */
                $count = (int)$pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn(0);
                $i = 0;
                while ($count > 0)
                {
                    // Table Exists, lets back it up
                    /** @noinspection SqlResolve */
                    $query = "SELECT * FROM `{$table}` LIMIT {$pageSize} OFFSET $i;";
                    $result = $pdo->query($query);
                    while ($row = $result->fetch())
                    {
                        $file->writeCSVLine($row);
                    }

                    $i += $pageSize;
                    $count -= $pageSize;
                }
            }

            $file->close();
        }

        // Create manifest
        $json = json_encode(['dbver' => DB_VERSION, 'timestamp' => time()], JSON_PRETTY_PRINT);
        $file = File::OpenWrite($path . DS . "backup.json");
        $file->write($json);
        $file->close();
    }

    /**
     * Replaces the current stats data from a backup
     *
     * @param string $path The full directory path where the backup.json file is located
     *
     * @return void
     *
     * @throws Exception if there are any SQL errors when restoring the data
     */
    public function restoreStatsFromBackup($path)
    {
        // Grab database
        $pdo = Database::GetConnection('stats');

        // Check for the manifest
        $file = File::OpenRead($path . DS . "backup.json");
        $data = json_decode($file->readToEnd(), true);

        // Check that the DB version matches the backup table version
        if (!isset($data['dbver']) || !Version::Equals($data['dbver'], DB_VERSION))
            throw new Exception('The backup database version does not match the table version!');

        try
        {
            // Start transaction as we are moving A LOT of data around.
            $pdo->beginTransaction();

            // Store the backup tables list in new variable, for when we reverse it later.
            $tables = self::$BackupTables;

            // Clear old stuff
            foreach (array_reverse($tables) as $table)
            {
                /** @noinspection SqlResolve */
                $pdo->exec("DELETE FROM `{$table}`;");
            }

            // Reset auto increments
            $pdo->exec("ALTER TABLE `player` AUTO_INCREMENT = 2900000;");
            $pdo->exec("ALTER TABLE `server` AUTO_INCREMENT = 1;");
            $pdo->exec("ALTER TABLE `round` AUTO_INCREMENT = 1;");

            // Process each upgrade only if the version is newer
            foreach (self::$BackupTables as $table)
            {
                // Check Table Exists
                $result = $pdo->query("SHOW TABLES LIKE '" . $table . "'");
                if (!empty($result->fetchAll()))
                {
                    // Table Exists, lets back it up
                    $backupFile = $pdo->quote($path . DS . $table . ".csv");

                    // Try to execute
                    $query = "LOAD DATA LOCAL INFILE {$backupFile} INTO TABLE `{$table}` FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n';";
                    $pdo->exec($query);
                }
            }

            // Commit changes
            $pdo->commit();
        }
        catch (Exception $e)
        {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Clears all *STATS* related data from the stats database
     */
    public function clearStatsTables()
    {
        // Grab database
        $pdo = Database::GetConnection('stats');

        try
        {
            // Start transaction as we are moving A LOT of data around.
            $pdo->beginTransaction();

            // Clear old stuff
            foreach (self::$ClearTables as $table)
            {
                /** @noinspection SqlResolve */
                $pdo->exec("DELETE FROM `{$table}`;");
            }

            // Reset auto increments
            $pdo->exec("ALTER TABLE `player` AUTO_INCREMENT = 29000000;");
            $pdo->exec("ALTER TABLE `server` AUTO_INCREMENT = 1;");
            $pdo->exec("ALTER TABLE `round` AUTO_INCREMENT = 1;");

            // Commit changes
            $pdo->commit();
        }
        catch (Exception $e)
        {
            $pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Migrates the current database version to the latest database schema
     *
     * @return bool true if the migration succeeded, false otherwise.
     *
     * @throws Exception if there is any errors moving the database schema up
     */
    public function migrateUp()
    {
        $expected = Version::Parse(DB_EXPECTED_VERSION);
        $version = Version::Parse(DB_VERSION);

        // Do we really need to migrate?
        if ($version->compare($expected) >= 0)
            return false;

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        $inTransaction = false;

        // Get our migrations list
        $migrations = include Path::Combine(SYSTEM_PATH, 'sql', 'migrations', 'migrations.php');
        $migrations = new Dictionary(false, $migrations);

        // Grab current database version
        $stmt = $pdo->query("SELECT `version` FROM `_version` ORDER BY `updateid` DESC LIMIT 1;");
        $result = $stmt->fetchColumn(0);
        if ($result === false)
            return false;

        try
        {
            // Apply all migrations until we are current
            do
            {
                // Get our next migration sql file
                $currentMigration = $migrations[$result];
                $nextVersion = $currentMigration['up'];

                // Upgrade until we hit the end
                if ($nextVersion == null)
                    break;

                // Create parser
                $file = Path::Combine(SYSTEM_PATH, 'sql', 'migrations', 'up', $nextVersion . '.sql');
                $parser = new SqlFileParser($file);
                $queries = $parser->getStatements();

                // Start transaction as we are moving A LOT of data around.
                $pdo->beginTransaction();
                $inTransaction = true;

                // Read file contents
                foreach ($queries as $query)
                {
                    $pdo->exec($query);
                }

                // Apply changes
                $pdo->commit();
                $inTransaction = false;

                // Grab new current version
                $stmt = $pdo->query("SELECT `version` FROM `_version` ORDER BY `updateid` DESC LIMIT 1;");
                $newResult = $stmt->fetchColumn(0);

                // Make sure we upgraded!
                if ($result == $newResult)
                {
                    throw new Exception("Failed to migrate the database from {$result} to {$newResult}");
                }

                $result = $newResult;
            } while (true);
        }
        catch (Exception $e)
        {
            if ($inTransaction)
                $pdo->rollBack();

            throw $e;
        }

        return true;
    }

    /**
     * Converts a size in bytes to a compact human readable string
     *
     * @param int $bytes The size in bytes
     * @param int $decimals The number of decimal places
     *
     * @return string
     */
    private function toFilesize($bytes, $decimals = 2)
    {
        $size = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');
        $factor = (int)floor((strlen($bytes) - 1) / 3);

        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' ' . $size[$factor];
    }
}