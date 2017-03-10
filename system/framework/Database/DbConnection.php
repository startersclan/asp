<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System\Database;

use PDO;

/**
 * Class DbConnection, PDO extension driver
 *
 * @author      Steven Wilson
 * @package     Asp
 * @subpackage  Database
 */
class DbConnection extends PDO
{
    /**
     * @var string
     */
    public $lastQuery;

    /**
     * Constructor
     *
     * @param string $server The database server ip
     * @param int $port The database server port
     * @param string $dbname The database name to connect to
     * @param string $username A database user with privileges
     * @param string $password The database user's password
     */
    public function __construct($server, $port, $dbname, $username, $password)
    {
        // Connect using the PDO Constructor
        $dsn = "mysql:host={$server};port={$port};dbname={$dbname};charset=UTF8";
        $opt = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_LOCAL_INFILE => true
        ];
        parent::__construct($dsn, $username, $password, $opt);
    }

    /**
     * An easy method that will delete data from a table
     *
     * @param string $table The table name we are updating
     * @param string|string[] $where The where statement Ex: "id = 5"
     *   Also accepts an array of $column => $value
     *
     * @return bool Returns TRUE on success of FALSE on error
     */
    public function delete($table, $where)
    {
        // Parse where clause
        if (is_array($where))
        {
            $sql = null;
            foreach ($where as $col => $value)
                $sql .= "`{$col}`='{$value}' AND ";

            $where = substr($sql, 0, -5);
        }

        // Return TRUE or FALSE
        $this->lastQuery = 'DELETE FROM ' . $table . ($where != '' ? ' WHERE ' . $where : '');

        return $this->exec($this->lastQuery) > 0;
    }

    /**
     * An easy method that will insert data into a table
     *
     * @param string $table The table name we are inserting into
     * @param mixed[] $data An array of "column => value"'s
     *
     * @return bool Returns TRUE on success of FALSE on error
     */
    public function insert($table, $data)
    {
        // Escape values for the query
        foreach ($data as $key => $value)
        {
            if (!is_int($value))
                $data[$key] = $this->quote($value);
        }

        // enclose the column names in grave accents
        $columns = implode('`, `', array_keys($data));
        $values = implode(', ', array_values($data));

        // Run the query
        $this->lastQuery = "INSERT INTO `{$table}`(`{$columns}`) VALUES ({$values})";

        return $this->exec($this->lastQuery);
    }

    /**
     * An easy method that will update an existing row in a table
     *
     * @param string $table The table name we are updating
     * @param mixed[] $data An array of "column => value"'s
     * @param string|string[] $where The where statement Ex: "id = 5"
     *   Also accepts an array of $column => $value
     *
     * @return bool Returns TRUE on success of FALSE on error
     */
    public function update($table, $data, $where)
    {
        // Our string of columns
        $query = "UPDATE `{$table}` SET ";

        // start creating the SQL string and enclose field names in `
        $first = true;
        foreach ($data as $key => $value)
        {
            if (!$first)
            {
                $query .= ', ';
            }

            $first = false;
            $val = (is_int($value)) ? $value : $this->quote($value);
            $query .= "`{$key}`={$val}";
        }

        // Parse where clause
        if (!empty($where))
        {
            if (is_array($where))
            {
                $sql = ' WHERE ';
                foreach ($where as $col => $value)
                {
                    $val = (is_int($value)) ? $value : $this->quote($value);
                    $sql .= "`{$col}`={$val} AND ";
                }

                $query .= substr($sql, 0, -5);
            }
            else
                $query .= $where;
        }

        // Build our query
        $this->lastQuery = $query;

        return $this->exec($this->lastQuery);
    }
}