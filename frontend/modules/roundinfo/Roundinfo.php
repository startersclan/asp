<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Battlefield2;
use System\Controller;
use System\Database;
use System\DataTables;
use System\Response;
use System\View;

/**
 * Roundinfo Module Controller
 *
 * @package Modules
 */
class Roundinfo extends Controller
{
    /**
     * @var RoundInfoModel
     */
    protected $RoundInfoModel;

    /**
     * @protocol    ANY
     * @request     /ASP/roundinfo
     * @output      html
     */
    public function index()
    {
        // Require database connection
        parent::requireDatabase();

        // Load view
        $view = new View('index', 'roundinfo');

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/roundinfo/js/index.js");

        // Attach needed stylesheets
        $view->attachStylesheet("/ASP/frontend/modules/players/css/links.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    ANY
     * @request     /ASP/roundinfo/view/$id
     * @output      html
     *
     * @param int $id The round history ID
     */
    public function view($id = 0)
    {
        // Grab database connection
        parent::requireDatabase(true);
        $pdo = Database::GetConnection('stats');
        $id = (int)$id;

        // Ensure correct format for ID
        if ($id == 0)
        {
            Response::Redirect('roundinfo');
            die;
        }

        // Load view
        $view = new View('view', 'roundinfo');

        // Fetch round
        $query = <<<SQL
SELECT h.*, mi.name AS `name`, s.name AS `server`, s.ip AS `ip`, s.port AS `port`
FROM round_history AS h 
  LEFT JOIN mapinfo AS mi ON h.mapid = mi.id 
  LEFT JOIN server AS s ON h.serverid = s.id
WHERE h.id={$id}
SQL;
        $round = $pdo->query($query)->fetch();
        if (empty($round))
        {
            Response::Redirect('roundinfo');
            die;
        }

        // Assign custom round values and attach to view
        $round['round_start_date'] = date('F jS, Y g:i A T', (int)$round['round_start']);
        $round['round_end_date'] = date('F jS, Y g:i A T', (int)$round['round_end']);
        $round['gamemode'] = Battlefield2::GetGameModeString($round['gamemode']);
        $round['team1name'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team1'])->fetchColumn(0);
        $round['team2name'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team2'])->fetchColumn(0);
        $view->set('round', $round);

        // Set winning team name
        switch ((int)$round['winner'])
        {
            case 1:
                $view->set('winner', $round['team1name']);
                break;
            case 2:
                $view->set('winner', $round['team2name']);
                break;
            default:
                $view->set('winner', "None");
                break;
        }

        // Load players
        $players1 = [];
        $players2 = [];
        $query = <<<SQL
SELECT h.pid, h.team, h.score, h.kills, h.deaths, h.rank, h.cmdscore, h.skillscore, h.teamscore, p.name
FROM player_history AS h 
  LEFT JOIN player AS p ON h.pid = p.id
WHERE roundid={$id}
SQL;
        $result = $pdo->query($query);
        while ($row = $result->fetch())
        {
            $team = (int)$row['team'];
            if ($team == $round['team1'])
                $players1[] = $row;
            else
                $players2[] = $row;
        }

        // Attach players to view
        $view->set('players1', $players1);
        $view->set('players2', $players2);

        // Can we add advanced information?
        parent::loadModel('RoundInfoModel', 'roundinfo');
        $result = $this->RoundInfoModel->addAdvancedRoundInfo($round, $view);
        $view->set('advanced', $result);

        // Alert user if we could not load the snapshot
        if (!$result)
            $view->displayMessage('warning', 'Unable to locate snapshot for this round. Some round details could not be displayed.');

        // Attach awards
        $this->RoundInfoModel->attachAwards($id, $pdo, $view);

        // Attach needed scripts for the form
        $view->attachScript("/ASP/frontend/js/datatables/jquery.dataTables.js");
        $view->attachScript("/ASP/frontend/modules/roundinfo/js/view.js");

        // Attach stylesheets
        $view->attachStylesheet("/ASP/frontend/css/icons/icol32.css");
        $view->attachStylesheet("/ASP/frontend/modules/roundinfo/css/view.css");

        // Send output
        $view->render();
    }

    /**
     * @protocol    POST
     * @request     /ASP/roundinfo/list
     * @output      json
     */
    public function postList()
    {
        try
        {
            $columns = [
                ['db' => 'id', 'dt' => 'id'],
                ['db' => 'round_end', 'dt' => 'round_end',
                    'formatter' => function( $d, $row ) {
                        $i = (int)$d;
                        return date('F jS, Y g:i A T', $i);
                    }
                ],
                ['db' => 'map', 'dt' => 'map'],
                ['db' => 'server', 'dt' => 'server'],
                ['db' => 'winner', 'dt' => 'winner',
                    'formatter' => function( $d, $row ) {

                        $w = $row['team'. $d];
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$w}.png\">";
                    }
                ],
                ['db' => 'team1', 'dt' => 'team1',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$d}.png\">";
                    }
                ],
                ['db' => 'team2', 'dt' => 'team2',
                    'formatter' => function( $d, $row ) {
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$d}.png\">";
                    }
                ],
                ['db' => 'tickets', 'dt' => 'tickets',
                    'formatter' => function( $d, $row ) {
                        return number_format($d);
                    }
                ],
                ['db' => 'players', 'dt' => 'players'],
                ['db' => 'id', 'dt' => 'actions',
                    'formatter' => function( $d, $row ) {
                        $id = $row['id'];
                        return '<span class="btn-group">
                            <a href="/ASP/roundinfo/view/'. $id .'"  rel="tooltip" title="View Round Details" class="btn btn-small">
                                <i class="icon-eye-open"></i>
                            </a>
                        </span>';
                    }
                ],
            ];

            $pdo = Database::GetConnection('stats');
            $data = DataTables::FetchData($_POST, $pdo, 'round_history_view', 'id', $columns);

            echo json_encode($data);
        }
        catch (Exception $e)
        {
            Asp::LogException($e);
            echo json_encode(['error' => $e->getMessage()]);
        }
    }
}