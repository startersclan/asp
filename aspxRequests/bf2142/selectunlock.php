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

// Make sure params are correct
$uid = (isset($_GET['uid'])) ? $_GET['uid'] : false;
if($uid === false)
{
    $Response->responseError(true);
    $Response->writeLine("Invalid Params!");
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


    $result = $connection->query("SELECT availunlocks FROM player WHERE id = {$pid}");
    if (!($result instanceof PDOStatement) || !($availunlocks = $result->fetchColumn()))
    {
        // E 104 NoRowsDBError: Error, no rows returned
        $Response->responseError(true, 104);
        $Response->send();
    }
    else
    {
        if($availunlocks > 0)
        {
            $result = $connection->query("SELECT * FROM unlocks WHERE id = {$pid} AND unlockid = {$uid}");
            if (!($result instanceof PDOStatement) || !($row = $result->fetch()))
            {
                $connection->exec("INSERT INTO unlocks SET id = {$pid}, unlockid = {$uid}");
                $availunlocks -= 1;

                $result = $connection->query("SELECT usedunlocks FROM player WHERE id = {$pid}");
                $used = $result->fetchColumn() + 1;

                $connection->exec($query = "UPDATE player SET
                    availunlocks = {$availunlocks},
                    usedunlocks = {$used}
                    WHERE id = {$pid}"
                );

                // Success result
                $Response->writeHeaderDataArray(array('result' => 0));
                $Response->send();
            }
            else
            {
                // E 303 UnlockErrorExists: Player Already has Unlock
                $Response->responseError(true, 303);
                $Response->send();
            }
        }
        else
        {
            // E 304 UnlockErrorNoCredit: Player Has No Unlock Credits
            $Response->responseError(true, 304);
            $Response->send();
        }
    }
}