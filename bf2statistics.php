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

// Namespace
namespace System;

use System\IO\Path;

/**
 * Define Constants
 */
define('TIME_START', microtime(1));
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__);
define('SYSTEM_PATH', ROOT . DS . 'system');
define('SNAPSHOT_TEMP_PATH', SYSTEM_PATH . DS . 'snapshots' . DS . 'temp');
define('SNAPSHOT_STORE_PATH', SYSTEM_PATH . DS . 'snapshots' . DS . 'processed');
define("_ERR_RESPONSE", "E\nH\tresponse\nD\t");


/**
 * Set Error Reporting and Zlib Compression
 */
error_reporting(E_ALL);
ini_set("log_errors", "1");
ini_set("error_log", SYSTEM_PATH . DS . 'logs' . DS . 'php_errors.log');
ini_set("display_errors", "0");

// Disable Z lib Compression
ini_set('zlib.output_compression', '0');

// Make Sure Script doesn't timeout even if the user disconnects!
set_time_limit(300);
ignore_user_abort(true);

/**
 * Register Autoloader
 */
include SYSTEM_PATH . DS . "framework" . DS . "Autoloader.php";
Autoloader::Register();

// Initiate the log writer
$LogWriter = new LogWriter(Path::Combine(SYSTEM_PATH, 'logs', 'stats_debug.log'), 'stats_debug');
$LogWriter->setLogLevel(Config::Get('debug_lvl'));

// Define custom mapid
define('CUSTOM_MAP_ID', Config::Get('game_custom_mapid'));

/*
| ---------------------------------------------------------------
| Security Check
| ---------------------------------------------------------------
*/
	if (!Security::IsAuthorizedGameServer(Request::ClientIp()))
	{
		$LogWriter->logSecurity("Unauthorised Access Attempted! (IP: " . Request::ClientIp() . ")", 0);
		die(_ERR_RESPONSE . "Unauthorised Gameserver");
	}


/*
| ---------------------------------------------------------------
| Process SNAPSHOT
| ---------------------------------------------------------------
*/
	$rawdata = file_get_contents('php://input');
	if(!$rawdata)
	{
		$errmsg = "SNAPSHOT Data NOT found!";
		ErrorLog($errmsg, 1);
		die(_ERR_RESPONSE . $errmsg);
	}

	// Convret snapshot string into an array
	$gooddata = explode('\\', $rawdata);
	$prefix = $gooddata[0];
	$servername = $gooddata[1];

	// Convert all the data into key => value pairs
	$sizeGoodData = count($gooddata);
	for ($x = 2; $x < $sizeGoodData; $x += 2)
		$data[$gooddata[$x]] = $gooddata[$x + 1];

	// Check for Complete Snapshot data
	if(!isset($data['EOF']) || $data['EOF'] != 1)
	{
		$errmsg = "SNAPSHOT Data NOT complete!";
		ErrorLog($errmsg, 1);
		die(_ERR_RESPONSE.$errmsg);
	}

	// Generate SNAPSHOT Filename
	$mapname = strtolower($data['mapname']);
	$mapdate = date('Ymd_Hi', (int)$data['mapstart']);
	$stats_filename  = '';
	if ($prefix != '')
		$stats_filename .= $prefix . '-';
	$stats_filename .= $mapname . '_' . $mapdate . '.txt';

	// SNAPSHOT Data OK
	$errmsg = "SNAPSHOT Data Complete ({$mapname}:{$mapdate})";
	ErrorLog($errmsg, 3);

	// Create SNAPSHOT backup file
	if(!isset($data['import']) || $data['import'] != 1)
	{
		$file = SNAPSHOT_TEMP_PATH . DS . $stats_filename;
		$handle = @fopen($file, 'wb');
		if($handle)
		{
			@fwrite($handle, $rawdata);
			@fclose($handle);

			$errmsg = "SNAPSHOT Data Logged (". $file .")";
			ErrorLog($errmsg, 3);
		}
		else
		{
			$errmsg = "Unable to create a new SNAPSHOT Data Logfile (". $file . ")! Please make sure SNAPSHOT paths are writable!";
			ErrorLog($errmsg, 1);
		}

		// Tell the game server that the snapshot has been received
		$out = "O\nH\tresponseD\tOK\n$\tOK\t$";
		header("Connection: close");
		header("Content-Length: " . strlen($out));
		echo $out;
		@ob_flush();
		@flush();
	}


/*
| ---------------------------------------------------------------
| Open database connection and select bf2stats database
| ---------------------------------------------------------------
*/
	// Connect to the database
	$DB = null;
	try {
		$DB = Database::Connect('bf2stats',
			array(
				'driver' => 'mysql',
				'host' => Config::Get('db_host'),
				'port' => Config::Get('db_port'),
				'database' => Config::Get('db_name'),
				'username' => Config::Get('db_user'),
				'password' => Config::Get('db_pass')
			)
		);
	}
	catch( Exception $e ) {
		$errmsg = "Failed to establish Database connection";
		ErrorLog($errmsg, 1);
		die(_ERR_RESPONSE.$errmsg);
	}

	// Define our database version!
	$stmt = (is_object($DB)) ? $DB->query("SELECT `dbver` FROM `_version`;") : false;
	define('DB_VER', ($stmt == false) ? '0.0.0' : $stmt->fetchColumn());

	// Check Database Version... this is rather important!
	if(DB_VER != Config::Get('db_expected_ver'))
	{
		$errmsg = "Database version expected: ". Config::Get('db_expected_ver') .", Found: ". DB_VER;
		ErrorLog($errmsg, 1);
		die();
	}
	else
	{
		$errmsg = "Database version expected: ". Config::Get('db_expected_ver') .", Found: ". DB_VER;
		ErrorLog($errmsg, 3);
	}

/*
| ---------------------------------------------------------------
| Begin Processing...
| ---------------------------------------------------------------
*/

?>