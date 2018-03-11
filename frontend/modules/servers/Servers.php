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

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        // Fetch server list!
        $servers = $this->serverModel->fetchServers();

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

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        // Ensure correct format for ID
        $id = (int)$id;
        if ($id == 0)
        {
            Response::Redirect('servers');
            die;
        }

        // Fetch server list!
        $server = $this->serverModel->fetchServerById($id);
        if (empty($server))
        {
            Response::Redirect('servers');
            die;
        }

        // Set last seen
        $server['last_update'] = TimeHelper::FormatDifference((int)$server['lastupdate'], time());

        // Create view
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
            $this->sendJsonResponse(false, 'Invalid Action');
            return;
        }

        // Ensure correct format for ID
        $id = (int)$_POST['serverId'];
        if ($id == 0)
        {
            $this->sendJsonResponse(false, 'Invalid Server Id');
            return;
        }

        // Require database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        // Fetch server
        $server = $this->serverModel->fetchServerById($id);
        if (empty($server))
        {
            $this->sendJsonResponse(false, 'Server Not Found!');
            return;
        }

        // Query Server, if we get a false response than the server is offline
        $result = $this->serverModel->queryServer($server['ip'], $server['queryport']);
        if (empty($result))
        {
            $this->sendJsonResponse(true, '', ['online' => false]);
            return;
        }

        // Load view and start settings variables
        $view = new View('details', 'servers');
        $view->set('server', $this->serverModel->formatRules($result['server']));
        $view->set('players1', $result['team1']);
        $view->set('players2', $result['team2']);

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
        $this->sendJsonResponse(true, $view->render(false, true), [
            'online' => true,
            'image' => $result['server']['bf2_sponsorlogo_url']
        ]);
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/add
     * @output      json
     */
    public function postAdd()
    {
        // Require database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        try
        {
            // Use a dictionary here to gain an exception on missing array item
            $items = new Dictionary(false, $_POST);

            // Switch on our action base
            switch ($_POST['action'])
            {
                case 'add':
                    // Add the server via the Model
                    $id = $this->serverModel->addServer(
                        $items['serverName'],
                        $items['serverPrefix'],
                        $items['serverIp'],
                        (int)$items['serverPort'],
                        (int)$items['serverQueryPort']
                    );

                    // Add additional information for the output
                    $items->add('mode', 'add');
                    $items->add('serverId', $id);

                    // Send client response
                    $this->sendJsonResponse(true, '', $items->toArray());
                    break;
                case 'edit':
                    // Update the server via the Model
                    $this->serverModel->updateServerById(
                        (int)$items['serverId'],
                        $items['serverName'],
                        $items['serverPrefix'],
                        $items['serverIp'],
                        (int)$items['serverPort'],
                        (int)$items['serverQueryPort']
                    );

                    // Add additional information for the output
                    $items->add('mode', 'update');
                    $items->add('serverId', (int)$items['serverId']);

                    $this->sendJsonResponse(true, '', $items->toArray());
                    break;
                default:
                    $this->sendJsonResponse(false, 'Invalid Action');
                    return;
            }
        }
        catch (Exception $e)
        {
            $this->sendJsonResponse(false, 'Query Failed! '. $e->getMessage(), [
                'lastQuery' => $this->serverModel->pdo->lastQuery
            ]);
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
            // Authorize servers
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
        $this->requireAction('plasma', 'unplasma');

        // Require database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        try
        {
            // Set Plasma mode on servers
            $mode = ($_POST['action'] == 'plasma');
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