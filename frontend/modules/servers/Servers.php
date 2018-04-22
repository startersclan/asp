<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
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
     * @var ServerAjaxModel
     */
    protected $ajaxModel = null;

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

        // Attach tags
        for ($i = 0; $i < count($servers); $i++)
        {
            $servers[$i]['auth_badge'] = ($servers[$i]['authorized']) ? 'success' : 'important';
            $servers[$i]['auth_text'] = ($servers[$i]['authorized']) ? 'Yes' : 'No';
            $servers[$i]['plasma_badge'] = ($servers[$i]['plasma']) ? 'success' : 'inactive';
            $servers[$i]['plasma_text'] = ($servers[$i]['plasma']) ? 'Yes' : 'No';
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
        $server['auth_badge'] = ($server['authorized']) ? 'success' : 'important';
        $server['auth_text'] = ($server['authorized']) ? 'Authorized' : 'Unauthorized';
        $server['plasma_badge'] = ($server['plasma']) ? 'success' : 'inactive';
        $server['plasma_text'] = ($server['plasma']) ? 'Yes' : 'No';

        // Create view
        $view = new View('view', 'servers');
        $view->set('server', $server);

        // Fetch Auth Token authorized addresses
        $addresses = $this->serverModel->fetchAuthorizedServerIpsById($id);
        $view->set('addresses', $addresses);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate.js");
        $view->attachScript("/ASP/frontend/js/flot/jquery.flot.min.js");
        $view->attachScript("/ASP/frontend/js/flot/plugins/jquery.flot.tooltip.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/js/bootstrap/bootstrap-tagsinput.js");
        $view->attachScript("/ASP/frontend/modules/servers/js/view.js");

        // Attach Stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");
        $view->attachStylesheet("/ASP/frontend/js/bootstrap/bootstrap-tagsinput.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    GET
     * @request     /ASP/servers/history/$id
     * @output      HTML
     *
     * @param int $id The server ID
     */
    public function getHistory($id = 0)
    {
        // Ensure correct format for ID
        $id = (int)$id;
        if ($id == 0)
        {
            Response::Redirect('servers');
            die;
        }

        // Require database
        $this->requireDatabase();

        // Load view
        $view = new View('history', 'servers');
        $view->set('id', $id);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/servers/js/history.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/modules/players/css/links.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/history
     * @output      json
     */
    public function postHistory($id = 0)
    {
        // Make sure we aren't logging into this page
        if ($_POST['action'] !== 'list')
        {
            $this->getHistory($id);
            return;
        }

        // Require a database connection
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerAjaxModel', 'servers', 'ajaxModel');

        // Get server id
        $serverId = (int)$_POST['serverId'];

        try
        {
            $data = $this->ajaxModel->getRoundList($serverId, $_POST);
            echo json_encode($data);
        }
        catch (Exception $e)
        {
            // Log Exception
            System::LogException($e);

            // Send error message to client
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/token
     * @output      json
     */
    public function postToken()
    {
        // Ensure correct action
        $this->requireAction('address', 'newId', 'newToken');

        // Require the database
        $this->requireDatabase(true);

        // Attach Model
        $this->loadModel('ServerModel', 'servers');

        // Sanitize id
        $id = (int)$_POST['serverId'];

        try
        {
            // Process based on action
            switch ($_POST['action'])
            {
                case 'address':
                    // Sync list with database
                    $this->serverModel->syncAuthorizedServerIpsById($id, $_POST['addresses']);

                    // Send success
                    $this->sendJsonResponse(true, 'Successfully sync\'d list');
                    break;
                case 'newId':
                    $newId = $this->serverModel->generateNewAuthIdForServer($id);
                    if ($newId === false)
                        $this->sendJsonResponse(false, 'Unable to generate new AuthId for server');
                    else
                        $this->sendJsonResponse(true, $newId);

                    break;
                case 'newToken':
                    $newId = $this->serverModel->generateNewAuthTokenForServer($id);
                    if (empty($newId))
                        $this->sendJsonResponse(false, 'Unable to generate new AuthToken for server');
                    else
                        $this->sendJsonResponse(true, $newId);
                    break;
            }
        }
        catch (Exception $e)
        {
            System::LogException($e);

            // Send success
            $this->sendJsonResponse(false, $e);
        }
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
                        $items['serverIp'],
                        (int)$items['serverPort'],
                        (int)$items['serverQueryPort'],
                        true,
                        $authID,
                        $authToken
                    );

                    // Add additional information for the output
                    $items->add('mode', 'add');
                    $items->add('serverId', $id);
                    $items->add('authId', $authID);
                    $items->add('authToken', $authToken);

                    // Send client response
                    $this->sendJsonResponse(true, '', $items->toArray());
                    break;
                case 'edit':
                    // Update the server via the Model
                    $this->serverModel->updateServerById(
                        (int)$items['serverId'],
                        $items['serverName'],
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
            System::LogException($e);

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
            System::LogException($e);

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
            System::LogException($e);

            // Tell the client that we have failed
            $this->sendJsonResponse(false, 'Query Failed! '. $e->getMessage());
        }
    }

    /**
     * @protocol    GET
     * @request     /ASP/servers/chartData/{id}
     * @output      json
     *
     * @param int $id The server ID to fetch chart data for
     *
     * @throws Exception
     */
    public function getChartData($id = 0)
    {
        // Hide errors. Specifically, Daylight Savings errors
        ini_set("display_errors", "0");

        // Require database connection
        $this->requireDatabase(true);

        // Load model
        $this->loadModel('ServerModel', 'servers');

        // Use our model to do all the hard work
        $data = $this->serverModel->getServerChartData($id);
        echo json_encode($data, JSON_PRETTY_PRINT, 10);
    }
}