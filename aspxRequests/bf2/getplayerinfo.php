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

	----------------------------------------------------------------------------------------

	URL: http://bf2tech.org/index.php/BF2_Statistics
*/

// No direct access
if(!defined("BF2_ADMIN"))
    die("No Direct Access");

// Import Classes
use System\AspResponse;
use System\Config;
use System\Database;

// Prepare output
$Response = new AspResponse();

// Get database connection
$connection = Database::GetConnection("stats");

// Make sure we have the required Params and they are valid!
$pid = (isset($_GET['pid'])) ? intval($_GET['pid']) : false;
$info = (isset($_GET['info'])) ? $_GET['info'] : '';
$transpose = (isset($_GET['transpose'])) ? intval($_GET['transpose']) : 0;
if ($pid == false || empty($info))
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
	// Player info
	//'Reworked' for MNG stats =)
	//omero, 2006-04-15 
	//split up an otherwise long and unreadable line 
	$requiredKeys = "per*,cmb*,twsc,cpcp,cacp,dfcp,kila,heal,rviv,rsup,rpar," .
		"tgte,dkas,dsab,cdsc,rank,cmsc,kick,kill,deth,suic,ospm," .
		"klpm,klpr,dtpr,bksk,wdsk,bbrs,tcdr,ban,dtpm,lbtl,osaa," .
		"vrk,tsql,tsqm,tlwf,mvks,vmks,mvn*,vmr*,fkit,fmap,fveh,fwea," .
		"wtm-,wkl-,wdt-,wac-,wkd-,vtm-,vkl-,vdt-,vkd-,vkr-," .
		"atm-,awn-,alo-,abr-,ktm-,kkl-,kdt-,kkd-";
		
	// Prevent SQl Injection  (phase 1)
	$pid = intval($pid);
	
	// Make sure we have all the required keys
	if(strpos($info,$requiredKeys) !== false)
	{
		// Fetch Player Data
		$result = $connection->query("SELECT * FROM player WHERE id = {$pid}");
		if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
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
			if(strpos($info, 'mng-') !== false)
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
                'smsc' => $row['skillscore'],
                'osaa' => 0, // Overall small arms accuracy
                'kill' => $row['kills'],
                'kila' => $row['damageassists'],
                'deth' => $row['deaths'],
                'suic' => $row['suicides'],
                'bksk' => $row['killstreak'],
                'wdsk' => $row['deathstreak'],
                'tvcr' => null, // Top Victim
                'topr' => null, // Top Opponent
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
                'mvns' => null, // Top Victim name
                'mvrs' => 0, // Top Victim rank
                'vmns' => null, // Top opponent name
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

            // Add Fav Victim and Opponent data
            addPlayerTopVitcimAndOpp($Output, $pid);

            // Add data and spit out the response
            $Response->writeHeaderDataArray($Output);
            $Response->send($transpose);
		}
	}
	// Time info
	elseif($info == 'ktm-,vtm-,wtm-,mtm-')
	{
		$kit = ($_GET['kit']) ? $_GET['kit'] : 0;
		$vehicle = ($_GET['vehicle']) ? $_GET['vehicle'] : 0;
		$weapon = ($_GET['weapon']) ? $_GET['weapon'] : 0;
		$map = ($_GET['map']) ? $_GET['map'] : 0;
        $name = null;

        // Fetch Player, make sure he exists
        $query = "SELECT `name` FROM `player` WHERE `id` = {$pid}";
        $result = $connection->query($query);
        if(!($result instanceof PDOStatement) || !($name = $result->fetchColumn()))
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
        }

        // Prepare response
        $Response->writeHeaderLine("asof");
        $Response->writeDataLine(time());
        $Response->writeHeaderLine("pid", "nick", "ktm-{$kit}", "vtm-{$vehicle}", "wtm-{$weapon}", "mtm-{$map}");

		// Kits
		$query = "SELECT `time{$kit}` FROM `kits` WHERE `id` = {$pid}";
		$result = $connection->query($query);
		if(!($result instanceof PDOStatement) || !($kitt = $result->fetchColumn()))
			$kitt = 0;

		// Vehicles
		$query = "SELECT `time{$vehicle}` FROM `vehicles` WHERE `id` = {$pid}";
		$result = $connection->query($query);
		if(!($result instanceof PDOStatement) || !($vehiclet = $result->fetchColumn()))
			$vehiclet = 0;
	
		// Weapons 
		$query = "SELECT GREATEST(`time0`, `time1`, `time2`, `time3`, `time4`, `time5`, `time6`, `time7`, `time8`, `knifetime`, `shockpadtime`, 
			(`c4time`+`claymoretime`+`atminetime`), `handgrenadetime`) FROM `weapons` WHERE `id` = {$pid}";
		$result = $connection->query($query);
		if(!($result instanceof PDOStatement) || !($weapont = $result->fetchColumn()))
			$weapont = 0;
   
		// Maps
		$query = "SELECT `time` FROM `maps` WHERE (`id` = {$pid}) AND (`mapid` = {$map})";
		$result = $connection->query($query);
		if(!($result instanceof PDOStatement) || !($mapt = $result->fetchColumn()))
			$mapt = 0;

        $Response->writeDataLine($pid, $name, $kitt, $vehiclet, $weapont, $mapt);
        $Response->send($transpose);
	}
	// Map info (added support for mbs- & mws-)
	elseif ($info == 'mtm-,mwn-,mls-' || $info == 'mtm-,mwn-,mls-,mbs-,mws-' || $info == 'mtm-,mwn-,mls-,cmap-' || $info == 'mtm-,mwn-,mls-,mbs-,mws-,cmap-')
	{
		// Make sure Player exists
		$query = "SELECT `name` FROM `player` WHERE `id` = {$pid}";
		$result = $connection->query($query);
        $name = null;
		if(!($result instanceof PDOStatement) || !($name = $result->fetchColumn()))
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
        $Output = array(
            'pid' => $pid,
            'nick' => $name
        );

        // Extended data?
        $Extended = (strpos($info, "mbs-") !== false);

        // Vanilla BF2 Maps (Middle East Theatre)
        for($i = 0; $i < 7; $i++)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // Vanilla BF2 Maps (Asian Theatre)
        for($i = 100; $i < 106; $i++)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // Special BF2 Maps (Wake Island, Highway tampa)
        for($i = 601; $i < 603; $i++)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // Special forces maps
        for($i = 300; $i < 308; $i++)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // More Maps (smoke screen, Taraba Quarry, Jalalabad)
        for($i = 10; $i < 13; $i++)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // Armored Fury Maps
        for($i = 200; $i < 203; $i++)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // Great wall, Operation Blue Pearl
        for($i = 110; $i < 130; $i += 10)
        {
            $Output["mtm-{$i}"] = $Output["mwn-{$i}"] = $Output["mls-{$i}"] = 0;
            if($Extended) $Output["mbs-{$i}"] = $Output["mws-{$i}"] = 0;
        }

        // Prepare where statement
        $where = (strpos($info, "cmap-") !== false)
            ? "`id` = {$pid}"
            : "`id` = {$pid} AND `mapid` < ". Config::Get("game_custom_mapid");

        // Fetch map data from DB
		$query = "SELECT * FROM `maps` WHERE {$where}";
		$result = $connection->query($query);
		if($result instanceof PDOStatement && ($row = $result->fetch()))
		{
			do {
                $i = $row['mapid'];
                $Output["mtm-{$i}"] = $row['time'];
                $Output["mwn-{$i}"] = $row['win'];
                $Output["mls-{$i}"] = $row['loss'];
                if($Extended)
                {
                    $Output["mbs-{$i}"] = $row['best'];
                    $Output["mws-{$i}"] = $row['worst'];
                }
			}
			while($row = $result->fetch());
		}

		// Output map data
        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
	}
	elseif ($info == 'rank')
	{
		$query = "SELECT `id`, `name`, `rank`, `chng`, `decr` FROM `player` WHERE `id` = {$pid}";
		$result = $connection->query($query);
		if(!($result instanceof PDOStatement) || !($row = $result->fetch())) 
		{
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
		}
		else
		{
			// Update
			$connection->exec("UPDATE `player` SET `chng` = 0, `decr` = 0 WHERE `id` = {$pid}");
            $Response->writeHeaderLine("pid", "nick", "rank", "chng", "decr");
            $Response->writeDataLine($row['id'], $row['name'], $row['rank'], $row['chng'], $row['decr']);
            $Response->send($transpose);
		}
	}
	elseif (checkGameServerRequest($info))
	{
		// NOTE: xpack and bf2 have same return
		// Make sure the Player exists
        $row = array();
		$result = $connection->query("SELECT * FROM `player` WHERE `id` = {$pid}");
		if(!($result instanceof PDOStatement) || !($row = $result->fetch()))  
		{
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
		}

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
		$result = $connection->query("SELECT * FROM `weapons` WHERE `id` = {$pid}");
		if(!($result instanceof PDOStatement) || !($roww = $result->fetch()))  
		{
			for ($i = 0; $i < 14; $i++)
                $Output["wkl-{$i}"] = '0';

            // Grappling hook, Tactical, and Zipline
            if(strpos($info, 'de-') !== false)
            {
                $Output['de-6'] = 0;
                $Output['de-7'] = 0;
                $Output['de-8'] = 0;
            }
		}
        else
        {
            for ($i = 0; $i < 9; $i++)
                $Output["wkl-{$i}"] = $roww["kills{$i}"];

            $Output["wkl-9"] = $roww['knifekills'];
            $Output["wkl-10"] = $roww['shockpadkills'];
            $Output["wkl-11"] = $roww['c4kills'] + $roww['atminekills'] + $roww['claymorekills'];
            $Output["wkl-12"] = $roww['handgrenadekills'];
            $Output["wkl-13"] = 0;

            // Grappling hook, Tactical, and Zipline
            if(strpos($info, 'de-') !== false)
            {
                $Output['de-6'] = $roww['tacticaldeployed'];
                $Output['de-7'] = $roww['grapplinghookdeployed'];
                $Output['de-8'] = $roww['ziplinedeployed'];
            }
        }

		// Kits
		$result = $connection->query("SELECT * FROM `kits` WHERE `id` = {$pid}");
		if(!($result instanceof PDOStatement) || !($rowk = $result->fetch()))
        {
            for ($i = 0; $i < 7; $i++)
            {
                $Output["ktm-$i"] = 0; // Time
                $Output["kkl-$i"] = 0; // Kills
            }
        }
        else
        {
            for ($i = 0; $i < 7; $i++)
            {
                $Output["ktm-$i"] = $rowk["time{$i}"]; // Time
                $Output["kkl-$i"] = $rowk["kills{$i}"]; // Kills
            }
        }

		// Vehicles
		$result = $connection->query("SELECT * FROM `vehicles` WHERE `id` = {$pid}");
		if(!($result instanceof PDOStatement) || !($rowv = $result->fetch()))
		{
			for ($i = 0; $i < 7; $i++)
			{
                $Output["vtm-$i"] = 0; // Time
                $Output["vkl-$i"] = 0; // Kills
			}
		}
        else
        {
            for ($i = 0; $i < 7; $i++)
            {
                $Output["vtm-$i"] = $rowv["time{$i}"]; // Time
                $Output["vkl-$i"] = $rowv["kills{$i}"]; // Kills
            }
        }
		
		// Army
		$result = $connection->query("SELECT * FROM `army` WHERE id = {$pid}");
		if(!($result instanceof PDOStatement) || !($rowa = $result->fetch()))  
		{
			for ($i = 0; $i < 14; $i++)
			{
				$Output["atm-{$i}"] = '0';
				$Output["abr-{$i}"] = '0';
				$Output["awn-{$i}"] = '0';
			}
		}
        else
        {
            for ($i = 0; $i < 14; $i++)
            {
                $Output["atm-{$i}"] = $rowa["time{$i}"];
                $Output["abr-{$i}"] = $rowa["best{$i}"];
                $Output["awn-{$i}"] = $rowa["win{$i}"];
            }
        }
		
		#vac-
        for($i = 0; $i < 7; $i++)
		    $Output["vac-{$i}"] = 0;

        $Response->writeHeaderDataArray($Output);
        $Response->send($transpose);
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

    // Assign some vars
    $fav = $favTime = $tempAcc = $Acc = 0;

    // Weapons
    $result = $connection->query("SELECT * FROM weapons WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 14; $i++)
        {
            $Output["wtm-$i"] = 0; // Time
            $Output["wkl-$i"] = 0; // Kills
            $Output["wdt-$i"] = 0; // Deaths
            $Output["wac-$i"] = 0; // Accuracy
            $Output["wkd-$i"] = 0; // K/D Ratio
        }
    }
    else
    {
        // Basic weapons
        for ($i = 0; $i < 9; $i++)
        {
            if($row["time{$i}"] > $favTime)
            {
                $fav = $i;
                $favTime = (int) $row["time{$i}"];
            }

            // Get integer values
            $kills  = (int) $row["kills{$i}"];
            $deaths = (int) $row["deaths{$i}"];
            $hits   = (int) $row["hit{$i}"];
            $fired  = (int) $row["fired{$i}"];
            $acc = ($fired != 0 && $hits != 0)
                ? round( ($hits / $fired) * 100, 0)
                : 0;
            $tempAcc += $acc;

            // Set weapon data
            $Output["wtm-$i"] = $row["time{$i}"];   // Time
            $Output["wkl-$i"] = $kills;     // Kills
            $Output["wdt-$i"] = $deaths;    // Deaths
            $Output["wac-$i"] = $acc;       // Accuracy

            // K/D Ratio
            if($deaths != 0)
            {
                $den = denominator($kills, $deaths);
                $Output["wkd-$i"] = ($kills / $den) . ':' . ($deaths / $den);
            }
            else
                $Output["wkd-$i"] = $kills . ':0';
        }

        // == Knife == //
        $kills  = (int) $row["knifekills"];
        $deaths = (int) $row["knifedeaths"];
        $hits   = (int) $row["knifehit"];
        $fired  = (int) $row["knifefired"];
        $acc = ($fired != 0 && $hits != 0)
            ? round( ($hits / $fired) * 100, 0)
            : 0;
        $tempAcc += $acc;

        // Favorite
        if($row["knifetime"] > $favTime)
        {
            $fav = 9;
            $favTime = (int) $row["knifetime"];
        }

        // Set weapon data
        $Output["wtm-9"] = $row["knifetime"];
        $Output["wkl-9"] = $kills;
        $Output["wdt-9"] = $deaths;
        $Output["wac-9"] = $acc;

        // K/D Ratio
        if($deaths != 0)
        {
            $den = denominator($kills, $deaths);
            $Output["wkd-9"] = ($kills / $den) . ':' . ($deaths / $den);
        }
        else
            $Output["wkd-9"] = $kills . ':0';

        // == Shock pad == //
        $kills  = (int) $row["shockpadkills"];
        $deaths = (int) $row["shockpaddeaths"];
        $hits   = (int) $row["shockpadhit"];
        $fired  = (int) $row["shockpadfired"];
        $acc = ($fired != 0 && $hits != 0)
            ? round( ($hits / $fired) * 100, 0)
            : 0;
        $tempAcc += $acc;

        // Favorite
        if($row["shockpadtime"] > $favTime)
        {
            $fav = 10;
            $favTime = (int) $row["shockpadtime"];
        }

        // Set weapon data
        $Output["wtm-10"] = $row["shockpadtime"];
        $Output["wkl-10"] = $kills;
        $Output["wdt-10"] = $deaths;
        $Output["wac-10"] = $acc;

        // K/D Ratio
        if($deaths != 0)
        {
            $den = denominator($kills, $deaths);
            $Output["wkd-10"] = ($kills / $den) . ':' . ($deaths / $den);
        }
        else
            $Output["wkd-10"] = $kills . ':0';

        // == Explosives == //
        $time = $row["c4time"] + $row["claymoretime"] + $row["atminetime"];
        $kills  = $row["c4kills"] + $row["claymorekills"] + $row["atminekills"];
        $deaths = $row["c4deaths"] + $row["claymoredeaths"] + $row["atminedeaths"];
        $hits   = $row["c4hit"] + $row["claymorehit"] + $row["atminehit"];
        $fired  = $row["c4fired"] + $row["claymorefired"] + $row["atminefired"];
        $acc = ($fired != 0 && $hits != 0)
            ? round( ($hits / $fired) * 100, 0)
            : 0;
        $tempAcc += $acc;

        // Favorite
        if($time > $favTime)
        {
            $fav = 11;
            $favTime = (int) $time;
        }

        // Set weapon data
        $Output["wtm-11"] = $time;
        $Output["wkl-11"] = $kills;
        $Output["wdt-11"] = $deaths;
        $Output["wac-11"] = $acc;

        // K/D Ratio
        if($deaths != 0)
        {
            $den = denominator($kills, $deaths);
            $Output["wkd-11"] = ($kills / $den) . ':' . ($deaths / $den);
        }
        else
            $Output["wkd-11"] = $kills . ':0';

        // == Hand grenade == //
        $kills  = (int) $row["handgrenadekills"];
        $deaths = (int) $row["handgrenadedeaths"];
        $hits   = (int) $row["handgrenadehit"];
        $fired  = (int) $row["handgrenadefired"];
        $acc = ($fired != 0 && $hits != 0)
            ? round( ($hits / $fired) * 100, 0)
            : 0;
        $tempAcc += $acc;

        // Favorite
        if($row["handgrenadetime"] > $favTime)
            $fav = 12;

        // Set weapon data
        $Output["wtm-12"] = $row["handgrenadetime"];
        $Output["wkl-12"] = $kills;
        $Output["wdt-12"] = $deaths;
        $Output["wac-12"] = $acc;

        // K/D Ratio
        if($deaths != 0)
        {
            $den = denominator($kills, $deaths);
            $Output["wkd-12"] = ($kills / $den) . ':' . ($deaths / $den);
        }
        else
            $Output["wkd-12"] = $kills . ':0';

        // Add weapon 13
        $Output["wtm-13"] = 0; // Time
        $Output["wkl-13"] = 0; // Kills
        $Output["wdt-13"] = 0; // Deaths
        $Output["wac-13"] = 0; // Accuracy
        $Output["wkd-13"] = 0; // K/D Ratio
    }

    $Ouput['fwea'] = $fav;
    $Ouput['osaa'] = ($tempAcc != 0) ? round($tempAcc / 12, 2) : 0;
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
    $fav = $favTime = $roadKills = 0;

    // Vehicles
    $result = $connection->query("SELECT * FROM vehicles WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 7; $i++)
        {
            $Output["vtm-$i"] = 0; // Time
            $Output["vkl-$i"] = 0; // Kills
            $Output["vdt-$i"] = 0; // Deaths
            $Output["vkd-$i"] = 0; // Kill / Death ratio
            $Output["vkr-$i"] = 0; // Road Kills
        }
    }
    else
    {
        for ($i = 0; $i < 7; $i++)
        {
            // Vars
            $time   = (int) $row["time{$i}"];
            $kills  = (int) $row["kills{$i}"];
            $deaths = (int) $row["deaths{$i}"];
            $roadKills += $row["rk{$i}"];

            // Add data
            $Output["vtm-$i"] = $time;
            $Output["vkl-$i"] = $kills;
            $Output["vdt-$i"] = $deaths;
            $Output["vkr-$i"] = $row["rk{$i}"];

            // K/D Ratio
            if($deaths != 0)
            {
                $den = denominator($kills, $deaths);
                $Output["vkd-{$i}"] = ($kills / $den) . ':' . ($deaths / $den);
            }
            else
                $Output["vkd-{$i}"] = $kills . ':0';

            // Favorite?
            if($time > $favTime)
            {
                $fav = $i;
                $favTime = $time;
            }
        }
    }

    $Output['fveh'] = $fav;
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

    // Weapons
    $result = $connection->query("SELECT * FROM army WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 14; $i++)
        {
            $Output["atm-$i"] = 0; // Time
            $Output["awn-$i"] = 0; // Wins
            $Output["alo-$i"] = 0; // Losses
            $Output["abr-$i"] = 0; // Best round
        }
    }
    else
    {
        for ($i = 0; $i < 14; $i++)
        {
            $Output["atm-$i"] = $row["time{$i}"];
            $Output["awn-$i"] = $row["win{$i}"];
            $Output["alo-$i"] = $row["loss{$i}"];
            $Output["abr-$i"] = $row["best{$i}"];
        }
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
    $fav = $favTime = 0;

    // Weapons
    $result = $connection->query("SELECT * FROM kits WHERE id = {$pid}");
    if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // Player doesn't exist, add defaults
        for ($i = 0; $i < 7; $i++)
        {
            $Output["ktm-$i"] = 0; // Time
            $Output["kkl-$i"] = 0; // Kills
            $Output["kdt-$i"] = 0; // Deaths
            $Output["kkd-$i"] = 0; // K/D Ratio
        }
    }
    else
    {
        for ($i = 0; $i < 7; $i++)
        {
            // Convert some vars to ints
            $time   = (int) $row["time{$i}"];
            $kills  = (int) $row["kills{$i}"];
            $deaths = (int) $row["deaths{$i}"];

            // Favorite
            if($time > $favTime)
            {
                $fav = $i;
                $favTime = $time;
            }

            // Add Data
            $Output["ktm-$i"] = $time;
            $Output["kkl-$i"] = $kills;
            $Output["kdt-$i"] = $deaths;

            // K/D Ratio
            if($deaths != 0)
            {
                $den = denominator($kills, $deaths);
                $Output["kkd-{$i}"] = ($kills / $den) . ':' . ($deaths / $den);
            }
            else
                $Output["kkd-{$i}"] = $kills . ':0';
        }
    }

    $Output['fkit'] = $fav;
}

/**
 * Adds the favorite victim and opponent data to the current output
 *
 * @param mixed[] $Output [Reference Variable]
 * @param string|int $pid The player ID
 */
function addPlayerTopVitcimAndOpp(&$Output, $pid)
{
    // Fetch DB connection
    $connection = Database::GetConnection("stats");

    // Fetch Fav Victim
    $result = $connection->query("SELECT victim, count FROM kills WHERE attacker={$pid} ORDER BY count DESC LIMIT 1");
    if($result instanceof PDOStatement && ($row = $result->fetch()))
    {
        $victim = $row['victim'];
        $count = $row['count'];
        $result = $connection->query("SELECT name, rank FROM player WHERE id={$victim}");
        if($result instanceof PDOStatement && ($row = $result->fetch()))
        {
            $Output['tvcr'] = $victim;
            $Output['mvks'] = $count;
            $Output['mvns'] = $row['name'];
            $Output['mvrs'] = $row['rank'];
        }
    }

    // Fetch Fav Opponent
    $result = $connection->query("SELECT attacker, count FROM kills WHERE victim={$pid} ORDER BY count DESC LIMIT 1");
    if($result instanceof PDOStatement && ($row = $result->fetch()))
    {
        $attacker = $row['attacker'];
        $count = $row['count'];
        $result = $connection->query("SELECT name, rank FROM player WHERE id={$attacker}");
        if($result instanceof PDOStatement && ($row = $result->fetch()))
        {
            $Output['topr'] = $attacker;
            $Output['vmks'] = $count;
            $Output['vmns'] = $row['name'];
            $Output['vmrs'] = $row['rank'];
        }
    }

}

/**
 * Outputs the data into a Key => value format
 * @param string[] $data
 */
function outputTransposedData($data)
{
    // Display in Alternate Format
    $num = 0;
    $transout = "O\n" .
        "H\tD\n" .
        "asof\t" . time() . "\n".
        "H\tD\n";

    foreach ($data as $key => $keyval)
        $transout .= $key . "\t" . $keyval . "\n";

    $num += strlen(preg_replace('/[\t\n]/','',$transout));
    echo rtrim($transout), "\n", "$\t$num\t$";
}


function checkGameServerRequest($info) 
{
	// Checks Game Server Query String
	$complete = true;
	$arr = array('rank','ktm-','dfcp','rpar','vtm-','bksk','scor','wdsk','wkl-','heal','dsab','cdsc','tsql','tsqm',
        'wins','vkl-','twsc','time','kill','rsup','tcdr','vac-');
	for($a = 0; $a < count($arr); $a++)
	{
		if (strpos( $info, $arr[$a]) === false )
			$complete = false;
	}
	return $complete;
}

function denominator($x, $y)
{
	while($y != 0)
	{
		$remainder = $x % $y;
		$x = $y;
		$y = $remainder;
	}
	return abs($x);
}