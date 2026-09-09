<?php
/**
 * Plugin Name: JOVEpay for WooCommerce
 * Plugin URI: https://www.jovepay.com/docs/plugins/woocommerce?utm_source=jpwc&utm_medium=plugin-uri
 * Description: Accept crypto with confidence using JOVEpay. Support USDT, USDC, ETH, BNB, LTC, and more—fast, flexible, and built for global commerce.
 * Version: 1.0.0
 * Author: JOVEpay
 * Author URI: https://www.jovepay.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: jovepay-for-woocommerce
 * Domain Path: /languages
 * Requires at least: 6.0
 * Requires PHP: 7.0
 * Requires Plugins: woocommerce
 * WC requires at least: 8.0
 * WC tested up to: 11.1.0
 * GitHub Plugin URI: https://github.com/livialink/jovepay-plugins
 * GitHub Branch: main
 */


defined( 'ABSPATH' ) || exit;


if ( ! defined( 'JPWC_PLUGIN_FILE' ) ) {
    define( 'JPWC_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'JPWC_VERSION' ) ) {
    define( 'JPWC_VERSION', '1.0.0' );
}

if ( ! defined( 'JPWC_PLUGIN_URL' ) ) {
    define( 'JPWC_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}
if ( ! defined( 'JPWC_PLUGIN_DIR_PATH' ) ) {
    define( 'JPWC_PLUGIN_DIR_PATH', plugin_dir_path( __FILE__ ) );
}

require dirname( JPWC_PLUGIN_FILE ) . '/includes/class-jpwc-init.php';

add_action( 'before_woocommerce_init', 'jpwc_declare_compatibility' );
add_action( 'plugins_loaded', 'jpwc_load' );


/**
 * Declares WooCommerce feature compatibility.
 *
 * @since 1.0.0
 * @version 1.0.0
 */
function jpwc_declare_compatibility() {
    if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', JPWC_PLUGIN_FILE, true );
        \Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'cart_checkout_blocks', JPWC_PLUGIN_FILE, true );
    }
}


/**
 * Loads Plugin
 *
 * @since 1.0.0
 * @version 1.0.0
 */
function jpwc_load() {
    JPWC_Init::get_instance();
}
