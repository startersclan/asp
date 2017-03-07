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

/**
 * This class is an advanced autoloader for missing class references.
 * Able to register namespace specific paths, as well as prefix
 * specific paths.
 *
 * @author      Steven Wilson
 * @package     Core
 */
class Autoloader
{
    /**
     * A bool that states whether the Autoloader is registered with spl_autoload
     * @var bool
     */
    protected static $isRegistered = false;

    /**
     * An array of registered paths
     * @var string[]
     */
    protected static $paths = array();

    /**
     * An array of registered namespace => path
     * @var string[string[]]
     */
    protected static $namespaces = array();

    /**
     * An array of registered prefix => path
     * @var string[string[]]
     */
    protected static $prefixes = array();

    /**
     * Registers the AutoLoader class with spl_autoload. Multiple
     * calls to this method will not yield any additional results.
     *
     * @return void
     */
    public static function Register()
    {
        if (self::$isRegistered) return;

        spl_autoload_register('System\Autoloader::LoadClass');

        // Add paths for the system
        self::$namespaces['System'] = array(__DIR__);
        self::$paths[0] = __DIR__ . DIRECTORY_SEPARATOR . "Exceptions";

        self::$isRegistered = true;
    }

    /**
     * Un-Registers the AutoLoader class with spl_autoload
     *
     * @return void
     */
    public static function UnRegister()
    {
        if (!self::$isRegistered) return;

        spl_autoload_unregister('System\Autoloader::LoadClass');

        self::$isRegistered = false;
    }

    /**
     * Registers a path for the autoload to search for classes. Namespaced
     * and prefixed registered paths will be searched first if the class
     * is namespaced, or prefixed.
     *
     * @param string $path Full path to search for a class
     *
     * @return void
     */
    public static function RegisterPath($path)
    {
        if (array_search($path, self::$paths) === false)
            self::$paths[] = $path;
    }

    /**
     * Registers a path for the autoloader to search in when searching
     * for a specific namespaced class. When calling this method more
     * than once with the same namespace, the path(s) will just be added
     * to the current running list of paths for that namespace
     *
     * @param string $namespace The namespace we are registering
     * @param string|array $path Full path, or an array of paths
     *   to search for the namespaced class'.
     *
     * @return void
     */
    public static function RegisterNamespace($namespace, $path)
    {
        // Make sure path is array
        if (!is_array($path))
            $path = (array)$path;

        // Fix path, providing correct directory separator
        $path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);

        // Set namespace paths
        if (isset(self::$namespaces[$namespace]))
            self::$namespaces[$namespace] = array_merge(self::$namespaces[$namespace], $path);
        else
            self::$namespaces[$namespace] = $path;
    }

    /**
     * Registers a path for the autoload to search for when searching
     * for a prefixed class. When calling this method more than once
     * with the same prefix, the path(s) will just be added to the current
     * running list of paths for that prefix
     *
     * @param string $prefix The class prefix we are registering
     * @param string|array $path Full path, or an array of paths
     *   to search for the prefixed class'
     *
     * @return void
     */
    public static function RegisterPrefix($prefix, $path)
    {
        // Make sure path is array
        if (!is_array($path))
            $path = (array)$path;

        // Fix path, providing correct directory separator
        $path = str_replace(array('\\', '/'), DIRECTORY_SEPARATOR, $path);

        // Set prefix paths
        if (isset(self::$prefixes[$prefix]))
            self::$prefixes[$prefix] = array_merge(self::$prefixes[$prefix], (array)$path);
        else
            self::$prefixes[$prefix] = (array)$path;
    }

    /**
     * Returns an array of all registered namespaces as keys, and an array
     * of registered paths for that namespace as values
     *
     * @return string[]
     */
    public static function GetNamespaces()
    {
        return self::$namespaces;
    }

    /**
     * Returns an array of all registered prefixes as keys, and an array
     * of registered paths for that prefix as values
     *
     * @return string[]
     */
    public static function GetPrefixes()
    {
        return self::$prefixes;
    }

    /**
     * Method used to search all registered paths for a missing class
     * reference (used by the spl_autoload method)
     *
     * @param string $class The class being loaded
     *
     * @return Bool Returns TRUE if the class is found, and file was
     *   included successfully.
     */
    public static function LoadClass($class)
    {
        // If the class name is namespaced, we will use the namespace to determine
        // the path to the class file.
        if (($pos = strripos($class, '\\')) !== false)
        {
            $namespace = substr($class, 0, $pos);
            $class = substr($class, $pos + 1);
            $nameparts = explode('\\', $namespace);

            /**
             * we will keep checking all namespaces, working up 1 level
             * each time until we reach a defined namespace path, and from there,
             * each sub namespace we removed becomes a sub dir to the class file path.
             */
            for ($i = count($nameparts); $i >= 0; $i--)
            {
                if (isset(self::$namespaces[$namespace]))
                {
                    foreach (self::$namespaces[$namespace] as $dir)
                    {
                        $file = $dir . DIRECTORY_SEPARATOR . str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class) . '.php';
                        if (file_exists($file))
                        {
                            /** @noinspection PhpIncludeInspection */
                            require $file;
                            return true;
                        }
                    }
                    break;
                }
                else
                {
                    // Append class name path, and remove the last namespace
                    $class = array_pop($nameparts) . '\\' . $class;
                    $namespace = implode('\\', $nameparts);
                }
            }
        }

        // If no namespace if found, but we have a possible prefixed class (with _ ), search prefixes
        elseif (($pos = strripos($class, '_')) !== false)
        {
            $prefix = substr($class, 0, $pos);
            $class = substr($class, $pos + 1);
            $nameparts = explode('_', $prefix);

            /**
             * As described above, check all prefixes, working up 1 level
             * each time until we reach a defined prefix path, and from there,
             * each underscore becomes a sub dir to the class file path.
             */
            for ($i = count($nameparts); $i >= 0; $i--)
            {
                if (isset(self::$prefixes[$prefix]))
                {
                    foreach (self::$prefixes[$prefix] as $dir)
                    {
                        $file = $dir . DIRECTORY_SEPARATOR . str_replace(array('_', '\\'), DIRECTORY_SEPARATOR, $class) . '.php';
                        if (file_exists($file))
                        {
                            /** @noinspection PhpIncludeInspection */
                            require $file;
                            return true;
                        }
                    }
                    break;
                }
                else
                {
                    // Append class name path, and remove the last namespace
                    $class = array_pop($nameparts) . '_' . $class;
                    $prefix = implode('_', $nameparts);
                }
            }
        }

        // If all else fails, or no prefix/namespace was found,
        // check default registered paths
        foreach (self::$paths as $dir)
        {
            $file = $dir . DIRECTORY_SEPARATOR . str_replace(array('_', '\\', '/'), DIRECTORY_SEPARATOR, $class) . '.php';
            if (file_exists($file))
            {
                /** @noinspection PhpIncludeInspection */
                require $file;
                return true;
            }
        }

        // If we are here, we didn't find the class :(
        return false;
    }
}
// EOF