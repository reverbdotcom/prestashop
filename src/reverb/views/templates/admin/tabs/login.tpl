{$reverb_login_form}

<script type="text/javascript">
    function changeReverbUrl(id)
    {
        if (id == 'sandbox_mode_off') {
            $('#reverb-url-help')
                .attr('href', '{$reverb_url_prod}')
                .html('{$reverb_url_prod}');
        } else {
            $('#reverb-url-help')
                .attr('href', '{$reverb_url_sandbox}')
                .html('{$reverb_url_sandbox}');
        }
    }

    $('input[name="sandbox_mode"]').change(function () {
        changeReverbUrl($(this).attr('id'))
    });

    changeReverbUrl($('input[name="sandbox_mode"]').attr('id'));
</script>