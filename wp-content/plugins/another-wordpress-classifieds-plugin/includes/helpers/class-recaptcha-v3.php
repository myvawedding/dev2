<?php
/**
 * @package AWPCP
 */

/**
 * Integration with reCAPTCHA v3.
 *
 * TODO: Let reCAPTCHA handle the overall process and provide delegates
 * for v2 and v3 integrations. This class should turn into one of those delegates.
 */
class AWPCP_reCAPTCHAv3 extends AWPCP_reCAPTCHA {

    /**
     * @since 3.9.4
     */
    protected function enqueue_scripts() {
        $url = 'https://www.google.com/recaptcha/api.js?onload={callback}&render={site_key}';
        $url = str_replace( '{callback}', 'AWPCPreCAPTCHAonLoadCallback', $url );
        $url = str_replace( '{site_key}', $this->site_key, $url );

        wp_enqueue_script( 'awpcp-recaptcha', $url, array( 'awpcp' ), 'v3', true );
    }

    /**
     * @since 3.9.4
     */
    protected function get_recaptcha_html( $site_key ) {
        $template  = '<div class="awpcp-recaptcha-action" data-name="awpcp_submit" data-sitekey="%s">';
        $template .= '<input type="hidden" name="awpcp_recaptcha_v3_response" />';
        $template .= '</div>';

        return sprintf( $template, $site_key );
    }

    /**
     * @since 3.9.4
     */
    protected function get_recaptcha_response() {
        return $this->request->post( 'awpcp_recaptcha_v3_response' );
    }

    /**
     * @since 3.9.4
     */
    protected function on_success( $response, &$error ) {
        $threshold = floatval( awpcp_get_option( 'recaptcha-v3-score-threshold', 0.5 ) );
        $score     = floatval( $response['score'] );

        if ( $score <= $threshold ) {
            $error = __( 'The current interaction was not approved by reCAPTCHA. Please try again.', 'another-wordpress-classifieds-plugin' );
            return false;
        }

        return true;
    }
}
