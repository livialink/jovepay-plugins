<?php
/**
 * Direct file access prevention
 */
defined( 'ABSPATH' ) || exit;

class JPWC_API {

    /**
     * Endpoint
     *
     * @var string
     * @since 1.0.0
     * @version 1.0.0
     */
    public $endpoint;


    /**
     * API key
     *
     * @var string
     * @since 1.0.0
     * @version 1.0.0
     */
    private $api_key;

    /**
     * JPWC_API constructor.
     * @param string $api_key
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct( $api_key ) {
        $this->api_key = $api_key;

        $this->endpoint = 'https://www.jovepay.com/pay';
    }

    /**
     * Ready the url to process off-page checkout
     *
     * @param array  $parameters
     * @param string $theme
     * @param string $language
     * @return string
     * @version 1.0.0
     * @since 1.0.0
     */
    public function off_page_checkout( $parameters = array(), $theme = 'light', $language = 'en' ) {

        $parameters['apiKey'] = $this->api_key;
        $parameters = urlencode( base64_encode( wp_json_encode( $parameters ) ) );
        $theme = sanitize_text_field( $theme );
        $language = sanitize_text_field( $language );
        $redirect_url = "{$this->endpoint}/payment?params={$parameters}&theme={$theme}&locale={$language}";

        return esc_url_raw( $redirect_url );
    }
}
