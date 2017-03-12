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
use System\Controller;
use System\Database;
use System\Database\UpdateOrInsertQuery;
use System\IO\Directory;
use System\IO\File;
use System\IO\Path;
use System\Snapshot;
use System\View;

class Snapshots extends Controller
{
    /**
     * @protocol    GET
     * @request     /ASP/snapshots
     * @output      html
     */
    public function index()
    {
        // Get snapshots
        $path = Path::Combine(SYSTEM_PATH, "snapshots", "unauthorized");
        $files = Directory::GetFiles($path, '.*\.json');

        // Create objects
        $snapshots = [];
        foreach ($files as $file)
        {
            $stream = File::OpenRead($file);
            $json = json_decode($stream->readToEnd(), true);
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

        // Load view
        $view = new View('index', 'snapshots');
        $view->set('snapshots', $snapshots);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/snapshots/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/snapshots/accept
     * @output      json
     */
    public function postAccept()
    {
        // Ensure a valid action
        if (!isset($_POST['action']) || $_POST['action'] != 'process')
        {
            if (isset($_POST['ajax']))
                echo json_encode(['success' => false, 'message' => 'Invalid Action!']);
            else
                $this->index();

            return;
        }

        // Ensure we have a backup selected
        if (!isset($_POST['snapshot']))
        {
            echo json_encode(['success' => false, 'message' => 'No snapshots specified!']);
            return;
        }

        $file = Path::Combine(SYSTEM_PATH, "snapshots", "unauthorized", $_POST['snapshot'] . '.json');
        if (!File::Exists($file))
        {
            echo json_encode(['success' => false, 'message' => 'No snapshots with the filename exists: ' . $_POST['snapshot']]);
            return;
        }

        // Ensure that the directories we need are writable
        $path1 = Path::Combine(SYSTEM_PATH, "snapshots", "processed");
        $path2 = Path::Combine(SYSTEM_PATH, "snapshots", "failed");
        if (!Directory::IsWritable($path1) || !Directory::IsWritable($path2))
        {
            echo json_encode(['success' => false, 'message' => 'Not all snapshot directories are writable. Please Test your system configuration.']);
            return;
        }

        try
        {
            $pdo = Database::GetConnection('stats');

            // Parse snapshot data
            $stream = File::OpenRead($file);
            $json = $stream->readToEnd();
            $data = json_decode($json, true);
            $stream->close();

            // Ensure we can parse json
            if ($data == null)
            {
                echo json_encode(['success' => false, 'message' => "Unable to decode json from snapshot: " . $file]);
                return;
            }

            // Create snapshot
            $data = new Dictionary(false, $data);
            $snapshot = new Snapshot($data);

            // Ensure this is an authorized server
            $ip = $pdo->quote($snapshot->serverIp);
            $port = $snapshot->serverPort;

            // Ensure server exists and is authorized before proceeding
            $result = $pdo->query("SELECT id, authorized FROM server WHERE `ip`={$ip} AND `port`={$port} LIMIT 1");
            if (!($row = $result->fetch()))
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

            // Move file
            $newPath = Path::Combine(SYSTEM_PATH, "snapshots", "processed", Path::GetFilename($file));
            File::Move($file, $newPath);

            // Tell the client of the success
            echo json_encode(['success' => true, 'message' => $message]);
        }
        catch (IOException $e)
        {
            $message = sprintf("Failed to process snapshot (%s)!\n\n%s", $file, $e->getMessage());
            echo json_encode(['success' => false, 'message' => $message]);
        }
        catch (Exception $e)
        {
            try
            {
                // Move snapshot to failed
                $newPath = Path::Combine(SYSTEM_PATH, "snapshots", "failed", Path::GetFilename($file));
                File::Move($file, $newPath);
            }
            catch (Exception $ex)
            {
                // ignore
            }

            // Output message
            $message = sprintf("Failed to process snapshot (%s)!\n\n%s", $file, $e->getMessage());
            echo json_encode(['success' => false, 'message' => $message]);
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/snapshots/delete
     * @output      json
     */
    public function postDelete()
    {
        // Ensure a valid action
        if (!isset($_POST['action']) || $_POST['action'] != 'delete')
        {
            if (isset($_POST['ajax']))
                echo json_encode(['success' => false, 'message' => 'Invalid Action!']);
            else
                $this->index();

            return;
        }

        // Ensure we have a backup selected
        if (!isset($_POST['snapshots']))
        {
            echo json_encode(['success' => false, 'message' => 'No snapshots specified!']);
            return;
        }

        $path = Path::Combine(SYSTEM_PATH, "snapshots", "unauthorized");

        try
        {
            foreach ($_POST['snapshots'] as $file)
            {
                $file = Path::Combine($path, $file . '.json');
                File::Delete($file);
            }

            echo json_encode(['success' => true, 'message' => 'Snapshots Removed.']);
        }
        catch (Exception $e)
        {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}