<?php
/*
    Copyright (C) 2006-2021 BF2Statistics

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

// Make sure we have an ID and PID
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;
$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

// Check user input
if ($pid == 0 || $id == 0)
{
    $Response->responseError(true, 107);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Connect to the database
    $connection = Database::GetConnection("stats");

    // Check if unlock is already selected first!
    $query = "SELECT * FROM `player_unlock` WHERE `player_id` = $pid AND `unlock_id` = $id LIMIT 1";
    $result = $connection->query($query)->fetch();
    if (empty($result))
    {
        // Grab player rank
        $rank = $connection->query("SELECT rank_id FROM `player` WHERE `id` = $pid LIMIT 1")->fetchColumn(0);
        if ($rank === false)
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
        }

        // Case to integer
        $rank = (int)$rank;

        // Determine Earned Unlocks due to Rank
        $unlocks = getUnlockCountByRank($rank);

        // Determine Bonus Unlocks due to Kit Badges
        $unlocks += getBonusUnlockCountByBadges($pid, $rank, $connection);

        // Check Used Unlocks
        $query = "SELECT COUNT(`player_id`) AS `count` FROM `player_unlock` WHERE `player_id` = {$pid}";
        $used = (int)$connection->query($query)->fetchColumn(0);

        // Determine available unlocks count
        $available = max(0, $unlocks - $used);

        // Finally, if the user HAS available unlocks, let them choose this one
        if ($available > 0)
        {
            $time = time();
            $connection->exec("INSERT INTO player_unlock VALUES ($pid, $id, $time)");
            $Response->writeLine("OK");
            $Response->send();
        }
        else
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "No Unlocks Available!");
            $Response->send();
        }
    }
    else
    {
        $Response->writeLine("OK");
        $Response->send();
    }
}

/**
 * This method returns the number of unlocks due to rank
 *
 * @param int $rank The players current rank
 *
 * @return int The amount of unlocks due to rank
 */
function getUnlockCountByRank($rank)
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
function getBonusUnlockCountByBadges($pid, $rank, $connection)
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
        1031119,        // Assault
        1031120,        // Anti-tank
        1031109,        // Sniper
        1031115,        // Spec-Ops
        1031121,        // Support
        1031105,        // Engineer
        1031113         // Medic
    );

    // Count number of kit badges obtained
    $checkawds = implode(",", $kitbadges);
    $query = <<<SQL
SELECT COUNT(`award_id`) AS `count` 
FROM `player_award` 
WHERE `player_id` = $pid 
  AND (
    `award_id` IN ($checkawds) 
      AND `level` >= $level
  );
SQL;

    return (int)$connection->query($query)->fetchColumn(0);
}