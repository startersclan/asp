<?php
/**
 * BF2Statistics ASP Framework
 *
 * @copyright   2013 - 2018, BF2Statistics.com
 * @license     GNU GPL v3
 */

/**
 * ArgumentException
 *
 * @author      Steven Wilson
 * @package     System
 * @subpackage  Exceptions
 */
class ArgumentException extends Exception
{
    protected $argument = '';

    public function __construct($message, $argument = '', $inner = null)
    {
        parent::__construct($message, 0, $inner);
        $this->argument = $argument;
    }

    public function getArgument()
    {
        return $this->argument;
    }
}