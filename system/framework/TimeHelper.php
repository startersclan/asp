<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;


class TimeHelper
{
    /**
     * Converts a time from seconds to a string format of h:m:s
     *
     * @param $seconds
     *
     * @return string
     */
    public static function SecondsToHms($seconds)
    {
        $h = floor($seconds / 3600);
        $reste_secondes = $seconds - $h * 3600;

        $m = floor($reste_secondes / 60);
        $reste_secondes = $reste_secondes - $m * 60;

        $s = round($reste_secondes, 3);
        $s = number_format($s, 0, '.', '');

        $h = str_pad($h, 2, '0', STR_PAD_LEFT);
        $m = str_pad($m, 2, '0', STR_PAD_LEFT);
        $s = str_pad($s, 2, '0', STR_PAD_LEFT);

        $temps = $h . ":" . $m . ":" . $s;

        return $temps;
    }

    /**
     * Formats a time difference (timestamp) into a human readable format
     *
     * @param int $time1 The earliest time
     * @param int $time2 The latter time
     *
     * @return string
     */
    public static function FormatDifference($time1, $time2)
    {
        if ($time1 == 0)
            return 'Never';

        $secondsDif = $time2 - $time1;
        $now = new \DateTime("@". $time2);
        $last = new \DateTime("@". $time1);
        $dif = $now->diff($last);
        $parts = [];

        // Less than a minute ago
        if ($secondsDif < 60)
        {
            if ($dif->s > 0)
                $parts[] = $dif->s . (($dif->s > 1) ? ' seconds' : ' second');
        }
        // Less than a day ago
        else if ($secondsDif < 86400)
        {
            if ($dif->h > 0)
                $parts[] = $dif->h . (($dif->h > 1) ? ' hours' : ' hour');

            if ($dif->i > 0)
                $parts[] = $dif->i . (($dif->i > 1) ? ' minutes' : ' minute');
        }
        // Less than a month
        else if ($secondsDif < (60 * 60 * 24 * 30))
        {
            if ($dif->d > 0)
                $parts[] = $dif->d . (($dif->d > 1) ? ' days' : ' day');

            if ($dif->h > 0)
                $parts[] = $dif->h . (($dif->h > 1) ? ' hours' : ' hour');
        }
        // More than a month
        else
        {
            if ($dif->y > 0)
                $parts[] = $dif->y . (($dif->y > 1) ? ' years' : ' year');

            if ($dif->m > 0)
                $parts[] = $dif->m . (($dif->m > 1) ? ' months' : ' month');

            if ($dif->d > 0)
                $parts[] = $dif->d . (($dif->d > 1) ? ' days' : ' day');
        }

        return implode(', ', $parts) . " ago";
    }
}