<?php
/*
    Copyright (C) 2006-2017  BF2Statistics

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

/**
 * The purpose of this ASPX call is for servers to verify a few things
 * about a player (this helps prevent cross service exploitation):
 *
 * 1. The player exists in our stats database
 * 2. His name matches the name we have on file.
 * 3. Player is not banned
 * 4. Player is logged in using our gamespy servers.
 *
 * If this page outputs any kind of error, the server must kick the player
 * as submission of a snapshot with this player involved will be voided.
 */

// Namespace
namespace System;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Prepare output
$Response = new AspResponse();

// Make sure we have a valid PID. Casting to int will sanitize input
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;
$nick = (isset($_GET['nick'])) ? str_replace('%20', '', $_GET['nick']) : '';

// Player id specified?
if ($pid == 0 || empty($nick))
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Get database connection
    $connection = Database::GetConnection("stats");

    // Make sure the player exists
    $query = "SELECT `name`, `permban` FROM `player` WHERE `id` = {$pid}";
    $result = $connection->query($query);

    // Query failed or player does not exist
    if (!($row = $result->fetch()))
    {
        $Response->writeHeaderLine("result", "message");
        $Response->writeDataLine("NOK", "Player Not Found!");
        $Response->send();
    }
    else
    {
        // Ensure the player is using our services, and is not banned!
        if (strtolower($nick) != strtolower($row['name']))
        {
            $Response->writeHeaderLine("result", "message");
            $Response->writeDataLine("NOK", "Player Nick Invalid!");
            $Response->send();
        }
        else if ($row['permban'] != 0)
        {
            $Response->writeHeaderLine("result", "message");
            $Response->writeDataLine("NOK", "Player Is Banned!");
            $Response->send();
        }
        /*
        else if ($row['online'] == 0)
        {
            $Response->writeHeaderLine("result", "message");
            $Response->writeDataLine("NOK", "Player Is Offline!");
            $Response->send();
        }
        */
        else
        {
            // Just echo OK for now
            $Response->writeHeaderLine("result", "message");
            $Response->writeDataLine("OK", "Player Is Valid");
            $Response->send();
        }
    }
}