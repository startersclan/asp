<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2019, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

class RankCalculator
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * @var \System\Collections\Dictionary
     */
    protected $ranks;

    /**
     * RankCalculator constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');

        // Load ranks
        $this->ranks = include \System\IO\Path::Combine(SYSTEM_PATH, 'config', 'ranks.php');
    }

    /**
     * Generates the next $count ranks that can currently be obtained by the player
     *
     * @param int $playerId
     * @param int $count
     *
     * @return array|bool
     * @throws Exception
     */
    public function getNextRanks($playerId, $count)
    {
        // sanitize
        $playerId = (int)$playerId;

        // Grab player
        // Fetch player
        $query = "SELECT `time`, `score`, `rank_id` FROM player WHERE `id`={$playerId}";
        $player = $this->pdo->query($query)->fetch();
        if (empty($player))
            return false;

        // define variables we will use
        $rank_id = (int)$player['rank_id'];
        $nextRanks = [];
        $missingAwards = [];
        $desc = '';

        // Make sure we don't get an index out of range exception
        $availablePromotionsLeft = count($this->ranks) - $rank_id;
        if ($availablePromotionsLeft < $count)
            $count = $availablePromotionsLeft;

        // Loop through each promotion
        for ($i = 0; $i < $count; $i++)
        {
            // Update rank id
            if (count($nextRanks) > 0)
            {
                end($nextRanks);
                $rank_id = key($nextRanks);
            }

            // Generate a list of ranks we can jump to based on our current rank
            $nextPromoRanks = $this->getNextRankUps($rank_id);
            if (empty($nextPromoRanks))
                break;

            // Defines if we added a rank for the next promotion
            $addedARank = false;
            $zeroRankId = key($nextPromoRanks);
            $rankCount = count($nextPromoRanks);

            // We need to reverse the next rank array, so highest possible ranks are first, and
            // then we work our way down until we are just +1 rank
            $nextPromoRanks = array_reverse($nextPromoRanks, true);

            // First we loop through the required awards (if any), and see if we
            // have the required awards and level to meet the promotion requirement
            foreach ($nextPromoRanks as $rankId => $rank)
            {
                $rankCount--;
                $meetsAwardReqs = true;
                $missingAwards = [];
                if (!empty($rank['has_awards']))
                {
                    foreach ($rank['has_awards'] as $key => $value)
                    {
                        $query = "SELECT COUNT(*) FROM `player_award` WHERE `player_id`={$playerId} AND `award_id`={$key} AND `level` >= {$value}";
                        if ($this->pdo->query($query)->fetchColumn(0) == 0)
                        {
                            $meetsAwardReqs = false;
                            $missingAwards[$key] = $value;
                        }
                    }
                }

                // If we meet the requirement for this rank, add it
                if ($meetsAwardReqs)
                {
                    // Set missing awards description
                    if (strlen($desc) > 0)
                    {
                        $rank['missing_desc'] = $desc;
                        $desc = '';
                    }
                    else
                    {
                        $rank['missing_desc'] = '';
                    }

                    $addedARank = true;
                    $nextRanks[$rankId] = $rank;
                    break;
                }
                else
                {
                    // If we have multiple ranks for next promotion, and we haven't cycled through all of them
                    $moreToGo = ($rankCount > 0 && $rankId != $zeroRankId);
                    $desc = $this->generateMissingDesc($rank['title'], $missingAwards, $moreToGo);
                }
            }

            // Make sure we add at least the next rank, even if we don't qualify
            if (!$addedARank)
            {
                $rank = $nextPromoRanks[$zeroRankId];

                // Set missing awards description
                if (strlen($desc) > 0)
                {
                    $rank['missing_desc'] = $desc;
                    $desc = '';
                }

                if (!empty($missingAwards))
                {
                    $rank['missing_awards'] = $missingAwards;
                }

                $nextRanks[$zeroRankId] = $rank;
            }
        }

        return $nextRanks;
    }

    /**
     * Gets an array of ranks that we can promote directly into from the specified rank
     *
     * @param int $rank_id
     *
     * @return array
     */
    protected function getNextRankUps($rank_id)
    {
        $ranks = [];
        $count = count($this->ranks);

        for ($i = $rank_id + 1; $i < $count; $i++)
        {
            // Grab rank
            $rank = $this->ranks[$i];

            // skip?
            if ($rank['skip'])
                continue;

            // Does this next rank support multiple rank requirements?
            if (is_array($rank['has_rank']))
            {
                if (in_array($rank_id, $rank['has_rank']))
                    $ranks[$i] = $rank;
            }
            else if ($rank_id == $rank['has_rank'])
            {
                $ranks[$i] = $rank;
            }
        }

        return $ranks;
    }

    /**
     * Generates the Missing Awards description message, based on what awards are missing
     *
     * @param array $rank
     * @param bool $prevRank
     *
     * @return string
     * @throws Exception
     */
    protected function generateMissingDesc($rankName, Array $missingAwards, $prevRank)
    {
        // Message
        $message = ($prevRank)
            ? "You are not yet eligible for the advanced rank of <strong>{$rankName}</strong> because you are missing the awards: "
            : "You are missing the awards: ";

        // Add missing award titles
        $names = [];
        foreach ($missingAwards as $awardId => $level)
        {
            // Fetch award name
            $query = "SELECT `name`, `type` FROM `award` WHERE id={$awardId}";
            $row = $this->pdo->query($query)->fetch();
            if (empty($row))
                throw new Exception("Invalid award id passed => {$awardId}");

            // If award is a badge, add the level
            if ($row['type'] == 1)
            {
                $prefix = \System\Battlefield2::GetBadgePrefix($level);
                $names[] = $prefix . ' ' . $row['name'];
            }
            else
            {
                // Store award name
                $names[] = $row['name'];
            }
        }

        // Combine award names, and add them to description
        $string = implode(', ', $names);
        return $message . $string . '.';
    }
}