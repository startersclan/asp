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
use System\Database;
use System\IO\Directory;
use System\IO\File;
use System\Response;
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
     * @protocol    ANY
     * @request     /ASP/config/test
     * @output      html
     */
    public function test()
    {
        // Require database connection
        if (DB_VER == '0.0.0')
        {
            Response::Redirect('install');
            die;
        }

        // Check for POST data
        if ($_POST['action'] == 'runtests')
        {
            $this->runTests();
            die;
        }

        // Set view variables
        $view = new View('testconfig', 'config');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/modules/config/js/testconfig.js");

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

    protected function runTests()
    {
        // Remove our time limit!
        ini_set('max_execution_time', 30);

        // Define our pass/fail messages for less typing
        define('__PASS','<b><font color="green">Pass</font></b><br />');
        define('__WARN','<b><font color="orange">Warn</font></b><br />');
        define('__FAIL','<b><font color="red">Fail</font></b><br />');

        // Vars
        $errors = false;
        $warns = false;

        // Check Database Access
        $out = " > Checking Database...<br />";
        $DB = Database::GetConnection('stats');
        if( $DB instanceof PDO )
        {
            $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Database Connection (".Cfg::Get('db_host')."): ".__PASS;

            // Check Database Version
            if (DB_VER != Cfg::Get('db_expected_ver'))
                $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Database version (".Cfg::Get('db_expected_ver')."): ".__FAIL;
            else
                $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Database version (".Cfg::Get('db_expected_ver')."): ".__PASS;
        }
        else
        {
            $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Database Connection (".Cfg::Get('db_host')."): ".__FAIL;
            $errors = true;
        }

        // Check Config File Write Access
        $out .= " > Checking System Config...<br />";
        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- System Config Path Writable (system/config): ";
        $path = SYSTEM_PATH . DS .'config'. DS;
        if (!Directory::IsWritable( $path ))
        {
            $out .= __FAIL;
            $errors = true;
        }
        else
        {
            $out .= __PASS;
        }

        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Config File Writable (system/config/config.php): ";
        if (!File::IsWritable( SYSTEM_PATH . DS .'config'. DS .'config.php' ))
        {
            $out .= __FAIL;
            $errors = true;
        }
        else
        {
            $out .= __PASS;
        }

        // Check Log File Write Access
        $out .= " > Checking System Log Files...<br />";
        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Stats Debug Log File Writable (system/logs/stats_debug.log): ";
        $log = SYSTEM_PATH . DS . 'logs' . DS . 'stats_debug.log';
        if (!File::IsWritable( $log ))
        {
            $out .= __WARN;
            $warns = true;
        }
        else
        {
            $out .= __PASS;
        }

        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- ASP Debug Log File Writable (system/logs/asp_debug.log): ";
        $log = SYSTEM_PATH . DS . 'logs' . DS . 'asp_debug.log';
        if (!File::IsWritable( $log ))
        {
            $out .= __WARN;
            $warns = true;
        }
        else
        {
            $out .= __PASS;
        }

        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- PHP Error Log File Writable (system/logs/php_errors.log): ";
        $log = SYSTEM_PATH . DS . 'logs' . DS . 'php_errors.log';
        if (!File::IsWritable( $log ))
        {
            $out .= __WARN;
            $warns = true;
        }
        else
        {
            $out .= __PASS;
        }

        // SNAPSHOTS
        $out .= " > Checking SNAPSHOT Storage Paths...<br />";
        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- SNAPSHOT Temporary Path Writable (system/snapshots/unprocessed): ";
        $path = SYSTEM_PATH . DS .'snapshots'. DS .'unprocessed'. DS;
        if (!Directory::IsWritable( $path ))
        {
            $out .= __FAIL;
            $errors = true;
        }
        else
        {
            $out .= __PASS;
        }

        // Snapshot Archive Path
        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- SNAPSHOT Processed Path Writable (system/snapshots/processed): ";
        $path = SYSTEM_PATH . DS .'snapshots'. DS .'processed'. DS;
        if (!Directory::IsWritable( $path ))
        {
            $out .= __FAIL;
            $errors = true;
        }
        else
        {
            $out .= __PASS;
        }

        // Snapshot Fail Path
        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- SNAPSHOT Fail Path Writable (system/snapshots/failed): ";
        $path = SYSTEM_PATH . DS .'snapshots'. DS .'failed'. DS;
        if (!Directory::IsWritable( $path ))
        {
            $out .= __FAIL;
            $errors = true;
        }
        else
        {
            $out .= __PASS;
        }

        // Check Admin Backup Write Access
        $out .= " > Checking Database Backup Storage Path...<br />";
        $path = str_replace(array('/', '\\'), DS, ltrim(Cfg::Get('admin_backup_path'), '/'));
        $out .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;- Backup Path Writable ({$path}): ";
        if (!File::IsWritable( $path ))
        {
            $out .= __FAIL;
            $errors = true;
        }
        else
        {
            $out .= __PASS;
        }

        // Finish :)
        $out .= '</p>';

        // Determine if our save is a success
        echo json_encode(
            array(
                'success' => ($errors == false),
                'warnings' => $warns,
                'html' => $out
            )
        );
    }
}