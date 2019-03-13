<div class="mws-panel grid_5">
    <div class="mws-panel-header">
        <span><i class="icon-rating3"></i> Current Sergeant Major of the Corps</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            <li>
                <!-- Hidden fields for JavaScript -->
                <span id="playerCurrentId" style="display: none">{player.id}</span>
                <!-- End Hidden fields for JavaScript -->
                <span class="key"><i class="icon-user"></i> Player Name</span>
                <span class="val">
					<span class="text-nowrap">
                        <span id="changeableName">{player.name}</span>
                    </span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-flag"></i> Player Country</span>
                <span class="val">
                    <img id="flag" style="margin: -3px 10px 0 0; height: 16px;" src="/ASP/frontend/images/flags/{player.country}.png"/>
					<span id="fullCountryName" class="text-nowrap">{player.country}</span>
                    (Last IP: {player.lastip})
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-check"></i> Account Status</span>
                <span class="val">
					<span class="text-nowrap">
                        <label id="status" class="label label-{player.badge}">{player.statustext}</label>
                    </span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-calendar"></i> Join Date</span>
                <span class="val">
					<span class="text-nowrap">{player.joined}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-history-2"></i> Last Seen</span>
                <span class="val">
					<span class="text-nowrap">{player.lastonline}</i></span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-watch2"></i> Time Played</span>
                <span class="val">
					<span class="text-nowrap">{player.timeplayed}</i></span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-retweet"></i> Games Played As</span>
                <span class="val">
					<span class="text-nowrap">{player.games}</span>
				</span>
            </li>
        </ul>
    </div>
</div>
<div class="mws-panel grid_3">
    <div class="mws-panel-header">
        <span><i class="icon-book"></i> Sergeant Major of the Corps Summary</span>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            <li>
                <span class="key"><i class="icon-watch2"></i> Last Selection</span>
                <span class="val">
					<span class="text-nowrap">{last}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-hour-glass"></i> Next Selection Due</span>
                <span class="val">
					<span class="text-nowrap" style="color: {next_color}; ">{next}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-fire"></i> Number of Eligible</span>
                <span class="val">
					<span class="text-nowrap">{records}</span>
				</span>
            </li>
        </ul>
    </div>
</div>
<div class="mws-panel grid_8">
    <div class="mws-panel-header">
        <span><i class="icon-users"></i> Eligible Sergeant Majors List</span>
    </div>
    <div class="mws-panel-body no-padding">
        <table class="mws-datatable-fn mws-table">
            <thead>
                <tr>
                    <th style="width: 3%"><span class="loading-cell"></span></th>
                    <th style="width: 5%">Rank</th>
                    <th style="width: 20%">Name</th>
                    <th>Country</th>
                    <th>Global Score</th>
                    <th>Join Date</th>
                    <th>Last Seen</th>
                    <th style="width: 5%">Status</th>
                    <th style="width: 7%">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>