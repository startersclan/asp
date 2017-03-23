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

class Servers extends Controller
{
    /**
     * @var ServerModel
     */
    protected $ServerModel;

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
        $result = $pdo->query("SELECT * FROM `server` WHERE id=" . $id);
        $server = $result->fetch();

        // Does server exist?
        if (!$server)
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
        parent::loadModel('ServerModel', 'servers');
        $result = $this->ServerModel->queryServer($server['ip'], $server['queryport']);

        // If we get a false response, server is offline
        if (!$result)
        {
            echo json_encode(['success' => true, 'online' => false, 'message' => '']);
            die;
        }

        // Load view and start settings variables
        $view = new View('details', 'servers');
        $view->set('server', $this->ServerModel->formatRules($result['server']));
        $view->set('players1', $this->ServerModel->addPlayerRanks($pdo, $result['team1']));
        $view->set('players2', $this->ServerModel->addPlayerRanks($pdo, $result['team2']));

        // Try and get team 1's real name and flag
        $name = $result['server']['bf2_team1'];
        if ($this->ServerModel->getArmy($name, $flag))
        {
            $view->set('team1name', $name);
            $view->set('team1flag', $flag);
        }

        // Try and get team 2's real name and flag
        $name = $result['server']['bf2_team2'];
        if ($this->ServerModel->getArmy($name, $flag))
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
        // Form post?
        parent::requireAction('delete');

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode(['success' => false, 'message' => 'Unable to connect to database!']);
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
            echo json_encode(['success' => true, 'message' => $_POST['servers']]);
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode(['success' => false, 'message' => 'Query Failed! ' . $e->getMessage()]);
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
        parent::requireAction('auth', 'unauth');

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode(['success' => false, 'message' => 'Unable to connect to database!']);
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
            echo json_encode(['success' => true, 'message' => $_POST['servers']]);
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode(['success' => false, 'message' => 'Query Failed! ' . $e->getMessage()]);
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/servers/plasma
     * @output      json
     */
    public function postPlasma()
    {
        // Form post?
        parent::requireAction('plasma', 'unplasma');

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode(['success' => false, 'message' => 'Unable to connect to database!']);
            die;
        }

        $mode = ($_POST['action'] == 'plasma') ? 1 : 0;
        $count = count($_POST['servers']);

        // Prepared statement!
        try
        {
            // Transaction if more than 5 servers
            if ($count > 5)
                $pdo->beginTransaction();

            // Prepare statement
            $stmt = $pdo->prepare("UPDATE server SET plasma=$mode WHERE id=:id");
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
            echo json_encode(['success' => true, 'message' => $_POST['servers']]);
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode(['success' => false, 'message' => 'Query Failed! ' . $e->getMessage()]);
            die;
        }
    }
}

