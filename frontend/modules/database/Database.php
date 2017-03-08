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
     * Converts a size in bytes to a compact human readable string
     *
     * @param int $bytes The size in bytes
     * @param int $decimals The number of decimal places
     *
     * @return string
     */
    private function toFilesize($bytes, $decimals = 2)
    {
        $size = array('B','kB','MB','GB','TB','PB','EB','ZB','YB');
        $factor = (int)floor((strlen($bytes) - 1) / 3);
        return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . ' '. $size[$factor];
    }
}