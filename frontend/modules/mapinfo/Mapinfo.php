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
use System\Collections\Dictionary;
use System\View;

/**
 * Mapinfo Module Controller
 *
 * @package Modules
 */
class Mapinfo extends Controller
{
    /** @var MapinfoModel */
    protected $model;

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
        $this->loadModel('MapinfoModel', 'mapinfo', 'model');
        $maps = $this->model->getMapStatistics();

        // Load view
        $view = new View('index', 'mapinfo');
        $view->set('maps', $maps);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/modules/mapinfo/js/index.js");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/mapinfo/edit
     * @output      json
     */
    public function postEdit()
    {
        // Require action
        $this->requireAction('edit');

        // Require database connection
        $this->requireDatabase(true);

        // Load model
        $this->loadModel('MapinfoModel', 'Mapinfo', 'model');

        try
        {
            // Use a dictionary here to gain an exception on missing array item
            $items = new Dictionary(false, $_POST);

            // Define server id
            $this->model->setMapDisplayNameById($items['mapId'], $items['mapName']);

            // Send response
            $this->sendJsonResponse(true, 'Success', ['mapId' => $items['mapId'], 'displayName' => $items['mapName']]);
        }
        catch (Exception $e)
        {
            $pdo = System\Database::GetConnection('stats');
            $this->sendJsonResponse(false, 'Query Failed! '. $e->getMessage(), ['lastQuery' => $pdo->lastQuery]);
            die;
        }
    }
}