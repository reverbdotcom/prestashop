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

{extends file="helpers/list/list_content.tpl"}


{block name="open_td"}
    {if $key == 'reverb_id'}
        <td
            {if isset($params.position)}
                id="td_{if !empty($position_group_identifier)}{$position_group_identifier|escape:'htmlall':'UTF-8'}{else}0{/if}_{$tr.$identifier|escape:'htmlall':'UTF-8'}{if $smarty.capture.tr_count > 1}_{($smarty.capture.tr_count - 1)|intval}{/if}"
            {/if}
            class="reverb-id {strip}{if !$no_link}pointer{/if}
                        {if isset($params.position) && $order_by == 'position'  && $order_way != 'DESC'} dragHandle{/if}
                        {if isset($params.class)} {$params.class|escape:'htmlall':'UTF-8'}{/if}
                        {if isset($params.align)} {$params.align|escape:'htmlall':'UTF-8'}{/if}{/strip}"
            {if (!isset($params.position) && !$no_link && !isset($params.remove_onclick))}
            onclick="document.location = '{$current_index|escape:'html':'UTF-8'}&amp;{$identifier|escape:'html':'UTF-8'}={$tr.$identifier|escape:'html':'UTF-8'}{if $view}&amp;view{else}&amp;update{/if}{$table|escape:'html':'UTF-8'}{if $page > 1}&amp;page={$page|intval}{/if}&amp;token={$token|escape:'html':'UTF-8'}'">
        {else}
        >
        {/if}
    {elseif $key == 'status'}
        <td
            {if isset($params.position)}
                id="td_{if !empty($position_group_identifier)}{$position_group_identifier|escape:'htmlall':'UTF-8'}{else}0{/if}_{$tr.$identifier|escape:'htmlall':'UTF-8'}{if $smarty.capture.tr_count > 1}_{($smarty.capture.tr_count - 1)|intval}{/if}"
            {/if}
            class="reverb-sync-status {strip}{if !$no_link}pointer{/if}
                            {if isset($params.position) && $order_by == 'position'  && $order_way != 'DESC'} dragHandle{/if}
                            {if isset($params.class)} {$params.class|escape:'htmlall':'UTF-8'}{/if}
                            {if isset($params.align)} {$params.align|escape:'htmlall':'UTF-8'}{/if}{/strip}"
            {if (!isset($params.position) && !$no_link && !isset($params.remove_onclick))}
            onclick="document.location = '{$current_index|escape:'html':'UTF-8'}&amp;{$identifier|escape:'html':'UTF-8'}={$tr.$identifier|escape:'html':'UTF-8'}{if $view}&amp;view{else}&amp;update{/if}{$table|escape:'html':'UTF-8'}{if $page > 1}&amp;page={$page|intval}{/if}&amp;token={$token|escape:'html':'UTF-8'}'">
        {else}
        >
        {/if}
    {elseif $key == 'details'}
        <td
            {if isset($params.position)}
                id="td_{if !empty($position_group_identifier)}{$position_group_identifier|escape:'htmlall':'UTF-8'}{else}0{/if}_{$tr.$identifier|escape:'htmlall':'UTF-8'}{if $smarty.capture.tr_count > 1}_{($smarty.capture.tr_count - 1)|intval}{/if}"
            {/if}
            class="reverb-sync-details {strip}{if !$no_link}pointer{/if}
                            {if isset($params.position) && $order_by == 'position'  && $order_way != 'DESC'} dragHandle{/if}
                            {if isset($params.class)} {$params.class|escape:'htmlall':'UTF-8'}{/if}
                            {if isset($params.align)} {$params.align|escape:'htmlall':'UTF-8'}{/if}{/strip}"
            {if (!isset($params.position) && !$no_link && !isset($params.remove_onclick))}
            onclick="document.location = '{$current_index|escape:'html':'UTF-8'}&amp;{$identifier|escape:'html':'UTF-8'}={$tr.$identifier|escape:'html':'UTF-8'}{if $view}&amp;view{else}&amp;update{/if}{$table|escape:'html':'UTF-8'}{if $page > 1}&amp;page={$page|intval}{/if}&amp;token={$token|escape:'html':'UTF-8'}'">
        {else}
        >
        {/if}
    {elseif $key == 'last_sync'}
        <td
            {if isset($params.position)}
                id="td_{if !empty($position_group_identifier)}{$position_group_identifier|escape:'htmlall':'UTF-8'}{else}0{/if}_{$tr.$identifier|escape:'htmlall':'UTF-8'}{if $smarty.capture.tr_count > 1}_{($smarty.capture.tr_count - 1)|intval}{/if}"
            {/if}
            class="reverb-last-sync {strip}{if !$no_link}pointer{/if}
                            {if isset($params.position) && $order_by == 'position'  && $order_way != 'DESC'} dragHandle{/if}
                            {if isset($params.class)} {$params.class|escape:'htmlall':'UTF-8'}{/if}
                            {if isset($params.align)} {$params.align|escape:'htmlall':'UTF-8'}{/if}{/strip}"
            {if (!isset($params.position) && !$no_link && !isset($params.remove_onclick))}
            onclick="document.location = '{$current_index|escape:'html':'UTF-8'}&amp;{$identifier|escape:'html':'UTF-8'}={$tr.$identifier|escape:'html':'UTF-8'}{if $view}&amp;view{else}&amp;update{/if}{$table|escape:'html':'UTF-8'}{if $page > 1}&amp;page={$page|intval}{/if}&amp;token={$token|escape:'html':'UTF-8'}'">
        {else}
        >
        {/if}
    {elseif $key == 'reverb_slug'}
        <td
            {if isset($params.position)}
                id="td_{if !empty($position_group_identifier)}{$position_group_identifier|escape:'htmlall':'UTF-8'}{else}0{/if}_{$tr.$identifier|escape:'htmlall':'UTF-8'}{if $smarty.capture.tr_count > 1}_{($smarty.capture.tr_count - 1)|intval}{/if}"
            {/if}
            style="min-width: 250px" class="reverb-buttons {strip}{if !$no_link}pointer{/if}
                            {if isset($params.position) && $order_by == 'position'  && $order_way != 'DESC'} dragHandle{/if}
                            {if isset($params.class)} {$params.class|escape:'htmlall':'UTF-8'}{/if}
                            {if isset($params.align)} {$params.align|escape:'htmlall':'UTF-8'}{/if}{/strip}"
            {if (!isset($params.position) && !$no_link && !isset($params.remove_onclick))}
            onclick="document.location = '{$current_index|escape:'html':'UTF-8'}&amp;{$identifier|escape:'html':'UTF-8'}={$tr.$identifier|escape:'html':'UTF-8'}{if $view}&amp;view{else}&amp;update{/if}{$table|escape:'html':'UTF-8'}{if $page > 1}&amp;page={$page|intval}{/if}&amp;token={$token|escape:'html':'UTF-8'}'">
        {else}
        >
        {/if}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}

{block name="td_content"}
    {if $key == 'status'}
        <span class="label {if {$tr.$key}}label-{$tr.$key|escape:'htmlall':'UTF-8'}{/if}">
			{$smarty.block.parent}
        </span>
        <div class="icon-status loading-mask" id="icon-{$tr.$identifier|escape:'htmlall':'UTF-8'}">Synchronisation</div>
    {elseif $key == 'reverb_slug'}
        <a href="#" title="" data-id="{$tr.$identifier|escape:'htmlall':'UTF-8'}" class="btn btn-default btn-view-sync" title="Syncronization"><i class="icon-refresh"></i></a>
        {if $ps_product_preview_base_url != ''}
            <a href="{$ps_product_preview_base_url|escape:'htmlall':'UTF-8'}/index.php?id_product={$tr.id_product|escape:'htmlall':'UTF-8'}&id_product_attribute={$tr.id_product_attribute|escape:'htmlall':'UTF-8'}&controller=product" title="Preview" target="_blank" class="btn btn-default"><i class="icon-search-plus"></i></a>
        {/if}
        {if $reverb_product_preview_url != ''}
            <a href="{$reverb_product_preview_url|escape:'htmlall':'UTF-8'}{if $tr.$key}{$tr.$key}{/if}" title="" target="_blank" class="btn btn-default btn-reverb-preview{if !$tr.$key} hide-ps{/if}"><i class="icon-search-plus"> Preview on Reverb</i></a>
        {/if}
    {elseif $key == 'icon'}
    {else}
        {$smarty.block.parent}
    {/if}
{/block}





