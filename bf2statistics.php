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

namespace System
{
    use Exception;
    use SecurityException;
    use System\Collections\Dictionary;
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
    define('SNAPSHOT_AUTH_PATH', SYSTEM_PATH . DS . 'snapshots' . DS . 'unauthorized');
    define('SNAPSHOT_FAIL_PATH', SYSTEM_PATH . DS . 'snapshots' . DS . 'failed');
    define('SNAPSHOT_TEMP_PATH', SYSTEM_PATH . DS . 'snapshots' . DS . 'unprocessed');
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

    // Check user agent first and foremost
    if (trim($_SERVER['HTTP_USER_AGENT']) != "GameSpyHTTP/1.0")
    {
        header("Content-Type: text/plain; charset=utf-8");
        header('HTTP/1.1 403 Forbidden');
        die(_ERR_RESPONSE . "You are not authorised to access this page.");
    }

    // Make Sure Script doesn't timeout even if the user disconnects!
    set_time_limit(30);
    ignore_user_abort(true);

    // Register Class Autoloader
    include SYSTEM_PATH . DS . "framework" . DS . "Autoloader.php";
    Autoloader::Register();

    // Initiate the log writer
    try
    {
        $LogWriter = new LogWriter(Path::Combine(SYSTEM_PATH, 'logs', 'stats_debug.log'), 'stats_debug');
        $LogWriter->setLogLevel(Config::Get('debug_lvl'));

        // Log this access
        $LogWriter->logNotice("Incoming snapshot data from (%s): ", Request::ClientIp());

        // Set timezone for logging timestamps
        date_default_timezone_set(Config::Get('admin_timezone'));
    }
    catch (Exception $e)
    {
        error_log($e->getMessage(), 1);
        die(_ERR_RESPONSE . "Internal Server Error");
    }

/*
| ---------------------------------------------------------------
| Connect to database
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
        die(_ERR_RESPONSE . "Stats Database Offline");
    }

/*
| ---------------------------------------------------------------
| Parse SNAPSHOT
| ---------------------------------------------------------------
*/

    // Read snapshot data from input
    $rawdata = file_get_contents('php://input');
    if (!$rawdata)
    {
        $errmsg = "SNAPSHOT Data NOT found!";
        $LogWriter->logError($errmsg);
        die(_ERR_RESPONSE . $errmsg);
    }

    // Convert data string to an array
    $data = json_decode($rawdata, true);
    if ($data == null)
    {
        $LogWriter->logError("SNAPSHOT data invalid. JSON error code: ". json_last_error());
        die(_ERR_RESPONSE . "SNAPSHOT Data Incomplete");
    }

    // Parse Snapshot
    try
    {
        // Add ip address of connecting client
        $prefix = ['serverIp' => Request::ClientIp()];
        $data = new Dictionary(false, $prefix + $data);

        // Create snapshot object
        $snapshot = new Snapshot($data);

        // SNAPSHOT Data OK
        $LogWriter->logNotice("SNAPSHOT Data Complete (%s)", $snapshot->mapName);
    }
    catch (Exception $e)
    {
        $LogWriter->logError($e);

        // If error code is unknown map
        if ($e->getCode() == 99)
            die(_ERR_RESPONSE . $e);
        else
            die(_ERR_RESPONSE . "SNAPSHOT Data Incomplete");
    }

    // Create SNAPSHOT backup file
    $fileName = $snapshot->getFilename();
    try
    {
        // Create and write the snapshot data into a backup file
        $file = new FileStream(SNAPSHOT_TEMP_PATH . DS . $fileName, 'w+');
        $file->write(json_encode($data->toArray(), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_NUMERIC_CHECK));
        $file->close();

        // Log
        $LogWriter->logNotice("SNAPSHOT Data Logged (%s)", $fileName);

        // start buffer output
        ob_start();

        // Tell the game server that the snapshot has been received
        echo "O\nH\tresponseD\tOK\n$\tOK\t$";

        // Set headers to close the connection
        header("Connection: close");
        header("Content-Length: " . ob_get_length());

        // Flush output to server
        ob_end_flush();
        @flush();
    }
    catch (Exception $e)
    {
        $LogWriter->logError("Unable to create a new SNAPSHOT Data Logfile (%s): %s", [$fileName, $e->getMessage()]);
        die(_ERR_RESPONSE . "Internal Server Error");
    }

/*
| ---------------------------------------------------------------
| Process SNAPSHOT
| ---------------------------------------------------------------
*/

    try
    {
        // Execute snapshot
        $snapshot->processData();
    }
    catch (SecurityException $e)
    {
        try
        {
            // Move unprocessed file to the unauthorised folder
            File::Move(SNAPSHOT_TEMP_PATH . DS . $fileName, SNAPSHOT_AUTH_PATH . DS . $fileName);
        }
        catch (Exception $e)
        {
            $LogWriter->logError("Unable to move SNAPSHOT to the UnAuth Folder: %s", $e->getMessage());
        }
    }
    catch (Exception $e)
    {
        // No need to log here... a message will be logged automatically within the Snapshot class!
        // Move unprocessed file to the failed folder
        File::Move(SNAPSHOT_TEMP_PATH . DS . $fileName, SNAPSHOT_FAIL_PATH . DS . $fileName);
    }

    // Finally, move the file
    if ($snapshot->isProcessed())
        File::Move(SNAPSHOT_TEMP_PATH . DS . $fileName, SNAPSHOT_STORE_PATH . DS . $fileName);
}