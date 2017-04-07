<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Collections\Dictionary;
use System\Controller;
use System\Database;
use System\Response;
use System\TimeHelper;
use System\View;

/**
 * Servers Module Controller
 *
 * @package Modules
 */
class Servers extends Controller
{
    /**
     * @var ServerModel
     */
    protected $serverModel;

    /**
     * @protocol    ANY
     * @request     /ASP/servers
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();

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
     * @protocol    ANY
     * @request     /ASP/servers/view/$id
     * @output      html
     *
     * @param int $id The Server ID
     */
    public function view($id)
    {
        // Require database connection
        $this->requireDatabase();

        // Ensure correct format for ID
        if ($id == 0 || !is_numeric($id))
        {
            Response::Redirect('servers');
            die;
        }

        // Grab database
        $pdo = Database::GetConnection('stats');
        $id = (int)$id;

        // Fetch server list!
        $server = $pdo->query("SELECT * FROM `server` WHERE id=" . $id)->fetch();
        if (empty($server))
        {
            Response::Redirect('servers');
            die;
        }

        // Set last seen
        $server['last_update'] = TimeHelper::FormatDifference((int)$server['lastupdate'], time());

        //var_dump($this->loadGamespyData($server['ip'], $server['queryport']));
        $view = new View('view', 'servers');
        $view->set('server', $server);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/servers/js/view.js");

        // Attach Stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/status
     * @output      json
     */
    public function postStatus()
    {
        // Form post?
        if ($_POST['action'] != 'status' || !isset($_POST['serverId']))
        {
            echo json_encode(['success' => false, 'message' => 'Invalid Action']);
            die;
        }

        // Require database connection
        $this->requireDatabase();

        // Ensure correct format for ID
        $id = (int)$_POST['serverId'];
        if ($id == 0)
        {
            echo json_encode(['success' => false, 'message' => 'Invalid Server Id']);
            die;
        }

        // Grab database
        $pdo = Database::GetConnection('stats');
        $id = (int)$id;

        // Fetch server list!
        $result = $pdo->query("SELECT * FROM `server` WHERE id=" . $id);
        $server = $result->fetch();

        // Does server exist?
        if (!$server)
        {
            echo json_encode(['success' => false, 'message' => 'Invalid Server Id']);
            die;
        }

        // Load model, and query server
        $this->loadModel('ServerModel', 'servers');
        $result = $this->serverModel->queryServer($server['ip'], $server['queryport']);

        // If we get a false response, server is offline
        if (!$result)
        {
            echo json_encode(['success' => true, 'online' => false, 'message' => '']);
            die;
        }

        // Load view and start settings variables
        $view = new View('details', 'servers');
        $view->set('server', $this->serverModel->formatRules($result['server']));
        $view->set('players1', $this->serverModel->addPlayerRanks($pdo, $result['team1']));
        $view->set('players2', $this->serverModel->addPlayerRanks($pdo, $result['team2']));

        // Try and get team 1's real name and flag
        $name = $result['server']['bf2_team1'];
        if ($this->serverModel->getArmy($name, $flag))
        {
            $view->set('team1name', $name);
            $view->set('team1flag', $flag);
        }

        // Try and get team 2's real name and flag
        $name = $result['server']['bf2_team2'];
        if ($this->serverModel->getArmy($name, $flag))
        {
            $view->set('team2name', $name);
            $view->set('team2flag', $flag);
        }

        // Send output
        echo json_encode(array(
            'success' => true,
            'online' => true,
            'message' => $view->render(false, true),
            'image' => $result['server']['bf2_sponsorlogo_url'])
        );
        die;
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
                    echo json_encode(['success' => false, 'message' => 'Invalid Action']);
                    die;
            }
        }
        catch (Exception $e)
        {
            echo json_encode([
                'success' => false,
                'message' => 'Query Failed! ' . $e->getMessage(),
                'lastQuery' => $pdo->lastQuery
            ]);
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
        // Require specific actions
        $this->requireAction('delete');

        // Require database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        try
        {
            // Delete servers
            $this->serverModel->deleteServers($_POST['servers']);
            $this->sendJsonResponse(true, $_POST['servers']);
        }
        catch (Exception $e)
        {
            // Log exception
            Asp::LogException($e);

            // Tell the client that we have failed
            $this->sendJsonResponse(false, 'Query Failed! '. $e->getMessage());
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/authorize
     * @output      json
     */
    public function postAuthorize()
    {
        // Require specific actions
        $this->requireAction('auth', 'unauth');

        // Require database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        try
        {
            // Delete servers
            $mode = ($_POST['action'] == 'auth');
            $this->serverModel->authorizeServers($mode, $_POST['servers']);
            $this->sendJsonResponse(true, $_POST['servers']);
        }
        catch (Exception $e)
        {
            // Log exception
            Asp::LogException($e);

            // Tell the client that we have failed
            $this->sendJsonResponse(false, 'Query Failed! '. $e->getMessage());
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/plasma
     * @output      json
     */
    public function postPlasma()
    {
        // Require specific actions
        $this->requireAction('auth', 'unauth');

        // Require database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        try
        {
            // Delete servers
            $mode = ($_POST['action'] == 'auth');
            $this->serverModel->plasmaServers($mode, $_POST['servers']);
            $this->sendJsonResponse(true, $_POST['servers']);
        }
        catch (Exception $e)
        {
            // Log exception
            Asp::LogException($e);

            // Tell the client that we have failed
            $this->sendJsonResponse(false, 'Query Failed! '. $e->getMessage());
        }
    }
}

