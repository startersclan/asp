<?php
use GameQ\GameQ;
use System\TimeHelper;

/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
class ServerModel
{
    /**
     * Queries a BF2 server for its current round iformation
     *
     * @param string $ip The servers IP address
     * @param int $port The servers Game Query Port
     *
     * @return array
     */
    public function queryServer($ip, $port)
    {
        // Include the AutoLoader for GameQ
        include SYSTEM_PATH . DS . 'framework' . DS . 'GameQ' . DS . 'Autoloader.php';

        // Query the server
        $key = $ip . ':'. $port;
        $GameQ = new GameQ();
        $GameQ->addServer(['type' => 'bf2', 'host' => $key, 'options' => ['query_port' => $port]]);
        $GameQ->setOption('timeout', 5); // seconds
        $results = $GameQ->process();

        // Prepare return values
        $results = $results[$key];
        $return = [
            'server' => [],
            'team1' => [],
            'team2' => []
        ];

        // Format the return array
        foreach ($results as $key => $value)
        {
            if ($key == "players")
            {
                // Separate players by team
                foreach ($value as $player)
                {
                    // For some reason, GameQ adds the teams as a player too
                    if (!isset($player['team']))
                        continue;

                    $return['team'. $player['team']][] = $player;
                }
            }
            else
            {
                $return['server'][$key] = $value;
            }
        }

        // Send return array
        unset($results);
        return $return;
    }

    /**
     * Converts an army name from a server response to it's full name representation.
     *
     * @param string $name [Reference Variable] Returns the army full name if we can.
     * @param int $flag [Reference Variable] Returns the flag ID for this army, or -1
     *
     * @return bool
     */
    public function getArmy(&$name, &$flag)
    {
        switch (strtolower($name))
        {
            case "mec":
                $flag = 1;
                $name = "Middle Eastern Coalition";
                return true;

            case "us":
            case "usa":
                $flag = 0;
                $name = "United States Marine Corps";
                return true;

            case "ch":
                $flag = 2;
                $name = "People's Liberation Army";
                return true;

            case "seal":
                $flag = 0;
                $name = "Seals";
                return true;

            case "sas":
                $flag = 4;
                $name = "SAS";
                return true;

            case "spetz":
                $flag = 5;
                $name = "Spetsnaz";
                return true;

            case "mecsf":
                $flag = 1;
                $name =  "Middle Eastern Coalition SF";
                return true;

            case "chinsurgent":
            case "rebels":
                $flag = 7;
                $name = "Rebels";
                return true;

            case "meinsurgent":
            case "insurgents":
                $flag = 8;
                $name = "Insurgents";
                return true;

            case "eu":
                $flag = 9;
                $name = "European Union";
                return true;

            default:
                $flag = -1;
                return false;
                break;
        }
    }

    /**
     * Formats the values of a servers response
     *
     * @param array $rules
     *
     * @return array
     */
    public function formatRules($rules)
    {
        $return = [];

        foreach ($rules as $key => $value)
        {
            switch ($key)
            {
                case 'password':
                case 'bf2_ranked':
                case 'bf2_bots':
                case 'bf2_dedicated':
                    $return[$key] = (((int)$value) == 1) ? "True" : "False";
                    break;
                case 'bf2_anticheat':
                case 'bf2_friendlyfire':
                case 'bf2_globalunlocks':
                case 'bf2_autobalanced':
                    $return[$key] = (((int)$value) == 1) ? "Enabled" : "Disabled";
                    break;
                case 'bf2_novehicles':
                    $return[$key] = (((int)$value) == 0) ? "Enabled" : "Disabled";
                    break;
                case 'roundtime':
                case 'timelimit':
                    $return[$key] = TimeHelper::SecondsToHms((int)$value);
                    break;
                case 'bf2_teamratio':
                    $return[$key] = (int)$value;
                    break;
                case 'teams':
                    $return['team1score'] = 0;
                    $return['team2score'] = 0;
                    if (is_array($value))
                    {
                        $return['team1score'] = $value[0]['score'];
                        $return['team2score'] = $value[1]['score'];
                    }
                    break;
                default:
                    $return[$key] = $value;
                    break;
            }
        }

        return $return;
    }

    /**
     * Adds the players rank to each player from a server response
     *
     * @param PDO $pdo
     * @param array $players
     *
     * @return array
     */
    public function addPlayerRanks(PDO $pdo, $players)
    {
        $return = [];

        foreach ($players as $player)
        {
            $id = (int)$player['pid'];
            $rows = $pdo->query("SELECT name, rank FROM player WHERE id={$id} LIMIT 1");
            if ($row = $rows->fetch())
            {
                $player['rank'] = ($row['name'] == $player['name']) ? (int)$row['rank'] : 0;
            }
            else
            {
                $player['rank'] = 0;
            }

            $return[] = $player;
        }

        return $return;
    }
}