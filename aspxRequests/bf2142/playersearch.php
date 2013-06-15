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

// Prepare output
$Response = new System\AspResponse();

// Make sure we have a search pattern
$nick = (isset($_GET['nick'])) ? $_GET['nick'] : false;
if(empty($nick))
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}

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

    // Prepare Response
    $Response->writeHeaderLine("pid", "asof");
    $Response->writeDataLine($pid, time());
    $Response->writeHeaderLine("searchpattern");
    $Response->writeDataLine($nick);
    $Response->writeHeaderLine("pid", "nick");

    // Fetch matching players
    $query = "SELECT `id`, `name` FROM `player` WHERE `name` LIKE '%".
        substr($connection->quote($nick), 1, -1) ."%'";
    $result = $connection->query($query);
    if($result instanceof PDOStatement)
    {
        while($row = $result->fetch())
            $Response->writeDataLine($row['id'], $row['name']);
    }

    $Response->send();
}