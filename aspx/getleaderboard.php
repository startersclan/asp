<?php
/*
	Copyright (C) 2006-2018  BF2Statistics

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
$item = null;

// Set response format
$format = (isset($_GET['format'])) ? min(2, abs((int)$_GET['format'])) : 0;
$Response->setResponseFormat($format);

// Cast URL parameters
$type = (isset($_GET['type'])) ? $_GET['type'] : '';
$id = (isset($_GET['id'])) ? $_GET['id'] : '';
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;

// Optional parameters
$after = (isset($_GET['after'])) ? (int)$_GET['after'] : 0;
$before = (isset($_GET['before'])) ? (int)$_GET['before'] : 0;
$pos = (isset($_GET['pos'])) ? (int)$_GET['pos'] : 1;
$min = ($pos - 1) - $before;
$max = $after + 1;

// Correction
if ($min < 0) $min = 0;
if ($max < 0) $max = 0;

// Make sure we have a type, and its valid
if (empty($type))
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

    // Prepare our output header
    $Response->writeHeaderLine("size", "asof");

    if ($type == 'score')
    {
        if ($id == 'overall')
        {
            $result = $connection->query("SELECT COUNT(id) FROM player WHERE score > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "score", "totaltime", "playerrank", "countrycode");

            if (!$pid)
            {
                $query = "SELECT id, name, rank_id, country, time, score FROM player WHERE score > 0
				    ORDER BY score DESC, name DESC LIMIT " . $min . ", " . $max;
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['id'],
                        trim($row['name']),
                        $row['score'],
                        $row['time'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
            else
            {
                $query = "SELECT id, name, rank_id, country, time, score FROM player WHERE id = {$pid}";
                $row = $connection->query($query)->fetch();
                if (!empty($row))
                {
                    $query = "SELECT COUNT(id) FROM player WHERE score > %d";
                    $stmt = $connection->query(vsprintf($query, [$row['score']]));
                    $Response->writeDataLine(
                        ((int)$stmt->fetchColumn(0)) + 1,
                        $row['id'],
                        trim($row['name']),
                        $row['score'],
                        $row['time'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
        }
        elseif ($id == 'commander')
        {
            $result = $connection->query("SELECT COUNT(id) FROM player WHERE cmdscore > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "coscore", "cotime", "playerrank", "countrycode");

            if ($pid == 0)
            {
                $query = "SELECT id, name, rank_id, country, cmdtime, cmdscore FROM player WHERE cmdscore > 0"
                    . " ORDER BY cmdscore DESC LIMIT {$min}, {$max}";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['id'],
                        trim($row['name']),
                        $row['cmdscore'],
                        $row['cmdtime'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
            else
            {
                $query = "SELECT id, name, rank_id, country, cmdtime, cmdscore FROM player WHERE id = {$pid}";
                $row = $connection->query($query)->fetch();
                if (!empty($row))
                {
                    $query = "SELECT COUNT(id) FROM player WHERE cmdscore > %d";
                    $stmt = $connection->query(vsprintf($query, [$row['cmdscore']]));
                    $Response->writeDataLine(
                        ((int)$stmt->fetchColumn(0)) + 1,
                        $row['id'],
                        trim($row['name']),
                        $row['cmdscore'],
                        $row['cmdtime'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
        }
        elseif ($id == 'team')
        {
            $result = $connection->query("SELECT COUNT(id) FROM player WHERE teamscore > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "teamscore", "totaltime", "playerrank", "countrycode");

            if ($pid == 0)
            {
                $query = "SELECT id, name, rank_id, country, time, teamscore FROM player WHERE teamscore > 0
				    ORDER BY teamscore DESC LIMIT " . $min . ", " . $max;
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['id'],
                        trim($row['name']),
                        $row['teamscore'],
                        $row['time'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
            else
            {
                $query = "SELECT id, name, rank_id, country, time, teamscore FROM player WHERE id = {$pid}";
                $row = $connection->query($query)->fetch();
                if (!empty($row))
                {
                    $query = "SELECT COUNT(id) FROM player WHERE teamscore > %d";
                    $stmt = $connection->query(vsprintf($query, [$row['teamscore']]));
                    $Response->writeDataLine(
                        ((int)$stmt->fetchColumn(0)) + 1,
                        $row['id'],
                        trim($row['name']),
                        $row['teamscore'],
                        $row['time'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
        }
        elseif ($id == 'combat')
        {
            $result = $connection->query("SELECT COUNT(id) FROM player WHERE skillscore > 0");
            $Response->writeDataLine($result->fetchColumn(), time());
            $Response->writeHeaderLine("n", "pid", "nick", "score", "totalkills", "totaltime", "playerrank", "countrycode");

            if (!$pid)
            {
                $query = "SELECT id, name, rank_id, country, time, kills, skillscore FROM player WHERE skillscore > 0
				    ORDER BY skillscore DESC LIMIT " . $min . ", " . $max;
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine(
                        $pos++,
                        $row['id'],
                        trim($row['name']),
                        $row['skillscore'],
                        $row['kills'],
                        $row['time'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
            else
            {
                $query = "SELECT id, name, rank_id, country, time, kills, skillscore FROM player WHERE id = {$pid}";
                $row = $connection->query($query)->fetch();
                if (!empty($row))
                {
                    $query = "SELECT COUNT(id) FROM player WHERE skillscore > %d";
                    $stmt = $connection->query(vsprintf($query, [$row['skillscore']]));
                    $Response->writeDataLine(
                        ((int)$stmt->fetchColumn(0)) + 1,
                        $row['id'],
                        trim($row['name']),
                        $row['skillscore'],
                        $row['kills'],
                        $row['time'],
                        $row['rank_id'],
                        strtoupper($row['country'])
                    );
                }
            }
        }
    }
    # Need weekly score calculations!
    elseif ($type == 'risingstar')
    {
        // Check if the result set is expired
        if (Config::Get('stats_risingstar_refresh') < time())
        {
            // Call the proceedure to fill the risingstar table
            $connection->exec("CALL `generate_rising_star`");
            Config::Set('stats_risingstar_refresh', time() + (86400 * 7));
            Config::Save();
        }

        // Grab total count
        $query = "SELECT COUNT(player_id) FROM risingstar";
        $total = (int)$connection->query($query)->fetchColumn(0);
        $Response->writeDataLine($total, time());
        $Response->writeHeaderLine("n", "pid", "nick", "weeklyscore", "totaltime", "date", "playerrank", "countrycode");

        if (!$pid)
        {
            $query = <<<SQL
SELECT pos, player_id, weeklyscore, p.name, p.rank_id, p.country, p.joined, p.time
FROM risingstar AS r 
  INNER JOIN player AS p ON player_id = p.id 
WHERE pos BETWEEN $min AND $max
SQL;
            $result = $connection->query($query);
            while ($row = $result->fetch())
            {
                $Response->writeDataLine(
                    $row['pos'],
                    $row['player_id'],
                    trim($row['name']),
                    $row['weeklyscore'],
                    $row['time'],
                    date('m/d/y h:i:00 A', $row['joined']),
                    $row['rank_id'],
                    strtoupper($row['country'])
                );
            }
        }
        else
        {
            // Ensure Player exists by PID
            $query = "SELECT `id`, `name`, `rank_id`, `country`, `time`, `joined` FROM player WHERE id={$pid} LIMIT 1";
            $player = $connection->query($query)->fetch();
            if (empty($player))
            {
                $Response->send();
                die;
            }

            // Get players position relative to everyone else
            $query = "SELECT pos, weeklyscore FROM risingstar WHERE player_id=%d";
            $result = $connection->query(sprintf($query, $pid))->fetch();

            // Fix empty result
            if (empty($result))
            {
                $result = ['pos' => $total + 1, 'weeklyscore' => 0];
            }

            // Get players position relative to everyone else
            $Response->writeDataLine(
                $result['pos'],
                $player['id'],
                trim($player['name']),
                $result['weeklyscore'],
                $player['time'],
                date('m/d/y h:i:00 A', $player['joined']),
                $player['rank_id'],
                strtoupper($player['country'])
            );
        }
    }
    elseif ($type == 'kit')
    {
        // Cast to integer
        $id = (int)$id;

        // Get the total number of results
        $result = $connection->query("SELECT COUNT(kit_id) FROM player_kit WHERE kit_id={$id} AND kills > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        $Response->writeHeaderLine("n", "pid", "nick", "killswith", "deathsby", "timeplayed", "playerrank", "countrycode");

        // Non-PID serach
        if ($pid == 0)
        {
            $query = <<<SQL
SELECT p.name, p.rank_id, p.country, k.player_id, k.kills, k.deaths, k.time
FROM player_kit AS k
  INNER JOIN player AS p ON k.player_id = p.id
WHERE k.kit_id = $id AND k.kills > 0
ORDER BY kills DESC, time DESC
LIMIT $min, $max
SQL;
            $result = $connection->query($query);
            while ($row = $result->fetch())
            {
                $Response->writeDataLine(
                    $pos++,
                    $row['player_id'],
                    trim($row['name']),
                    $row['kills'],
                    $row['deaths'],
                    $row['time'],
                    $row['rank_id'],
                    strtoupper($row['country'])
                );
            }
        }
        else // Searching by PID
        {
            // Ensure Player exists by PID
            $query = "SELECT `id`, `name`, `rank_id`, `country` FROM player WHERE id={$pid} LIMIT 1";
            $player = $connection->query($query)->fetch();
            if (empty($player))
            {
                $Response->send();
                die;
            }

            // Fetch players kit if he has one...
            $query = "SELECT `kills`, `deaths`, `time` FROM player_kit WHERE player_id={$pid} AND kit_id={$id} LIMIT 1";
            $result = $connection->query($query);
            if ($row = $result->fetch())
            {
                $player += $row;
            }
            else
            {
                $player['kills'] = 0;
                $player['deaths'] = 0;
                $player['time'] = 0;
            }

            // Get player position relative to everyone else
            $query = "SELECT COUNT(kit_id) FROM player_kit WHERE kit_id=%d AND kills > %d AND time > %d";
            $result = $connection->query(sprintf($query, $id, $player['kills'], $player['time']));
            $pos = ((int)$result->fetchColumn()) + 1;

            // Send response
            $Response->writeDataLine(
                $pos,
                $player['id'],
                trim($player['name']),
                $player['kills'],
                $player['deaths'],
                $player['time'],
                $player['rank_id'],
                strtoupper($player['country'])
            );
        }
    }
    elseif ($type == 'vehicle')
    {
        // Cast to integer
        $id = (int)$id;

        $result = $connection->query("SELECT COUNT(vehicle_id) FROM player_vehicle WHERE vehicle_id={$id} AND kills > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        $Response->writeHeaderLine("n", "pid", "nick", "killswith", "deathsby", "timeused", "playerrank", "countrycode");

        if (!$pid)
        {
            $query = <<<SQL
SELECT 
  p.name AS name, p.rank_id AS rank, p.country AS country, 
  k.player_id AS pid, k.kills AS kills, k.deaths AS deaths, k.time AS `time`
FROM player_vehicle AS k
  INNER JOIN player AS p ON k.player_id = p.id
WHERE k.vehicle_id = $id AND k.kills > 0
ORDER BY kills DESC, time DESC
LIMIT $min, $max
SQL;
            $result = $connection->query($query);
            while ($row = $result->fetch())
            {
                $Response->writeDataLine(
                    $pos++,
                    $row['pid'],
                    trim($row['name']),
                    $row['kills'],
                    $row['deaths'],
                    $row['time'],
                    $row['rank_id'],
                    strtoupper($row['country'])
                );
            }
        }
        else // Searching by PID
        {
            // Ensure Player exists by PID
            $query = "SELECT `id`, `name`, `rank_id`, `country` FROM player WHERE id={$pid} LIMIT 1";
            $player = $connection->query($query)->fetch();
            if (empty($player))
            {
                $Response->send();
                die;
            }

            // Fetch players kit if he has one...
            $query = "SELECT `kills`, `deaths`, `time` FROM player_vehicle WHERE player_id={$pid} AND vehicle_id={$id} LIMIT 1";
            $row = $connection->query($query)->fetch();
            if (!empty($row))
            {
                $player += $row;
            }
            else
            {
                $player['kills'] = 0;
                $player['deaths'] = 0;
                $player['time'] = 0;
            }

            // Get player position relative to everyone else
            $query = "SELECT COUNT(vehicle_id) FROM player_vehicle WHERE vehicle_id=%d AND kills > %d AND time > %d";
            $result = $connection->query(sprintf($query, $id, $player['kills'], $player['time']));
            $pos = ((int)$result->fetchColumn()) + 1;

            // Send response
            $Response->writeDataLine(
                $pos,
                $player['id'],
                trim($player['name']),
                $player['kills'],
                $player['deaths'],
                $player['time'],
                $player['rank_id'],
                strtoupper($player['country'])
            );
        }
    }
    elseif ($type == 'weapon')
    {
        // Cast to integer
        $id = (int)$id;

        $result = $connection->query("SELECT COUNT(weapon_id) FROM player_weapon WHERE weapon_id={$id} AND kills > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        # NOTE: EA typo (deathsby=detahsby)
        $Response->writeHeaderLine("n", "pid", "nick", "killswith", "detahsby", "timeused", "accuracy", "playerrank", "countrycode");

        if (!$pid)
        {
            $query = <<<SQL
SELECT 
  p.name AS name, p.rank_id AS rank, p.country AS country, 
  k.player_id AS pid, k.kills AS kills, k.deaths AS deaths, k.time AS `time`, k.hits AS hits, k.fired AS fired
FROM player_weapon AS k
  INNER JOIN player AS p ON k.player_id = p.id
WHERE k.weapon_id = $id AND k.kills > 0
ORDER BY kills DESC, time DESC
LIMIT $min, $max
SQL;

            $result = $connection->query($query);
            while ($row = $result->fetch())
            {
                $fired = (int)$row['fired'];
                $hits = (int)$row['hits'];
                $Response->writeDataLine(
                    $pos++,
                    $row['pid'],
                    trim($row['name']),
                    $row['kills'],
                    $row['deaths'],
                    $row['time'],
                    ($fired > 0) ? @number_format(($hits / $fired) * 100) : 0,
                    $row['rank_id'],
                    strtoupper($row['country'])
                );
            }
        }
        else // Searching by PID
        {
            // Ensure Player exists by PID
            $query = "SELECT `id`, `name`, `rank_id`, `country` FROM player WHERE id={$pid} LIMIT 1";
            $player = $connection->query($query)->fetch();
            if (empty($player))
            {
                $Response->send();
                die;
            }

            // Fetch players kit if he has one...
            $query = "SELECT `kills`, `deaths`, `time`, `hits`, `fired` FROM player_weapon WHERE player_id=%d AND weapon_id=%d LIMIT 1";
            $row = $connection->query(sprintf($query, $pid, $id))->fetch();
            if (!empty($row))
            {
                $fired = (int)$row['fired'];
                $hits = (int)$row['hits'];
                $player += $row;
                $player['accuracy'] = ($fired > 0) ? @number_format(($hits / $fired) * 100) : 0;
            }
            else
            {
                $player['kills'] = 0;
                $player['deaths'] = 0;
                $player['time'] = 0;
                $player['accuracy'] = 0;
            }

            // Get player position relative to everyone else
            $query = "SELECT COUNT(weapon_id) FROM player_weapon WHERE weapon_id=%d AND kills > %d AND time > %d";
            $result = $connection->query(sprintf($query, $id, $player['kills'], $player['time']));
            $pos = ((int)$result->fetchColumn()) + 1;

            // Send response
            $Response->writeDataLine(
                $pos,
                $player['id'],
                trim($player['name']),
                $player['kills'],
                $player['deaths'],
                $player['time'],
                $player['accuracy'],
                $player['rank_id'],
                strtoupper($player['country'])
            );
        }
    }
    else
    {
        $Response->writeLine('Unknown type!');
    }

    // Send Output
    $Response->send($item);
}