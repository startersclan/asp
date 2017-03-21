<?php
/*
	Copyright (C) 2006-2017  BF2Statistics

	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA

	----------------------------------------------------------------------------------------

	URL: http://bf2tech.org/index.php/BF2_Statistics
*/

// Namespace
namespace System;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Grab game constants
defined("NUM_ARMIES") or include SYSTEM_PATH . DIRECTORY_SEPARATOR . "GameConstants.php";

// Prepare output
$Response = new AspResponse();

// Get database connection
$connection = Database::GetConnection("stats");

// Make sure we have the required Params and they are valid!
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;
$info = (isset($_GET['info'])) ? $_GET['info'] : '';
$transpose = (isset($_GET['transpose'])) ? (int)$_GET['transpose'] : 0;

// Ensure we have the required url parameters
if ($pid == 0 || empty($info))
{
    $Response->responseError(true, 107);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // BFHQ Request
    if (stringStartsWith($info, "per*,cmb*"))
    {
        // Fetch Player Data
        $result = $connection->query("SELECT * FROM player WHERE id = {$pid}");
        if (!($row = $result->fetch()))
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
        }
        else
        {
            // Initialize response
            $Response->writeHeaderLine("asof");
            $Response->writeDataLine(time());

            # For MNG
            $name = trim($row['name']);
            if (strpos($info, 'mng-') !== false)
                $name = htmlspecialchars($name);

            // Build initial header
            $Output = array(
                'pid' => $row['id'],
                'nick' => $name,
                'scor' => $row['score'],
                'jond' => $row['joined'],
                'wins' => $row['wins'],
                'loss' => $row['losses'],
                'mode0' => $row['mode0'],
                'mode1' => $row['mode1'],
                'mode2' => $row['mode2'],
                'time' => $row['time'],
                'smoc' => (($row['rank']) == 11 ? 1 : 0),
                'cmsc' => $row['skillscore'],
                'osaa' => '0.00', // Overall small arms accuracy
                'kill' => $row['kills'],
                'kila' => $row['damageassists'],
                'deth' => $row['deaths'],
                'suic' => $row['suicides'],
                'bksk' => $row['killstreak'],
                'wdsk' => $row['deathstreak'],
                'tvcr' => 0, // Top Victim
                'topr' => 0, // Top Opponent
                'klpm' => @number_format(60 * ($row['kills'] / $row['time']), 2, '.', ''), // Kills per min
                'dtpm' => @number_format(60 * ($row['deaths'] / $row['time']), 2, '.', ''), // Deaths per min
                'ospm' => @number_format(60 * ($row['score'] / $row['time']), 2, '.', ''), // Score per min
                'klpr' => @number_format($row['kills'] / $row['rounds'], 2, '.', ''), // Kill per round
                'dtpr' => @number_format($row['deaths'] / $row['rounds'], 2, '.', ''), // Deaths per round
                'twsc' => $row['teamscore'],
                'cpcp' => $row['captures'],
                'cacp' => $row['captureassists'],
                'dfcp' => $row['defends'],
                'heal' => $row['heals'],
                'rviv' => $row['revives'],
                'rsup' => $row['ammos'],
                'rpar' => $row['repairs'],
                'tgte' => $row['targetassists'],
                'dkas' => $row['driverassists'],
                'dsab' => $row['driverspecials'],
                'cdsc' => $row['cmdscore'],
                'rank' => $row['rank'],
                'kick' => $row['kicked'],
                'bbrs' => $row['rndscore'],
                'tcdr' => $row['cmdtime'],
                'ban' => $row['banned'],
                'lbtl' => $row['lastonline'],
                'vrk' => 0, // Vehicle Road Kills
                'tsql' => $row['sqltime'],
                'tsqm' => $row['sqmtime'],
                'tlwf' => $row['lwtime'],
                'mvks' => 0, // Top Victim kills
                'vmks' => 0, // Top Opponent Kills
                'mvns' => "-", // Top Victim name
                'mvrs' => 0, // Top Victim rank
                'vmns' => "-", // Top opponent name
                'vmrs' => 0, // Top opponent rank
                'fkit' => 0, // Fav Kit
                'fmap' => 0, // Fav Map
                'fveh' => 0, // Fav vehicle
                'fwea' => 0, // Fav Weapon
                'tnv' => 0, // NIGHT VISION GOGGLES Time - NOT USED
                'tgm' => 0 // GAS MASK TIME - NOT USED
            );

            // Add Weapon data
            addWeaponData($Output, $pid);

            // Add Vehicle Data
            addVehicleData($Output, $pid);

            // Add Army Data
            addArmyData($Output, $pid);

            // Add kit data
            addKitData($Output, $pid);

            // Add Tactical data
            addTacticalData($Output, $pid);

            // Add Fav Victim and Opponent data
            addPlayerTopVictimAndOpp($Output, $pid);

            // Get Favorite Map
            getFavMap($Output, $pid);

            // Add data and spit out the response
            $Response->writeHeaderDataArray($Output);
            $Response->send($transpose);
        }
    }
    // Server Request
    elseif (stringStartsWith($info, "rank") && stringEndsWith($info, "vac-"))
    {
        // NOTE: xpack and bf2 have same return
        $query = <<<SQL
SELECT id, name, score, rank, defends, repairs, heals, ammos, driverspecials, cmdscore, cmdtime, sqltime, 
  sqmtime, wins, losses, teamscore, killstreak, deathstreak, time, kills
FROM `player` 
WHERE `id` = {$pid}
SQL;

        // Make sure the Player exists
        $result = $connection->query($query);
        if (!($row = $result->fetch()))
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
        }

        // Load stats data
        StatsData::Load();

        // Build initial player data
        $Output = array(
            'pid' => $row['id'],
            'name' => $row['name'],
            'scor' => $row['score'],
            'rank' => $row['rank'],
            'dfcp' => $row['defends'],
            'rpar' => $row['repairs'],
            'heal' => $row['heals'],
            'rsup' => $row['ammos'],
            'dsab' => $row['driverspecials'],
            'cdsc' => $row['cmdscore'],
            'tcdr' => $row['cmdtime'],
            'tsql' => $row['sqltime'],
            'tsqm' => $row['sqmtime'],
            'wins' => $row['wins'],
            'loss' => $row['losses'],
            'twsc' => $row['teamscore'],
            'bksk' => $row['killstreak'],
            'wdsk' => $row['deathstreak'],
            'time' => $row['time'],
            'kill' => $row['kills']
        );

        // Weapons
        $includeTacticals = strpos($info, 'de-') !== false;

        // Add default data
        for ($i = 0; $i < StatsData::$NumWeapons; $i++)
        {
            // Skip tactical equipment and explosives (Leave 11 index for combined explosives)
            if ($i > 12 && $i < 18)
                continue;

            $Output["wkl-{$i}"] = 0;
        }

        // Grappling hook, Tactical, and Zipline
        if ($includeTacticals)
        {
            $Output['de-6'] = 0;
            $Output['de-7'] = 0;
            $Output['de-8'] = 0;
        }

        $result = $connection->query("SELECT * FROM `player_weapon` WHERE `pid` = {$pid}");
        while ($roww = $result->fetch())
        {
            $i = (int)$roww['id'];

            // Tactical weapons
            if ($i > 14 && $i < 18 && $includeTacticals)
            {
                switch ($i)
                {
                    case 17:
                        $Output['de-6'] = $roww['fired'];
                        break;
                    case 16:
                        $Output['de-8'] = $roww['fired'];
                        break;
                    case 15:
                        $Output['de-7'] = $roww['fired'];
                        break;
                }
            }

            // check for explosive, which are all combined into wkl-11
            else if (in_array($i, EXPLOSIVE_IDS))
            {
                $Output["wkl-11"] += (int)$roww['kills'];
            }
            else
            {
                $Output["wkl-{$i}"] = $roww['kills'];
            }
        }

        // Kits
        for ($i = 0; $i < StatsData::$NumKits; $i++)
        {
            $Output["ktm-$i"] = 0; // Time
            $Output["kkl-$i"] = 0; // Kills
        }

        $result = $connection->query("SELECT * FROM `player_kit` WHERE `pid` = {$pid}");
        while ($rowk = $result->fetch())
        {
            $i = $rowk["id"];
            $Output["ktm-$i"] = $rowk["time"]; // Time
            $Output["kkl-$i"] = $rowk["kills"]; // Kills
        }

        // Vehicles
        for ($i = 0; $i < StatsData::$NumVehicles; $i++)
        {
            $Output["vtm-$i"] = 0; // Time
            $Output["vkl-$i"] = 0; // Kills
            $Output["vac-{$i}"] = 0; // Vehicle accuracy? Always zero
        }

        $result = $connection->query("SELECT * FROM `player_vehicle` WHERE `pid` = {$pid}");
        while ($rowv = $result->fetch())
        {
            $i = $rowv["id"];
            $Output["vtm-$i"] = $rowv["time"]; // Time
            $Output["vkl-$i"] = $rowv["kills"]; // Kills
        }

        // Army
        for ($i = 0; $i < StatsData::$NumArmies; $i++)
        {
            $Output["atm-{$i}"] = 0;
            $Output["abr-{$i}"] = 0;
            $Output["awn-{$i}"] = 0;
        }

        $result = $connection->query("SELECT * FROM `player_army` WHERE pid = {$pid}");
        while ($rowa = $result->fetch())
        {
            $i = $rowa["id"];
            $Output["atm-{$i}"] = $rowa["time"];
            $Output["abr-{$i}"] = $rowa["best"];
            $Output["awn-{$i}"] = $rowa["wins"];
        }

        // Send response
        $Response->writeHeaderLine("asof");
        $Response->writeDataLine(time());
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    // Time info
    elseif ($info == 'ktm-,vtm-,wtm-,mtm-')
    {
        $kit = isset($_GET['kit']) ? (int)$_GET['kit'] : 0;
        $vehicle = isset($_GET['vehicle']) ? (int)$_GET['vehicle'] : 0;
        $weapon = isset($_GET['weapon']) ? (int)$_GET['weapon'] : 0;
        $map = isset($_GET['map']) ? (int)$_GET['map'] : 0;
        $name = null;

        // Fetch Player, make sure he exists
        $query = "SELECT `name` FROM `player` WHERE `id` = {$pid}";
        $result = $connection->query($query);
        if (!($name = $result->fetchColumn()))
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send(); // script ends here
        }

        // Prepare response
        $Response->writeHeaderLine("asof");
        $Response->writeDataLine(time());
        $Response->writeHeaderLine("pid", "nick", "ktm-{$kit}", "vtm-{$vehicle}", "wtm-{$weapon}", "mtm-{$map}");

        // Kits
        $query = "SELECT `time` FROM `player_kit` WHERE `pid`={$pid} AND `id`={$kit}";
        $result = $connection->query($query);
		$kitt = (int)$result->fetchColumn();

        // Vehicles
        $query = "SELECT `time` FROM `player_vehicle` WHERE `pid` = {$pid} AND `id`={$vehicle}";
        $result = $connection->query($query);
		$vehiclet = (int)$result->fetchColumn();

        // Weapons
        $query = "SELECT `time` FROM `player_weapon` WHERE `pid` = {$pid} AND `id`={$weapon}";
        $result = $connection->query($query);
        $weapont = (int)$result->fetchColumn();

        // Maps
        $query = "SELECT `time` FROM `player_map` WHERE (`pid` = {$pid}) AND (`mapid` = {$map})";
        $result = $connection->query($query);
		$mapt = (int)$result->fetchColumn();

		// Write and send response
        $Response->writeDataLine($pid, $name, $kitt, $vehiclet, $weapont, $mapt);
        $Response->send($transpose);
    }
    // Map info (added support for mbs- & mws-)
    elseif (stringStartsWith($info, 'mtm-,mwn-,mls-'))
    {
        // Make sure Player exists
        $query = "SELECT `name` FROM `player` WHERE `id` = {$pid}";
        $result = $connection->query($query);
        $name = null;
        if (!($name = $result->fetchColumn()))
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
        }

        // Prepare response
        $Response->writeHeaderLine("asof");
        $Response->writeDataLine(time());

        // Add default map data
        $Output = ['pid' => $pid, 'nick' => $name];

        // Build individual headers, so they can group together in response
        $mtm = $mwn = $mls = $mbs = $mws = array();

        // Extended data?
        $Extended = (strpos($info, "mbs-") !== false);

        // Vanilla BF2 Maps (Middle East Theatre)
        for ($i = 0; $i < 7; $i++)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // Vanilla BF2 Maps (Asian Theatre)
        for ($i = 100; $i < 106; $i++)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // Special BF2 Maps (Wake Island, Highway tampa)
        for ($i = 601; $i < 603; $i++)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // Special forces maps
        for ($i = 300; $i < 308; $i++)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // More Maps (smoke screen, Taraba Quarry, Jalalabad)
        for ($i = 10; $i < 13; $i++)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // Armored Fury Maps
        for ($i = 200; $i < 203; $i++)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // Great wall, Operation Blue Pearl
        for ($i = 110; $i < 130; $i += 10)
        {
            $mtm[$i] = $mwn[$i] = $mls[$i] = 0;
            if ($Extended) $mbs[$i] = $mws[$i] = 0;
        }

        // Prepare where statement
        $where = (strpos($info, "cmap-") !== false)
            ? "`pid` = {$pid}"
            : "`pid` = {$pid} AND `mapid` < 700";

        // Fetch map data from DB
        $query = "SELECT * FROM `player_map` WHERE {$where}";
        $result = $connection->query($query);
        if ($row = $result->fetch())
        {
            do
            {
                $i = (int)$row['mapid'];
                $mtm[$i] = $row['time'];
                $mwn[$i] = $row['wins'];
                $mls[$i] = $row['losses'];
                if ($Extended)
                {
                    $mbs[$i] = $row['best'];
                    $mws[$i] = $row['worst'];
                }
            } while ($row = $result->fetch());
        }

        /**
         * If the headers aren't all grouped together, the map info wont parse in the bf2 client,
         * Therefor was must do this in a non-efficient way
         */
        foreach ($mtm as $i => $value)
            $Output["mtm-{$i}"] = $value;

        foreach ($mwn as $i => $value)
            $Output["mwn-{$i}"] = $value;

        foreach ($mls as $i => $value)
            $Output["mls-{$i}"] = $value;

        if ($Extended)
        {
            foreach ($mbs as $i => $value)
                $Output["mbs-{$i}"] = $value;

            foreach ($mws as $i => $value)
                $Output["mws-{$i}"] = $value;
        }

        // Output map data
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    elseif ($info == 'rank')
    {
        $query = "SELECT `id`, `name`, `rank`, `chng`, `decr` FROM `player` WHERE `id` = {$pid}";
        $result = $connection->query($query);
        if (!($row = $result->fetch()))
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
        }
        else
        {
            // Update
            if ($row['chng'] != 0 || $row['decr'] != 0)
                $connection->exec("UPDATE `player` SET `chng` = 0, `decr` = 0 WHERE `id` = {$pid}");

            $Response->writeHeaderLine("pid", "nick", "rank", "chng", "decr");
            $Response->writeDataLine($row['id'], $row['name'], $row['rank'], $row['chng'], $row['decr']);
            $Response->send($transpose);
        }
    }
    else
    {
        $Response->responseError(true);
        $Response->writeHeaderLine("asof", "err");
        $Response->writeDataLine(time(), "Parameter Error!");
        $Response->send();
    }
}

/**
 * Adds the weapon data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addWeaponData(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");
    $limit = NUM_WEAPONS - 1;

    // Assign some vars
    $favTime = 0;
    $totalHits = 0;
    $totalFired = 0;

    // Explosives sum variables
    $eKills = $eDeaths = 0;

    /**
     * Add Defaults
     *
     * ORDER IS IMPORTANT HERE FOR BFHQ!
     */

    // Time
    for ($i = 0; $i < $limit; $i++)
        $Output["wtm-$i"] = 0;

    // Kills
    for ($i = 0; $i < $limit; $i++)
        $Output["wkl-$i"] = 0;

    // Deaths
    for ($i = 0; $i < $limit; $i++)
        $Output["wdt-$i"] = 0;

    // Accuracy
    for ($i = 0; $i < $limit; $i++)
        $Output["wac-$i"] = 0;

    //  K/D Ratio
    for ($i = 0; $i < $limit; $i++)
        $Output["wkd-$i"] = '0:0';

    // Weapons
    $result = $connection->query("SELECT * FROM player_weapon WHERE pid = {$pid} ORDER BY id");
    while ($row = $result->fetch())
    {
        $i = (int)$row['id'];

        // Exclude Tactical weapons
        if ($i < NUM_WEAPONS)
        {
            // Define whether this weapon is an explosive
            $isExplosive = in_array($i, EXPLOSIVE_IDS);
            if ($isExplosive)
                $i = 11;

            // Convert weapon stats to integers
            $time = (int)$row["time"];
            $kills = (int)$row["kills"];
            $deaths = (int)$row["deaths"];
            $hits = (int)$row["hits"];
            $fired = (int)$row["fired"];
            $acc = ($fired != 0 && $hits != 0) ? round(($hits / $fired) * 100, 0) : 0;

            // Update totals for later
            $totalFired += $fired;
            $totalHits += $hits;

            // Define favorite based on Time Played
            if ($time > $favTime)
            {
                $Output['fwea'] = $i;
                $favTime = $time;
            }

            // Set weapon data
            $Output["wtm-$i"] += $time;      // Time
            $Output["wkl-$i"] += $kills;     // Kills
            $Output["wdt-$i"] += $deaths;    // Deaths
            $Output["wac-$i"] += $acc;       // Accuracy

            // check for explosive, which are all combined into wkl-11
            if ($isExplosive)
            {
                $eKills += $kills;
                $eDeaths += $deaths;
            }
            else
            {
                // K/D Ratio
                if ($deaths != 0)
                {
                    $den = denominator($kills, $deaths);
                    $Output["wkd-$i"] = ($kills / $den) . ':' . ($deaths / $den);
                }
                else
                    $Output["wkd-$i"] = $kills . ':0';
            }
        }
    }

    // K/D Ratio for explosives
    if ($eDeaths != 0)
    {
        $den = denominator($eKills, $eDeaths);
        $Output["wkd-11"] = ($eKills / $den) . ':' . ($eDeaths / $den);
    }
    else
        $Output["wkd-11"] = $eKills . ':0';

    // Set favorite data's
    $acc = ($totalHits != 0) ? round($totalHits / $totalFired, 2) * 100 : 0;
    $Output['osaa'] = number_format((float)$acc, 2, '.', '');
}

/**
 * Adds the vehicle data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addVehicleData(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Assign some vars
    $favTime = 0;
    $roadKills = 0;

    /**
     * Add Defaults
     *
     * ORDER IS IMPORTANT HERE FOR BFHQ!
     */

    // Time
    for ($i = 0; $i < NUM_VEHICLES; $i++)
        $Output["vtm-$i"] = 0;

    // Kills
    for ($i = 0; $i < NUM_VEHICLES; $i++)
        $Output["vkl-$i"] = 0;

    // Deaths
    for ($i = 0; $i < NUM_VEHICLES; $i++)
        $Output["vdt-$i"] = 0;

    // Kill / Death ratio
    for ($i = 0; $i < NUM_VEHICLES; $i++)
        $Output["vkd-$i"] = '0:0';

    // Road Kills
    for ($i = 0; $i < NUM_VEHICLES; $i++)
        $Output["vkr-$i"] = 0;

    // Vehicles
    $result = $connection->query("SELECT * FROM player_vehicle WHERE pid = {$pid} AND id < ". NUM_VEHICLES ." ORDER BY id");
    while ($row = $result->fetch())
    {
        $i = (int)$row['id'];

        // Vars
        $time = (int)$row["time"];
        $kills = (int)$row["kills"];
        $deaths = (int)$row["deaths"];
        $roadKills = (int)$row["roadkills"];

        // Add data
        $Output["vtm-$i"] = $time;
        $Output["vkl-$i"] = $kills;
        $Output["vdt-$i"] = $deaths;
        $Output["vkr-$i"] = $roadKills;

        // K/D Ratio
        if ($deaths != 0)
        {
            $den = denominator($kills, $deaths);
            $Output["vkd-{$i}"] = ($kills / $den) . ':' . ($deaths / $den);
        }
        else
            $Output["vkd-{$i}"] = $kills . ':0';

        // Favorite?
        if ($time > $favTime)
        {
            $Output['fveh'] = $i;
            $favTime = $time;
        }
    }

    $Output['vrk'] = $roadKills;
}

/**
 * Adds the army data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addArmyData(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    /**
     * Add Defaults
     *
     * ORDER IS IMPORTANT HERE FOR BFHQ!
     */

    // Time
    for ($i = 0; $i < 10; $i++)
        $Output["atm-$i"] = 0;

    // Wins
    for ($i = 0; $i < 10; $i++)
        $Output["awn-$i"] = 0;

    // Losses
    for ($i = 0; $i < 10; $i++)
        $Output["alo-$i"] = 0;

    // Best Round
    for ($i = 0; $i < 10; $i++)
        $Output["abr-$i"] = 0;

    // Weapons
    $result = $connection->query("SELECT * FROM player_army WHERE pid = {$pid} AND id < 10 ORDER BY id");
    while ($row = $result->fetch())
    {
        $i = (int)$row['id'];
        $Output["atm-$i"] = $row["time"];
        $Output["awn-$i"] = $row["wins"];
        $Output["alo-$i"] = $row["losses"];
        $Output["abr-$i"] = $row["best"];
    }
}

/**
 * Adds the kit data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addKitData(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Assign some vars
    $favTime = 0;

    /**
     * Add Defaults
     *
     * ORDER IS IMPORTANT HERE FOR BFHQ!
     */
    // Time
    for ($i = 0; $i < NUM_KITS; $i++)
        $Output["ktm-$i"] = 0; // Time

    // Kills
    for ($i = 0; $i < NUM_KITS; $i++)
        $Output["kkl-$i"] = 0; // Kills

    // Deaths
    for ($i = 0; $i < NUM_KITS; $i++)
        $Output["kdt-$i"] = 0; // Deaths

    // K/D Ratio
    for ($i = 0; $i < NUM_KITS; $i++)
        $Output["kkd-$i"] = '0:0'; // K/D Ratio

    // Weapons
    $result = $connection->query("SELECT * FROM player_kit WHERE pid = {$pid} AND id < ". NUM_KITS ." ORDER BY id");
    while ($row = $result->fetch())
    {
        $i = (int)$row['id'];

        // Convert some vars to ints
        $time = (int)$row["time"];
        $kills = (int)$row["kills"];
        $deaths = (int)$row["deaths"];

        // Favorite
        if ($time > $favTime)
        {
            $Output['fkit'] = $i;
            $favTime = $time;
        }

        // Add Data
        $Output["ktm-$i"] = $time;
        $Output["kkl-$i"] = $kills;
        $Output["kdt-$i"] = $deaths;

        // K/D Ratio
        if ($deaths != 0)
        {
            $den = denominator($kills, $deaths);
            $Output["kkd-{$i}"] = ($kills / $den) . ':' . ($deaths / $den);
        }
        else
            $Output["kkd-{$i}"] = $kills . ':0';
    }
}

/**
 * Adds the tactical data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addTacticalData(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    /**
     * Add Defaults
     *
     * ORDER IS IMPORTANT HERE FOR BFHQ!
     */

    // Time
    for ($i = 6; $i < 9; $i++)
        $Output["de-$i"] = 0;

    // Weapons
    $result = $connection->query("SELECT * FROM player_weapon WHERE pid = {$pid} AND id > 15");
    while ($row = $result->fetch())
    {
        $i = ((int)$row['id']) - 10;
        $Output["de-$i"] = (int)$row['fired'];
    }
}

/**
 * Adds the favorite victim and opponent data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addPlayerTopVictimAndOpp(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Fetch Fav Victim
    $result = $connection->query("SELECT victim, `count` FROM player_kill WHERE attacker={$pid} ORDER BY `count` DESC LIMIT 1");
    if ($row = $result->fetch())
    {
        $victim = $row['victim'];
        $count = $row['count'];
        $result = $connection->query("SELECT name, rank FROM player WHERE id={$victim}");
        if ($row = $result->fetch())
        {
            $Output['tvcr'] = $victim;
            $Output['mvks'] = $count;
            $Output['mvns'] = $row['name'];
            $Output['mvrs'] = $row['rank'];
        }
    }

    // Fetch Fav Opponent
    $result = $connection->query("SELECT attacker, `count` FROM player_kill WHERE victim={$pid} ORDER BY `count` DESC LIMIT 1");
    if ($row = $result->fetch())
    {
        $attacker = $row['attacker'];
        $count = $row['count'];
        $result = $connection->query("SELECT name, rank FROM player WHERE id={$attacker}");
        if ($row = $result->fetch())
        {
            $Output['topr'] = $attacker;
            $Output['vmks'] = $count;
            $Output['vmns'] = $row['name'];
            $Output['vmrs'] = $row['rank'];
        }
    }
}

/**
 * Adds the favorite map data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function getFavMap(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Fetch Fav Victim
    $result = $connection->query("SELECT mapid FROM player_map WHERE pid={$pid} AND mapid < 700 ORDER BY time DESC LIMIT 1");
    if ($id = $result->fetchColumn())
    {
        $Output['fmap'] = $id;
    }
}

function denominator($x, $y)
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
 * Determines whether the end of a string matches the specified string
 */
function stringEndsWith( $string, $sub )
{
    $len = strlen( $sub );
    return substr_compare( $string, $sub, -$len, $len ) === 0;
}

/**
 * Determines whether the beginning of a string matches a specified string
 */
function stringStartsWith( $string, $sub )
{
    return substr_compare( $string, $sub, 0, strlen( $sub ) ) === 0;
}