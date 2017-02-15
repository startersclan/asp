<?php

/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Config AS Cfg;
use System\View;

class Config
{
    /**
     * @protocol    ANY
     * @request     /ASP/config/[?:index]
     * @output      html
     */
    public function index()
    {
        // Fetch all vars from the system config file
        $items = Cfg::FetchAll();

        // Set view variables
        $view = new View('index', 'config');
        $view->set('config', $items);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/modules/config/js/editconfig.js");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/config/save
     * @output      json
     */
    public function postSave()
    {
        try
        {
            foreach ($_POST as $item => $val)
            {
                $key = explode('__', $item);
                if ($key[0] == 'cfg')
                {
                    Cfg::Set($key[1], $val);
                }
            }

            // Determine if our save is a success
            $result = Cfg::Save();
            echo json_encode( array('success' => $result) );
        }
        catch (Exception $e)
        {
            // Determine if our save is a success
            echo json_encode( array('success' => 'false', 'message' => $e->getMessage()) );
        }
    }
}