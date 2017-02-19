<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Collections\Dictionary;
use System\Database;
use System\Response;
use System\View;

class Gamedata
{
    /**
     * @protocol    ANY
     * @request     /ASP/gamedata
     * @output      html
     */
    public function index()
    {
        // Require database connection
        if (DB_VER == '0.0.0')
        {
            Response::Redirect('install');
            die;
        }

        $pdo = Database::GetConnection('stats');
        $armies = $pdo->query("SELECT * FROM army ORDER BY id")->fetchAll();
        $kits = $pdo->query("SELECT * FROM kit ORDER BY id")->fetchAll();
        $vehicles = $pdo->query("SELECT * FROM vehicle ORDER BY id")->fetchAll();
        $weapons = $pdo->query("SELECT * FROM weapon ORDER BY id")->fetchAll();

        // Load view
        $view = new View('index', 'gamedata');
        $view->set('armies', $armies);
        $view->set('kits', $kits);
        $view->set('vehicles', $vehicles);
        $view->set('weapons', $weapons);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/js/select2/select2.min.js");
        $view->attachScript("/ASP/frontend/modules/gamedata/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/gamedata/add
     * @output      json
     */
    public function postAdd()
    {
        $pdo = Database::GetConnection('stats');
        try
        {
            // Use a dictionary here to gain an exception on missing array item
            $items = new Dictionary(false, $_POST);

            // Define server id
            $id = (isset($_POST['itemId'])) ? (int)$items['itemId'] : 0;
            $name = preg_replace('/[^A-Za-z0-9_\-\s\t\/\.]/', '', trim($items['itemName']));
            $type = preg_replace("/[^A-Za-z]/", '', $items['itemType']);

            // Switch on our action base
            switch ($_POST['action'])
            {
                case 'add':
                    /** @noinspection SqlResolve */
                    $max = (int)$pdo->query("SELECT COALESCE(max(`id`), -1) FROM $type")->fetchColumn(0);
                    $pdo->insert($type, [
                        'id' => ++$max,
                        'name' => $name,
                    ]);

                    // Get insert ID
                    echo json_encode(['success' => true, 'itemId' => $max, 'itemName' => $name, 'itemType' => $type]);
                    break;
                case 'edit':
                    $pdo->update($type, ['name' => $name], ['id' => $id]);
                    echo json_encode(['success' => true, 'itemId' => $id, 'itemName' => $name, 'itemType' => $type]);
                    break;
                default:
                    echo json_encode(array('success' => false, 'message' => 'Invalid Action'));
                    die;
            }
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage(), 'lastQuery' => $pdo->lastQuery) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/gamedata/delete
     * @output      json
     */
    public function postDelete()
    {
        // Form post?
        if ($_POST['action'] != 'delete')
        {
            echo json_encode( array('success' => false, 'message' => 'Invalid Action') );
            die;
        }

        // Grab database connection
        $pdo = Database::GetConnection('stats');
        if ($pdo === false)
        {
            echo json_encode( array('success' => false, 'message' => 'Unable to connect to database!') );
            die;
        }

        // Prepared statement!
        try
        {
            // Use a dictionary here to gain an exception on missing array item
            $items = new Dictionary(false, $_POST);

            // Define server id
            $itemId = (int)$items['itemId'];
            $type = preg_replace("/[^A-Za-z]/", '', $items['itemType']);

            // Prepare statement
            $stmt = $pdo->prepare("DELETE FROM `player_{$type}` WHERE `id`=:id");
            $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $stmt->execute();

            // Prepare statement
            $stmt = $pdo->prepare("DELETE FROM `{$type}` WHERE `id`=:id");
            $stmt->bindValue(':id', $itemId, PDO::PARAM_INT);
            $stmt->execute();

            // Echo success
            echo json_encode( array('success' => true, 'itemId' => $itemId, 'itemType' => $type) );
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }
}