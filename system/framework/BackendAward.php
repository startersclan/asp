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

use System\Database\DbConnection;

class BackendAward
{
    /**
     * @var int
     */
    public $awardId = 0;

    /**
     * @var AwardCriteria[]
     */
    protected $awardCriteria = array();

    /**
     * BackendAward constructor.
     *
     * @param $awardId
     * @param $criteria
     */
    public function __construct($awardId, $criteria)
    {
        $this->awardId = (int)$awardId;
        $this->awardCriteria = $criteria;
    }

    /**
     * @param Player $player
     * @param DbConnection $connection
     * @param int $level
     *
     * @return bool
     */
    public function criteriaMet(Player $player, DbConnection $connection, &$level)
    {
        /**
         * Get the award count (or level for badges) for this award
         * @noinspection SqlResolve
         */
        $query = "SELECT COALESCE(max(level), 0) FROM player_award WHERE pid=%d AND id=%d";
        $result = $connection->query(sprintf($query, $player->pid, $this->awardId));
        $awardCount = (int) $result->fetchColumn();
        $isRibbon = ($this->awardId > 3000000);

        // Can only receive ribbons once in a lifetime, so return false if we have it already
        if ($isRibbon && $awardCount > 0)
            return false;

        // Set output variable
        $level = $awardCount;

        // Loop through each criteria and see if we have met the criteria
        foreach ($this->awardCriteria as $criteria)
        {
            // Build the query. We always use a count() or sum() to return a sortof bool.
            $where = str_replace('###', $level, $criteria->where);
            $query = vsprintf("SELECT %s FROM `%s` WHERE `pid`=%d AND %s LIMIT 1", [
                $criteria->field,
                $criteria->table,
                $player->pid,
                $where
            ]);

            $result = $connection->query($query);
            if ($result instanceof \PDOStatement)
            {
                $row = $result->fetch();
                if (empty($row) || !$criteria->checkCriteria($row, $awardCount))
                        return false;
            }
        }

        return true;
    }
}