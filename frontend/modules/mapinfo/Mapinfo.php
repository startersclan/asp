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
use System\Database;
use System\View;

class Mapinfo extends Controller
{
    /**
     * @protocol    ANY
     * @request     /ASP/mapinfo
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();

        // Fetch server list!
        $pdo = Database::GetConnection('stats');
        $result = $pdo->query("SELECT * FROM `mapinfo` ORDER BY id ASC");
        $maps = $result->fetchAll() or [];

        $maps = array_map(function ($values)
        {
            $time = (int)$values['time'];
            $values['time'] = $this->secondsToTime($time);
            return $values;
        }, $maps);

        // Load view
        $view = new View('index', 'mapinfo');
        $view->set('maps', $maps);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/mapinfo/js/index.js");

        // Send output
        $view->render();
    }

    private function secondsToTime($seconds)
    {
        // extract hours
        $hours = floor($seconds / (60 * 60));

        // extract minutes
        $divisor_for_minutes = $seconds % (60 * 60);
        $minutes = floor($divisor_for_minutes / 60);

        // extract the remaining seconds
        $divisor_for_seconds = $divisor_for_minutes % 60;
        $seconds = ceil($divisor_for_seconds);

        $obj = '';

        if ($hours > 0)
            $obj .= $hours ." Hrs, ";

        if ($minutes > 0)
            $obj .= $minutes ." Mins, ";

        if ($seconds > 0)
            $obj .= $seconds ." Secs.";

        return rtrim($obj, ", ");
    }
}