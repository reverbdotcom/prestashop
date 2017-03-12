{*
* 2007-2015 PrestaShop
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
*  @copyright 2007-2015 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div class="panel">
	<div class="row moduleconfig-header">
		<div class="col-xs-5 text-right">
			<img src="{$module_dir|escape:'html':'UTF-8'}views/img/logo.jpg" />
		</div>
		<div class="col-xs-7 text-left">
			<h2>{l s='Lorem' mod='reverb'}</h2>
			<h4>{l s='Lorem ipsum dolor' mod='reverb'}</h4>
		</div>
	</div>

	<hr />

	<div role="tabpanel">
		<ul class="nav nav-tabs" role="tablist">
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
					{include file='./tabs/sync_status.tpl'}
				</div>
				<div role="tabpanel"
					 class="tab-pane  {if ((isset($active_tab) == true) && ($active_tab == 'categories'))} active{/if}"
					 id="categories">
					{include file='./tabs/categories.tpl'}
				</div>
				<div role="tabpanel"
					 class="tab-pane {if ((isset($active_tab) == true) && ($active_tab == 'settings'))} active{/if}"
					 id="settings">
					{include file='./tabs/settings.tpl'}
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
