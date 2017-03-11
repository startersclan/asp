<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\Controller;
use System\Database;
use System\DataTables;
use System\Response;
use System\View;

class Roundinfo extends Controller
{
    /**
     * @protocol    ANY
     * @request     /ASP/roundinfo
     * @output      html
     */
    public function index()
    {
        // Require database connection
        $this->requireDatabase();

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
        // Require database connection
        $this->requireDatabase();

        // Ensure correct format for ID
        if ($id == 0 || !is_numeric($id))
        {
            Response::Redirect('roundinfo');
            die;
        }

        // Grab database
        $pdo = Database::GetConnection('stats');
        $id = (int)$id;
        $players1 = [];
        $players2 = [];

        // Load view
        $view = new View('view', 'roundinfo');

        // Fetch round
        $query = <<<SQL
SELECT h.*, mi.name AS `name`, s.name AS `server`
FROM round_history AS h 
JOIN mapinfo AS mi ON h.mapid = mi.id 
JOIN server AS s ON h.serverid = s.id
WHERE h.id={$id}
SQL;
        $round = $pdo->query($query)->fetch();
        if ($round == false)
        {
            Response::Redirect('roundinfo');
            die;
        }

        // Assign custom round values and attach to view
        $round['round_start'] = date('F jS, Y g:i A T', (int)$round['round_start']);
        $round['round_end'] = date('F jS, Y g:i A T', (int)$round['round_end']);
        $round['gamemode'] = $this->getGameModeString($round['gamemode']);
        $round['team1name'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team1'])->fetchColumn(0);
        $round['team2name'] = $pdo->query("SELECT name FROM army WHERE id=". $round['team2'])->fetchColumn(0);
        $view->set('round', $round);

        // Set winning team name
        $winner = ($round['winner'] == 1) ? $round['team1name'] : $round['team2name'];
        $view->set('winner', $winner);

        // Load players
        $query = <<<SQL
SELECT h.pid, h.team, h.score, h.kills, h.deaths, h.rank, h.cmdscore, h.skillscore, h.teamscore, p.name
FROM player_history AS h LEFT JOIN player AS p ON h.pid = p.id
WHERE roundid={$id}
SQL;
        $result = $pdo->query($query);
        while ($row = $result->fetch())
        {
            $team = (int)$row['team'];
            if ($team == 1)
                $players1[] = $row;
            else
                $players2[] = $row;
        }

        // Attach players to view
        $view->set('players1', $players1);
        $view->set('players2', $players2);

        // Load awards
        $query = <<<SQL
SELECT pa.id AS `id`, p.name AS `name`, a.name AS `award`
FROM player_award AS pa 
  LEFT JOIN player AS p ON pa.pid = p.id
  LEFT JOIN award AS a ON pa.id = a.id
WHERE roundid={$id} ORDER BY pa.id
SQL;

        // Attach awards
        $awards = $pdo->query($query)->fetchAll();
        $view->set('awards', $awards);

        // Assign Player positions
        foreach ($awards as $award)
        {
            $id = (int)$award['id'];
            if ($id == 2051907)
                $view->set('1st_place', $award['name']);
            else if ($id == 2051919)
                $view->set('2nd_place', $award['name']);
            else if ($id == 2051902)
                $view->set('3rd_place', $award['name']);
        }

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

    private function getGameModeString($gamemode)
    {
        switch ((int)$gamemode)
        {
            default: return "Unknown";
            case 0: return "Conquest";
            case 1: return "Single Player";
            case 2: return "Coop";
        }
    }
}