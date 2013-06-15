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
    // Connect to the database
    $connection = System\Database::GetConnection("stats");

    // Prepare output
    $Response = new System\AspResponse();
	
	$out = "";
	$availunlocks = 0;
	
	switch (System\Config::Get("bf2142_unlocks_mode"))
	{
		case 0:
			$result = $connection->query("SELECT name, rank, usedunlocks FROM player WHERE id = {$pid}");
			if (!($result instanceof PDOStatement) || !($row = $result->fetch()))
            {
                // E 104 NoRowsDBError: Error, no rows returned
                $Response->responseError(true, 104);
                $Response->send(); die;
            }

			$nick = $row['name'];
			$rank = $row['rank'];
			
			// Determine Earned Unlocks due to Rank
			$rankunlocks = getRankUnlocks($rank);
			
			// Determine Earned Unlocks due to Badges and Ribbons
			$bonusunlocks = getBP1Unlocks($pid, $connection);
			
			// Available Unlocks
			$availunlocks = $rankunlocks + $bonusunlocks;
			
			// Check Used Unlocks
			$query = "SELECT COUNT(id) AS count FROM unlocks WHERE id = {$pid}";
			$result = $connection->query($query);
			if ($result instanceof PDOStatement && ($usedunlocks = $result->fetchColumn()))
			{
				// Determine total unlocks available
				$availunlocks -= $usedunlocks;
				
				// Update Unlocks Data
				$query = "UPDATE player SET availunlocks = {$availunlocks}, usedunlocks = {$usedunlocks} WHERE id = {$pid}";
				$connection->exec($query);
			}

            // Future note... Sqlite, Coalesce == IfNull
			$query = "SELECT ".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 111 AND unlockid <= 115) AS uid0,".  
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 121 AND unlockid <= 125) AS uid1,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 211 AND unlockid <= 215) AS uid2,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 221 AND unlockid <= 225) AS uid3,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 311 AND unlockid <= 315) AS uid4,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 321 AND unlockid <= 325) AS uid5,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 411 AND unlockid <= 415) AS uid6,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 421 AND unlockid <= 425) AS uid7,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 511 AND unlockid <= 516) AS uid8,".
				  " (SELECT COALESCE(MAX(unlockid) ,0) FROM unlocks WHERE id = {$pid} AND unlockid >= 521 AND unlockid <= 524) AS uid9";

            // Prepare response
            $Response->writeHeaderLine("pid", "nick", "asof");
            $Response->writeDataLine($pid, $nick, time());
            $Response->writeHeaderLine("Avcred");
            $Response->writeDataLine($availunlocks);
            $Response->writeHeaderLine("UnlockID");

            // Add unlocks
            $result = $connection->query($query);
			if($result instanceof PDOStatement)
            {
                // Output unlocks
                $rows = array_filter($result->fetchAll());
                foreach ($rows as $unl)
                    $Response->writeDataLine($unl);
            }
			break;
		case 1:
            // Prepare response
            $Response->writeHeaderLine("pid", "nick", "asof");
            $Response->writeDataLine($pid, "All_Unlocks", time());
            $Response->writeHeaderLine("Avcred");
            $Response->writeDataLine($availunlocks);
            $Response->writeHeaderLine("UnlockID");
            $Response->writeDataLine(115);
            $Response->writeDataLine(125);
            $Response->writeDataLine(215);
            $Response->writeDataLine(225);
            $Response->writeDataLine(315);
            $Response->writeDataLine(325);
            $Response->writeDataLine(415);
            $Response->writeDataLine(425);
            $Response->writeDataLine(516);
            $Response->writeDataLine(524);
			break;
		default:
            // Prepare response
            $Response->writeHeaderLine("pid", "nick", "asof");
            $Response->writeDataLine($pid, "No_Unlocks", time());
            $Response->writeHeaderLine("Avcred");
            $Response->writeDataLine($availunlocks);
            $Response->writeHeaderLine("UnlockID");
            $Response->writeDataLine(110);
            $Response->writeDataLine(120);
            $Response->writeDataLine(210);
            $Response->writeDataLine(220);
            $Response->writeDataLine(310);
            $Response->writeDataLine(320);
            $Response->writeDataLine(410);
            $Response->writeDataLine(420);
            $Response->writeDataLine(510);
            $Response->writeDataLine(520);
			break;
	}
	
	// Send Response
    $Response->send();
}

	
function getRankUnlocks($rank)
{
	// Determine Earned Unlocks due to Rank
	if ($rank >= 40) {$rankunlocks = 40;}
	elseif ($rank >= 39) {$rankunlocks = 39;}
	elseif ($rank >= 38) {$rankunlocks = 38;}
	elseif ($rank >= 37) {$rankunlocks = 37;}
	elseif ($rank >= 36) {$rankunlocks = 36;}
	elseif ($rank >= 35) {$rankunlocks = 35;}
	elseif ($rank >= 34) {$rankunlocks = 34;}
	elseif ($rank >= 33) {$rankunlocks = 33;}
	elseif ($rank >= 32) {$rankunlocks = 32;}
	elseif ($rank >= 31) {$rankunlocks = 31;}
	elseif ($rank >= 30) {$rankunlocks = 30;}
	elseif ($rank >= 29) {$rankunlocks = 29;}
	elseif ($rank >= 28) {$rankunlocks = 28;}
	elseif ($rank >= 27) {$rankunlocks = 27;}
	elseif ($rank >= 26) {$rankunlocks = 26;}
	elseif ($rank >= 25) {$rankunlocks = 25;}
	elseif ($rank >= 24) {$rankunlocks = 24;}
	elseif ($rank >= 23) {$rankunlocks = 23;}
	elseif ($rank >= 22) {$rankunlocks = 22;}
	elseif ($rank >= 21) {$rankunlocks = 21;}
	elseif ($rank >= 20) {$rankunlocks = 20;}
	elseif ($rank >= 19) {$rankunlocks = 19;}
	elseif ($rank >= 18) {$rankunlocks = 18;}
	elseif ($rank >= 17) {$rankunlocks = 17;}
	elseif ($rank >= 16) {$rankunlocks = 16;}
	elseif ($rank >= 15) {$rankunlocks = 15;}
	elseif ($rank >= 14) {$rankunlocks = 14;}
	elseif ($rank >= 13) {$rankunlocks = 13;}
	elseif ($rank >= 12) {$rankunlocks = 12;}
	elseif ($rank >= 11) {$rankunlocks = 11;}
	elseif ($rank >= 10) {$rankunlocks = 10;}
	elseif ($rank >= 9) {$rankunlocks = 9;}
	elseif ($rank >= 8) {$rankunlocks = 8;}
	elseif ($rank >= 7) {$rankunlocks = 7;}
	elseif ($rank >= 6) {$rankunlocks = 6;}
	elseif ($rank >= 5) {$rankunlocks = 5;}
	elseif ($rank >= 4) {$rankunlocks = 4;}
	elseif ($rank >= 3) {$rankunlocks = 3;}
	elseif ($rank >= 2) {$rankunlocks = 2;}
	elseif ($rank >= 1) {$rankunlocks = 1;}
	else {$rankunlocks = 0;}
	return $rankunlocks;
}

function getBP1Unlocks($pid, PDO $connection)
{
	// Define Kit Badges Array
	$BPbadges = array(
		"120",	    // Basic Arctic Combat Badge
		"120",      // Expert Arctic Combat Badge
		"120",      // Veteran Arctic Combat Badge
        "121",      // Basic Vehicle Excellence Badge
        "121",	    // Expert Vehicle Excellence Badge
		"121",	    // Veteran Vehicle Excellence Badge
		"320",		// Operation Snowflake Ribbon
		"321",		// Cold Front Unit Service Ribbon
		"322",		// Transporter Duty Ribbon
		"323"		// Meritorious Winter strike Ribbon
    );
	
	// Count number badges/ribbons obtained
	$checkawds = "'" . implode("','", $BPbadges) . "'";
	$query = "SELECT COUNT(id) AS count FROM awards WHERE id = {$pid} AND (awd IN ({$checkawds}))";
	$result = $connection->query($query);
    if ($result instanceof PDOStatement && ($count = $result->fetchColumn()))
		return $count;
    else
		return 0;
}