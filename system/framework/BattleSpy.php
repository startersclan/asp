<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

namespace System;
use PDO;
use System\Database\UpdateOrInsertQuery;

/**
 * BattleSpy Anti-Cheat Analyzer
 *
 * This class is used as an Anti-Cheat software to catch and flag players who
 * exceed specified thresholds in the specified areas:
 * <pre>
 * - Score per minute exceeds threshold
 * - Excessive kills on a single player
 * - Excessive total kills in the round
 * - Excessive awards earned by a single player in the round
 * </pre>
 *
 * BattleSpy will never ban or suspend players. This system will
 * only flag players for further review by the stats admin.
 *
 * @package System
 */
class BattleSpy
{
    /**
     * @var int Cross Service Exploitation Flag
     */
    const FLAG_PLAYER_CSE = 1;

    /**
     * @var int Player Banned prior to round start Flag
     */
    const FLAG_PLAYER_BANNED = 2;

    /**
     * @var int Excessive Score Per Minute Flag
     */
    const FLAG_PLAYER_SPM = 3;

    /**
     * @var int Excessive Kill on a Player Flag
     */
    const FLAG_PLAYER_TARGET_KILLS = 4;

    /**
     * @var int Excessive Kill on a Player Flag
     */
    const FLAG_PLAYER_KILLS = 5;

    /**
     * @var int Excessive Earned Awards on Player Flag
     */
    const FLAG_PLAYER_AWARDS = 6;

    /**
     * @var array
     */
    protected $notifications;

    /**
     * @var PDO The database connection
     */
    protected $pdo;

    /**
     * @var int The server ID used for this report
     */
    protected $serverId;

    /**
     * @var int The round ID used for this report
     */
    protected $roundId;

    /**
     * @var bool Indicates whether BattleSpy is enabled
     */
    protected $enabled = false;

    /**
     * @var int Gets or Sets the maximum score per minute threshold
     */
    protected $maxSPM = 0;

    /**
     * @var int Gets or Sets the maximum kills per minute threshold
     */
    protected $maxKPM = 0;

    /**
     * @var int Gets or Sets the maximum kills per target threshold
     */
    protected $maxTargetKills = 0;

    /**
     * @var int Gets or Sets the maximum earned awards threshold (excludes backend awards)
     */
    protected $maxAwards = 0;

    /**
     * BattleSpy constructor.
     *
     * @param PDO $connection
     * @param int $serverId
     * @param int $roundId
     */
    public function __construct(PDO $connection, $serverId, $roundId)
    {
        // Set internals
        $this->pdo = $connection;
        $this->serverId = $serverId;
        $this->roundId = $roundId;

        // Load configuration
        $this->enabled = ((int)Config::Get('battlespy_enable') != 0);
        $this->maxSPM = (float)Config::Get('battlespy_max_spm');
        $this->maxKPM = (float)Config::Get('battlespy_max_kpm');
        $this->maxTargetKills = (int)Config::Get('battlespy_max_target_kills');
        $this->maxAwards = (int)Config::Get('battlespy_max_awards');
    }

    /**
     * Analyzes a players stats to check for cheating, and applies
     * an internal report if the player is found to be cheating.
     *
     * @param Player $player
     */
    public function analyze(Player $player)
    {
        // Quit if not enabled
        if (!$this->enabled) return;

        // Calculate total time the player played in the round by minutes
        $mins = round($player->roundTime / 60, 3);

        /** Check score per minute */
        $spm = ($mins == 0) ? 0 : round($player->roundScore / $mins, 3);
        if ($spm > $this->maxSPM)
        {
            $message = sprintf("Player Score per Min (%f) exceeds threshold of (%f)", $spm, $this->maxSPM);
            $this->report($player->pid, $message, self::FLAG_PLAYER_SPM);
        }

        /** Check kills per minute */
        $kpm = ($mins == 0) ? 0 : round($player->kills / $mins, 3);
        if ($kpm > $this->maxKPM)
        {
            $message = sprintf("Player Kills per Min (%f) exceeds threshold of (%f)", $kpm, $this->maxKPM);
            $this->report($player->pid, $message, self::FLAG_PLAYER_KILLS);
        }

        /** Check target kills */
        foreach ($player->victims as $pid => $count)
        {
            if ($count > $this->maxTargetKills)
            {
                $message = sprintf("Player Kills on Player (%d) exceeds threshold of (%d)", $pid, $this->maxTargetKills);
                $this->report($player->pid, $message, self::FLAG_PLAYER_TARGET_KILLS);
            }
        }

        /** Check awards */
        $awardCount = count($player->earnedAwards);
        if ($awardCount > $this->maxAwards)
        {
            $message = "Player Award Count (%d) exceeds threshold of (%d)";
            $this->report($player->pid, sprintf($message, $awardCount, $this->maxAwards), self::FLAG_PLAYER_AWARDS);
        }

        /** Check weapon accuracy */
    }

    /**
     * Adds a report message to the list of reports
     *
     * @param int $playerId
     * @param string $message
     * @param int $flagCode
     */
    public function report($playerId, $message, $flagCode)
    {
        $this->notifications[] = [
            'pid' => $playerId,
            'message' => $message,
            'flag' => $flagCode
        ];
    }

    /**
     * finalizes and saves the reported messages for
     * an admin to see.
     */
    public function finalize()
    {
        // Only create a database report if we have any messages
        if (!empty($this->notifications))
        {
            // Create report record for the database
            $query = new UpdateOrInsertQuery($this->pdo, 'battlespy_report');
            $query->set('serverid', '=', $this->serverId);
            $query->set('roundid', '=', $this->roundId);
            $query->executeInsert();

            // Grab report ID
            $reportId = $this->pdo->lastInsertId("id");

            // Insert report messages
            $query = new UpdateOrInsertQuery($this->pdo, 'battlespy_message');
            $query->set('reportid', '=', $reportId);

            // Insert messages
            foreach ($this->notifications as $report)
            {
                $query->setArray($report, '=');
                $query->executeInsert();
            }

            // Clear notifications
            $this->notifications = [];
        }
    }
}