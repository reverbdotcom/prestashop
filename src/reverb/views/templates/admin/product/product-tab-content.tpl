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
            <div class="row">
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
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="reverb_enabled" id="reverb_enabled_sync_on" value="1"
                                   {if ($reverb_enabled)}checked="checked"{/if}>
                            <label for="reverb_enabled_sync_on">Yes</label>

                            <input type="radio" name="reverb_enabled" id="reverb_enabled_sync_off" value="0"
                                   {if !($reverb_enabled)}checked="checked"{/if}>
                            <label for="reverb_enabled_sync_off">No</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </span>
                    <div class="alert alert-info" role="alert">
                        <i class="material-icons">help</i>
                        <p class="alert-text">
                            {l s='When you activate the syncronization with reverb the product is sent to  Reverb\'s marketplace.'}
                            <br/>
                            {l s='Then you can see the status of the synchronization on the page of Reverb Module.'}
                        </p>
                    </div>
                </div>
            </div>
            <div class="row form-group">
                <div class="row">
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
                        <span class="switch prestashop-switch fixed-width-lg">
                           <select class="form-control reverb-condition" name="reverb_condition">
                               <option value="" {if ($reverb_condition == '')}selected="selected"{/if}>--</option>
                               {foreach from=$reverb_list_conditions item=condition key=reverb_key}
                                   {$reverb_key}
                                   <option value="{$reverb_key}"
                                           {if ($reverb_condition == $reverb_key)}selected="selected"{/if}>{$condition}</option>
                               {/foreach}
                            </select>
                        </span>
                    </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form-group">
                <div class="row">
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
                                   value="{$reverb_finish}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form-group">
                <div class="row">
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
                                   value="{$reverb_year}"/>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form-group">
                <div class="row">
                    <div class="col-md-12">
                        <label class="col-lg-3">
                    <span class="label-tooltip"
                          data-toggle="tooltip"
                          data-html="true"
                          title=""
                          data-original-title="{l s='' mod='reverb'}">{l s='Sold as is' mod='reverb'}
                    </span>
                        </label>
                        <div class="col-lg-9">
                    <span class="switch prestashop-switch fixed-width-lg">
                        <span class="switch prestashop-switch fixed-width-lg">
                            <input type="radio" name="reverb_sold" id="reverb_enabled_sold_on" value="1"
                                   {if ($reverb_sold)}checked="checked"{/if}>
                            <label for="reverb_enabled_sold_on">Yes</label>

                            <input type="radio" name="reverb_sold" id="reverb_enabled_sold_off" value="0"
                                   {if !($reverb_sold)}checked="checked"{/if}>
                            <label for="reverb_enabled_sold_off">No</label>
                            <a class="slide-button btn"></a>
                        </span>
                    </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row form-group">
                <div class="row">
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
                            {foreach from=$reverb_list_country item='country'}
                                    <option value="{$country.iso_code}">&nbsp;{$country.name|escape}</option>
                                {/foreach}
                            </select>
                    </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


