<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Controller;
use System\Database;
use System\View;

/**
 * Mapinfo Module Controller
 *
 * @package Modules
 */
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
        $maps = $pdo->query("SELECT * FROM map ORDER BY id ASC")->fetchAll();
        if ($maps === false) $maps = [];

        // Convert map time to a human readable format
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