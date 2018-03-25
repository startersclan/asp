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
use System\IO\Path;

/**
 * This class provides common methods for fetching Battlefield 2 related information
 *
 * @package System
 */
class Battlefield2
{
    /**
     * @var array
     */
    protected static $Ranks = [];

    /**
     * Converts a badge level to its string name
     *
     * @param int $level The badge level
     *
     * @return string
     */
    public static function GetBadgePrefix($level)
    {
        if ($level == 3) return 'Gold';
        else if ($level == 2) return 'Silver';
        else return 'Bronze';
    }

    /**
     * Gets the name of a game mode by its id
     *
     * @param int $gamemode
     *
     * @return string
     */
    public static function GetGameModeString($gamemode)
    {
        switch ((int)$gamemode)
        {
            default: return "Unknown";
            case 0: return "Conquest";
            case 1: return "Single Player";
            case 2: return "Coop";
        }
    }

    /**
     * Fetches the name of a rank by ID
     *
     * @param int $rank
     *
     * @return string
     */
    public static function GetRankName($rank)
    {
        if (empty(self::$Ranks))
        {
            /** @noinspection PhpIncludeInspection */
            self::$Ranks = include Path::Combine(SYSTEM_PATH, 'config', 'ranks.php');
        }
        return ($rank > count(self::$Ranks)) ? "Unknown ({$rank})" : self::$Ranks[$rank]['title'];
    }
}