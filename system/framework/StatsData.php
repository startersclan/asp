<?php
/**
 * BF2Statistics ASP Management
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;

class StatsData
{
    /**
     * @var int The number of army types in the database
     */
    public static $NumArmies = 0;

    /**
     * @var int The number of kit types in the database
     */
    public static $NumKits = 0;

    /**
     * @var int The number of vehicle types in the database
     */
    public static $NumVehicles = 0;

    /**
     * @var int The number of weapon types in the database
     */
    public static $NumWeapons = 0;

    /**
     * @var int The number of gamemodes in the database
     */
    public static $NumGamemodes = 0;

    /**
     * @var int The number of ranks in the database
     */
    public static $NumRanks = 0;

    /**
     * @var string[] An array of ArmyId => Army String Name
     */
    public static $ArmyNames = [];

    /**
     * @var string[] An array of KitId => Kit String Name
     */
    public static $KitNames = [];

    /**
     * @var string[] An array of VehicleId => Vehicle String Name
     */
    public static $VehicleNames = [];

    /**
     * @var string[] An array of WeaponId => Weapon String Name
     */
    public static $WeaponNames = [];

    /**
     * @var string[] An array of GamemodeId => Game Mode String Name
     */
    public static $GameModes = [];

    /**
     * @var string[] An array of RankId => Rank String Name
     */
    public static $RankNames = [];

    /**
     * Loads the stats data from the database if it has not been previously
     * called
     */
    public static function Load()
    {
        // Only load data if it has not been loaded yet
        if (self::$NumArmies == 0)
        {
            $pdo = Database::GetConnection('stats');

            // Load armies
            $result = $pdo->query("SELECT name FROM army ORDER BY id ASC");
            while ($row = $result->fetch())
            {
                self::$ArmyNames[] = $row['name'];
                self::$NumArmies++;
            }

            // Load kits
            $result = $pdo->query("SELECT name FROM kit ORDER BY id ASC");
            while ($row = $result->fetch())
            {
                self::$KitNames[] = $row['name'];
                self::$NumKits++;
            }

            // Load vehicles
            $result = $pdo->query("SELECT name FROM vehicle ORDER BY id ASC");
            while ($row = $result->fetch())
            {
                self::$VehicleNames[] = $row['name'];
                self::$NumVehicles++;
            }

            // Load weapons
            $result = $pdo->query("SELECT name FROM weapon ORDER BY id ASC");
            while ($row = $result->fetch())
            {
                self::$WeaponNames[] = $row['name'];
                self::$NumWeapons++;
            }

            // Load Game Modes
            $result = $pdo->query("SELECT name FROM game_mode ORDER BY id ASC");
            while ($row = $result->fetch())
            {
                self::$GameModes[] = $row['name'];
                self::$NumGamemodes++;
            }

            // Load Ranks
            $result = $pdo->query("SELECT name FROM `rank` ORDER BY id ASC");
            while ($row = $result->fetch())
            {
                self::$RankNames[] = $row['name'];
                self::$NumRanks++;
            }
        }
    }
}