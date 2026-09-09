<?php
/** Direct file access prevention */
defined('ABSPATH') || exit;

class JPWC_Gateway extends WC_Payment_Gateway
{
    /**
     * JPWC_Gateway constructor.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct()
    {
        $this->id = 'jovepay';
        $this->title = $this->get_option('title');
        $this->icon = apply_filters('jpwc_icon', JPWC_PLUGIN_URL . 'assets/images/icon.svg');
        $this->has_fields = false;
        $this->method_title = __('JOVEpay', 'jovepay-for-woocommerce');
        $this->description = $this->get_option('description');
        $this->method_description = __('Allows customer to checkout with +100 cryptocurrencies.', 'jovepay-for-woocommerce');
        $this->supports = array('products');
        $this->init_form_fields();
        $this->init_settings();

        add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
        add_action('woocommerce_api_jpwc_gateway', array($this, 'ipn_callback'));
        add_filter('woocommerce_gateway_title', array($this, 'prefix_checkout_title_with_logo'), 10, 2);
    }

    /**
     * Crypto coin icons shown beside the gateway logo at checkout.
     *
     * @return array<int, array{id: string, src: string, alt: string}>
     * @since 1.0.0
     * @version 1.0.0
     */
    public function get_checkout_coin_icons()
    {
        $base = JPWC_PLUGIN_URL . 'assets/images/';

        $icons = array(
            array(
                'id'  => 'btc',
                'src' => $base . 'btc.png',
                'alt' => 'Bitcoin',
            ),
            array(
                'id'  => 'eth',
                'src' => $base . 'eth.png',
                'alt' => 'Ethereum',
            ),
            array(
                'id'  => 'usdt',
                'src' => $base . 'usdt.png',
                'alt' => 'Tether',
            ),
            array(
                'id'  => 'bnb',
                'src' => $base . 'bnb.png',
                'alt' => 'BNB',
            ),
        );

        /**
         * Filter the coin icons shown next to JOVEpay at checkout.
         *
         * @param array $icons List of icon definitions.
         */
        return apply_filters('jpwc_checkout_coin_icons', $icons);
    }

    /**
     * Prefix the payment method title with the JOVEpay logo on classic checkout.
     *
     * WooCommerce renders title then icon; this puts the logo before the label text.
     *
     * @param string $title Gateway title.
     * @param string $id    Gateway id.
     * @return string
     * @since 1.0.0
     * @version 1.0.0
     */
    public function prefix_checkout_title_with_logo($title, $id)
    {
        if ($id !== $this->id) {
            return $title;
        }

        // Keep admin settings (and non-checkout screens) as plain text.
        if (is_admin() && !wp_doing_ajax()) {
            return $title;
        }

        if (!function_exists('is_checkout') || !is_checkout()) {
            return $title;
        }

        $logo_url = WC_HTTPS::force_https_url($this->icon);

        return sprintf(
            '<span class="jpwc-payment-method-label"><img class="jpwc-payment-icons__logo" src="%1$s" alt="" /><span class="jpwc-payment-method-label__text">%2$s</span></span>',
            esc_url($logo_url),
            esc_html($title)
        );
    }

    /**
     * Overlapping coin stack for classic checkout (shown after the titled logo+label).
     *
     * @return string
     * @since 1.0.0
     * @version 1.0.0
     */
    public function get_icon()
    {
        $coins = $this->get_checkout_coin_icons();

        if (empty($coins)) {
            return apply_filters('jpwc_gateway_icon', '', $this->id);
        }

        $html  = '<span class="jpwc-payment-icons" aria-hidden="true">';
        $html .= '<span class="jpwc-payment-icons__stack">';
        $total = count($coins);

        foreach ($coins as $index => $coin) {
            $z_index = $total - $index;
            $html   .= sprintf(
                '<img class="jpwc-payment-icons__coin" src="%1$s" alt="%2$s" style="z-index:%3$d" />',
                esc_url(WC_HTTPS::force_https_url($coin['src'])),
                esc_attr($coin['alt']),
                absint($z_index)
            );
        }

        $html .= '</span></span>';

        return apply_filters('jpwc_gateway_icon', $html, $this->id);
    }

    /**
     * Output admin settings with a WordPress nonce on the gateway form.
     *
     * WooCommerce already wraps this screen in a settings form; the nonce is
     * added here so this gateway verifies its own save action independently.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function admin_options()
    {
        wp_nonce_field('jpwc_update_gateway_settings', 'jpwc_gateway_nonce', false);
        parent::admin_options();
    }

    /**
     * Admin form fields
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function init_form_fields()
    {
        $this->form_fields = array(
            'enabled' => array(
                'title' => __('Enable/Disable', 'jovepay-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable JOVEpay', 'jovepay-for-woocommerce'),
                'default' => 'no'
            ),
            'isTestnet' => array(
                'title' => __('Testnet', 'jovepay-for-woocommerce'),
                'type' => 'checkbox',
                'label' => __('Enable Testnet', 'jovepay-for-woocommerce'),
                'default' => 'no'
            ),
            'theme' => array(
                'title' => __('Theme', 'jovepay-for-woocommerce'),
                'type' => 'select',
                'options' => array(
                    'light' => __('Light', 'jovepay-for-woocommerce'),
                    'dark' => __('Dark', 'jovepay-for-woocommerce'),
                ),
                'default' => 'light',
            ),
            'title' => array(
                'title' => __('Title', 'jovepay-for-woocommerce'),
                'type' => 'text',
                'default' => __('JOVEpay', 'jovepay-for-woocommerce'),
                'desc_tip' => true,
                'description' => __('Title for JOVEpay', 'jovepay-for-woocommerce'),
            ),
            'description' => array(
                'title' => __('Pay with Crypto', 'jovepay-for-woocommerce'),
                'type' => 'text',
                'default' => __('Pay with Crypto', 'jovepay-for-woocommerce'),
                'desc_tip' => true,
                'description' => __('Add a new description for JOVEpay Gateway, Customers will see at checkout.', 'jovepay-for-woocommerce'),
            ),
            'api_key' => array(
                'title' => __('API Key', 'jovepay-for-woocommerce'),
                'type' => 'password',
                'description' => sprintf(
                    /* translators: %s: link to JOVEpay API settings */
                    __('Get your API: %s', 'jovepay-for-woocommerce'),
                    '<a href="' . esc_url('https://app.jovepay.com/en/payments-settings#api') . '" target="_blank">' . esc_html__('here', 'jovepay-for-woocommerce') . '</a>'
                ),
            ),
            'ipn_key' => array(
                'title' => __('IPN Secret Key', 'jovepay-for-woocommerce'),
                'type' => 'text',
                'description' => sprintf(
                    /* translators: %s: link to JOVEpay webhook settings */
                    __('Get your IPN Secret Key: %s', 'jovepay-for-woocommerce'),
                    '<a href="' . esc_url('https://app.jovepay.com/en/payments-settings#webhooks') . '" target="_blank">' . esc_html__('here', 'jovepay-for-woocommerce') . '</a>'
                ),
            ),
            'webhook_url' => array(
                'title' => __('Webhook URL', 'jovepay-for-woocommerce'),
                'type' => 'text',
                'default' => add_query_arg('wc-api', 'JPWC_Gateway', home_url('/')),
                'custom_attributes' => array('readonly' => 'readonly')
            )
        );
    }

    /**
     * Process Admin Settings | Validate
     *
     * @return bool|void
     * @since 1.0.0
     * @version 1.0.0
     */
    public function process_admin_options()
    {
        if (!current_user_can('manage_woocommerce')) {
            return false;
        }

        check_admin_referer('jpwc_update_gateway_settings', 'jpwc_gateway_nonce');

        parent::process_admin_options();

        if (empty($this->get_option('api_key'))) {
            WC_Admin_Settings::add_error(esc_html__('Error: API Key is required.', 'jovepay-for-woocommerce'));
            return false;
        }
    }

    /**
     * Process the payment by redirecting to JOVEpay checkout.
     *
     * @param int $order_id
     * @return array|void
     * @since 1.0.0
     * @version 1.0.0
     */
    public function process_payment($order_id)
    {
        $order = wc_get_order($order_id);
        $api_key = $this->get_option('api_key');

        return $this->off_site_checkout($api_key, $order);
    }

    /**
     * Off-Site checkout
     *
     * @param $api_key
     * @param $order
     * @return array
     * @since 1.0.0
     * @version 1.0.0
     */
    public function off_site_checkout($api_key, $order)
    {
        $order_id = $order->get_id();

        $parameters = array(
            'dataSource' => 'woocommerce',
            'ipnCallbackUrl' => esc_url_raw($this->get_option('webhook_url')),
            'isTestnet' => $this->get_option('isTestnet') == 'yes' ? true : false,
            'priceCurrency' => sanitize_text_field($order->get_currency()),
            'successUrl' => esc_url_raw($this->get_return_url($order)),
            'cancelUrl' => esc_url_raw($order->get_cancel_order_url_raw()),
            'orderId' => absint($order_id),
            'customerName' => sanitize_text_field($order->get_billing_first_name()),
            'customerEmail' => sanitize_email($order->get_billing_email()),
            'priceAmount' => number_format((float) $order->get_total(), 8, '.', ''),
        );

        $order_items = $order->get_items();
        $items = array();

        foreach ($order_items as $item) {
            $items[] = $item->get_data();
        }

        $parameters['products'] = $items;
        $parameters = apply_filters('jpwc_checkout_parameters', $parameters);

        $locale = get_locale();
        $language = strtolower(substr($locale, 0, 2));

        $jovepay = new JPWC_API($api_key);
        $redirect_url = $jovepay->off_page_checkout($parameters, $this->get_option('theme'), $language);

        return array(
            'result' => 'success',
            'redirect' => $redirect_url
        );
    }

    /**
     * Validates the IPN request signature.
     *
     * @param string $raw_request Raw request body.
     * @return bool
     * @since 1.0.0
     * @version 1.0.0
     */
    private function checkIpnRequestIsValid($raw_request): bool
    {
        // Get the signature from the headers
        $signature = isset($_SERVER['HTTP_X_JOVEPAY_SIG'])
            ? sanitize_text_field(wp_unslash($_SERVER['HTTP_X_JOVEPAY_SIG']))
            : '';

        if (!$signature) {
            wc_get_logger()->error('Missing x-jovepay-sig header');
            return false;
        }

        if (!$raw_request) {
            wc_get_logger()->error('Empty php://input');
            return false;
        }

        // Compute HMAC with your secret key
        $secret = $this->get_option('ipn_key');
        $hmac = hash_hmac('sha256', $raw_request, $secret);

        // Compare HMAC with header signature securely
        if (!hash_equals($hmac, trim($signature))) {
            wc_get_logger()->error('Invalid signature');
            return false;
        }

        return true;
    }

    /**
     * Webhook Catcher | action_hook callback
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function ipn_callback()
    {
        $raw_request = file_get_contents('php://input');

        if (!$this->checkIpnRequestIsValid($raw_request)) {
            wp_send_json_error(['message' => 'Invalid signature'], 403);
        }

        $request = json_decode($raw_request, true);

        // Invalid Call, Order ID doesn't exist.
        if (!is_array($request) || !array_key_exists('orderId', $request)) {
            wp_send_json_error(['message' => 'Invalid Call'], 400);
        }

        // Sanitize order ID
        $order_id = absint($request['orderId']);
        if (!$order_id) {
            wp_send_json_error(['message' => 'Invalid Order ID'], 400);
        }

        $order = wc_get_order($order_id);

        if (!$order) {
            wp_send_json_error(['message' => 'Order not found'], 400);
        }
        // completed order can be refunded.
        $hasReceivedPayment = $order->has_status(array('completed', 'processing'));
        if(($hasReceivedPayment && $request['paymentStatus'] != 'refunded') || $order->has_status(array('refunded','failed'))) {
            wp_send_json_error(['message' => 'Order already processed'], 400);
        }
        // finished - the funds have reached your personal address and the payment is finished.
        if (isset($request['paymentStatus']) && $request['paymentStatus'] == 'finished') {
            $order->update_status('processing', esc_html__('JOVEpay finished IPN Call.', 'jovepay-for-woocommerce'));
        }
        if (isset($request['paymentStatus']) && $request['paymentStatus'] == 'confirmed') {
            $order->update_status('on-hold', esc_html__('JOVEpay confirmed IPN Call.', 'jovepay-for-woocommerce'));
        }
        // refunded - the funds were refunded back to the user.
        if (isset($request['paymentStatus']) && $request['paymentStatus'] == 'refunded') {
            $order->update_status('refunded', esc_html__('JOVEpay refunded IPN Call.', 'jovepay-for-woocommerce'));
        }

        // failed - the payment wasn't completed due to the error of some kind.
        if (isset($request['paymentStatus']) && $request['paymentStatus'] == 'failed') {
            $order->update_status('failed', esc_html__('JOVEpay failed IPN Call.', 'jovepay-for-woocommerce'));
        }

        wp_send_json_success(['message' => 'OK']);
    }
}

/**
 * Adds Gateway into WooCommerce
 *
 * @param array $gateways Array of payment gateway classes.
 * @return array Modified array of payment gateway classes.
 * @since 1.0.0
 * @version 1.0.0
 */
function jpwc_add_gateway_to_wc($gateways)
{
    $gateways[] = 'JPWC_Gateway';
    return $gateways;
}

add_filter('woocommerce_payment_gateways', 'jpwc_add_gateway_to_wc');
