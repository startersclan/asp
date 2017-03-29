<?php
use System\Collections\Dictionary;
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\Player;
use System\Response;
use System\Snapshot;
use System\StatsData;
use System\TimeHelper;
use System\TimeSpan;
use System\View;

/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
class PlayerHistoryModel
{
    /**
     * @var array
     */
    public $ranks = [];

    protected function getBadgePrefix($level)
    {
        if ($level == 3) return 'Gold';
        else if ($level == 2) return 'Silver';
        else return 'Bronze';
    }

    public function getGameModeString($gamemode)
    {
        switch ((int)$gamemode)
        {
            default: return "Unknown";
            case 0: return "Conquest";
            case 1: return "Single Player";
            case 2: return "Coop";
        }
    }

    /**
     * Fetches the name of a rank by ID
     *
     * @param int $rank
     *
     * @return string
     */
    public function getRankName($rank)
    {
        if (empty($this->ranks))
        {
            /** @noinspection PhpIncludeInspection */
            $this->ranks = include Path::Combine(SYSTEM_PATH, 'config', 'ranks.php');
        }
        return ($rank > count($this->ranks)) ? "Unknown ({$rank})" : $this->ranks[$rank]['title'];
    }

    public function getDenominator($x, $y)
    {
        while ($y != 0)
        {
            $remainder = $x % $y;
            $x = $y;
            $y = $remainder;
        }

        return abs($x);
    }

    /**
     * Formats the values coming from the database to display properly in the view
     *
     * @param array $round
     *
     * @return array
     */
    public function formatRoundInfo(array $round)
    {
        $data = [];
        $time = 0;
        $score = 0;

        foreach ($round as $key => $value)
        {
            switch ($key)
            {
                case 'mapname':
                    $data[$key] = $value;
                    $data['lc_mapname'] = strtolower($value);
                    break;
                case 'time':
                    $time = (int)$value;
                    $span = TimeSpan::FromSeconds($time);
                    $data['time'] = $time;
                    $data['timePlayed'] = $span->format("%j minutes, %w seconds");
                    break;
                case 'kills':
                case 'deaths':
                case 'teamscore':
                case 'cmdscore':
                case 'skillscore':
                    $data[$key] = number_format((int)$value);
                    break;
                case 'score':
                    $score = (int)$value;
                    $data[$key] = number_format($score);
                    break;
                case 'gamemode':
                    $data[$key] = $this->getGameModeString($value);
                    break;
                default:
                    $data[$key] = $value;
                    break;
            }
        }

        // Get player ratio
        $kills = (int)$round['kills'];
        $deaths = (int)$round['deaths'];
        $den = $this->getDenominator($kills, $deaths);
        if ($den == 0)
            $data['ratio'] = "0/0";
        else
            $data['ratio'] = ($kills / $den) . '/' . ($deaths / $den);
        $data['ratio2'] = ($deaths > 0) ? number_format( $kills / $deaths, 2) : $kills . ".00";
        $data['ratioColor'] = ($data['ratio2'] > 0.99) ? "green" : "red";

        // Set date formats
        $data['round_start_date'] = date('F jS, Y g:i A T', (int)$round['round_start']);
        $data['round_end_date'] = date('F jS, Y g:i A T', (int)$round['round_end']);

        // Set rank name
        $data['rankName'] = $this->getRankName((int)$round['rank']);

        // Set round time
        $span = TimeSpan::FromSeconds((int)$round['round_end'] - (int)$round['round_start']);
        $data['roundTime'] = $span->format("%j minutes, %w seconds");

        // Calculate SPM
        $data['spm'] = ($time > 0) ? number_format($score / ($time / 60), 3 ) : 0;
        return $data;
    }

    /**
     * Adds advanced round statistics if the snapshot if found and able to
     * be loaded properly.
     *
     * @param $pid
     * @param $round
     * @param View $view
     *
     * @return bool
     */
    public function addAdvancedRoundInfo($pid, $round, View $view)
    {
        // Attempt to find shapshot files
        $time = new \DateTime("@{$round['round_end']}", new \DateTimeZone("UTC"));
        $format = $time->format('Ymd_His');
        $path = Path::Combine(SYSTEM_PATH, "snapshots", "processed");
        $files = Directory::GetFiles($path, '.*'. $round['mapname'] .'_'. $format .'\.json');
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

            // Grab player, and ensure he is in the snapshot
            $player = $snapshot->getPlayerById($pid);
            if (!$player)
            {
                Response::Redirect('players/view/'. $pid .'/history');
                die;
            }

            // Load stats data
            StatsData::Load();

            // Add player team stats
            $view->set('completed', ($player->completedRound) ? "Yes" : "No");
            $view->set('kicked', $player->timesKicked);
            $view->set('banned', $player->timesBanned);
            $view->set('heals', $player->heals);
            $view->set('revives', $player->revives);
            $view->set('ammos', $player->resupplies);
            $view->set('repairs', $player->repairs);
            $view->set('captures', $player->flagCaptures);
            $view->set('captureassists', $player->flagCaptureAssists);
            $view->set('neutralizes', $player->flagNeutralizes);
            $view->set('neutralizeassists', $player->flagNeutralizeAssists);
            $view->set('defends', $player->flagDefends);
            $view->set('driverspecials', $player->driverSpecials);
            $view->set('damageassists', $player->damageAssists);
            $view->set('targetassists', $player->targetAssists);

            // Add player times
            $view->set('cmdtime', TimeHelper::SecondsToHms($player->cmdTime));
            $view->set('sqltime', TimeHelper::SecondsToHms($player->sqlTime));
            $view->set('sqmtime', TimeHelper::SecondsToHms($player->sqmTime));
            $view->set('lwtime', TimeHelper::SecondsToHms($player->lwTime));

            // Negative Stats
            $view->set('teamkills', $player->teamKills);
            $view->set('teamdamage', $player->teamDamage);
            $view->set('teamvehicledamage', $player->teamVehicleDamage);
            $view->set('suicides', $player->suicides);

            // Misc stats
            $view->set('killstreak', $player->killStreak);
            $view->set('deathstreak', $player->deathStreak);
            $this->attachTopVictimAndOpp($player, $view, $snapshot);

            // Attach players round stats
            $this->attachKitData($player, $view);
            $this->attachVehicleData($player, $view);
            $this->attachWeaponData($player, $view);
            $this->attachAwardData($player, $view);

            // Return
            return true;
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            return false;
        }
    }

    /**
     * Appends Kit data to a view
     *
     * @param Player $player
     * @param View $view
     */
    public function attachKitData(Player $player, View $view)
    {
        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => "-"
        ];
        $averages = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => 0.00
        ];

        // iterate through the results
        foreach ($player->kitData as $obj)
        {
            // Skip unknown objects
            if ($obj->id >= StatsData::$NumKits) continue;

            // Get K/D ratio
            $ratio = ($obj->deaths > 0) ? round($obj->kills / $obj->deaths, 3) : (float)$obj->kills;

            // Add kit data
            $data[] = [
                'name' => StatsData::$KitNames[$obj->id],
                'kills' => $obj->kills,
                'deaths' => $obj->deaths,
                'time' => TimeHelper::SecondsToHms($obj->time),
                'ratio' => number_format($ratio, 3)
            ];

            // Totals
            $totals['kills'] += $obj->kills;
            $totals['deaths'] += $obj->deaths;
            $totals['time'] += $obj->time;
            $totals['ratio'] += $ratio;
        }

        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['kills'] = ($totals['kills'] > 0)
                ? number_format($totals['kills'] / $length, 0)
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format($totals['deaths'] / $length, 0)
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? (int)round($totals['time'] / $length, 0)
                : 0;
            $averages['ratio'] = ($totals['ratio'] > 0)
                ? round($totals['ratio'] / $length, 3)
                : "0.00";
        }

        // Finalize totals
        $totals['kills'] = number_format($totals['kills']);
        $totals['deaths'] = number_format($totals['deaths']);
        $totals['time'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("kitData", $data);
        $view->set("kitTotals", $totals);
        $view->set("kitAverage", $averages);
    }

    /**
     * Appends Vehicle data to a view
     *
     * @param Player $player
     * @param View $view
     */
    public function attachVehicleData(Player $player, View $view)
    {
        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'timePlayed' => "00:00:00",
            'roadKills' => 0,
            'ratio' => "-"
        ];
        $averages = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'roadKills' => 0,
            'ratio' => 0.00
        ];

        // iterate through the results
        foreach ($player->vehicleData as $obj)
        {
            // Skip unknown objects
            if ($obj->id >= StatsData::$NumVehicles) continue;

            // Get K/D ratio
            $ratio = ($obj->deaths > 0) ? round($obj->kills / $obj->deaths, 3) : (float)$obj->kills;

            // Add vehicle data
            $data[] = [
                'name' => StatsData::$VehicleNames[$obj->id],
                'kills' => $obj->kills,
                'deaths' => $obj->deaths,
                'roadKills' => $obj->roadKills,
                'time' => TimeHelper::SecondsToHms($obj->time),
                'ratio' => number_format($ratio, 3)
            ];

            // Totals
            $totals['kills'] += $obj->kills;
            $totals['deaths'] += $obj->deaths;
            $totals['time'] += $obj->time;
            $totals['roadKills'] += $obj->roadKills;
            $totals['ratio'] += $ratio;
        }


        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['kills'] = ($totals['kills'] > 0)
                ? number_format($totals['kills'] / $length, 0)
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format($totals['deaths'] / $length, 0)
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? (int)round($totals['time'] / $length, 0)
                : 0;
            $averages['roadKills'] = ($totals['roadKills'] > 0)
                ? number_format($totals['roadKills'] / $length, 0)
                : 0;
            $averages['ratio'] = ($totals['ratio'] > 0)
                ? round($totals['ratio'] / $length, 3)
                : "0.00";
        }

        // Finalize totals
        $totals['kills'] = number_format($totals['kills']);
        $totals['deaths'] = number_format($totals['deaths']);
        $totals['roadKills'] = number_format($totals['roadKills']);
        $totals['timePlayed'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("vehicleData", $data);
        $view->set("vehicleTotals", $totals);
        $view->set("vehicleAverage", $averages);
    }

    /**
     * Appends Kit data to a view
     *
     * @param Player $player
     * @param View $view
     */
    public function attachWeaponData(Player $player, View $view)
    {
        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'timePlayed' => "00:00:00",
            'ratio' => "-",
            'accuracy' => 0.00,
            'fired' => 0,
            'hits' => 0
        ];
        $averages = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'timePlayed' => "00:00:00",
            'ratio' => 0.00,
            'accuracy' => 0.00,
            'fired' => 0,
            'hits' => 0
        ];

        // iterate through the results
        foreach ($player->weaponData as $obj)
        {
            // Skip unknown objects
            if ($obj->id >= StatsData::$NumWeapons) continue;

            // Get accuracy and K/D ratio
            $acc = ($obj->fired > 0) ? (($obj->hits / $obj->fired) * 100) : 0;
            $ratio = ($obj->deaths > 0) ? round($obj->kills / $obj->deaths, 3) : (float)$obj->kills;

            // Add kit data
            $data[] = [
                'name' => StatsData::$WeaponNames[$obj->id],
                'kills' => $obj->kills,
                'deaths' => $obj->deaths,
                'hits' => $obj->hits,
                'fired' => $obj->fired,
                'time' => TimeHelper::SecondsToHms($obj->time),
                'ratio' => number_format($ratio, 3),
                'accuracy' => round($acc, 2)
            ];

            // Totals
            $totals['kills'] += $obj->kills;
            $totals['deaths'] += $obj->deaths;
            $totals['time'] += $obj->time;
            $totals['fired'] += $obj->fired;
            $totals['hits'] += $obj->hits;
            $totals['ratio'] += $ratio;
        }

        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['kills'] = ($totals['kills'] > 0)
                ? number_format($totals['kills'] / $length, 0)
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format($totals['deaths'] / $length, 0)
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? (int)round($totals['time'] / $length, 0)
                : 0;
            $averages['fired'] = ($totals['fired'] > 0)
                ? number_format($totals['fired'] / $length, 0)
                : 0;
            $averages['hits'] = ($totals['hits'] > 0)
                ? number_format($totals['hits'] / $length, 0)
                : 0;
            $averages['ratio'] = ($totals['ratio'] > 0)
                ? round($totals['ratio'] / $length, 3)
                : "0.00";
            $averages['accuracy'] = ($totals['fired'] > 0)
                ? round((($totals['hits'] / $totals['fired']) * 100), 2)
                : "0.00";
        }

        // Finalize totals
        $totals['kills'] = number_format($totals['kills']);
        $totals['deaths'] = number_format($totals['deaths']);
        $totals['deaths'] = number_format($totals['fired']);
        $totals['timePlayed'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("weaponData", $data);
        $view->set("weaponTotals", $totals);
        $view->set("weaponAverage", $averages);
    }

    /**
     * Adds the favorite victim and opponent data to the current output
     *
     * @param Player $player
     * @param View $view
     * @param Snapshot $snapshot
     */
    public function attachTopVictimAndOpp(Player $player, View $view, Snapshot $snapshot)
    {
        $data = [
            'id' => 0,
            'name' => "N/A",
            'rank' => 0,
            'count' => 0
        ];

        foreach ($player->victims as $pid => $count)
        {
            if ($count > $data['count'])
            {
                $victim = $snapshot->getPlayerById($pid);
                $data['id'] = $pid;
                $data['name'] = $victim->name;
                $data['rank'] = $victim->rank;
                $data['count'] = $count;
            }
        }

        // Update view
        $view->set('favVictim', $data);

        // Now fetch worst enemy
        $data = [
            'id' => 0,
            'name' => "None",
            'rank' => 0,
            'count' => 0
        ];

        foreach ($snapshot->players as $p)
        {
            // Skip our player
            if ($player->pid == $p->pid)
                continue;

            foreach ($p->victims as $pid => $count)
            {
                if ($pid == $player->pid)
                {
                    if ($count > $data['count'])
                    {
                        $data['id'] = $p->pid;
                        $data['name'] = $p->name;
                        $data['rank'] = $p->rank;
                        $data['count'] = $count;
                    }
                }
            }
        }

        // Update view
        $view->set('worstOp', $data);
    }

    /**
     * Appends Award data to a view
     *
     * @param Player $player
     * @param View $view
     */
    public function attachAwardData(Player $player, View $view)
    {
        $badges = [];
        $medals = [];
        $ribbons = [];

        foreach ($player->earnedAwards as $id => $level)
        {
            $sid = (string)$id;
            switch ((int)$sid[0])
            {
                case 1:
                    $badges[] = ['id' => $id, 'prefix' => $this->getBadgePrefix($level), 'level' => $level];
                    break;
                case 2:
                    $medals[] = ['id' => $id, 'level' => $level];
                    break;
                case 3:
                    $ribbons[$id] = ['id' => $id, 'level' => $level];
                    break;
            }
        }

        $view->set('medals', $medals);
        $view->set('badges', $badges);
        $view->set('ribbons', $ribbons);
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