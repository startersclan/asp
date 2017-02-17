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

use Exception;
use System\Collections\Dictionary;
use System\Database\UpdateOrInsertQuery;
use System\IO\Path;

class Snapshot extends GameResult
{
    /**
     * @var LogWriter
     */
    protected $logWriter;

    /**
     * @var bool
     */
    protected $isProcessed = false;

    /**
     * @var string
     */
    public $dataString;

    /**
     * Returns the server IP that posted this snapshot. If no Server
     * IP is provided (Ex: loading snapshot from a file), then the local
     * loopback address will be returned instead
     *
     * @var string
     */
    public $serverIp = '127.0.0.1';


    /**
     * Snapshot constructor.
     *
     * @param string $snapshotData
     * @param string $serverIp
     *
     * @throws Exception
     */
    public function __construct($snapshotData, $serverIp = '127.0.0.1')
    {
        $this->dataString = $snapshotData;
        $this->serverIp = $serverIp;

        $data = explode('\\', $snapshotData);
        $length = count($data);

        // Check for invalid snapshot string. All snapshots have at least 36 data pairs,
        // and has an Even number of data sectors.
        if ($length < 36 || $length % 2 != 0)
            throw new Exception("Snapshot does not contain at least 36 elements, or contains an odd number of elements");

        // local vars
        $standardData = new Dictionary();

        try
        {
            // Define some variables we will use here
            $playerData = new Dictionary();
            $killData = [];
            $newPlayer = true;

            // Convert our standard data into key => value pairs
            for ($i = 2; $i < $length; $i += 2)
            {
                // Format: "DataKey_PlayerIndex". PlayerIndex is NOT the Player Id
                $parts = explode('_', $data[$i]);

                // If no underscore is detected in the key, this is NOT player data
                if (count($parts) == 1)
                {
                    $standardData[$data[$i]] = $data[$i + 1];

                    if ($parts[0] == 'EOF')
                    {
                        if (!$newPlayer)
                        {
                            // Save that last players stats!!
                            $this->addPlayer(new Player($playerData, $killData));

                            // Reset data for next player
                            $playerData->clear();
                            /** @noinspection PhpUnusedLocalVariableInspection */
                            $killData = array();
                        }
                        break;
                    }
                }
                // If the item key is "pID", then we have a new player record
                else if ($parts[0] == "pID")
                {
                    // If we have data, complete this player and start a new
                    if (!$newPlayer)
                    {
                        // Save that last players stats!!
                        $this->addPlayer(new Player($playerData, $killData));

                        // Reset data for next player
                        $playerData->clear();
                        $killData = array();
                    }

                    $newPlayer = false;
                    $playerData[$parts[0]] = $data[$i + 1];
                }
                else if ($parts[0] == "mvks") // Skip mvks... kill data only needs processed once (mvns)
                    continue;
                else if ($parts[0] == "mvns") // Player kill data
                    $killData[$data[$i + 1]] = $data[$i + 3];
                else
                    $playerData[$parts[0]] = $data[$i + 1];
            }
        }
        catch (Exception $e)
        {
            throw new Exception("Error assigning Key => value pairs: ". $e->getMessage(), 1, $e);
        }

        // Make sure we have a completed snapshot
        if (!isset($standardData["EOF"]))
            throw new Exception("No End of File element was found, Snapshot assumed to be incomplete.");

        // Server data
        $this->serverPrefix = $data[0];
        $this->serverName = $data[1];
        $this->serverPort = (int)$standardData["gameport"];
        $this->queryPort = (int)$standardData["queryport"];

        // Map Data
        $this->mapName = preg_replace("/[^A-Za-z0-9_]/", '', $standardData["mapname"]);
        $this->mapId = (int)$standardData["mapid"];
        $this->roundStartTime = (int)$standardData["mapstart"];
        $this->roundEndTime = (int)$standardData["mapend"];

        // Misc Data
        $this->gameMode = (int)$standardData["gm"];
        $this->mod = $standardData["v"]; // bf2 mod replaced the version key, since we dont care the version anyways
        $this->playersConnected = (int)$standardData["pc"];

        // Army Data... There is no RWA key if there was no winner...
        $this->winningTeam = (int)$standardData["win"]; // Temp
        $this->winningArmyId = ($standardData->containsKey("rwa")) ? (int)$standardData["rwa"] : -1;
        $this->team1ArmyId = (int)$standardData["ra1"];
        $this->team1Tickets = (int)$standardData["rs1"];
        $this->team2ArmyId = (int)$standardData["ra2"];
        $this->team2Tickets = (int)$standardData["rs2"];

        // Grab database connection
        $connection = Database::GetConnection("stats");

        // Check for unknown map id
        if ($this->mapId == 99)
        {
            $stmt = $connection->prepare("SELECT id FROM mapinfo WHERE name = :name");
            $stmt->bindValue(':name', $this->mapName, \PDO::PARAM_STR);
            if ($stmt->execute() && ($id = $stmt->fetchColumn()) !== false)
            {
                $this->mapId = (int)$id;
            }
            else
                throw new Exception("Invalid map received (Unknown Map ID): ". $this->mapName);
        }
        else
        {
            $result = $connection->query("SELECT COUNT(id) FROM mapinfo WHERE id = ". $this->mapId);
            if (($id = (int)$result->fetchColumn()) == 0)
            {
                $connection->insert('mapinfo', ['id' => $this->mapId, 'name' => $this->mapName]);
            }
        }

        // Check for processed snapshot
        $query = "SELECT COUNT(id) FROM round_history WHERE mapid=%d AND round_end=%d AND round_start=%d";
        $result = $connection->query(sprintf($query, $this->mapId, $this->roundEndTime, $this->roundStartTime));
        $this->isProcessed = ((int)$result->fetchColumn()) > 0;
    }

    /**
     * Returns whether this snapshot has been processed into the database
     *
     * @return bool
     */
    public function isProcessed()
    {
        return $this->isProcessed;
    }

    /**
     * Processes the snapshot data, inserted and updating player data in the gamespy database
     *
     * @throws Exception
     */
    public function processData()
    {
        // Make sure we are not processing the same data again
        if ($this->isProcessed)
            throw new Exception("Round data has already been processed!");

        // Grab database connection and lets go!
        $connection = Database::GetConnection("stats");

        // Get a log file
        if ($this->logWriter == null)
        {
            $this->logWriter = LogWriter::Instance("stats_debug");
            if ($this->logWriter === false)
            {
                $this->logWriter = new LogWriter(Path::Combine(SYSTEM_PATH, "logs", "stats_debug.log"), "stats_debug");
                $this->logWriter->setLogLevel(Config::Get('debug_lvl'));
            }
        }

        // Ensure this is an authorized server
        $ip = $connection->quote($this->serverIp);
        $result = $connection->query("SELECT id, authorized FROM server WHERE `ip`={$ip} AND `port`={$this->serverPort} LIMIT 1");
        if (!($row = $result->fetch()) || (int)$row['authorized'] == 0)
        {
            $this->logWriter->logSecurity("Unauthorised Game Server '{$this->serverIp}:{$this->serverPort}' attempted to send snapshot data!");
            throw new Exception("Unauthorised Game Server!");
        }
        else
        {
            $serverId = (int)$row['id'];
        }

        // Start logging information about this snapshot
        $this->logWriter->logNotice("Begin Processing (%s) From Server ID (%d)...", [$this->mapName, $serverId]);
        if ($this->isCustomMap)
            $this->logWriter->logNotice("Custom Map (%d)...", $this->mapId);
        else
            $this->logWriter->logNotice("Standard Map (%d)...", $this->mapId);

        // Log player count
        $playerCount = count($this->players);
        $this->logWriter->logNotice("Found (". $playerCount .") Player(s)...");

        // Ensure the player count is within range of the config
        if ($playerCount < (int)Config::Get("stats_players_min"))
        {
            $this->logWriter->logWarning("Minimum round Player count does not meet the ASP requirement... Aborting");
            throw new Exception("Minimum round Player count does not meet the ASP requirement");
        }

        // Import backend awards
        BackendAwardData::Load();

        // To prevent half complete snapshots due to exceptions,
        // Put the whole thing in a try block, and rollback on error
        try
        {
            // Wrap in a transaction to speed things up
            $connection->beginTransaction();
            $start = microtime(true);

            // ********************************
            // Process RoundInfo
            // ********************************
            $query = new UpdateOrInsertQuery($connection, 'round_history');
            $query->set('mapid', '=', $this->mapId);
            $query->set('serverid', '=', $serverId);
            $query->set('round_start', '=', $this->roundStartTime);
            $query->set('round_end', '=', $this->roundEndTime);
            $query->set('imported', '=', time());
            $query->set('gamemode', '=', $this->gameMode);
            $query->set('mod', '=', $this->mod);
            $query->set('winner', '=', $this->winningTeam);
            $query->set('team1', '=', $this->team1ArmyId);
            $query->set('team2', '=', $this->team2ArmyId);
            $query->set('tickets1', '=', $this->team1Tickets);
            $query->set('tickets2', '=', $this->team2Tickets);
            $query->set('pids1', '=', $this->team1Players);
            $query->set('pids1_end', '=', $this->team1PlayersEnd);
            $query->set('pids2', '=', $this->team2Players);
            $query->set('pids2_end', '=', $this->team2PlayersEnd);
            $query->executeInsert();

            // Grab round ID
            $roundId = $connection->lastInsertId("id");

            // ********************************
            // Process Players
            // ********************************

            // Loop through each player, and process them
            foreach ($this->players as $player)
            {
                // Player meets min round time or are we ignoring AI?
                if ($player->roundTime < Config::Get('stats_min_player_game_time') || $player->isAi && Config::Get('stats_ignore_ai'))
                    continue;

                // Write log
                $this->logWriter->logNotice("Processing Player (". $player->pid .")");

                // Define some variables
                $onWinningTeam = $player->team == $this->winningTeam;

                // Prepare for player update / insertion
                $query = new UpdateOrInsertQuery($connection, 'player');
                $query->set('time', '+', $player->roundTime);
                $query->set('rounds', '+', (int)$player->completedRound);
                $query->set('lastip', '=', $player->ipAddress);
                $query->set('score', '+', $player->roundScore);
                $query->set('cmdscore', '+', $player->commandScore);
                $query->set('skillscore', '+', $player->skillScore);
                $query->set('teamscore', '+', $player->teamScore);
                $query->set('kills', '+', $player->kills);
                $query->set('deaths', '+', $player->deaths);
                $query->set('captures', '+', $player->flagCaptures);
                $query->set('captureassists', '+', $player->flagCaptureAssists);
                $query->set('defends', '+', $player->flagDefends);
                $query->set('damageassists', '+', $player->damageAssists);
                $query->set('heals', '+', $player->heals);
                $query->set('revives', '+', $player->revives);
                $query->set('ammos', '+', $player->ammos);
                $query->set('repairs', '+', $player->repairs);
                $query->set('targetassists', '+', $player->targetAssists);
                $query->set('driverspecials', '+', $player->driverSpecials);
                $query->set('teamkills', '+', $player->teamKills);
                $query->set('teamdamage', '+', $player->teamDamage);
                $query->set('teamvehicledamage', '+', $player->teamVehicleDamage);
                $query->set('suicides', '+', $player->suicides);
                $query->set('rank', '=', $player->rank);
                $query->set('banned', '+', $player->timesBanned);
                $query->set('kicked', '+', $player->timesKicked);
                $query->set('cmdtime', '+', $player->cmdTime);
                $query->set('sqltime', '+', $player->sqlTime);
                $query->set('sqmtime', '+', $player->sqmTime);
                $query->set('lwtime', '+', $player->lwTime);
                $query->set('timepara', '+', $player->timeParachute);
                $query->set('wins', '+', $onWinningTeam ? 1 : 0);
                $query->set('losses', '+', (!$onWinningTeam) ? 1 : 0);
                $query->set('rndscore', '+', $player->roundScore);
                $query->set('lastonline', '=', $this->roundEndTime);
                $query->set('mode0', '+', ($this->gameMode == 0) ? 1 : 0);
                $query->set('mode1', '+', ($this->gameMode == 1) ? 1 : 0);
                $query->set('mode2', '+', ($this->gameMode == 2) ? 1 : 0);

                // Check if player exists already
                $sql = "SELECT lastip, country, rank, killstreak, deathstreak, rndscore FROM player WHERE id=%d LIMIT 1";
                $result = $connection->query(sprintf($sql, $player->pid));

                if ($row = $result->fetch())
                {
                    // Write log
                    $this->logWriter->logNotice("Updating EXISTING Player (". $player->pid .")");

                    // Correct rank if needed
                    $rank = (int)$row['rank'];
                    if ($rank > $player->rank && $rank != 11 && $rank != 21)
                    {
                        $player->rank = $rank;
                        $this->logWriter->logNotice("Rank correction ({$player->pid}), Using database rank ({$rank})");
                    }

                    // Calculate best killstreak/deathstreak
                    if ($player->killStreak > (int)$row['killstreak'])
                        $query->set('killstreak', '=', $player->killStreak);

                    if ($player->deathStreak > (int)$row['deathstreak'])
                        $query->set('deathstreak', '=', $player->deathStreak);

                    if ($player->roundScore > (int)$row['rndscore'])
                        $query->set('rndscore', '=', $player->roundScore);

                    // Execute the update
                    $query->where('id', '=', $player->pid);
                    $query->executeUpdate();
                }
                else if ($player->isAi)
                {
                    // Write log
                    $this->logWriter->logNotice("Adding NEW AI Player (" . $player->pid . ")");

                    //$countryCode = ''; // TODO finish meh
                    $query->set('id', '=', $player->pid);
                    $query->set('name', '=', $player->name);
                    $query->set('password', '=', '');
                    $query->set('country', '=', 'US');
                    $query->set('joined', '=', $this->roundEndTime);
                    $query->set('killstreak', '=', $player->killStreak);
                    $query->set('deathstreak', '=', $player->deathStreak);
                    $query->set('rndscore', '=', $player->roundScore);
                    $query->executeInsert();
                }
                else
                {
                    continue;
                }

                // ********************************
                // Insert Player history.
                // ********************************
                $this->logWriter->logDebug("Processing Player History Data (%d)", $player->pid);
                $query = new UpdateOrInsertQuery($connection, 'player_history');
                $query->set('pid', '=', $player->pid);
                $query->set('roundid', '=', $roundId);
                $query->set('team', '=', $player->team);
                $query->set('timestamp', '+', $this->roundEndTime);
                $query->set('time', '=', $player->roundTime);
                $query->set('score', '=', $player->roundScore);
                $query->set('cmdscore', '=', $player->commandScore);
                $query->set('skillscore', '=', $player->skillScore);
                $query->set('teamscore', '=', $player->teamScore);
                $query->set('kills', '=', $player->kills);
                $query->set('deaths', '=', $player->deaths);
                $query->set('rank', '=', $player->rank);
                $query->executeInsert();

                // ********************************
                // Process Player Army Data
                // ********************************
                $this->logWriter->logDebug("Processing Army Data (%d)", $player->pid);
                $i = 0;
                foreach ($player->timeAsArmy as $time)
                {
                    // Skip un-played armies
                    if ($time == 0)
                    {
                        $i++;
                        continue;
                    }

                    $query = new UpdateOrInsertQuery($connection, 'player_army');
                    $query->where('id', '=', $i);
                    $query->where('pid', '=', $player->pid);
                    $query->set('time', '+', $time);

                    // If the player ended the game as this army, update with round info
                    if ($player->armyId == $i)
                    {
                        $query->set('wins', '+', $onWinningTeam ? 1 : 0);
                        $query->set('losses', '+', $onWinningTeam ? 0 : 1);
                        $query->set('best', 'g', $player->roundScore);
                        $query->set('worst', 'l', $player->roundScore);
                    }

                    $query->execute();
                    $i++;
                }

                // ********************************
                // Process Player Kills
                // ********************************
                $this->logWriter->logDebug("Processing Kill Data (%d)", $player->pid);
                $query = new UpdateOrInsertQuery($connection, 'player_kill');
                $query->where('attacker', '=', $player->pid);
                foreach ($player->victims as $pid => $count)
                {
                    $query->where('victim', '=', $pid);
                    $query->set('count', '+', $count);
                    $query->execute();
                }

                // ********************************
                // Process Player Kit Data
                // ********************************
                $this->logWriter->logDebug("Processing Kit Data (%d)", $player->pid);
                $query = new UpdateOrInsertQuery($connection, 'player_kit');
                $query->where('pid', '=', $player->pid);
                foreach ($player->kitData as $id => $object)
                {
                    // Ignore objects that the player did not use in round
                    if ($object->time == 0) continue;

                    $query->set('time', '+', $object->time);
                    $query->set('kills', '+', $object->kills);
                    $query->set('deaths', '+', $object->deaths);
                    $query->where('id', '=', $id);
                    $query->execute();
                }

                // ********************************
                // Process Player Map Data
                // ********************************
                $this->logWriter->logDebug("Processing Map Data (%d)", $player->pid);
                $query = new UpdateOrInsertQuery($connection, 'player_map');
                $query->set('time', '+', $player->roundTime);
                $query->set('wins', '+', $onWinningTeam ? 1 : 0);
                $query->set('losses', '+', $onWinningTeam ? 0 : 1);
                $query->set('bestscore', 'g', $player->roundScore);
                $query->set('worstscore', 'l', $player->roundScore);
                $query->where('pid', '=', $player->pid);
                $query->where('mapid', '=', $this->mapId);
                $query->execute();

                // ********************************
                // Process Player Vehicle Data
                // ********************************
                $this->logWriter->logDebug("Processing Vehicle Data (%d)", $player->pid);
                $query = new UpdateOrInsertQuery($connection, 'player_vehicle');
                $query->where('pid', '=', $player->pid);
                foreach ($player->vehicleData as $id => $object)
                {
                    // Ignore objects that the player did not use in round
                    if ($object->time == 0) continue;

                    $query->set('time', '+', $object->time);
                    $query->set('kills', '+', $object->kills);
                    $query->set('deaths', '+', $object->deaths);
                    $query->set('roadkills', '+', $object->roadKills);
                    $query->where('id', '=', $id);
                    $query->execute();
                }

                // ********************************
                // Process Player Weapon Data
                // ********************************
                $this->logWriter->logDebug("Processing Weapon Data (%d)", $player->pid);
                $query = new UpdateOrInsertQuery($connection, 'player_weapon');
                $query->where('pid', '=', $player->pid);
                foreach ($player->weaponData as $id => $object)
                {
                    // Ignore objects that the player did not use in round
                    if ($object->time == 0) continue;

                    $query->set('time', '+', $object->time);
                    $query->set('kills', '+', $object->kills);
                    $query->set('deaths', '+', $object->deaths);
                    $query->set('fired', '+', $object->fired);
                    $query->set('hits', '+', $object->hits);
                    $query->where('id', '=', $id);
                    $query->execute();
                }

                // ********************************
                // Process Player Awards Data
                // ********************************
                $this->logWriter->logDebug("Processing Award Data (%d)", $player->pid);
                if ($player->completedRound || !Config::Get('stats_awds_complete'))
                {
                    // Add Backend awards to player
                    foreach (BackendAwardData::$BackendAwards as $award)
                    {
                        if ($award->criteriaMet($player, $connection, $level))
                            $player->earnedAwards[$award->awardId] = $level;
                    }

                    // Log
                    $this->logWriter->logDebug("Player (%d) Earned %d Awards...", [$player->pid, count($player->earnedAwards)]);

                    foreach ($player->earnedAwards as $key => $value)
                    {
                        // Get our award type. Award.Key is the ID, Award.Value is the level (or count)
                        $isMedal = ($key > 2000000 && $key < 3000000);
                        $isBadge = ($key < 2000000);

                        // Check is player has this award already
                        $query = "SELECT COUNT(*) FROM player_award WHERE pid=%d AND id=%d";
                        if ($isBadge)
                            $query .= " AND level=". (int)$value;

                        // Check for prior awarding of award
                        $result = $connection->query( sprintf($query, $player->pid, $key) . ' LIMIT 1');
                        if (($count = (int)$result->fetchColumn(0)) == 0)
                        {
                            // Need to do extra work for Badges as more than one badge level may have been awarded.
                            // The snapshot will only post the highest awarded level of a badge, so here we award
                            // the lower level badges if the player does not have them.
                            if ($isBadge)
                            {
                                // Check all prior badge levels, and make sure the player has them
                                for ($j = 1; $j < $value; $j++)
                                {
                                    $query = "SELECT COUNT(*) FROM player_award WHERE pid=%d AND id=%d AND level=%d LIMIT 1";
                                    $result = $connection->query( sprintf($query, $player->pid, $key, $j) );
                                    if (($count = (int)$result->fetchColumn(0)) == 0)
                                    {
                                        // Prepare Query
                                        $query = new UpdateOrInsertQuery($connection, 'player_award');
                                        $query->set('pid', '=', $player->pid);
                                        $query->set('id', '=', $key);
                                        $query->set('roundid', '=', $roundId);
                                        $query->set('level', '=', $j);
                                        $query->set('earned', '=', $this->roundEndTime + 5 + $j);
                                        $query->set('first', '=', 0);
                                        $query->executeInsert();
                                    }
                                }
                            }

                            $query = new UpdateOrInsertQuery($connection, 'player_award');
                            $query->set('pid', '=', $player->pid);
                            $query->set('id', '=', $key);
                            $query->set('roundid', '=', $roundId);
                            $query->set('level', '=', $value);
                            $query->set('earned', '=', $this->roundEndTime);
                            $query->set('first', '=', ($isMedal) ? $this->roundEndTime : 0);
                            $query->executeInsert();
                        }
                        else if ($isMedal) // === Player has received this award prior === //
                        {
                            $query = new UpdateOrInsertQuery($connection, 'player_award');
                            $query->where('pid', '=', $player->pid);
                            $query->where('id', '=', $key);
                            $query->set('roundid', '=', $roundId);
                            $query->set('level', '+', 1);
                            $query->set('earned', '=', $this->roundEndTime);
                            $query->executeUpdate();
                        }

                        // Add best round count if player earned best round medal
                        if ($key == 2051907)
                        {
                            $query = new UpdateOrInsertQuery($connection, 'player_army');
                            $query->where('pid', '=', $player->pid);
                            $query->where('id', '=', $player->armyId);
                            $query->set('brnd', '+', 1);
                            $query->execute();
                        }
                    } // End Foreach Award
                } // End Award Processing
            } // End player loop

            // ********************************
            // Process ServerInfo
            // ********************************
            $query = new UpdateOrInsertQuery($connection, 'server');
            $query->set('name', '=', $this->serverName);
            $query->set('queryport', '=', $this->queryPort);
            $query->set('lastupdate', '=', time());
            $query->where('id', '=', $serverId);
            $query->executeUpdate();

            // ********************************
            // Process MapInfo
            // ********************************
            $query = new UpdateOrInsertQuery($connection, 'mapinfo');
            $query->set('time', '+', $this->roundTime);
            $query->set('score', '+', $this->mapScore);
            $query->set('times', '+', 1);
            $query->set('kills', '+', $this->mapKills);
            $query->set('deaths', '+', $this->mapDeaths);
            $query->where('id', '=', $this->mapId);
            $query->executeUpdate();

            // ********************************
            // Commit the Transaction and Log
            // ********************************
            $connection->commit();
            $this->isProcessed = true;

            // Create log entry
            $time = round(microtime(true) - $start, 3) * 1000;
            $this->logWriter->logNotice("Snapshot (%s) processed in %d milliseconds", [$this->getFilename(), $time]);
        }
        catch (Exception $e)
        {
            // Rollback the changes
            $connection->rollBack();

            // Write log
            $this->logWriter->logError("Failed to process SNAPSHOT! %s", $e->getMessage());
            throw $e;
        }
    }

    /**
     * Returns the snapshot filename for this snapshot
     *
     * @return string
     */
    public function getFilename()
    {
        // Generate SNAPSHOT Filename
        $mapdate = date('Ymd_Hi', time());
        $prefix  = '';
        if (!empty($this->serverPrefix))
            $prefix = $this->serverPrefix . '-';

        return $prefix . $this->mapName . '_' . $mapdate . '.txt';
    }

    /**
     * Adds a player to the list of round players, and adds their
     * stats to the map stats variables.
     *
     * @param Player $player
     */
    protected function addPlayer(Player $player)
    {
        $this->players[] = $player;

        // Add map data
        $this->mapScore += $player->roundScore;
        $this->mapKills += $player->kills;
        $this->mapDeaths += $player->deaths;

        // DO team counts
        if ($player->armyId == $this->team1ArmyId)
        {
            $this->team1Players++;
            if ($player->completedRound) // Completed round?
                $this->team1PlayersEnd++;
        }
        else
        {
            $this->team2Players++;
            if ($player->completedRound) // Completed round?
                $this->team2PlayersEnd++;
        }
    }
}