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
        <div class="col-md-6">
            <div class="row">
                {literal}
                <script type="text/javascript">
                    $().ready(function () {
                        var input_id = '{/literal}tags_reverb_search{literal}';
                        $('#'+input_id).tagify({delimiters: [13,44], addTagPrompt: '{/literal}{l s='Search Sku or Name' mod='reverb'}{literal}'});
                        $('#btn-search-submit').click( function() {
                            $(this).find('#'+input_id).val($('#'+input_id).tagify('serialize'));
                            processSearchAjax($('#search_form').serialize(),$('#search_form').attr('action'), 1);
                        });
                    });
                </script>
                {/literal}
                <form method="post"
                      action="{$ajax_url|escape:'htmlall':'UTF-8'}&action=SearchProductMassEdit&ajax=true"
                      id="search_form"
                      enctype="multipart/form-data">
                    <div class="col-md-10">
                        <input
                            type="text"
                            id="tags_reverb_search"
                            class="tagify updateCurrentText"
                            name="tags_reverb_search"
                            value=""
                            style="display: none;" />
                    </div>
                    <div class="col-md-2">
                        <div class="input-group-btn">
                            <button class="btn btn-default" type="button" id="btn-search-submit"><i class="icon icon-search"></i></button>
                        </div>
                    </div>
                </form>
            </div>
            <div class="row">
                <hr />
            </div>
            <div class="row">
                <table class="table table-striped table-bordered" id="table-products-list">
                    <thead>
                    <tr>
                        <th><input id="select-all" type="checkbox" title="{l s="Select/Deselect all" mod="reverb"}"></th>
                        <th>{l s='SKU' mod='reverb'}</th>
                        <th>{l s='Name' mod='reverb'}</th>
                        <th>{l s='Actions' mod='reverb'}</th>
                    </tr>
                    <thead>
                    <tbody>
                    {foreach from=$products_mass_edit['products'] item=product key=key}
                        <tr id="product-{$product['id_product']|escape:'htmlall':'UTF-8'}">
                            <td>
                                <input id="{$product['id_product']|escape:'htmlall':'UTF-8'}"
                                       name="checkbox-product[]"
                                       type="checkbox"
                                       class="checkbox-bulk"
                                       value="{$product['id_product']|escape:'htmlall':'UTF-8'}"
                                >
                            </td>
                            <td>{$product['reference']|escape:'htmlall':'UTF-8'}</td>
                            <td>{$product['name']|escape:'htmlall':'UTF-8'}</td>
                            <td>
                                <a class="btn toggle-product-activation"
                                   href="#"
                                   data-id="{$product['id_product']}"
                                   title="{if ($product['reverb_enabled'])}{l s='Enabled' mod='reverb'}{else}{l s='Disabled' mod='reverb'}{/if}"
                                >
                                    {if ($product['reverb_enabled'])}
                                        <i class="icon-ok-sign"></i>
                                    {else}
                                        <i class="icon-remove"></i>
                                    {/if}
                                </a>
                                <button class="btn btn-small load-product"
                                        type="button"
                                        data-id="{$product['id_product']}"
                                        data-key="{$key}"
                                >Load</button>
                            </td>
                        </tr>
                    {/foreach}
                    </tbody>
                </table>
            </div>
            <div class="row">
                <div class="col-md-6">
                    <div class="col-md-6">
                        <select class=" pull-left" id="bulk-action">
                            <option value="mass-synchronize">{l s='On / Off synchronization' mod='reverb'}</option>
                            <option value="mass-offer">{l s='On / Off Make an offers' mod='reverb'}</option>
                            <option value="mass-local-pickup">{l s='On / Off Local pickup' mod='reverb'}</option>
                            <option value="mass-edit">{l s='Edit products in bulk' mod='reverb'}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <button class="btn btn-small "
                                type="button"
                                id="btn-bulk-action"
                        >Go</button>
                    </div>
                </div>
                <div class="col-md-6">
                    <ul class="pagination pull-right">
                        <li class="{if ($products_mass_edit['page'] == 1)}disabled{/if}" id="first-page">
                            <a href="javascript:void(0);" class="pagination-link" data-page="1">
                                <i class="icon-double-angle-left"></i>
                            </a>
                        </li>
                        <li class="{if ($products_mass_edit['page'] == 1)}disabled{/if}" id="prev-page">
                            <a href="javascript:void(0);" class="pagination-link" data-page="{$products_mass_edit['page']-1}" id="prev-page-link">
                                <i class="icon-angle-left"></i>
                            </a>
                        </li>
                        <li id="pagination-nbpage" class=" disabled">
                            <a href="javascript:void(0);" class="pagination-link " data-page="{$products_mass_edit['page']}">
                                <span id="current-page">{$products_mass_edit['page']}</span> / <span id="nbr-page">{$products_mass_edit['nbPage']}</span>
                            </a>
                        </li>
                        <li class="{if ($products_mass_edit['page'] == $products_mass_edit['nbPage'])}disabled{/if}" id="next-page">
                            <a href="javascript:void(0);" class="pagination-link" data-page="{$products_mass_edit['page']+1}" id="next-page-link">
                                <i class="icon-angle-right"></i>
                            </a>
                        </li>
                        <li class="{if ($products_mass_edit['page'] == $products_mass_edit['nbPage'])}disabled{/if}" id="last-page">
                            <a href="javascript:void(0);" class="pagination-link" data-page="{$products_mass_edit['nbPage']}">
                                <i class="icon-double-angle-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="panel">
                <form id="edit_form">
                    <input type="hidden" id="products-to-edit" name="products-to-edit"/>
                    <div class="row form-group">
                        <div class="col-md-12">
                            <!-- SWITCH reverb_enabled MODE START -->
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                <span class="label-tooltip"
                                      data-toggle="tooltip"
                                      data-html="true"
                                      title=""
                                      data-original-title="{l s='When you activate the syncronization with reverb the product is sent on Reverb\'smarketplace' mod='reverb'}">
                                    {l s='Active synchronization' mod='reverb'}
                                </span>
                                </label>
                                <div class="col-lg-9">
                                    <span class="switch prestashop-switch fixed-width-lg">
                                        <input type="radio" name="reverb_enabled" id="reverb_enabled_switchmode_on" value="1" checked>
                                        <label for="reverb_enabled_switchmode_on">{l s='Yes' mod='reverb'}</label>
                                        <input type="radio" name="reverb_enabled" id="reverb_enabled_switchmode_off" value="0">
                                        <label for="reverb_enabled_switchmode_off">{l s='No' mod='reverb'}</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                                    <div class="alert alert-info" role="alert">
                                        <p class="alert-text">
                                            {l s='When you activate the syncronization with reverb the product is sent to  Reverb\'s marketplace.'  mod='reverb' }
                                            <br/>
                                            {l s='Then you can see the status of the synchronization on the page of Reverb Module.' mod='reverb'}
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <!-- SWITCH reverb_enabled MODE END -->
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
                                <span class="switch prestashop-switch fixed-width-lg">
                                   <select class="form-control reverb-condition" name="reverb_condition" id="reverb-condition">
                                       <option value="" selected="selected">--</option>
                                       {foreach from=$reverb_list_conditions item=condition key=reverb_key}
                                           <option value="{$reverb_key}">
                                               {$condition|escape:'htmlall':'UTF-8'}
                                           </option>
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
                                <input type="text" name="reverb_model" class="form-control reverb-model" readonly />
                                <div class="alert alert-info" role="alert">
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
                                <input type="text" name="reverb_finish" class="form-control reverb-finish" />
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
                                <input type="text" name="reverb_year" class="form-control reverb-year" />
                            </div>
                        </div>
                    </div>
                    <div class="row form-group">
                        <div class="col-md-12">
                            <!-- SWITCH reverb_offers_enabled MODE START -->
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    <span class="label-tooltip"
                                          data-toggle="tooltip"
                                          data-html="true"
                                          title="">
                                        {l s='Make an Offer' mod='reverb'}
                                    </span>
                                </label>
                                <div class="col-lg-9">
                                    <span class="switch prestashop-switch fixed-width-lg">
                                        <input type="radio" name="reverb_offers_enabled" id="reverb_offers_enabled_switchmode_on" value="1" checked>
                                        <label for="reverb_offers_enabled_switchmode_on">{l s='Yes' mod='reverb'}</label>
                                        <input type="radio" name="reverb_offers_enabled" id="reverb_offers_enabled_switchmode_off" value="0">
                                        <label for="reverb_offers_enabled_switchmode_off">{l s='No' mod='reverb'}</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                                </div>
                            <!-- SWITCH reverb_offers_enabled MODE END -->
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
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <select name="reverb_country" id="country_select" class="form-control input-large" >
                                        <option value="">--</option>
                                        {foreach from=$reverb_list_country item='country'}
                                            <option value="{$country.iso_code|escape:'htmlall':'UTF-8'}">&nbsp;{$country.name|escape:'htmlall':'UTF-8'}</option>
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
                                <span class="switch prestashop-switch fixed-width-lg">
                                    <select name="reverb_shipping" id="shipping_select" class="form-control input-large" >
                                        <option value="reverb">
                                            {l s='Reverb shipping profile'  mod='reverb'}
                                        </option>
                                        <option value="custom" selected="selected">
                                            {l s='Any : use custom shipping'  mod='reverb'}
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
                                    <select class="form-control reverb-shipping-profiles"  name="reverb_shipping_profile" id="reverb_shipping_profile">
                                        <option value="">{l s='Select a shipping profile'  mod='reverb'}</option>
                                        {foreach from=$reverb_shipping_profiles item='profile'}
                                            <option value="{$profile['id']}">
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
                        <div class="col-md-12 form-group">
                            <!-- SWITCH reverb_shipping_local MODE START -->
                            <div class="form-group">
                                <label class="control-label col-lg-3">
                                    <span class="label-tooltip"
                                          data-toggle="tooltip"
                                          data-html="true"
                                          title="">
                                        {l s='Local Pickup' mod='reverb'}
                                    </span>
                                </label>
                                <div class="col-lg-9">
                                    <span class="switch prestashop-switch fixed-width-lg">
                                        <input type="radio" name="reverb_shipping_local" id="reverb_shipping_local_switchmode_on" value="1" checked>
                                        <label for="reverb_shipping_local_switchmode_on">{l s='Yes' mod='reverb'}</label>
                                        <input type="radio" name="reverb_shipping_local" id="reverb_shipping_local_switchmode_off" value="0">
                                        <label for="reverb_shipping_local_switchmode_off">{l s='No' mod='reverb'}</label>
                                        <a class="slide-button btn"></a>
                                    </span>
                                </div>
                                <!-- SWITCH reverb_shipping_local MODE END -->
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
                                        <th>{l s='Standard Rate'  mod='reverb'}</th>
                                        <th></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <tr>
                                        <td>
                                            <select class="form-control reverb-shipping-region"  name="reverb_shipping_methods_region[]" >
                                                <option value="">{l s='Select a region'  mod='reverb'}</option>
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
                                    </tbody>
                                </table>
                                <div class="pull-right add-shipping">
                                    <button type="button" class="btn btn-primary-outline sensitive add" id="add-shipping-method"><i class="icon-plus-sign"></i> {l s='Add shipping locations' mod='reverb'}</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
                <div class="panel-footer" id="mass-edit-panel-footer" style="display: none;">
                    <button type="submit" value="1" id="form_mass_edit_button" class="btn btn-default pull-right">
                        <i class="process-icon-save"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal mass edit validation-->
<div class="modal fade" id="mass-edit-modal" tabindex="-1" role="dialog" aria-labelledby="confirmMassEdit" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="confirmMassEdit">{l s='Warning'  mod='reverb'}</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>{l s='Are you sure you want to mass-edit the selected products?'  mod='reverb'}</strong></p>
                <p>{l s='If you continue, please fill last shipping region and method.'  mod='reverb'}</p>
                <p>{l s='Note: the Model field is Read only and when the form is saved by default the model field is the product name. '   mod='reverb'}</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="mass-edit-cancel">{l s='Cancel'  mod='reverb'}</button>
                <button type="button" id="mass-edit-ok" class="btn btn-primary">{l s='Continue'  mod='reverb'}</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal pre edit confirmation -->
<div class="modal fade" id="mass-edit-modal-confirmation" tabindex="-1" role="dialog" aria-labelledby="confirmMassEditSubmit" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="confirmMassEditSubmit">{l s='Warning'  mod='reverb'}</h3>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p><strong>{l s='Are you sure you want to mass-edit the selected products?'  mod='reverb'}</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cancel'  mod='reverb'}</button>
                <button type="button" id="mass-edit-ok-confirm" class="btn btn-primary">{l s='Continue'  mod='reverb'}</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal edit result -->
<div class="modal fade" id="mass-edit-modal-result" tabindex="-1" role="dialog" aria-labelledby="massEditResult" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h3 class="modal-title" id="massEditResult"></h3>
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

    function removeShippingMethod(element) {
        $(element).parents('tr').remove();
        return false;
    }

    function addShippingLine(check)
    {
        var lastTr = $('#shipping-methods-table tr').last();
        if (check) {
            var region = lastTr.find('select.reverb-shipping-region').val();
            if (region == '') {
                showErrorMessage("{l s='Please fill last shipping region and method' mod='reverb'}")
            }
        } else {
            var newTr = lastTr.clone();
            newTr.find('td select.reverb-shipping-region').val('');
            newTr.find('td input.reverb-shipping-rate').val('');
            newTr.find('td').last().html('<button onclick="removeShippingMethod(this);" type="button" class="btn btn-invisible btn-block delete p-l-0 p-r-0 btn-delete-shipping-method"><i class="icon-trash"></i></button>');
            newTr.appendTo('#shipping-methods-table');
        }
        return newTr;
    }

    /**
     * Init Shipping Method with Everywhere, Europe and France
     */
    function initShippingMethod(check) {
        var listShipping = ['XX','EUR_EU','FR'];
        listShipping.forEach( function(s) {
            var lastTr = $('#shipping-methods-table tr').last();
            var region = lastTr.find('select.reverb-shipping-region').val(s);
            addShippingLine(check);
        });
    }

    function resetForm()
    {
        document.getElementById("edit_form").reset();
        var oldSelects = $('#shipping-methods-table tr');
        addShippingLine(false);
        initShippingMethod(false);
        oldSelects.remove();
    }

    function processMassEditAjax(data, massEdit) {
        $.post('{$ajax_url}&action=MassEdit&ajax=true', data, function (response) {
            var result = JSON.parse(response);
            if (result.status === 'error') {
                $('#mass-edit-modal-result').find('#text-result').html(result.message);
                $('#mass-edit-modal-result').find('#massEditResult').html("{l s='Error'  mod='reverb'}");
                $('#mass-edit-modal-result').modal('show');
            } else {
                $.each(result.products, function (i, item) {
                    var trHTML = '';
                    trHTML += '<td><input id="' + item.id_product + '" name="checkbox-product[]" type="checkbox" value="' + item.id_product + '" class="checkbox-bulk"></td>';
                    trHTML += '<td>' + item.reference + '</td><td>' + item.name + '</td>';
                    trHTML += '<td><a class="btn toggle-product-activation" href="#" data-id="' + item.id_product + '"';
                    trHTML += 'title="';

                    if (item.reverb_enabled)
                        trHTML += '{l s='Enabled' mod='reverb'}';
                    else
                        trHTML += '{l s='Disabled' mod='reverb'}';

                    trHTML += '">';
                    if (item.reverb_enabled === 1 || item.reverb_enabled === '1') {
                        trHTML += '<i class="icon-ok-sign"></i>';
                    } else {
                        trHTML += '<i class="icon-remove"></i>';
                    }

                    trHTML += '</a><button class="btn btn-small load-product" type="button" data-id="' + item.id_product + '">Load</button>';
                    trHTML += '</td>';

                    $('#table-products-list tbody tr#product-' + item.id_product).html(trHTML);
                });

                $('#mass-edit-modal-result').find('#text-result').html(result.message);
                $('#mass-edit-modal-result').find('#massEditResult').html("{l s='Success'  mod='reverb'}");
                $('#mass-edit-modal-result').modal('show');

                resetForm();

                if (massEdit) {
                    // Disable model field and hide save button
                    $('input[name="reverb_model"]').val('').attr('readonly', 'readonly');
                    $('#mass-edit-panel-footer').hide();
                }
            }
        }).fail(function (error) {
            $('#mass-edit-modal-result').find('#text-result').html(error.responseText);
            $('#mass-edit-modal-result').find('#massEditResult').html("{l s='Error'  mod='reverb'}");
            $('#mass-edit-modal-result').modal('show');
        });
    }


    function processSearchAjax(data, url, page) {
        if (page !== undefined) {
            data = data + '&page_reverb_search=' + page
        }
        console.log(data);
        $.ajax({
            url: url,
            data: data,
            datatype: 'json'
        }).success(function (response) {
            var pagination = JSON.parse(response);
            if (response.status == 'error') {
                showErrorMessage(response.message);
            } else {
                var trHTML = '';
                $.each(pagination.products, function (i, item) {
                    trHTML += '<tr><td><input id="' + item.id_product + '" name="checkbox-product[]" type="checkbox" value="' + item.id_product + '" class="checkbox-bulk"></td>';
                    trHTML += '<td>' + item.reference + '</td><td>' + item.name + '</td>';
                    trHTML += '<td><a class="btn toggle-product-activation" href="#" data-id="' + item.id_product + '"';
                    trHTML += 'title="';

                    if (item.reverb_enabled)
                        trHTML += '{l s='Enabled' mod='reverb'}';
                    else
                        trHTML += '{l s='Disabled' mod='reverb'}';

                    trHTML += '">';
                    if (item.reverb_enabled) {
                        trHTML += '<i class="icon-ok-sign"></i>';
                    } else {
                        trHTML += '<i class="icon-remove"></i>';
                    }

                    trHTML += '</a><button class="btn btn-small load-product" type="button" data-id="' + item.id_product + '">Load</button>';
                    trHTML += '</td></tr>';
                });

                console.log('page = ' + pagination.page);
                console.log('nbpage = ' + pagination.nbPage);

                // Update pagination
                $('#current-page').html(pagination.page);
                $('#nbr-page').html(pagination.nbPage);
                if (pagination.page == 1) {
                    $('#prev-page, #first-page').addClass('disabled');
                } else if (pagination.nbPage > 1) {
                    $('#prev-page, #first-page').removeClass('disabled');
                }

                if (pagination.page == pagination.nbPage) {
                    $('#next-page, #last-page').addClass('disabled');
                } else {
                    $('#next-page, #last-page').removeClass('disabled');
                }
                $('#prev-page > a').data('page', parseInt(pagination.page) - 1);
                $('#next-page > a').data('page', parseInt(pagination.page) + 1);
            }
            $('#table-products-list tbody').html(trHTML);
        }).fail(function (error) {
            showErrorMessage(error.responseText);
        });
    }

    $(document).ready(function () {

        showShippingMode($('#shipping_select').val());

        $('#add-shipping-method').click(function () {
            addShippingLine();
            return false;
        });

        initShippingMethod(true);

        $('#shipping_select').live('change', function () {
            showShippingMode($(this).val());
        });

        $('.load-product').live('click', function () {
            var id_product = $(this).data('id');

            // Ajax call with secure token
            $.post('{$ajax_url}&action=LoadProduct&ajax=true', {
                    'id_product': id_product,
            }, function (response) {
                product = JSON.parse(response);

                // Enable model field and show save button
                $('input[name="reverb_model"]').removeAttr('readonly');
                $('#mass-edit-panel-footer').show();

                if (product['reverb_enabled'] == '1') {
                    $('#reverb_enabled_switchmode_on').attr('checked', 'checked');
                    $('#reverb_enabled_switchmode_off').removeAttr('checked');
                } else {
                    $('#reverb_enabled_switchmode_on').removeAttr('checked');
                    $('#reverb_enabled_switchmode_off').attr('checked', 'checked');
                }

                $('#reverb-condition>option[value="' + product['id_condition'] + '"]').attr('selected', 'selected');

                $('input[name="reverb_model"]').val(product['model']);
                $('input[name="reverb_finish"]').val(product['finish']);
                $('input[name="reverb_year"]').val(product['year']);
                if (product['offers_enabled'] == '1') {
                    $('#reverb_offers_enabled_switchmode_on').attr('checked', 'checked');
                    $('#reverb_offers_enabled_switchmode_off').removeAttr('checked');
                } else {
                    $('#reverb_offers_enabled_switchmode_on').removeAttr('checked');
                    $('#reverb_offers_enabled_switchmode_off').attr('checked', 'checked');
                }
                $('#country_select>option[value="'+product['origin_country_code']+'"]').attr('selected', 'selected');

                if (product['id_shipping_profile'] !== '0') {
                    $('#shipping_select>option[value="reverb"]').attr('selected', 'selected');
                    showShippingMode($('#shipping_select').val());
                    $('#reverb_shipping_profile>option[value="' + product['id_shipping_profile'] + '"]').attr('selected', 'selected');
                } else {
                    $('#shipping_select>option[value="custom"]').attr('selected', 'selected');
                    showShippingMode($('#shipping_select').val());
                    if (product['shipping_local'] == 1) {
                        $('#reverb_shipping_local_switchmode_on').attr('checked', 'checked');
                        $('#reverb_shipping_local_switchmode_off').removeAttr('checked');
                    } else {
                        $('#reverb_shipping_local_switchmode_on').removeAttr('checked');
                        $('#reverb_shipping_local_switchmode_off').attr('checked', 'checked');
                    }

                    var oldSelects = $('#shipping-methods-table tr');
                    if (product['shippings'].length > 0) {
                        for (key in product['shippings']) {
                            var shipping = product['shippings'][key];
                            var newTr = addShippingLine(false);
                            newTr.find('td select.reverb-shipping-region').val(shipping.location);
                            newTr.find('td input.reverb-shipping-rate').val(shipping.rate);
                        }
                    } else {
                        initShippingMethod(false);
                    }
                    // Remove default shippings row
                    oldSelects.remove();
                }
                $('#products-to-edit').val(id_product);
                // Uncheck all products
                $('.checkbox-bulk').removeAttr('checked');
            })
            .fail(function() {
                showErrorMessage("{l s='An error has occured. Please try again' mod='reverb'}");
            })
            return false;
        });

        $('.toggle-product-activation').live('click', function () {
            var link = $(this);
            link.attr('disabled', 'disabled');

            var id_product = $(this).data('id');

            // Ajax call with secure token
            $.post('{$ajax_url}&action=ToggleActiveSyncronization&ajax=true', {
                    'id_product': id_product,
                }, function (response) {
                    response = JSON.parse(response);
                    showSuccessMessage("{l s='Synchronization updated' mod='reverb'}");
                    if (response.enabled) {
                        link.children('i').removeClass().addClass('icon-ok-sign');
                        link.attr('title', "{l s='Enabled' mod='reverb'}");
                    } else {
                        link.children('i').removeClass().addClass('icon-remove');
                        link.attr('title', "{l s='Disabled' mod='reverb'}");
                    }
                }
            )
            .fail(function() {
                showErrorMessage("{l s='An error has occured. Please try again' mod='reverb'}");
            })
            .always(function() {
                link.removeAttr('disabled');
            });
            return false;
        });

        $('#select-all').live('click', function () {
            if ($(this).attr('checked')) {
                $('.checkbox-bulk').attr('checked', 'checked');
            } else {
                $('.checkbox-bulk').removeAttr('checked');
            }
        });

        $('.pagination-link').live('click', function () {
            processSearchAjax($('#search_form').serialize(),$('#search_form').attr('action'), $(this).data('page'));
        });

        $('#btn-bulk-action').live('click', function () {
            if ($('.checkbox-bulk:checked').length == 0) {
                showErrorMessage("{l s='Please select at least one product' mod='reverb'}");
                return false;
            }
            var productIds = '';
            $('.checkbox-bulk:checked').each(function(i,e){
                productIds += 'productIds[]=' + $(e).attr('value') + '&';
            });

            if ($('#bulk-action').val() === 'mass-edit') {
                $('#mass-edit-modal').modal('show');
            } else {
                // Disable model field and show hide button
                $('input[name="reverb_model"]').val('').attr('readonly', 'readonly');
                $('#mass-edit-panel-footer').hide();
                processMassEditAjax(productIds + 'bulkAction=' + $('#bulk-action').val(), true);
            }
        });

        $('#mass-edit-ok').live('click', function () {
            $('#products-to-edit').val('');
            // Disable model field and show save button
            $('input[name="reverb_model"]').val('').attr('readonly', 'readonly');
            $('#mass-edit-panel-footer').show();
            $('#mass-edit-modal').modal('hide');
        });

        $('#mass-edit-cancel').live('click', function () {
            $('#products-to-edit').val('');
            // Disable model field and show save button
            $('#mass-edit-panel-footer').hide();
            $('#mass-edit-modal').modal('hide');
        });

        $('#mass-edit-ok-confirm').live('click', function () {
            $('#mass-edit-modal-confirmation').modal('hide');
            var productIds = '';
            if ($('#products-to-edit').val() === '') {
                // Mass edit
                var massEdit = true;
                var nbProducts = 0;
                $('.checkbox-bulk:checked').each(function (i, e) {
                    nbProducts++;
                    productIds += 'productIds[]=' + $(e).attr('value') + '&';
                });
            } else {
                var massEdit = false;
                // Single edit
                var nbProducts = 1;
                productIds = 'productIds[]=' + $('#products-to-edit').val() + '&';
            }
            data = productIds + 'bulkAction=mass-edit' + '&' + $('#edit_form').serialize();

            processMassEditAjax(data, massEdit);

            return false;
        });


        $('#form_mass_edit_button').live('click', function () {
            $('#mass-edit-modal-confirmation').modal('show');
        });
    });
</script>