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

        $indicesServer = array(
            'PHP_SELF',
            'argv',
            'argc',
            'GATEWAY_INTERFACE',
            'SERVER_ADDR',
            'SERVER_NAME',
            'SERVER_SOFTWARE',
            'SERVER_PROTOCOL',
            'REQUEST_METHOD',
            'REQUEST_TIME',
            'REQUEST_TIME_FLOAT',
            'QUERY_STRING',
            'DOCUMENT_ROOT',
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_CHARSET',
            'HTTP_ACCEPT_ENCODING',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_CONNECTION',
            'HTTP_HOST',
            'HTTP_REFERER',
            'HTTP_USER_AGENT',
            'HTTPS',
            'REMOTE_ADDR',
            'REMOTE_HOST',
            'REMOTE_PORT',
            'REMOTE_USER',
            'REDIRECT_REMOTE_USER',
            'SCRIPT_FILENAME',
            'SERVER_ADMIN',
            'SERVER_PORT',
            'SERVER_SIGNATURE',
            'PATH_TRANSLATED',
            'SCRIPT_NAME',
            'REQUEST_URI',
            'PHP_AUTH_DIGEST',
            'PHP_AUTH_USER',
            'PHP_AUTH_PW',
            'AUTH_TYPE',
            'PATH_INFO',
            'ORIG_PATH_INFO'
        );

        echo '<table cellpadding="10">' ;
        foreach ($indicesServer as $arg) {
            if (isset($_SERVER[$arg])) {
                echo '<tr><td>'.$arg.'</td><td>' . $_SERVER[$arg] . '</td></tr>' ;
            }
            else {
                echo '<tr><td>'.$arg.'</td><td>-</td></tr>' ;
            }
        }
        echo '</table>' ;

        //throw new Exception('test');
    }
}