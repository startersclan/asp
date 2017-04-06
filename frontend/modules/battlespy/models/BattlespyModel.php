<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

/**
 * Battlespy Model
 *
 * @package Models
 * @subpackage Battlepspy
 */
class BattlespyModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * BattlespyModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * This method retrieves the battlespy report list
     *
     * @return array
     */
    public function getReportList()
    {
        // Fetch player
        $query = <<<SQL
SELECT r.*, rh.mapid, rh.round_end AS `timestamp`, s.name AS `server`, mi.name AS `mapname`,
  (SELECT COUNT(id) FROM battlespy_message WHERE `reportid` = r.id) AS `count`
FROM battlespy_report AS r
  LEFT JOIN round_history AS rh ON r.roundid = rh.id
  LEFT JOIN server AS s ON r.serverid = s.id
  LEFT JOIN mapinfo AS mi ON rh.mapid = mi.id
SQL;
        $result = $this->pdo->query($query);
        $reports = [];

        // Add date format
        while ($report = $result->fetch())
        {
            $i = (int)$report['timestamp'];
            $report['date'] = date('F jS, Y g:i A T', $i);
            $reports[] = $report;
        }

        return $reports;
    }

    public function getReportById($id)
    {

    }

    public function deleteReportById($id)
    {

    }

    public function deleteMessageById($id)
    {

    }
}