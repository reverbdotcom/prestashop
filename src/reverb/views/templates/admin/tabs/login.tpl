{**
*
*
* @author Johan Protin
* @copyright Copyright (c) 2017 - Johan Protin
* @license Apache License Version 2.0, January 2004
* @package Reverb
*}
{$reverb_login_form}

<script type="text/javascript">
    function changeReverbUrl(field) {
        $('#token_production').parent().parent().hide();
        $('#token_sandbox').parent().parent().hide();
        if ($(field).attr('id') == 'sandbox_mode_off') {
            if ($(field).is(":checked")) {
                $('#token_production').parent().parent().show();
            }else{
                $('#token_sandbox').parent().parent().show();
            }
        }else{
            if ($(field).is( ":checked" )) {
                $('#token_sandbox').parent().parent().show();
            }else{
                $('#token_production').parent().parent().show();
            }
        }
    }

    $('input[name="sandbox_mode"]').change(function () {
        changeReverbUrl(this)
    });

    changeReverbUrl($('input[id="sandbox_mode_off"]'));
</script>