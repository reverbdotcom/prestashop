<div class="panel">
    <table class="table" id="categories-mapping">
        <thead>
        <tr>
            <th>{l s='Prestashop Name' mod='reverb'}</th>
            <th>{l s='Reverb Name' mod='reverb'}</th>
            <th class="action-result"></th>
        </tr>
        </thead>
        <tbody>
        {if (isset($ps_categories))}
            {foreach from=$ps_categories item=ps_category key=ps_id}
                <tr>
                    <td>{$ps_category.name}</td>
                    <td>
                        <form method="post" action="">
                            <input type="hidden" name="ps_category_id" value="{$ps_id}">
                            <input type="hidden" name="mapping_id" value="{$ps_category.id_mapping}">
                            <select class="reverb-category" name="reverb_code" disabled="disabled">
                                <option value="" {if ($ps_category.reverb_code == '')}selected="selected"{/if}>--</option>
                                {foreach from=$reverb_categories item=reverb_category key=reverb_key}
                                    <option value="{$reverb_key}" {if ($ps_category.reverb_code == $reverb_key)}selected="selected"{/if}>{$reverb_category}</option>
                                {/foreach}
                            </select>
                            <input type="hidden" value="1" name="submitReverbModuleCategoryMapping">
                        </form>
                    </td>
                    <td>
                        <span class="icon icon-ok-circle"></span>
                        <span class="icon icon-remove-circle"></span>
                    </td>
                </tr>
            {/foreach}
        {/if}
        </tbody>
    </table>
</div>
<script type="text/javascript">
    $(document).ready(function() {

        $('.reverb-category')
            .removeAttr('disabled') // Remove disabled attribute
            .change(function() {

            form = $(this).parent('form');
            formData = form.serialize();

            select = $(this);
            select.attr('disabled', 'disabled');

            // Ajax call with secure token
            $.post('{$ajax_url}&action=CategoryMapping&ajax=true',
                formData,
                function (response) {
                    form.find("input[name='mapping_id']").val(response);
                    successSpan = form.parents('tr').find('.icon-ok-circle');
                    successSpan.fadeIn('slow', function() {
                        setTimeout(function() {
                            successSpan.fadeOut('slow');
                        }, 2000);
                    });
                }
            )
            .fail(function() {
                alert( "An error has occured. Please try again." );
                successSpan = form.parents('tr').find('.icon-remove-circle');
                successSpan.fadeIn('slow', function() {
                    setTimeout(function() {
                        successSpan.fadeOut('slow');
                    }, 2000);
                });
            })
            .always(function() {
                select.removeAttr('disabled');
            });
        });
    });
</script>