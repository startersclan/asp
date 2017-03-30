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
     * Attempts to attach advanced round info from the snapshot into the view
     *
     * @param array $round The round info array from the round_history table
     * @param View $view The view to attach advanced info into
     *
     * @return bool true if the snapshot was loaded, otherwise false
     */
    public function addAdvancedRoundInfo(array $round, View $view)
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
     * @param PDO $pdo Database connection
     * @param View $view
     */
    public function attachAwards($id, PDO $pdo, View $view)
    {
        // Load awards
        $query = <<<SQL
SELECT pa.id AS `id`, pa.level AS `level`, a.type AS `type`, p.name AS `player_name`, a.name AS `name`, 
  h.team AS `team`, pa.roundid AS `rid`, h.rank AS `rank`
FROM player_award AS pa 
  LEFT JOIN player AS p ON pa.pid = p.id
  LEFT JOIN award AS a ON pa.id = a.id
  LEFT JOIN player_history AS h ON pa.pid = h.pid AND pa.roundid = h.roundid
WHERE pa.roundid = $id ORDER BY pa.id
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
    }

    /**
     * Loads the snapshot data from a snapshot file, and returns the data array
     *
     * @param string $file
     *
     * @return array
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