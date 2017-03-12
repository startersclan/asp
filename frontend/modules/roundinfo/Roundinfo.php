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
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\Response;
use System\Snapshot;
use System\View;

class Roundinfo extends Controller
{
    /**
     * @protocol    ANY
     * @request     /ASP/roundinfo
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();

        // Load view
        $view = new View('index', 'roundinfo');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/roundinfo/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/modules/players/css/links.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    ANY
     * @request     /ASP/roundinfo/view/$id
     * @output      html
     *
     * @param int $id The round history ID
     */
    public function view($id = 0)
    {
        // Require database connection
        $this->requireDatabase();

        // Ensure correct format for ID
        if ($id == 0 || !is_numeric($id))
        {
            Response::Redirect('roundinfo');
            die;
        }

        // Grab database
        $pdo = Database::GetConnection('stats');
        $id = (int)$id;

        // Load view
        $view = new View('view', 'roundinfo');

        // Fetch round
        $query = <<<SQL
SELECT h.*, mi.name AS `name`, s.name AS `server`, s.ip AS `ip`, s.port AS `port`
FROM round_history AS h 
  LEFT JOIN mapinfo AS mi ON h.mapid = mi.id 
  LEFT JOIN server AS s ON h.serverid = s.id
WHERE h.id={$id}
SQL;
        $round = $pdo->query($query)->fetch();
        if ($round == false)
        {
            Response::Redirect('roundinfo');
            die;
        }

        // Assign custom round values and attach to view
        $round['round_start_date'] = date('F jS, Y g:i A T', (int)$round['round_start']);
        $round['round_end_date'] = date('F jS, Y g:i A T', (int)$round['round_end']);
        $round['gamemode'] = $this->getGameModeString($round['gamemode']);
        $round['team1name'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team1'])->fetchColumn(0);
        $round['team2name'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team2'])->fetchColumn(0);
        $view->set('round', $round);

        // Set winning team name
        switch ((int)$round['winner'])
        {
            case 1:
                $view->set('winner', $round['team1name']);
                break;
            case 2:
                $view->set('winner', $round['team2name']);
                break;
            default:
                $view->set('winner', "None");
                break;
        }

        // Load players
        $players1 = [];
        $players2 = [];
        $query = <<<SQL
SELECT h.pid, h.team, h.score, h.kills, h.deaths, h.rank, h.cmdscore, h.skillscore, h.teamscore, p.name
FROM player_history AS h 
  LEFT JOIN player AS p ON h.pid = p.id
WHERE roundid={$id}
SQL;
        $result = $pdo->query($query);
        while ($row = $result->fetch())
        {
            $team = (int)$row['team'];
            if ($team == 1)
                $players1[] = $row;
            else
                $players2[] = $row;
        }

        // Attach players to view
        $view->set('players1', $players1);
        $view->set('players2', $players2);

        // Load awards
        $query = <<<SQL
SELECT pa.id AS `id`, pa.level AS `level`, a.type AS `type`, p.name AS `player_name`, a.name AS `name`, 
  h.team AS `team`, pa.roundid AS `rid`, h.rank AS `rank`
FROM player_award AS pa 
  LEFT JOIN player AS p ON pa.pid = p.id
  LEFT JOIN award AS a ON pa.id = a.id
  LEFT JOIN player_history AS h ON pa.pid = h.pid AND pa.roundid = h.roundid
WHERE pa.roundid=($id) ORDER BY pa.id
SQL;

        // Fetch the round awards
        $awards = $pdo->query($query)->fetchAll();

        // Assign Player positions
        $i = 0;
        foreach ($awards as $award)
        {
            $id = (int)$award['id'];
            if ($id == 2051907)
            {
                $awards[$i]['type'] = 3; // for medal image
                $data = ['name' => $award['player_name'], 'rank' => $award['rank'], 'team' => $award['team']];
                $view->set('first_place', $data);
            }
            else if ($id == 2051919)
            {
                $awards[$i]['type'] = 4; // for medal image
                $data = ['name' => $award['player_name'], 'rank' => $award['rank'], 'team' => $award['team']];
                $view->set('second_place', $data);
            }
            else if ($id == 2051902)
            {
                $awards[$i]['type'] = 5; // for medal image
                $data = ['name' => $award['player_name'], 'rank' => $award['rank'], 'team' => $award['team']];
                $view->set('third_place', $data);
            }
            $i++;
        }

        // Attach awards
        $view->set('awards', $awards);

        // Can we add advanced information?
        $result = $this->addAdvancedRoundInfo($round, $view);
        $view->set('advanced', $result);

        // Alert user if we could not load the snapshot
        if (!$result)
            $view->displayMessage('warning', 'Unable to locate snapshot for this round. Some round details could not be displayed.');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/roundinfo/js/view.js");

        // Attach stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol32.css");
        $view->attachStylesheet("/ASP/frontend/modules/roundinfo/css/view.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/roundinfo/list
     * @output      json
     */
    public function postList()
    {
        try
        {
            $columns = [
                ['db' => 'id', 'dt' => 'id'],
                ['db' => 'round_end', 'dt' => 'round_end',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return date('F jS, Y g:i A T', $i);
                    }
                ],
                ['db' => 'map', 'dt' => 'map'],
                ['db' => 'server', 'dt' => 'server'],
                ['db' => 'winner', 'dt' => 'winner',
                    'formatter' => function( $d, $row ) {

                        $w = $row['team'. $d];
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$w}.png\">";
                    }
                ],
                ['db' => 'team1', 'dt' => 'team1',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$d}.png\">";
                    }
                ],
                ['db' => 'team2', 'dt' => 'team2',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$d}.png\">";
                    }
                ],
                ['db' => 'tickets', 'dt' => 'tickets',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'players', 'dt' => 'players'],
                ['db' => 'id', 'dt' => 'actions',
                    'formatter' => function( $d, $row ) {
                        $id = $row['id'];
                        return '<span class="btn-group">
                            <a href="/ASP/roundinfo/view/'. $id .'"  rel="tooltip" title="View Round Details" class="btn btn-small">
                                <i class="icon-eye-open"></i>
                            </a>
                        </span>';
                    }
                ],
            ];

            $pdo = Database::GetConnection('stats');
            $data = DataTables::FetchData($_POST, $pdo, 'round_history_view', 'id', $columns);

            echo json_encode($data);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }

    private function getGameModeString($gamemode)
    {
        switch ((int)$gamemode)
        {
            default: return "Unknown";
            case 0: return "Conquest";
            case 1: return "Single Player";
            case 2: return "Coop";
        }
    }

    private function addAdvancedRoundInfo($round, View $view)
    {
        // Attempt to find shapshot files
        $time = new \DateTime("@{$round['round_end']}", new \DateTimeZone("UTC"));
        $format = $time->format('Ymd_His');
        $path = Path::Combine(SYSTEM_PATH, "snapshots", "processed");
        $files = Directory::GetFiles($path, '.*'. $round['name'] .'_'. $format .'\.json');
        $length = count($files);

        // Quit here if we have no snapshot files to load
        if ($length == 0) return false;

        // Define our needed variables
        $data = [];
        $found = false;

        // If we have just 1 file, then that is it!
        if ($length == 1)
        {
            // Load snapshot into an array
            $data = $this->loadSnapshotData($files[0]);
            if ($data == null) return false;

            $found = true;
        }
        else
        {
            /**
             * We will have to search through each snapshot that was submitted the
             * very timestamp as the round end time to determine which one matches this round ID.
             */
            $potentials = [];
            foreach ($files as $file)
            {
                // Load snapshot into an array
                $data = $this->loadSnapshotData($file);
                if ($data == null) continue;

                // compare map start times, as this could be the fastest way to determine
                if ($round['round_start'] == $data['mapStart'])
                {
                    $potentials[] = $data;
                }
            }

            // If we have one potential file, then that is the one!
            if (count($potentials) == 1)
            {
                $found = true;
                $data = $potentials[0];
            }
            else if (count($potentials) > 1)
            {
                // Try one last time to compare IP and game port
                foreach ($potentials as $rnd)
                {
                    // Validate this snapshot against the round data we have
                    if ($rnd['serverIp'] == $round['ip'] && $rnd['gamePort'] == $round['port'])
                    {
                        $found = true;
                        $data = $rnd;
                        break;
                    }
                }
            }
        }

        // If we havent found the snapshot, quit here
        if (!$found) return false;

        // Wrap in a try-catch. We should not have any issues since this snapshot
        // has been loaded before, but you never know...
        try
        {
            // Lets load our data into a snapshot object
            $data = new Dictionary(false, $data);
            $snapshot = new Snapshot($data);

            // Skill Players
            $players = [];
            foreach ($snapshot->getTopSkillPlayers() as $key => $data)
            {
                // Split on capital character so we can insert a space
                $catName = preg_split('/(?=[A-Z])/', ucfirst($key));
                $data['category'] = implode(' ', $catName);
                $players[] = $data;
            }
            $view->set('topSkillPlayers', $players);

            // Add data
            $view->set('topKitPlayers', $snapshot->getTopKitPlayers());
            $view->set('topVehiclePlayers', $snapshot->getTopVehiclePlayers());
            $view->set('commanders', $snapshot->getCommanders());
            return true;
        }
        catch (Exception $e)
        {
            return false;
        }
    }

    private function loadSnapshotData($file)
    {
        // Parse snapshot data
        $stream = File::OpenRead($file);
        $json = $stream->readToEnd();
        $data = json_decode($json, true);
        $stream->close();

        return $data;
    }
}