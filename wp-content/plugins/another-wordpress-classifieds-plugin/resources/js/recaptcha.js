/*global AWPCP, grecaptcha*/
AWPCP.run('awpcp/init-recaptcha', ['jquery'],
function($) {
    var maxAttempts = 45,
        attempts = 0,
        maxDelay = 1500,
        timeout = false;

    var initReCaptcha = function() {
        if ( window['AWPCPGetReCaptchaResponse'] ) {
            return;
        }

        var $widgets = $( '.awpcp-recaptcha' );

        if ( $widgets.length ) {
            renderReCaptchaWidgets( $widgets );
        }

        var $actions = $( '.awpcp-recaptcha-action' );

        if ( $actions.length ) {
            executeReCaptchaAction( $actions );
        }
    };

    var renderReCaptchaWidgets = function( $widgets ) {
        $widgets.each( function() {
            var element = $( this );

            if ( ! element.data( 'awpcp-recaptcha' ) ) {
                grecaptcha.render( this, {
                  'sitekey' : element.attr( 'data-sitekey' ),
                  'theme' : 'light'
                } );

                element.data( 'awpcp-recaptcha', true );
            }
        } );

        window['AWPCPGetReCaptchaResponse'] = function( callback ) {
            callback();
        }
    };

    var executeReCaptchaAction = function( $actions ) {
        $action = $actions.eq( 0 );

        window['AWPCPGetReCaptchaResponse'] = function( callback ) {
            grecaptcha.execute( $action.data( 'sitekey' ), { action: $action.data( 'name' ) } ).then( function( token ) {
                $action.find( ':hidden' ).val( token );
                callback();
            } );
        }
    };

    var waitForReCaptchaToBeReady = function() {
        attempts = attempts + 1;

        if ( typeof grecaptcha !== 'undefined' && typeof grecaptcha.render !== 'undefined' ) {
            initReCaptcha();
        } else if ( attempts <= maxAttempts ) {
            timeout = setTimeout( waitForReCaptchaToBeReady, maxDelay * Math.pow( attempts / maxAttempts, 2 ) );
        }
    };

    window['AWPCPreCAPTCHAonLoadCallback'] = function() {
        if ( timeout ) {
            clearTimeout( timeout );
        }

        initReCaptcha();
    };

    $( waitForReCaptchaToBeReady );
});
