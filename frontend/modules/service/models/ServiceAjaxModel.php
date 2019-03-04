<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2019, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\DataTables;

class ServiceAjaxModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * ServiceAjaxModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * This method retrieves the played rounds by provider id for DataTables
     *
     * @param array $data The GET or POSTS data for DataTables
     *
     * @return array
     */
    public function getRisingStarList($data)
    {
        $columns = [
            ['db' => 'pos', 'dt' => 'check',
                'formatter' => function( $d, $row ) {
                    return "<input type=\"checkbox\">";
                }
            ],
            ['db' => 'pos', 'dt' => 'position'],
            ['db' => 'joined', 'dt' => 'joined',
                'formatter' => function( $d, $row ) {
                    $i = (int)$d;
                    return date('F jS, Y g:i A T', $i);
                }
            ],
            ['db' => 'player_id', 'dt' => 'id'],
            ['db' => 'name', 'dt' => 'name'],
            ['db' => 'weeklyscore', 'dt' => 'score',
                'formatter' => function( $d, $row ) {
                    $score = round($d / 10000, 2);
                    return "{$score}%";
                }
            ],
            ['db' => 'rank_id', 'dt' => 'rank',
                'formatter' => function( $d, $row ) {
                    return "<img class='center' src=\"/ASP/frontend/images/ranks/rank_{$d}.gif\">";
                }
            ],
            ['db' => 'country', 'dt' => 'country',
                'formatter' => function( $d, $row ) {
                    return "<img class='center flag' src=\"/ASP/frontend/images/flags/{$d}.png\">";
                }
            ],
            ['db' => 'player_id', 'dt' => 'actions',
                'formatter' => function( $d, $row ) {
                    $id = $row['player_id'];
                    return '<span class="btn-group">
                            <a href="/ASP/players/view/'. $id .'"  rel="tooltip" title="View Player Details" class="btn btn-small">
                                <i class="icon-eye-open"></i>
                            </a>
                        </span>';
                }
            ],
        ];

        return DataTables::FetchData($data, $this->pdo, 'rising_star_view', 'pos', $columns);
    }
}