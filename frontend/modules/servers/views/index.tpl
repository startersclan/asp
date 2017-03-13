<div id="jui-global-message" class="alert" style="display: none;"></div>
<div class="mws-panel grid_8">
    <div class="mws-panel-header">
        <span><i class="icon-table"></i> Registered Servers</span>
    </div>
    <div class="mws-panel-toolbar">
        <div class="btn-toolbar">
            <div class="btn-group">
                <a id="add-new" href="#" class="btn"><i class="icol-add"></i> Add New Server</a>
                <a id="delete-selected" href="#" class="btn"><i class="icol-cross"></i> Delete Selected</a>
                <a id="auth-selected" href="#" class="btn"><i class="icol-accept"></i> Authorize Selected</a>
                <a id="unauth-selected" href="#" class="btn"><i class="icol-cross-shield-2"></i> Un-Authorize Selected</a>
                <a id="refresh" href="#" class="btn"><i class="icol-arrow-refresh"></i> Refresh</a>
            </div>
        </div>
    </div>
    <div class="mws-panel-body no-padding">
        <table class="mws-datatable-fn mws-table">
            <thead>
            <tr>
                <th class="checkbox-column">
                    <input id="select-all" type="checkbox">
                </th>
                <th style="width: 5%">SID</th>
                <th>Server Name</th>
                <th style="width: 15%">Server Token</th>
                <th style="width: 15%">Server IP Address</th>
                <th style="width: 7%">Server Port</th>
                <th style="width: 7%">Query Port</th>
                <th style="width: 6%">Snapshots</th>
                <th style="width: 6%">Authorized</th>
                <th style="width: 7%">Actions</th>
            </tr>
            </thead>
            <tbody>
            {servers}
            <tr id="tr-server-{id}">
                <td class="checkbox-column">
                    <input id="server-{id}" type="checkbox">
                </td>
                <td>{id}</td>
                <td>{name}</td>
                <td>{prefix}</td>
                <td>{ip}</td>
                <td>{port}</td>
                <td>{queryport}</td>
                <td>{snapshots}</td>
                <td><?php echo ({authorized} == 1) ? 'Yes' : 'No'; ?></td>
                <td>
                    <span class="btn-group">
                        <a id="go-btn" href="/ASP/servers/view/{id}" rel="tooltip" title="View Server" class="btn btn-small"><i class="icon-eye-open"></i></a>
                        <a id="edit-btn-{id}" href="#"  rel="tooltip" title="Edit Server" class="btn btn-small"><i class="icon-pencil"></i></a>
                        <a id="auth-btn-{id}" href="#" rel="tooltip" title="Authorize Server" class="btn btn-small"
                        <?php echo ({authorized} == 0) ? '' : ' style="display: none"'; ?>><i class="icon-ok"></i></a>
                        <a id="unauth-btn-{id}" href="#" rel="tooltip" title="Un-Authorize Server" class="btn btn-small"
                        <?php echo ({authorized} == 1) ? '' : ' style="display: none"'; ?>><i class="icon-unlink"></i></a>
                        <a id="delete-btn-{id}" href="#" rel="tooltip" title="Delete Server" class="btn btn-small"
                        <?php echo ({snapshots} == 0) ? '' : ' disabled="disabled"'; ?>><i class="icon-trash"></i></a>
                    </span>
                </td>
            </tr>
            {/servers}
            </tbody>
        </table>

        <!-- Add New Server Ajax Model -->
        <div id="add-server-form">
            <form id="mws-validate" class="mws-form" method="post" action="/ASP/servers/add">
                <input id="post-action" type="hidden" name="action" value="add">
                <input id="server-id" type="hidden" name="serverId" value="0">
                <div id="mws-validate-error" class="mws-form-message error" style="display:none;"></div>
                <div id="jui-message" class="alert" style="display: none;"></div>
                <div class="mws-form-inline">
                    <div class="mws-form-row">
                        <label class="mws-form-label">Server Name</label>
                        <div class="mws-form-item">
                            <input type="text" name="serverName" class="required large">
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Server Prefix</label>
                        <div class="mws-form-item">
                            <input type="text" name="serverPrefix" class="required large">
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Server Ip Address</label>
                        <div class="mws-form-item">
                            <input type="text" name="serverIp" class="required large">
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Server Port</label>
                        <div class="mws-form-item">
                            <input type="text" id="s1" name="serverPort" class="required mws-spinner" value="16567">
                            <label for="s1" class="error" generated="true" style="display:none"></label>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Server Query Port</label>
                        <div class="mws-form-item">
                            <input type="text" id="s2" name="serverQueryPort" class="required mws-spinner" value="29900">
                            <label for="s2" class="error" generated="true" style="display:none"></label>
                        </div>
                    </div>
                </div>
            </form>
        </div>

        <!-- Delete Server Confirmation Model -->
        <div id="mws-jui-dialog">
            <div class="mws-dialog-inner"></div>
        </div>
    </div>
</div>