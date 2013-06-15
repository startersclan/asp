<?php
/**
 * BF2Statistics ASP Management Asp
 *
 * @copyright   2013, Plexis Dev Team
 * @license     GNU GPL v3
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
        if(!isset($_POST['loginAttempts']))
            $_POST['loginAttempts'] = 1;

        // If the posted username and/or password doesn't match whats set in config.
        if($username != Config::Get('admin_user') || $password != Config::Get('admin_pass'))
        {
            // If first login attempt, initiate a login attempt counter
            if($_POST['loginAttempts'] == 0)
            {
                $_POST['loginAttempts'] = 1;
                return false;
            }

            // Otherwise, check if attempts are at 3, if so then lock the ASP for now
            else
            {
                if( $_POST['loginAttempts'] >= 3 )
                {
                    echo '<blink>
                        <p style="font-weight:bold;font-size:170px;color:red;font-family:sans-serif;">
                            <center>Max Login Attempts Reached</center>
                        </p>
                        </blink>';
                    exit;
                }
                else
                {
                    $_POST['loginAttempts'] += 1;
                    return false;
                }
            }
        }

        // Else, the username and password matched, login is a success
        else
        {
            // Start Session, set session variables
            $_SESSION['adminAuth'] = sha1(Config::Get('admin_user') .':'. Config::Get('admin_pass'));
            $_SESSION['adminTime'] = time();
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
        if(!self::IsValidSession()) return;

        // Reset Session Values
        $_SESSION['adminAuth'] = '';
        $_SESSION['adminTime'] = '';

        // If session exists, un register all variables that exist and destroy session
        session_destroy();
    }

    /**
     * @return bool
     */
    public static function IsValidSession()
    {
        // Session isn't set
        if(!isset($_SESSION['adminAuth']))
            return false;

        // If the password set is wrong
        if($_SESSION['adminAuth'] != sha1(Config::Get('admin_user').':'.Config::Get('admin_pass')))
            return false;

        // If the session time is expired
        if($_SESSION['adminTime'] < time() - (30*60))
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
        Config::Get('admin_hosts');
        return true;
    }
}

// Start session
session_start();