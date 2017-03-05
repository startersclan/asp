<?php
/*
    Copyright (C) 2006-2017 BF2Statistics

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

// Namespace
namespace System;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Prepare output
$Response = new AspResponse();

// Make sure we have a valid PID. Casting to int will sanitize input
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;

// Player does not exist?
if ($pid == 0)
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Connect to the database
    $connection = Database::GetConnection("stats");

    switch ((int)Config::Get('game_unlocks'))
    {
        case 0:
            // Get Player Data
            $result = $connection->query("SELECT `name`, `score`, `rank` FROM `player` WHERE `id` = {$pid}");
            if (!($row = $result->fetch()))
            {
                $Response->writeHeaderLine("pid", "nick", "asof");
                $Response->writeDataLine($pid, "No_Player", time());
                $Response->writeHeaderLine("enlisted", "officer");
                $Response->writeDataLine(0, 0);
                $Response->writeHeaderLine("id", "state");
                $Response->send();
            }
            else
            {
                // Cast to an int
                $rank = (int)$row['rank'];

                // Determine Earned Unlocks due to Rank
                $rankunlocks = getRankUnlocks($rank);

                // Determine Bonus Unlocks due to Kit Badges
                $bonusunlocks = getBonusUnlocks($pid, $rank, $connection);

                // Check Used Unlocks
                $query = "SELECT COUNT(`pid`) AS `count` FROM `player_unlock` WHERE `pid` = {$pid}";
                $result = $connection->query($query);
                $usedunlocks = (int)$result->fetchColumn(0);

                // Determine available unlocks count
                $availunlocks = max(0, ($rankunlocks + $bonusunlocks) - $usedunlocks);

                // Prepare output
                $Response->writeHeaderLine("pid", "nick", "asof");
                $Response->writeDataLine($pid, $row['name'], time());
                $Response->writeHeaderLine("enlisted", "officer");
                $Response->writeDataLine($availunlocks, 0);
                $Response->writeHeaderLine("id", "state");

                // Get players current unlocks
                $query = "SELECT unlockid FROM `player_unlock` WHERE `pid`={$pid} ORDER BY `unlockid` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
					$Response->writeDataLine($row['unlockid'], 's');

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

            // Get all current unlocks
            $result = $connection->query("SELECT `id` FROM `unlock` ORDER BY `id` ASC");
            while ($row = $result->fetch())
				$Response->writeDataLine($row['id'], 's');

            $Response->send();
            break;
        default:
            $Response->writeHeaderLine("pid", "nick", "asof");
            $Response->writeDataLine($pid, "No_Player", time());
            $Response->writeHeaderLine("enlisted", "officer");
            $Response->writeDataLine(0, 0);
            $Response->writeHeaderLine("id", "state");

            // Get all current unlocks
            $query = "SELECT `id` FROM `unlock` ORDER BY `id` ASC";
            $result = $connection->query($query);
            while ($row = $result->fetch())
				$Response->writeDataLine($row['id'], 'n');

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
function checkUnlock($want, $need, $unlocks, AspResponse $Response)
{
    // Only if the vanilla unlock is unlocked
    if (isset($unlocks[$need]) && $unlocks[$need] == 's')
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
    if ($rank >= 9)
    {
        return 7;
    }
    elseif ($rank >= 7)
    {
        return 6;
    }
    elseif ($rank >= 6)
    {
        return 5;
    }
    elseif ($rank >= 5)
    {
        return 4;
    }
    elseif ($rank >= 4)
    {
        return 3;
    }
    elseif ($rank >= 3)
    {
        return 2;
    }
    elseif ($rank >= 2)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

/**
 * This method returns the amount of bonus unlocks due to
 * earned badges
 *
 * @param int $pid The player ID
 * @param int $rank The players rank
 * @param \System\Database\DbConnection $connection
 *
 * @return int
 */
function getBonusUnlocks($pid, $rank, $connection)
{
    // Check if Minimum Rank Unlocks obtained
    if ($rank < Config::Get('game_unlocks_bonus_min'))
        return 0;

    // Are bonus Unlocks available?
    $level = (int)Config::Get('game_unlocks_bonus');
    if ($level == 0)
        return 0;

    // Define Kit Badges Array
    $kitbadges = array(
        "1031119",        // Assault
        "1031120",        // Anti-tank
        "1031109",        // Sniper
        "1031115",        // Spec-Ops
        "1031121",        // Support
        "1031105",        // Engineer
        "1031113"         // Medic
    );

    // Count number of kit badges obtained
    $checkawds = "'" . implode("','", $kitbadges) . "'";
    $query = <<<SQL
SELECT COUNT(`id`) AS `count` 
FROM `player_award` 
WHERE `pid` = $pid 
  AND (
    `id` IN ($checkawds) 
      AND `level` >= $level
  );
SQL;

    $result = $connection->query($query);
    return (int)$result->fetchColumn(0);
}