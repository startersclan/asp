<?php
use System\Config;
use System\Database;
use System\Response;
use System\View;

/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
class Install
{
    public function index()
    {
        if (isset($_POST['action']) && $_POST['action'] == 'save')
        {
            foreach ($_POST as $item => $val)
            {
                $key = explode('__', $item);
                if($key[0] == 'cfg')
                {
                    Config::Set($key[1], $val);
                }
            }

            // Save changes
            Config::Save();

            // Try to connect to the database with new settings
            try
            {
                Database::Connect('stats',
                    array(
                        'driver' => 'mysql',
                        'host' => Config::Get('db_host'),
                        'port' => Config::Get('db_port'),
                        'database' => Config::Get('db_name'),
                        'username' => Config::Get('db_user'),
                        'password' => Config::Get('db_pass')
                    )
                );

                // Success!
                Response::Redirect('./home');
                die;
            }
            catch (Exception $e)
            {
                // ignore
            }
        }

        // Create view
        $view = new View('index', 'install');
        $view->set('admin_user', Config::Get('admin_user'));
        $view->set('admin_pass', Config::Get('admin_pass'));
        $view->set('ip_list', htmlentities(implode("\n", Config::Get('admin_hosts')), ENT_HTML5, "UTF-8"));

        $view->set('db_host', Config::Get('db_host'));
        $view->set('db_port', Config::Get('db_port'));
        $view->set('db_user', Config::Get('db_user'));
        $view->set('db_pass', Config::Get('db_pass'));
        $view->set('db_name', Config::Get('db_name'));

        // Attach stylesheets and scripts for the wizard form
        $view->attachStylesheet("./frontend/js/wizard/wizard.css");
        $view->attachScript("./frontend/js/wizard/wizard.min.js");
        $view->attachScript("./frontend/modules/install/js/wizard.js");

        // Render view
        $view->render();
    }
}