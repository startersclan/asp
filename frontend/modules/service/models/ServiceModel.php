<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2019, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

use System\Config;
use System\Collections\Dictionary;

/**
 * Service Model
 *
 * @package Models
 * @subpackage Service
 */
class ServiceModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    public $pdo;

    /**
     * @var int Time stamp of one week ago
     */
    private static $OneWeekAgo = 0;

    /**
     * @var int Time stamp of two weeks ago
     */
    private static $TwoWeekAgo = 0;

    /**
     * ServiceModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');

        // Set static vars
        if (self::$OneWeekAgo == 0)
        {
            self::$OneWeekAgo = time() - (86400 * 7);
            self::$TwoWeekAgo = time() - (86400 * 14);
        }
    }

    /**
     * Fetches the number of records in the rising star table
     *
     * @return int
     */
    public function getRisingStarCount()
    {
        return (int)$this->pdo->query("SELECT COUNT(player_id) FROM risingstar")->fetchColumn(0);
    }

    /**
     * Fetches the number of Sergeant Majors that are eligible for promotion to SMOC
     *
     * @return int
     */
    public function getNumOfEligibleSergeantMajors()
    {
        return (int)$this->pdo->query("SELECT COUNT(id) FROM player WHERE rank_id=10")->fetchColumn(0);
    }

    /**
     * Fetches the number of Lieutenant Generals that are eligible for promotion to General
     *
     * @return int
     */
    public function getNumOfEligibleGenerals()
    {
        return (int)$this->pdo->query("SELECT COUNT(id) FROM player WHERE rank_id=20")->fetchColumn(0);
    }

    /**
     * Fetches the number of rounds played
     *
     * @return int
     */
    public function getNumRoundsPlayed()
    {
        return (int)$this->pdo->query("SELECT COUNT(id) FROM `round`")->fetchColumn(0);
    }

    /**
     * Clears out the current Rising Star table, and rebuilds it from scratch.
     *
     * Rising star is calculated by comparing a player's career score per minute,
     * and comparing that against all the score per minute the player has achieved
     * over the last 7 days.
     *
     * This process is performed for each player who has scored at least 10 points in
     * their career, have played for at least 2 weeks, and have played a game in the
     * last 7 days.
     *
     * @return void
     */
    public function buildRisingStarTable()
    {
        // Create a list of players
        $players = new Dictionary();

        // Delete contents of the last rising star
        $this->pdo->exec("DELETE FROM `risingstar`");
        $this->pdo->exec("ALTER TABLE `risingstar` AUTO_INCREMENT = 1");

        // Prepare statement
        $one = self::$OneWeekAgo;
        $query = <<<SQL
SELECT SUM(prh.score) AS `score`, SUM(prh.time) AS `time`
FROM player_round_history AS prh 
  JOIN round AS r on prh.round_id = r.id 
WHERE player_id=:id AND r.time_imported > $one
SQL;
        $stmt = $this->pdo->prepare($query);

        // Fetch each player
        $query = "SELECT id, name, country, score, time FROM player WHERE score > 10 AND joined < ". self::$TwoWeekAgo;
        $reader = $this->pdo->query($query);

        // Get each player's last week SPM
        while ($player = $reader->fetch())
        {
            // Convert values
            $score = (int)$player['score'];
            $time = (int)$player['time'];
            $minutes = $time / 60;
            $playerId = (int)$player['id'];

            // Skip if no spm
            if ($score == 0 || $minutes == 0) continue;

            // Get total SPM
            $totalSpm = round($score / $minutes, 7);

            // Get last week's SPM
            $stmt->bindParam(':id', $playerId, PDO::PARAM_INT);
            $stmt->execute();
            $row = $stmt->fetch();

            // Convert values
            $score = (int)$row['score'];
            $time = (int)$row['time'];
            $minutes = $time / 60;

            // Skip if no spm
            if ($score == 0 || $minutes == 0) continue;

            // Calculate weekly score per min
            $weekSpm = round($score / $minutes, 7);

            // Calculate the score
            $percent = min(999, round($weekSpm / $totalSpm, 7) * 100);
            $score = $percent * 10000;

            // Add to dictionary
            $players->add($playerId, $score);
        }

        // Sort the dictionary
        $players = $players->toArray();
        arsort($players);

        try
        {
            // Wrap in a transaction!
            $this->pdo->beginTransaction();

            // Create statement
            $query = "INSERT INTO risingstar(player_id, weeklyscore) VALUES (:id, :score)";
            $stmt = $this->pdo->prepare($query);

            // Insert values into the risingstar table
            foreach ($players as $id => $score)
            {
                $stmt->bindParam(':id', $id, PDO::PARAM_INT);
                $stmt->bindParam(':score', $score, PDO::PARAM_INT);
                $stmt->execute();
            }

            // commit
            $this->pdo->commit();
        }
        catch (Exception $e)
        {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    public function selectSMOC($playerId)
    {

    }

    public function selectGeneral($playerId, $unique = true)
    {

    }
}