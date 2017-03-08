<div id="jui-message" style="display: none;"></div>
<div class="mws-panel grid_8">
    <div class="mws-panel-header">
        <span><i class="icon-database"></i> Database Table Status</span>
    </div>
    <div class="mws-panel-body no-padding">
        <table class="mws-datatable-fn mws-table">
            <thead>
            <tr>
                <th>Table Name</th>
                <th>Size</th>
                <th>Row Count</th>
                <th>Avg Row Size</th>
                <th>Engine</th>
            </tr>
            </thead>
            <tody>
                {tables}
                <tr>
                    <td>{name}</td>
                    <td>{size}</td>
                    <td>{rows}</td>
                    <td>{avg_row_length}</td>
                    <td>{engine}</td>
                </tr>
                {/tables}
            </tody>
        </table>
    </div>
</div>