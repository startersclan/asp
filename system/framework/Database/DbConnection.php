<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * @copyright   2013, BF2Statistics.com
 * @license     GNU GPL v3
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
     * The result of the last query
     */
    public $result;

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
        $dsn = "mysql:host={$server};port={$port};dbname={$dbname}";
        parent::__construct($dsn, $username, $password, array(\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION));
    }

    /**
     * Executes an SQL query on the database
     *
     * @param string $query The sql query to execute
     *
     * @return \PDOStatement
     */
    public function query($query)
    {
        return parent::query($query);
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
        if(is_array($where))
        {
            $sql = null;
            foreach($where as $col => $value)
                $sql .= "`{$col}`='{$value}' AND ";

            $where = substr($sql, 0, -5);
        }

        // Return TRUE or FALSE
        return ($this->exec('DELETE FROM ' . $table . ($where != '' ? ' WHERE ' . $where : '')) > 0);
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
        // enclose the column names in grave accents
        $cols = '`' . implode('`,`', array_keys($data)) . '`';
        $values = '';

        // question marks for escaping values later on
        $count = count($data);
        for($i = 0; $i < $count; $i++)
            $values .= (is_numeric($data[$i])) ? $data[$i] . ", " : $this->quote($data[$i]) . ", ";

        // run the query
        return $this->exec('INSERT INTO ' . $table . '(' . $cols . ') VALUES (' . rtrim($values, ', ') . ')');
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
        // Parse where clause
        if(is_array($where))
        {
            $sql = null;
            foreach($where as $col => $value)
                $sql .= "`{$col}`='{$value}' AND ";

            $where = substr($sql, 0, -5);
        }

        // Do we have a where tp process?
        if($where != '')
            $where = ' WHERE ' . $where;

        // Our string of columns
        $cols = '';

        // start creating the SQL string and enclose field names in `
        foreach($data as $key => $value)
            $cols .= ', `' . $key . '` = '. (is_numeric($value)) ? $value : $this->quote($value);

        // Build our query
        return $this->exec('UPDATE ' . $table . ' SET ' . ltrim($cols, ', ') . $where);
    }
}