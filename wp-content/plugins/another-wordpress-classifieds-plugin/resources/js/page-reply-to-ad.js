(function($, undefined) {

    var AWPCP = jQuery.AWPCP = jQuery.extend({}, jQuery.AWPCP, AWPCP);

    $(function() {
        $.AWPCP.validate();

        var container = $('.awpcp-reply-to-ad'), form;

        // Reply to Ad form
        form = container.find('.awpcp-reply-to-ad-form');
        if (form.length) {
            form.validate({
                messages: $.AWPCP.l10n('page-reply-to-ad'),
                submitHandler: function( form ) {
                    if ( window['AWPCPGetReCaptchaResponse'] ) {
                        window['AWPCPGetReCaptchaResponse']( function() {
                            form.submit();
                        } );
                    } else {
                        form.submit();
                    }
                }
            });
        }
    });

})(jQuery);
