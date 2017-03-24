{**
 * 2007-2016 PrestaShop
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
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/osl-3.0.php Open Software License (OSL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 *}
{extends file="helpers/list/list_footer.tpl"}
{block name="after"}
	<script type="text/javascript">
		$('a.btn-view-sync').on('click',function(e){
		    var me =  $(this).data('id');
		    $('#icon-' + me).fadeIn();
            $.ajax({
                type: 'POST',
                url: "index.php",
                cache: false,
                data: "ajax=1&controller=AdminReverbConfiguration&token={getAdminToken tab='AdminReverbConfiguration'}&action=syncronizeProduct&id_product="+ $(this).data('id'),
                success: function (response) {
                    $('#icon-' + me).hide();
                    $('#icon-' + me + '-success').fadeIn(1000);
                    $('#icon-' + me + '-success').fadeOut(1000);
                },
                error: function (response) {
                    $('#icon-' + me).hide();
                    $('#icon-' + me + '-error').fadeIn(1000);
                    $('#icon-' + me + '-error').fadeOut(1000);
                    //showErrorMessage(jQuery.parseJSON(response.responseText).message);
                },
            });
		});
	</script>
{/block}
