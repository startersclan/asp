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

/**
 * This class represents a Literal value to be used in an SQL query.
 * The string value will NOT be wrapped in quotations when inserted
 * into the query string.
 *
 * This object should only be used on value that are pre-checked
 * for SQL injection strings. Miss use of this class can leave the
 * database vulnerable to an attack.
 *
 * @package System\Database\QueryBuilder
 */
class SqlLiteral
{
    /**
     * @var mixed The Literal value to be appended to the query string
     */
    public $value;

    /**
     * SqlLiteral constructor.
     *
     * @param mixed $value The Literal value to be appended to the query string
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}