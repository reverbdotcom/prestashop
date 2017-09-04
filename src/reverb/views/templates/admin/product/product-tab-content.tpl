{**
*
*
* @author Johan Protin
* @copyright Copyright (c) 2017 - Johan Protin
* @license Apache License Version 2.0, January 2004
* @package Reverb
*}
<div class="panel" style="padding:30px">
    <div class="row moduleconfig-header">
        <div class="col-lg-3 text-right">
            <h1 class="site-header__logo"></h1>
        </div>
        <div class="col-lg-7 text-left">
            <h2>{l s='Welcome to the world’s most popular music gear website.' mod='reverb'}</h2>
            <h4>{l s='Reverb.com is the online marketplace for musicians to buy, sell and learn about new, used, vintage and handmade music gear. When Reverb launched in 2013, it was founded on the principle that buying and selling musical instruments should be easy and affordable. Since then we’ve become a thriving marketplace that connects millions of people around the world to the gear and the inspiration needed to make music.' mod='reverb'}</h4>
            <h4>{l s='The Reverb Marketplace is made up of hundreds of thousands of buyers and sellers – from beginner musicians to collectors, mom-and-pop shops to large retailers, and popular manufacturers to boutique builders and luthiers. You might even run into some of your favorite rock stars buying and selling on Reverb!' mod='reverb'}</h4>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='When you activate the syncronization with reverb the product is sent on Reverb\'smarketplace' mod='reverb'}">{l s='Active synchronization' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="reverb_enabled" id="reverb_enabled_sync_on" value="1"
                           {if ($reverb_enabled)}checked="checked"{/if}>
                    <label for="reverb_enabled_sync_on">Yes</label>

                    <input type="radio" name="reverb_enabled" id="reverb_enabled_sync_off" value="0"
                           {if !($reverb_enabled)}checked="checked"{/if}>
                    <label for="reverb_enabled_sync_off">No</label>
                    <a class="slide-button btn"></a>
                </span>
                <div class="alert alert-info" role="alert">
                    <p class="alert-text">
                        {l s='When you activate the syncronization with reverb the product is sent to  Reverb\'s marketplace.' mod='reverb'}
                        <br/>
                        {l s='Then you can see the status of the synchronization on the page of Reverb Module.' mod='reverb'}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Condition' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch">
                   <select class="form-control reverb-condition" name="reverb_condition">
                       <option value="" {if ($reverb_condition == '')}selected="selected"{/if}>--</option>
                       {foreach from=$reverb_list_conditions item=condition key=reverb_key}
                           {$reverb_key|escape:'htmlall':'UTF-8'}
                           <option value="{$reverb_key|escape:'htmlall':'UTF-8'}"
                                   {if ($reverb_condition == $reverb_key)}selected="selected"{/if}>{$condition|escape:'htmlall':'UTF-8'}</option>
                       {/foreach}
                    </select>
                </span>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Model' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <input type="text" name="reverb_model" class="form-control reverb-model"
                       value="{$reverb_model|escape:'htmlall':'UTF-8'}"/>
                <div class="alert alert-info" role="alert">
                    <i class="material-icons">help</i>
                    <p class="alert-text">
                        {l s='Please check the model field and ensure it is filled out' mod='reverb'}
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Finish' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <input type="text" name="reverb_finish" class="form-control reverb-finish"
                       value="{$reverb_finish|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Year' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <input type="text" name="reverb_year" class="form-control reverb-year"
                       value="{$reverb_year|escape:'htmlall':'UTF-8'}"/>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Make an Offer' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="offers_enabled" id="reverb_offers_enabled_on" value="1"
                           {if ($reverb_offers_enabled)}checked="checked"{/if}>
                    <label for="reverb_offers_enabled_on">Yes</label>

                    <input type="radio" name="offers_enabled" id="reverb_offers_enabled_off" value="0"
                           {if !($reverb_offers_enabled)}checked="checked"{/if}>
                    <label for="reverb_offers_enabled_off">No</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Country origin' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch">
                    <select name="reverb_country" id="country_select" class="form-control input-large" >
                        <option value="">--</option>
                        {foreach from=$reverb_list_country item='country'}
                            <option value="{$country.iso_code|escape:'htmlall':'UTF-8'}" {if ($country.iso_code == $reverb_country)}selected="selected"{/if}>&nbsp;{$country.name|escape:'htmlall':'UTF-8'}</option>
                        {/foreach}
                    </select>
                </span>
            </div>
        </div>
    </div>
    <div class="row form-group">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Apply shipping profile' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch">
                    <select name="reverb_shipping" id="shipping_select" class="form-control input-large" >
                        <option value="reverb" {if ($reverb_shipping_profile != '')}selected="selected"{/if}>
                            {l s='Reverb shipping profile'  mod='reverb'}
                        </option>
                        <option value="custom" {if ($reverb_shipping_methods|count)}selected="selected"{/if}>
                            {l s='Any : use custom shipping' mod='reverb'}
                        </option>
                    </select>
                </span>
            </div>
        </div>
    </div>
    <div class="row form-group" id="shipping-profile">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">{l s='Reverb shipping profile ID' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                {if ($reverb_shipping_profiles|count)}
                    <select class="form-control reverb-shipping-profiles"  name="reverb_shipping_profile" >
                        <option value="">{l s='Select a shipping profile'  mod='reverb'}</option>
                        {foreach from=$reverb_shipping_profiles item='profile'}
                            <option value="{$profile['id']|escape:'htmlall':'UTF-8'}" {if ($profile['id'] == $reverb_shipping_profile)}selected="selected"{/if}>
                                {$profile['name']|escape:'htmlall':'UTF-8'}
                            </option>
                        {/foreach}
                    </select>
                {else}
                    <p>{l s='You have no shipping profiles.' mod='reverb'}</p>
                {/if}
                <div class="alert alert-info" role="alert">
                    <p class="alert-text">
                        <a target="_blank" href="{$reverb_url|escape:'htmlall':'UTF-8'}/my/selling/shipping_rates">
                            {l s='See your Reverb shipping profile' mod='reverb'}
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="row form-group" id="shipping-methods">
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">
                    {l s='Local Pickup' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <span class="switch prestashop-switch fixed-width-lg">
                    <input type="radio" name="reverb_shipping_local" id="reverb_shipping_local_on" value="1"
                           {if ($reverb_shipping_local)}checked="checked"{/if}>
                    <label for="reverb_shipping_local_on">Yes</label>

                    <input type="radio" name="reverb_shipping_local" id="reverb_shipping_local_off" value="0"
                           {if !($reverb_shipping_local)}checked="checked"{/if}>
                    <label for="reverb_shipping_local_off">No</label>
                    <a class="slide-button btn"></a>
                </span>
            </div>
        </div>
        <div class="col-md-12">
            <label class="col-lg-3">
                <span class="label-tooltip"
                      data-toggle="tooltip"
                      data-html="true"
                      title=""
                      data-original-title="{l s='' mod='reverb'}">
                    {l s='Shipping methods' mod='reverb'}
                </span>
            </label>
            <div class="col-lg-9">
                <table class="table" id="shipping-methods-table">
                    <thead>
                    <tr>
                        <th>{l s='Location' mod='reverb'}</th>
                        <th>{l s='Standard Rate' mod='reverb'}</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    {if ($reverb_shipping_methods|count)}
                        {foreach from=$reverb_shipping_methods item='method' key="key"}
                            <tr>
                                <td>
                                    <select class="form-control reverb-shipping-region"  name="reverb_shipping_methods_region[]" >
                                        <option value="">{l s='Select a region' mod='reverb'}</option>
                                        {foreach from=$reverb_regions item='region' key="code"}
                                            <option value="{$code|escape:'htmlall':'UTF-8'}" {if ($code == $method['region_code'])}selected="selected"{/if}>
                                                {$region|escape:'htmlall':'UTF-8'}
                                            </option>
                                        {/foreach}
                                    </select>
                                </td>
                                <td>
                                    <div class="input-group money-type">
                                        <span class="input-group-addon">{$currency|escape:'htmlall':'UTF-8'}</span>
                                        <input type="text"
                                               name="reverb_shipping_methods_rate[]"
                                               class="form-control reverb-shipping-rate"
                                               value="{$method['rate']|escape:'htmlall':'UTF-8'}" />
                                    </div>
                                </td>
                                <td>
                                    {if ($key > 0)}
                                        <button onclick="removeShippingMethod(this);" type="button" class="btn btn-invisible btn-block delete p-l-0 p-r-0 btn-delete-shipping-method"><i class="icon-trash"> {l s='Delete' mod='reverb'}</i></button>
                                    {/if}
                                </td>
                            </tr>
                        {/foreach}
                    {else}
                        <tr>
                            <td>
                                <select class="form-control reverb-shipping-region"  name="reverb_shipping_methods_region[]" >
                                    <option value="">{l s='Select a region' mod='reverb'}</option>
                                    {foreach from=$reverb_regions item='region' key="code"}
                                        <option value="{$code|escape:'htmlall':'UTF-8'}">{$region|escape:'htmlall':'UTF-8'}</option>
                                    {/foreach}
                                </select>
                            </td>
                            <td>
                                <div class="input-group money-type">
                                    <span class="input-group-addon">{$currency|escape:'htmlall':'UTF-8'}</span>
                                    <input type="text"
                                           name="reverb_shipping_methods_rate[]"
                                           class="form-control reverb-shipping-rate"
                                           value=""/>
                                </div>
                            </td>
                            <td></td>
                        </tr>
                    {/if}
                    </tbody>
                </table>
            </div>
            <div class="col-md-3"></div>
            <div class="col-md-9">
                <button type="button" class="btn btn-primary-outline sensitive add" id="add-shipping-method"><i class="icon-plus-sign"></i> {l s='Add shipping locations' mod='reverb'}</button>
            </div>
        </div>
    </div>
    <div class="panel-footer">
        <a href="{$link->getAdminLink('AdminProducts')|escape:'htmlall':'UTF-8'}:'html':'UTF-8'}{if isset($smarty.request.page) && $smarty.request.page > 1}&amp;submitFilterproduct={$smarty.request.page|intval}{/if}" class="btn btn-default"><i class="process-icon-cancel"></i> {l s='Cancel' mod='reverb'}</a>
        <button type="submit" name="submitAddproduct" class="btn btn-default pull-right" {if isset($old16_prestashop) && $old16_prestashop>0}disabled="disabled"{/if}><i class="process-icon-{if isset($old16_prestashop) && $old16_prestashop>0}loading{else}save{/if}"></i> {l s='Save' mod='reverb'}</button>
        <button type="submit" name="submitAddproductAndStay" class="btn btn-default pull-right" {if isset($old16_prestashop) && $old16_prestashop>0}disabled="disabled"{/if}><i class="process-icon-{if isset($old16_prestashop) && $old16_prestashop>0}loading{else}save{/if}"></i> {l s='Save and stay' mod='reverb'}</button>
    </div>
</div>

<script type="text/javascript">

    function showShippingMode(mode)
    {
        if (mode == 'reverb') {
            $('#shipping-methods').hide();
            $('#shipping-profile').show();
        } else {
            $('#shipping-profile').hide();
            $('#shipping-methods').show();
        }
    }

    function removeShippingMethod(element)
    {
        $(element).parents('tr').remove();
        return false;
    }

    /**
     * Init Shipping Method with Everywhere, Europe and France
     */
    function initShippingMethod() {
        var listShipping = ['XX','EUR_EU','FR'];
        listShipping.forEach( function(s) {
                var lastTr = $('#shipping-methods-table tr').last();
                var region = lastTr.find('select.reverb-shipping-region').val(s);
                $('#add-shipping-method').click();
            }
        );
    }

    $(document).ready(function () {
        showShippingMode($('#shipping_select').val());

        $('#add-shipping-method').click(function () {
            var lastTr = $('#shipping-methods-table tr').last();
            var region = lastTr.find('select.reverb-shipping-region').val();
            var rate = lastTr.find('input.reverb-shipping-rate').val();
            if (region == '') {
                showErrorMessage("{l s='Please fill last shipping region and method' mod='reverb'}")
            } else {
                var newTr = lastTr.clone();
                newTr.find('td select.reverb-shipping-region').val('');
                newTr.find('td input.reverb-shipping-rate').val('');
                newTr.find('td').last().html('<button onclick="removeShippingMethod(this);" type="button" class="btn btn-invisible btn-block delete p-l-0 p-r-0 btn-delete-shipping-method"><i class="icon-trash"></i></button>');
                newTr.appendTo('#shipping-methods-table');
            }
            return false;
        });

        {if !($reverb_shipping_methods|count)}
            initShippingMethod();
        {/if}

        $('#shipping_select').change(function () {
            showShippingMode($(this).val());
        });
    });
</script>