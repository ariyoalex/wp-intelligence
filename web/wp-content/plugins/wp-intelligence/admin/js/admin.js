(function($) {
    'use strict';
    $(document).on('click', '.wpi-run-scan', function(e) {
        e.preventDefault();
        var $btn = $(this);
        $btn.prop('disabled', true).text(wpiAdmin.i18n.scanning);
        $.post(wpiAdmin.ajaxUrl, {
            action: 'wpi_run_scan',
            _wpnonce: wpiAdmin.nonce
        }, function(response) {
            if (response.success) {
                location.reload();
            } else {
                $btn.prop('disabled', false).text('Run First Scan');
                alert(response.data.message || wpiAdmin.i18n.error);
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('Run First Scan');
            alert(wpiAdmin.i18n.error);
        });
    });
})(jQuery);
