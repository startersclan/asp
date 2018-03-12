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
use System\Database\UpdateOrInsertQuery;
use System\IO\File;
use System\Player;
use System\TimeHelper;
use System\TimeSpan;
use System\View;

/**
 * Player Model
 *
 * @package Models
 * @subpackage Players
 */
class PlayerModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * PlayerModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * Creates a new player record in the player table
     *
     * @param string $name The player unique nick
     * @param string $password The player password, or null if creating an offline account
     * @param string $email The player email address
     * @param string $iso The player country ISO code.
     * @param int $rank The starting player rank
     *
     * @return bool true on success, otherwise false
     *
     * @throws ArgumentException thrown if any of the parameters are invalid.
     */
    public function createPlayer($name, $password, $email, $iso, $rank = 0)
    {
        // Check for valid ISO
        $iso = trim($iso);
        if (strlen($iso) != 2)
            throw new ArgumentException('Invalid country ISO passed: '. $iso, 'iso');

        // Check length of player name
        $name = preg_replace("/[^". Player::NAME_REGEX ."]/", '', trim($name));
        if (empty($name))
            throw new ArgumentException('Empty player name passed', 'name');
        else if (strlen($name) > 32)
            throw new ArgumentException('Player name cannot be longer than 32 characters!', 'name');

        // Prepare statement

        return $this->pdo->insert('player', [
            'name' => $name,
            'password' => md5(trim($password)),
            'rank' => (int)$rank,
            'email' => trim($email),
            'country' => $iso
        ]);
    }

    /**
     * Updates the specified player fields in the player table. Data is not filtered.
     *
     * @param int $id The player id
     * @param array $cols An array of [name => value] to set for the player
     *
     * @return bool true on success, otherwise false
     */
    public function updatePlayer($id, $cols)
    {
        return $this->pdo->update('player', $cols, ['id' => $id]);
    }

    /**
     * Deletes a player record from the player table, and all associated
     * records from the other stats tables
     *
     * @param int $id The player id
     *
     * @return int The number of rows affected by the last SQL statement
     */
    public function deletePlayer($id)
    {
        // Prepare statement
        $stmt = $this->pdo->prepare("DELETE FROM player WHERE id=:id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Deletes all bot records from the player table, and all associated
     * records from the other stats tables
     *
     * @return int The number of rows affected by the last SQL statement
     */
    public function deleteBotPlayers()
    {
        // Prepare statement
        return $this->pdo->exec("DELETE FROM player WHERE password=''");
    }

    /**
     * Fetches player data for the Players controller
     *
     * @param int $id the player ID
     *
     * @return bool|array
     */
    public function fetchPlayer($id)
    {
        // Fetch player
        $query = <<<SQL
SELECT `name`, `rank`, `email`, `joined`, `time`, `lastonline`, `score`, `skillscore`, `cmdscore`, `teamscore`, 
  `kills`, `deaths`, `teamkills`, `kicked`, `banned`, `permban`, `heals`, `repairs`, `ammos`, `revives`,
  `captures`, `captureassists`, `defends`, `country`, `driverspecials`, `neutralizes`, `neutralizeassists`,
  `damageassists`, `rounds`, `wins`, `losses`, `cmdtime`, `sqmtime`, `sqltime`, `lwtime`, `suicides`, 
  `teamdamage`, `teamvehicledamage`, `killstreak`, `rndscore`
FROM player
WHERE `id`={$id}
SQL;
        return $this->pdo->query($query)->fetch();
    }

    /**
     * Resets a players stats, and removes all awards, unlocks, and rank histories
     *
     * @param int $id The player ID
     *
     * @throws Exception
     */
    public function resetPlayerStats($id)
    {
        // Cast id to an integer, to be safe
        $id = (int)$id;

        try
        {
            // Start transaction
            $this->pdo->beginTransaction();

            // Delete all records from the following tables for this player
            $tables = [
                'player_kit', 'player_army', 'player_award', 'player_rank_history',
                'player_map', 'player_vehicle', 'player_weapon', 'player_unlock'
            ];
            foreach ($tables as $table)
            {
                $this->pdo->exec("DELETE FROM {$table} WHERE pid={$id}");
            }

            // Reset all player stats
            $query = new UpdateOrInsertQuery($this->pdo, 'player');
            $query->set('time', '=', 0);
            $query->set('rounds', '=', 0);
            $query->set('score', '=', 0);
            $query->set('cmdscore', '=', 0);
            $query->set('skillscore', '=', 0);
            $query->set('teamscore', '=', 0);
            $query->set('kills', '=', 0);
            $query->set('wins', '=', 0);
            $query->set('losses', '=', 0);
            $query->set('deaths', '=', 0);
            $query->set('captures', '=', 0);
            $query->set('captureassists', '=', 0);
            $query->set('neutralizes', '=', 0);
            $query->set('neutralizeassists', '=', 0);
            $query->set('defends', '=', 0);
            $query->set('damageassists', '=', 0);
            $query->set('heals', '=', 0);
            $query->set('revives', '=', 0);
            $query->set('ammos', '=', 0);
            $query->set('repairs', '=', 0);
            $query->set('targetassists', '=', 0);
            $query->set('driverspecials', '=', 0);
            $query->set('teamkills', '=', 0);
            $query->set('teamdamage', '=', 0);
            $query->set('teamvehicledamage', '=', 0);
            $query->set('suicides', '=', 0);
            $query->set('rank', '=', 0);
            $query->set('cmdtime', '=', 0);
            $query->set('sqltime', '=', 0);
            $query->set('sqmtime', '=', 0);
            $query->set('lwtime', '=', 0);
            $query->set('timepara', '=', 0);
            $query->set('mode0', '=', 0);
            $query->set('mode1', '=', 0);
            $query->set('mode2', '=', 0);
            $query->set('rndscore', '=', 0);
            $query->set('deathstreak', '=', 0);
            $query->set('killstreak', '=', 0);
            $query->where('id', '=', $id);
            $query->executeUpdate();

            // Commit changes
            $this->pdo->commit();
        }
        catch (Exception $e)
        {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Removes all awards for the specified player
     *
     * @param int $id the player id
     *
     * @return int the number of rows that were deleted
     */
    public function resetPlayerAwards($id)
    {
        // Cast id to an integer, to be safe
        $id = (int)$id;
        return $this->pdo->exec("DELETE FROM player_award WHERE player_id={$id}");
    }

    /**
     * Removes all weapon unlocks for the specified player
     *
     * @param int $id the player id
     *
     * @return int the number of rows that were deleted
     */
    public function resetPlayerUnlocks($id)
    {
        // Cast id to an integer, to be safe
        $id = (int)$id;
        return $this->pdo->exec("DELETE FROM player_unlock WHERE player_id={$id}");
    }

    /**
     * Bans or UnBans a player
     *
     * @param int $id The player id
     * @param bool $banned
     *
     * @return int the number of rows affected by the last SQL statement
     */
    public function setPlayerBanned($id, $banned)
    {
        $time = ($banned) ? time() : 0;
        $mode = ($banned) ? 1 : 0;

        // Prepare statement
        $stmt = $this->pdo->prepare("UPDATE player SET permban=:mode, bantime=:time WHERE id=:id");
        $stmt->bindValue(':id', $id, PDO::PARAM_INT);
        $stmt->bindValue(':mode', $mode, PDO::PARAM_INT);
        $stmt->bindValue(':time', $time, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount();
    }

    /**
     * Appends a players Army data to a view
     *
     * @param int $id
     * @param View $view
     */
    public function attachArmyData($id, View $view)
    {
        // Insure $id is an integer
        $id = (int)$id;

        // Prepare return data
        $data = [];
        $totals = [
            'time' => 0,
            'wins' => 0,
            'losses' => 0,
            'ratio' => 0.00,
            'best' => 0
        ];
        $averages = [
            'time' => 0,
            'wins' => 0,
            'losses' => 0,
            'ratio' => 0.00,
            'best' => 0
        ];

        // fetch player kit data
        $query = "SELECT * FROM `player_army` AS pk JOIN army ON pk.army_id = army.id WHERE `player_id`=".$id;
        $result = $this->pdo->query($query);

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
                ? number_format($totals['wins'] / $length, 0)
                : 0;
            $averages['losses'] = ($totals['losses'] > 0)
                ? number_format($totals['losses'] / $length, 0)
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? (int)round($totals['time'] / $length, 0)
                : 0;
            $averages['best'] = ($totals['best'] > 0)
                ? number_format($totals['best'] / $length, 0)
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
     * Appends a players Kit data to a view
     *
     * @param int $id
     * @param View $view
     */
    public function attachKitData($id, View $view)
    {
        // Insure $id is an integer
        $id = (int)$id;

        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => 0.00
        ];
        $averages = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => 0.00
        ];

        // fetch player kit data
        $query = "SELECT * FROM `player_kit` AS pk JOIN kit ON pk.kit_id = kit.id WHERE `player_id`=".$id;
        $result = $this->pdo->query($query);

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
     * Appends a players Vehicle data to a view
     *
     * @param int $id
     * @param View $view
     */
    public function attachVehicleData($id, View $view)
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
            'ratio' => 0.00
        ];
        $averages = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'roadKills' => 0,
            'ratio' => 0.00
        ];

        // fetch player kit data
        $query = "SELECT * FROM `player_vehicle` AS pk JOIN vehicle ON pk.vehicle_id = vehicle.id WHERE `player_id`=".$id;
        $result = $this->pdo->query($query);

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
        $totals['time'] = TimeHelper::SecondsToHms($totals['time']);
        $averages['time'] = TimeHelper::SecondsToHms($averages['time']);

        // attach data
        $view->set("vehicleData", $data);
        $view->set("vehicleTotals", $totals);
        $view->set("vehicleAverage", $averages);
    }

    /**
     * Appends a players Weapon data to a view
     *
     * @param int $id
     * @param View $view
     */
    public function attachWeaponData($id, View $view)
    {
        // Insure $id is an integer
        $id = (int)$id;

        // Prepare return data
        $data = [];
        $totals = [
            'kills' => 0,
            'deaths' => 0,
            'time' => 0,
            'ratio' => 0.00,
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
        $query = "SELECT * FROM `player_weapon_view` AS pk JOIN weapon ON pk.weapon_id = weapon.id WHERE `player_id`=".$id;
        $result = $this->pdo->query($query);

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
                ? number_format($totals['kills'] / $length, 0)
                : 0;
            $averages['deaths'] = ($totals['deaths'] > 0)
                ? number_format($totals['deaths'] / $length, 0)
                : 0;
            $averages['time'] = ($totals['time'] > 0)
                ? (int)round( $totals['time'] / $length, 0 )
                : 0;
            $averages['fired'] = ($totals['fired'] > 0)
                ? number_format($totals['fired'] / $length, 0)
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
     * Calculate greatest common divisor of x and y. The result is always positive even
     * if either of, or both, input operands are negative.
     *
     * @param number $x
     * @param number $y
     *
     * @return number A positive number that divides into both x and y
     */
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
                    if ($time < 86400)
                        $data['timeplayed'] = $span->format("%y Hours, %j Mins, %s Seconds");
                    else
                        $data['timeplayed'] = $span->format("%d Days, %y Hours, %j Mins");
                    break;
                case 'joined':
                    $value = (int)$value;
                    $data['joined'] = ($value == 0) ? "Never" : date('F jS, Y g:i A T', $value);
                    break;
                case 'lastonline':
                    $value = (int)$value;
                    $data['lastonline'] = ($value == 0) ? "Never" : date('F jS, Y g:i A T', $value);
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
        $data['rankName'] = Battlefield2::GetRankName((int)$player['rank']);

        // Calculate SPM
        $data['spm'] = ($time > 0) ? number_format( $score / ($time / 60), 3 ) : 0;
        return $data;
    }

    /**
     * Appends a players Award data to a view
     *
     * @param int $id
     * @param View $view
     */
    public function attachAwardData($id, View $view)
    {
        $medals = [];
        $badges = [];
        $ribbons = [];

        // Ensure pid is an int
        $id = (int)$id;

        // Grab all awards
        $awards = $this->pdo->query("SELECT * FROM award")->fetchAll();
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
        $awards = $this->pdo->query($query)->fetchAll();
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
                    $badges[$id]['prefix'] = Battlefield2::GetBadgePrefix((int)$data['level']);
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
     */
    public function attachTopVictimAndOpp($id, View $view)
    {
        // Fetch Fav Victim
        $result = $this->pdo->query("SELECT victim, `count` FROM player_kill WHERE attacker={$id} ORDER BY `count` DESC LIMIT 1");
        if ($row = $result->fetch())
        {
            $victim = $row['victim'];
            $count = $row['count'];
            $result = $this->pdo->query("SELECT name, rank FROM player WHERE id={$victim}");
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
        $result = $this->pdo->query("SELECT attacker, `count` FROM player_kill WHERE victim={$id} ORDER BY `count` DESC LIMIT 1");
        if ($row = $result->fetch())
        {
            $attacker = $row['attacker'];
            $count = $row['count'];
            $result = $this->pdo->query("SELECT name, rank FROM player WHERE id={$attacker}");
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

    /**
     * Imports a series of bot players into the player table from a botNames.ai file.
     *
     * @param string $filePath The filepath to the botNames.ai file
     *
     * @return int The number of bot entries imported into the database
     *
     * @throws Exception
     */
    public function importBotsFromFile($filePath)
    {
        // Open file
        $lines = File::ReadAllLines($filePath);

        // Prepare for adding bots
        $pattern = "/^aiSettings\.addBotName[\s\t]+(?<name>[". Player::NAME_REGEX ."]+)$/i";
        $bots = [];
        $imported = 0;

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
            // wrap these inserts in a transaction, to speed things along.
            $this->pdo->beginTransaction();
            foreach ($bots as $bot)
            {
                try
                {
                    // Quote name
                    $name = $this->pdo->quote($bot);

                    // Check if name exists already
                    $exists = $this->pdo->query("SELECT id FROM player WHERE name={$name} LIMIT 1")->fetchColumn(0);
                    if ($exists === false)
                    {
                        $query = "INSERT INTO `player`(`name`, `country`, `email`, `password`) VALUES ({$name}, 'US', 'bot@botNames.ai', '')";
                        $this->pdo->exec($query);
                        $imported++;
                    }
                }
                catch (PDOException $e)
                {
                    // ignore
                }
            }

            // Submit changes
            $this->pdo->commit();

            // Return number of imported bots
            return $imported;
        }
        catch (Exception $e)
        {
            $this->pdo->rollBack();
            throw $e;
        }
    }
}