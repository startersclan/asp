<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System\Database\QueryBuilder;

use PDO;
use System\Database\DbConnection;

/**
 * Class SelectQuery
 * @package System\Database\QueryBuilder
 */
class SelectQuery
{
    /**
     * @var DbConnection
     */
    protected $connection;

    /**
     * @var string
     */
    protected $fromTable = '';

    /**
     * @var string
     */
    protected $fromTableAlias = '';

    /**
     * @var SelectWhereStatement
     */
    protected $whereStatement;

    /**
     * @var array
     */
    protected $parameters = [];

    /**
     * @var int
     */
    protected $limit = 0;

    /**
     * @var int
     */
    protected $offset = 0;

    /**
     * @var array [colName => DESC|ASC]
     */
    protected $ordered = [];

    /**
     * @var array [colName => DESC|ASC]
     */
    protected $groupBy = [];

    /**
     * SelectQuery constructor.
     *
     * @param DbConnection $connection
     * @param $table
     */
    public function __construct(DbConnection $connection, $table)
    {
        $this->connection = $connection;
        $this->table = $table;
        $this->whereStatement = new SelectWhereStatement($this);
    }

    /**
     * @param string[] ...$columns
     *
     * @return SelectQuery
     */
    public function select(...$columns)
    {
        return $this;
    }

    public function selectRaw($raw, $alias)
    {

    }

    public function selectAs($column, $alias)
    {

    }

    public function selectCount($name, $alias = null, $distinct = false)
    {

    }

    public function selectMax($name, $alias = null)
    {

    }

    public function selectMin($name, $alias = null)
    {

    }

    public function selectAvg($name, $alias = null)
    {

    }

    public function join($table, $column)
    {

    }

    public function leftJoin($table, $column)
    {

    }

    public function rightJoin($table, $column)
    {

    }

    public function innerJoin($table, $column)
    {

    }

    /**
     * @param string $field
     *
     * @return SqlExpression
     */
    public function where($field)
    {
        // Create a new where condition
        return $this->whereStatement->andWhere($field);
    }

    public function limit($count)
    {

    }

    public function offset($offset)
    {

    }

    public function orderBy($field)
    {

    }

    public function setParam($name, $value, $pdoType = PDO::PARAM_STR)
    {
        $this->parameters[$name] = [$value, $pdoType];
    }

    public function execute()
    {

    }

    public function fetch(...$columns)
    {

    }

    public function fetchAll(...$columns)
    {

    }

    protected function buildQuery()
    {

    }
}