<?php
/*
    Copyright (C) 2006-2013  BF2Statistics

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
*/

// DecryptionFailure: Authentication token decryption failure
use System\Database;

// No direct access
if(!defined("BF2_ADMIN"))
    die("No Direct Access");

// Make sure we have an auth token!
if(!isset($_GET["auth"]) || strlen($_GET["auth"]) < 4)
    // Official servers do not output a formal response if the Auth key is invalid
    die("None: No error");

// Prepare output
$Response = new System\AspResponse();

// Create Auth token
$AuthKey = new System\AuthKey($_GET['auth']);

// Make sure the key isn't expired
if($AuthKey->isExpired())
{
    // Official servers do not output official responses for expired auth tokens
    die("ExpiredAuth: Expired authentication token");
}
else
{
    // Make player PID of Token
    $pid = $AuthKey->getPid();

    // Connect to the database
    $connection = System\Database::GetConnection("stats");

    // Get vars
    $transpose = (isset($_GET['transpose'])) ? $_GET['transpose'] : 0;
    $mode = (isset($_GET['mode'])) ? $_GET['mode'] : false;

    // Check Token is server version
    $clType = ($AuthKey->isServerRequest()) ? 'server' : 'client';

    // Make sure player id exists!
    $player = array();
    if($mode != "players" && $mode != "comp")
    {
        $result = $connection->query("SELECT * FROM player WHERE id={$pid}");
        if(!($result instanceof PDOStatement) || !($player = $result->fetch()))
        {
            // E 104 NoRowsDBError: Error, no rows returned
            $Response->responseError(true, 104);
            $Response->send();
            die;
        }
    }

    // Process Request
    if ($mode == "base" && !$AuthKey->isServerRequest())
    {
        // Prepare output
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), $clType);

        // Fetch dogtags
        $result = $connection->query("SELECT COALESCE(SUM(count) ,0) AS sum, COUNT(id) AS count FROM dogtags WHERE id = {$pid}");
        if (!($result instanceof PDOStatement) || !($row = $result->fetch()))
        {
            $row = array(
                'sum' => 0,
                'count' => 0
            );
        }

        // Start output
        $Response->writeHeaderDataArray(
            array(
                'pid' => $pid,
                'nick' => $player['name'],
                'tid' => 0, // Tournament Id
                'gsco' => $player['score'],
                'crpt' => ($player['score'] + $player['experiencescore'] + $player['awaybonusscore']),
                'rnk' => $player['rank'],
                'rnkcg' => $player['rnkcg'],
                'tt' => $player['time'],
                'pdt' => $row['count'],
                'pdtc' => $row['sum'],
                'kdr' => @round($player['kills'] / $player['deaths'], 3),
                'ent-1' => 0,
                'ent-2' => 0,
                'ent-3' => $player['vet'],
                'bp-1' => 1,
                'unavl' => $player['availunlocks']
            )
        );

        // Add awards Header
        $Response->writeHeaderLine("award", "level", "when", "first");

        // Get Awards
        $result = $connection->query("SELECT awd, level, earned, first FROM awards WHERE id = {$pid} ORDER BY awd ASC");
        if ($result instanceof PDOStatement)
        {

            while($row = $result->fetch())
            {
                $level = 0;
                $first = (($row['awd'] > 199 && $row['awd'] < 300) || $row['awd'] > 399) ? $row['first'] : 0;

                // Badges
                if ($row['awd'] > 99 && $row['awd'] < 200)
                {
                    $awd = $row['awd'] . "_" . $row['level'];
                }
                else
                {
                    $awd = $row['awd'];
                    $level = $row['level'];
                }

                // Ribbons
                if ($row['awd'] > 299 && $row['awd'] < 400)
                    $level = 0;

                $Response->writeDataLine($awd, $level, $row['earned'], $first);
            }
        }

        // Send Response
        $Response->send($transpose);
    }
    elseif ($mode == "base" && $AuthKey->isServerRequest())
    {
        // Prepare output
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), $clType);

        // Prepare output
        $Output = array(
            'pid' => $pid,
            'nick' => $player['name'],
            'tid' => 0, // Tournament Id
            'gsco' => $player['score'],
            'rnk' => $player['rank'],
            'tac' => $player['cmdtime'],
            'cs' => $player['cmdtime'],
            'tt' => $player['time'],
            'crpt' => ($player['score'] + $player['experiencescore'] + $player['awaybonusscore']),
            'klls' => $player['kills'],
            'klstrk' => $player['killstreak'],
            'bnspt' => $player['awaybonusscore'],
            'dstrk' => $player['deathstreak'],
            'rps' => $player['repairs'],
            'resp' => $player['resupplies'],
            'tasl' => $player['sqltime'],
            'tasm' => $player['sqmtime'],
            'awybt' => $player['awaybonustimeleft'],
            'hls' => $player['heals'],
            'sasl' => 0,
            'tds' => $player['titandefendkills'],
            'win' => $player['wins'],
            'los' => $player['losses'],
            'unlc' => $player['usedunlocks'],
            'expts' => $player['experiencescore'],
            'cpt' => $player['captures'],
            'dcpt' => $player['defends'],
            'twsc' => $player['teamscore'],
            'tcd' => $player['titanpartsdestroyed'],
            'slpts' => $player['squadleaderbonusscore'],
            'tcrd' => $player['titancoredestroyed'],
            'md' => $player['missilesdestroyed'],
            'ent' => 0,
            'ent-1' => 0,
            'ent-2' => 0,
            'ent-3' => $player['vet'],
            'bp-1' => 1,
            'htp' => 0,
            'hkl' => 0,
            'atp' => 0,
            'akl' => 0,
            'klsk' => 0,
            'klse' => 0,
            'pdt' => $connection->query("SELECT COUNT(id) FROM dogtags WHERE id = {$pid}")->fetchColumn()
        );

        // Add vehicle data
        addVehicleData($pid, $Output);

        // Add kit data
        addKitData($pid, $Output);

        // Add weapon data
        addWeaponData($pid, $Output);

        // Add Equipment data
        addEquipmentData($pid, $Output);

        // Add Army
        addArmyData($pid, $Output);

        // Add game mode data
        addGamemodeData($pid, $Output);

        // Add map data
        addMapData($pid, $Output, false);

        // Send data
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);

    }
    elseif($mode == "map" && !$AuthKey->isServerRequest())
    {
        // Prepare output
        $Response->writeHeaderLine("pid", "nick", "tid", "asof");
        $Response->writeDataLine($pid, $player['name'], 0, time());

        // Add map data as a response
        addMapData($pid, $Response, true);
        $Response->send($transpose);
    }
    elseif ($mode == "veh" && !$AuthKey->isServerRequest())
    {
        // Prepare output
        $Response->writeHeaderLine("pid", "nick", "tid", "asof");
        $Response->writeDataLine($pid, $player['name'], 0, time());

        // Add map data as a response
        $Ouput = array();
        addVehicleData($pid, $Output, true);
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    elseif ($mode == "wep" && !$AuthKey->isServerRequest())
    {
        // Prepare output
        $Response->writeHeaderLine("pid", "nick", "tid", "asof");
        $Response->writeDataLine($pid, $player['name'], 0, time());

        // Add map data as a response
        $Ouput = array();
        addWeaponData($pid, $Output, true);
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    elseif ($mode == "com" && !$AuthKey->isServerRequest())
    {
        // Get weapon 27 kills ?? its in Off response so... yea
        $result = $connection->query("SELECT kills27 FROM weapons WHERE id = {$pid}");
        $kills = ($result instanceof PDOStatement) ? $result->fetchColumn() : 0;

        // Prepare output
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), "client");
        $Output = array(
            'pid' => $pid,
            'nick' => $player['name'],
            'tid' => 0, // Tournament Id
            'slbspn' => $player['squadleaderspawns'],
            'sluav' => $player['squadleaderuav'],
            'kluav' => 0,
            'cs' => $player['cmdscore'],
            'slpts' => $player['squadleaderbonusscore'],
            'tasl' => $player['sqltime'],
            'sasl' => 0,
            'tac' => $player['cmdtime'],
            'slbcn' => $player['squadleaderbeaconspawn'],
            'wkls-27' => $kills,
            'csgpm-0' => $player['csgpm0'],
            'csgpm-1' => $player['csgpm1'],
            'csgpm-2' =>  $player['csgpm2']
        );

        // Send response
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    elseif ($mode == "ovr" && !$AuthKey->isServerRequest())
    {
        // === WE need to fetch player favorites... kinda daunting i know right? === //

        // Favorite game mode
        $favGm = 0;
        $query = "SELECT CASE GREATEST(`tgpm0`, `tgpm1`, `tgpm2`, `tgpm3`, `tgpm4`, `tgpm5`, `tgpm6`) ".
            "WHEN `tgpm0` THEN 0 WHEN `tgpm1` THEN 1 WHEN `tgpm2` THEN 2 WHEN `tgpm3` THEN 3 ".
            "WHEN `tgpm4` THEN 4 WHEN `tgpm5` THEN 5 WHEN `tgpm6` THEN 6 ELSE 0 END AS maxcol ".
            "FROM game_mode WHERE id = {$pid}";
        $reult = $connection->query($query);
        if($result instanceof PDOStatement)
            $favGm = $result->fetchColumn();

        // Favorite Kit
        $favKit = 0;
        $query = "SELECT CASE GREATEST(`time0`, `time1`, `time2`, `time3`) ".
            "WHEN `time0` THEN 0 WHEN `time1` THEN 1 WHEN `time2` THEN 2 WHEN `time3` THEN 3 ".
            "ELSE 0 END AS maxcol ".
            "FROM kits WHERE id = {$pid}";
        $reult = $connection->query($query);
        if($result instanceof PDOStatement)
            $favKit = $result->fetchColumn();

        // Favorite map
        $favMap = 0;
        $result = $connection->query("SELECT mapid FROM maps WHERE id = {$pid} ORDER BY `time` DESC LIMIT 1");
        if($result instanceof PDOStatement)
            $favMap = $result->fetchColumn();

        // Favorite Equipment
        $favEq = 0;
        $query = "SELECT time0, time1, time2, time3, time4, time5, time6, time7, time8, time9, time10,
            time11, time12, time13, time14, time15 FROM equipment WHERE id = {$pid}";
        $result = $connection->query($query);
        if($result instanceof PDOStatement && ($row = $result->fetch()))
        {
            arsort($row);
            $favEq = str_replace('time', '', key($row));
        }

        // Favorite Vehicle
        $favVeh = 0;
        $query = "SELECT time0, time1, time2, time3, time4, time5, time6, time7, time8, time9, time10,
            time11, time12, time13, time14, time15 FROM vehicles WHERE id = {$pid}";
        $result = $connection->query($query);
        if($result instanceof PDOStatement && ($row = $result->fetch()))
        {
            arsort($row);
            $favVeh = str_replace('time', '', key($row));
        }

        // Favorite Weapon
        $favWep = 0;
        $query = "SELECT time0, time1, time2, time3, time4, time5, time6, time7, time8, time9, time10,
            time11, time12, time13, time14, time15, time16, time17, time18, time19, time20, time21, time22,
            time23, time24, time25, time26, time27, time28, time29, time30, time31
            FROM weapons WHERE id = {$pid}";
        $result = $connection->query($query);
        if($result instanceof PDOStatement && ($row = $result->fetch()))
        {
            arsort($row);
            $favWep = str_replace('time', '', key($row));
        }

        // Dog tags
        $result = $connection->query("SELECT COALESCE(SUM(`count`), 0) AS sum, COUNT(id) AS `count` FROM dogtags WHERE id = {$pid}");
        if(!($result instanceof PDOStatement) || ($row = $result->fetch()))
        {
            $row = array(
                'sum' => 0,
                'count' => 0
            );
        }

        // Prepare output
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), "client");
        $Output = array(
            'pid' => $pid,
            'nick' => $player['name'],
            'tid' => 0, // Tournament Id
            'gsco' => $player['score'],
            'tt' => $player['time'],
            'crpt' => ($player['score'] + $player['experiencescore'] + $player['awaybonusscore']),
            'fgm' => $favGm,
            'fm' => $favMap,
            'fe' => $favEq,
            'fv' => $favVeh,
            'fk' => $favKit,
            'fw' => $favWep,
            'win' => $player['wins'],
            'los' => $player['losses'],
            'acdt' => $player['joined'],
            'lgdt' => $player['lastonline'],
            'brs' => $player['rndscore'],
            'ept-3' => 0,
            'pdt' => $row['count'],
            'pdtc' => $row['sum']
        );

        // Send response
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);

    }
    elseif($mode == "wrk" && !$AuthKey->isServerRequest())
    {
        // Prepare output
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), "client");
        $Output = array(
            'pid' => $pid,
            'nick' => $player['name'],
            'tid' => 0, // Tournament Id
            'twsc' => $player['teamscore'],
            'cpt' => $player['captures'],
            'capa' => $player['captureassists'],
            'dcpt' => $player['defends'],
            'hls' => $player['heals'],
            'rps' => $player['repairs'],
            'rvs' => $player['revives'],
            'resp' => $player['resupplies'],
            'talw' => $player['lwtime'],
            'dass' => $player['driverspecials'],
            'tkls' => $player['teamkills'],
            'tdmg' => $player['teamdamage'],
            'tvdmg' => $player['teamvehicledamage'],
            'tasm' => $player['sqmtime'],
            'tasl' => $player['sqltime'],
            'tac' => $player['cmdtime'],
            'cs' => $player['cmdscore'],
            'sasl' => 0,
            'cts' => $player['capturedmissilesilos']
        );

        // Send response
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    elseif($mode == "ply" && !$AuthKey->isServerRequest())
    {
        // To prevent errors, we always have 1 round
        $rounds = ($player['rounds'] == 0) ? 1 : $player['rounds'];

        // Prepare output
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), "client");
        $Output = array(
            'pid' => $pid,
            'nick' => $player['name'],
            'tid' => 0,
            'klls' => $player['kills'],
            'dths' => $player['deaths'],
            'suic' => $player['suicides'],
            'klstrk' => $player['killstreak'],
            'dstrk' => $player['deathstreak'],
            'spm' => (($player['score'] == 0) ? 0 : @round(($player['score'] / ($player['time'] / 60)), 3)),
            'kdr' => getKdr($player['kills'], $player['deaths']),
            'kpm' => (($player['kills'] == 0) ? 0 : @round(($player['kills'] / ($player['time'] / 60)), 3)),
            'dpm' => (($player['deaths'] == 0) ? 0 : @round(($player['deaths'] / ($player['time'] / 60)), 3)),
            'akpr' => (($player['kills'] == 0) ? 0 : @round(($player['kills'] / ($rounds / 60)), 3)),
            'adpr' => (($player['deaths'] == 0) ? 0 : @round(($player['deaths'] / ($rounds / 60)), 3)),
            'tots' => $player['totalshots'],
            'toth' => $player['totalhits'],
            'ovaccu' => (($player['totalhits'] == 0) ? 0 : @round(($player['totalhits'] / $player['totalshots']), 3))
        );

        // Add Kit Data
        addKitData($pid, $Output, true);

        // Send response
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
    }
    elseif($mode == "titan" && !$AuthKey->isServerRequest())
    {
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), "client");
        $Response->writeHeaderLine(
            "pid", "nick", "tid", "tas", "tdrps", "tds", "tgr", "tgd", "tcd", "tcrd", "ttp", "trp", "cts"
        );
        $Response->writeDataLine(
            $player['pid'], $player['nick'], 0, $player['titanattackkills'], $player['titandrops'],
            $player['titandefendkills'], $player['titanweaponsrepaired'], $player['titanweaponsdestroyed'],
            $player['titanpartsdestroyed'], $player['titancoredestroyed'], $player['tgpm1'], $player['trpm1'],
            $player['capturedmissilesilos']
        );

        // Send response
        $Response->send($transpose);
    }
    elseif($mode == "players" && $AuthKey->isServerRequest())
    {
        // Check if the URL variable exists and contains values
        $sPid = (isset($_GET['sPid'])) ? $_GET['sPid'] : false;
        if(empty($sPid))
        {
            // E 998 InvalidQryTest: Invalid or missing query string params
            $Response->responseError(true, 998);
            $Response->send();
            die;
        }

        // Prepare response
        $Response->writeHeaderLine("asof", "cb");
        $Response->writeDataLine(time(), "server");
        $Response->writeHeaderLine(
            "pid", "nick", "tid", "tt", "klstrk", "dstrk", "win", "los", "hkl", "htp", "akl", "atp", "tgpm-0",
            "kgpm-0", "bksgpm-0", "ctgpm-0", "csgpm-0", "trpm-0", "tgpm-1", "kgpm-1", "tbksgpm-1", "ctgpm-1",
            "csgpm-1", "trpm-1", "attp-0", "attp-1", "awin-0", "awin-1"
        );

        // Trim all Pid's
        $array = explode(',', trim($sPid));
        $players = join(',', array_map('trim', $array));

        // Fetch players data
        $query = "SELECT id, name, time, killstreak, deathstreak, wins, losses FROM player WHERE id IN({$players})
            ORDER BY id DESC";
        $result = $connection->query($query);

        // Game Mode
        $query = "SELECT id, tgpm0, tgpm1, kgpm0, kgpm1, bksgpm0, bksgpm1, ctgpm0, ctgpm1, csgpm0, csgpm1, trpm0, trpm1
            FROM game_mode WHERE id IN({$players}) ORDER BY id DESC";
        $result2 = $connection->query($query);

        // Army
        $query = "SELECT id, time0, time1, win0, win1 FROM army WHERE id IN({$players}) ORDER BY id DESC";
        $result3 = $connection->query($query);

        if($result instanceof PDOStatement && $result2 instanceof PDOStatement && $result3 instanceof PDOStatement)
        {
            while($row = $result->fetch())
            {
                // Fetch all rows
                $rowg = $result2->fetch();
                $rowa = $result3->fetch();

                // Make sure we have a row!
                if($rowg == false || $rowa == false)
                    continue;

                // Skip bad records
                if (($row['id'] != $rowg['id']) || ($row['id'] != $rowa['id']))
                    continue;

                // Append response data
                $Response->writeDataLine($row['id'], $row['name'], 0, $row['time'], $row['killstreak'],
                    $row['deathstreak'], $row['wins'], $row['losses'], 0, 0, 0, 0, $rowg['tgpm0'],
                    $rowg['kgpm0'], $rowg['bksgpm0'], $rowg['ctgpm0'], $rowg['csgpm0'], $rowg['trpm0'], $rowg['tgpm1'],
                    $rowg['kgpm1'], $rowg['bksgpm1'], $rowg['ctgpm1'], $rowg['csgpm1'], $rowg['trpm1'], $rowa['time0'],
                    $rowa['time1'], $rowa['win0'], $rowa['win1']
                );
            }
        }

        // Send Response
        $Response->send($transpose);
    }
    elseif($mode == "comp" && !$AuthKey->isServerRequest())
    {
        // Not finished
        $Response->responseError(true);
        $Response->writeHeaderDataArray(array("err" => "Not Complete"));
        $Response->send();
        die;
    }
    else
    {
        // E 998 InvalidQryTest: Invalid or missing query string params
        $Response->responseError(true, 998);
        $Response->send();
        die;
    }
}

/**
 * Appends map data to the current output
 *
 * @param $pid
 * @param $Response [Reference Variable]
 * @param bool $asResponse
 *
 * @return void
 */
function addMapData($pid, &$Response, $asResponse = true)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    $result = $connection->query("SELECT * FROM maps WHERE id = {$pid} AND mapid <= 20 ORDER BY gm ASC, mapid ASC");
    if($result instanceof PDOStatement)
    {
        $maps = array();
        while($row = $result->fetch())
        {
            $maps[$row['gm']][$row['mapid']] =
                array(
                    'time' => $row['time'],
                    'win' => $row['win'],
                    'loss' => $row['loss'],
                    'best' => $row['best'],
                    'score' => $row['score'],
                    'kills' => $row['kills']
                );
        }

        // Spit out each mode
        for($i = 0; $i < 4; $i++)
        {
            if(!isset($maps[$i]))
                continue;

            if($asResponse)
            {
                $out = array();
                foreach($maps[$i] as $id => $map)
                {
                    $out["mtt-{$i}-{$id}"] = $map['time'];
                    $out["mwin-{$i}-{$id}"] = $map['win'];
                    $out["mlos-{$i}-{$id}"] = $map['loss'];
                    $out["mbr-{$i}-{$id}"] = $map['best'];
                    $out["msc-{$i}-{$id}"] = $map['score'];
                }
                /** @var $Response System\AspResponse */
                $Response->writeHeaderDataArray($out);
            }
            else
            {
                foreach($maps[$i] as $id => $map)
                {
                    $Response["mtt-{$i}-{$id}"] = $map['time'];
                    $Response["mwin-{$i}-{$id}"] = $map['win'];
                    $Response["mlos-{$i}-{$id}"] = $map['loss'];
                    $Response["mbr-{$i}-{$id}"] = $map['best'];
                    $Response["msc-{$i}-{$id}"] = $map['score'];
                }
            }
        }
    }
}

/**
 * Adds the vehicle data to the current output
 *
 * @param string|int $pid The player ID
 * @param mixed[] $Output [Reference Variable]
 * @param bool $extended
 */
function addVehicleData($pid, &$Output, $extended = false)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Vehicles
    $result = $connection->query("SELECT * FROM vehicles WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 16; $i++)
        {
            $Output["vtp-$i"] = 0;   // Time played with vehicle
            $Output["vkls-$i"] = 0;  // Kills with vehicle
            $Output["vdths-$i"] = 0; //  Deaths by vehicle
            $Output['vdstry'] = 0;   // Destroyed vehicle
            if($extended)
            {
                $Output["vkdr-$i"] = 0;  // Kill / Death ratio
                $Output["vkrls-$i"] = 0; // Road Kills
            }
        }
    }
    else
    {
        for ($i = 0; $i < 16; $i++)
        {
            // Convert to Integer
            $kills  = (int) $row["kills{$i}"];
            $deaths = (int) $row["deaths{$i}"];

            // Add data
            $Output["vtp-$i"] = $row["time{$i}"];
            $Output["vkls-$i"] = $kills;
            $Output["vdths-$i"] = $deaths;
            $Output['vdstry'] = $row["dstry{$i}"];
            if($extended)
            {
                $Output["vkdr-{$i}"] = getKdr($kills, $deaths);
                $Output["vkrls-$i"] = $row["rk{$i}"];
            }
        }
    }
}

/**
 * Adds the kit data to the current output
 *
 * @param string|int $pid The player ID
 * @param mixed[] $Output [Reference Variable]
 * @param bool $extended
 */
function addKitData($pid, &$Output, $extended = false)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Vehicles
    $result = $connection->query("SELECT * FROM kits WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 4; $i++)
        {
            $Output["ktt-{$i}"] = 0;   // Time played as kit
            if($extended)
                $Output["kkls-{$i}"] = 0;
        }
    }
    else
    {
        for ($i = 0; $i < 4; $i++)
        {
            // Add data
            $Output["ktt-{$i}"] = $row["time{$i}"];
            if($extended)
                $Output["kkls-{$i}"] = $row["kills{$i}"];
        }
    }
}

/**
 * Adds the weapon data to the current output
 *
 * @param string|int $pid The player ID
 * @param mixed[] $Output [Reference Variable]
 * @param bool $extended
 */
function addWeaponData($pid, &$Output, $extended = false)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Vehicles
    $result = $connection->query("SELECT * FROM weapons WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 32; $i++)
        {
            $Output["wkls-{$i}"] = 0;   // Weapon Kills
            if($extended)
            {
                $Output["wtp-{$i}"] = 0;    // Weapon Time played
                $Output["wtpk-{$i}"] = 0;   // (Time per kill)??
                $Output["wdths-{$i}"] = 0;  // Deaths by weapon
                $Output["wshts-{$i}"] = 0;  // Shots
                $Output["whts-{$i}"] = 0;   // Hits
                $Output["waccu-{$i}"] = 0;  // Accuracy
                $Output["wkdr-{$i}"] = 0;   // Kill / Death ration
            }
        }
    }
    else
    {
        for ($i = 0; $i < 32; $i++)
        {
            // Add data
            $Output["wkls-{$i}"] = $row["kills{$i}"];
            if($extended)
            {
                $Acc = ($row["hit{$i}"] == 0) ? 0 : @round(($row["hit{$i}"] / $row["fired{$i}"]), 3);
                $Output["wtp-{$i}"] = $row["time{$i}"];
                $Output["wtpk-{$i}"] = 0;
                $Output["wdths-{$i}"] = $row["deaths{$i}"];
                $Output["wshts-{$i}"] = $row["fired{$i}"];
                $Output["whts-{$i}"] = $row["hit{$i}"];
                $Output["waccu-{$i}"] = $Acc;
                $Output["wkdr-{$i}"] = getKdr($row["kills{$i}"], $row["deaths{$i}"]);
            }
        }
    }
}

/**
 * Adds the equipment data to the current output
 *
 * @param string|int $pid The player ID
 * @param mixed[] $Output [Reference Variable]
 */
function addEquipmentData($pid, &$Output)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Vehicles
    $result = $connection->query("SELECT * FROM equipment WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 17; $i++)
        {
            $Output["etp-{$i}"] = 0;
            $Output["etpk-{$i}"] = 0;
        }
    }
    else
    {
        for ($i = 0; $i < 17; $i++)
        {
            // Add data
            $Output["etp-{$i}"] = $row["time{$i}"];
            $Output["etpk-{$i}"] = $row["timek{$i}"];
        }
    }
}

/**
 * Adds army data to the current output
 *
 * @param string|int $pid The player ID
 * @param mixed[] $Output [Reference Variable]
 */
function addArmyData($pid, &$Output)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Fetch Army Data
    $result = $connection->query("SELECT * FROM army WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 2; $i++)
        {
            $Output["attp-{$i}"] = 0;
            $Output["awin-{$i}"] = 0;
        }
    }
    else
    {
        for ($i = 0; $i < 2; $i++)
        {
            // Add data
            $Output["attp-{$i}"] = $row["time{$i}"];
            $Output["awin-{$i}"] = $row["win{$i}"];
        }
    }
}

/**
 * Adds game mode data to the current output
 *
 * @param string|int $pid The player ID
 * @param mixed[] $Output [Reference Variable]
 */
function addGamemodeData($pid, &$Output)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Fetch Army Data
    $result = $connection->query("SELECT * FROM game_mode WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 7; $i++)
        {
            $Output["tgpm-{$i}"] = 0;
            $Output["kgpm-{$i}"] = 0;
            $Output["bksgpm-{$i}"] = 0;
            $Output["ctgpm-{$i}"] = 0;
            $Output["csgpm-{$i}"] = 0;
            $Output["trpm-{$i}"] = 0;
        }
    }
    else
    {
        for ($i = 0; $i < 7; $i++)
        {
            // Add data
            $Output["tgpm-{$i}"] = $row["tgpm{$i}"];
            $Output["kgpm-{$i}"] = $row["kgpm{$i}"];
            $Output["bksgpm-{$i}"] = $row["bksgpm{$i}"];
            $Output["ctgpm-{$i}"] = $row["ctgpm{$i}"];
            $Output["csgpm-{$i}"] = $row["csgpm{$i}"];
            $Output["trpm-{$i}"] = $row["trpm{$i}"];
        }
    }
}

/**
 * Returns the Kill / Death Ration
 *
 * @param $kills
 * @param $deaths
 *
 * @return float|int
 */
function getKdr($kills, $deaths)
{
    return (($kills == 0) ? 0 : @round(($kills / $deaths), 3));
}