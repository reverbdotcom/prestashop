{**
*
*
* @author Johan Protin
* @copyright Copyright (c) 2017 - Johan Protin
* @license Apache License Version 2.0, January 2004
* @package Reverb
*}
<div class="panel">
    <div class="row">
        <div class="col-md-12 col-xs-12">
            <div class="col-md-2 col-xs-2">
                <span id="orders_sync_action" class="button btn btn-default ">
                  <span>{l s='Launch Orders Sync' mod='reverb'}</span>
                </span><br />
                <span class="pending-order " style="display:none;">{l s='Pending Orders Sync...' mod='reverb'}</span>
            </div>
            <div class="col-md-10 col-xs-10">
                <span>{l s='This manual action starts the cron job.' mod='reverb'}</span>
            </div>
        </div>
    </div>
</div>

{$reverb_orders_status}
<script type="text/javascript">
    $(document).ready(function() {
        $('#orders_sync_action')
            .removeAttr('disabled') // Remove disabled attribute
            .click (function(){
                var select = $(this);
                select.attr('disabled', 'disabled');
                $('.pending-order').show();
                // Ajax call with secure token
                $.get(baseDir + 'modules/reverb/cron.php?code=orders',
                    function (response) {
                        showSuccessMessage("{l s='Orders Synced with success' mod='reverb'}");
                        $('.pending-order').hide();
                        location.reload(true);
                }
                )
                .fail(function() {
                    showErrorMessage("{l s='An error has occured. Please try again' mod='reverb'}");
                    $('.pending-order').hide();
                })
                .always(function() {
                    select.removeAttr('disabled');
                    $('.pending-order').hide();
                });
            });
    });
</script>