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

/**
 * @todo: Redo this clan manager! Should not be doing full table scans, and outputting (potentially) thousands of lines.
 */

// Namespace
namespace System;
use Exception;
use PDO;

// No direct access
defined("BF2_ADMIN") or die("No Direct Access");

// Prepare output
$Response = new AspResponse();

// Make sure we have a list type and it valid
$listtype = (isset($_GET['type'])) ? $_GET['type'] : 0;
if (!is_numeric($listtype)) 
{
    $Response->responseError(true);
    $Response->writeHeaderLine("asof", "err");
    $Response->writeDataLine(time(), "Invalid Syntax!");
    $Response->send();
}
else 
{
    // Connect to the database
    $connection = Database::GetConnection("stats");
	
	// Build our criteria based on $_GET['type']
	$where = "";
    $binds = array();
	switch ($listtype) 
	{
		case 0:		#Blacklist
			$banlimit = ((isset($_GET['banned'])) && (is_numeric($_GET['banned']))) ? $_GET['banned'] : 100;	// Default Ban Limit is 100
			$where .= " AND (`banned` >= :banlimit OR `permban` = 1)";
            $binds[':banlimit'] = intval($banlimit);
			break;
		case 1:		#Whitelist
			if ($_GET['clantag']) 
			{
				$where .= " AND `clantag` = :clantag  AND `permban` = 0";
                $binds[':clantag'] = stripslashes($_GET['clantag']);
			}
			break;
		case 2:		#Greylist
			// Get Criteria
			$criteria = array('score','rank','time','kdratio','country','banned');
			$where = "";
			foreach($criteria as $param) 
			{
				if(isset($_GET[$param])) 
				{
					switch ($param) 
					{
						case 'id':
							if (is_numeric($_GET['id'])) 
                            { 
                                $where .= " AND `id` = :id";
                                $binds[':id'] = intval($_GET['id']);
                            }
							break;
						case 'score':
						case 'rank':
						case 'time':
                            if (is_numeric($_GET[$param])) 
                            { 
                                $where .= " AND `{$param}` >= :".$param;
                                $binds[':'.$param] = intval($_GET[$param]);
                            }
							break;
						case 'kdratio':
                            if (is_numeric($_GET['kdratio']) || is_float($_GET['kdratio'])) 
                            { 
                                $where .= " AND (`kills` / `deaths`) >= :kdratio";
                                $binds[':kdratio'] = floatval($_GET['kdratio']);
                            }
							break;
						case 'country':
							$paramArray = str_replace (",", "','", $_GET[$param]);
							$where .= " AND `{$param}` IN ('" . $paramArray . "')";
							break;
						case 'banned':
							if(is_numeric($_GET['banned']))
                            {
                                $where .= " AND (`banned` < :banned AND `permban` = 0)";
                                $binds[':banned'] = intval($_GET['banned']);
                            }
							break;
					}
				}
			}
			break;
	}

	// Prepare output header
    $Response->writeHeaderLine("size", "asof");

	// Prepare our statement query
	$stmt = $connection->prepare("SELECT id, name FROM player WHERE lastip != '0.0.0.0'{$where} ORDER BY id ASC");
	foreach($binds as $k => $v)
    {
        $type = (is_int($v)) ? PDO::PARAM_INT : PDO::PARAM_STR;
        $stmt->bindValue($k, $v, $type);
    }
    
    // Execute the statement
    $result = false;
    try {
        $result = $stmt->execute();
    }
    catch( Exception $e ) {}


	if($result)
	{
        $buffer = '';
        $i = 0;
        while($row = $stmt->fetch())
        {
            $buffer .= "D\t{$row['id']}\t{$row['name']}\n";
            ++$i;
        }

        $Response->writeDataLine($i, time());
        $Response->writeHeaderLine("pid", "nick");
        $Response->writeLine($buffer);
	}
    else
    {
        $Response->writeDataLine("0", time());
        $Response->writeHeaderLine("pid", "nick");
    }
    
    $Response->send();
}