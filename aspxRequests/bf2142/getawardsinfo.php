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

    // Make sure player exists
    $name = null;
    $result = $connection->query("SELECT name FROM player WHERE id = {$pid}");
    if (!($result instanceof PDOStatement) || !($name = $result->fetchColumn()))
    {
        // E 104 NoRowsDBError: Error, no rows returned
        $Response->responseError(true, 104);
        $Response->send();
    }

    // Prepare our output header
    $Response->writeHeaderLine("pid", "nick", "asof");
    $Response->writeDataLine($pid, $name, time());
    $Response->writeHeaderLine("award", "level", "when", "first");

    // Query and get all of the Players awards
    // Use a prepared statement to prevent Sql Injection (level 1)
    $stmt = $connection->prepare("SELECT awd, level, earned, first FROM awards WHERE id = :pid ORDER BY awd ASC");
    $stmt->bindValue(':pid', intval($pid), PDO::PARAM_INT);

    // try and execute the prepared statement
    $result = $stmt->execute();
    while($row = $stmt->fetch())
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

    $Response->send();
}