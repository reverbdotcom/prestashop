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
                <span id="product_sync_action" class="button btn btn-default ">
                  <span>{l s='Launch Listing Sync' mod='reverb'}</span>
                </span><br />
            <span class="pending-listing " style="display:none;">{l s='Pending Listing Sync...' mod='reverb'}</span>
        </div>
        <div class="col-md-10 col-xs-10">
            <span>{l s='This manual action starts the cron job.' mod='reverb'}</span>
        </div>
    </div>
</div>
</div>

{$reverb_sync_status}

<script type="text/javascript">
    $(document).ready(function() {
        $('#product_sync_action')
            .removeAttr('disabled') // Remove disabled attribute
            .click (function(){
                var select = $(this);
                select.attr('disabled', 'disabled');
                $('.pending-listing').show();
                // Ajax call with secure token
                $.get('{$url_site}/modules/reverb/cron.php?code=products',
                    function (response) {
                        showSuccessMessage("{l s='Listing Synced with success' mod='reverb'}");
                        $('.pending-listing').hide();
                        location.reload(true);
                    }
                )
                .fail(function() {
                    showErrorMessage("{l s='An error has occured. Please try again' mod='reverb'}");
                    $('.pending-listing').hide();
                })
                .always(function() {
                    select.removeAttr('disabled');
                    $('.pending-listing').hide();
                });
        });
    });
</script>