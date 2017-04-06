<div class="panel">
	<div class="row">
        <div class="col-md-12 col-xs-12">
        	<div class="row">
                <!-- English FAQ -->
                <div class="col-md-6 ">
                	<h3>English</h3>
				    <dl id="faq_q_a">
				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq1" aria-expanded="true" aria-controls="faq1">
				        	<i class="icon icon-question-circle"></i> What is Reverb?
				        </dt>
				        <dd id="faq1" class="panel-collapse collapse">
				            <p>
				                Reverb is the largest and fastest growing community of musicians on the web. It's a one-stop resource that helps musicians learn about, compare, and find the gear that's right for them at a fair price, and a simple platform for private sellers and dealers to sell gear fast.Reverb is the journey and the destination.
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq2" aria-expanded="true" aria-controls="faq2">
				        	<i class="icon icon-question-circle"></i> Comment se connecter à son compte Reverb.com dans le module PrestaShop ?
				        </dt>
				        <dd id="faq2" class="panel-collapse collapse">
				            <p>
				                Vous devez aller sur le site Reverb.com et vous connectez à votre compte vendeur. Ensuite il faut aller dans les paramètres de votre compte et accéder à API & Integration.<br />
								<img src="/modules/reverb/views/img/faq/capture-1-generate-token.png" style="width:70%" /><br />
				                Une fois le token généré, vous faites un copier/coller dans l'onglet de connexion du module Reverb.Valider votre saisit pour vous connectez.<br />
				                <img src="/modules/reverb/views/img/faq/capture-2-login.png" style="width:70%" />
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq3" aria-expanded="true" aria-controls="faq3">
				        	<i class="icon icon-question-circle"></i> Comment Reverb peut reconnaître mes catégories de produit ?
				        </dt>
				        <dd id="faq3" class="panel-collapse collapse">
				            <p>
				                Le module de Reverb permet de sélectionner votre catégorie de produit et l'associer à une catégorie Reverb. Il faut se rendre dans la configuration du module Reverb et l'onglet Product type mapping.
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq4" aria-expanded="true" aria-controls="faq4">
				        	<i class="icon icon-question-circle"></i> Comment synchroniser vos produits vers Reverb.com et traiter vos commandes Reverb sur PrestaShop ?
				        </dt>
				        <dd id="faq4" class="panel-collapse collapse">
			                <h4>Paramétrer vos produits:</h4>

							<p>Afin que votre catalogue produits ou certains produits soient visible sur le site de Reverb.com, il faut remplir les informations nécéssaires à l'édition de votre produit. Pour cela, il faut se rendre sur Catalogue > Produits > éditer le produit souhaité. Si vous êtes sur PrestaShop 1.6 alors il faut éditer l'onglet Reverb. Sinon vous êtes sur PrestaShop 1.7, il faut donc se rendre dans l'onglet Options des modules > Sélectionner le module Reverb.</p>

							<h4>Paramétrer votre tâche CRON:</h4>

							<p>Les tâche CRON sont un programme qui permet aux utilisateurs des systèmes Unix d’exécuter automatiquement des scripts, des commandes ou des logiciels à une date et une heure spécifiées à l’avance, ou selon un cycle défini à l’avance. Nous vous demandons de vous rendre sur le panel administratif de votre hébergement afin de vous renseignez comment se configure l'ordonacement de vos CRON, si rapprochez-vous de votre hébergeur.</p>
							<p>Les tâches CRON à configurer sont les suivantes:</p>
							<ul>
							<li>*/5 * * * *  php /var/www/html/modules/reverb/cron.php?code=product > /var/log/cron.log</li>
							<li>*/8 * * * *  php /var/www/html/modules/reverb/cron.php?code=orders > /var/log/cron.log"</li>
							</ul>
							<p>La première ligne concerne la synchronisation automatique de vos produits éligibles à Reverb toutes les 5 minutes.
							La deuxième ligne importe vos commandes créées sur Reverb.com sur votre boutique PrestaShop toutes les 8 minutes. Elle permet aussi d'exporter la mise à jour de vos commandes traitées en expédié en envoyant le shipping tracker sur Reverb.com. Ainsi vos clients pourront suivre la livraison de leurs commandes directement sur Reverb.</p>

							<h4>Gestion de la synchronisation des produits:</h4>

							<p>Dans la configuration du module Reverb sur PrestaShop, il faut vous rendre dans l'onglet Sync status. Vous avez un tableau contenant la possibilité de filtrer le résultat pour mieux retrouver ce dont vous avez besoin. Vous pourrez visualiser l'état de votre synchronisation (Success, error, to_sync), vous aurez aussi la possibilité de visualiser le message en cas d'erreur, de succès. 3 actions possibles pour chaque ligne du tableau: Synchronisation du produit Manuellement, afficher la fiche produit sur PrestaShop et afficher la produit sur Reverb.com.</p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq5" aria-expanded="true" aria-controls="faq5">
				        	<i class="icon icon-question-circle"></i> Que faire si la synchronisation ne fonctionne pas ?
				        </dt>
				        <dd id="faq5" class="panel-collapse collapse">
				            <p>
				                <ul>
									<li>Vérifier si le token de connexion est valide</li>
									<li>Vérifier que chaque produit éligible à Reverb est bien paramétrés</li>
									<li>Vérifier les logs dans l'onglet logs</li>
									<li>Si le problème persiste, veuillez contacter le <a href="https://reverb.com/fr/page/contact" target="_blank">support de Reverb</a></li>
								</ul>
				            </p>
				        </dd>
				    </dl>
				</div>
                <!-- French FAQ -->
                <div class="col-md-6">
                	<h3>Français</h3>
					<dl id="faq_q_a">
				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq6" aria-expanded="true" aria-controls="faq6">
				        	<i class="icon icon-question-circle"></i> What is Reverb?
				        </dt>
				        <dd id="faq6" class="panel-collapse collapse">
				            <p>
				                Reverb is the largest and fastest growing community of musicians on the web. It's a one-stop resource that helps musicians learn about, compare, and find the gear that's right for them at a fair price, and a simple platform for private sellers and dealers to sell gear fast.Reverb is the journey and the destination.
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq7" aria-expanded="true" aria-controls="faq7">
				        	<i class="icon icon-question-circle"></i> Comment se connecter à son compte Reverb.com dans le module PrestaShop ?
				        </dt>
				        <dd id="faq7" class="panel-collapse collapse">
				            <p>
				                Vous devez aller sur le site Reverb.com et vous connectez à votre compte vendeur. Ensuite il faut aller dans les paramètres de votre compte et accéder à API & Integration.<br />
								<img src="/modules/reverb/views/img/faq/capture-1-generate-token.png" style="width:70%" /><br />
				                Une fois le token généré, vous faites un copier/coller dans l'onglet de connexion du module Reverb.Valider votre saisit pour vous connectez.<br />
				                <img src="/modules/reverb/views/img/faq/capture-2-login.png" style="width:70%" />
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq8" aria-expanded="true" aria-controls="faq8">
				        	<i class="icon icon-question-circle"></i> Comment Reverb peut reconnaître mes catégories de produit ?
				        </dt>
				        <dd id="faq8" class="panel-collapse collapse">
				            <p>
				                Le module de Reverb permet de sélectionner votre catégorie de produit et l'associer à une catégorie Reverb. Il faut se rendre dans la configuration du module Reverb et l'onglet Product type mapping.
				            </p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq9" aria-expanded="true" aria-controls="faq9">
				        	<i class="icon icon-question-circle"></i> Comment synchroniser vos produits vers Reverb.com et traiter vos commandes Reverb sur PrestaShop ?
				        </dt>
				        <dd id="faq9" class="panel-collapse collapse">
			                <h4>Paramétrer vos produits:</h4>

							<p>Afin que votre catalogue produits ou certains produits soient visible sur le site de Reverb.com, il faut remplir les informations nécéssaires à l'édition de votre produit. Pour cela, il faut se rendre sur Catalogue > Produits > éditer le produit souhaité. Si vous êtes sur PrestaShop 1.6 alors il faut éditer l'onglet Reverb. Sinon vous êtes sur PrestaShop 1.7, il faut donc se rendre dans l'onglet Options des modules > Sélectionner le module Reverb.</p>

							<h4>Paramétrer votre tâche CRON:</h4>

							<p>Les tâche CRON sont un programme qui permet aux utilisateurs des systèmes Unix d’exécuter automatiquement des scripts, des commandes ou des logiciels à une date et une heure spécifiées à l’avance, ou selon un cycle défini à l’avance. Nous vous demandons de vous rendre sur le panel administratif de votre hébergement afin de vous renseignez comment se configure l'ordonacement de vos CRON, si rapprochez-vous de votre hébergeur.</p>
							<p>Les tâches CRON à configurer sont les suivantes:</p>
							<ul>
							<li>*/5 * * * *  php /var/www/html/modules/reverb/cron.php?code=product > /var/log/cron.log</li>
							<li>*/8 * * * *  php /var/www/html/modules/reverb/cron.php?code=orders > /var/log/cron.log"</li>
							</ul>
							<p>La première ligne concerne la synchronisation automatique de vos produits éligibles à Reverb toutes les 5 minutes.
							La deuxième ligne importe vos commandes créées sur Reverb.com sur votre boutique PrestaShop toutes les 8 minutes. Elle permet aussi d'exporter la mise à jour de vos commandes traitées en expédié en envoyant le shipping tracker sur Reverb.com. Ainsi vos clients pourront suivre la livraison de leurs commandes directement sur Reverb.</p>

							<h4>Gestion de la synchronisation des produits:</h4>

							<p>Dans la configuration du module Reverb sur PrestaShop, il faut vous rendre dans l'onglet Sync status. Vous avez un tableau contenant la possibilité de filtrer le résultat pour mieux retrouver ce dont vous avez besoin. Vous pourrez visualiser l'état de votre synchronisation (Success, error, to_sync), vous aurez aussi la possibilité de visualiser le message en cas d'erreur, de succès. 3 actions possibles pour chaque ligne du tableau: Synchronisation du produit Manuellement, afficher la fiche produit sur PrestaShop et afficher la produit sur Reverb.com.</p>
				        </dd>

				        <dt data-toggle="collapse" data-parent="#faq_q_a" href="#faq10" aria-expanded="true" aria-controls="faq10">
				        	<i class="icon icon-question-circle"></i> Que faire si la synchronisation ne fonctionne pas ?
				        </dt>
				        <dd id="faq10" class="panel-collapse collapse">
				            <p>
				                <ul>
									<li>Vérifier si le token de connexion est valide</li>
									<li>Vérifier que chaque produit éligible à Reverb est bien paramétrés</li>
									<li>Vérifier les logs dans l'onglet logs</li>
									<li>Si le problème persiste, veuillez contacter le <a href="https://reverb.com/fr/page/contact" target="_blank">support de Reverb</a></li>
								</ul>
				            </p>
				        </dd>
				    </dl>
                </div>
            </div>
        </div>
    </div>
</div>