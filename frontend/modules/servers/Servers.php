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
            echo json_encode(array('success' => false, 'message' => 'Invalid Action'));
            die;
        }

        // Require database connection
        $this->requireDatabase();

        // Ensure correct format for ID
        $id = (int)$_POST['serverId'];
        if ($id == 0)
        {
            echo json_encode(array('success' => false, 'message' => 'Invalid Server Id'));
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
            echo json_encode(array('success' => false, 'message' => 'Invalid Server Id'));
            die;
        }

        // Load server information via UDP
        $result = $this->loadGamespyData($server['ip'], $server['queryport']);
        if (!$result)
        {
            echo json_encode(array('success' => true, 'online' => false, 'message' => ''));
            die;
        }

        // Load view and start settings variables
        $view = new View('details', 'servers');
        $view->set('server', $this->formatRules($result['server']));
        $view->set('players1', $this->addPlayerRanks($pdo, $result['team1']));
        $view->set('players2', $this->addPlayerRanks($pdo, $result['team2']));

        // Try and get team 1's real name and flag
        $name = $result['server']['bf2_team1'];
        if ($this->getArmy($name, $flag))
        {
            $view->set('team1name', $name);
            $view->set('team1flag', $flag);
        }

        // Try and get team 2's real name and flag
        $name = $result['server']['bf2_team2'];
        if ($this->getArmy($name, $flag))
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
                    echo json_encode(array('success' => false, 'message' => 'Invalid Action'));
                    die;
            }
        }
        catch (Exception $e)
        {
            echo json_encode(array('success' => false, 'message' => 'Query Failed! ' . $e->getMessage(), 'lastQuery' => $pdo->lastQuery));
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
            echo json_encode(array('success' => false, 'message' => 'Invalid Action'));
            die;
        }

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode(array('success' => false, 'message' => 'Unable to connect to database!'));
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
            echo json_encode(array('success' => true, 'message' => $_POST['servers']));
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode(array('success' => false, 'message' => 'Query Failed! ' . $e->getMessage()));
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
            echo json_encode(array('success' => false, 'message' => 'Invalid Action'));
            die;
        }

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode(array('success' => false, 'message' => 'Unable to connect to database!'));
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
            echo json_encode(array('success' => true, 'message' => $_POST['servers']));
        }
        catch (Exception $e)
        {
            // Rollback?
            if ($count > 5)
                $pdo->rollBack();

            echo json_encode(array('success' => false, 'message' => 'Query Failed! ' . $e->getMessage()));
            die;
        }
    }

    protected function loadGamespyData($ip, $port)
    {
        // Setup our predefined vars
        $i = 1;
        $end = false;
        $Packet = array(1 => '', 2 => '', 3 => '');

        // Open our socket to the server, UDP port always open so we cant determine
        // the online status of our server yet!
        $sock = @fsockopen("udp://" . $ip, $port);
        @socket_set_timeout($sock, 0, 500000);

        // Query the gamespy data
        $queryString = "\xFE\xFD\x00\x10\x20\x30\x40\xFF\xFF\xFF\x01";
        @fwrite($sock, $queryString);

        // Look through and read each of the 3 packets that get returned
        while (!$end)
        {
            $bytes = @fread($sock, 1);
            $status = @socket_get_status($sock);
            $length = $status['unread_bytes'];

            if ($length > 0)
            {
                $Info[$i] = $bytes . fread($sock, $length);

                preg_match("/splitnum(...)/is", $Info[$i], $regs);
                $String = $regs[1];

                $num = ord(substr($String, 1, 1));

                if ($num == 128 || $num == 0)
                {
                    $Packet[1] = $Info[$i];
                }

                if ($num == 129 || $num == 1)
                {
                    $Packet[2] = $Info[$i];
                }

                if ($num == 130)
                {
                    $Packet[3] = $Info[$i];
                }
            }

            if ($length == 0)
            {
                $end = true;
            }

            $i++;
        }

        // Close the socket and build our packet string
        @fclose($sock);
        $Info = $Packet[1] . $Packet[2] . $Packet[3];

        // If our string is empty, return false
        if (empty($Info)) return false;

        // Parse our returned packets
        $output = str_replace("\\", "", $Info);
        $changeChr = chr(0);
        $output = str_replace($changeChr, "\\", $output);
        $rules = "x" . substr($output, 0, strpos($output, "\\\\" . chr(1)));
        $players = "\\" . substr($output, strpos($output, "\\\\" . chr(1)) + 3);

        $p3 = strpos($players, "\\\\" . chr(2));

        if (!$p3)
        {
            $p3 = strpos($players, "\\\\team_t");
        }
        if (!$p3)
        {
            $p3 = strpos($players, "\team_t");
        }

        // Parse players
        $players = substr($players, 0, $p3);
        $players = str_replace("\\ 0@splitnum\", "", $players);
        $players = str_replace("\\ 0@splitnum\\", "", $players);
        $players = str_replace(" 0@splitnum\\", "", $players);
        $players = str_replace(" 0@splitnum\\‚", "", $players);

        //Parse Rules
        $rule_temp = substr($rules, 1);
        $rule_temp = str_replace("€", "\\", $rule_temp);
        $rules_arr = explode("\\", $rule_temp);
        $rules_count = count($rules_arr);

        // Build our server data into a nice array
        $rule = [];
        for ($i = 0; $i < ($rules_count / 2); $i++)
        {
            $r1[$i] = $rules_arr[$i * 2];
            $r2[$i] = $rules_arr[($i * 2) + 1];
            $rule[$r1[$i]] = $r2[$i];
        }

        $tags = explode("\\", $players);

        $index = 0;
        $player = array();
        $currentProp = "";
        $newIndexFlag = false;
        $propCount = 0;
        $tagCount = count($tags) - 1;

        for ($i = 0; $i < $tagCount; $i++)
        {
            if ($tags[$i] == "" && substr($tags[$i + 1], strlen($tags[$i + 1]) - 1, 1) == "_" && $tags[$i + 1] != $currentProp && ord($tags[$i + 2]) == 0)
            {
                $currentProp = $tags[$i + 1];
                $index = 0;
                $prop[$propCount] = $currentProp;
                $propCount++;
            }
            else
            {

                if ($tags[$i] == $currentProp && ord($tags[$i + 1]) != 0)
                {
                    $index = ord($tags[$i + 1]);
                    $newIndexFlag = true;
                }
                else
                {
                    if ($tags[$i] != "" && $currentProp != "" && $tags[$i] != $currentProp)
                    {
                        $player[$currentProp][$index] = $tags[$i];
                        if ($newIndexFlag)
                        {
                            $player[$currentProp][$index] = substr($tags[$i], 1);
                            $newIndexFlag = false;
                        }
                        $index++;
                    }
                }
            }
        }

        // Build out player list
        $data = array();
        $count = count($player['player_']);
        for ($p = 0; $p < $count; $p++)
        {
            // Fix missing deaths bug in custom maps ??
            if (!isset($player["deaths_"][$p])) $player["deaths_"][$p] = 0;
            $data[] = array(
                'name' => $player["player_"][$p],
                'score' => (int)$player["score_"][$p],
                'kills' => (int)$player["skill_"][$p],
                'deaths' => (int)$player["deaths_"][$p],
                'ping' => (int)$player["ping_"][$p],
                'team' => (int)$player["team_"][$p],
                'pid' => (int)$player["pid_"][$p],
                'ai' => (int)$player["AIBot_"][$p]
            );
        }

        // Prepate our return array
        $return = array(
            'server' => $rule,
            'team1' => array(),
            'team2' => array()
        );

        // Sort each player by team
        foreach ($data as $player)
        {
            $return['team' . $player['team']][] = $player;
        }

        return $return;
    }

    /**
     * Converts an army name from a server response to it's full name representation.
     *
     * @param string $name [Reference Variable] Returns the army full name if we can.
     * @param int $flag [Reference Variable] Returns the flag ID for this army, or -1
     *
     * @return bool
     */
    private function getArmy(&$name, &$flag)
    {
        switch (strtolower($name))
        {
            case "mec":
                $flag = 1;
                $name = "Middle Eastern Coalition";
                return true;

            case "us":
            case "usa":
                $flag = 0;
                $name = "United States Marine Corps";
                return true;

            case "ch":
                $flag = 2;
                $name = "People's Liberation Army";
                return true;

            case "seal":
                $flag = 0;
                $name = "Seals";
                return true;

            case "sas":
                $flag = 4;
                $name = "SAS";
                return true;

            case "spetz":
                $flag = 5;
                $name = "Spetsnaz";
                return true;

            case "mecsf":
                $flag = 1;
                $name =  "Middle Eastern Coalition SF";
                return true;

            case "chinsurgent":
            case "rebels":
                $flag = 7;
                $name = "Rebels";
                return true;

            case "meinsurgent":
            case "insurgents":
                $flag = 8;
                $name = "Insurgents";
                return true;

            case "eu":
                $flag = 9;
                $name = "European Union";
                return true;

            default:
                $flag = -1;
                return false;
                break;
        }
    }

    /**
     * Formats the values of a servers response
     *
     * @param array $rules
     *
     * @return array
     */
    private function formatRules($rules)
    {
        $return = [];

        foreach ($rules as $key => $value)
        {
            switch ($key)
            {
                case 'password':
                case 'bf2_ranked':
                case 'bf2_bots':
                case 'bf2_dedicated':
                    $return[$key] = (((int)$value) == 1) ? "True" : "False";
                    break;
                case 'bf2_anticheat':
                case 'bf2_friendlyfire':
                case 'bf2_globalunlocks':
                case 'bf2_autobalanced':
                    $return[$key] = (((int)$value) == 1) ? "Enabled" : "Disabled";
                    break;
                case 'bf2_novehicles':
                    $return[$key] = (((int)$value) == 0) ? "Enabled" : "Disabled";
                    break;
                case 'roundtime':
                case 'timelimit':
                    $return[$key] = TimeHelper::SecondsToHms($value);
                    break;
                case 'bf2_teamratio':
                    $return[$key] = (int)$value;
                    break;
                default:
                    $return[$key] = $value;
                    break;
            }
        }

        return $return;
    }

    /**
     * Adds the players rank to each player from a server response
     *
     * @param PDO $pdo
     * @param array $players
     *
     * @return array
     */
    private function addPlayerRanks(PDO $pdo, $players)
    {
        $return = [];

        foreach ($players as $player)
        {
            $id = (int)$player['pid'];
            $rows = $pdo->query("SELECT name, rank FROM player WHERE id={$id} LIMIT 1");
            if ($row = $rows->fetch())
            {
                $player['rank'] = ($row['name'] == $player['name']) ? (int)$row['rank'] : 0;
            }
            else
            {
                $player['rank'] = 0;
            }

            $return[] = $player;
        }

        return $return;
    }
}

