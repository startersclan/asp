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
namespace System
{
    use Exception;
    use System\IO\File;
    use System\IO\FileStream;
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

/*
| ---------------------------------------------------------------
| Security Check
| ---------------------------------------------------------------
*/
    if (!Security::IsAuthorizedGameServer(Request::ClientIp()))
    {
        $LogWriter->logSecurity("Unauthorised Access Attempted! (IP: %s)", Request::ClientIp());
        die(_ERR_RESPONSE . "Unauthorised Gameserver");
    }


/*
| ---------------------------------------------------------------
| Connect to database and parse SNAPSHOT
| ---------------------------------------------------------------
*/
    // Connect to the database
    try
    {
        Database::Connect('stats',
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
    catch (Exception $e)
    {
        $LogWriter->logError("Failed to establish Database connection: " . $e->getMessage());
        die(_ERR_RESPONSE . "Failed to establish Database connection");
    }

    // Read snapshot data from input
    $rawdata = file_get_contents('php://input');
    if (!$rawdata)
    {
        $errmsg = "SNAPSHOT Data NOT found!";
        $LogWriter->logError($errmsg);
        die(_ERR_RESPONSE . $errmsg);
    }

    // Parse Snapshot
    try
    {
        $snapshot = new Snapshot($rawdata, Request::ClientIp());

        // SNAPSHOT Data OK
        $LogWriter->logNotice("SNAPSHOT Data Complete (%s)", $snapshot->mapName);
    }
    catch (Exception $e)
    {
        $LogWriter->logError($e);
        die(_ERR_RESPONSE . $e);
    }

    // Create SNAPSHOT backup file
    $fileName = $snapshot->getFilename();
    try
    {
        // Create and write the snapshot data into a backup file
        $file = new FileStream(SNAPSHOT_TEMP_PATH . DS . $fileName, 'wb');
        $file->write($rawdata);
        $file->close();

        // Log
        $LogWriter->logNotice("SNAPSHOT Data Logged (%s)", $fileName);

        // Tell the game server that the snapshot has been received
        $out = "O\nH\tresponseD\tOK\n$\tOK\t$";
        header("Connection: close");
        header("Content-Length: " . strlen($out));
        echo $out;
        @ob_flush();
        @flush();
    }
    catch (Exception $e)
    {
        $errmsg = "Unable to create a new SNAPSHOT Data Logfile (%s)! Please make sure SNAPSHOT paths are writable!";
        $LogWriter->logError($errmsg, $fileName);
        die(_ERR_RESPONSE . "Unable to create a new SNAPSHOT Data Logfile");
    }

/*
| ---------------------------------------------------------------
| Process SNAPSHOT
| ---------------------------------------------------------------
*/
    try
    {
        $snapshot->processData();
    }
    catch (Exception $e)
    {
        // No need to log here... a message will be logged automatically!
    }

    // Finally, move the file
    File::Move($fileName, SNAPSHOT_STORE_PATH . DS . $fileName);
}