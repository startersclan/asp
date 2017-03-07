<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2017, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System;

/**
 * Security Class
 *
 * @author      Steven Wilson
 * @package     Asp
 */
class Security
{
    const SESSION_COOKIE_LIFETIME = 3600;

    /**
     * Takes a username and password, and returns attempts to log the
     * user in.
     *
     * @param string $username The username logging in
     * @param string $password The unencrypted password
     *
     * @return bool
     */
    public static function Login($username, $password)
    {
        // Initialize or retrieve the current values for the login variables
        if (!isset($_SESSION['loginAttempts']))
            $_SESSION['loginAttempts'] = 1;

        // If the posted username and/or password doesn't match whats set in config.
        if ($username != Config::Get('admin_user') || $password != Config::Get('admin_pass'))
        {
            // If first login attempt, initiate a login attempt counter
            if ($_SESSION['loginAttempts'] < 3)
            {
                $_SESSION['loginAttempts'] += 1;
                return false;
            }

            // Otherwise, check if attempts are at 3, if so then lock the ASP for now
            else
            {
                echo '<blink>
                    <p style="font-weight:bold;font-size:170px;color:red;font-family:sans-serif;">
                        <center>Max Login Attempts Reached</center>
                    </p>
                    </blink>';
                exit;
            }
        }

        // Else, the username and password matched, login is a success
        else
        {
            // Start Session, set session variables
            $_SESSION['adminAuth'] = sha1(Config::Get('admin_user') . ':' . Config::Get('admin_pass'));
            $_SESSION['adminTime'] = time();
            $_SESSION['loginAttempts'] = 0;
            return true;
        }
    }

    /**
     * Logs the user out and sets all session variables to Guest.
     *
     * @return void
     */
    public static function Logout()
    {
        // If sessions is already killed, just return
        if (!self::IsValidSession()) return;

        // Reset Session Values
        $_SESSION['adminAuth'] = '';
        $_SESSION['adminTime'] = 0;

        // If session exists, un register all variables that exist and destroy session
        session_destroy();
    }

    /**
     * @return bool
     */
    public static function IsValidSession()
    {
        // Session isn't set
        if (!isset($_SESSION['adminAuth']))
            return false;

        // If the password set is wrong
        if ($_SESSION['adminAuth'] != sha1(Config::Get('admin_user') . ':' . Config::Get('admin_pass')))
            return false;

        // If the session time is expired
        if ($_SESSION['adminTime'] < time() - (30 * 60))
            return false;

        // Everything is good, update the session time
        $_SESSION['adminTime'] = time();

        return true;
    }

    /**
     * @param $ip
     *
     * @return bool
     */
    public static function IsAuthorizedIp($ip)
    {
        $hosts = Config::Get('admin_hosts');
        return in_array($ip, $hosts);
    }

    /**
     * @param $ip
     *
     * @return bool
     */
    public static function IsAuthorizedGameServer($ip)
    {
        $hosts = Config::Get('game_hosts');
        return in_array($ip, $hosts);
    }
}

// This sends a persistent cookie that lasts an hour.
session_start(['cookie_lifetime' => Security::SESSION_COOKIE_LIFETIME]);