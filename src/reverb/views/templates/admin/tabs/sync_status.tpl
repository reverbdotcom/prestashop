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

<div class="modal fade" id="bulk-sync-result" tabindex="-1" role="dialog" aria-labelledby="bulkSyncResult" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="bulkSyncResult"></h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="text-result"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal">{l s='OK'  mod='reverb'}</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    $(document).ready(function() {
        $('#product_sync_action')
                .removeAttr('disabled') // Remove disabled attribute
                .click (function(){
                    var select = $(this);
                    select.attr('disabled', 'disabled');
                    $('.pending-listing').show();
                    // Ajax call with secure token
                    //$.get('../modules/reverb/cron.php?code=products',
                    $.post('{$ajax_url}&action=ProductCron&ajax=true',
                            function (response) {
                                var result = JSON.parse(response);

                                if (result.success) {
                                    $('#bulk-sync-result').find('#bulkSyncResult').html("{l s='Success'  mod='reverb'}");
                                } else {
                                    $('#bulk-sync-result').find('#bulkSyncResult').html("{l s='Error'  mod='reverb'}");
                                }
                                $('#bulk-sync-result').find('#text-result').html(result.message);
                                $('#bulk-sync-result').modal('show').on('hidden.bs.modal', function () {
                                    window.location = '{$module_url}';
                                });

                                //showSuccessMessage("{l s='Listing Synced with success' mod='reverb'}");
                                $('.pending-listing').hide();
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