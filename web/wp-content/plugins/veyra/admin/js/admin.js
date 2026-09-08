(function($) {
    'use strict';
    $(document).on('click', '.veyra-run-scan', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text(veyraAdmin.i18n.scanning);
        $.post(veyraAdmin.ajaxUrl, {
            action: 'veyra_run_scan',
            _wpnonce: veyraAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                $btn.prop('disabled', false).text('Run First Scan');
                alert(response.data.message || veyraAdmin.i18n.error);
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Run First Scan');
            alert(veyraAdmin.i18n.error);
        });
    });
})(jQuery);
