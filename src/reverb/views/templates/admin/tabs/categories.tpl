{$reverb_mapping_categories}

<script type="text/javascript">
    $(document).ready(function() {

        $('.reverb-category')
            .removeAttr('disabled') // Remove disabled attribute
            .change(function() {

                select = $(this);
                select.attr('disabled', 'disabled');

                ps_category_id = select.data('ps-category-id');
                reverb_code = select.val();
                mapping_id = select.data('mapping-id');

                // Ajax call with secure token
                $.post('{$ajax_url}&action=CategoryMapping&ajax=true', {
                        'ps_category_id': ps_category_id,
                        'reverb_code': reverb_code,
                        'mapping_id': mapping_id,
                    }, function (response) {
                        select.attr("data-mapping-id", response);
                        showSuccessMessage("{l s='Mapping category updated' mod='reverb'}");
                        successSpan = select.parent('td').find('.icon-ok-circle');
                        successSpan.fadeIn('slow', function() {
                            setTimeout(function() {
                                successSpan.fadeOut('slow');
                            }, 2000);
                        });
                    }
                )
                .fail(function() {
                    showErrorMessage("{l s='An error has occured. Please try again' mod='reverb'}");
                    successSpan = select.parent('td').find('.icon-remove-circle');
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