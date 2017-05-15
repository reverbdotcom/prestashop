{**
 * 2007-2017 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2017 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{extends file="helpers/list/list_footer.tpl"}
{block name="after"}
	<script type="text/javascript">

        function showReverbOrderMessage(id, status, message, tr)
        {
            tr.find('.reverb-order-sync-details').html(message);

            // Update sync status
            var syncLoader = tr.find('.reverb-order-status .icon-status');
            syncLoader.fadeOut(300);

            if (status == 'success') {
                showSuccessMessage(message);
            } else {
                showErrorMessage(message);
            }
        }
        function showReverbOrderInformations(response, tr)
        {
            // Update sync details
            tr.find('.reverb-order-sync-details').html(response.message);

            // Update sync status
            var syncStatus = tr.find('.reverb-order-status span');
            syncStatus.removeClass('label-success').removeClass('label-error').removeClass('label-to_sync')
                .addClass('label-' + response.status)
                .html(response.status);

            syncStatus.fadeIn(2301);

            // Update last sync
            if (response['last-synced'] !== 'undefined') {
                tr.find('.reverb-last-sync').html(response['last-synced']);
            }

            // Update preview button
            if (response['reverb-id'] !== 'undefined') {
                var reverbButton = tr.find('.reverb-buttons .btn-reverb-preview');
                var href = reverbButton.attr('href');
                if (reverbButton.hasClass('hide-ps')) {
                    reverbButton.attr('href', href + response['reverb-id']).removeClass('hide-ps');
                }
            }

        }

		$('a.btn-view-order-sync').on('click',function(e) {
		    var link = $(this);
		    link.attr('disabled', 'disabled');
		    var tr =  $(this).parents('tr');
            var syncStatus = tr.find('.reverb-order-status span');
            syncStatus.hide();

            // Update sync status
            var syncLoader = tr.find('.reverb-order-status .icon-status');
            syncLoader.fadeIn(2000);

		    var id =  $(this).data('id');
		    $('#icon-' + id).fadeIn();
            $.ajax({
                type: 'POST',
                url: "index.php",
                cache: false,
                data: "ajax=1&controller=AdminReverbConfiguration&token={getAdminToken tab='AdminReverbConfiguration'}&action=syncronizeOrder&reverb-id="+ $(this).data('id'),
                dataType: 'json',
                success: function (response) {
                    showReverbOrderMessage(id, response.status, response.message, tr);
                    showReverbOrderInformations(response, tr);
                },
                error: function (response) {
                    console.log(response);
                    showReverbOrderMessage(id, 'error', 'An error occured. Please try again later', tr);
                },
                complete: function () {
                    link.removeAttr('disabled');
                }
            });
            return false;
		});
	</script>
{/block}
