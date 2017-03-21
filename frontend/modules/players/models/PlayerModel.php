<?php
use System\IO\Path;
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
class PlayerModel
{
    /**
     * @var array
     */
    public $ranks = [];

    /**
     * Appends Army data to a view
     *
     * @param int $id
     * @param View $view
     * @param PDO $pdo
     */
    public function attachArmyData($id, View $view, PDO $pdo)
    {
        // Insure $id is an integer
        $id = (int)$id;

        // Prepare return data
        $data = [];
        $totals = [
            'time' => 0,
            'wins' => 0,
            'losses' => 0,
            'ratio' => "-",
            'best' => "-"
        ];
        $averages = [
            'time' => 0,
            'wins' => 0,
            'losses' => 0,
            'ratio' => 0.00,
            'best' => 0
        ];

        // fetch player kit data
        $query = "SELECT * FROM `player_army` AS pk JOIN army ON pk.id = army.id WHERE `pid`=".$id;
        $result = $pdo->query($query);

        // iterate through the results
        while ($row = $result->fetch())
        {
            $wins = (int)$row['wins'];
            $losses = (int)$row['losses'];
            $time = (int)$row['time'];
            $best = (int)$row['best'];
            $ratio = ($losses > 0) ? round($wins / $losses, 3) : (float)$wins;

            // Add kit data
            $data[] = [
                'name' => $row['name'],
                'wins' => $wins,
                'losses' => $losses,
                'best' => $best,
                'time' => TimeHelper::SecondsToHms($time),
                'ratio' => number_format($ratio, 3)
            ];

            // Totals
            $totals['wins'] += $wins;
            $totals['losses'] += $losses;
            $totals['time'] += $time;
            $totals['best'] += $best;
            $totals['ratio'] += $ratio;
        }

        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['wins'] = ($totals['wins'] > 0)
                ? number_format( round($totals['wins'] / $length, 0) )
                : 0;
            $averages['losses'] = ($totals['losses'] > 0)
                ? number_format( round($totals['losses'] / $length, 0) )
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? number_format( round($totals['time'] / $length, 0) )
                : 0;
            $averages['best'] = ($totals['best'] > 0)
                ? number_format( round($totals['best'] / $length, 0) )
                : 0;
            $averages['ratio'] = ($totals['ratio'] > 0)
                ? round($totals['ratio'] / $length, 3)
                : "0.00";
        }

        // Finalize totals
        $totals['wins'] = number_format($totals['wins']);
        $totals['losses'] = number_format($totals['losses']);
        $totals['time'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("armyData", $data);
        $view->set("armyTotals", $totals);
        $view->set("armyAverage", $averages);
    }

    /**
     * Appends Kit data to a view
     *
     * @param int $id
     * @param View $view
     * @param PDO $pdo
     */
    public function attachKitData($id, View $view, PDO $pdo)
    {
        // Insure $id is an integer
        $id = (int)$id;

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

        // fetch player kit data
        $query = "SELECT * FROM `player_kit` AS pk JOIN kit ON pk.id = kit.id WHERE `pid`=".$id;
        $result = $pdo->query($query);

        // iterate through the results
        while ($row = $result->fetch())
        {
            $kills = (int)$row['kills'];
            $deths = (int)$row['deaths'];
            $time = (int)$row['time'];
            $ratio = ($deths > 0) ? round($kills / $deths, 3) : (float)$kills;

            // Add kit data
            $data[] = [
                'name' => $row['name'],
                'kills' => $kills,
                'deaths' => $deths,
                'time' => TimeHelper::SecondsToHms($time),
                'ratio' => number_format($ratio, 3)
            ];

            // Totals
            $totals['kills'] += $kills;
            $totals['deaths'] += $deths;
            $totals['time'] += $time;
            $totals['ratio'] += $ratio;
        }

        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['kills'] = ($totals['kills'] > 0)
                ? number_format( round($totals['kills'] / $length, 0) )
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format( round($totals['deaths'] / $length, 0) )
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? number_format( round($totals['time'] / $length, 0) )
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
     * @param int $id
     * @param View $view
     * @param PDO $pdo
     */
    public function attachVehicleData($id, View $view, PDO $pdo)
    {
        // Insure $id is an integer
        $id = (int)$id;

        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
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

        // fetch player kit data
        $query = "SELECT * FROM `player_vehicle` AS pk JOIN vehicle ON pk.id = vehicle.id WHERE `pid`=".$id;
        $result = $pdo->query($query);

        // iterate through the results
        while ($row = $result->fetch())
        {
            $kills = (int)$row['kills'];
            $deths = (int)$row['deaths'];
            $time = (int)$row['time'];
            $rKill = (int)$row['roadkills'];
            $ratio = ($deths > 0) ? round($kills / $deths, 3) : (float)$kills;

            // Add kit data
            $data[] = [
                'name' => $row['name'],
                'kills' => $kills,
                'deaths' => $deths,
                'roadKills' => $rKill,
                'time' => TimeHelper::SecondsToHms($time),
                'ratio' => number_format($ratio, 3)
            ];

            // Totals
            $totals['kills'] += $kills;
            $totals['deaths'] += $deths;
            $totals['time'] += $time;
            $totals['roadKills'] += $rKill;
            $totals['ratio'] += $ratio;
        }

        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['kills'] = ($totals['kills'] > 0)
                ? number_format( round($totals['kills'] / $length, 0) )
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format( round($totals['deaths'] / $length, 0) )
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? number_format( round($totals['time'] / $length, 0) )
                : 0;
            $averages['roadKills'] = ($totals['roadKills'] > 0)
                ? number_format( round($totals['roadKills'] / $length, 0) )
                : 0;
            $averages['ratio'] = ($totals['ratio'] > 0)
                ? round($totals['ratio'] / $length, 3)
                : "0.00";
        }

        // Finalize totals
        $totals['kills'] = number_format($totals['kills']);
        $totals['deaths'] = number_format($totals['deaths']);
        $totals['roadKills'] = number_format($totals['roadKills']);
        $totals['time'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("vehicleData", $data);
        $view->set("vehicleTotals", $totals);
        $view->set("vehicleAverage", $averages);
    }

    /**
     * Appends Kit data to a view
     *
     * @param int $id
     * @param View $view
     * @param PDO $pdo
     */
    public function attachWeaponData($id, View $view, PDO $pdo)
    {
        // Insure $id is an integer
        $id = (int)$id;

        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => "-",
            'accuracy' => 0.00,
            'fired' => 0,
            'hits' => 0
        ];
        $averages = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => 0.00,
            'accuracy' => 0.00,
            'fired' => 0,
            'hits' => 0
        ];

        // fetch player kit data
        $query = "SELECT * FROM `player_weapon_view` AS pk JOIN weapon ON pk.id = weapon.id WHERE `pid`=".$id;
        $result = $pdo->query($query);

        // iterate through the results
        while ($row = $result->fetch())
        {
            $kills = (int)$row['kills'];
            $deths = (int)$row['deaths'];
            $time = (int)$row['time'];
            $fired = (int)$row['fired'];
            $acc = ((float)$row['accuracy']) * 100;
            $ratio = ($deths > 0) ? round($kills / $deths, 3) : (float)$kills;

            // Add kit data
            $data[] = [
                'name' => $row['name'],
                'kills' => $kills,
                'deaths' => $deths,
                'fired' => $fired,
                'hits' =>(int)$row['hits'],
                'time' => TimeHelper::SecondsToHms($time),
                'ratio' => number_format($ratio, 3),
                'accuracy' => round($acc, 2)
            ];

            // Totals
            $totals['kills'] += $kills;
            $totals['deaths'] += $deths;
            $totals['time'] += $time;
            $totals['fired'] += $fired;
            $totals['hits'] += (int)$row['hits'];
            $totals['accuracy'] += $acc;
            $totals['ratio'] += $ratio;
        }

        // Do averages
        $length = count($data);
        if ($length > 0)
        {
            $averages['kills'] = ($totals['kills'] > 0)
                ? number_format( round($totals['kills'] / $length, 0) )
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format( round($totals['deaths'] / $length, 0) )
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? number_format( round($totals['time'] / $length, 0) )
                : 0;
            $averages['fired'] = ($totals['fired'] > 0)
                ? number_format( round($totals['fired'] / $length, 0) )
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
        $totals['time'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("weaponData", $data);
        $view->set("weaponTotals", $totals);
        $view->set("weaponAverage", $averages);
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
     * Formats player scores and times
     *
     * @param array $player
     *
     * @return array
     */
    public function formatPlayerData(array $player)
    {
        $data = [];
        $time = 0;
        $score = 0;
        $wins = 0;
        $losses = 0;

        foreach ($player as $key => $value)
        {
            switch ($key)
            {
                case 'time':
                    $time = (int)$value;
                    $span = TimeSpan::FromSeconds($time);
                    $data['time'] = $time;
                    $data['timeplayed'] = $span->format("%d Days, %y Hours, %j Mins");
                    break;
                case 'joined':
                    $data['joined'] = date('F jS, Y g:i A T', (int)$value);
                    break;
                case 'lastonline':
                    $data['lastonline'] = date('F jS, Y g:i A T', (int)$value);
                    break;
                case 'cmdtime':
                case 'sqmtime':
                case 'sqltime':
                case 'lwtime':
                    $data[$key] = TimeHelper::SecondsToHms((int)$value);
                    break;
                break;
                case 'kills':
                case 'deaths':
                case 'teamscore':
                case 'cmdscore':
                case 'skillscore':
                case 'heals':
                case 'revives':
                case 'ammos':
                case 'repairs':
                case 'captures':
                case 'captureassists':
                case 'neutralizes':
                case 'neutralizeassists':
                case 'defends':
                case 'driverspecials':
                case 'damageassists':
                case 'rounds':
                case 'teamdamage':
                case 'teamvehicledamage':
                case 'suicides':
                case 'killstreak':
                case 'rndscore':
                    $data[$key] = number_format((int)$value);
                    break;
                case 'wins':
                    $wins = (int)$value;
                    $data[$key] = number_format($wins);
                    break;
                case 'losses':
                    $losses = (int)$value;
                    $data[$key] = number_format($losses);
                    break;
                case 'score':
                    $score = (int)$value;
                    $data[$key] = number_format($score);
                    break;
                default:
                    $data[$key] = $value;
                    break;
            }
        }

        // Get player ratio
        $kills = (int)$player['kills'];
        $deaths = (int)$player['deaths'];
        $den = $this->getDenominator($kills, $deaths);
        if ($den == 0)
            $data['ratio'] = "0/0";
        else
            $data['ratio'] = ($kills / $den) . '/' . ($deaths / $den);
        $data['ratio2'] = ($deaths > 0) ? number_format( $kills / $deaths, 2) : $kills . ".00";
        $data['ratioColor'] = ($data['ratio2'] > 0.99) ? "green" : "red";

        // Set W/L Ratio
        $den = $this->getDenominator($wins, $losses);
        if ($den == 0)
            $data['WLRatio'] = "0/0";
        else
            $data['WLRatio'] = ($wins / $den) . '/' . ($losses / $den);
        $data['WLRatio2'] = ($losses > 0) ? number_format( $wins / $losses, 2) : $wins . ".00";
        $data['WLRatioColor'] = ($data['WLRatio2'] > 0.99) ? "green" : "red";

        // Set rank name
        $data['rankName'] = $this->getRankName((int)$player['rank']);

        // Calculate SPM
        $data['spm'] = ($time > 0) ? number_format( $score / ($time / 60), 3 ) : 0;
        return $data;
    }

    /**
     * Appends Award data to a view
     *
     * @param int $id
     * @param View $view
     * @param PDO $pdo
     */
    public function attachAwardData($id, View $view, PDO $pdo)
    {
        $medals = [];
        $badges = [];
        $ribbons = [];

        // Ensure pid is an int
        $id = (int)$id;

        // Grab all awards
        $awards = $pdo->query("SELECT * FROM award")->fetchAll();
        foreach ($awards as $award)
        {
            $aid = (int)$award['id'];
            $type = (int)$award['type'];

            $data = [
                'id' => $aid,
                'name' => $award['name'],
                'type' => $type,
                'level' => 0,
                'first' => "Never",
                'last' => "Never"
            ];

            switch ($type)
            {
                case 0:
                    $ribbons[$aid] = $data;
                    break;
                case 1:
                    $badges[$aid] = $data;
                    break;
                case 2:
                    $medals[$aid] = $data;
                    break;
            }
        }

        // Now fetch player awards
        $query = "SELECT * FROM player_awards_view AS v JOIN award AS a ON a.id = v.id WHERE pid=".$id;
        $awards = $pdo->query($query)->fetchAll();
        foreach ($awards as $award)
        {
            $id = (int)$award['id'];
            $type = (int)$award['type'];

            $data = [
                'level' => (int)$award['level'],
                'first' => date('F jS, Y g:i A T', (int)$award['first']),
                'last' => date('F jS, Y g:i A T', (int)$award['earned'])
            ];

            switch ($type)
            {
                case 0:
                    $ribbons[$id]['level'] = $data['level'];
                    $ribbons[$id]['first'] = $data['first'];
                    $ribbons[$id]['last'] = $data['last'];
                    break;
                case 1:
                    $badges[$id]['prefix'] = $this->getBadgePrefix((int)$data['level']);
                    $badges[$id]['level'] = $data['level'];
                    $badges[$id]['first'] = $data['first'];
                    $badges[$id]['last'] = $data['last'];
                    break;
                case 2:
                    $medals[$id]['level'] = $data['level'];
                    $medals[$id]['first'] = $data['first'];
                    $medals[$id]['last'] = $data['last'];
                    break;
            }
        }

        $view->set('medals', $medals);
        $view->set('badges', $badges);
        $view->set('ribbons', $ribbons);
    }

    /**
     * Adds the favorite victim and opponent data to the current output
     *
     * @param int $id
     * @param View $view
     * @param PDO $pdo
     */
    public function attachTopVictimAndOpp($id, View $view, PDO $pdo)
    {
        // Fetch Fav Victim
        $result = $pdo->query("SELECT victim, `count` FROM player_kill WHERE attacker={$id} ORDER BY `count` DESC LIMIT 1");
        if ($row = $result->fetch())
        {
            $victim = $row['victim'];
            $count = $row['count'];
            $result = $pdo->query("SELECT name, rank FROM player WHERE id={$victim}");
            if ($row = $result->fetch())
            {
                $data = [
                    'id' => $victim,
                    'name' => $row['name'],
                    'rank' => $row['rank'],
                    'count' => $count
                ];
                $view->set('favVictim', $data);
            }
        }
        else
        {
            $data = [
                'id' => $id . "/#",
                'name' => "N/A",
                'rank' => 0,
                'count' => 0
            ];
            $view->set('favVictim', $data);
        }

        // Fetch Fav Opponent
        $result = $pdo->query("SELECT attacker, `count` FROM player_kill WHERE victim={$id} ORDER BY `count` DESC LIMIT 1");
        if ($row = $result->fetch())
        {
            $attacker = $row['attacker'];
            $count = $row['count'];
            $result = $pdo->query("SELECT name, rank FROM player WHERE id={$attacker}");
            if ($row = $result->fetch())
            {
                $data = [
                    'id' => $attacker,
                    'name' => $row['name'],
                    'rank' => $row['rank'],
                    'count' => $count
                ];
                $view->set('worstOp', $data);
            }
        }
        else
        {
            $data = [
                'id' => $id . "/#",
                'name' => "N/A",
                'rank' => 0,
                'count' => 0
            ];
            $view->set('worstOp', $data);
        }
    }

    protected function getBadgePrefix($level)
    {
        if ($level == 3) return 'Gold';
        else if ($level == 2) return 'Silver';
        else return 'Bronze';
    }
}