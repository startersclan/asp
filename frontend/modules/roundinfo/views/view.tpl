<div class="mws-panel grid_2">
    <div class="mws-panel-header">
        <span><i class="icon-map-marker"></i> {round.name}</span>
    </div>
    <div class="mws-panel-body" style="text-align: center">
        <img src="/ASP/frontend/images/maps/<?php echo strtolower('{round.name}'); ?>">
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
					<span class="text-nowrap">{round.server}</span>
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
					<span class="text-nowrap">{round.round_start}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-retweet"></i> Round End</span>
                <span class="val">
					<span class="text-nowrap">{round.round_end}</span>
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
					<span class="text-nowrap">{winner}</span>
				</span>
            </li>
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/award_star_gold.png" style="height: 24px"/> 1st Place
                </span>
                <span class="val">
					<span class="text-nowrap">{1st_place}</span>
				</span>
            </li>
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/award_star_silver.png" style="height: 24px"/> 2nd Place
                </span>
                <span class="val">
					<span class="text-nowrap">{2nd_place}</span>
				</span>
            </li>
            <li>
                <span class="key">
                    <img src="/ASP/frontend/images/icons/award_star_bronze.png" style="height: 24px"/> 3rd Place
                </span>
                <span class="val">
					<span class="text-nowrap">{3rd_place}</span>
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
                    <td>{name}</td>
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
                    <td>{name}</td>
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