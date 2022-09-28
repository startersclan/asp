<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2021, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System;
use ArgumentException;

/**
 * Represents a time interval.
 *
 * @package System
 */
class TimeSpan
{
    protected $seconds = 0;

    /**
     * Constructor
     *
     * @param int $hours
     * @param int $mins
     * @param int $secs secs - an amount of seconds, absolute value is used
     *
     * @throws ArgumentException if the argument passed is not numeric
     */
    public function __construct($hours, $mins, $secs)
    {
        if (!is_numeric($hours))
            throw new ArgumentException('Given argument is not an integer: ' . gettype($hours), 'hours');

        if (!is_numeric($mins))
            throw new ArgumentException('Given argument is not an integer: ' . gettype($mins), 'mins');

        if (!is_numeric($secs))
            throw new ArgumentException('Given argument is not an integer: ' . gettype($secs), 'secs');

        // Append seconds
        $this->seconds = (int)abs($secs);

        // Append hours
        if ($hours > 0)
            $this->seconds += (int)abs($hours * 3600);

        // Append minutes
        if ($mins > 0)
            $this->seconds += (int)abs($mins * 60);
    }

    /**
     * Add a TimeSpan
     *
     * @param TimeSpan $span
     *
     * @return TimeSpan
     * @throws ArgumentException
     */
    public function add(TimeSpan $span)
    {
        $this->seconds += $span->seconds;
        return $this;
    }

    /**
     * Subtracts a TimeSpan from this current TimeSpan object
     *
     * @param TimeSpan $span
     *
     * @return TimeSpan
     *
     * @throws ArgumentException
     */
    public function subtract(TimeSpan $span)
    {
        // Check for new negative value
        if ($span->seconds > $this->seconds)
        {
            throw new ArgumentException('Cannot subtract ' . $span->toString() . ' from ' . $this->toString());
        }

        $this->seconds -= $span->seconds;
        return $this;
    }

    /**
     * Gets a timespan from seconds
     *
     * @param int $seconds
     *
     * @return TimeSpan
     */
    public static function FromSeconds($seconds)
    {
        return new self(0, 0, $seconds);
    }

    /**
     * Gets a timespan from minutes
     *
     * @param int $minutes
     *
     * @return TimeSpan
     */
    public static function FromMinutes($minutes)
    {
        return new self(0, $minutes, 0);
    }

    /**
     * Gets a timespan from hours
     *
     * @param int $hours
     *
     * @return TimeSpan
     */
    public static function FromHours($hours)
    {
        return new self($hours, 0, 0);
    }

    /**
     * Gets a timespan from days
     *
     * @param int $days
     *
     * @return TimeSpan
     */
    public static function days($days)
    {
        return new self(0, 0, $days * 86400);
    }

    /**
     * Gets a timespan from weeks
     *
     * @param int $weeks
     *
     * @return TimeSpan
     */
    public static function FromWeeks($weeks)
    {
        return new self(0, 0, $weeks * 604800);
    }

    /**
     * Returns this span of time in seconds
     *
     * @return  int
     */
    public function getSeconds()
    {
        return $this->seconds;
    }

    /**
     * Returns the amount of 'whole' seconds in this
     * span of time
     *
     * @return  int
     */
    public function getWholeSeconds()
    {
        return $this->seconds % 60;
    }

    /**
     * Return an amount of minutes less than or equal
     * to this span of time
     *
     * @return  int
     */
    public function getMinutes()
    {
        return (int)floor($this->seconds / 60);
    }

    /**
     * Returns a float value representing this span of time
     * in minutes
     *
     * @return  float
     */
    public function getMinutesFloat()
    {
        return $this->seconds / 60;
    }

    /**
     * Returns the amount of 'whole' minutes in this
     * span of time
     *
     * @return  int
     */
    public function getWholeMinutes()
    {
        return (int)floor(($this->seconds % 3600) / 60);
    }

    /**
     * Adds an amount of minutes to this span of time
     *
     * @param int $mins
     */
    public function addMinutes($mins)
    {
        $this->seconds += (int)$mins * 60;
    }

    /**
     * Returns an amount of hours less than or equal
     * to this span of time
     *
     * @return  int
     */
    public function getHours()
    {
        return (int)floor($this->seconds / 3600);
    }

    /**
     * Returns a float value representing this span of time
     * in hours
     *
     * @return  float
     */
    public function getHoursFloat()
    {
        return $this->seconds / 3600;
    }

    /**
     * Returns the amount of 'whole' hours in this
     * span of time
     *
     * @return  int
     */
    public function getWholeHours()
    {
        return (int)floor(($this->seconds % 86400) / 3600);
    }

    /**
     * Adds an amount of Hours to this span of time
     *
     * @param int $hours
     */
    public function addHours($hours)
    {
        $this->seconds += (int)$hours * 3600;
    }

    /**
     * Returns an amount of days less than or equal
     * to this span of time
     *
     * @return  int
     */
    public function getDays()
    {
        return (int)floor($this->seconds / 86400);
    }

    /**
     * Returns a float value representing this span of time
     * in days
     *
     * @return  float
     */
    public function getDaysFloat()
    {
        return $this->seconds / 86400;
    }

    /**
     * Returns the amount of 'whole' days in this
     * span of time
     *
     * @return  int
     */
    public function getWholeDays()
    {
        return $this->getDays();
    }

    /**
     * Adds an amount of Days to this span of time
     *
     * @param int $days
     */
    public function addDays($days)
    {
        $this->seconds += (int)$days * 86400;
    }

    /**
     * Format timespan
     *
     * Format tokens are:
     * <pre>
     * %s   - seconds
     * %w   - 'whole' seconds
     * %m   - minutes
     * %M   - minutes (float)
     * %j   - 'whole' minutes
     * %h   - hours
     * %H   - hours (float)
     * %y   - 'whole' hours
     * %d   - days
     * %D   - days (float)
     * %e   - 'whole' days
     * </pre>
     *
     * @param   string $format
     *
     * @return  string the formatted timespan
     */
    public function format($format)
    {
        $return = '';
        $o = 0;
        $l = strlen($format);
        while (false !== ($p = strcspn($format, '%', $o)))
        {
            $return .= substr($format, $o, $p);
            if (($o += $p + 2) <= $l)
            {
                switch ($format{$o - 1})
                {
                    case 's':
                        $return .= $this->getSeconds();
                        break;
                    case 'w':
                        $return .= $this->getWholeSeconds();
                        break;
                    case 'm':
                        $return .= $this->getMinutes();
                        break;
                    case 'M':
                        $return .= sprintf('%.2f', $this->getMinutesFloat());
                        break;
                    case 'j':
                        $return .= $this->getWholeMinutes();
                        break;
                    case 'h':
                        $return .= $this->getHours();
                        break;
                    case 'H':
                        $return .= sprintf('%.2f', $this->getHoursFloat());
                        break;
                    case 'y':
                        $return .= $this->getWholeHours();
                        break;
                    case 'd':
                        $return .= $this->getDays();
                        break;
                    case 'D':
                        $return .= sprintf('%.2f', $this->getDaysFloat());
                        break;
                    case 'e':
                        $return .= $this->getWholeDays();
                        break;
                    case '%':
                        $return .= '%';
                        break;
                    default:
                        $o--;
                }
            }
        }

        return $return;
    }

    /**
     * Indicates whether the timespan to compare equals this timespan
     *
     * @param TimeSpan $cmp
     *
     * @return bool true if the two timespan objects are equal
     */
    public function equals($cmp)
    {
        return ($cmp instanceof TimeSpan) && ($cmp->getSeconds() == $this->getSeconds());
    }

    /**
     * Creates a string representation
     *
     * @param string $format, defaults to '%ed, %yh, %jm, %ws'
     *
     * @return string
     */
    public function toString($format = '%ed, %yh, %jm, %ws')
    {
        return $this->format($format);
    }

    public function __toString()
    {
        return $this->toString();
    }
}