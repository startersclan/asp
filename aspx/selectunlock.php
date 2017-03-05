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

// Make sure we have an ID and PID
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;
$id = (isset($_GET['id'])) ? (int)$_GET['id'] : 0;

// Check user input
if ($pid == 0 || $id == 0)
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

    // Check if unlock is already selected first!
    $result = $connection->query("SELECT * FROM `player_unlock` WHERE `unlockid` = $id AND `pid` = $pid LIMIT 1");
    if (!($exists = $result->fetch()))
    {
        /** TODO prevent cheating here via HTTP calls! */
        $connection->exec("INSERT INTO player_unlock VALUES ($pid, $id)");
    }

    $Response->writeLine("OK");
    $Response->send();
}