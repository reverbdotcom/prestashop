<div class="panel">
    <div class="row">
        <div class="col-md-4 col-xs-4">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-danger">Error Logs</a>
                {foreach from=$logs['error'] item=select}
                    <a href="{$module_dir}logs/{$select}" target="_blank" class="list-group-item ">{$select}</a>
                {/foreach}
            </div>
        </div>
        <div class="col-md-4 col-xs-4">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-info">Info Logs</a>
                {foreach from=$logs['info'] item=select}
                    <a href="{$module_dir}logs/{$select}" target="_blank" class="list-group-item ">{$select}</a>
                {/foreach}
            </div>
        </div>
        <div class="col-md-4 col-xs-4">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-info">Cron Logs</a>
                {foreach from=$logs['cron'] item=select}
                    <a href="{$module_dir}logs/{$select}" target="_blank" class="list-group-item ">{$select}</a>
                {/foreach}
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-4 col-xs-4">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-warning">Categories Logs</a>
                {foreach from=$logs['categories'] item=select}
                    <a href="{$module_dir}logs/{$select}" target="_blank" class="list-group-item ">{$select}</a>
                {/foreach}
            </div>
        </div>
        <div class="col-md-4 col-xs-4">
            <div class="list-group">
                <a href="#" class="list-group-item list-group-item-warning">Listings Logs</a>
                {foreach from=$logs['listings'] item=select}
                    <a href="{$module_dir}logs/{$select}" target="_blank" class="list-group-item ">{$select}</a>
                {/foreach}
            </div>
        </div>
    </div>
</div>