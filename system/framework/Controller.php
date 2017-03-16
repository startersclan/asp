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

abstract class Controller
{
    /**
     * Performs a redirection to the installer if a database
     * connection cannot be established
     */
    protected function requireDatabase()
    {
        // Require database connection
        if (DB_VER == '0.0.0')
        {
            Response::Redirect('install');
            die;
        }
    }

    protected function loadModel($modelName, $module)
    {
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
        $this->{$modelName} = new $modelName();
    }
}