<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\AspResponse;
use System\Autoloader;
use System\Database;
use System\Config;
use System\IO\Path;
use System\LogWriter;
use System\Request;
use System\Security;
use System\Version;
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
     * Initiates the ASP admin interface.
     *
     * @return void
     */
    public static function Run()
    {
        // Only allow the system to run once
        if (self::$isRunning) return;

        // Register that we are running
        self::$isRunning = true;

        // Register AutoLoader
        Autoloader::Register();

        // Process request based on type
        if (isset($_GET['aspx']))
            self::HandleAspxRequest();
        else
            self::HandleAdminRequest();
    }

    /**
     * Handles an .ASPX request
     */
    protected static function HandleAspxRequest()
    {
        // Disable Zlib Compression, and hide errors
        ini_set('zlib.output_compression', '0');
        ini_set("display_errors", "0");

        // Connect to the stats database
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
            goto DatabaseOffline;
        }

        // Sometimes an exception isn't thrown by PDO
        if (!(Database::GetConnection('stats') instanceof PDO))
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
        $file = Path::Combine(ROOT, "aspx", strtolower($_GET['aspx']) . ".php");

        // Make sure file exists
        if (!file_exists($file))
        {
            header('HTTP/1.1 404 Not Found');
            echo "<h1>404 Not Found</h1>";
            echo "The page that you have requested could not be found.";
            die;
        }

        // Define content type as Text, and run the ASPX file
        header("Content-Type: text/plain; charset=utf-8");

        /** @noinspection PhpIncludeInspection */
        include $file;
    }

    /**
     * Handles an admin request
     */
    protected static function HandleAdminRequest()
    {
        // First, Lets make sure the IP can view the ASP
        if (!Security::IsAuthorizedIp(Request::ClientIp()))
            die("<font color='red'>ERROR:</font> You are NOT Authorised to access this Page! (Ip: " . Request::ClientIp() . ")");

        // Create ASP log file instance
        $LogWriter = new LogWriter(Path::Combine(SYSTEM_PATH, "logs", "asp_debug.log"), "Asp");

        // Connect to the stats database
        $DB = false;
        try
        {
            $DB = Database::Connect('stats',
                array(
                    'driver' => 'mysql',
                    'host' => Config::Get('db_host'),
                    'port' => Config::Get('db_port'),
                    'database' => Config::Get('db_name'),
                    'username' => Config::Get('db_user'),
                    'password' => Config::Get('db_pass')
                )
            );

            $stmt = $DB->query("SELECT `version` FROM `_version`;");
            define('DB_VER', $stmt->fetchColumn());
        }
        catch (Exception $e)
        {
            define('DB_VER', '0.0.0');
            $LogWriter->logDebug("Database connection failed: " . $e->getMessage());
        }

        // Make sure config expected DB version is up to date
        $DbVer = Version::Parse(DB_VER);
        if ($DbVer->compare(Config::Get('db_expected_ver')) == 1)
        {
            Config::Set('db_expected_ver', DB_VER);
            Config::Save();
        }

        // Always set a post and get actions
        if (!isset($_POST['action'])) $_POST['action'] = '';
        if (!isset($_GET['action'])) $_GET['action'] = '';

        // Check for login / logout requests
        if ($_POST['action'] == 'login' && isset($_POST['username']) && isset($_POST['password']))
        {
            Security::Login($_POST['username'], $_POST['password']);
        }
        elseif ($_POST['action'] == 'logout' || $_GET['action'] == 'logout')
        {
            Security::Logout();
        }

        // Check and see if the user is logged in
        if (!Security::IsValidSession())
        {
            $View = new View('login');
            $View->render(false);
            return;
        }

        // Get our MVC route
        $uri = (isset($_GET['uri']) && !empty($_GET['uri'])) ? $_GET['uri'] : 'home';
        $parts = explode('/', $uri);
        $length = count($parts);
        $GLOBALS['controller'] = $controller = ($length > 0) ? $parts[0] : 'home';
        $action = ($length > 1) ? $parts[1] : 'index';
        $params = array_slice($parts, 2);

        // Load task
        self::LoadModule($controller, $action, $params);
    }

    /**
     * Loads and runs a module with the specified action and parameters
     *
     * @param string $name The module and controller name
     * @param string $action The action, or method name to execute in the controller
     * @param array $params The parameters to be passed to the controller's action method.
     *
     * @return void
     */
    public static function LoadModule($name, $action, $params)
    {
        // Process the task by making sure the module exists
        $modNMame = strtolower($name);
        $className = ucfirst($name);
        $file = Path::Combine(ROOT, 'frontend', 'modules', $modNMame, $className . '.php');

        // Check if the controller exists already, if not, import it
        if (!class_exists($className, false))
        {
            // Build file path to the controller, check if it exists
            if (!file_exists($file))
            {
                // 404
                $view = new View('404');
                $view->set('module_name', $modNMame);
                $view->render();
                return;
            }

            // Load our controller file
            /** @noinspection PhpIncludeInspection */
            require $file;
        }

        // Construct our controller
        $controller = new $className();

        // Create a reflection of the controller method
        try
        {
            $method = new \ReflectionMethod($controller, $action);

            // If the method is not public, then we don't allow URL access!
            if (!$method->isPublic())
                die("Method \"{$action}\" is not a public method, and cannot be called via URL.");

            // Invoke the module controller and action
            $method->invokeArgs($controller, $params);
        }
        catch (\ReflectionException $e)
        {
            die("Controller \"{$className}\" does not contain the method \"{$action}\"");
        }
    }
}