<?php
// Prevent Direct Access
defined('BF2_ADMIN') or die('No Direct Access!');

// Navigation menu builder
function build_navigation()
{
    // Section links
    $task = $GLOBALS['controller'];
    $system = array('config', 'install', 'database');
    $players = array('players');
    $server = array('serverinfo','mapinfo', 'snapshots');
    
    // Prepare for open/closed sections
    $Sys = in_array($task, $system);
    $Plyrs = in_array($task, $players);
    $Svr = in_array($task, $server);
    if(!$Sys && !$Plyrs && !$Svr) $task = 'home';
    
    $html = '
                <li'; if($task == 'home') $html .= ' class="active"'; $html .= '>
                    <a href="/ASP"><i class="icon-home"></i>Dashboard</a>
                </li>
                <li'; if($Sys == true) $html .= ' class="active"'; $html .= '>
                <a href="#"><i class="icon-tools"></i> System</a>';
                if(DB_VER == '0.0.0') 
                { 
                    $html .= '<ul>
                        <li><a href="/ASP/config">Edit Configuration</a></li>
                        <li><a href="/ASP/install">System Installation</a></li>
                    </ul>';
                }
                elseif(DB_VER !== CODE_VER)
                {
                    $html .= '<ul>
                        <li><a href="/ASP/config">Edit Configuration</a></li>
                        <li><a href="/ASP/install">System Installation</a></li>
                        <li><a href="/ASP/database/upgrade">Upgrade Database</a></li>
                        <li><a href="/ASP/database">Backup Database</a></li>
                    </ul>';
                }
                else
                {
                    $html .= '
                        <ul'; if($Sys == false) $html .= ' class="closed"'; $html .= '>
                            <li><a href="/ASP/config">System Configuration</a></li>
                            <li><a href="/ASP/install">System Installation</a></li>
                            <li><a href="/ASP/config/test">System Tests</a></li>
                            <li><a href="/ASP/database/upgrade">Upgrade Database</a></li>
                            <li><a href="/ASP/database/clear">Clear Database</a></li>
                            <li><a href="/ASP/database">Backup Database</a></li>
                            <li><a href="/ASP/database/restore">Restore Database</a></li>
                        </ul>
                    </li>
                    <li'; if($Plyrs == true) $html .= ' class="active"'; $html .= '>
                        <a href="#"><i class="icon-users"></i> Manage Players</a>
                        <ul'; if($Plyrs == false) $html .= ' class="closed"'; $html .= '>
                            <li><a href="/ASP/players">Manage Players</a></li>
                            <li><a href="/ASP/players/merge">Merge Players</a></li>
                            <li><a href="/ASP/players/import">Import Player From EA</a></li>
                            <li><a href="/ASP/players/validateRanks">Validate Ranks</a></li>
                            <li><a href="/ASP/players/checkAwards">Check Awards</a></li>
                        </ul>
                    </li>
                    <li'; if($Svr == true) $html .= ' class="active"'; $html .= '>
                        <a href="#"><i class="icon-business-card"></i> Server Admin</a>
                        <ul'; if($Svr == false) $html .= ' class="closed"'; $html .= '>
                            <li><a href="/ASP/servers">Server Info</a></li>
                            <li><a href="/ASP/mapinfo">Map Info</a></li>
                            <li><a href="/ASP/snapshots">Manage Snapshots</a></li>
                        </ul>
                    </li>';
                }
    $html .= '
                    <li><a href="/ASP/index.php?action=logout"><i class="icon-off"></i> Logout</a></li>'. PHP_EOL;
    echo $html;
}