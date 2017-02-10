<?php
/*
    Copyright (C) 2006-2016  BF2Statistics

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

use PDO;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Prepare output
$Response = new AspResponse();

// Make sure we have a valid PID. Casting to int will sanitize input
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;
$transpose = (isset($_GET['transpose'])) ? (int)$_GET['transpose'] : 0;

// Player id specified?
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

    // Prepare our output header
    $Response->writeHeaderLine("pid", "asof");
    $Response->writeDataLine($pid, time());
    $Response->writeHeaderLine("award", "level", "when", "first");

    // Query and get all of the Players awards
    // Use a prepared statement to prevent Sql Injection (level 1)
    $stmt = $connection->prepare("SELECT `id`, `level`, `earned`, `first` FROM player_award WHERE `pid`=:pid ORDER BY `id`");
    $stmt->bindValue(':pid', $pid, PDO::PARAM_INT);
    if ($stmt->execute())
    {
        // Output awards
        while ($row = $stmt->fetch())
        {
            // Ribbons will get a 'first' value of 0
            $first = (($row['id'] > 2000000) && ($row['id'] < 3000000)) ? $row['first'] : 0;
            $Response->writeDataLine($row['id'], $row['level'], $row['earned'], $first);
        }
    }

    $Response->send($transpose);
}