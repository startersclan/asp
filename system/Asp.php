<?php
/**
 * BF2Statistics ASP Framework
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

        // Check user-agent
        if (Config::Get('stats_strict_api') == 1)
        {
            if (trim($_SERVER['HTTP_USER_AGENT'])  != "GameSpyHTTP/1.0")
            {
                header('HTTP/1.1 403 Forbidden');
                echo "<h1>403 Forbidden</h1>";
                echo "You are not authorised to access this page.";
                die;
            }
        }

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

        // Determine requested file name, protecting against Local File Inclusion
        $file = Path::GetFilenameWithoutExtension(strtolower($_GET['aspx']));
        $file = Path::Combine(ROOT, "aspx", $file . ".php");

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

        try
        {
            /** @noinspection PhpIncludeInspection */
            include $file;
        }
        catch (Exception $e)
        {
            // Create ASP log file instance
            try
            {
                new LogWriter(Path::Combine(SYSTEM_PATH, "logs", "asp_debug.log"), "Asp");
                Asp::LogException($e);
            }
            catch (Exception $ex)
            {
                // ignore
            }

            $response = new AspResponse();
            $response->responseError(true, 500);
            $response->writeHeaderLine("asof", "err");
            $response->writeDataLine(time(), "Internal Server Error");
            $response->send();
        }
    }

    /**
     * Handles an admin request
     */
    protected static function HandleAdminRequest()
    {
        // First and Foremost, Set timezone
        date_default_timezone_set(Config::Get('admin_timezone'));

        // Next, Lets make sure the IP can view the ASP
        if (!Security::IsAuthorizedIp(Request::ClientIp()))
            die('<span style="color: red; ">ERROR:</span> You are NOT Authorised to access this Page! (Ip: ' . Request::ClientIp() . ')');

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
            // Check for an ajax request, and answer accordingly
            if (isset($_POST['ajax']) && filter_var($_POST['ajax'], FILTER_VALIDATE_BOOLEAN))
            {
                // Respond in a commonly expected format for this admin panel
                echo json_encode(['success' => false, 'message' => "Login session has expired! Please refresh the page and login again."]);
            }
            else
            {
                $View = new View('login');
                $View->render(false);
            }
            return;
        }

        // Create ASP log file instance
        try {
            $LogWriter = new LogWriter(Path::Combine(SYSTEM_PATH, "logs", "asp_debug.log"), "Asp");
        }
        catch (Exception $e) {
            // Use tmp file instead
            $LogWriter = new LogWriter();
        }

        // Connect to the stats database
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

            $stmt = $DB->query("SELECT `version` FROM `_version` ORDER BY `updateid` DESC LIMIT 1;");
            $result = $stmt->fetchColumn();
            define('DB_VER', ($result === false) ? '0.0.0' : $result);
        }
        catch (Exception $e)
        {
            define('DB_VER', '0.0.0');
            $LogWriter->logDebug("Database connection failed: " . $e->getMessage());
        }

        // Parse version strings
        $dVer = Version::Parse(DB_VER);
        $cVer = Version::Parse(Config::Get('db_expected_ver'));

        // Make sure config expected DB version is up to date
        if (Version::GreaterThan($dVer, $cVer))
        {
            Config::Set('db_expected_ver', DB_VER);
            Config::Save();
        }

        // Get our MVC route
        $uri = (isset($_GET['uri']) && !empty($_GET['uri'])) ? $_GET['uri'] : 'home';
        $parts = explode('/', $uri);
        $length = count($parts);
        $GLOBALS['controller'] = $controller = ($length > 0) ? $parts[0] : 'home';
        $action = ($length > 1 && !empty($parts[1])) ? $parts[1] : 'index';
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

        // The way we built this path here naturally protects against Local File Inclusion
        $file = Path::Combine(ROOT, 'frontend', 'modules', $modNMame, $className . '.php');

        // Check if the controller exists already, if not, import it
        if (!class_exists($className, false))
        {
            // Build file path to the controller, check if it exists
            if (!file_exists($file))
            {
                // Show 404
                $view = new View('404');
                $view->set('message', "Module \"{$modNMame}\" Does not Exist!");
                $view->render();
                return;
            }

            // Load our controller file
            /** @noinspection PhpIncludeInspection */
            require $file;
        }

        // Load the controller reflection
        $rController = new \ReflectionClass($className);

        // Make sure the controller is not abstract object
        if ($rController->isAbstract())
            die('Module controller "'. $className .'" is abstract, and cannot be called via url');

        // Construct our controller
        $controller = new $className();

        // Check request method prefix'd action
        $m = strtolower( Request::Method() ) . ucfirst($action);
        if ($rController->hasMethod($m))
        {
            $action = $m;
        }
        elseif (!$rController->hasMethod($action))
        {
            $view = new View('404');
            $view->set('message', "Module \"{$className}\" does not contain the method \"{$action}\" or \"{$m}\"!");
            $view->render();
            return;
        }

        // Create a reflection of the controller method
        $method = new \ReflectionMethod($controller, $action);

        // If the method is not public, then we don't allow URL access!
        if (!$method->isPublic())
            die("Method \"{$action}\" is not a public method, and cannot be called via URL.");

        // Invoke the module controller and action
        $method->invokeArgs($controller, $params);
    }

    /**
     * Logs a detailed and recursive exception to the asp_debug.log file
     *
     * @param Exception $e
     */
    public static function LogException(Exception $e)
    {
        $log = LogWriter::Instance('Asp');
        if ($log instanceof LogWriter)
        {
            $log->logError('Exception Type: ' . get_class($e));
            $log->writeLine("\tMessage: " . $e->getMessage());
            $log->writeLine("\tCode: " . $e->getCode());
            $log->writeLine("\tFile: " . $e->getFile());
            $log->writeLine("\tLine: " . $e->getLine());
            $log->writeLine("\tStack Trace: ");
            foreach ($e->getTrace() as $message)
            {
                $output = implode(', ', array_map(
                    function ($v, $k)
                    {
                        return (is_array($v))
                            ? sprintf("%s=[%s]", $k, var_export($v, true))
                            : sprintf("%s='%s'", $k, $v);
                    },
                    $message,
                    array_keys($message)
                ));
                $log->writeLine("\t\t- " . $output);
            }

            if ($ex = $e->getPrevious())
            {
                $log->writeLine("\tInner Exceptions: ");
                do
                {
                    $log->writeLine(sprintf("\t\t- %s [%s] (%d) : %s", $ex->getMessage(), $ex->getFile(), $ex->getLine(), get_class($ex)));
                } while ($ex = $e->getPrevious());
            }
        }
    }
}