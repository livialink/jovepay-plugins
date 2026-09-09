<?php
/**
 * Direct file access prevention
 */
defined( 'ABSPATH' ) || exit;

class JPWC_Init {

    /**
     * @var
     *
     * @version 1.0.0
     * @since 1.0.0
     */
    private static $_instance;

    /**
     * Singleton
     * @return JPWC_Init
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public static function get_instance() {

        if( self::$_instance == null ) {
            self::$_instance = new self();
        }

        return self::$_instance;

    }

    /**
     * JPWC_Init constructor.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function __construct() {

        $this->validate();
        $this->add_plugin_row_notices();

    }

    /**
     * Meets requirements
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function validate() {

        if( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            $this->init();
        }
        else {
            add_action( 'admin_notices', array( $this, 'missing_wc' ) );
        }

    }

    /**
     * Shows Notice
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function missing_wc() {

        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php esc_html_e( 'In order to use JOVEpay for WooCommerce, make sure WooCommerce is installed and active.', 'jovepay-for-woocommerce' ); ?></p>
        </div>
        <?php

    }

    /**
     * Finally initialize the Plugin :)
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    private function init() {

		add_action( 'woocommerce_blocks_loaded', array( $this, 'checkout_block_support' ) );

        $this->includes();
        $this->hooks();

    }

    /**
     * Includes files
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function includes() {

        require 'class-gateway.php';
        require 'class-api.php';

    }

    /**
     * Action, Filter Hooks
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function hooks() {

        add_filter( 'plugin_row_meta', array( $this, 'plugin_row_meta' ), 10, 5 );
        add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_checkout_styles' ) );

    }

    /**
     * Enqueues checkout icon styles on classic and block checkout.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function enqueue_checkout_styles() {
        if ( ! function_exists( 'is_checkout' ) || ! is_checkout() ) {
            return;
        }

        wp_enqueue_style(
            'jpwc-checkout-icons',
            JPWC_PLUGIN_URL . 'assets/css/checkout-icons.css',
            array(),
            JPWC_VERSION
        );
    }

    /**
     * Add plugin row notices
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function add_plugin_row_notices() {

        add_action( 'after_plugin_row', array( $this, 'plugin_row_wc_requirement_notice' ), 10, 3 );

    }

    /**
     * Enqueues admin scripts on the gateway settings screen.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function admin_enqueue_scripts() {
        wp_enqueue_script(
            'jpwc-custom-scripts',
            JPWC_PLUGIN_URL . 'assets/js/scripts.js',
            array( 'jquery' ),
            JPWC_VERSION,
            true
        );

        wp_localize_script( 'jpwc-custom-scripts', 'jpwc', 
            array( 
                'images' => JPWC_PLUGIN_URL . 'assets/images/',
                'i18n' => array(
                    'howToSetup' => esc_html__( 'How to Setup?', 'jovepay-for-woocommerce' ),
                    'documentation' => esc_html__( 'Documentation', 'jovepay-for-woocommerce' ),                    
                ),
            ) 
        );
    }

    /**
     * Filter Callback
     *
     * @param $plugin_meta
     * @param $plugin_file
     * @param $plugin_data
     * @param $status
     * @since 1.0.0
     * @version 1.0.0
     */
    public function plugin_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ) {

        $plugin_basename = plugin_basename( JPWC_PLUGIN_FILE );
        
        if( $plugin_file === $plugin_basename ) {

            $plugin_meta[] = sprintf(
                '<a href="%s" style="color: green; font-weight: bold" target="_blank">%s</a>',
                esc_url( 'https://www.jovepay.com/docs/plugins/woocommerce?utm_source=jpwc' ),
                esc_html__( 'Docs', 'jovepay-for-woocommerce' )
            );

            $plugin_meta[] = sprintf(
                '<a href="%s" target="_blank">%s</a>',
                esc_url( 'mailto:dev@jovepay.com?subject=' . rawurlencode( esc_html__( 'JOVEpay for WooCommerce Plugin Support', 'jovepay-for-woocommerce' ) ) ),
                esc_html__( 'Mail Developer', 'jovepay-for-woocommerce' )
            );

        }

        return $plugin_meta;

    }

    /**
     * Shows WooCommerce requirement notice in plugin list
     *
     * @param string $plugin_file Plugin file path
     * @param array  $plugin_data Plugin data
     * @param string $status Plugin status
     * @since 1.0.0
     * @version 1.0.0
     */
    public function plugin_row_wc_requirement_notice( $plugin_file, $plugin_data, $status ) {

        $plugin_basename = plugin_basename( JPWC_PLUGIN_FILE );
        
        // Only show notice for our plugin
        if( $plugin_file !== $plugin_basename ) {
            return;
        }

        if( !function_exists( 'is_plugin_active' ) ) {
            include_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        if( !is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
            ?>
            <tr class="plugin-update-tr active">
                <td colspan="4" class="plugin-update colspanchange">
                    <div class="notice inline notice-warning notice-alt">
                        <p>
                            <strong><?php esc_html_e( 'WooCommerce Required', 'jovepay-for-woocommerce' ); ?>:</strong>
                            <?php esc_html_e( 'This plugin requires WooCommerce to be installed and activated.', 'jovepay-for-woocommerce' ); ?>
                            <?php
                            if( file_exists( WP_PLUGIN_DIR . '/woocommerce/woocommerce.php' ) ) {
                                $activate_url = wp_nonce_url( 'plugins.php?action=activate&plugin=woocommerce/woocommerce.php', 'activate-plugin_woocommerce/woocommerce.php' );
                                echo ' <a href="' . esc_url( $activate_url ) . '">' . esc_html__( 'Activate WooCommerce', 'jovepay-for-woocommerce' ) . '</a>';
                            } else {
                                $install_url = wp_nonce_url( self_admin_url( 'update.php?action=install-plugin&plugin=woocommerce' ), 'install-plugin_woocommerce' );
                                echo ' <a href="' . esc_url( $install_url ) . '">' . esc_html__( 'Install WooCommerce', 'jovepay-for-woocommerce' ) . '</a>';
                            }
                            ?>
                        </p>
                    </div>
                </td>
            </tr>
            <?php
        }

    }

    /**
     * Registers WooCommerce Blocks integration.
     *
     * @since 1.0.0
     * @version 1.0.0
     */
    public function checkout_block_support() {
		
		if ( class_exists( 'Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType' ) ) {
			
			require_once 'jovepay-gateway-block.php';
	
			add_action( 'woocommerce_blocks_payment_method_type_registration', array( $this, 'register_checkout_block' ) );
	
		}
	
	}

    /**
     * Registers the JOVEpay payment method with WooCommerce Blocks.
     *
     * @param object $payment_method_registry Payment method type registry.
     * @since 1.0.0
     * @version 1.0.0
     */
    public function register_checkout_block( $payment_method_registry ) {
		
		$payment_method_registry->register( new JovepayGatewayBlock );

	}
}
