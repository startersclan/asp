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

class AwardData
{
    /**
     * @var array An array of all awards that are earned in game via python.
     */
    public static $PythonAwards = array();

    /**
     *
     * @var BackendAward[] An array of backend earned awards
     */
    public static $BackendAwards = array();

    /**
     * Loads and caches the award data if not already done.
     */
    public static function Load()
    {
        if (empty(self::$PythonAwards))
        {
            // Fetch non-backend awards
            $pdo = Database::GetConnection('stats');
            $result = $pdo->query("SELECT `id`, `code` FROM `award` WHERE `backend`=0");
            while ($row = $result->fetch())
            {
                $id = (int)$row['id'];
                self::$PythonAwards[$row['code']] = $id;
            }

            /** @noinspection PhpIncludeInspection */
            self::$BackendAwards = include Path::Combine(SYSTEM_PATH, "config", "backendAwards.php");
        }
    }
}