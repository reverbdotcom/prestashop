{**
*
*
* @author Johan Protin
* @copyright Copyright (c) 2017 - Johan Protin
* @license Apache License Version 2.0, January 2004
* @package Reverb
*}
{$reverb_mapping_categories}

<!-- Modal pre edit confirmation -->
<div class="modal fade" id="child-categories-modal" tabindex="-1" role="dialog" aria-labelledby="mappingChildCategory" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body" >
                <h3 class="modal-title" id="mappingChildCategory">{l s='Category:' mod='reverb'} <span id="category-name-parent">XXXXXXXX</span></h3>
                <table id="datatables-categories" class="table display" width="100%">
                    <thead>
                        <tr>
                            <th><strong>{l s='Prestashop Name'}</strong></th>
                            <th><strong>{l s='Reverb Name'}</strong></th>
                        </tr>
                    </thead>
                    <tfoot>
                    <tr>
                        <th>{l s='Prestashop Name'}</th>
                        <th>{l s='Reverb Name'}</th>
                    </tr>
                    </tfoot>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">{l s='Cancel'  mod='reverb'}</button>
            </div>
        </div>
    </div>
</div>

<script type="text/javascript">
    function loadChildCategories(param) {
        // Call ajax child-categories
        $.post('{$ajax_url}&action=ChildCategories&ajax=true', { 'category_parent':param }, function (response) {
            var result = JSON.parse(response);
            if (result.status === 'error') {
            } else {
                $.extend( true, $.fn.dataTable.defaults, {
                    "searching": false,
                    "ordering":false
                } );
                $('#datatables-categories').DataTable().destroy();
                $('#datatables-categories').DataTable({
                    data:result.data,
                    "columns":[
                        {
                            "render": function (data, type, row) {
                                return row.name;
                            }
                        },
                        {
                            "render": function(data, type, row){
                                var $select = $("<select></select>", {
                                    "name":"reverb_code",
                                    "class":"reverb-category",
                                    "data-mapping-id": row.id_mapping,
                                    "data-ps-category-id": row.ps_category_id,
                                    "onchange":'updateMappingReverb($(this));'
                                });
                                $.each(result.reverb_categories, function(k,v){
                                    var $option = $("<option></option>", {
                                        "text": v.name,
                                        "value": v.code
                                    });
                                    if(row.reverb_code === v.code){
                                        $option.attr("selected", "selected");
                                    }
                                    $select.append($option);
                                });
                                return $select.prop("outerHTML");
                            }
                        }
                    ]
                });
                // show the popup with datatables
                $('#child-categories-modal').modal('show');
                // title of the popup
                $('#category-name-parent').text(result.category_parent.id + ' - ' + result.category_parent.name);
            }
        }).fail(function (error) {
            //TODO
        });

    }

    function updateMappingReverb(select) {
        select.attr('disabled', 'disabled');

        var ps_category_id = select.data('ps-category-id');
        var reverb_code = select.val();
        var mapping_id = select.data('mapping-id');

        // Ajax call with secure token
        $.post('{$ajax_url}&action=CategoryMapping&ajax=true', {
                    'ps_category_id': ps_category_id,
                    'reverb_code': reverb_code,
                    'mapping_id': mapping_id,
                }, function (response) {
                    select.attr("data-mapping-id", response);
                    showSuccessMessage("{l s='Mapping category updated' mod='reverb'}");
                    var successSpan = select.parent('td').find('.icon-ok-circle');
                    successSpan.fadeIn('slow', function() {
                        setTimeout(function() {
                            successSpan.fadeOut('slow');
                        }, 2000);
                    });
                }
        )
        .fail(function() {
            showErrorMessage("{l s='An error has occured. Please try again' mod='reverb'}");
            var successSpan = select.parent('td').find('.icon-remove-circle');
            successSpan.fadeIn('slow', function() {
                setTimeout(function() {
                    successSpan.fadeOut('slow');
                }, 2000);
            });
        })
        .always(function() {
            select.removeAttr('disabled');
        });
    }

    $(document).ready(function() {
        $('.reverb-category')
            .removeAttr('disabled') // Remove disabled attribute
            .change(function() {
                updateMappingReverb($(this));
            });
    });
</script>