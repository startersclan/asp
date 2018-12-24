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
use PDO;
use System\Database\DbConnection;
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
     * @var int Excessive Team Kills on a Player Flag
     */
    const FLAG_PLAYER_TEAMKILLS = 7;

    /**
     * @var array
     */
    protected $notifications;

    /**
     * @var DbConnection The database connection
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
     * @var int Gets or Sets the maximum team kills threshold
     */
    protected $maxTeamKills = 0;

    /**
     * @var int Gets or Sets the maximum earned awards threshold (excludes backend awards)
     */
    protected $maxAwards = 0;

    /**
     * BattleSpy constructor.
     *
     * @param DbConnection $connection
     * @param int $serverId
     * @param int $roundId
     */
    public function __construct(DbConnection $connection, $serverId, $roundId)
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
        $this->maxTeamKills = (int)Config::Get('battlespy_max_team_kills');
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
        if ($this->maxSPM > 0)
        {
            $spm = ($mins == 0) ? 0 : round($player->roundScore / $mins, 3);
            if ($spm > $this->maxSPM)
            {
                // Determine severity
                $plus50p = $this->maxSPM * 1.5;
                $double = $this->maxSPM * 2;

                // Report player for having too high of a SPM
                $severity = ($spm > $double) ? 3 : (($spm > $plus50p) ? 2 : 1);
                $message = sprintf("Player Score per Min (%.3f) exceeds threshold of (%.3f)", $spm, $this->maxSPM);
                $this->report($player->id, $message, self::FLAG_PLAYER_SPM, $severity);
            }
        }

        /** Check kills per minute */
        if ($this->maxKPM > 0)
        {
            $kpm = ($mins == 0) ? 0 : round($player->kills / $mins, 3);
            if ($kpm > $this->maxKPM)
            {
                // Determine severity
                $plus50p = $this->maxKPM * 1.5;
                $double = $this->maxKPM * 2;

                // Report player for having too high of a KPM
                $severity = ($spm > $double) ? 3 : (($spm > $plus50p) ? 2 : 1);
                $message = sprintf("Player Kills per Min (%.3f) exceeds threshold of (%.3f)", $kpm, $this->maxKPM);
                $this->report($player->id, $message, self::FLAG_PLAYER_KILLS, $severity);
            }
        }

        /** Check target kills */
        if ($this->maxTargetKills > 0)
        {
            // Set severity limits
            $plus50p = $this->maxTargetKills * 1.5;
            $double = $this->maxTargetKills * 2;

            foreach ($player->victims as $pid => $count)
            {
                if ($count > $this->maxTargetKills)
                {
                    // Report player for having too many kills on a single player
                    $severity = ($count > $double) ? 3 : (($count > $plus50p) ? 2 : 1);
                    $message = sprintf("Player to Player Kills (%d) on victim Player (%d) exceeds threshold of (%d)", $count, $pid, $this->maxTargetKills);
                    $this->report($player->id, $message, self::FLAG_PLAYER_TARGET_KILLS, $severity);
                }
            }
        }

        /** Check awards */
        if ($this->maxAwards > 0)
        {
            $awardCount = count($player->earnedAwards);
            if ($awardCount > $this->maxAwards)
            {
                $message = "Player Award Count (%d) exceeds threshold of (%d)";
                $this->report($player->id, sprintf($message, $awardCount, $this->maxAwards), self::FLAG_PLAYER_AWARDS, 1);
            }
        }

        /** Check teamkills */
        if ($this->maxTeamKills > 0)
        {
            if ($player->teamKills > $this->maxTeamKills)
            {
                // Determine severity
                $plus50p = $this->maxTeamKills * 1.5;
                $double = $this->maxTeamKills * 2;

                // Report player for having too many team kills
                $severity = ($player->teamKills > $double) ? 3 : (($player->teamKills > $plus50p) ? 2 : 1);
                $message = sprintf("Player Team Kills (%d) exceeds threshold of (%d)", $player->teamKills, $this->maxTeamKills);
                $this->report($player->id, $message, self::FLAG_PLAYER_TEAMKILLS, $severity);
            }
        }

        /** Check weapon accuracy */
    }

    /**
     * Adds a report message to the list of reports
     *
     * @param int $playerId The offending player ID
     * @param string $message The message that describes the player offense
     * @param int $flagCode The constant flag code of the offense
     * @param int $severity The severity level of the offense, in a range of 1 to 3,
     *  1 being low, and 3 being high.
     *
     * @return void
     */
    public function report($playerId, $message, $flagCode, $severity)
    {
        $this->notifications[] = [
            'player_id' => $playerId,
            'flag' => $flagCode,
            'severity' => min(abs($severity), 3),
            'message' => $message
        ];
    }

    /**
     * Finalizes and saves the reported messages for
     * an admin to see. If this method is not called,
     * this report will be disposed.
     */
    public function finalize()
    {
        // Only create a database report if we have any messages
        if (!empty($this->notifications))
        {
            // Create report record for the database
            $query = new UpdateOrInsertQuery($this->pdo, 'battlespy_report');
            $query->set('server_id', '=', $this->serverId);
            $query->set('round_id', '=', $this->roundId);
            $query->executeInsert();

            // Grab report ID
            $reportId = $this->pdo->lastInsertId("id");

            // Insert report messages
            $query = new UpdateOrInsertQuery($this->pdo, 'battlespy_message');
            $query->set('report_id', '=', $reportId);

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