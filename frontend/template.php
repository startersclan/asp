<?php
// Prevent Direct Access
defined('BF2_ADMIN') or die('No Direct Access!');

use System\IO\Directory;
use System\IO\Path;
use System\Navigation;
use System\NavigationItem;

// Navigation menu builder
function build_navigation()
{
    // Get  un-authorized snapshots count
    try
    {
        $ss = count( Directory::GetFiles(Path::Combine(SYSTEM_PATH, "snapshots", "unauthorized"), '.*\.json') );
    }
    catch (Exception $e)
    {
        // Ignore
        $ss = 0;
    }

    // Define Section controllers for the opening of drop down menus
    $task = $GLOBALS['controller'];
    $system = array('config', 'install', 'database');
    $players = array('players');
    $server = array('servers','mapinfo', 'snapshots');
    $game = array('gamedata');

    // Create navigation class
    $navigation = new Navigation();

    // Add Dashboard link
    $group = new NavigationItem("Dashboard", "/ASP/", "icon-home", $task == 'home');
    $navigation->append($group);

    // Add System Links
    $group = new NavigationItem("System", "#", "icon-tools", in_array($task, $system));
    $group->append('/ASP/config', 'System Configuration');
    $group->append('/ASP/install', 'System Installation');

    // Adjust navigation items based on a few variables
    if (DB_VER == '0.0.0')
    {
        // No database connection? Fine then... no navigation for you!
        $navigation->append($group);
    }
    else if (DB_VER !== CODE_VER)
    {
        // If mis-matched database version, allow these 2 actions
        $group->append('/ASP/database/upgrade', 'Upgrade Database Schema');
        $group->append('/ASP/database/backup', 'Backup Stats Database');
        $navigation->append($group);
    }
    else
    {
        // Append the rest of system links
        $group->append('/ASP/config/test', 'System Tests');
        $group->append('/ASP/database', 'Database Table Status');
        $group->append('/ASP/database/upgrade', 'Update Database Schema');
        $group->append('/ASP/database/clear', 'Clear Stats Database');
        $group->append('/ASP/database/backup', 'Backup Stats Database');
        $group->append('/ASP/database/restore', 'Restore Database');
        $navigation->append($group);

        // Add Player Links
        $group = new NavigationItem("Manage Players", "#", "icon-users", in_array($task, $players));
        $group->append('/ASP/players', 'Manage Players');
        $group->append('/ASP/players/merge', 'Merge Players');
        $navigation->append($group);

        // Add Server Admin Links
        $snapshots = ($ss > 0) ? '<span class="mws-nav-tooltip" title="Unauthorized Snapshots">'. $ss .'</span>' : '';
        $group = new NavigationItem("Server Admin". $snapshots, "#", "icon-business-card", in_array($task, $server));
        $group->append('/ASP/servers', 'Manage Servers');
        $group->append('/ASP/snapshots', 'Manage Snapshots');
        $group->append('/ASP/mapinfo', 'Map Statistics');
        $navigation->append($group);

        // Add Game Data Links
        $group = new NavigationItem("Game Data", "#", "icon-link", in_array($task, $game));
        $group->append('/ASP/gamedata', 'Manage Stat Keys');
        $group->append('/ASP/gamedata/awards', 'Manage Awards');
        $group->append('/ASP/gamedata/unlocks', 'Manage unlocks');
        $navigation->append($group);
    }

    // Logout
    $group = new NavigationItem("Logout", "/ASP/index.php?action=logout", "icon-off", false);
    $navigation->append($group);

    echo $navigation->toHtml();
}