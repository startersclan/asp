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
 * This object represents an SQL expression within a Where clause.
 *
 * @package System\Database\QueryBuilder
 */
class SqlExpression
{
    /**
     * @var int Gets or Sets the ExpressionType
     */
    protected $type;

    /**
     * @var string The fieldName for this expression
     */
    protected $fieldName;

    /**
     * @var mixed The value of the expression
     */
    protected $value;

    /**
     * @var SelectWhereStatement The where statement this expression belongs to
     */
    protected $statement;

    /**
     * WhereCondition constructor.
     *
     * @param string $fieldName
     * @param SelectWhereStatement $statement
     */
    public function __construct($fieldName, SelectWhereStatement $statement)
    {
        $this->statement = $statement;
        $this->fieldName = $fieldName;
    }

    /**
     * Specifies the comparison of this expression with an Equal operator
     *
     * @param mixed $value The value of this expression
     * @param bool $literal Specifies whether the $value should be converted to an SqlLiteral
     *  value, in which quoting of the value will not be done.
     *
     * @return SelectWhereStatement
     */
    public function equals($value, $literal = false)
    {
        $this->type = ExpressionType::EQUAL;
        $this->value = ($literal || is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a Not equal operator
     *
     * @param mixed $value The value of this expression
     * @param bool $literal Specifies whether the $value should be converted to an SqlLiteral
     *  value, in which quoting of the value will not be done.
     *
     * @return SelectWhereStatement
     */
    public function notEqualTo($value, $literal = false)
    {
        $this->type = ExpressionType::NOT_EQUAL;
        $this->value = ($literal || is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a LIKE operator
     *
     * @param mixed $value The value of this expression
     *
     * @return SelectWhereStatement
     */
    public function like($value)
    {
        $this->type = ExpressionType::LIKE;
        $this->value = (is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a NOT LIKE operator
     *
     * @param mixed $value The value of this expression
     *
     * @return SelectWhereStatement
     */
    public function notLike($value)
    {
        $this->type = ExpressionType::NOT_LIKE;
        $this->value = (is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a Greater Than operator
     *
     * @param mixed $value The value of this expression
     * @param bool $literal Specifies whether the $value should be converted to an SqlLiteral
     *  value, in which quoting of the value will not be done.
     *
     * @return SelectWhereStatement
     */
    public function greaterThan($value, $literal = false)
    {
        $this->type = ExpressionType::GT;
        $this->value = ($literal || is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a Greater Than or Equals operator
     *
     * @param mixed $value The value of this expression
     * @param bool $literal Specifies whether the $value should be converted to an SqlLiteral
     *  value, in which quoting of the value will not be done.
     *
     * @return SelectWhereStatement
     */
    public function greaterOrEquals($value, $literal = false)
    {
        $this->type = ExpressionType::GTE;
        $this->value = ($literal || is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a Less Than operator
     *
     * @param mixed $value The value of this expression
     * @param bool $literal Specifies whether the $value should be converted to an SqlLiteral
     *  value, in which quoting of the value will not be done.
     *
     * @return SelectWhereStatement
     */
    public function lessThan($value, $literal = false)
    {
        $this->type = ExpressionType::LT;
        $this->value = ($literal || is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression with a Less Than or Equals operator
     *
     * @param mixed $value The value of this expression
     * @param bool $literal Specifies whether the $value should be converted to an SqlLiteral
     *  value, in which quoting of the value will not be done.
     *
     * @return SelectWhereStatement
     */
    public function lessOrEquals($value, $literal = false)
    {
        $this->type = ExpressionType::LTE;
        $this->value = ($literal || is_numeric($value)) ? new SqlLiteral($value) : $value;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression is within a set of values
     *
     * @param array $values A set of values to test the expression in
     *
     * @return SelectWhereStatement
     */
    public function in(array $values)
    {
        $this->type = ExpressionType::IN;
        $this->value = $values;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression is NOT within a set of values
     *
     * @param array $values A set of values to test the expression in
     *
     * @return SelectWhereStatement
     */
    public function notIn(array $values)
    {
        $this->type = ExpressionType::NOT_IN;
        $this->value = $values;
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression is within a range of values
     *
     * @param mixed $value1
     * @param mixed $value2
     *
     * @return SelectWhereStatement
     */
    public function between($value1, $value2)
    {
        $this->type = ExpressionType::BETWEEN;
        $this->value = [$value1, $value2];
        return $this->statement;
    }

    /**
     * Specifies the comparison of this expression is NOT within a range of values
     *
     * @param mixed $value1
     * @param mixed $value2
     *
     * @return SelectWhereStatement
     */
    public function notBetween($value1, $value2)
    {
        $this->type = ExpressionType::NOT_BETWEEN;
        $this->value = [$value1, $value2];
        return $this->statement;
    }

    public function buildExpression()
    {

    }
}