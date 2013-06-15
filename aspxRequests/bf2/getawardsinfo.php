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

// Make sure we have a Player ID and its valid!
$pid = (isset($_GET['pid'])) ? intval($_GET['pid']) : false;
$transpose = (isset($_GET['transpose'])) ? intval($_GET['transpose']) : 0;
if($pid == false)
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
    
	// Prepare our output header
    $Response->writeHeaderLine("pid", "asof");
    $Response->writeDataLine($pid, time());
    $Response->writeHeaderLine("award", "level", "when", "first");
	
	// Query and get all of the Players awards
    // Use a prepared statement to prevent Sql Injection (level 1)
    $stmt = $connection->prepare("SELECT awd, level, earned, first FROM awards WHERE id = :pid ORDER BY id");
    $stmt->bindValue(':pid', intval($pid), PDO::PARAM_INT);
    
    // try and execute the prepared statement
	$result = $stmt->execute();
	while($row = $stmt->fetch())
	{
		$first = (($row['awd'] > 2000000) && ($row['awd'] < 3000000)) ? $row['first'] : 0;
        $Response->writeDataLine($row['awd'], $row['level'], $row['earned'], $first);
	}
	
	$Response->send($transpose);
}