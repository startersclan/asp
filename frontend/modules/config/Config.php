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
    public function getIndex()
    {
        $items = Cfg::FetchAll();

        $view = new View('index', 'config');
        $view->set('config', $items);

        $view->attachScript("/ASP/frontend/modules/config/js/editconfig.js");
        $view->render();
    }

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