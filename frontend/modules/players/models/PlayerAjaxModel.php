<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
use System\DataTables;
use System\TimeHelper;

/**
 * Player Ajax Model
 *
 * This model is used to fetch player data for the dataTables javascript library
 *
 * @package Models
 * @subpackage Players
 */
class PlayerAjaxModel
{
    /**
     * @var \System\Database\DbConnection The stats database connection
     */
    protected $pdo;

    /**
     * PlayerAjaxModel constructor.
     */
    public function __construct()
    {
        // Fetch database connection
        $this->pdo = System\Database::GetConnection('stats');
    }

    /**
     * This method retrieves the player list for DataTables
     *
     * @param array $data The GET or POSTS data for DataTables
     *
     * @return array
     */
    public function getPlayerList($data)
    {
        $columns = [
            ['db' => 'id', 'dt' => 'id'],
            ['db' => 'name', 'dt' => 'name'],
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
                    return $d == 0 ? '<span style="color: green; ">No</span>' : '<span style="color: red; ">Yes</span>';
                }
            ],
            ['db' => 'kicked', 'dt' => 'actions',
                'formatter' => function( $d, $row ) {
                    $id = $row['id'];
                    $banned = ($row['permban'] == 1) ? '' : ' style="display: none"';
                    $nbanned = ($row['permban'] == 0) ? '' : ' style="display: none"';

                    return '<span class="btn-group">
                            <a id="go-'. $id .'" href="/ASP/players/view/'. $id .'"  rel="tooltip" title="View Player" class="btn btn-small"><i class="icon-eye-open"></i></i></a>
                            <a id="edit-btn-'. $id .'" href="#"  rel="tooltip" title="Edit Player" class="btn btn-small"><i class="icon-pencil"></i></a>
                            <a id="ban-btn-'. $id .'" href="#" rel="tooltip" title="Ban Player" class="btn btn-small"'. $nbanned .'><i class="icon-flag"></i></a>
                            <a id="unban-btn-'. $id .'" href="#" rel="tooltip" title="Unban Player" class="btn btn-small"'.$banned.'><i class="icon-ok"></i></a>
                            <a id="delete-btn-'. $id .'" href="#" rel="tooltip" title="Delete Player" class="btn btn-small"><i class="icon-trash"></i></a>
                        </span>';
                }
            ],
        ];

        // Use the DataTables library class
        $applyFilter = ((int)$_POST['showBots']) == 0;
        $filter = ($applyFilter) ? "`password` != ''" : '';
        return DataTables::FetchData($data, $this->pdo, 'player', 'id', $columns, $filter);
    }

    /**
     * This method retrieves the player history list for DataTables
     *
     * @param int $id The player id
     * @param array $data The GET or POSTS data for DataTables
     *
     * @return array
     */
    public function fetchPlayerRoundHistory($id, $data)
    {
        $columns = [
            ['db' => 'pid', 'dt' => 'id'],
            ['db' => 'roundid', 'dt' => 'rid'],
            ['db' => 'name', 'dt' => 'server'],
            ['db' => 'mapname', 'dt' => 'map'],
            ['db' => 'score', 'dt' => 'score',
                'formatter' => function( $d, $row ) {
                    return number_format($d);
                }
            ],
            ['db' => 'kills', 'dt' => 'kills',
                'formatter' => function( $d, $row ) {
                    return number_format($d);
                }
            ],
            ['db' => 'deaths', 'dt' => 'deaths',
                'formatter' => function( $d, $row ) {
                    return number_format($d);
                }
            ],
            ['db' => 'time', 'dt' => 'time',
                'formatter' => function( $d, $row ) {
                    $i = (int)$d;
                    return TimeHelper::SecondsToHms($i);
                }
            ],
            ['db' => 'team', 'dt' => 'team',
                'formatter' => function( $d, $row ) {
                    return "<img class='center' src=\"/ASP/frontend/images/armies/small/{$d}.png\">";
                }
            ],
            ['db' => 'timestamp', 'dt' => 'timestamp',
                'formatter' => function( $d, $row ) {
                    $i = (int)$d;
                    return date('F jS, Y g:i A T', $i);
                }
            ],
            ['db' => 'rank', 'dt' => 'actions',
                'formatter' => function( $d, $row ) {
                    $id = (int)$row['pid'];
                    $rid = (int)$row['roundid'];

                    return '<span class="btn-group">
                            <a href="/ASP/players/view/'. $id .'/history/'. $rid .'"  rel="tooltip" title="View Round Details" class="btn btn-small">
                                <i class="icon-eye-open"></i>
                            </a>
                        </span>';
                }
            ],
        ];

        // Fetch data
        $filter = "`pid` = ". $id;
        return DataTables::FetchData($data, $this->pdo, 'player_history_view', 'pid', $columns, $filter);
    }
}