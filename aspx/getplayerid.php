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

use PDO;
use PDOStatement;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Prepare output
$Response = new AspResponse();

// Get database connection
$connection = Database::GetConnection("stats");

// Get our Player Nick
if (isset($_POST['nick']))
{
    $nick = $_POST['nick'];
}
else
{
    $nick = (isset($_GET['nick'])) ? $_GET['nick'] : '';
}

// Indicate whether this player is an AI bot
$isAi = ((isset($_GET['ai'])) ? (int)$_GET['ai'] : 0) > 0;

// Search by name?
if (!empty($nick))
{
    // Sanitize nick
    $pattern = Player::NAME_REGEX;
    $nick = preg_replace("/[^{$pattern}]/", '', $nick);

    // Ensure nick is not too long!
    if (strlen($nick) > 32)
    {
        $Response->responseError(true);
        $Response->writeHeaderLine("err");
        $Response->writeDataLine("Nick Specified is larger than 32 characters!");
        $Response->send();
    }

    // Try to fetch players id
    $result = $connection->prepare("SELECT id FROM player WHERE name = :nick LIMIT 1");
    $result->bindValue(":nick", $nick, PDO::PARAM_STR);

    // Player does not exist
    if (!$result->execute() || !($pid = $result->fetchColumn(0)))
    {
        if ($isAi)
        {
            // Use the internal procedure
            $stmt = $connection->prepare("CALL `create_player` (?, ?, ? , ?, @pid)");
            $stmt->bindValue(1, $nick, PDO::PARAM_STR);
            $stmt->bindValue(2, '', PDO::PARAM_STR);
            $stmt->bindValue(3, 'US', PDO::PARAM_STR);
            $stmt->bindValue(4, '127.0.0.1', PDO::PARAM_STR);
            $stmt->execute();

            // Fetch Player id from procedure parameter
            $stmt->bindColumn(1, $pid, PDO::PARAM_INT);
            $stmt->fetch(PDO::FETCH_BOUND);
        }
        else
        {
            $Response->responseError(true);
            $Response->writeHeaderLine("asof", "err");
            $Response->writeDataLine(time(), "Player Not Found!");
            $Response->send();
            die;
        }
    }

    // Send response
    $Response->writeHeaderLine("pid");
    $Response->writeDataLine($pid);
    $Response->send();
}
else
{
    $Response->responseError(true);
    $Response->writeHeaderLine("err");
    $Response->writeDataLine("No Nick Specified!");
    $Response->send();
}