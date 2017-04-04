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
use System\IO\Directory;
use System\IO\Path;
use System\View;

/**
 * Database Module Controller
 *
 * @package Modules
 */
class Database extends Controller
{
    /**
     * @var DatabaseModel
     */
    private $DatabaseModel;

    /**
     * @protocol    ANY
     * @request     /ASP/database
     * @output      html
     */
    public function index()
    {
        // Require database connection
        parent::requireDatabase();

        // Load Model
        parent::loadModel('DatabaseModel', 'database');

        // A list of tables we care about
        $tables = [
            'mapinfo', 'server', 'round_history', 'player', 'player_army', 'player_award', 'player_weapon',
            'player_kit', 'player_kill', 'player_map', 'player_history', 'player_rank_history',
            'player_vehicle', 'player_unlock'
        ];

        // Create view
        $view = new View('index', 'database');
        $view->set('tables', $this->DatabaseModel->getTableStatus($tables));
        $view->render();
    }

    /**
     * @protocol    GET
     * @request     /ASP/database/backup
     * @output      html
     */
    public function getBackup()
    {
        // Create view
        $view = new View('backup', 'database');
        $view->attachScript("/ASP/frontend/modules/database/js/backup.js");
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/database/backup
     * @output      html
     */
    public function postBackup()
    {
        // Require proper action or redirect
        parent::isAction('backup') or $this->getBackup();

        // We require a database!
        parent::requireDatabase(true);

        // Check that the backup directory is writable
        $path = Path::Combine(SYSTEM_PATH, 'backups');
        if (!Directory::IsWritable($path))
        {
            echo json_encode(['success' => false, 'message' => 'Database backup path (' . $path . ') is not writable!']);
            die;
        }

        try
        {
            // Define backup folder path
            $path = Path::Combine(SYSTEM_PATH, 'backups', date('Y-m-d_Hi'));

            // Load model, and call method
            parent::loadModel('DatabaseModel', 'database');
            $this->DatabaseModel->createStatsBackup($path);

            // Tell the client that we were successful
            echo json_encode(['success' => true, 'message' => 'System Data Backup Successful!']);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @protocol    GET
     * @request     /ASP/database/restore
     * @output      html
     */
    public function getRestore()
    {
        // Create view
        $view = new View('restore', 'database');
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/modules/database/js/restore.js");

        // Set vars
        $dirs = Directory::GetDirectories(Path::Combine(SYSTEM_PATH, 'backups'));
        for ($i = 0; $i < count($dirs); $i++)
            $dirs[$i] = Path::GetFileName($dirs[$i]);

        $view->set('backups', array_reverse($dirs));

        // Send the output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/database/restore
     * @output      html
     */
    public function postRestore()
    {
        // Require proper action or redirect
        parent::isAction('restore') or $this->getRestore();

        // We require a database!
        parent::requireDatabase(true);

        // Ensure we have a backup selected
        if (!isset($_POST['backup']))
        {
            echo json_encode(['success' => false, 'message' => 'No backup specified!']);
            return;
        }

        // Define backup folder path
        $path = Path::Combine(SYSTEM_PATH, 'backups', $_POST['backup']);

        // Ensure the backup directory is real!
        if (!Directory::Exists($path))
        {
            echo json_encode(['success' => false, 'message' => 'Invalid Backup: Does not exist!']);
            return;
        }

        try
        {
            // Load model, and call method
            parent::loadModel('DatabaseModel', 'database');
            $this->DatabaseModel->restoreStatsFromBackup($path);

            // Tell the client that we were successful
            echo json_encode(['success' => true, 'message' => 'System Data Backup Successful!']);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    /**
     * @protocol    GET
     * @request     /ASP/database/clear
     * @output      html
     */
    public function getClear()
    {
        // Create view
        $view = new View('clear', 'database');
        $view->attachScript("/ASP/frontend/modules/database/js/clear.js");
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/database/clear
     * @output      json
     */
    public function postClear()
    {
        // Require proper action or redirect
        parent::isAction('clear') or $this->getClear();

        // We require a database!
        parent::requireDatabase(true);

        try
        {
            // Load model, and call method
            parent::loadModel('DatabaseModel', 'database');
            $this->DatabaseModel->clearStatsTables();

            // Tell the client that we were successful
            echo json_encode(['success' => true, 'message' => 'Stats Data Cleared Successfully!']);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}