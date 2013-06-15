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

// No direct access
if(!defined("BF2_ADMIN"))
    die("No Direct Access");

// Prepare output
$Response = new System\AspResponse();

// Make sure we have a valid PID
$pid = (isset($_GET['pid'])) ? intval($_GET['pid']) : false;
if(!$pid) 
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Connect to the database
    $connection = System\Database::GetConnection("stats");

    // Setup some vars
    $earned = 0;
    $availunlocks = 0;

    switch((int) System\Config::Get('game_unlocks'))
    {
        case 0:
            // Get Player Data
            $result = $connection->query("SELECT `name`, `score`, `rank`, `usedunlocks` FROM `player` WHERE `id` = {$pid}");
            if(!($result instanceof PDOStatement) || !($row = $result->fetch()))
            {
                $Response->writeHeaderLine("pid", "nick", "asof");
                $Response->writeDataLine($pid, "No_Player", time());
                $Response->writeHeaderLine("enlisted", "officer");
                $Response->writeDataLine(0, 0);
                $Response->writeHeaderLine("id", "state");
                for ($i = 11; $i < 100; $i += 11)
                    $Response->writeDataLine($i, 'n');
                for ($i = 111; $i < 556; $i += 111)
                    $Response->writeDataLine($i, 'n');

                $Response->send();
            }
            else
            {
                $nick = $row['name'];
                $rank = $row['rank'];

                // Determine Earned Unlocks due to Rank
                $rankunlocks = getRankUnlocks($rank);

                // Determine Bonus Unlocks due to Kit Badges
                $bonusunlocks = getBonusUnlocks($pid, $rank, $connection);

                // Available Unlocks
                $availunlocks = $rankunlocks + $bonusunlocks;

                // Check Used Unlocks
                $query = "SELECT COUNT(`id`) AS `count` FROM `unlocks` WHERE (`id` = {$pid}) AND (`state` = 's')";
                $result = $connection->query($query);
                if($result instanceof PDOStatement)
                {
                    $usedunlocks = $result->fetchColumn();

                    // Determine total unlocks available
                    $availunlocks -= $usedunlocks;

                    // Update Unlocks Data
                    $query = "UPDATE player SET availunlocks = {$availunlocks}, usedunlocks = {$usedunlocks} WHERE id = {$pid}";
                    $connection->exec($query);
                }

                // Prepare output
                $Response->writeHeaderLine("pid", "nick", "asof");
                $Response->writeDataLine($pid, $nick, time());
                $Response->writeHeaderLine("enlisted", "officer");
                $Response->writeDataLine($availunlocks, 0);
                $Response->writeHeaderLine("id", "state");

                // Get our current unlocks
                $unlocks = array();
                $query = "SELECT kit, state FROM unlocks WHERE id={$pid} ORDER BY kit ASC";
                $result = $connection->query($query);
                if($result instanceof PDOStatement && ($row = $result->fetch()))
                {
                    do {
                        if((int)$row['kit'] < 78)
                            $Response->writeDataLine($row['kit'], $row['state']);

                        $unlocks[$row['kit']] = $row['state'];
                    }
                    while($row = $result->fetch());

                    // Check for SF unlocks
                    checkUnlock(88, 22, $unlocks, $Response);
                    checkUnlock(99, 33, $unlocks, $Response);
                    checkUnlock(111, 44, $unlocks, $Response);
                    checkUnlock(222, 55, $unlocks, $Response);
                    checkUnlock(333, 66, $unlocks, $Response);
                    checkUnlock(444, 11, $unlocks, $Response);
                    checkUnlock(555, 77, $unlocks, $Response);
                }
                else
                {
                    for ($i = 11; $i < 80; $i += 11)
                    {
                        $Response->writeDataLine($i, 'n');
                        $unlocks[$row['kit']] = 'n';
                    }
                }

                // Send response
                $Response->send();
            }
            break;
        case 1:
            $Response->writeHeaderLine("pid", "nick", "asof");
            $Response->writeDataLine($pid, "All_Unlocks", time());
            $Response->writeHeaderLine("enlisted", "officer");
            $Response->writeDataLine(0, 0);
            $Response->writeHeaderLine("id", "state");
            for ($i = 11; $i < 100; $i += 11)
                $Response->writeDataLine($i, 's');
            for ($i = 111; $i < 556; $i += 111)
                $Response->writeDataLine($i, 's');

            $Response->send();
            break;
        default:
            $Response->writeHeaderLine("pid", "nick", "asof");
            $Response->writeDataLine($pid, "No_Player", time());
            $Response->writeHeaderLine("enlisted", "officer");
            $Response->writeDataLine(0, 0);
            $Response->writeHeaderLine("id", "state");
            for ($i = 11; $i < 100; $i += 11)
                $Response->writeDataLine($i, 'n');
            for ($i = 111; $i < 556; $i += 111)
                $Response->writeDataLine($i, 'n');

            $Response->send();
            break;
    }
}

/**
 * This method analysis a players current unlocks, and determines if
 * the qualify for / earned a special forces unlock
 *
 * @param int $want The wanted SF unlock ID
 * @param int $need The needed vanilla unlock to have the $want unlock
 * @param string[] $unlocks An array of current unlocks
 * @param \System\AspResponse $Response The response object
 */
function checkUnlock($want, $need, $unlocks, System\AspResponse $Response)
{
    // Only if the vanilla unlock is unlocked
    if(isset($unlocks[$need]) && $unlocks[$need] == 's')
    {
        // Has the SF unlocked been used?
        $keep = (isset($unlocks[$want]) && $unlocks[$want] == 's') ? 's' : 'n';
        $Response->writeDataLine($want, $keep);
    }
}

/**
 * This method returns the number of unlocks due to rank
 *
 * @param int $rank The players current rank
 *
 * @return int The amount of unlocks due to rank
 */
function getRankUnlocks($rank) 
{
	// Determine Earned Unlocks due to Rank
	if ($rank >= 9) {$rankunlocks = 7;}          // Unlock7 => Master Gunnery Sergeant
	elseif ($rank >= 7) {$rankunlocks = 6;}      // Unlock6 => Master Sergeant
	elseif ($rank >= 6) {$rankunlocks = 5;}      // Unlock5 => Gunnery Sergeant
	elseif ($rank >= 5) {$rankunlocks = 4;}      // Unlock4 => Staff Sergeant
	elseif ($rank >= 4) {$rankunlocks = 3;}      // Unlock3 => Sergeant
	elseif ($rank >= 3) {$rankunlocks = 2;}      // Unlock2 => Corporal
	elseif ($rank >= 2) {$rankunlocks = 1;}      // Unlock1 => Lance Corporal
	else {$rankunlocks = 0;}
	return $rankunlocks;
}

/**
 * This method returns the amount of bonus unlocks due to
 * earned badges
 *
 * @param int $pid The player ID
 * @param int $rank The players rank
 * @param \System\Database\DbConnection $connection
 *
 * @return int|string
 */
function getBonusUnlocks($pid, $rank, $connection)
{
	// Check if Minimum Rank Unlocks obtained
	if ($rank < System\Config::Get('game_unlocks_bonus_min'))
		return 0;
	
	// Are bonus Unlocks available?
	if(!System\Config::Get('game_unlocks_bonus'))
		return 0;
	
	// Define Kit Badges Array
	$kitbadges = array(
		"1031119",		// Assault
		"1031120",		// Anti-tank
		"1031109",		// Sniper
		"1031115",		// Spec-Ops
		"1031121",		// Support
		"1031105",		// Engineer
		"1031113"		// Medic
	);
	
	// Count number of kit badges obtained
	$checkawds = "'" . implode("','", $kitbadges) . "'";
	$query = "SELECT COUNT(`id`) AS `count` FROM `awards` WHERE `id` = {$pid} AND (`awd` IN ({$checkawds}) AND `level` = ".
        System\Config::Get('game_unlocks_bonus').")";
	$result = $connection->query($query);
    return ($result instanceof PDOStatement) ? $result->fetchColumn() : 0;
}