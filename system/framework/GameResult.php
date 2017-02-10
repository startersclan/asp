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

abstract class GameResult
{
    /**
     * Epoch timestamp of when the Round Started (Python's time.time() function)
     *
     * @var int
     */
    public $roundStartTime;

    /**
     * Epoch timestamp of when the Round Ended (Python's time.time() function)
     *
     * @var int
     */
    public $roundEndTime;

    /**
     * Returns the timespan of time the round lasted from start to finish in seconds
     *
     * @var int
     */
    public $roundTime;

    /**
     * Snapshot Server prefix
     *
     * @var string
     */
    public $serverPrefix;

    /**
     * Snapshot Server name
     *
     * @var string
     */
    public $serverName;

    /**
     * Snapshot Server Port
     *
     * @var int
     */
    public $serverPort;

    /**
     * Snapshot Server Gamespy Query Port
     *
     * @var int
     */
    public $queryPort;

    /**
     * Map ID Played during this round
     *
     * @var int
     */
    public $mapId;

    /**
     * Map name Played during this round
     *
     * @var string
     */
    public $mapName;

    /**
     * Is this a custom map?
     *
     * @var bool
     */
    public $isCustomMap;

    /**
     * Total amount of kills from all players in the round
     *
     * @var int
     */
    public $mapKills;

    /**
     * Total amount of deaths from all players in the round
     *
     * @var int
     */
    public $mapDeaths;

    /**
     * Sum of all the players score in the given round
     *
     * @var int
     */
    public $mapScore;

    /**
     * The gamemode ID of this round (Cq, Coop, SP)
     *
     * @var int
     */
    public $gameMode;

    /**
     * Mod name that was played
     *
     * @var string
     */
    public $mod;

    /**
     * The winning team ID. Value is set to 0 if no team won (tie)
     *
     * @var int
     */
    public $winningTeam;

    /**
     * Winning Army ID. Value is set to -1 if no army won the round
     *
     * @var int
     */
    public $winningArmyId;

    /**
     * Team 1's Army Id
     *
     * @var int
     */
    public $team1ArmyId;

    /**
     * Team 2's Army Id
     *
     * @var int
     */
    public $team2ArmyId;

    /**
     * Remaining round tickets team 1
     *
     * @var int
     */
    public $team1Tickets;

    /**
     * Remaining round tickets team 2
     *
     * @var int
     */
    public $team2Tickets;

    /**
     * The number of team 1 players
     *
     * @var int
     */
    public $team1Players;

    /**
     * The number of team 2 players
     *
     * @var int
     */
    public $team2Players;

    /**
     * The number of team 1 players at the EOR
     *
     * @var int
     */
    public $team1PlayersEnd;

    /**
     * The number of team 2 players at the EOR
     *
     * @var int
     */
    public $team2PlayersEnd;

    /**
     * The total number of players who played in the round
     *
     * @var int
     */
    public $playersConnected;

    /**
     * The list of players and their data that played this round
     *
     * @var Player[]
     */
    public $players = [];
}