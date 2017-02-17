<div id="jui-message" style="display: none;"></div>
<div class="mws-panel grid_8">
    <div class="mws-panel-header">
        <span><i class="icon-flag"></i> Maps Played</span>
    </div>
    <div class="mws-panel-body no-padding">
        <table class="mws-datatable-fn mws-table">
            <thead>
            <tr>
                <th style="width: 5%">ID</th>
                <th>Map Name</th>
                <th>Total Score</th>
                <th>Total Time Played</th>
                <th>Rounds Played</th>
                <th>Total Kills</th>
                <th>Total Deaths</th>
                <th>Custom</th>
            </tr>
            </thead>
            <tody>
            {maps}
                <tr id="tr-map-{id}">
                    <td>{id}</td>
                    <td>{name}</td>
                    <td><?php echo number_format({score}); ?></td>
                    <td>{time}</td>
                    <td><?php echo number_format({times}); ?></td>
                    <td><?php echo number_format({kills}); ?></td>
                    <td><?php echo number_format({deaths}); ?></td>
                    <td><?php echo ({custom} == 1) ? 'Yes' : 'No'; ?></td>
                </tr>
            {/maps}
            </tody>
        </table>
    </div>
</div>
