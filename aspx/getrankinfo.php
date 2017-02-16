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

// Namespace
namespace System;

use PDOStatement;

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
else // Player exists
{
    // Get database connection
    $connection = Database::GetConnection("stats");

    // Make sure the player exists
    $query = "SELECT `rank`, `chng`, `decr` FROM `player` WHERE `id` = {$pid}";
    $result = $connection->query($query);

    // Query failed or player does not exist
    if (!($row = $result->fetch()))
    {
        $Response->responseError(true);
        $Response->writeHeaderLine("asof", "err");
        $Response->writeDataLine(time(), "Player Not Found!");
        $Response->send();
    }
    else
    {
        // Reset notifications
        if ($row['chng'] != '0' || $row['decr'] != '0')
            $connection->exec("UPDATE `player` SET `chng` = 0, `decr` = 0 WHERE `id` = {$pid}");

        // Send response
        $Response->writeHeaderLine("rank", "chng", "decr");
        $Response->writeDataLine($row['rank'], $row['chng'], $row['decr']);
        $Response->send();
    }
}