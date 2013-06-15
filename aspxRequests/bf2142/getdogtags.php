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

    // Prepare output
    $Response = new System\AspResponse();

    // Make sure player exists
    $result = $connection->query("SELECT id, name FROM player WHERE id = {$pid}");
    if (!($result instanceof PDOStatement) || !($row = $result->fetch()))
    {
        // E 104 NoRowsDBError: Error, no rows returned
        $Response->responseError(true, 104);
        $Response->send();
        die;
    }

    // Prepare response
    $Response->writeHeaderLine("pid", "nick", "asof");
    $Response->writeDataLine($pid, $row['name'], time());
    $Response->writeHeaderLine("profileid", "first", "last", "count");

    // Fetch players dog tags
	$query = "SELECT victim, first, last, count FROM dogtags WHERE id = {$pid} ORDER BY id";
	$result = $connection->query($query);
	if ($result instanceof PDOStatement)
	{
		while ($row = $result->fetch())
		{
			$first = date('m/d/Y H:i:s A', $row['first']);
			$last = date('m/d/Y H:i:s A', $row['last']);
            $Response->writeDataLine($row['victim'], $first, $last, $row['count']);
		}
	}
	
	$Response->send();
}