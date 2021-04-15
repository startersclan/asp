<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2021, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\DataTables;

/**
 * Round Info Ajax Model
 *
 * This model is used to fetch round data for the dataTables javascript library
 *
 * @package Models
 * @subpackage RoundInfo
 */
class RoundInfoAjaxModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * RoundInfoAjaxModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * This method retrieves the played rounds list for DataTables
     *
     * @param array $data The GET or POSTS data for DataTables
     *
     * @return array
     */
    public function getRoundList($data)
    {
        $columns = [
            ['db' => 'id', 'dt' => 'check',
                'formatter' => function( $d, $row ) {
                    return "<input type=\"checkbox\">";
                }
            ],
            ['db' => 'id', 'dt' => 'id'],
            ['db' => 'round_end', 'dt' => 'round_end',
                'formatter' => function( $d, $row ) {
                    $i = (int)$d;
                    return date('F jS, Y g:i A T', $i);
                }
            ],
            ['db' => 'map', 'dt' => 'map'],
            ['db' => 'server_name', 'dt' => 'server'],
            ['db' => 'winner', 'dt' => 'winner',
                'formatter' => function( $d, $row ) {
                    $id = (int)$d;
                    if ($id == 0)
                        return "<img class='center' src=\"/ASP/frontend/images/armies/small/-1.png\">";

                    $w = $row["team{$id}"];
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

        return DataTables::FetchData($data, $this->pdo, 'round_history_view', 'id', $columns);
    }
}