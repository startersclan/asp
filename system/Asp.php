<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * @copyright   2013, BF2Statistics.com
 * @license     GNU GPL v3
 */
use System\AspResponse;
use System\Autoloader;
use System\Database;
use System\Config;
use System\IO\Path;
use System\LogWritter;
use System\Request;
use System\Security;
use System\View;

/**
 * Asp Class
 *
 * Responsible for loading the frontend
 */
class Asp
{
    /**
     * Indicates whether the Asp is running
     * @var bool
     */
    private static $isRunning = false;

    /**
     * The current game mode we are running (bf2 or bf2142)
     * @var string
     */
    public static $GameMode;

    /**
     * Initiates the ASP admin interface.
     *
     * @return void
     */
    public static function Run()
    {
        // Only allow the system to run once
        if(self::$isRunning)
            return;

        // Register that we are running
        self::$isRunning = true;

        // Register AutoLoader
        Autoloader::Register();

        // Process request based on type
        if(isset($_GET['aspx']))
            self::HandleAspxRequest();
        else
            self::HandleAdminRequest();
    }

    /**
     * Handles an admin request
     */
    protected static function HandleAdminRequest()
    {
        // Get running game mode
        self::$GameMode = Request::Cookie("selectedGame", "bf2");

        // First, Lets make sure the IP can view the ASP
        if(!Security::IsAuthorizedIp( Request::ClientIp() ))
            die("<font color='red'>ERROR:</font> You are NOT Authorised to access this Page! (Ip: ". Request::ClientIp() .")");

        // Create ASP log file instance
        $LogWritter = new LogWritter(Path::Combine(SYSTEM_PATH, "logs", "asp_debug.log"), "Asp");

        // Connect to the stats database
        $DB = false;
        try {
            $DB = Database::Connect('stats',
                array(
                    'driver' => 'mysql',
                    'host' => Config::Get('db_host'),
                    'port' => Config::Get('db_port'),
                    'database' => ((self::$GameMode == "bf2")
                        ? Config::Get('bf2stats_db_name')
                        : Config::Get('bf2142stats_db_name')
                    ),
                    'username' => Config::Get('db_user'),
                    'password' => Config::Get('db_pass')
                )
            );
        }
        catch( Exception $e ) {
            $LogWritter->logDebug("Database connection failed: ". $e->getMessage());
        }

        // Define our database version!
        $stmt = ($DB instanceof PDO) ? $DB->query("SELECT `dbver` FROM `_version`;") : false;
        define('DB_VER', ($stmt == false) ? '0.0.0' : $stmt->fetchColumn());

        // Make sure config expected DB version is up to date
        if(self::VersionToInt( DB_VER ) > self::VersionToInt( Config::Get('db_expected_ver') ))
        {
            Config::Set('db_expected_ver', DB_VER);
            Config::Save();
        }

        // Always set a post and get actions
        if(!isset($_POST['action'])) $_POST['action'] = null;
        if(!isset($_GET['action']))  $_GET['action'] = null;

        // Get / Set our current task
        $task = (isset($_GET['task'])) ? $_GET['task'] : false;
        if($task == false)
        {
            (isset($_POST['task'])) ? $_GET['task'] = $_POST['task'] : $_GET['task'] = 'home';
        }

        // Check for login / logout requests
        if($_POST['action'] == 'login' && isset($_POST['username']) && isset($_POST['password']))
            Security::Login($_POST['username'], $_POST['password']);
        elseif($_POST['action'] == 'logout' || $_GET['action'] == 'logout')
            Security::Logout();

        // Check and see if the user is logged in
        if( !Security::IsValidSession() )
        {
            $View = new View('login');
            $View->render(false);
            return;
        }
        else
            self::LoadModule(ucfirst( strtolower($_GET['task']) ));
    }

    /**
     * Handles an .ASPX request
     */
    protected static function HandleAspxRequest()
    {
        // Disable Zlib Compression, and hide errors
        ini_set('zlib.output_compression', '0');
        ini_set("display_errors", "0");

        // Determine game mode
        self::$GameMode = (array_key_exists('auth', $_GET) || $_GET['aspx'] == 'validateplayer') ? "bf2142" : "bf2";

        // Connect to the stats database
        try {
            Database::Connect('stats',
                array(
                    'driver' => 'mysql',
                    'host' => Config::Get('db_host'),
                    'port' => Config::Get('db_port'),
                    'database' => ((self::$GameMode == "bf2")
                        ? Config::Get('bf2stats_db_name')
                        : Config::Get('bf2142stats_db_name')
                    ),
                    'username' => Config::Get('db_user'),
                    'password' => Config::Get('db_pass')
                )
            );
        }
        catch( Exception $e ) {
            goto DatabaseOffline;
        }

        // Sometimes an exception isn't thrown by PDO
        if(!(Database::GetConnection('stats') instanceof PDO))
        {
            DatabaseOffline:
            {
                $Response = new AspResponse();
                $Response->responseError(true);
                $Response->writeHeaderLine("asof", "err");
                $Response->writeDataLine(time(), "Stats Database Offline");
                $Response->send();
            }
        }

        // Determine requested game
        $file = ROOT . DS . "aspxRequests" . DS . self::$GameMode . DS . strtolower($_GET['aspx']) . ".php";

        // Make sure file exists
        if(!file_exists($file))
        {
            header('HTTP/1.1 404 Not Found');
            echo "<h1>404 Not Found</h1>";
            echo "The page that you have requested could not be found.";
            die;
        }

        include $file;
    }

    /**
     * Loads and run a module
     *
     * @param string $name The module name
     *
     * @return void
     */
    public static function LoadModule($name)
    {
        // Process the task by making sure the module exists
        $name = ucfirst($name);
        $file = Path::Combine(ROOT, 'frontend', 'modules', $name, $name .'.php');
        if( !file_exists($file) )
        {
            // 404
            $Template = new View('404');
            $Template->render();
            return;
        }

        // Load the module and run!
        include $file ;
        $name = ucfirst($name);
        $Module = new $name;
        $Module->Init();
    }

    /**
     * Converts a string version from a float to INT for comparison
     *
     * @param string $verString The float version as a string
     *
     * @return int
     */
    public static function VersionToInt($verString)
    {
        $ver_arr = explode(".", $verString);

        $i = 1;
        $result = 0;
        foreach($ver_arr as $vbit)
        {
            $result += $vbit * $i;
            $i = $i / 100;
        }
        return $result;
    }
}