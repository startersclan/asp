<div class="mws-panel grid_2">
    <div class="mws-panel-header">
        <span><i class="icon-map-marker"></i> {round.name}</span>
    </div>
    <div class="mws-panel-body no-padding" style="text-align: center">
        <img src="/ASP/frontend/images/maps/<?php echo strtolower('{round.name}'); ?>.png">
    </div>
</div>
<div class="mws-panel grid_3">
    <div class="mws-panel-header">
        <span><i class="icon-book"></i> Round Information</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            <li>
                <span class="key"><i class="icon-monitor"></i> Server Name</span>
                <span class="val">
					<span class="text-nowrap">
                        <a href="/ASP/servers/view/{round.serverid}">{round.server}</a>
                    </span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-globe"></i>Map Name</span>
                <span class="val">
					<span class="text-nowrap">{round.name}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-folder-closed"></i> Game Mod</span>
                <span class="val">
					<span class="text-nowrap">{round.mod}</i></span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-direction"></i> Game Mode</span>
                <span class="val">
					<span class="text-nowrap">{round.gamemode}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-history-2"></i> Round Start</span>
                <span class="val">
					<span class="text-nowrap">{round.round_start_date}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-retweet"></i> Round End</span>
                <span class="val">
					<span class="text-nowrap">{round.round_end_date}</span>
				</span>
            </li>
        </ul>
    </div>
</div>
<div class="mws-panel grid_3">
    <div class="mws-panel-header">
        <span><i class="icon-flag"></i> Round Summary</span>
    </div>
    <div class="mws-panel-body">
        <div class="table-container">
            <div class="left">
                <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{round.team1}.png"/>
                <br />
                {round.team1name}
            </div>
            <div class="center">
                <span style="font-weight: 900; font-size: 32px;">{round.tickets1} : {round.tickets2}</span>
            </div>
            <div class="right">
                <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{round.team2}.png"/>
                <br />
                {round.team2name}
            </div>
        </div>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/flag_blue.png" style="height: 24px"/> Winning Team
                </span>
                <span class="val">
					<span class="text-nowrap">{round.winningTeamName}</span>
				</span>
            </li>
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/award_star_gold.png" style="height: 24px"/> 1st Place
                </span>
                <span class="val">
					<span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{first_place.rank}.gif"/>
                        {first_place.name}
                        <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{first_place.team}.png"/>
                    </span>
				</span>
            </li>
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/award_star_silver.png" style="height: 24px"/> 2nd Place
                </span>
                <span class="val">
					<span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{second_place.rank}.gif"/>
                        {second_place.name}
                        <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{second_place.team}.png"/>
                    </span>
				</span>
            </li>
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/award_star_bronze.png" style="height: 24px"/> 3rd Place
                </span>
                <span class="val">
					<span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{third_place.rank}.gif"/>
                        {third_place.name}
                        <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{third_place.team}.png"/>
                    </span>
				</span>
            </li>
        </ul>
    </div>
</div>
<div class="mws-panel grid_4 mws-collapsible clear">
    <div class="mws-panel-header">
        <span>
            <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{round.team1}.png"/>
            {round.team1name}
        </span>
    </div>
    <div class="mws-panel-body no-padding">
        <table id="team2" class="mws-table">
            <thead>
            <tr>
                <th style="width: 7%">Rank</th>
                <th>Name</th>
                <th style="width: 10%">Score</th>
                <th style="width: 10%">SS</th>
                <th style="width: 10%">TS</th>
                <th style="width: 10%">CS</th>
                <th style="width: 10%">K</th>
                <th style="width: 10%">D</th>
            </tr>
            </thead>
            <tbody>
            {players1}
                <tr>
                    <td><img src="/ASP/frontend/images/ranks/rank_{rank}.gif"/></td>
                    <?php if ({pid} == 0): ?>
                    <td>{name}</td>
                    <?php else: ?>
                    <td>
                        <a href="/ASP/players/history/{pid}/{round.id}"
                           rel="tooltip"
                           data-placement="right"
                           title="Click to view Player Round Details">
                            {name}
                        </a>
                    </td>
                    <?php endif ?>
                    <td>{score}</td>
                    <td>{skillscore}</td>
                    <td>{teamscore}</td>
                    <td>{cmdscore}</td>
                    <td>{kills}</td>
                    <td>{deaths}</td>
                </tr>
            {/players1}
            </tbody>
        </table>
    </div>
</div>
<div class="mws-panel grid_4 mws-collapsible">
    <div class="mws-panel-header">
        <span>
            <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{round.team2}.png"/>
            {round.team2name}
        </span>
    </div>
    <div class="mws-panel-body no-padding">
        <table id="team1" class="mws-table">
            <thead>
            <tr>
                <th style="width: 7%">Rank</th>
                <th>Name</th>
                <th style="width: 10%">Score</th>
                <th style="width: 10%">SS</th>
                <th style="width: 10%">TS</th>
                <th style="width: 10%">CS</th>
                <th style="width: 10%">K</th>
                <th style="width: 10%">D</th>
            </tr>
            </thead>
            <tbody>
            {players2}
                <tr>
                    <td><img src="/ASP/frontend/images/ranks/rank_{rank}.gif"/></td>
                    <?php if ({pid} == 0): ?>
                    <td>{name}</td>
                    <?php else: ?>
                    <td>
                        <a href="/ASP/players/history/{pid}/{round.id}"
                           rel="tooltip"
                           data-placement="right"
                           title="Click to view Player Round Details">
                            {name}
                        </a>
                    </td>
                    <?php endif ?>
                    <td>{score}</td>
                    <td>{skillscore}</td>
                    <td>{teamscore}</td>
                    <td>{cmdscore}</td>
                    <td>{kills}</td>
                    <td>{deaths}</td>
                </tr>
            {/players2}
            </tbody>
        </table>
    </div>
</div>
<?php if ($advanced): ?>
<div class="mws-panel grid_4 mws-collapsible" style="min-width: 330px">
    <div class="mws-panel-header">
        <span><i class="icon-pacman"></i> Top Player Scores</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            {topSkillPlayers}
            <li>
                <span class="key">{category}</span>
                <span class="val">
					<span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{rank}.gif"/>
                        {name}
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/armies/small/{team}.png"/>
                        ({value})
                    </span>
				</span>
            </li>
            {/topSkillPlayers}
        </ul>
    </div>
</div>
<div class="mws-panel grid_4 mws-collapsible">
    <div class="mws-panel-header">
        <span><i class="icon-accessibility-2"></i> Top Kit Players</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            {topKitPlayers}
            <li>
                <span class="key">{iteration.key}</span>
                <span class="val">
                    <span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{rank}.gif"/>
                        {name}
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/armies/small/{team}.png"/>
                        - [{time_string}] {kills} kills, {deaths} deaths
                    </span>
                </span>
            </li>
            {/topKitPlayers}
        </ul>
    </div>
</div>
<div class="mws-panel grid_4 mws-collapsible">
    <div class="mws-panel-header">
        <span><i class="icon-chopper"></i> Top Vehicle Players</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            {topVehiclePlayers}
            <li>
                <span class="key">{iteration.key}</span>
                <span class="val">
                    <span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{rank}.gif"/>
                        {name}
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/armies/small/{team}.png"/>
                        - [{time_string}] {kills} kills, {deaths} deaths
                    </span>
                </span>
            </li>
            {/topVehiclePlayers}
        </ul>
    </div>
</div>
<div class="mws-panel grid_4 mws-collapsible">
    <div class="mws-panel-header">
        <span><i class="icon-hand-down"></i> Game Commanders</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            {commanders}
            <li>
                <span class="key" style="width: 250px">
                    <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{team}.png"/>
                    <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{rank}.gif"/>
                    {name}
                </span>
                <span class="val">
                    <span class="text-nowrap">{score} points [{time_string}]</span>
                </span>
            </li>
            {/commanders}
        </ul>
    </div>
</div>
<?php endif ?>
<div class="mws-panel grid_4 mws-collapsible" style="min-width: 330px">
    <div class="mws-panel-header">
        <span><i class="icon-trophy"></i> Player Earned Awards</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            {awards}
            <li>
                <span class="key" style="width: 250px">
                    <?php if ({type} == 0): ?>
                    <img style="margin: -2px 0 0 0; max-height: 24px;" src="/ASP/frontend/images/icons/ribbon.png"/>
                    <?php elseif ({type} == 1): ?>
                    <img style="margin: -2px 0 0 0; max-height: 24px;" src="/ASP/frontend/images/icons/badge_{level}.png"/>
                    <?php elseif ({type} == 2): ?>
                    <img style="margin: -2px 0 0 0; max-height: 24px;" src="/ASP/frontend/images/icons/medal.png"/>
                    <?php elseif ({type} == 3): ?>
                    <img style="margin: -2px 0 0 0; max-height: 24px;" src="/ASP/frontend/images/icons/award_star_gold.png"/>
                    <?php elseif ({type} == 4): ?>
                    <img style="margin: -2px 0 0 0; max-height: 24px;" src="/ASP/frontend/images/icons/award_star_silver.png"/>
                    <?php elseif ({type} == 5): ?>
                    <img style="margin: -2px 0 0 0; max-height: 24px;" src="/ASP/frontend/images/icons/award_star_bronze.png"/>
                    <?php endif ?>
                    {name}
                </span>
                <span class="val">
                    <span class="text-nowrap">
                        <img style="margin: -3px 0 0 0" src="/ASP/frontend/images/ranks/rank_{rank}.gif"/>
                        {player_name}
                        <img style="margin: -3px 10px 0 5px" src="/ASP/frontend/images/armies/small/{team}.png"/>
                    </span>
                </span>
            </li>
            {/awards}
        </ul>
    </div>
</div>