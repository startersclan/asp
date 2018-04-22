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

use Exception;
use SecurityException;
use System\Collections\Dictionary;
use System\Database\UpdateOrInsertQuery;
use System\IO\Path;
use System\Net\IPAddress;

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
     * @var BattleSpy
     */
    protected $battleSpy;

    /**
     * Returns the server IP that posted this snapshot. If no Server
     * IP is provided (Ex: loading snapshot from a file), then the local
     * loopback address will be returned instead
     *
     * @var string
     */
    public $serverIp = '';

    /**
     * @var int The Server ID in the database if it exists
     */
    public $serverId = 0;

    /**
     * @var int The server's AuthID if there is one!
     */
    public $authId = 0;

    /**
     * @var string The server's AuthToken if there is one!
     */
    public $authToken = '';

    /**
     * Snapshot constructor.
     *
     * @param Dictionary $snapshotData snapshot as a JSON string
     *
     * @throws Exception
     */
    public function __construct(Dictionary $snapshotData)
    {
        // Store ip address as we will need this later
        $this->serverIp = $snapshotData['serverIp'];

        // Load award and stats data
        AwardData::Load();
        StatsData::Load();

        // Check snapshot version!
        if (Version::LessThan($snapshotData['version'], "3.0"))
            throw new Exception("Incompatible snapshot version: ". $snapshotData['version']);

        // Server data
        $this->authId = (int)$snapshotData["authId"];
        $this->authToken = $snapshotData["authToken"];
        $this->serverName = preg_replace("/[^". Player::NAME_REGEX ."]/", '', $snapshotData['serverName']);
        $this->serverPort = (int)$snapshotData["gamePort"];
        $this->queryPort = (int)$snapshotData["queryPort"];

        // Map Data
        $this->mapName = preg_replace("/[^A-Za-z0-9_]/", '', $snapshotData["mapName"]);
        $this->mapId = (int)$snapshotData["mapId"];
        $this->roundStartTime = (int)$snapshotData["mapStart"];
        $this->roundEndTime = (int)$snapshotData["mapEnd"];
        $this->roundTime = $this->roundEndTime - $this->roundStartTime;

        // Misc Data
        $this->gameMode = (int)$snapshotData["gameMode"];
        $this->mod = preg_replace("/[^A-Za-z0-9_\\.\\-]/", '', $snapshotData['mod']);
        $this->playersConnected = (int)$snapshotData["pc"];

        // Army Data... There is no RWA key if there was no winner...
        $this->winningTeam = (int)$snapshotData["winner"]; // Temp
        $this->winningArmyId = (int)$snapshotData->getValueOrDefault("rwa", -1);
        $this->team1ArmyId = (int)$snapshotData["ra1"];
        $this->team1Tickets = (int)$snapshotData["rs1"];
        $this->team2ArmyId = (int)$snapshotData["ra2"];
        $this->team2Tickets = (int)$snapshotData["rs2"];

        // Add players
        foreach ($snapshotData['players'] as $player)
        {
            $player = new Dictionary(false, $player);
            $this->addPlayer(new Player($player));
        }

        // Grab database connection
        $connection = Database::GetConnection("stats");

        // Check for unknown map id
        if ($this->mapId == 99)
        {
            $stmt = $connection->prepare("SELECT id FROM map WHERE name = :name");
            $stmt->bindValue(':name', $this->mapName, \PDO::PARAM_STR);
            if ($stmt->execute() && ($id = $stmt->fetchColumn(0)) !== false)
            {
                $this->mapId = (int)$id;
            }
            else
                throw new Exception("Invalid map received (Unknown Map ID): ". $this->mapName, 99);
        }
        else
        {
            $result = $connection->query("SELECT COUNT(id) FROM map WHERE id = ". $this->mapId);
            if (($id = (int)$result->fetchColumn(0)) == 0)
            {
                $connection->insert('map', ['id' => $this->mapId, 'name' => $this->mapName, 'displayname' => $this->mapName]);
            }
        }

        // Load server
        $result = $connection->query("SELECT `id` FROM `server` WHERE `auth_id`={$this->authId}");
        if ($row = $result->fetch())
        {
            $this->serverId = (int)$row['id'];
        }

        // Check for processed snapshot. Server ports can change so do not check by server ID
        $query = "SELECT COUNT(id) FROM round WHERE map_id=%d AND time_end=%d AND time_start=%d";
        $result = $connection->query(sprintf($query, $this->mapId, $this->roundEndTime, $this->roundStartTime));
        $this->isProcessed = ((int)$result->fetchColumn(0)) > 0;
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
     * @throws SecurityException if the server posting this snapshot is not authorized to post snapshots
     * @throws Exception if an error occurs while processing data
     */
    public function processData()
    {
        // Make sure we are not processing the same data again
        if ($this->isProcessed)
            throw new Exception("Round data has already been processed!");

        // Grab database connection and lets go!
        $connection = Database::GetConnection("stats");

        // Get a log file
        $this->logWriter = LogWriter::Instance("stats_debug");
        if ($this->logWriter === false)
        {
            $this->logWriter = new LogWriter(Path::Combine(SYSTEM_PATH, "logs", "stats_debug.log"), "stats_debug");
            $this->logWriter->setLogLevel(Config::Get('debug_lvl'));
        }

        // ---------------------------------------------------------------------
        // Start logging information about this snapshot
        $this->logWriter->logNotice("Begin Processing (%s) From Server ID (%d)...", [$this->mapName, $this->serverId]);

        // ---------------------------------------------------------------------
        // Ensure this is a valid AuthId. If AuthID is invalid, server ID will be 0
        if ($this->serverId == 0)
        {
            $this->logWriter->logSecurity("Invalid AuthID found in Snapshot data [{$this->authId}]");
            throw new SecurityException("Invalid AuthID found in Snapshot data", empty($row) ? 0 : 1);
        }
        else
        {
            $this->logWriter->logDebug("Valid AuthID found in Snapshot data [{$this->authId}]");
        }

        // ---------------------------------------------------------------------
        // Ensure this is an authorized server
        $result = $connection->query("SELECT `authorized` FROM `server` WHERE `id`={$this->serverId}");
        if (!($row = $result->fetch()) || (int)$row['authorized'] == 0)
        {
            $this->logWriter->logSecurity("Unauthorised Game Server '{$this->serverIp}:{$this->serverPort}' attempted to send snapshot data!");
            throw new SecurityException("Unauthorised Game Server!", empty($row) ? 2 : 3);
        }
        else
        {
            $this->logWriter->logDebug("Found authorised Game Server '{$this->serverIp}:{$this->serverPort}'");
        }

        // ---------------------------------------------------------------------
        // Ensure this is an valid authorization token, and the server IP is OK
        $result = $connection->query("SELECT `ip`, `auth_token` FROM `server` WHERE `id`={$this->serverId}");
        if ($row = $result->fetch())
        {
            // Match Authentication Token
            $token = $row['auth_token'];
            if ($token !== $this->authToken)
            {
                $this->logWriter->logSecurity("Invalid AuthToken passed! [AuthId: {$this->authId}, AuthToken: {$this->authToken}]");
                throw new SecurityException("Invalid AuthToken passed!", 4);
            }
            else
            {
                $this->logWriter->logDebug("Valid AuthToken passed! [AuthId: {$this->authId}, AuthToken: {$this->authToken}]");
            }

            // Validate the server IP against the AuthToken
            if ($this->serverIp !== $row['ip'])
            {
                // Match Server IP's
                $valid = false;
                $addy = IPAddress::Parse($this->serverIp);
                $result = $connection->query("SELECT `address` FROM `server_auth_ip` WHERE `id`={$this->serverId}");
                while ($row = $result->fetch())
                {
                    if ($addy->isInCidr($row['address']))
                        $valid = true;
                }

                if (!$valid)
                {
                    $message = "Invalid Server IpAddress received for AuthId! [AuthId: {$this->authId}, ServerAddress: {$this->serverIp}]";
                    $this->logWriter->logSecurity($message);
                    throw new SecurityException($message, 5);
                }
                else
                {
                    $this->logWriter->logDebug("Valid Server IpAddress received for AuthId! [ServerAddress: {$this->serverIp}]");
                }
            }
        }
        else
        {
            $this->logWriter->logSecurity("Unauthorised Game Server '{$this->serverIp}:{$this->serverPort}' attempted to send snapshot data!");
            throw new SecurityException("Unauthorised Game Server!", empty($row) ? 6 : 7);
        }

        // ---------------------------------------------------------------------
        // Fetch mod ID and make sure its authorized
        $modName = $connection->quote($this->mod);
        $result = $connection->query("SELECT `id`, `authorized` FROM `game_mod` WHERE `name`={$modName}");
        if (!($row = $result->fetch()) || (int)$row['authorized'] == 0)
        {
            $this->logWriter->logWarning("Unauthorised Game Mod '{$this->mod}' played in round.");
            throw new Exception("Unauthorised Game Mod!", empty($row) ? 0 : 1);
        }
        else
        {
            $modId = (int)$row['id'];
        }

        // ---------------------------------------------------------------------
        // Logging player and map information in this snapshot
        $playerCount = count($this->players);
        $message = ($this->isCustomMap) ? "Custom Map (%d)..." : "Standard Map (%d)...";
        $this->logWriter->logNotice($message, $this->mapId);
        $this->logWriter->logNotice("Found (%d) Player(s)...", $playerCount);

        // Ensure the player count is within range of the config
        if ($playerCount < (int)Config::Get("stats_players_min"))
        {
            $this->logWriter->logWarning("Minimum round Player count does not meet the ASP requirement... Aborting");
            throw new Exception("Minimum round Player count does not meet the ASP requirement");
        }

        // ---------------------------------------------------------------------
        // Ensure the army IDs are not unknown
        if ($this->team1ArmyId >= StatsData::$NumArmies)
        {
            $message = sprintf("Unknown ArmyId (%d) for team 1 found in Snapshot.. Aborting", $this->team1ArmyId);
            $this->logWriter->logWarning($message);
            throw new Exception($message);
        }
        else if ($this->team2ArmyId >= StatsData::$NumArmies)
        {
            $message = sprintf("Unknown ArmyId (%d) for team 2 found in Snapshot.. Aborting", $this->team2ArmyId);
            $this->logWriter->logWarning($message);
            throw new Exception($message);
        }

        // ---------------------------------------------------------------------
        // Ensure the game mode is not unknown
        if ($this->gameMode >= StatsData::$NumGamemodes)
        {
            $message = sprintf("Unknown GameMode id (%d) found in Snapshot.. Aborting", $this->gameMode);
            $this->logWriter->logWarning($message);
            throw new Exception($message);
        }

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
            $query = new UpdateOrInsertQuery($connection, 'round');
            $query->set('map_id', '=', $this->mapId);
            $query->set('server_id', '=', $this->serverId);
            $query->set('mod_id', '=', $modId);
            $query->set('gamemode_id', '=', $this->gameMode);
            $query->set('team1_army_id', '=', $this->team1ArmyId);
            $query->set('team2_army_id', '=', $this->team2ArmyId);
            $query->set('time_start', '=', $this->roundStartTime);
            $query->set('time_end', '=', $this->roundEndTime);
            $query->set('time_imported', '=', time());
            $query->set('winner', '=', $this->winningTeam);
            $query->set('tickets1', '=', $this->team1Tickets);
            $query->set('tickets2', '=', $this->team2Tickets);
            $query->executeInsert();

            // Grab round ID
            $roundId = $connection->lastInsertId("id");
            $wasDrawRound = ($this->winningArmyId == -1);

            // Init BattleSpy
            $this->battleSpy = new BattleSpy($connection, $this->serverId, $roundId);

            // ********************************
            // Check Player Integrity
            // ********************************
            foreach ($this->players as $player)
            {
                // Check if player exists already
                $sql = "SELECT permban, bantime FROM player WHERE id=%d LIMIT 1";
                $row = $connection->query(sprintf($sql, $player->id))->fetch();

                // If player does not exist, stop here!
                if (empty($row))
                {
                    // Is this a Cross Service Exploitation?
                    if (!$player->isAi)
                    {
                        $message = sprintf(
                            "Player Not Found! Cross Service Exploitation found on player (%s) with pid (%d)",
                            $player->name,
                            $player->id
                        );
                    }
                    else
                    {
                        $message = sprintf("Unauthorized Bot/Offline player found: %s (%d)!", $player->name, $player->id);
                    }

                    // Write log
                    $this->logWriter->logError($message);
                    throw new Exception($message);
                }
                else
                {
                    $banned = (int)$row['permban'];
                    $bantime = (int)$row['bantime'];

                    // Check if the player was banned before the start of the round. Servers should be
                    // using the VerifyPlayer module to prevent this from happening
                    if ($banned && $bantime < ($this->roundStartTime + 3600))
                    {
                        $message = sprintf("Banned Player Found in Snapshot: %s (%d)!", $player->name, $player->id);
                        $this->logWriter->logError($message);
                        throw new Exception($message);
                    }
                }
            }

            // ********************************
            // Process Players
            // ********************************
            foreach ($this->players as $player)
            {
                // Player meets min round time or are we ignoring AI?
                if ($player->roundTime < Config::Get('stats_min_player_game_time') || $player->isAi && Config::Get('stats_ignore_ai'))
                    continue;

                // Define some variables
                $onWinningTeam = $player->team == $this->winningTeam;

                // Write log
                $this->logWriter->logNotice("Processing Player (%d)", $player->id);

                // Check if player exists already
                $sql = "SELECT lastip, country, rank_id, killstreak, deathstreak, bestscore FROM player WHERE id=%d LIMIT 1";
                $row = $connection->query(sprintf($sql, $player->id))->fetch();

                // Run player check through BattleSpy
                $this->battleSpy->analyze($player);

                // Prepare for player update / insertion
                $query = new UpdateOrInsertQuery($connection, 'player');
                $query->set('time', '+', $player->roundTime);
                $query->set('rounds', '+', (int)$player->completedRound);
                $query->set('lastip', '=', $player->ipAddress);
                $query->set('rank_id', '=', $player->rank);
                $query->set('score', '+', $player->roundScore);
                $query->set('cmdscore', '+', $player->commandScore);
                $query->set('skillscore', '+', $player->skillScore);
                $query->set('teamscore', '+', $player->teamScore);
                $query->set('kills', '+', $player->kills);
                $query->set('deaths', '+', $player->deaths);
                $query->set('captures', '+', $player->flagCaptures);
                $query->set('captureassists', '+', $player->flagCaptureAssists);
                $query->set('neutralizes', '+', $player->flagNeutralizes);
                $query->set('neutralizeassists', '+', $player->flagNeutralizeAssists);
                $query->set('defends', '+', $player->flagDefends);
                $query->set('damageassists', '+', $player->damageAssists);
                $query->set('heals', '+', $player->heals);
                $query->set('revives', '+', $player->revives);
                $query->set('resupplies', '+', $player->resupplies);
                $query->set('repairs', '+', $player->repairs);
                $query->set('targetassists', '+', $player->targetAssists);
                $query->set('driverspecials', '+', $player->driverSpecials);
                $query->set('teamkills', '+', $player->teamKills);
                $query->set('teamdamage', '+', $player->teamDamage);
                $query->set('teamvehicledamage', '+', $player->teamVehicleDamage);
                $query->set('suicides', '+', $player->suicides);
                $query->set('banned', '+', $player->timesBanned);
                $query->set('kicked', '+', $player->timesKicked);
                $query->set('cmdtime', '+', $player->cmdTime);
                $query->set('sqltime', '+', $player->sqlTime);
                $query->set('sqmtime', '+', $player->sqmTime);
                $query->set('lwtime', '+', $player->lwTime);
                $query->set('timepara', '+', $player->timeParachute);
                $query->set('lastonline', '=', $this->roundEndTime);
                $query->set('mode0', '+', ($this->gameMode == 0));
                $query->set('mode1', '+', ($this->gameMode == 1));
                $query->set('mode2', '+', ($this->gameMode == 2));

                // Set wins / losses
                if (!$wasDrawRound)
                {
                    $query->set('wins', '+', $onWinningTeam);
                    $query->set('losses', '+', (!$onWinningTeam));
                }

                // Correct rank if needed
                $rank = (int)$row['rank_id'];
                if ($rank > $player->rank && $rank != 11 && $rank != 21)
                {
                    $player->rank = $rank;
                    $this->logWriter->logNotice("Rank correction ({$player->id}), Using database rank ({$rank})");
                }

                // Calculate best killstreak/deathstreak
                if ($player->killStreak > (int)$row['killstreak'])
                    $query->set('killstreak', '=', $player->killStreak);

                if ($player->deathStreak > (int)$row['deathstreak'])
                    $query->set('deathstreak', '=', $player->deathStreak);

                if ($player->roundScore > (int)$row['bestscore'])
                    $query->set('bestscore', '=', $player->roundScore);

                // Execute the update
                $query->where('id', '=', $player->id);
                $query->executeUpdate();

                // ********************************
                // Insert Player history.
                // ********************************
                $this->logWriter->logDebug("Processing Player History Data (%d)", $player->id);
                $query = new UpdateOrInsertQuery($connection, 'player_round_history');
                $query->set('player_id', '=', $player->id);
                $query->set('round_id', '=', $roundId);
                $query->set('army_id', '=', $player->armyId);
                $query->set('time', '=', $player->roundTime);
                $query->set('rank_id', '=', $player->rank);
                $query->set('score', '=', $player->roundScore);
                $query->set('cmdscore', '=', $player->commandScore);
                $query->set('skillscore', '=', $player->skillScore);
                $query->set('teamscore', '=', $player->teamScore);
                $query->set('kills', '=', $player->kills);
                $query->set('deaths', '=', $player->deaths);
                $query->set('captures', '=', $player->flagCaptures);
                $query->set('captureassists', '=', $player->flagCaptureAssists);
                $query->set('neutralizes', '=', $player->flagNeutralizes);
                $query->set('neutralizeassists', '=', $player->flagNeutralizeAssists);
                $query->set('defends', '=', $player->flagDefends);
                $query->set('heals', '=', $player->heals);
                $query->set('revives', '=', $player->revives);
                $query->set('resupplies', '=', $player->resupplies);
                $query->set('repairs', '=', $player->repairs);
                $query->set('damageassists', '=', $player->damageAssists);
                $query->set('targetassists', '=', $player->targetAssists);
                $query->set('driverspecials', '=', $player->driverSpecials);
                $query->set('teamkills', '=', $player->teamKills);
                $query->set('teamdamage', '=', $player->teamDamage);
                $query->set('teamvehicledamage', '=', $player->teamVehicleDamage);
                $query->set('suicides', '=', $player->suicides);
                $query->set('killstreak', '=', $player->killStreak);
                $query->set('deathstreak', '=', $player->deathStreak);
                $query->set('cmdtime', '=', $player->cmdTime);
                $query->set('sqltime', '=', $player->sqlTime);
                $query->set('sqmtime', '=', $player->sqmTime);
                $query->set('lwtime', '=', $player->lwTime);
                $query->set('timepara', '=', $player->timeParachute);
                $query->set('completed', '=', $player->completedRound);
                $query->set('banned', '=', $player->timesBanned);
                $query->set('kicked', '=', $player->timesKicked);
                $query->executeInsert();

                // ********************************
                // Process Player Army Data
                // ********************************
                $this->logWriter->logDebug("Processing Army Data (%d)", $player->id);
                foreach ($player->timeAsArmy as $id => $time)
                {
                    // Skip un-played armies
                    if ($time == 0)
                        continue;

                    $query = new UpdateOrInsertQuery($connection, 'player_army');
                    $query->where('player_id', '=', $player->id);
                    $query->where('army_id', '=', $id);
                    $query->set('time', '+', $time);

                    // If the player ended the game as this army, update with round info
                    if ($player->armyId == $id)
                    {
                        $query->set('wins', '+', $onWinningTeam);
                        $query->set('losses', '+', (!$onWinningTeam));
                        $query->set('best', 'g', $player->roundScore);
                        $query->set('worst', 'l', $player->roundScore);
                    }

                    $query->execute();

                    // Add to History
                    $query = new UpdateOrInsertQuery($connection, 'player_army_history');
                    $query->set('player_id', '=', $player->id);
                    $query->set('round_id', '=', $roundId);
                    $query->set('army_id', '=', $id);
                    $query->set('time', '=', $time);
                    $query->executeInsert();

                }

                // ********************************
                // Process Player Kills
                // ********************************
                $this->logWriter->logDebug("Processing Kill Data (%d)", $player->id);
                $query = new UpdateOrInsertQuery($connection, 'player_kill');
                $query->where('attacker', '=', $player->id);
                $query2 = new UpdateOrInsertQuery($connection, 'player_kill_history');
                $query2->set('attacker', '=', $player->id);
                $query2->set('round_id', '=', $roundId);
                foreach ($player->victims as $pid => $count)
                {
                    // Update main stats record
                    $query->where('victim', '=', $pid);
                    $query->set('count', '+', $count);
                    $query->execute();

                    // Add to History
                    $query2->set('victim', '=', $pid);
                    $query2->set('count', '=', $count);
                    $query2->executeInsert();
                }

                // ********************************
                // Process Player Kit Data
                // ********************************
                $this->logWriter->logDebug("Processing Kit Data (%d)", $player->id);
                $query = new UpdateOrInsertQuery($connection, 'player_kit');
                $query->where('player_id', '=', $player->id);
                $query2 = new UpdateOrInsertQuery($connection, 'player_kit_history');
                $query2->set('player_id', '=', $player->id);
                $query2->set('round_id', '=', $roundId);
                foreach ($player->kitData as $object)
                {
                    // Update main stats record
                    $query->set('time', '+', $object->time);
                    $query->set('kills', '+', $object->kills);
                    $query->set('deaths', '+', $object->deaths);
                    $query->where('kit_id', '=', $object->id);
                    $query->execute();

                    // Add to History
                    $query2->set('kit_id', '=', $object->id);
                    $query2->set('time', '=', $object->time);
                    $query2->set('kills', '=', $object->kills);
                    $query2->set('deaths', '=', $object->deaths);
                    $query2->executeInsert();
                }

                // ********************************
                // Process Player Map Data
                // ********************************
                $this->logWriter->logDebug("Processing Map Data (%d)", $player->id);
                $query = new UpdateOrInsertQuery($connection, 'player_map');
                $query->set('time', '+', $player->roundTime);
                $query->set('wins', '+', $onWinningTeam ? 1 : 0);
                $query->set('losses', '+', $onWinningTeam ? 0 : 1);
                $query->set('bestscore', 'g', $player->roundScore);
                $query->set('worstscore', 'l', $player->roundScore);
                $query->where('player_id', '=', $player->id);
                $query->where('map_id', '=', $this->mapId);
                $query->execute();

                // ********************************
                // Process Player Vehicle Data
                // ********************************
                $this->logWriter->logDebug("Processing Vehicle Data (%d)", $player->id);
                $query = new UpdateOrInsertQuery($connection, 'player_vehicle');
                $query->where('player_id', '=', $player->id);
                $query2 = new UpdateOrInsertQuery($connection, 'player_vehicle_history');
                $query2->set('player_id', '=', $player->id);
                $query2->set('round_id', '=', $roundId);
                foreach ($player->vehicleData as $object)
                {
                    // Update main stats record
                    $query->set('time', '+', $object->time);
                    $query->set('kills', '+', $object->kills);
                    $query->set('deaths', '+', $object->deaths);
                    $query->set('roadkills', '+', $object->roadKills);
                    $query->where('vehicle_id', '=', $object->id);
                    $query->execute();

                    // Add to History
                    $query2->set('vehicle_id', '=', $object->id);
                    $query2->set('time', '=', $object->time);
                    $query2->set('kills', '=', $object->kills);
                    $query2->set('deaths', '=', $object->deaths);
                    $query2->set('roadkills', '=', $object->roadKills);
                    $query2->executeInsert();
                }

                // ********************************
                // Process Player Weapon Data
                // ********************************
                $this->logWriter->logDebug("Processing Weapon Data (%d)", $player->id);
                $query = new UpdateOrInsertQuery($connection, 'player_weapon');
                $query->where('player_id', '=', $player->id);
                $query2 = new UpdateOrInsertQuery($connection, 'player_weapon_history');
                $query2->set('player_id', '=', $player->id);
                $query2->set('round_id', '=', $roundId);
                foreach ($player->weaponData as $object)
                {
                    // Update main stats record
                    $query->set('time', '+', $object->time);
                    $query->set('kills', '+', $object->kills);
                    $query->set('deaths', '+', $object->deaths);
                    $query->set('fired', '+', $object->fired);
                    $query->set('hits', '+', $object->hits);
                    $query->set('deployed', '+', $object->deployed);
                    $query->where('weapon_id', '=', $object->id);
                    $query->execute();

                    // Add to History
                    $query2->set('weapon_id', '=', $object->id);
                    $query2->set('time', '=', $object->time);
                    $query2->set('kills', '=', $object->kills);
                    $query2->set('fired', '=', $object->fired);
                    $query2->set('hits', '=', $object->hits);
                    $query2->set('deployed', '=', $object->deployed);
                    $query2->executeInsert();
                }

                // ********************************
                // Process Player Awards Data
                // ********************************
                $this->logWriter->logDebug("Processing Award Data (%d)", $player->id);
                if ($player->completedRound || !Config::Get('stats_awds_complete'))
                {
                    // Add Backend awards to player
                    foreach (AwardData::$BackendAwards as $award)
                    {
                        if ($award->criteriaMet($player, $connection, $level))
                            $player->earnedAwards[$award->awardId] = $level;
                    }

                    // Log
                    $this->logWriter->logDebug("Player (%d) Earned %d Awards...", [$player->id, count($player->earnedAwards)]);

                    foreach ($player->earnedAwards as $key => $value)
                    {
                        // Get our award type. Award.Key is the ID, Award.Value is the level (or count)
                        $isMedal = ($key > 2000000 && $key < 3000000);
                        $isBadge = ($key < 2000000);

                        // Check is player has this award already
                        $query = "SELECT COUNT(*) FROM player_award WHERE player_id=%d AND award_id=%d";
                        if ($isBadge)
                            $query .= " AND level=". (int)$value;

                        // Check for prior awarding of award
                        $result = $connection->query( sprintf($query, $player->id, $key) . ' LIMIT 1');
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
                                    $query = "SELECT COUNT(*) FROM player_award WHERE player_id=%d AND award_id=%d AND level=%d LIMIT 1";
                                    $result = $connection->query( sprintf($query, $player->id, $key, $j) );
                                    if (($count = (int)$result->fetchColumn(0)) == 0)
                                    {
                                        // Prepare Query
                                        $query = new UpdateOrInsertQuery($connection, 'player_award');
                                        $query->set('player_id', '=', $player->id);
                                        $query->set('award_id', '=', $key);
                                        $query->set('round_id', '=', $roundId);
                                        $query->set('level', '=', $j);
                                        $query->executeInsert();
                                    }
                                }
                            }

                            // Add player award
                            $query = new UpdateOrInsertQuery($connection, 'player_award');
                            $query->set('player_id', '=', $player->id);
                            $query->set('award_id', '=', $key);
                            $query->set('round_id', '=', $roundId);
                            $query->set('level', '=', $value);
                            $query->executeInsert();
                        }
                        else if ($isMedal) // === Player has received this award prior === //
                        {
                            $query = new UpdateOrInsertQuery($connection, 'player_award');
                            $query->where('player_id', '=', $player->id);
                            $query->where('award_id', '=', $key);
                            $query->set('round_id', '=', $roundId);
                            $query->set('level', '=', 1);
                            $query->executeInsert();
                        }

                        // Add best round count if player earned best round medal
                        if ($key == 2051907)
                        {
                            $query = new UpdateOrInsertQuery($connection, 'player_army');
                            $query->where('player_id', '=', $player->id);
                            $query->where('army_id', '=', $player->armyId);
                            $query->set('brnd', '+', 1);
                            $query->execute();
                        }
                    } // End Foreach Award
                } // End Award Processing
            } // End player loop

            // ********************************
            // Process ServerInfo
            // ********************************
            $this->logWriter->logDebug("Saving server updated information");
            $query = new UpdateOrInsertQuery($connection, 'server');
            $query->set('name', '=', StringHelper::SubStrWords($this->serverName, 100));
            $query->set('gameport', '=', $this->serverPort);
            $query->set('queryport', '=', $this->queryPort);
            $query->set('lastupdate', 'g', time());
            $query->where('id', '=', $this->serverId);
            $query->executeUpdate();

            // ********************************
            // Save BattleSpy Reports
            // ********************************
            $this->battleSpy->finalize();

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
        $time = new \DateTime("@{$this->roundEndTime}", new \DateTimeZone("UTC"));
        return "{$this->serverId}-{$this->mapName}_{$time->format('Ymd_His')}.json";
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