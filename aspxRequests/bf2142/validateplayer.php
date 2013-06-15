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

    -------------------------------------------------------------------------------

    This file is used to validate players against GameSpy, but we can use it
    to create online profiles from here, once player joins to server.
 
*/

// No direct access
if(!defined("BF2_ADMIN"))
    die("No Direct Access");

// Prepare output
$Response = new System\AspResponse();

$SoldierNick = (isset($_GET['SoldierNick'])) ? $_GET['SoldierNick'] : false;
$pid = (isset($_GET['pid'])) ? intval($_GET['pid']) : false;
if($pid === false || $SoldierNick === false)
{
    $Response->responseError(true);
    $Response->writeLine("Invalid Params!");
    $Response->send();
}
else
{
    if(!empty($SoldierNick))
    {
        // Connect to the database
        $connection = System\Database::GetConnection("stats");

        $result = $connection->query("SELECT name FROM player WHERE id = {$pid}");
        if ($result instanceof PDOStatement && ($name = $result->fetchColumn()))
        {
            // Return as ok
            $Response->writeHeaderLine("pid", "nick", "spid", "asof");
            $Response->writeDataLine($pid, $SoldierNick, $pid, time());
            $Response->writeHeaderDataArray(array('result' => 'Ok'));
            $Response->send();
        }
        else
        {
            // Begin transaction
            $connection->beginTransaction();

            // Create player
            $query = "INSERT INTO player SET
                id = {$pid},
                name = '". substr($connection->quote($SoldierNick), 1, -1) ."',
                country = 'xx',
                ip = '0.0.0.0',
                joined = " . time() ."
            ";
            $connection->exec($query);
            $connection->exec("INSERT INTO army SET id = {$pid}");
            $connection->exec("INSERT INTO equipment SET id = {$pid}");
            $connection->exec("INSERT INTO game_mode SET id = {$pid}");
            $connection->exec("INSERT INTO kits SET id = {$pid}");
            $connection->exec("INSERT INTO vehicles SET id = {$pid}");
            $connection->exec("INSERT INTO weapons SET id = {$pid}");

            // Commit transaction
            $connection->commit();

            // Send response
            $Response->writeHeaderLine("asof", "ok");
            $Response->writeDataLine(time(), "Soldier has been add!");
            $Response->send();
        }
    }
}