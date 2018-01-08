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
        <div class="col-md-12 col-xs-12">
        	<div class="row">
                <!-- English FAQ -->
                <div class="col-md-6 ">
                	<h3>{l s='English' mod='reverb'}</h3>
				    <dl id="faq_q_a">
				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq1" aria-expanded="true" aria-controls="faq1">
				        	<i class="icon icon-question-circle"></i>  {l s='What is Reverb?' mod='reverb'}
				        </dt>
				        <dd id="faq1" class="panel-collapse collapse">
				            <p>
				            	{l s='Reverb is the largest and fastest growing community of musicians on the web. It\'s a one-stop resource that helps musicians learn about, compare, and find the gear that\'s right for them at a fair price, and a simple platform for private sellers and dealers to sell gear fast.Reverb is the journey and the destination.' mod='reverb'}
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq2" aria-expanded="true" aria-controls="faq2">
				        	<i class="icon icon-question-circle"></i>  {l s='How to connect to your Reverb.com account in the PrestaShop module?' mod='reverb'}
				        </dt>
				        <dd id="faq2" class="panel-collapse collapse">
				            <p>	
				            	{l s='You need to go to Reverb.com and log in to your seller account. Then you have to go into your account settings and access API & Integration.' mod='reverb'}<br />
								<img src="/modules/reverb/views/img/faq/capture-1-generate-token.png" style="width:70%" /><br />
				                {l s='Once the token is generated, you copy and paste it into the Login tab of the Reverb module. Validate your entry to log in.' mod='reverb'}<br />
				                <img src="/modules/reverb/views/img/faq/capture-2-login.png" style="width:70%" />
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq3" aria-expanded="true" aria-controls="faq3">
				        	<i class="icon icon-question-circle"></i>  {l s='How can Reverb recognize my product categories?' mod='reverb'}
				        </dt>
				        <dd id="faq3" class="panel-collapse collapse">
				            <p>
				                {l s='The Reverb module allows you to select your product category and associate it with a Reverb category. You must go to the Reverb module configuration and the Product type mapping tab.' mod='reverb'}
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq4" aria-expanded="true" aria-controls="faq4">
				        	<i class="icon icon-question-circle"></i>  {l s='How to sync your products to Reverb.com and process your Reverb orders on PrestaShop?' mod='reverb'}
				        </dt>
				        <dd id="faq4" class="panel-collapse collapse">
			                <h4>{l s='Setting your products:' mod='reverb'}</h4>

							<p>{l s='So that your product catalog or certain products are visible on the Reverb.com, you must complete the information required to edit your product. It presupposes to go on Catalog > Products > edit your product' mod='reverb'}. {l s='If you are on PrestaShop 1.6 then you have to edit the Reverb tab. Otherwise you are on PrestaShop 1.7, so go to the Module Options tab > Select the Reverb module' mod='reverb'}</p>
							<h4>{l s='Setting the CRON:' mod='reverb'}</h4>

							<p>{l s='CRON Tasks is a program that allows users of Unix systems to automatically run scripts, commands, or software at a specified date and time or in a pre-defined cycle. Go to the administrative panel of your hosting in order to learn how to set up the ordering of your CRON, otherwise get closer to your host.' mod='reverb'}</p>
							<p>{l s='The following cron Tasks must be configured:' mod='reverb'}</p>
							<ul>
							<li>*/5 * * * *  php /var/www/html/modules/reverb/cron.php?code=products > /var/log/cron.log</li>
							<li>*/8 * * * *  php /var/www/html/modules/reverb/cron.php?code=orders > /var/log/cron.log"</li>
							</ul>
							<p>{l s='The first cron is a script executed every 5 minutes about the product sync - PrestaShop to Reverb. The second cron is a script executed every 8 minutes about the order sync - Reverb to PrestaShop.' mod='reverb'}</p>
							<h4>{l s='Product sync management:' mod='reverb'}</h4>

							<p>{l s='In the Reverb module configuration in PrestaShop, you need to go to Sync Status tab. You can filter your search results and you can see the status of sync (Success, error, to_sync) with a message. 3 actions are availables: Sync a product Manuelly, a PrestaShop product link and a Reverb product link.' mod='reverb'}</p>
				        </dd>
				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq5" aria-expanded="true" aria-controls="faq5">
				        	<i class="icon icon-question-circle"></i>  {l s='And what if I\'ve met the sync does\'t work?' mod='reverb'}
				        </dt>
				        <dd id="faq5" class="panel-collapse collapse">
				            <p>
				                <ul>
									<li>{l s='Control if the token is valid' mod='reverb'}</li>
									<li>{l s='Control that each eligible product in Reverb is setup correctly' mod='reverb'}</li>
									<li>{l s='Control the logs in Logs tab' mod='reverb'}</li>
									<li>{l s='If the problem persist, contact' mod='reverb'} <a href="https://reverb.com/fr/page/contact" target="_blank">{l s='the Reverb support' mod='reverb'}</a></li>
								</ul>
				            </p>
				        </dd>
				    </dl>
				</div>
            </div>
        </div>
    </div>
</div>