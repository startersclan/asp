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

// Namespace
namespace System;

use PDOStatement;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Prepare output
$Response = new AspResponse();

// Get database connection
$connection = Database::GetConnection("stats");

// Make sure we have the needed params
$pid = (isset($_GET['pid'])) ? (int)$_GET['pid'] : 0;
$mapid = (isset($_GET['mapid'])) ? (int)$_GET['mapid'] : 0;
$mapname = (isset($_GET['mapname'])) ? $_GET['mapname'] : '';
$limit = (isset($_GET['customonly'])) ? (int)$_GET['customonly'] : 0;
$transpose = (isset($_GET['transpose'])) ? (int)$_GET['transpose'] : 0;

// Limit results to custom maps ONLY
$maplimit = ($limit == 1) ? " AND id >= " . Config::Get('game_custom_mapid') : '';

if ($pid > 0)
{
    // Build our query
    $query = "SELECT m.*, mi.name AS mapname FROM player_map AS m JOIN mapinfo AS mi ON m.mapid = mi.id " .
        "WHERE m.pid = {$pid} ORDER BY mapid";
    $result = $connection->query($query);
    if ($row = $result->fetch())
    {
        $Response->writeHeaderLine("mapid", "mapname", "time", "win", "loss", "best", "worst");
        do
        {
            $Response->writeDataLine(
                $row['mapid'],
                $row['mapname'],
                $row['time'],
                $row['wins'],
                $row['losses'],
                $row['bestscore'],
                $row['worstscore']
            );
        } while ($row = $result->fetch());
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
    // Prepare the SQL query
    $query = "SELECT id, name, score, time, times, kills, deaths FROM mapinfo ";
    $binds = [];

    // Finish query based on url parameters
    if ($mapid > 0)
    {
        $query .= "WHERE id = {$mapid} {$maplimit}";
    }
    elseif (!empty($mapname))
    {
        $query .= "WHERE name = :mapname {$maplimit}";
        $binds[':mapname'] = $mapname;
    }
    else
    {
        $query .= "WHERE name <> '' {$maplimit} ORDER BY id";
    }

    // Prepare the query using parameters, making it SQL injection safe
    $stmt = $connection->prepare($query);
    foreach ($binds as $key => $value)
        $stmt->bindValue($key, $value, \PDO::PARAM_STR);

    // Execute the statement
    if ($stmt->execute() && ($row = $stmt->fetch()))
    {
        $Response->writeHeaderLine("mapid", "name", "score", "time", "times", "kills", "deaths");
        do
        {
            $Response->writeDataLine(
                $row['id'],
                $row['name'],
                $row['score'],
                $row['time'],
                $row['times'],
                $row['kills'],
                $row['deaths']
            );
        } while ($row = $stmt->fetch());
    }
    else
    {
        $Response->responseError(true);
        $Response->writeHeaderLine("err");
        $Response->writeDataLine("Map Data Not Found!");
    }
}

// Output data
$Response->send($transpose);