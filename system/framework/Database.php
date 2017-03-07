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

use System\Database\DbConnection;

/**
 * Database Factory Class
 *
 * @author      Steven Wilson
 * @package     Database
 */
class Database
{
    /**
     * An array of all stored connections
     * @var DbConnection[]
     */
    protected static $connections = array();

    /**
     * Initiates a new database connection.
     *
     * @param string $name Name or ID of the connection
     * @param array $i The database connection information
     *     array(
     *       'driver'
     *       'host'
     *       'port'
     *       'database'
     *       'username'
     *       'password')
     * @param bool $new If connection already exists, setting to true
     *    will overwrite the old connection ID with the new connection
     *
     * @throws \Exception
     * @return \System\Database\DbConnection Returns a Database Driver Object
     */
    public static function Connect($name, $i, $new = false)
    {
        // If the connection already exists, and $new is false, return existing
        if (isset(self::$connections[$name]) && !$new)
            return self::$connections[$name];

        // Connect using the PDO Constructor
        self::$connections[$name] = new DbConnection(
            $i['host'],
            $i['port'],
            $i['database'],
            $i['username'],
            $i['password']
        );

        return self::$connections[$name];
    }

    /**
     * Returns the connection object for the given Name or ID
     *
     * @param string $name Name or ID of the connection
     *
     * @return bool|\System\Database\DbConnection Returns a Database Driver Object,
     *    or false of the connection $name doesn't exist
     */
    public static function GetConnection($name = 'bf2stats')
    {
        if (isset(self::$connections[$name]))
            return self::$connections[$name];

        return false;
    }
}