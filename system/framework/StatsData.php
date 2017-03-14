<?php
/**
 * BF2Statistics ASP Management
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;

class StatsData
{
    /**
     * @var int The number or army types in the database
     */
    public static $NumArmies = 0;

    /**
     * @var int The number or kit types in the database
     */
    public static $NumKits = 0;

    /**
     * @var int The number or vehicle types in the database
     */
    public static $NumVehicles = 0;

    /**
     * @var int The number or weapon types in the database
     */
    public static $NumWeapons = 0;

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
            $result = $pdo->query("SELECT name FROM army")->fetchAll();
            self::$NumArmies = count($result);
            for ($i = 0; $i < self::$NumArmies; $i++)
                self::$ArmyNames[] = $result[$i]['name'];

            // Load kits
            $result = $pdo->query("SELECT name FROM kit")->fetchAll();
            self::$NumKits= count($result);
            for ($i = 0; $i < self::$NumKits; $i++)
                self::$KitNames[] = $result[$i]['name'];

            // Load vehicles
            $result = $pdo->query("SELECT name FROM vehicle")->fetchAll();
            self::$NumVehicles = count($result);
            for ($i = 0; $i < self::$NumVehicles; $i++)
                self::$VehicleNames[] = $result[$i]['name'];

            // Load weapons
            $result = $pdo->query("SELECT name FROM weapon")->fetchAll();
            self::$NumWeapons = count($result);
            for ($i = 0; $i < self::$NumWeapons; $i++)
                self::$WeaponNames[] = $result[$i]['name'];
        }
    }
}