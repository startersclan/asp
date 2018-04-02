<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Database;

class MapinfoModel
{
    /**
     * Sets the display name of a map by id
     *
     * Sanitization does occur in this method
     *
     * @param int $id
     * @param string $name
     *
     * @return bool
     */
    public function setMapDisplayNameById($id, $name)
    {
        $pdo = Database::GetConnection('stats');
        $id = (int)$id;
        $name = preg_replace('/[^A-Za-z0-9_\-\s\t\/\.]/', '', trim($name));
        return $pdo->update('map', ['displayname' => $name], ['id' => $id]);
    }

    /**
     * @param bool $format Use number_format
     *
     * @return array
     */
    public function getMapStatistics($format = true)
    {
        // Fetch map list
        $pdo = Database::GetConnection('stats');
        $query = <<<SQL
SELECT 
	m.id AS id, 
	m.name,
    m.displayname, 
    COALESCE(SUM(DISTINCT r.time_end - r.time_start), 0) AS time, 
    COALESCE(SUM(rh.kills), 0) AS kills, 
    COALESCE(SUM(rh.deaths), 0) AS deaths, 
    COALESCE(SUM(rh.score), 0) AS score, 
    COUNT(DISTINCT r.id) as `count`
FROM map AS m
    LEFT JOIN round AS r ON r.map_id = m.id
    LEFT JOIN player_round_history AS rh ON rh.round_id = r.id
WHERE rh.score > 0
GROUP BY m.id ORDER BY m.id
SQL;

        $maps = [];
        $rows = $pdo->query($query);
        while ($row = $rows->fetch())
        {
            if ($format)
            {
                $time = (int)$row['time'];
                $row['time'] = $this->secondsToTime($time);
                $row['score'] = number_format($row['score']);
                $row['kills'] = number_format($row['kills']);
                $row['deaths'] = number_format($row['deaths']);
            }
            $maps[] = $row;
        }

        return $maps;
    }

    /**
     * @param $id
     * @param bool $format Use number_format
     *
     * @param array $data [Reference Variable] Data filled if the map has been played with map info
     *
     * @return bool true if map data is found in the database, false otherwise
     */
    public function getMapStatisticsById($id, $format = true, array &$data)
    {
        // Fetch server list!
        $pdo = Database::GetConnection('stats');
        $query = <<<SQL
SELECT 
	m.id AS id, 
    m.displayname AS name, 
    COALESCE(SUM(DISTINCT r.time_end - r.time_start), 0) AS time, 
    COALESCE(SUM(rh.kills), 0) AS kills, 
    COALESCE(SUM(rh.deaths), 0) AS deaths, 
    COALESCE(SUM(rh.score), 0) AS score, 
    COUNT(DISTINCT r.id) as `count`
FROM map AS m
    LEFT JOIN round AS r ON r.map_id = m.id
    LEFT JOIN player_round_history AS rh ON rh.round_id = r.id
WHERE m.id = $id
GROUP BY m.id ORDER BY m.id LIMIT 1
SQL;

        $row = $pdo->query($query)->fetch();
        if (empty($row))
        {
            $data = [];
            return false;
        }
        else
        {
            if ($format)
            {
                $time = (int)$row['time'];
                $row['time'] = $this->secondsToTime($time);
                $row['score'] = number_format($row['score']);
                $row['kills'] = number_format($row['kills']);
                $row['deaths'] = number_format($row['deaths']);
            }

            $data = $row;
            return true;
        }
    }

    private function secondsToTime($seconds)
    {
        $span = \System\TimeSpan::FromSeconds($seconds);
        $days = $span->getWholeDays();
        $obj = '';

        if ($days > 0)
        {
            $days = number_format($days);
            $obj = $days . " Days, ";
        }

        return $obj . $span->format('%y Hours, %j Minutes');
    }
}