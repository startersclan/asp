<?php
// Prevent Direct Access
defined('BF2_ADMIN') or die('No Direct Access!');

// Navigation menu builder
function build_navigation()
{
    // Section links
    $task = 'home';
    $system = array('editconfig','testconfig','installdb','upgradedb','cleardb','backupdb','restoredb');
    $players = array('manageplayers','mergeplayers','importplayer');
    $server = array('serverinfo','mapinfo','validateranks','checkawards','importlogs');
    
    // Prepare for open/closed sections
    $Sys = in_array($task, $system);
    $Plyrs = in_array($task, $players);
    $Svr = in_array($task, $server);
    if(!$Sys && !$Plyrs && !$Svr) $task = 'home';
    
    $html = '
                <li'; if($task == 'home') $html .= ' class="active"'; $html .= '>
                    <a href="./"><i class="icon-home"></i>Dashboard</a>
                </li>
                <li'; if($Sys == true) $html .= ' class="active"'; $html .= '>
                <a href="#"><i class="icon-tools"></i> System</a>';
                if(DB_VER == '0.0.0') 
                { 
                    $html .= '<ul>
                        <li><a href="./config">Edit Configuration</a></li>
                        <li><a href="./install">System Installation</a></li>
                    </ul>';
                }
                elseif(DB_VER !== CODE_VER)
                {
                    $html .= '<ul>
                        <li><a href="./config">Edit Configuration</a></li>
                        <li><a href="./install">System Installation</a></li>
                        <li><a href="./database/upgrade">Upgrade Database</a></li>
                        <li><a href="./database">Backup Database</a></li>
                    </ul>';
                }
                else
                {
                    $html .= '
                        <ul'; if($Sys == false) $html .= ' class="closed"'; $html .= '>
                            <li><a href="./config">Edit Configuration</a></li>
                            <li><a href="./config/test">Test System</a></li>
                            <li><a href="./install">System Installation</a></li>
                            <li><a href="./database/upgrade">Upgrade Database</a></li>
                            <li><a href="./database/clear">Clear Database</a></li>
                            <li><a href="./database">Backup Database</a></li>
                            <li><a href="./database/restore">Restore Database</a></li>
                        </ul>
                    </li>
                    <li'; if($Plyrs == true) $html .= ' class="active"'; $html .= '>
                        <a href="#"><i class="icon-users"></i> Manage Players</a>
                        <ul'; if($Plyrs == false) $html .= ' class="closed"'; $html .= '>
                            <li><a href="./players">Manage Players</a></li>
                            <li><a href="./players/merge">Merge Players</a></li>
                            <li><a href="./players/import">Import Player From EA</a></li>
                        </ul>
                    </li>
                    <li'; if($Svr == true) $html .= ' class="active"'; $html .= '>
                        <a href="#"><i class="icon-graph"></i> Server Admin</a>
                        <ul'; if($Svr == false) $html .= ' class="closed"'; $html .= '>
                            <li><a href="./servers">Server Info</a></li>
                            <li><a href="./mapinfo">Map Info</a></li>
                            <li><a href="./tools/validateRanks">Validate Ranks</a></li>
                            <li><a href="./tools/checkAwards">Check Awards</a></li>
                            <li><a href="./tools/importLogs">Import Logs</a></li>
                        </ul>
                    </li>';
                }
    $html .= '
                    <li><a href="index.php?action=logout"><i class="icon-off"></i> Logout</a></li>'. PHP_EOL;
    echo $html;
}