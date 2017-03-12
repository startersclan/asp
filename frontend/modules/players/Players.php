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
use System\DataTables;
use System\Player;
use System\Response;
use System\TimeHelper;
use System\View;

class Players extends Controller
{
    /**
     * @protocol    ANY
     * @request     /ASP/players
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();

        // Load view
        $view = new View('index', 'players');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/jquery.form.js");
        $view->attachScript("/ASP/frontend/js/validate/jquery.validate-min.js");
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/js/select2/select2.min.js");
        $view->attachScript("/ASP/frontend/modules/players/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/modules/players/css/links.css");
        $view->attachStylesheet("/ASP/frontend/css/icons/icol16.css");
        $view->attachStylesheet("/ASP/frontend/js/select2/select2.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    ANY
     * @request     /ASP/players/view/id
     * @output      html
     */
    public function view($id)
    {
        // Require database connection
        $this->requireDatabase();

        // make sure we have an ID
        if (empty($id))
        {
            Response::Redirect('players');
            die;
        }

        // Load view
        $view = new View('view', 'players');
        $view->set('id', $id);

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/authorize
     * @output      json
     */
    public function postAuthorize()
    {
        // Form post?
        if ($_POST['action'] != 'ban' && $_POST['action'] != 'unban')
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

        $mode = ($_POST['action'] == 'ban') ? 1 : 0;

        // Prepared statement!
        try
        {
            // Ensure pid exists
            if (!isset($_POST['playerId']))
                throw new Exception('No Player ID Specified!');

            // Extract player ID
            $playerId = (int)$_POST['playerId'];

            // Prepare statement
            $stmt = $pdo->prepare("UPDATE player SET permban=$mode WHERE id=:id");
            $stmt->bindValue(':id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Echo success
            echo json_encode( array('success' => true, 'message' => $_POST['playerId']) );
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/delete
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
            // Ensure pid exists
            if (!isset($_POST['playerId']))
                throw new Exception('No Player ID Specified!');

            // Extract player ID
            $playerId = (int)$_POST['playerId'];

            // Prepare statement
            $stmt = $pdo->prepare("DELETE FROM player WHERE id=:id");
            $stmt->bindValue(':id', $playerId, PDO::PARAM_INT);
            $stmt->execute();

            // Echo success
            echo json_encode( array('success' => true, 'message' => $_POST['playerId']) );
        }
        catch (Exception $e)
        {
            echo json_encode( array('success' => false, 'message' => 'Query Failed! '. $e->getMessage()) );
            die;
        }
    }

    /**
     * @protocol    POST
     * @request     /ASP/players/add
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
            $id = (isset($_POST['playerId'])) ? (int)$items['playerId'] : 0;
            $name = preg_replace("/[^". Player::NAME_REGEX ."]/", '', $items['playerName']);

            // Switch on our action base
            switch ($_POST['action'])
            {
                case 'add':
                    $pdo->insert('player', [
                        'name' => ' '. trim($name),
                        'password' => md5($items['playerPassword']),
                        'rank' => $items['playerRank'],
                        'country' => $items['playerCountry']
                    ]);

                    // Get insert ID
                    echo json_encode(['success' => true, 'mode' => 'add']);
                    break;
                case 'edit':

                    // Fetch player
                    $pass = $pdo->query("SELECT `password` FROM player WHERE id=". $id)->fetchColumn(0);
                    if (!empty($pass))
                    {
                        // Online player here
                        $name = ' '. trim($name);
                    }

                    $cols = [
                        'name' => $name,
                        'country' => $items['playerCountry'],
                        'rank' => $items['playerRank']
                    ];

                    // Add password if it is not empty
                    if (!empty($items['playerPassword']))
                        $cols['password'] = $items['playerPassword'];

                    $pdo->update('player', $cols, ['id' => $id]);

                    echo json_encode(['success' => true, 'mode' => 'update']);
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
     * @request     /ASP/players/list
     * @output      json
     */
    public function postList()
    {
        try
        {
            $columns = [
                ['db' => 'id', 'dt' => 'id'],
                ['db' => 'name', 'dt' => 'name',
                    'formatter' => function( $d, $row ) {
                        $id = $row['id'];
                        return '<a href="/ASP/players/view/'.$id.'">'. $d .'</a>';
                    }
                ],
                ['db' => 'rank', 'dt' => 'rank',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/ranks/rank_{$d}.gif\">";
                    }
                ],
                ['db' => 'score', 'dt' => 'score',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'country', 'dt' => 'country'],
                ['db' => 'joined', 'dt' => 'joined',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return date('d M Y', $i);
                    }
                ],
                ['db' => 'lastonline', 'dt' => 'online',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return TimeHelper::FormatDifference($i, time());
                    }
                ],
                ['db' => 'clantag', 'dt' => 'clan'],
                ['db' => 'permban', 'dt' => 'permban',
                    'formatter' => function( $d, $row ) {
                        return $d == 0 ? '<font color="green">No</font>' : '<font color="red">Yes</font>';
                    }
                ],
                ['db' => 'kicked', 'dt' => 'actions',
                    'formatter' => function( $d, $row ) {
                        $id = $row['id'];
                        $banned = ($row['permban'] == 1) ? '' : ' style="display: none"';
                        $nbanned = ($row['permban'] == 0) ? '' : ' style="display: none"';

                        return '<span class="btn-group">
                            <a id="edit-btn-'. $id .'" href="#"  rel="tooltip" title="Edit Player" class="btn btn-small"><i class="icon-pencil"></i></a>
                            <a id="ban-btn-'. $id .'" href="#" rel="tooltip" title="Ban Player" class="btn btn-small"'. $nbanned .'><i class="icon-flag"></i></a>
                            <a id="unban-btn-'. $id .'" href="#" rel="tooltip" title="Unban Player" class="btn btn-small"'.$banned.'><i class="icon-ok"></i></a>
                            <a id="delete-btn-'. $id .'" href="#" rel="tooltip" title="Delete Player" class="btn btn-small"><i class="icon-trash"></i></a>
                        </span>';
                    }
                ],
            ];

            $pdo = Database::GetConnection('stats');
            $data = DataTables::FetchData($_POST, $pdo, 'player', 'id', $columns);

            echo json_encode($data);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}