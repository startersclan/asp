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

// Prepare output
$Response = new System\AspResponse();
 
 // Make sure we have an ID and PID
$pid = (isset($_GET['pid'])) ? (int) $_GET['pid'] : false;
$id = (isset($_GET['id'])) ? (int) $_GET['id'] : false;
if(!$pid || !is_numeric($pid) || !$id || !is_numeric($id)) 
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Connect to the database
    $connection = System\Database::GetConnection("stats");

    // Update the unlock state of the chosen weapon
	$connection->exec("UPDATE `unlocks` SET `state` = 's' WHERE `id` = {$pid} AND `kit` = {$id}");

	// First, remove an available unlock
	$result = $connection->query("SELECT `availunlocks` FROM `player` WHERE `id` = {$pid}");
    if($result instanceof PDOStatement && ($unlocks = $result->fetchColumn()))
    {
        // Update, removing 1 available unlock from the player
        $unlocks -= 1;
        $connection->exec("UPDATE `player` SET `availunlocks` = {$unlocks} WHERE `id` = {$pid}");
    }
    
    // Add one to the used unlocks
    $result = $connection->query("SELECT `usedunlocks` FROM `player` WHERE `id` = {$pid}");
    if($result instanceof PDOStatement)
    {
        // Update, adding 1 used unlock from the player
        $used = $result->fetchColumn();
        $used += 1;
        $connection->exec("UPDATE `player` SET `usedunlocks` = {$used} WHERE `id` = {$pid}");
    }
    
    $Response->writeLine("OK");
    $Response->send();
}