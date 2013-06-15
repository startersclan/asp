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

// Make sure we have a PID list
$pidlist = (isset($_GET['playerlist'])) ? $_GET['playerlist'] : 0;

// Get our Player Nick
if(isset($_POST['nick'])) 
{
    $nick = $_POST['nick'];
    $isBot = (isset($_POST['ai'])) ? intval($_POST['ai']) : 0;
} 
else
{
    $nick = (isset($_GET['nick'])) ? $_GET['nick'] : '';
    $isBot = (isset($_GET['ai'])) ? intval($_GET['ai']) : 0;
}


if(!empty($nick)) 
{
    // Try to fetch players id
    $query = "SELECT id FROM player WHERE name = '" . substr($connection->quote($nick), 1, -1) . "' LIMIT 1";
    $result = $connection->query($query);
    if( !($result instanceof PDOStatement) || !($pid = $result->fetchColumn()) )
    {
		// Default PID
		$pid = Config::Get('game_default_pid');
		
		// create new player at 'lowest id' - 1
        $query = "INSERT player (id, name, joined, isbot) SELECT LEAST(IFNULL(MIN(id),". $pid ."), ".
            $pid .")-1 AS id, '". substr($connection->quote($nick), 1, -1) ."' AS name, " . time() . " AS joined, ".
            $isBot ." AS isbot FROM player";
        if( $connection->exec($query) !== false ) 
        {
            // get that new minimum PID..
            $query = "SELECT MIN(id) AS min FROM player";
            $result = $connection->query($query);
            if($result instanceof PDOStatement) 
            {
                $pid = $result->fetchColumn();
            }
            else
            {
                $Response->responseError(true);
                $Response->writeHeaderLine("asof", "err");
                $Response->writeDataLine(time(), "Database Insertion Error");
                $Response->send();
            }
            
            // Insert unlocks
			$query = "";
            for ($i = 11; $i < 100; $i += 11)
                $query .= "($pid, $i, 'n'), ";
            
            for($i = 111; $i < 556; $i += 111)
                $query .= "($pid, $i, 'n'), ";
				
			$connection->exec("INSERT INTO unlocks VALUES  " . trim($query, ", "));
        }
    }

    // Send response
    $Response->writeHeaderLine("pid");
    $Response->writeDataLine($pid);
    $Response->send();
} 
elseif($pidlist) 
{
    // Get a list of all PIDS from the database where the IP is non local
    $query = "SELECT id FROM player WHERE ip <> '127.0.0.1'";
    $result = $connection->query($query);
    $Response->writeHeaderLine("pid");
    
    if($result instanceof PDOStatement) 
    {
        while($row = $result->fetch())
            $Response->writeDataLine($row['id']);
    }
    
    $Response->send();

}
else 
{
    $Response->responseError(true);
    $Response->writeHeaderLine("err");
    $Response->writeDataLine("No Nick Specified!");
    $Response->send();
}