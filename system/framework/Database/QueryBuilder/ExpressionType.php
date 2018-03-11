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

class ExpressionType
{
    const EQUAL = 1;

    const NOT_EQUAL = 2;

    const LIKE = 3;

    const NOT_LIKE = 4;

    const GT = 5;

    const GTE = 6;

    const LT = 7;

    const LTE = 8;

    const IN = 9;

    const NOT_IN = 10;

    const BETWEEN = 11;

    const NOT_BETWEEN = 12;
}