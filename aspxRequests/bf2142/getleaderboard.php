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

// Prepare output
$Response = new System\AspResponse();

// Make sure the key isn't expired
if($AuthKey->isExpired())
{
    // Official servers do not output official responses for expired auth tokens
    die("ExpiredAuth: Expired authentication token");
}

$type = (isset($_GET['type'])) ? intval($_GET['type']) : false;
if ($type === false)
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else
{
    // Make player PID of Token
    $pid = $AuthKey->getPid();

    // Connect to the database
    $connection = System\Database::GetConnection("stats");

    // Init response
    $Response->writeHeaderLine("size", "asof");

    // Optional parameters
    $after = (isset($_GET['after'])) ? $_GET['after'] : 0;
    $pos = (isset($_GET['pos'])) ? $_GET['pos'] : 0;
    $min = ($pos - 1);
    $max = $after + 1;

    $poss = 1;
    $num = 0;
    $out = "";


	if ($type == 'overallscore')
	{
        $result = $connection->query("SELECT COUNT(id) FROM player WHERE score > 0");
        $Response->writeDataLine($result->fetchColumn(), time());
        $Response->writeHeaderLine("rank", "pos", "pid", "nick", "globalscore", "playerrank", "countrycode", "Vet");
		
		if ($pid) 
		{
            $query = "SELECT id, name, rank, country, vet, score FROM player WHERE id = {$pid} LIMIT 1";
            $result = $connection->query($query);
            if($result instanceof PDOStatement && ($row = $result->fetch()))
            {
                $num = intval($connection->query("SELECT COUNT(id) FROM player WHERE score > {$row['score']}")->fetchColumn()) + 1;
                $country = strtoupper($row['country']);
                $Response->writeDataLine($num, $num, $row['id'], $row['name'], $row['score'], $row['rank'],
                    $country, $row['vet']
                );
            }
		}

        // New header
        $Response->writeHeaderLine("rank", "pos", "pid", "nick", "globalscore", "playerrank", "countrycode", "Vet", "dt");

        // Fetch leader boards
        $query = "SELECT id, name, rank, country, vet, score FROM player WHERE score >= 0
            ORDER BY score DESC, name DESC LIMIT ". $min .", ". $max;
        $result = $connection->query($query);
        if($result instanceof PDOStatement)
        {
            while($row = $result->fetch())
            {
                $country = strtoupper($row['country']);
                $Response->writeDataLine($poss, $poss, $row['id'], $row['name'], $row['score'], $row['rank'],
                    $country, $row['vet'], 0
                );
                $poss++;
            }
        }

        // Send response
        $Response->send();
	}
}