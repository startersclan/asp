<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Collections\Dictionary;
use System\Database;
use System\Database\UpdateOrInsertQuery;
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\Snapshot;

/**
 * Snapshots Model
 *
 * @package Models
 * @subpackage Snashots
 */
class SnapshotsModel
{
    /**
     * Fetches an array of all un-authorized snapshots, and returns a data array
     * of information about the snapshot.
     *
     * @param string $folder The snapshot folder name
     *
     * @return array
     *
     * @throws DirectoryNotFoundException thrown if the snapshot folder does not exist.
     * @throws IOException thrown if there is an error opening a snapshot file.
     */
    public function getSnapshots($folder)
    {
        // Get snapshots
        $path = Path::Combine(SYSTEM_PATH, "snapshots", $folder);
        $files = Directory::GetFiles($path, '.*\.json');

        // Create objects
        $snapshots = [];
        foreach ($files as $file)
        {
            // Open snapshot file, and grab its JSON
            $stream = File::OpenRead($file);
            $json = json_decode($stream->readToEnd(), true);
            $stream->close();

            // Ensure the JSON is valid
            if ($json != null)
            {
                $snapshot = new Dictionary(true, $json);
                $snapshots[] = [
                    'name' => Path::GetFilenameWithoutExtension($file),
                    'prefix' => $snapshot['prefix'],
                    'server' => $snapshot['serverName'],
                    'port' => $snapshot['gamePort'],
                    'ipaddress' => $snapshot['serverIp'],
                    'map' => $snapshot['mapName'],
                    'players' => count($snapshot['players']),
                    'date' => date('M j, Y G:i T', (int)$snapshot['mapEnd'])
                ];
            }
        }

        return $snapshots;
    }

    /**
     * Imports a snapshot, adding the server to the server table if it does not exist,
     * otherwise authorizing the server to post snapshots.
     *
     * After the snapshot is parsed, it will be moved to the 'processed' snapshot directory.
     *
     * @param string $file The full file path to the snapshot json file
     * @param string $message [Reference Variable] Gets the result message
     *
     * @return void
     *
     * @throws Exception thrown if there is an error parsing the JSON content, or if
     *  the snapshot data is incomplete.
     * @throws IOException thrown if there is a problem moving the snapshot file to the
     *  processed folder.
     */
    public function importSnapshot($file, &$message)
    {
        // Grab that DB connection
        $pdo = Database::GetConnection('stats');

        // Parse snapshot data
        $stream = File::OpenRead($file);
        $json = $stream->readToEnd();
        $data = json_decode($json, true);
        $stream->close();

        // Ensure we can parse json
        if ($data == null)
            throw new Exception("Unable to decode json from snapshot: " . $file);

        // Create snapshot
        $data = new Dictionary(false, $data);
        $snapshot = new Snapshot($data);

        // Ensure this is an authorized server
        $ip = $pdo->quote($snapshot->serverIp);
        $port = $snapshot->serverPort;

        // Ensure server exists and is authorized before proceeding
        $row = $pdo->query("SELECT id, authorized FROM server WHERE `ip`={$ip} AND `port`={$port} LIMIT 1")->fetch();
        if (empty($row))
        {
            // Create server entry
            $query = new UpdateOrInsertQuery($pdo, 'server');
            $query->set('prefix', '=', $snapshot->serverPrefix);
            $query->set('name', '=', $snapshot->serverName);
            $query->set('ip', '=', $snapshot->serverIp);
            $query->set('port', '=', $snapshot->serverPort);
            $query->set('queryport', '=', $snapshot->queryPort);
            $query->set('authorized', '=', 1);
            $query->set('lastupdate', '=', $snapshot->roundEndTime);
            $query->executeInsert();
        }
        else
        {
            $serverId = (int)$row['id'];
            $authorized = ((int)$row['authorized']) == 1;
            if (!$authorized)
                $pdo->exec("UPDATE `server` SET authorized=1 WHERE id={$serverId}");
        }

        // Ensure snapshot is not already processed from before!
        if ($snapshot->isProcessed())
        {
            $message = "Snapshot was already processed.";
        }
        else
        {
            // Process data
            $snapshot->processData();
            $message = "Snapshot was processed successfully.";
        }

        /**
         * Move file. Use snapshot's getFilename() in case this import was planted by an admin,
         * which was created locally on the bf2 servers snapshot path. Having the correct filename
         * is important for the /roundinfo/view/ ASP page.
         */
        $newPath = Path::Combine(SYSTEM_PATH, "snapshots", "processed", $snapshot->getFilename());
        File::Move($file, $newPath);
    }
}