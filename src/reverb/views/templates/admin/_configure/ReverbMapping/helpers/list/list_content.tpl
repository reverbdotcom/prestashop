{**
*
*
* @author Johan Protin
* @copyright Copyright (c) 2017 - Johan Protin
* @license Apache License Version 2.0, January 2004
* @package Reverb
*}
{extends file="helpers/list/list_content.tpl"}

{block name="open_td"}
    {$smarty.block.parent}
{/block}

{block name="td_content"}
    {if ($is_logged)}
        {if $key == 'reverb_category'}
            <select class="reverb-category"
                    name="reverb_code"
                    disabled="disabled"
                    data-mapping-id="{$tr.id_mapping|escape:'htmlall':'UTF-8'}"
                    data-ps-category-id="{$tr.ps_category_id|escape:'htmlall':'UTF-8'}"
            >
                <option value="" {if ($tr.reverb_code == '')}selected="selected"{/if}>--</option>
                {foreach from=$reverb_categories item=reverb_category key=reverb_key}
                    <option value="{$reverb_key|escape:'htmlall':'UTF-8'}" {if ($tr.reverb_code == $reverb_key)}selected="selected"{/if}>{$reverb_category|escape:'htmlall':'UTF-8'}</option>
                {/foreach}
            </select>
            <span class="icon icon-ok-circle hide-ps"></span>
            <span class="icon icon-remove-circle hide-ps"></span>
        {else}
            {$smarty.block.parent}
        {/if}
    {/if}
{/block}





