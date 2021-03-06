{*
* 2007-2017 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2017 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<div class="row moduleconfig-header">
		<div class="col-xs-2 text-right" style="min-width:216px;">
			<h1 class="site-header__logo"></h1>
		</div>
		<div class="col-xs-10 text-left">
			<h2>{l s='Welcome to the world’s most popular music gear website.' mod='reverb'}</h2>
			<h4>{l s='Reverb.com is the online marketplace for musicians to buy, sell and learn about new, used, vintage and handmade music gear. When Reverb launched in 2013, it was founded on the principle that buying and selling musical instruments should be easy and affordable. Since then we’ve become a thriving marketplace that connects millions of people around the world to the gear and the inspiration needed to make music.' mod='reverb'}</h4>
			<h4>{l s='The Reverb Marketplace is made up of hundreds of thousands of buyers and sellers – from beginner musicians to collectors, mom-and-pop shops to large retailers, and popular manufacturers to boutique builders and luthiers. You might even run into some of your favorite rock stars buying and selling on Reverb!' mod='reverb'}</h4>
		</div>
	</div>

	<hr />

    {include file='./alerts.tpl'}

	<div role="tabpanel">
		<ul class="nav nav-tabs" role="tablist" id="myTab">
			<li role="presentation"
				class=" {if (!$is_logged || (isset($active_tab) && $active_tab == 'login'))} active{/if}">
				<a href="#login" aria-controls="login" role="tab" data-toggle="tab">
					<span class="icon icon-user"></span> {l s='Login' mod='reverb'}
				</a>
			</li>
			<li role="presentation"
				class="{if (!$is_logged)}disabled{elseif (isset($active_tab) && ($active_tab == 'sync_status'))}active{/if}">
				<a href="#sync_status" aria-controls="sync_status" role="tab" {if ($is_logged)}data-toggle="tab"{/if}>
					<span class="icon icon-refresh"></span> {l s='Sync Status' mod='reverb'}
				</a>
			</li>
			<li role="presentation"
				class="{if (!$is_logged)}disabled{elseif (isset($active_tab) && ($active_tab == 'orders_status'))}active{/if}">
				<a href="#orders_status" aria-controls="orders_status" role="tab" {if ($is_logged)}data-toggle="tab"{/if}>
					<span class="icon icon-refresh"></span> {l s='Orders Sync Status' mod='reverb'}
				</a>
			</li>
			<li role="presentation"
				class="{if (!$is_logged)}disabled{elseif (isset($active_tab) && ($active_tab == 'categories'))}active{/if}">
				<a href="#categories" aria-controls="categories" role="tab" {if ($is_logged)}data-toggle="tab"{/if}>
					<span class="icon icon-sort"></span> {l s='Product Type Mapping' mod='reverb'}
				</a>
			</li>
			<li role="presentation"
				class="{if (!$is_logged)}disabled{elseif (isset($active_tab) && ($active_tab == 'settings'))}active{/if}">
				<a href="#settings" aria-controls="settings" role="tab" {if ($is_logged)}data-toggle="tab"{/if}>
					<span class="icon icon-cogs"></span> {l s='Settings' mod='reverb'}
				</a>
			</li>
			<li role="presentation"
				class="{if (!$is_logged)}disabled{elseif (isset($active_tab) && ($active_tab == 'mass_edit'))}active{/if}">
				<a href="#mass_edit" aria-controls="mass_edit" role="tab" {if ($is_logged)}data-toggle="tab"{/if}>
					<span class="icon icon-list"></span> {l s='Mass edit' mod='reverb'}
				</a>
			</li>
			<li role="presentation">
				<a href="#faq" aria-controls="faq" role="tab" data-toggle="tab">
					<span class="icon icon-question-sign"></span> {l s='FAQ' mod='reverb'}
				</a>
			</li>
			<li role="presentation">
				<a href="#logs" aria-controls="logs" role="tab" data-toggle="tab">
					<span class="icon icon-align-justify"></span> {l s='Logs' mod='reverb'}
				</a>
			</li>
		</ul>

		<div class="tab-content">
			<div role="tabpanel"
				 class="tab-pane{if (!$is_logged || (isset($active_tab) && $active_tab == 'login'))} active{/if}"
				 id="login">
                {include file='./tabs/login.tpl'}
			</div>
            {if ($is_logged)}
				<div role="tabpanel"
					 class="tab-pane {if ((isset($active_tab) == false) || ($active_tab == 'sync_status'))} active{/if}"
					 id="sync_status">
					{if ($is_logged)}
						{include file='./tabs/sync_status.tpl'}
                    {/if}
				</div>
				<div role="tabpanel"
					 class="tab-pane {if ((isset($active_tab) == false) || ($active_tab == 'orders_status'))} active{/if}"
					 id="orders_status">
					{if ($is_logged)}
						{include file='./tabs/orders_status.tpl'}
                    {/if}
				</div>
				<div role="tabpanel"
					 class="tab-pane  {if ((isset($active_tab) == true) && ($active_tab == 'categories'))} active{/if}"
					 id="categories">
                    {if ($is_logged)}
						{include file='./tabs/categories.tpl'}
                    {/if}
				</div>
				<div role="tabpanel"
					 class="tab-pane {if ((isset($active_tab) == true) && ($active_tab == 'settings'))} active{/if}"
					 id="settings">
                    {if ($is_logged)}
						{include file='./tabs/settings.tpl'}
                    {/if}
				</div>
				<div role="tabpanel"
					 class="tab-pane {if ((isset($active_tab) == true) && ($active_tab == 'mass_edit'))} active{/if}"
					 id="mass_edit">
                    {if ($is_logged)}
						{include file='./tabs/mass_edit.tpl'}
                    {/if}
				</div>
            {/if}
			<div role="tabpanel" class="tab-pane" id="faq">
                {include file='./tabs/faq.tpl'}
			</div>
			<div role="tabpanel" class="tab-pane" id="logs">
                {include file='./tabs/logs.tpl'}
			</div>
		</div>
	</div>
</div>
<script type="text/javascript">
	$(document).ready(function(){
		$('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
			localStorage.setItem('activeTab', $(e.target).attr('href'));
		});
		var activeTab = localStorage.getItem('activeTab');
		if(activeTab){
			$('#myTab a[href="' + activeTab + '"]').tab('show');
		}
	});
</script>
