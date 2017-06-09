{**
*
*
* @author Johan Protin
* @copyright Copyright (c) 2017 - Johan Protin
* @license Apache License Version 2.0, January 2004
* @package Reverb
*}
{if (isset($errors)) && (count($errors) > 0)}
    <div class="alert alert-danger">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4>{l s='Error!' mod='reverb'}</h4>
        <ul class="list-unstyled">
            {foreach from=$errors item='message'}
                <li>{$message|escape:'html':'UTF-8'}</li>
            {/foreach}
        </ul>
    </div>
{/if}

{if (isset($infos)) && (count($infos) > 0)}
    <div class="alert alert-warning">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4>{l s='Notice!' mod='reverb'}</h4>
        <ul class="list-unstyled">
            {foreach from=$infos item='message'}
                <li>{$message|escape:'html':'UTF-8'}</li>
            {/foreach}
        </ul>
    </div>
{/if}

{if (isset($successes)) && (count($successes) > 0)}
    <div class="alert alert-success">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        <h4>{l s='Success!' mod='reverb'}</h4>
        <ul class="list-unstyled">
            {foreach from=$successes item='message'}
                <li>{$message|escape:'html':'UTF-8'}</li>
            {/foreach}
        </ul>
    </div>
{/if}