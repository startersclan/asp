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
    public static $NumArmies = 0;

    public static $NumKits = 0;

    public static $NumVehicles = 0;

    public static $NumWeapons = 0;

    public static function Load()
    {
        if (self::$NumArmies == 0)
        {
            $pdo = Database::GetConnection('stats');

            self::$NumArmies = (int)$pdo->query("SELECT COUNT(id) FROM army")->fetchColumn(0);

            self::$NumKits = (int)$pdo->query("SELECT COUNT(id) FROM kit")->fetchColumn(0);

            self::$NumVehicles = (int)$pdo->query("SELECT COUNT(id) FROM vehicle")->fetchColumn(0);

            self::$NumWeapons = (int)$pdo->query("SELECT COUNT(id) FROM weapon")->fetchColumn(0);
        }
    }
}