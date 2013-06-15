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
$rank = (isset($_GET["rank"])) ? intval($_GET['rank']) : 0;
$gs = (isset($_GET["gs"])) ? intval($_GET['gs']) : 0;
$xp = (isset($_GET["xp"])) ? intval($_GET['xp']) : 0;
$ab = (isset($_GET["ab"])) ? intval($_GET['ab']) : 0;
if($rank === false || $gs === false || $xp === false || $ab === false)
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

    $query = "SELECT rank, score, experiencescore, awaybonusscore FROM player WHERE id = {$pid}";
    $result = $connection->query($query);
    if (!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // E 104 NoRowsDBError: Error, no rows returned
        $Response->responseError(true, 104);
        $Response->send();
        die;
    }
    else
    {
        // Rank cannot be negative
        if($rank < 0)
        {
            // E 201 RankChangeError: New rank is less than current rank
            $Response->responseError(true, 201);
            $Response->send();
        }

        // New rank cannot be less than the current rank
        elseif($rank < $row['rank'])
        {
            // E 204 ChangeErrorGS
            $Response->responseError(true, 204);
            $Response->send();
        }

        // Rank cannot be higher than 40
        elseif($rank > 40)
        {
            // E 203 InvalidRankError: Invalid Rank Selection
            $Response->responseError(true, 203);
            $Response->send();
        }
        else
        {
            // Rank change
            $rnk_change = ($row['rank'] > $rank) ? $row['rank'] : $rank;

            // Replace globals
            $gs_change = ($row['score'] > $gs) ? $row['score'] : $gs;
            $xp_change = ($row['experiencescore'] > $xp) ? $row['experiencescore'] : $xp;
            $ab_change = ($row['awaybonusscore'] > $ab) ? $row['awaybonusscore'] : $ab;

            // Update rows
            $connection->exec("UPDATE player SET rank = {$rnk_change}, score = {$gs_change}, experiencescore = {$xp_change},
                awaybonusscore = {$ab_change} WHERE id = {$pid}");

            // Success result
            $Response->writeHeaderDataArray(array('result' => 0));
            $Response->send();
        }
    }
}