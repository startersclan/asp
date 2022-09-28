<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2021, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

/**
 * Define that we are here in the BF2 Admin area, prevents direct
 * linking of files, Also define ROOT and system paths
 */
define('BF2_ADMIN', true);
define('CODE_VERSION', '3.1.0');
define('CODE_VERSION_DATE', '2021-4-21');
define('DB_EXPECTED_VERSION', '3.1.0');
define('DS', DIRECTORY_SEPARATOR);
define('ROOT', __DIR__);
define('SYSTEM_PATH', ROOT . DS . 'system');
define('TIME_START', microtime(true));

// Make sure we are running php version 5.6.2 or newer!!!!
if (!defined('PHP_VERSION_ID') || PHP_VERSION_ID < 50602)
    die("PHP version 5.6.2 or newer required to run the ASP administration panel. Your version: " . PHP_VERSION);

// Make sure we have PDO loaded
if (!defined('PDO::ATTR_DRIVER_NAME'))
    die("PDO extension is not loaded! This version of the ASP requires PHP's PDO extension. Please enable it and try again.");

// Ensure mod rewrite is enabled
if (!isset($_SERVER['HTTP_MOD_REWRITE']) || $_SERVER['HTTP_MOD_REWRITE'] != "On" )
    die("Apache Module mod_rewrite is required! Please enable it and try again.");

// Set Error Reporting
error_reporting(E_ALL);
ini_set("log_errors", "1");
ini_set("error_log", SYSTEM_PATH . DS . 'logs' . DS . 'php_errors.log');
ini_set("display_errors", "1");

// Require the needed scripts to launch the system
require SYSTEM_PATH . DS . 'framework' . DS . 'Autoloader.php';
require SYSTEM_PATH . DS . 'System.php';

// Load the controller, which in turn loads the current task
System::Run();