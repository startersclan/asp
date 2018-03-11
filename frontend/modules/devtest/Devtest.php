<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

/**
 * A Dev testing module
 */
class Devtest extends \System\Controller
{
    public function index()
    {
        $pdo = System\Database::GetConnection('stats');
        $pdo->from('player_army')->select('army_id', 'player_id')->where('army_id')->between(1, 6);

        //throw new Exception('test');
    }
}