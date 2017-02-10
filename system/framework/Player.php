<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;


use System\Collections\Dictionary;

class Player
{
    /**
     * @var int The players PID
     */
    public $pid = 0;

    /**
     * @var string The players name
     */
    public $name = '';

    /**
     * @var int The players rank at the end of the round
     */
    public $rank = 0;

    /**
     * @var string The players IP Address
     */
    public $ipAddress = '';

    /**
     * @var bool Specifies if this player is an AI controlled player
     */
    public $isAi = false;

    /**
     * @var int Indicates the players overall score at the end of the round
     */
    public $roundScore = 0;

    /**
     * @var int Indicates the players round time played in seconds
     */
    public $roundTime = 0;

    /**
     * @var int Indicates the players command score at the end of the round
     */
    public $commandScore = 0;

    /**
     * @var int Indicates the players skill score at the end of the round
     */
    public $skillScore = 0;

    /**
     * @var int Indicates the players team score at the end of the round
     */
    public $teamScore = 0;

    /**
     * @var int Indicates the Army ID this player was playing as
     */
    public $armyId = 0;

    /**
     * @var int Indicates the Team the player was on
     */
    public $team = 0;

    /**
     * @var bool Returns whether or not the player was present at the end of the round
     */
    public $completedRound = false;

    /**
     * @var int Returns the Time played as a Commander this round
     */
    public $cmdTime = 0;

    /**
     * @var int Returns the Time played as a Squad Leader this round
     */
    public $sqlTime = 0;

    /**
     * @var int Returns the Time played as a Squad Member this round
     */
    public $sqmTime = 0;

    /**
     * @var int Returns the Time played as a Lone Wolf this round
     */
    public $lwTime = 0;

    /**
     * @var int Indicates the number of times this player was kicked from the server
     */
    public $timesKicked = 0;

    /**
     * @var int Indicates the number of times this player was banned
     */
    public $timesBanned = 0;

    /**
     * @var array A list of players that were killed by this player [VictimPid => KillCount]
     */
    public $victims = array();

    /**
     * @var ObjectStat[] An array of the players weapon data and scores
     */
    public $weaponData = array();

    /**
     * @var ObjectStat[] An array of the players kit data and scores
     */
    public $kitData = array();

    /**
     * @var ObjectStat[] An array of the players vehicle data and scores
     */
    public $vehicleData = array();

    /**
     * @var int[] An array containing the seconds played as each army by ID
     */
    public $timeAsArmy = array();

    /**
     * @var int The time the player spent in a parachute
     */
    public $timeParachute = 0;

    public $kills = 0;
    public $deaths = 0;
    public $suicides = 0;
    public $killStreak = 0;
    public $deathStreak = 0;

    public $heals = 0;
    public $revives = 0;
    public $ammos = 0;
    public $repairs = 0;

    public $flagCaptures = 0;
    public $flagCaptureAssists = 0;
    public $flagDefends = 0;

    public $damageAssists = 0;
    public $targetAssists = 0;
    public $driverSpecials = 0;

    public $teamKills = 0;
    public $teamDamage = 0;
    public $teamVehicleDamage = 0;

    /**
     * Player constructor.
     *
     * @param Dictionary $playerData
     * @param array $killData
     */
    public function __construct(Dictionary $playerData, array $killData)
    {
        /**
         * We use a dictionary here, so that we get a nice exception
         * thrown if a key does not exist!
         */
        $this->pid = (int)$playerData['pID'];
        $this->name = $playerData['name'];
        $this->rank = (int)$playerData['rank'];
        $this->roundScore = (int)$playerData['rs'];
        $this->roundTime = (int)$playerData['ctime'];
        $this->commandScore = (int)$playerData['cs'];
        $this->skillScore = (int)$playerData['ss'];
        $this->teamScore = (int)$playerData['ts'];
        $this->armyId = (int)$playerData['a'];
        $this->team = (int)$playerData['t'];
        $this->completedRound = ((int)$playerData['c']) == 1;
        $this->cmdTime = (int)$playerData['tco'];
        $this->sqlTime = (int)$playerData['tsl'];
        $this->sqmTime = (int)$playerData['tsm'];
        $this->lwTime = (int)$playerData['tlw'];
        $this->timesKicked = (int)$playerData['kck'];
        $this->timesBanned = (int)$playerData['ban'];
        $this->isAi = ((int)$playerData['ai']) == 1;
        $this->ipAddress = preg_replace("/[^A-Za-z0-9.:]/", '', $playerData['ip']);

        $this->kills = (int)$playerData["kills"];
        $this->deaths = (int)$playerData["deaths"];
        $this->suicides = (int)$playerData["su"];
        $this->killStreak = (int)$playerData["ks"];
        $this->deathStreak = (int)$playerData["ds"];
        $this->heals = (int)$playerData["he"];
        $this->revives = (int)$playerData["rev"];
        $this->repairs = (int)$playerData["rep"];
        $this->ammos = (int)$playerData["rsp"];
        $this->flagCaptures = (int)$playerData["cpc"];
        $this->flagCaptureAssists = (int)$playerData["cpa"];
        $this->flagDefends = (int)$playerData["cpd"];
        $this->damageAssists = (int)$playerData["ka"];
        $this->targetAssists = (int)$playerData["tre"];
        $this->driverSpecials = (int)$playerData["drs"];
        $this->teamKills = (int)$playerData["tmkl"];
        $this->teamDamage = (int)$playerData["tmdg"];
        $this->teamVehicleDamage = (int)$playerData["tmvd"];

        // Add parachute
        $this->timeParachute = (int)$playerData["tvp"];

        // Extract Army Data
        for ($i = 0; $i < NUM_ARMIES; $i++)
            $this->timeAsArmy[$i] = (int)$playerData['ta' . $i];

        // Extract Kit Data
        for ($i = 0; $i < NUM_KITS; $i++)
        {
            $object = new ObjectStat();
            $object->time = (int)$playerData["tk{$i}"];
            $object->kills = (int)$playerData["kk{$i}"];
            $object->deaths = (int)$playerData["dk{$i}"];
            $this->kitData[$i] = $object;
        }

        // Extract Vehicle Data
        for ($i = 0; $i < NUM_VEHICLES; $i++)
        {
            $object = new ObjectStat();
            $object->time = (int)$playerData["tv{$i}"];
            $object->kills = (int)$playerData["kv{$i}"];
            $object->deaths = (int)$playerData["bv{$i}"];
            $object->roadKills = (int)$playerData["kvr{$i}"];
            $this->vehicleData[$i] = $object;
        }

        // Extract Weapon Data 1
        for ($i = 0; $i < 9; $i++)
        {
            $object = new ObjectStat();
            $object->time = (int)$playerData["tw{$i}"];
            $object->kills = (int)$playerData["kw{$i}"];
            $object->deaths = (int)$playerData["bw{$i}"];
            $object->fired = (int)$playerData["sw{$i}"];
            $object->hits = (int)$playerData["hw{$i}"];
            $this->weaponData[$i] = $object;
        }

        // Extract more weapon data (Keys change)
        $j = 9;
        for ($i = 0; $i < 9; $i++)
        {
            if ($i < 6)
            {
                $object = new ObjectStat();
                $object->time = (int)$playerData["te{$i}"];
                $object->kills = (int)$playerData["ke{$i}"];
                $object->deaths = (int)$playerData["be{$i}"];
                $object->fired = (int)$playerData["se{$i}"];
                $object->hits = (int)$playerData["he{$i}"];
                $this->weaponData[$j++] = $object;
            }
            else if ($i == 6)
            {
                $object = new ObjectStat();
                $object->time = (int)$playerData["te6"];
                $object->deployed = (int)$playerData["de6"];
                $this->weaponData[$j++] = $object;
            }
            else // Grappling hook && Zipline
            {
                $be = ($i == 7) ? 9 : 8; // Coding error in the python!
                $object = new ObjectStat();
                $object->time = (int)$playerData["te{$i}"];
                $object->deaths = (int)$playerData["be{$be}"];
                $object->deployed = (int)$playerData["de{$i}"];
                $this->weaponData[$j++] = $object;
            }
        }

        // Sometimes Squad times are negative.. idk why, but we need to fix that here
        if ($this->sqlTime < 0) $this->sqlTime = 0;
        if ($this->sqmTime < 0) $this->sqmTime = 0;
        if ($this->lwTime < 0) $this->lwTime = 0;
    }
}

class ObjectStat
{
    public $time = 0;
    public $kills = 0;
    public $deaths = 0;
    public $fired = 0;
    public $hits = 0;
    public $roadKills = 0;
    public $deployed = 0;
}

// Grab game constants
defined("NUM_ARMIES") or include SYSTEM_PATH . DIRECTORY_SEPARATOR . "GameConstants.php";