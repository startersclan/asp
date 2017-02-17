<div class="mws-panel grid_8">
    <div class="mws-panel-header">
        <span><i class="icon-tools"></i> Edit Config</span>
    </div>
    <div id="mws-jui-dialog">
        <div class="mws-dialog-inner"><p>HI</p></div>
    </div>
    <div class="mws-panel-body no-padding">
        <form id="configForm" class="mws-form" method="POST" action="/ASP/config/save">
            <input type="hidden" name="action" value="save_config" />
            <div id="mws-validate-error" class="mws-form-message error" style="display:none;"></div>
            <div class="mws-form-inline">
                <fieldset>
                    <div class="mws-form-row">
                        <div id="js_message" style="display: none;"></div>
                        <p>
                            This area allows you to alter the configuration of the Battlefield 2 Private Statistics system. This only alters the global settings defined on the "Gamespy"
                            database server. To alter in-game configurations, please edit the "python/bf2/BF2StatisticsConfig.py" file on your game server.
                        </p>
                    </div>
                </fieldset>

                <!-- Stats Processing -->
                <fieldset class="mws-form-inline">
                    <legend>Stats Processing Options</legend>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Ignore AI Players"
                               data-content="Enabling this will force AI player stats to *NOT* be saved at the end of the round.">
                            Ignore AI Players:
                        </label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__stats_ignore_ai">
                                <option value="1" <?php if('{config.stats_ignore_ai}' == '1') echo 'selected="selected"'; ?>>Yes</option>
                                <option value="0" <?php if('{config.stats_ignore_ai}' == '0') echo 'selected="selected"'; ?>>No</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Min. Round Game Time"
                               data-content="Minimum amount of the the game must have played to be accepted for processing (in seconds).">
                            Min. Game Time (Round):
                        </label>
                        <div class="mws-form-item">
                            <div class="small">
                                <input type="text" id="s1" name="cfg__stats_min_game_time" class="required mws-spinner" value="{config.stats_min_game_time}">
                                <label for="s1" class="error" generated="true" style="display:none"></label>
                            </div>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Min. Player Game Time"
                               data-content="Minimum amount of that each player must have played to be accepted for processing (in seconds).">
                            Min. Game Time (Player):
                        </label>
                        <div class="mws-form-item">
                            <div class="small">
                                <input rel="popover" type="text" id="s2" name="cfg__stats_min_player_game_time" class="required mws-spinner" value="{config.stats_min_player_game_time}">
                                <label for="s2" class="error" generated="true" style="display:none"></label>
                            </div>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Min. Players Played"
                               data-content="Minimum number of players who must play in the round for the game to be accepted for processing.">
                            Min. Players:
                        </label>
                        <div class="mws-form-item">
                            <div class="small">
                                <input type="text" id="s3" name="cfg__stats_players_min" class="required mws-spinner" value="{config.stats_players_min}" title="Minimum players in SNAPSHOT before processing? Includes Bots.">
                                <label for="s3" class="error" generated="true" style="display:none"></label>
                            </div>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Max. Players Played"
                               data-content="Maximum number of players who can play in the round for the game to be accepted for processing.">
                            Max. Players:
                        </label>
                        <div class="mws-form-item">
                            <div class="small">
                                <input type="text" id="s4" name="cfg__stats_players_max" class="required mws-spinner" value="{config.stats_players_max}" title="Maximum players in SNAPSHOT before stopping processing (used to stop data hole loops). Includes Bots.">
                                <label for="s4" class="error" generated="true" style="display:none"></label>
                            </div>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Rank Checking"
                               data-content="Enable Rank Checking? Leave off, unless you are having problems with ranks being reset to 0.">
                            Rank Checking:
                        </label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__stats_rank_check"">
                                <option value="1" <?php if('{config.stats_rank_check}' == '1') echo 'selected="selected"'; ?>>Enabled</option>
                                <option value="0" <?php if('{config.stats_rank_check}' == '0') echo 'selected="selected"'; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Rank Tenure:</label>
                        <div class="mws-form-item">
                            <input type="text" class="small required" name="cfg__stats_rank_tenure" value="{config.stats_rank_tenure}" title="Minimum time to hold special ranks (ie, Sergeant Major of the Corps (SMoC) & General (GEN)). in DAYS"/>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Auto Process SMOC:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__stats_process_smoc" title="Enabeling this option will enable SMOC promotion checking everytime a snapshot is pushed to the server. Rank tenure and score will be used to determine if a new
                                Sergeant Major gets promoted to SMOC">
                                <option value="1" <?php if('{config.stats_process_smoc}' == '1') echo 'selected="selected"'; ?>>Enabled</option>
                                <option value="0" <?php if('{config.stats_process_smoc}' == '0') echo 'selected="selected"'; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">GEN Rank Tenure:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__stats_process_gen" title="Enableing this option will only allow 1 general to have the 4 star rank at a time, Much like the SMOC rank. Rank tenure will be used to determine if a new 3
                                star GEN (with the highest global score), gets promoted to 4 stars">
                                <option value="1" <?php if('{config.stats_process_gen}' == '1') echo 'selected="selected"'; ?>>Enabled</option>
                                <option value="0" <?php if('{config.stats_process_gen}' == '0') echo 'selected="selected"'; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Awards Require Round Completion"
                               data-content="If enabled, a player must complete the round to have his awards accepted.">
                            Awards Require Round Completion:
                        </label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__stats_awds_complete" title="">
                                <option value="1" <?php if('{config.stats_awds_complete}' == '1') echo 'selected="selected"'; ?>>Enabled</option>
                                <option value="0" <?php if('{config.stats_awds_complete}' == '0') echo 'selected="selected"'; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Lan IP Override"
                               data-content="Local Players IP 'Over-ride' for Country Code Lookup. Enter a properly formatted non-private IP.">
                            Lan IP Override:
                        </label>
                        <div class="mws-form-item">
                            <input type="text" class="small required" name="cfg__stats_lan_override" value="{config.stats_lan_override}">
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label"
                               rel="popover"
                               data-trigger="hover"
                               data-placement="right"
                               data-original-title="Local Player IP Override"
                               data-content="Individual Players IP 'Override' for Country Code Lookup. Enter one per line.">
                            Local Player IP Override:
                        </label>
                        <div class="mws-form-item">
                            <textarea class="small" name="cfg__stats_local_pids" rows="50%" cols="100%"><?php echo implode("\n", \System\Config::Get('stats_local_pids')); ?></textarea>
                        </div>
                    </div>
                </fieldset>

                <!-- Global Config -->
                <fieldset class="mws-form-inline">
                    <legend>Global Game Server Configuration</legend>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Authorized Server Ip Adresses:</label>
                        <div class="mws-form-item">
                            <textarea class="small" name="cfg__game_hosts" rows="50%" cols="100%"
                                      title="Authorised Game Servers. Enter one IPv4 Address per line (Supports CIDR x.x.x.x/y notation)."><?php echo implode("\n", \System\Config::Get('game_hosts')); ?>
                            </textarea>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Custom MapID:</label>
                        <div class="mws-form-item">
                            <div class="small">
                                <input type="text" id="s6" name="cfg__game_custom_mapid" class="required mws-spinner" value="{config.game_custom_mapid}"
                                       title="Default Custom MapID. This will be used for the first custom map detetced, all others will increment from this value (Default: 700).
                                            NOTE: All Custom MapID's will be assigned based on the HIGHEST existing MapID.
                                            WARNING: Set this ONLY once or you may lose access to you custom map data!">
                                <label for="s6" class="error" generated="true" style="display:none"></label>
                            </div>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Unlocks Option:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__game_unlocks" title="Global Unlocks handling">
                                <option value="0" <?php if('{config.game_unlocks}' == '0') echo 'selected="selected"'; ?>>Earned</option>
                                <option value="1" <?php if('{config.game_unlocks}' == '1') echo 'selected="selected"'; ?>>All Unlocked</option>
                                <option value="-1" <?php if('{config.game_unlocks}' == '-1') echo 'selected="selected"'; ?>>Disabled</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Bonus Unlocks:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__game_unlocks_bonus" title="Allow bonus Unlocks based on Kit Badges?">
                                <option value="0" <?php if('{config.game_unlocks_bonus}' == '0') echo 'selected="selected"'; ?>>&lt;None&gt;</option>
                                <option value="1" <?php if('{config.game_unlocks_bonus}' == '1') echo 'selected="selected"'; ?>>Basic</option>
                                <option value="2" <?php if('{config.game_unlocks_bonus}' == '2') echo 'selected="selected"'; ?>>Veteran</option>
                                <option value="3" <?php if('{config.game_unlocks_bonus}' == '3') echo 'selected="selected"'; ?>>Expert</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Min Rank for unlocks:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__game_unlocks_bonus_min" title="Minimum Rank before allowing bonus unlocks">
                                <option value="0" <?php if('{config.game_unlocks_bonus_min}' == '0') echo 'selected="selected"'; ?>>Private (0)</option>
                                <option value="1" <?php if('{config.game_unlocks_bonus_min}' == '1') echo 'selected="selected"'; ?>>Pvt First Class (1)</option>
                                <option value="2" <?php if('{config.game_unlocks_bonus_min}' == '2') echo 'selected="selected"'; ?>>Lance Corporal (2)</option>
                                <option value="3" <?php if('{config.game_unlocks_bonus_min}' == '3') echo 'selected="selected"'; ?>>Corporal (3)</option>
                                <option value="4" <?php if('{config.game_unlocks_bonus_min}' == '4') echo 'selected="selected"'; ?>>Sergeant (4)</option>
                                <option value="5" <?php if('{config.game_unlocks_bonus_min}' == '5') echo 'selected="selected"'; ?>>Staff Sergeant (5)</option>
                                <option value="6" <?php if('{config.game_unlocks_bonus_min}' == '6') echo 'selected="selected"'; ?>>Gunnery Sergeant (6)</option>
                                <option value="7" <?php if('{config.game_unlocks_bonus_min}' == '7') echo 'selected="selected"'; ?>>&lt;Field Officer&gt; (7-11)</option>
                                <option value="12" <?php if('{config.game_unlocks_bonus_min}' == '12') echo 'selected="selected"'; ?>>&lt;Officer&gt; (12+)</option>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <!-- Admin Config -->
                <fieldset class="mws-form-inline">
                    <legend>Admin Config</legend>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Admin Username:</label>
                        <div class="mws-form-item">
                            <input type="text" class="small required" name="cfg__admin_user" value="{config.admin_user}" title="Username for access to BF2 Stats Admin System. NOTE: You will be forced to re-logon after this has been saved."/>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Admin Password:</label>
                        <div class="mws-form-item">
                            <input type="password" class="small required" name="cfg__admin_pass" value="{config.admin_pass}" title="Password for access to BF2 Stats Admin System. NOTE: You will be forced to re-logon after this has been saved."/>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Auth. Admin Ips:</label>
                        <div class="mws-form-item">
                            <textarea class="small" name="cfg__admin_hosts" rows="50%" cols="100%" title="Authorised IP Addresses for Admin System (Localhost is ALWAYS enabled).
                                Enter one IPv4 Address per line (Supports CIDR x.x.x.x/y notation)."><?php echo implode("\n", \System\Config::Get('admin_hosts')); ?>
                            </textarea>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Stats Logging:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__debug_lvl" title="Stats Debug Logging Level (Includes all message above selected option).">
                                <option value="0" <?php if('{config.debug_lvl}' == '0') echo 'selected="selected"'; ?>>Security (0)</option>
                                <option value="1" <?php if('{config.debug_lvl}' == '1') echo 'selected="selected"'; ?>>Errors (1)</option>
                                <option value="2" <?php if('{config.debug_lvl}' == '2') echo 'selected="selected"'; ?>>Warning (2)</option>
                                <option value="3" <?php if('{config.debug_lvl}' == '3') echo 'selected="selected"'; ?>>Notice (3)</option>
                                <option value="4" <?php if('{config.debug_lvl}' == '4') echo 'selected="selected"'; ?>>Detailed (4)</option>
                            </select>
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">DB Backup Path:</label>
                        <div class="mws-form-item">
                            <input type="text" class="small required" name="cfg__admin_backup_path" value="{config.admin_backup_path}" title="Path to store database backup data (Include trailing '/').
                                This should be an absolute path as it is MySQL using it, not PHP (execpt for restores, then PHP needs it)."
                            />
                        </div>
                    </div>
                    <div class="mws-form-row">
                        <label class="mws-form-label">Ignore AI Players:</label>
                        <div class="mws-form-item">
                            <select class="small" name="cfg__admin_ignore_ai" title="Ignore AI players in player lists?">
                                <option value="1" <?php if('{config.admin_ignore_ai}' == '1') echo 'selected="selected"'; ?>>Yes</option>
                                <option value="0" <?php if('{config.admin_ignore_ai}' == '0') echo 'selected="selected"'; ?>>No</option>
                            </select>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="mws-button-row">
                <input type="submit" value="Submit" class="btn btn-danger">
                <input type="reset" value="Reset" class="btn ">
            </div>
        </form>
    </div>
</div>