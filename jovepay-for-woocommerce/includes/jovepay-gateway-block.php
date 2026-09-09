<?php
defined( 'ABSPATH' ) || exit;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

/**
 * Jovepay Blocks integration
 *
 * @since 1.0.0
 * @version 1.0.0
 */
final class JovepayGatewayBlock extends AbstractPaymentMethodType {

	/**
	 * The gateway instance.
	 *
	 * @var JPWC_Gateway
	 */
	private $gateway;

	/**
	 * Payment method name/id/slug.
	 *
	 * @var string
	 */
	protected $name = 'jovepay';

	/**
	 * Initializes the payment method type.
	 */
	public function initialize() {
		$this->settings = get_option( 'woocommerce_jovepay_settings', [] );
		$this->gateway  = new JPWC_Gateway();
	}

	/**
	 * Returns if this payment method should be active. If false, the scripts will not be enqueued.
	 *
	 * @return boolean
	 */
	public function is_active() {
		return $this->gateway->is_available();
	}

	/**
	 * Returns an array of scripts/handles to be registered for this payment method.
	 *
	 * @return array
	 */
	public function get_payment_method_script_handles() {

		$script_path       = '/assets/blocks/frontend/blocks.js';
		$script_asset_path = JPWC_PLUGIN_DIR_PATH . 'assets/blocks/frontend/blocks.asset.php';
		$script_asset      = file_exists( $script_asset_path )
			? require( $script_asset_path )
			: array(
				'dependencies' => array(),
				'version'      => JPWC_VERSION
			);
		$script_url        = JPWC_PLUGIN_URL . $script_path;

		wp_register_style(
			'jpwc-checkout-icons',
			JPWC_PLUGIN_URL . 'assets/css/checkout-icons.css',
			array(),
			JPWC_VERSION
		);
		wp_enqueue_style( 'jpwc-checkout-icons' );

		wp_register_script(
			'jpwc-checkout-block',
			$script_url,
			$script_asset[ 'dependencies' ],
			$script_asset[ 'version' ],
			true
		);

		return [ 'jpwc-checkout-block' ];
	}

	/**
	 * Returns an array of key=>value pairs of data made available to the payment methods script.
	 *
	 * @return array
	 */
	public function get_payment_method_data() {
		return [
			'title'       => $this->get_setting( 'title' ),
			'description' => $this->get_setting( 'description' ),
			'icon'        => $this->gateway->icon,
			'icons'       => $this->gateway->get_checkout_coin_icons(),
			'supports'    => array_filter( $this->gateway->supports, [ $this->gateway, 'supports' ] )
		];
	}
}