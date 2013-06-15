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

// Import Classes
use System\AspResponse;
use System\Config;
use System\Database;

// Prepare output
$Response = new AspResponse();

// Get database connection
$connection = Database::GetConnection("stats");

// Make sure we have the needed params
$pid 	 = (isset($_GET['pid'])) ? intval($_GET['pid']) : 0;
$mapid 	 = (isset($_GET['mapid'])) ? intval($_GET['mapid']) : 0;
$mapname = (isset($_GET['mapname'])) ? $_GET['mapname'] : '';
$limit	 = (isset($_GET['customonly'])) ? intval($_GET['customonly']) : 0;

// Limit results to custom maps ONLY
$maplimit = ($limit == 1) ? " AND id >= " . Config::Get('game_custom_mapid') : '';

if($pid) 
{
	// Build our query
	$query = "SELECT m.*, mi.name AS mapname" .
		"\nFROM maps m JOIN mapinfo mi ON m.mapid = mi.id" .
		"\nWHERE m.id = {$pid}" .
		"\nORDER BY mapid";
	$result = $connection->query($query);
	if($result instanceof PDOStatement && ($row = $result->fetch()))
	{
        $Response->writeHeaderLine("mapid", "mapname", "time", "win", "loss", "best", "worst");
		do {
            $Response->writeDataLine(
                $row['mapid'],
                $row['mapname'],
                $row['time'],
                $row['win'],
                $row['loss'],
                $row['best'],
                $row['worst']
            );
		} 
		while($row = $result->fetch()); 
	}
	else
    {
        $Response->responseError(true);
        $Response->writeHeaderLine("err");
        $Response->writeDataLine("Map Data Not Found!");
    }
} 
else 
{
	// Get the proper query
	$query = "SELECT id, name, score, time, times, kills, deaths FROM mapinfo ";
	if($mapid) 
		$query .= "WHERE id = {$mapid} {$maplimit}";
	elseif(!empty($mapname))
		$query .= "WHERE name = '". substr($connection->quote($mapname), 1, -1) ."' {$maplimit}";
	else 
		$query .= "WHERE name <> '' {$maplimit} ORDER BY id";
	
	$result = $connection->query($query);
	if($result instanceof PDOStatement && ($row = $result->fetch()))
	{
        $Response->writeHeaderLine("mapid", "name", "score", "time", "times", "kills", "deaths");
		do {
            $Response->writeDataLine(
                $row['id'],
                $row['name'],
                $row['score'],
                $row['time'],
                $row['times'],
                $row['kills'],
                $row['deaths']
            );
		}
		while($row = $result->fetch());
	}
    else
    {
        $Response->responseError(true);
        $Response->writeHeaderLine("err");
        $Response->writeDataLine("Map Data Not Found!");
    }
}

// Output data
$Response->send();