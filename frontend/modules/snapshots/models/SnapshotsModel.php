<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2018, BF2statistics.com
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
                    'authid' => $snapshot['authId'],
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
     * @param bool $ignoreAuthorization if true, all security and authorization protocols will be skipped.
     *      Should always remain false unless an administrator OK's the snapshot for manual processing.
     * @param string $message [Reference Variable] Gets the result message
     *
     * @return void
     *
     * @throws Exception thrown if there is an error parsing the JSON content, or if
     *  the snapshot data is incomplete.
     * @throws IOException thrown if there is a problem moving the snapshot file to the
     *  processed folder.
     * @throws SecurityException if the snapshot has an invalid AuthId
     */
    public function importSnapshot($file, $ignoreAuthorization, &$message)
    {
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

        // Ensure we are bound to a stats provider
        if ($snapshot->providerId == 0)
            throw new SecurityException("Server is not authorized to post snapshot data");

        // Ensure snapshot is not already processed from before!
        if ($snapshot->isProcessed())
        {
            $message = "Snapshot was already processed.";
        }
        else
        {
            // Process data
            $snapshot->processData($ignoreAuthorization);
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