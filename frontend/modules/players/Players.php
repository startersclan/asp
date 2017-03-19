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
use System\DataTables;
use System\IO\File;
use System\IO\Path;
use System\Player;
use System\Response;
use System\TimeHelper;
use System\View;

class Players extends Controller
{
    /**
     * @var PlayerModel
     */
    protected $PlayerModel = null;

    /**
     * @var PlayerHistoryModel
     */
    protected $PlayerHistoryModel = null;

    /**
     * @protocol    ANY
     * @request     /ASP/players
     * @output      html
     */
    public function index()
    {
        // Require database connection
        parent::requireDatabase();

        // Load view
        $view = new View('index', 'players');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/js/select2/select2.min.js");
        $view->attachScript("/ASP/frontend/js/fileinput/fileinput.js");
        $view->attachScript("/ASP/frontend/modules/players/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/modules/players/css/links.css");
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");
        $view->attachStylesheet("/ASP/frontend/js/select2/select2.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    ANY
     * @request     /ASP/players/view/$id/$subpage/$subid
     * @output      html
     *
     * @param int $id The player ID
     * @param string $subpage
     * @param int $subid
     */
    public function view($id, $subpage = '', $subid = 0)
    {
        // Ensure correct format for ID
        $id = (int)$id;
        if ($id == 0)
        {
            Response::Redirect('players');
            die;
        }

        // Are we loading a sub page?
        if (!empty($subpage))
        {
            $this->showHistory($id, $subid);
            return;
        }

        // Require database connection
        parent::requireDatabase();
        $pdo = Database::GetConnection('stats');

        // Fetch player
        $query = <<<SQL
SELECT `name`, `rank`, `joined`, `time`, `lastonline`, `score`, `skillscore`, `cmdscore`, `teamscore`, 
  `kills`, `deaths`, `teamkills`, `kicked`, `banned`, `permban`, `heals`, `repairs`, `ammos`, `revives`,
  `captures`, `captureassists`, `defends`, `country`, `driverspecials`, `neutralizes`, `neutralizeassists`,
  `damageassists`, `rounds`, `wins`, `losses`, `cmdtime`, `sqmtime`, `sqltime`, `lwtime`, `suicides`, 
  `teamdamage`, `teamvehicledamage`, `killstreak`, `rndscore`
FROM player
WHERE `id`={$id}
SQL;
        $player = $pdo->query($query)->fetch();
        if ($player == false)
        {
            // Load view
            $view = new View('404', 'players');
            $view->set('id', $id);
            $view->render();
            return;
        }

        // Attach Model
        parent::loadModel("PlayerModel", 'players');

        // Load view
        $view = new View('view',  'players');
        $view->set('id', $id);
        $view->set('player', $this->PlayerModel->formatPlayerData($player));

        // Attach player object stats
        $this->PlayerModel->attachArmyData($id, $view, $pdo);
        $this->PlayerModel->attachKitData($id, $view, $pdo);
        $this->PlayerModel->attachVehicleData($id, $view, $pdo);
        $this->PlayerModel->attachWeaponData($id, $view, $pdo);
        $this->PlayerModel->attachAwardData($id, $view, $pdo);
        $this->PlayerModel->attachTopVictimAndOpp($id, $view, $pdo);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/js/select2/select2.min.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/players/js/view.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/js/select2/select2.css");
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");
        $view->attachStylesheet("/ASP/frontend/modules/players/css/view.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    ANY
     * @request     /ASP/players/view/$id/history/$subid
     * @output      html
     *
     * @param int $id The player ID
     * @param int $subid The round id, if any
     */
    private function showHistory($id, $subid)
    {
        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');
        $subid = (int)$subid;

        // Showing history list or specific round?
        if ($subid == 0)
        {
            // Load view
            $view = new View('history', 'players');
            $view->set('id', $id);

            // Attach needed scripts for the form
            $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
            $view->attachScript("/ASP/frontend/modules/players/js/history.js");

            // Send output
            $view->render();
        }
        else
        {
            // Attach Model
            parent::loadModel("PlayerHistoryModel", 'players');

            // Fetch round
            $query = <<<SQL
SELECT ph.*, h.*, p.name, mi.name AS `mapname`, s.name AS `server`, s.ip AS `ip`, s.port AS `port`,
  h.pids1_end + h.pids2_end AS `playerCount`
FROM player_history AS ph 
  LEFT JOIN player AS p ON ph.pid = p.id
  LEFT JOIN round_history AS h ON ph.roundid = h.id
  LEFT JOIN mapinfo AS mi ON h.mapid = mi.id 
  LEFT JOIN server AS s ON h.serverid = s.id
WHERE pid={$id} AND roundid={$subid}
SQL;
            $round = $pdo->query($query)->fetch();
            if ($round == false)
            {
                Response::Redirect('players/view/'. $id .'/history');
                die;
            }

            // Create view
            $view = new View('history_detail', 'players');

            // Assign custom round values and attach to view
            $round['teamName'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team'])->fetchColumn(0);
            $view->set('round', $this->PlayerHistoryModel->formatRoundInfo($round));

            // Add advanced info if we can
            $view->set('advanced', $this->PlayerHistoryModel->addAdvancedRoundInfo($id, $round, $view));

            // Get next round ID
            $query = "SELECT MIN(`roundid`) FROM player_history WHERE pid={$id} AND roundid > ". $subid;
            $n = (int)$pdo->query($query)->fetchColumn(0);
            $view->set('nextRoundId', $n);
            $view->set('nBtnStyle', ($n == 0) ? ' disabled="disabled"' : '');

            // Get previous round ID
            $query = "SELECT MAX(`roundid`) FROM player_history WHERE pid={$id} AND roundid < ". $subid;
            $n = (int)$pdo->query($query)->fetchColumn(0);
            $view->set('prevRoundId', $n);
            $view->set('pBtnStyle', ($n == 0) ? ' disabled="disabled"' : '');

            // Attach scripts
            $view->attachScript("/ASP/frontend/js/flot/jquery.flot.min.js");
            $view->attachScript("/ASP/frontend/js/flot/plugins/jquery.flot.tooltip.js");
            $view->attachScript("/ASP/frontend/js/flot/plugins/jquery.flot.pie.min.js");
            $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
            $view->attachScript("/ASP/frontend/modules/players/js/history_detail.js");

            // Attach stylesheets
            $view->attachStylesheet("/ASP/frontend/modules/players/css/history_detail.css");
            $view->attachStylesheet("/ASP/frontend/modules/players/css/view.css");

            // Set kill/death Ratio Chart Data
            $data = [
                ['label' => "Kills", 'data' => $round['kills'], 'color' => "#00479f"],
                ['label' => "Deaths", 'data' => $round['deaths'], 'color' => "#c75d7b"]
            ];
            $view->setJavascriptVar('killData', $data);

            // Set Time Played As chart data
            $vars = $view->getVars();
            $data = [
                ['label' => "Weapons", 'data' => $vars['weaponTotals']['time'], 'color' => "#00479f"],
                ['label' => "Vehicles", 'data' => $vars['vehicleTotals']['time'], 'color' => "#c75d7b"]
            ];
            $view->setJavascriptVar('timePlayedData', $data);

            // Send output
            $view->render();
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/authorize
     * @output      json
     */
    public function postAuthorize()
    {
        // We only accept these POST actions
        parent::requireAction("ban", "unban");

        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');
        $mode = ($_POST['action'] == 'ban') ? 1 : 0;
        $time = ($mode == 1) ? time() : 0;

        // Prepared statement!
        try
        {
            // Ensure pid exists
            if (!isset($_POST['playerId']))
                throw new Exception('No Player ID Specified!');

            // Extract player ID
            $playerId = (int)$_POST['playerId'];

            // Prepare statement
            $stmt = $pdo->prepare("UPDATE player SET permban=:mode, bantime=:time WHERE id=:id");
            $stmt->bindValue(':id', $playerId, PDO::PARAM_INT);
            $stmt->bindValue(':mode', $mode, PDO::PARAM_INT);
            $stmt->bindValue(':time', $time, PDO::PARAM_INT);
            $stmt->execute();

            // Echo success
            echo json_encode( array('success' => true, 'message' => $_POST['playerId']) );
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/delete
     * @output      json
     */
    public function postDelete()
    {
        // We only accept these POST actions
        parent::requireAction("delete", "deleteBots");

        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');

        // Prepared statement!
        try
        {
            switch ($_POST['action'])
            {
                case "delete":
                    // Ensure pid exists
                    if (!isset($_POST['playerId']))
                        throw new Exception('No Player ID Specified!');

                    // Extract player ID
                    $playerId = (int)$_POST['playerId'];

                    // Prepare statement
                    $stmt = $pdo->prepare("DELETE FROM player WHERE id=:id");
                    $stmt->bindValue(':id', $playerId, PDO::PARAM_INT);
                    $stmt->execute();

                    // Echo success
                    echo json_encode( array('success' => true, 'message' => $_POST['playerId']) );
                    break;
                case "deleteBots":
                    // Prepare statement
                    $result = $pdo->exec("DELETE FROM player WHERE password=''");

                    // Echo success
                    echo json_encode( array('success' => true, 'message' => $result) );
                    break;
            }
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/add
     * @output      json
     */
    public function postAdd()
    {
        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');

        try
        {
            // Use a dictionary here to gain an exception on missing array item
            $items = new Dictionary(false, $_POST);

            // Define server id
            $id = (isset($_POST['playerId'])) ? (int)$items['playerId'] : 0;
            $name = preg_replace("/[^". Player::NAME_REGEX ."]/", '', $items['playerName']);

            // Switch on our action base
            switch ($_POST['action'])
            {
                case 'add':
                    $pdo->insert('player', [
                        'name' => ' '. trim($name),
                        'password' => md5($items['playerPassword']),
                        'rank' => $items['playerRank'],
                        'country' => $items['playerCountry']
                    ]);

                    // Get insert ID
                    echo json_encode(['success' => true, 'mode' => 'add']);
                    break;
                case 'edit':

                    // Fetch player
                    $pass = $pdo->query("SELECT `password` FROM player WHERE id=". $id)->fetchColumn(0);
                    if (!empty($pass))
                    {
                        // Online player here
                        $name = ' '. trim($name);
                    }

                    $cols = [
                        'name' => $name,
                        'country' => $items['playerCountry'],
                        'rank' => $items['playerRank']
                    ];

                    // Add password if it is not empty
                    if (!empty($items['playerPassword']))
                        $cols['password'] = $items['playerPassword'];

                    // do update
                    $pdo->update('player', $cols, ['id' => $id]);

                    // Load model
                    parent::loadModel("PlayerModel", __CLASS__);

                    echo json_encode([
                        'success' => true,
                        'mode' => 'update',
                        'name' => $name,
                        'rank' => $items['playerRank'],
                        'iso' => $items['playerCountry'],
                        'rankName' => $this->PlayerModel->getRankName((int)$items['playerRank'])
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
     * @request     /ASP/players/list
     * @output      json
     */
    public function postList()
    {
        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');

        try
        {
            $columns = [
                ['db' => 'id', 'dt' => 'id'],
                ['db' => 'name', 'dt' => 'name'],
                ['db' => 'rank', 'dt' => 'rank',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/ranks/rank_{$d}.gif\">";
                    }
                ],
                ['db' => 'score', 'dt' => 'score',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'country', 'dt' => 'country'],
                ['db' => 'joined', 'dt' => 'joined',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return date('d M Y', $i);
                    }
                ],
                ['db' => 'lastonline', 'dt' => 'online',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return TimeHelper::FormatDifference($i, time());
                    }
                ],
                ['db' => 'clantag', 'dt' => 'clan'],
                ['db' => 'permban', 'dt' => 'permban',
                    'formatter' => function( $d, $row ) {
                        return $d == 0 ? '<font color="green">No</font>' : '<font color="red">Yes</font>';
                    }
                ],
                ['db' => 'kicked', 'dt' => 'actions',
                    'formatter' => function( $d, $row ) {
                        $id = $row['id'];
                        $banned = ($row['permban'] == 1) ? '' : ' style="display: none"';
                        $nbanned = ($row['permban'] == 0) ? '' : ' style="display: none"';

                        return '<span class="btn-group">
                            <a id="go-'. $id .'" href="/ASP/players/view/'. $id .'"  rel="tooltip" title="View Player" class="btn btn-small"><i class="icon-eye-open"></i></i></a>
                            <a id="edit-btn-'. $id .'" href="#"  rel="tooltip" title="Edit Player" class="btn btn-small"><i class="icon-pencil"></i></a>
                            <a id="ban-btn-'. $id .'" href="#" rel="tooltip" title="Ban Player" class="btn btn-small"'. $nbanned .'><i class="icon-flag"></i></a>
                            <a id="unban-btn-'. $id .'" href="#" rel="tooltip" title="Unban Player" class="btn btn-small"'.$banned.'><i class="icon-ok"></i></a>
                            <a id="delete-btn-'. $id .'" href="#" rel="tooltip" title="Delete Player" class="btn btn-small"><i class="icon-trash"></i></a>
                        </span>';
                    }
                ],
            ];

            $pdo = Database::GetConnection('stats');
            $applyFilter = ((int)$_POST['showBots']) == 0;
            $filter = ($applyFilter) ? "`password` != ''" : '';
            $data = DataTables::FetchData($_POST, $pdo, 'player', 'id', $columns, $filter);

            echo json_encode($data);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/history
     * @output      json
     */
    public function postHistory()
    {
        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');
        $id = (int)$_POST['playerId'];

        try
        {
            $columns = [
                ['db' => 'pid', 'dt' => 'id'],
                ['db' => 'roundid', 'dt' => 'rid'],
                ['db' => 'name', 'dt' => 'server'],
                ['db' => 'mapname', 'dt' => 'map'],
                ['db' => 'score', 'dt' => 'score',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'kills', 'dt' => 'kills',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'deaths', 'dt' => 'deaths',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'time', 'dt' => 'time',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return TimeHelper::SecondsToHms($i);
                    }
                ],
                ['db' => 'team', 'dt' => 'team',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$d}.png\">";
                    }
                ],
                ['db' => 'timestamp', 'dt' => 'timestamp',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return date('F jS, Y g:i A T', $i);
                    }
                ],
                ['db' => 'rank', 'dt' => 'actions',
                    'formatter' => function( $d, $row ) {
                        $id = (int)$row['pid'];
                        $rid = (int)$row['roundid'];

                        return '<span class="btn-group">
                            <a href="/ASP/players/view/'. $id .'/history/'. $rid .'"  rel="tooltip" title="View Round Details" class="btn btn-small">
                                <i class="icon-eye-open"></i>
                            </a>
                        </span>';
                    }
                ],
            ];

            // Fetch data
            $filter = "`pid` = ". $id;
            $data = DataTables::FetchData($_POST, $pdo, 'player_history_view', 'pid', $columns, $filter);

            echo json_encode($data);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/import
     * @output      json
     */
    public function postImport()
    {
        $output = Path::Combine(SYSTEM_PATH, "config", "botNames.ai");

        if (isset($_FILES["botNamesFile"]))
        {
            $file = $_FILES["botNamesFile"];

            //Filter the file types , if you want.
            if ($file["error"] > 0 && $file['error'] != UPLOAD_ERR_OK)
            {
                switch ($file['error']) {
                    case UPLOAD_ERR_NO_FILE:
                        echo json_encode(['success' => false, 'error' => "No file received."]);
                        break;
                    case UPLOAD_ERR_INI_SIZE:
                    case UPLOAD_ERR_FORM_SIZE:
                        echo json_encode(['success' => false, 'error' => "Exceeded filesize limit."]);
                        break;
                    default:
                        echo json_encode(['success' => false, 'error' => "Unknown Error."]);
                        break;
                }
            }
            else
            {
                try
                {
                    //move the uploaded file to uploads folder;
                    @move_uploaded_file($file["tmp_name"], $output);

                    // Open file
                    $lines = File::ReadAllLines($output);
                }
                catch (Exception $e)
                {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                    die;
                }

                // Prepare for adding bots
                $pattern = "/^aiSettings\.addBotName[\s\t]+(?<name>[". Player::NAME_REGEX ."]+)$/i";
                $bots = [];

                // Grab database connection
                parent::requireDatabase(true);
                $pdo = Database::GetConnection('stats');

                // Parse file lines
                foreach ($lines as $line)
                {
                    if (preg_match($pattern, $line, $match))
                    {
                        $bots[] = $match["name"];
                    }
                }

                try
                {
                    $imported = 0;

                    // wrap these inserts in a transaction, to speed things along.
                    $pdo->beginTransaction();
                    foreach ($bots as $bot)
                    {
                        try
                        {
                            // Quote name
                            $name = $pdo->quote($bot);

                            // Check if name exists already
                            $exists = $pdo->query("SELECT id FROM player WHERE name={$name} LIMIT 1")->fetchColumn(0);
                            if ($exists === false)
                            {
                                $pdo->exec("INSERT INTO `player`(`name`, `country`, `password`) VALUES ({$name}, 'US', '');");
                                $imported++;
                            }
                        }
                        catch (PDOException $e)
                        {
                            // ignore
                        }
                    }

                    // Submit changes
                    $pdo->commit();

                    echo json_encode(['success' => true, 'error' => "File Received OK. Added ". $imported . " Bots"]);
                }
                catch (Exception $e)
                {
                    $pdo->rollBack();
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
            }
        }
        else
        {
            echo json_encode(['success' => false, 'error' => "No file received."]);
        }
    }

}