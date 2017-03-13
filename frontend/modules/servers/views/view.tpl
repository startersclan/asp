<div id="jui-global-message" class="alert loading">Fetching Server Status and Information...</div>
<div class="mws-panel grid_2 mws-collapsible">
    <div class="mws-panel-header">
        <span><i class="icon-camera"></i> Server Sponsor Logo</span>
    </div>
    <div class="mws-panel-body no-padding" style="text-align: center">
        <img id="server-image" src="/ASP/frontend/images/maps/default.png">
    </div>
</div>
<div class="mws-panel grid_3 mws-collapsible">
    <div class="mws-panel-header">
        <span><i class="icon-book"></i> Server Details</span>
    </div>
    <div class="mws-panel-toolbar">
        <div class="btn-toolbar">
            <div class="btn-group">
                <a id="auth-server" href="#" class="btn" <?php echo ({server.authorized} == 0) ? '' : ' style="display: none"'; ?>>
                    <i class="icol-accept"></i> Authorize Selected
                </a>
                <a id="unauth-server" href="#" class="btn" <?php echo ({server.authorized} == 1) ? '' : ' style="display: none"'; ?>>
                    <i class="icol-cross-shield-2"></i> Un-Authorize Server
                </a>
                <a id="refresh" href="#" class="btn"><i class="icol-arrow-refresh"></i> Re-Query Server</a>
            </div>
        </div>
    </div>
    <div class="mws-panel-body no-padding">
        <ul class="mws-summary clearfix">
            <li>
                <span class="key"><i class="icon-monitor"></i> Server Name</span>
                <span class="val">
                    <span class="text-nowrap">{server.name} (ID: <span id="serverId">{server.id}</span>)</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-globe"></i> Server Address</span>
                <span class="val">
					<span class="text-nowrap">{server.ip}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-cord"></i> Game Port</span>
                <span class="val">
					<span class="text-nowrap">{server.port}</i></span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-direction"></i> Query Port</span>
                <span class="val">
					<span class="text-nowrap">{server.queryport}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-history-2"></i> Last Snapshot</span>
                <span class="val">
					<span class="text-nowrap">{server.last_update}</span>
				</span>
            </li>
            <li>
                <span class="key"><i class="icon-broadcast"></i> Status</span>
                <span class="val">
					<span class="text-nowrap">
                        <span id="status">Loading...</span>
                    </span>
				</span>
            </li>
        </ul>
    </div>
</div>
<div id="details"></div>
