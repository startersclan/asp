<?php
use System\Collections\Dictionary;
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
class Servers
{
    /**
     * @protocol    ANY
     * @request     /ASP/servers
     * @output      html
     */
    public function index()
    {
        // Require database connection
        if (DB_VER == '0.0.0')
        {
            Response::Redirect('install');
            die;
        }

        // Fetch server list!
        $pdo = Database::GetConnection('stats');
        $result = $pdo->query("SELECT * FROM `server` ORDER BY id ASC");
        $servers = $result->fetchAll() or [];

        // Select counts of snapshots received by each server
        $counts = [];
        $res = $pdo->query("SELECT `serverid`, COUNT(*) AS `count` FROM `round_history` GROUP BY `serverid`")->fetchAll();
        foreach ($res as $row)
        {
            $key = (int)$row['serverid'];
            $counts[$key] = (int)$row['count'];
        }

        for ($i = 0; $i < count($servers); $i++)
        {
            $key = (int)$servers[$i]['id'];
            $servers[$i]['snapshots'] = (!isset($counts[$key])) ? 0 : $counts[$key];
        }

        // Load view
        $view = new View('index', 'servers');
        $view->set('servers', $servers);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/servers/js/serverinfo.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/add
     * @output      json
     */
    public function postAdd()
    {
        $pdo = Database::GetConnection('stats');
        try
        {
            // Use a dictionary here to gain an exception on missing array item
            $items = new Dictionary(false, $_POST);

            // Define server id
            $id = (isset($_POST['serverId'])) ? (int)$items['serverId'] : 0;

            // Switch on our action base
            switch ($_POST['action'])
            {
                case 'add':
                    $pdo->insert('server', [
                        'ip' => $items['serverIp'],
                        'prefix' => $items['serverPrefix'],
                        'name' => $items['serverName'],
                        'port' => (int)$items['serverPort'],
                        'queryport' => (int)$items['serverQueryPort'],
                    ]);

                    // Get insert ID
                    $id = $pdo->lastInsertId('id');
                    echo json_encode([
                        'success' => true,
                        'mode' => 'add',
                        'serverId' => $id,
                        'serverName' => $items['serverName'],
                        'serverPrefix' => $items['serverPrefix'],
                        'serverIp' => $items['serverIp'],
                        'serverPort' => $items['serverPort'],
                        'serverQueryPort' => $items['serverQueryPort']
                    ]);
                    break;
                case 'edit':
                    $pdo->update('server', [
                        'ip' => $items['serverIp'],
                        'prefix' => $items['serverPrefix'],
                        'name' => $items['serverName'],
                        'port' => (int)$items['serverPort'],
                        'queryport' => (int)$items['serverQueryPort']
                    ], ['id' => $id]);

                    echo json_encode([
                        'success' => true,
                        'mode' => 'update',
                        'serverId' => $id,
                        'serverName' => $items['serverName'],
                        'serverPrefix' => $items['serverPrefix'],
                        'serverIp' => $items['serverIp'],
                        'serverPort' => $items['serverPort'],
                        'serverQueryPort' => $items['serverQueryPort']
                    ]);
                    break;
                default:
                    echo json_encode(array('success' => false, 'message' => 'Invalid Action'));
                    die;
            }
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage(), 'lastQuery' => $pdo->lastQuery) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/delete
     * @output      json
     */
    public function postDelete()
    {
        // Form post?
        if ($_POST['action'] != 'delete')
        {
            echo json_encode( array('success' => false, 'message' => 'Invalid Action') );
            die;
        }

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode( array('success' => false, 'message' => 'Unable to connect to database!') );
            die;
        }

        $count = count($_POST['servers']);

        // Prepared statement!
        try
        {
            // Transaction if more than 5 servers
            if ($count > 5)
                $pdo->beginTransaction();

            // Prepare statement
            $stmt = $pdo->prepare("DELETE FROM server WHERE id=:id");
            foreach ($_POST['servers'] as $serverId)
            {
                // Ignore the all!
                if ($serverId == 'all') continue;

                // Bind value and run query
                $stmt->bindValue(':id', (int)$serverId, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Commit?
            if ($count > 5)
                $pdo->commit();

            // Echo success
            echo json_encode( array('success' => true, 'message' => $_POST['servers']) );
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/authorize
     * @output      json
     */
    public function postAuthorize()
    {
        // Form post?
        if ($_POST['action'] != 'auth' && $_POST['action'] != 'unauth')
        {
            echo json_encode( array('success' => false, 'message' => 'Invalid Action') );
            die;
        }

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode( array('success' => false, 'message' => 'Unable to connect to database!') );
            die;
        }

        $mode = ($_POST['action'] == 'auth') ? 1 : 0;
        $count = count($_POST['servers']);

        // Prepared statement!
        try
        {
            // Transaction if more than 5 servers
            if ($count > 5)
                $pdo->beginTransaction();

            // Prepare statement
            $stmt = $pdo->prepare("UPDATE server SET authorized=$mode WHERE id=:id");
            foreach ($_POST['servers'] as $serverId)
            {
                // Ignore the all!
                if ($serverId == 'all') continue;

                // Bind value and run query
                $stmt->bindValue(':id', (int)$serverId, PDO::PARAM_INT);
                $stmt->execute();
            }

            // Commit?
            if ($count > 5)
                $pdo->commit();

            // Echo success
            echo json_encode( array('success' => true, 'message' => $_POST['servers']) );
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }
}