<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;

use System\IO\File;
use System\IO\Path;

/**
 * This class provides common methods for module controllers to inherit.
 *
 * @package System
 */
abstract class Controller
{
    /**
     * Performs a redirection to the installer if a database
     * connection cannot be established. This method will kill
     * the current script execution if a connection cannot be
     * established.
     *
     * @param bool $isAjaxRequest if true, then a json formatted response
     *  will be sent to the client instead of a redirection.
     */
    protected function requireDatabase($isAjaxRequest = false)
    {
        // Require database connection
        if (DB_VER == '0.0.0')
        {
            // Redirect if this is not an ajax request
            if (!$isAjaxRequest)
            {
                Response::Redirect('install');
            }
            else
            {
                echo json_encode(['success' => false, 'message' => 'Unable to connect to database!']);
            }

            // Kill the script
            die;
        }
    }

    /**
     * Checks to see if the POST or GET action matches any of the arguments passed
     * to this function. If false, then an Invalid Action json message is sent
     * to the client browser.
     *
     * This method will the current script execution if none of the required
     * actions are met.
     */
    public function requireAction()
    {
        $args = func_get_args();
        foreach ($args as $arg)
        {
            // Check if action is set and exists
            if ($_POST['action'] == $arg || $_GET['action'] == $arg)
            {
                // quit here
                return;
            }
        }

        echo json_encode( array('success' => false, 'message' => 'Invalid Action') );
        die;
    }

    /**
     * Checks to see if the POST or GET action matches any of the arguments passed
     * to this function, and returns the result
     *
     * @return bool true if one of the passed arguments matches the current action,
     *  otherwise false.
     */
    public function isAction()
    {
        $args = func_get_args();
        foreach ($args as $arg)
        {
            // Check if action is set and exists
            if ($_POST['action'] == $arg || $_GET['action'] == $arg)
            {
                // quit here
                return true;
            }
        }

        return false;
    }

    /**
     * Loads a model class from the specified module folder.
     *
     * @param string $modelName The name of the Model class
     * @param string $module The module name this Model is located with
     * @param string $propertyName Sets the name of the property this Model will be
     *  stored into. If no name is provided, this method will use the $moduleName
     *  in camel-case
     *
     * @throws \Exception if the model file cannot be located
     */
    protected function loadModel($modelName, $module, $propertyName = null)
    {
        // Make sure we have a property name
        if (empty($propertyName))
            $propertyName = lcfirst($modelName);

        // Load the class if it has not been loaded already
        if (!class_exists($modelName, false))
        {
            $path = Path::Combine(ROOT, 'frontend', 'modules', $module, 'models', $modelName . '.php');
            if (!File::Exists($path))
                throw new \Exception("Unable to locate class model: ". $path);

            /** @noinspection PhpIncludeInspection */
            require $path;
        }

        // Create instance and attach to this class
        $this->{$propertyName} = new $modelName();
    }
}