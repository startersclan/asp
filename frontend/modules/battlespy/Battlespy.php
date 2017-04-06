<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\View;

/**
 * Battlespy Module Controller
 *
 * @package Modules
 */
class Battlespy extends \System\Controller
{
    /**
     * @var BattlespyModel
     */
    protected $model;

    /**
     * @protocol    ANY
     * @request     /ASP/battlespy
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();

        // Attach Model
        $this->loadModel('BattlespyModel', 'battlespy', 'model');

        // Fetch report list!
        $reports = $this->model->getReportList();

        // Load view
        $view = new View('index', 'battlespy');
        $view->set('reports', $reports);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/battlespy/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");

        // Send output
        $view->render();
    }
}