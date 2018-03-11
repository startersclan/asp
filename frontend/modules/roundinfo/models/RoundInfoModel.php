<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Battlefield2;
use System\Collections\Dictionary;
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\Snapshot;
use System\View;

/**
 * Round Info Model
 *
 * @package Models
 * @subpackage Roundinfo
 */
class RoundInfoModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * RoundInfoModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * Fetches information about a round in a 2 dimensional array.
     *
     * @param int $id The round id
     *
     * @return array|bool the round information on success, false otherwise
     */
    public function fetchRoundInfoById($id)
    {
        // Fetch round
        $query = <<<SQL
SELECT h.*, mi.name AS `name`, s.name AS `server`, s.ip AS `ip`, s.port AS `port`
FROM round AS h 
  LEFT JOIN map AS mi ON h.map_id = mi.id 
  LEFT JOIN server AS s ON h.server_id = s.id
WHERE h.id={$id}
SQL;
        $round = $this->pdo->query($query)->fetch();
        if (empty($round))
            return false;

        // Assign custom round values and attach to view
        $round['round_start_date'] = date('F jS, Y g:i A T', (int)$round['time_start']);
        $round['round_end_date'] = date('F jS, Y g:i A T', (int)$round['time_end']);
        $round['gamemode'] = Battlefield2::GetGameModeString($round['gamemode']);
        $round['team1name'] = $this->pdo->query("SELECT `name` FROM army WHERE id=". $round['team1'])->fetchColumn(0);
        $round['team2name'] = $this->pdo->query("SELECT `name` FROM army WHERE id=". $round['team2'])->fetchColumn(0);

        // Set winning team name
        switch ((int)$round['winner'])
        {
            case 1:
                $round['winningTeamName'] = $round['team1name'];
                break;
            case 2:
                $round['winningTeamName'] = $round['team2name'];
                break;
            default:
                $round['winningTeamName'] = "None";
                break;
        }

        // Load players
        $players1 = [];
        $players2 = [];
        $query = <<<SQL
SELECT h.player_id, h.team, h.score, h.kills, h.deaths, h.rank, h.cmdscore, h.skillscore, h.teamscore, p.name
FROM player_history AS h 
  LEFT JOIN player AS p ON h.player_id = p.id
WHERE round_id={$id}
SQL;
        $result = $this->pdo->query($query);
        while ($row = $result->fetch())
        {
            $team = (int)$row['team'];
            if ($team == $round['team1'])
                $players1[] = $row;
            else
                $players2[] = $row;
        }

        return ['round' => $round, 'players1' => $players1, 'players2' => $players2];
    }

    /**
     * Attempts to attach advanced round info from the snapshot into the view
     *
     * @param array $round The round info array from the round_history table
     * @param View $view The view to attach advanced info into
     *
     * @return bool true if the snapshot was loaded, otherwise false
     *
     * @throws DirectoryNotFoundException
     * @throws FileNotFoundException
     * @throws IOException
     * @throws ObjectDisposedException
     * @throws SecurityException
     */
    public function addAdvancedRoundInfo(array $round, View $view)
    {
        // Attempt to find shapshot files
        $time = new \DateTime("@{$round['time_end']}", new \DateTimeZone("UTC"));
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
                if ($round['time_start'] == $data['mapStart'])
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

        // If we haven't found the snapshot, quit here
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

    /**
     * Attaches all the earned awards from a round to a View
     *
     * @param int $id The round ID to fetch awards from
     * @param View $view
     */
    public function attachAwards($id, View $view)
    {
        // Load awards
        $query = <<<SQL
SELECT pa.award_id AS `id`, pa.level AS `level`, a.type AS `type`, p.name AS `player_name`, a.name AS `name`, 
  h.team AS `team`, pa.round_id AS `rid`, h.rank AS `rank`
FROM player_award AS pa 
  LEFT JOIN player AS p ON pa.player_id = p.id
  LEFT JOIN award AS a ON pa.award_id = a.id
  LEFT JOIN player_history AS h ON pa.player_id = h.player_id AND pa.round_id = h.round_id
WHERE pa.round_id = $id ORDER BY pa.award_id
SQL;

        // Fetch the round awards
        $awards = $this->pdo->query($query)->fetchAll();

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
    }

    /**
     * Loads the snapshot data from a snapshot file, and returns the data array
     *
     * @param string $file
     *
     * @return array
     *
     * @throws FileNotFoundException
     * @throws IOException
     * @throws ObjectDisposedException
     */
    protected function loadSnapshotData($file)
    {
        // Parse snapshot data
        $stream = File::OpenRead($file);
        $json = $stream->readToEnd();
        $data = json_decode($json, true);
        $stream->close();

        return $data;
    }
}