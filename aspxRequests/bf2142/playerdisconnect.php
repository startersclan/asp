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
$gs = (isset($_GET["gs"])) ? intval($_GET['gs']) : false;
$xp = (isset($_GET["xp"])) ? intval($_GET['xp']) : false;
$ab = (isset($_GET["ab"])) ? intval($_GET['ab']) : false;
if($gs === false || $xp === false || $ab === false)
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

    // Make sure this is a server call
    if(!$AuthKey->isServerRequest())
    {
        $Response->responseError(true);
        $Response->writeLine("Not a valid Server Call");
        $Response->send();
    }

    // Make sure server is a valid server
    if(false)
    {
        // E 900 InvalidRankServer (UN: means ip not in white list)
        $Response->responseError(true, 900);
        $Response->send();
    }
		
    $query = "SELECT score, experiencescore, awaybonusscore FROM player WHERE id = {$pid}";
    $result = $connection->query($query);
    if (!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // E 104 NoRowsDBError: Error, no rows returned
        $Response->responseError(true, 104);
        $Response->send();
    }
    else
    {
        // Replace globals
        $gs_change = ($row['score'] > $gs) ? $row['score'] : $gs;
        $xp_change = ($row['experiencescore'] > $xp) ? $row['experiencescore'] : $xp;
        $ab_change = ($row['awaybonusscore'] > $ab) ? $row['awaybonusscore'] : $ab;


        $connection->exec("UPDATE player4 SET score = {$gs_change} WHERE id = {$pid}");

        $connection->exec("UPDATE player4 SET experiencescore = {$xp_change} WHERE id = {$pid}");

        $connection->exec("UPDATE player4 SET awaybonusscore = {$ab_change} WHERE id = {$pid}");

        // Success result
        $Response->writeHeaderLine("result");
        $Response->writeDataLine(0);
        $Response->send();
    }
}