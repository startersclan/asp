<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Controller;
use System\Database as DB;
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\View;

class Database extends Controller
{
    /**
     * @protocol    ANY
     * @request     /ASP/database
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();
        $pdo = DB::GetConnection('stats');

        // Create view
        $view = new View('index', 'database');

        // A list of tables we care about
        $whitelist = [
            'mapinfo', 'server', 'round_history', 'player', 'player_army', 'player_award', 'player_weapon',
            'player_kit', 'player_kill', 'player_map', 'player_history', 'player_vehicle', 'player_unlock'
        ];
        $tables = [];

        // Get table sizes
        $q = $pdo->query("SHOW TABLE STATUS");
        while ($row = $q->fetch())
        {
            // Skip tables we dont care about
            if (!in_array($row['Name'], $whitelist))
                continue;

            $size = $row["Data_length"] + $row["Index_length"];
            $tables[] = [
                'name' => $row['Name'],
                'size' => $this->toFilesize($size),
                'rows' => number_format($row['Rows']),
                'avg_row_length' => $this->toFilesize($row['Avg_row_length']),
                'engine' => $row['Engine']
            ];
        }

        // Render View
        $view->set('tables', $tables);
        $view->render();
    }

    /**
     * @protocol    GET
     * @request     /ASP/database/backup
     * @output      html
     */
    public function getBackup()
    {
        // Create view
        $view = new View('backup', 'database');
        $view->attachScript("/ASP/frontend/modules/database/js/backup.js");
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/database/backup
     * @output      html
     */
    public function postBackup()
    {
        if (!isset($_POST['action']) || $_POST['action'] != 'backup')
        {
            if (isset($_POST['ajax']))
                json_encode(['success' => false, 'message' => 'Invalid Action!']);
            else
                $this->getBackup();

            return;
        }

        // Check that the backup directory is writable
        $path = SYSTEM_PATH . DS . 'backups';
        if (!Directory::IsWritable($path))
        {
            json_encode(['success' => false, 'message' => 'Database backup path (' . $path . ') is not writable!']);
            die;
        }

        // We require a database!
        $this->requireDatabase();
        $pdo = DB::GetConnection('stats');

        try
        {
            // Define backup folder path
            $path = Path::Combine($path, date('Y-m-d_Hi'));

            // Delete directory for sub path if it does exist already
            if (Directory::Exists($path))
                Directory::Delete($path, true);

            // Create directory
            Directory::CreateDirectory($path, 0775);

            // A list of tables we care about
            $tables = [
                'army', 'kit', 'vehicle', 'weapon', 'unlock',
                'mapinfo', 'server', 'round_history', 'player', 'player_army', 'player_award', 'player_weapon',
                'player_kit', 'player_kill', 'player_map', 'player_history', 'player_vehicle', 'player_unlock'
            ];

            // Perform backups
            // Process each upgrade only if the version is newer
            foreach ($tables as $table)
            {
                // Check Table Exists
                $result = $pdo->query("SHOW TABLES LIKE '" . $table . "'");
                if (!empty($result->fetchAll()))
                {
                    // Table Exists, lets back it up
                    $backupFile = $pdo->quote($path . DS . $table . ".csv");
                    $query = "SELECT * INTO OUTFILE {$backupFile} FIELDS TERMINATED BY ',' OPTIONALLY ENCLOSED BY '\"' LINES TERMINATED BY '\n' FROM `{$table}`;";

                    // Try to execute
                    try
                    {
                        $pdo->exec($query);
                    }
                    catch (PDOException $e)
                    {
                        $errors[] = "Table (" . $table . ") *NOT* Backed Up: {$e->getMessage()}}";
                    }
                }
            }

            // Prepare for Output
            $html = '';
            if (!empty($errors))
            {
                // Delete backup!
                Directory::Delete($path, true);

                // Generate error message
                $html .= 'Failed to backup all database tables... <br /><br />List of Errors:<br /><ul>';
                foreach ($errors as $e)
                    $html .= '<li>' . $e . '</li>';

                $html .= '</ul>';

                echo json_encode(['success' => false, 'message' => $html]);
            }
            else
            {
                $data = ['dbver' => DB_VER, 'timestamp' => time()];

                // Create manifest
                $file = File::OpenWrite($path . DS . "backup.json");
                $file->write(json_encode($data, JSON_PRETTY_PRINT));
                $file->close();

                // Tell the client that we were successful
                echo json_encode(['success' => true, 'message' => 'System Data Backup Successful!']);
            }
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            json_encode(['success' => false, 'message' => $e->getMessage()]);

        }
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