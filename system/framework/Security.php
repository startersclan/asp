<?php
/**
 * BF2Statistics ASP Framework
 *
 * Author:       Steven Wilson
 * Copyright:    Copyright (c) 2006-2021, BF2statistics.com
 * License:      GNU GPL v3
 *
 */
namespace System;
use System\Net\IPAddress;

/**
 * Security Class
 *
 * @author      Steven Wilson
 * @package     Asp
 */
class Security
{
    /**
     * @var int Specifies the lifetime of the cookie in seconds which is sent to the browser.
     * @see http://php.net/manual/en/session.configuration.php#ini.session.cookie-lifetime
     */
    const SESSION_COOKIE_LIFETIME = 0;

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
        // Check if the username and password matches the config
        if ($username == Config::Get('admin_user') && $password == Config::Get('admin_pass'))
        {
            // Start Session, set session variables
            $_SESSION['adminAuth'] = sha1(Config::Get('admin_user') . ':' . Config::Get('admin_pass'));
            $_SESSION['adminTime'] = time();

            // Update last login time
            $last = Config::Get('admin_current_login');
            Config::Set('admin_last_login', $last);
            Config::Set('admin_current_login', $_SESSION['adminTime']);
            Config::Save();

            return true;
        }

        return false;
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
        return IPAddress::IsInCIDR($ip, $hosts);
    }
}

// This sends a persistent cookie that lasts an hour.
session_start(['cookie_lifetime' => Security::SESSION_COOKIE_LIFETIME]);