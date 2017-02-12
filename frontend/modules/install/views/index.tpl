<div class="mws-panel grid_8">
    <div class="mws-panel-header">
        <span><i class="icon-magic"></i> ASP Installation and Setup</span>
    </div>
    <div class="mws-panel-body no-padding">
        <form class="mws-form wzd-validate" action="./install" method="post">
            <input type="hidden" name="action" value="save">
            <!-- Page 1 -->
            <fieldset class="wizard-step mws-form-inline">
                <legend class="wizard-label"><i class="icol-user-business-boss"></i> Admin Settings</legend>
                <div id class="mws-form-row">
                    <label class="mws-form-label">Admin Username <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="text" name="cfg__admin_user" class="required large" value="{admin_user}">
                    </div>
                </div>
                <div class="mws-form-row">
                    <label class="mws-form-label">Admin Password <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="password" name="cfg__admin_pass" class="required large" value="{admin_pass}">
                    </div>
                </div>
                <div class="mws-form-row">
                    <label class="mws-form-label">Admin IP Whitelist <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <textarea name="cfg__admin_hosts" rows="" cols="" class="required large">{ip_list}</textarea>
                    </div>
                </div>
            </fieldset>
            <!-- Page 2 -->
            <fieldset class="wizard-step mws-form-inline">
                <legend class="wizard-label"><i class="icol-database"></i> Database Setup</legend>
                <div id class="mws-form-row">
                    <label class="mws-form-label">Database Host <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="text" name="cfg__db_host" class="required large" value="{db_host}">
                    </div>
                </div>
                <div id class="mws-form-row">
                    <label class="mws-form-label">Database Port <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="text" id="s4" name="cfg__db_port" class="required mws-spinner" value="{db_port}">
                        <label for="s4" class="error" generated="true" style="display:none"></label>
                    </div>
                </div>
                <div id class="mws-form-row">
                    <label class="mws-form-label">Database User <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="text" name="cfg__db_user" class="required large" value="{db_user}">
                    </div>
                </div>
                <div id class="mws-form-row">
                    <label class="mws-form-label">Database Password <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="password" name="cfg__db_pass" class="required large" value="{db_pass}">
                    </div>
                </div>
                <div id class="mws-form-row">
                    <label class="mws-form-label">Database Name <span class="required">*</span></label>
                    <div class="mws-form-item">
                        <input type="text" name="cfg__db_name" class="required large" value="{db_name}">
                    </div>
                </div>
            </fieldset>
        </form>
    </div>
</div>