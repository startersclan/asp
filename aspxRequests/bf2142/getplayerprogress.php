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

// Make sure we have an auth token!
if(!isset($_GET["auth"]) || strlen($_GET["auth"]) < 4)
    // Official servers do not output a formal response if the Auth key is invalid
    die("None: No error");

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

    // Prepare output
    $Response = new System\AspResponse();

    // Get mode and scale
    $mode = (isset($_GET['mode'])) ? $_GET['mode'] : null;
    $scale = (isset($_GET['scale'])) ? $_GET['scale'] : null;

    // Last 30 Game Days
    $date = 60*60*24*30;

    // Fetch player's progress history
    $query = "SELECT `timestamp` FROM progress_history WHERE id = {$pid} AND timestamp >= (UNIX_TIMESTAMP() - {$date})";
    $result = $connection->query($query);
    if (!($result instanceof PDOStatement) || $scale != "game")
    {
        // E 104 NoRowsDBError: Error, no rows returned
        $Response->responseError(true, 104);
        $Response->send();
        die;
    }
    else
    {
        // Prepare response header
        $Response->writeHeaderLine("pid", "asof");
        $Response->writeDataLine($pid, time());

        $num = 0;
        $out = "";

        switch ($mode)
        {
            case "score":
                $Response->writeHeaderLine("date", "score");
                $query = "SELECT `timestamp`, score FROM progress_history
                    WHERE id = {$pid} AND timestamp >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['score']);
                }
                break;

            case "point":
                $Response->writeHeaderLine("date", "points", "globalscore", "experiencepoints", "awaybonus");
                $query = "SELECT `timestamp`, score, experiencescore, awaybonusscore FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $points = ($row['score'] + $row['experiencescore'] + $row['awaybonusscore']);
                    $Response->writeDataLine($row['timestamp'], $points, $row['score'], $row['experiencescore'],
                        $row['awaybonusscore']
                    );
                }
                break;

            case "kills":
                $Response->writeHeaderLine("date", "kpm", "dpm");
                $query = "SELECT `timestamp`, kpm, dpm FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['kpm'], $row['dpm']);
                }
                break;

            case "spm":
                $Response->writeHeaderLine("date", "spm");
                $query = "SELECT `timestamp`, spm FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['spm']);
                }
                break;

            case "ttp":
                $Response->writeHeaderLine("date", "ttp");
                $query = "SELECT `timestamp`, tottime FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['tottime']);
                }
                break;

            case "role":
                $Response->writeHeaderLine("date", "cotime", "sltime", "smtime", "lwtime", "ttp");
                $query = "SELECT `timestamp`, cmdtime, sqltime, sqmtime, lwtime, tottime FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['cmdtime'], $row['sqltime'], $row['sqmtime'],
                        $row['lwtime'], $row['tottime']
                    );
                }
                break;

            case "flag":
                $Response->writeHeaderLine("date", "captures", "assist", "defend");
                $query = "SELECT `timestamp`, captures, captureassists, defends FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['captures'], $row['captureassists'], $row['defends']);
                }
                break;

            case "wl":
                $Response->writeHeaderLine("date", "wins", "losses");
                $query = "SELECT `timestamp`, wins, losses FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                     ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['wins'], $row['losses']);
                }
                break;

            case "twsc":
                $Response->writeHeaderLine("date", "twsc");
                $query = "SELECT `timestamp`, teamscore FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['teamscore']);
                }
                break;

            case "sup":
                $Response->writeHeaderLine("date", "hls", "rps", "rvs", "resp");
                $query = "SELECT `timestamp`, heals, repairs, revives, resupplies FROM progress_history
                    WHERE id = {$pid} AND `timestamp` >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY `timestamp` ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['heals'], $row['repairs'],
                        $row['revives'], $row['resupplies']
                    );
                }
                break;

            case "waccu":
                $head .= "H\tdate\twaccu\n";
                $Response->writeHeaderLine("date", "waccu");
                $query = "SELECT `timestamp`, accuracy FROM progress_history
                    WHERE id = {$pid} AND timestamp >= (UNIX_TIMESTAMP() - {$date})
                    ORDER BY timestamp ASC";
                $result = $connection->query($query);
                while ($row = $result->fetch())
                {
                    $Response->writeDataLine($row['timestamp'], $row['accuracy']);
                }
                break;

            default:
                // E 102 InvalidSearchTerm: Invalid search terms
                $Response->responseError(true, 102);
                break;
        }

        // Send response
        $Response->send();
    }
}