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


class SelectWhereStatement
{
    protected $groups = [];

    protected $groupId = 0;

    /**'
     * @var SelectQuery
     */
    protected $query;

    protected $currentCondition;

    public $innerClauseOperatorAnd = true;

    /**
     * SelectWhereStatement constructor.
     *
     * @param SelectQuery $builder
     */
    public function __construct(SelectQuery $builder)
    {
        $this->query = $builder;
        $this->groups[] = [];
    }

    /**
     * Ends the current active clause, and creates a new one.
     */
    public function createNewClause()
    {
        if ($this->hasClause())
        {
            $this->groupId = count($this->groups);
            $this->groups[] = [];
        }
    }

    /**
     * Appends a new condition to the current WhereStatement
     *
     * @param string $fieldName
     *
     * @return SqlExpression
     */
    public function andWhere($fieldName)
    {
        // Create new clause if we are using the OR separator
        if (!$this->innerClauseOperatorAnd)
            $this->createNewClause();

        // Create new condition
        $condition = new SqlExpression($fieldName, $this);
        $this->groups[$this->groupId][] = $condition;
        $this->currentCondition = $condition;

        // Return condition for chaining
        return $condition;
    }

    /**
     * Appends a new condition to the current WhereStatement
     *
     * @param string $fieldName
     *
     * @return SqlExpression
     */
    public function orWhere($fieldName)
    {
        // Create new clause if we are using the AND separator
        if ($this->innerClauseOperatorAnd)
            $this->createNewClause();

        // Create new condition
        $condition = new SqlExpression($fieldName, $this);
        $this->groups[$this->groupId][] = $condition;
        $this->currentCondition = $condition;

        // Return condition for chaining
        return $condition;
    }

    /**
     * @return bool
     */
    protected function hasClause()
    {
        return !empty($this->groups[$this->groupId]);
    }
}