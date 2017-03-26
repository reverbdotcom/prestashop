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

{extends file="helpers/list/list_content.tpl"}


{block name="open_td"}
    {$smarty.block.parent}
{/block}

{block name="td_content"}
    {if $key == 'status'}
        <span class="label color_field" style="{if $tr.$key == 'success'}background-color:#95CC6B;{elseif $tr.$key == 'error'}background-color:#8f0621;{/if}color:white">
			{$smarty.block.parent}
        </span>
    {elseif $key == 'reverb_slug'}
        <a href="#" title="" data-id="{$tr.$identifier}" class="btn btn-default btn-view-sync"><i class="icon-refresh"></i> Syncronization</a>
        {if $tr.$key}
            <a href="{$ps_product_preview_base_url}/index.php?id_product={$tr.id_product}&id_product_attribute=0&controller=product" title="" target="_blank" class="btn btn-default"><i class="icon-search-plus"> Preview</i></a>
            <a href="{$reverb_product_preview_url}{$tr.$key}" title="" target="_blank" class="btn btn-default"><i class="icon-search-plus"> Preview on Reverb</i></a>
        {/if}
    {elseif $key == 'icon'}
        <div class="icon-status loading-mask" id="icon-{$tr.$identifier}">Synchronisation</div>
        <div class="icon-status success" id="icon-{$tr.$identifier}-success">Synchronisation success</div>
        <div class="icon-status error" id="icon-{$tr.$identifier}-error">Synchronisation error</div>
    {else}
        {$smarty.block.parent}
    {/if}
{/block}





