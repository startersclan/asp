<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */

use System\Net\iIPAddress;

/**
 * A Dev testing module
 */
class Devtest extends \System\Controller
{
    /**
     *
     */
    public function index()
    {
        //$pdo = System\Database::GetConnection('stats');
        //$pdo->from('player_army')->select('army_id', 'player_id')->where('army_id')->between(1, 6);

        if (\System\Net\IPAddress::TryParse('192.168.1.1', $addy))
        {
            /* @var $addy iIPAddress */
            echo ($addy->isLoopback()) ? 'true' : 'false';
        }

        //throw new Exception('test');
    }
}