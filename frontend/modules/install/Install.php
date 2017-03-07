<?php
use System\Config;
use System\Database;
use System\Database\SqlFileParser;
use System\LogWriter;
use System\View;

/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
class Install
{
    /**
     * @protocol    GET
     * @request     /ASP/install/[?:index]
     * @output      html
     */
    public function getIndex()
    {
        // Convert admin hosts array to a string
        $items = implode("\n", Config::Get('admin_hosts'));

        // Create view
        $view = new View('index', 'install');
        $view->set('admin_user', Config::Get('admin_user'));
        $view->set('admin_pass', Config::Get('admin_pass'));
        $view->set('ip_list', htmlentities($items, ENT_HTML5, "UTF-8"));

        $view->set('db_host', Config::Get('db_host'));
        $view->set('db_port', Config::Get('db_port'));
        $view->set('db_user', Config::Get('db_user'));
        $view->set('db_pass', Config::Get('db_pass'));
        $view->set('db_name', Config::Get('db_name'));

        // Attach stylesheets for the wizard form
        $view->attachStylesheet("/ASP/frontend/js/wizard/wizard.css");
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");
        $view->attachStylesheet("/ASP/frontend/css/icons/icol32.css");

        // Attach scripts for the wizard form
        $view->attachScript("/ASP/frontend/js/wizard/wizard.js");
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/modules/install/js/wizard.js");

        // Render view
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/install/[?:index]
     * @output      json
     */
    public function postIndex()
    {
        // Form post?
        if (!isset($_POST['process']) || $_POST['process'] != 'config')
        {
            $this->getIndex();
            die;
        }

        foreach ($_POST as $item => $val)
        {
            $key = explode('__', $item);
            if ($key[0] == 'cfg')
            {
                // Fix array
                if ($key[1] == 'admin_hosts')
                    $val = array_map('trim', explode("\n", trim($val)));

                Config::Set($key[1], $val);
            }
        }

        // Save changes
        Config::Save();

        // Try to connect to the database with new settings
        try
        {
            $DB = Database::Connect('stats',
                array(
                    'driver' => 'mysql',
                    'host' => Config::Get('db_host'),
                    'port' => Config::Get('db_port'),
                    'database' => Config::Get('db_name'),
                    'username' => Config::Get('db_user'),
                    'password' => Config::Get('db_pass')
                )
            );
        }
        catch (Exception $e)
        {
            echo json_encode(
                array(
                    'success' => false,
                    'tablesExist' => false,
                    'message' => 'Failed to establish connection to (' . Config::Get('db_host') . '): ' . $e->getMessage()
                )
            );
            die;
        }

        // Fetch tables version
        try
        {
            $stmt = $DB->query("SELECT `version` FROM `_version`;");
            $versions = $stmt->fetchAll();
            if (!empty($versions))
            {
                echo json_encode(
                    array(
                        'success' => true,
                        'tablesExist' => true,
                        'message' => ''
                    )
                );
                die;
            }
        }
        catch (Exception $e) {}

        // Successful connection
        echo json_encode(
            array(
                'success' => true,
                'tablesExist' => false,
                'message' => ''
            )
        );
        die;
    }

    /**
     * @protocol    POST
     * @request     /ASP/install/tables
     * @output      json
     */
    public function postTables()
    {
        // Form post?
        if (!isset($_POST['process']) || $_POST['process'] != 'installdb')
        {
            $this->getIndex();
            die;
        }

        $DB = Database::GetConnection('stats');
        $current = '';

        // Fetch tables version
        try
        {
            $DB->beginTransaction();

            // Create parser
            $parser = new SqlFileParser(SYSTEM_PATH . DS . 'sql' . DS . 'schema.sql');
            $queries = $parser->getStatements();

            // Read file contents
            foreach ($queries as $query)
                $DB->exec($query);

            // Commit changes
            $DB->commit();

            // Successful connection
            echo json_encode(['success' => true, 'message' => '']);
            die;
        }
        catch (Exception $e)
        {
            if ($DB instanceof PDO)
            {
                $DB->rollBack();
            }

            $logWriter = new LogWriter(SYSTEM_PATH . DS . 'logs' . DS . 'php_errors.log');
            $logWriter->logDebug('Query Failed: ' . $current);

            // Successful connection
            echo json_encode(
                array(
                    'success' => false,
                    'message' => 'Failed to install database tables! ' . $e->getMessage()
                )
            );
            die;
        }
    }
}