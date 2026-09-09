=== JOVEpay for WooCommerce ===
Contributors: jovepay
Tags: woocommerce, cryptocurrency, bitcoin, ethereum, payments
Requires at least: 6.0
Tested up to: 7.1
Stable tag: 1.0.0
Requires PHP: 7.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Accept 100+ cryptocurrencies at WooCommerce checkout with settlement to your wallet via JOVEpay.

== Description ==

JOVEpay for WooCommerce adds a cryptocurrency payment gateway to your WooCommerce store. Customers pay with crypto on the JOVEpay hosted checkout; your store receives order status updates through secure webhooks (IPN).

A free [JOVEpay merchant account](https://app.jovepay.com/) is required. Payment processing is handled by JOVEpay (an external service). See the [JOVEpay documentation](https://www.jovepay.com/docs/plugins/woocommerce) for setup details, and review JOVEpay’s terms and privacy policy on [jovepay.com](https://www.jovepay.com/).

= Features =

* Accept 100+ cryptocurrencies at checkout.
* Redirect customers to JOVEpay’s hosted payment page (light or dark theme).
* Automatic order status updates via signed IPN/webhooks.
* Testnet mode for safe end-to-end testing.
* Works with classic WooCommerce checkout and Cart/Checkout Blocks.
* Compatible with WooCommerce High-Performance Order Storage (HPOS).

= Demo =

Try the live demo store: [woocommerce.jovepay.com](https://woocommerce.jovepay.com/). Sign in to the [JOVEpay dashboard](https://app.jovepay.com/) to create API credentials for your own store.

= Requirements =

* WordPress 6.0 or higher
* WooCommerce 8.0 or higher
* PHP 7.0 or higher
* A JOVEpay merchant account (API Key and IPN Secret Key)

= Popular coins =

* Bitcoin (BTC)
* Ethereum (ETH)
* BNB Smart Chain (BEP20)
* Litecoin (LTC)
* Bitcoin Cash (BCH)
* Dogecoin (DOGE)
* Solana (SOL)
* Cardano (ADA)
* Polygon (POL)
* Arbitrum (ARB)
* Optimism (OP)
* and more

= Stablecoins =

* Tether (USDT)
* USD Coin (USDC)
* DAI
* TrueUSD (TUSD)
* PayPal USD (PYUSD)
* Global Dollar (USDG)
* EURC

Coin availability depends on your JOVEpay account and network settings. For coin requests or support, contact [info@jovepay.com](mailto:info@jovepay.com).

== Installation ==

= Automatic installation =

1. In your WordPress admin, go to Plugins → Add New.
2. Search for “JOVEpay for WooCommerce”.
3. Click Install Now, then Activate.
4. Continue with the configuration steps below.

= Manual installation =

1. Download the plugin zip file.
2. In WordPress admin, go to Plugins → Add New → Upload Plugin.
3. Choose the zip file, click Install Now, then Activate.
4. Continue with the configuration steps below.

= Configuration =

1. Create or sign in to your [JOVEpay account](https://app.jovepay.com/).
2. Copy your API Key from [Payment Settings → API](https://app.jovepay.com/en/payments-settings#api).
3. Copy your IPN Secret Key from [Payment Settings → Webhooks](https://app.jovepay.com/en/payments-settings#webhooks).
4. In WordPress, go to WooCommerce → Settings → Payments.
5. Enable JOVEpay and open its settings.
6. Paste the API Key and IPN Secret Key, choose theme and testnet options if needed, then save.
7. Copy the Webhook URL shown in the plugin settings into your JOVEpay webhook configuration (if required by your account).
8. Place a test order to confirm checkout and order status updates.

Full guide: [JOVEpay WooCommerce documentation](https://www.jovepay.com/docs/plugins/woocommerce).

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. WooCommerce must be installed and activated. The plugin shows an admin notice if WooCommerce is missing.

= Do I need a JOVEpay account? =

Yes. You need API and IPN credentials from [app.jovepay.com](https://app.jovepay.com/).

= Does it support the WooCommerce Cart and Checkout Blocks? =

Yes. The gateway registers with WooCommerce Blocks and also works on classic shortcode checkout.

= Is High-Performance Order Storage (HPOS) supported? =

Yes. Compatibility with custom order tables (HPOS) is declared.

= What happens after a customer pays? =

The customer is redirected to JOVEpay to complete payment. JOVEpay then calls your store’s webhook URL with a signed IPN. The plugin verifies the signature and updates the order status (for example processing, on-hold, refunded, or failed).

= Can I test without real funds? =

Yes. Enable Testnet in the gateway settings and use JOVEpay’s test environment.

= Where can I get help? =

* Documentation: https://www.jovepay.com/docs/plugins/woocommerce
* Support email: info@jovepay.com
* After the plugin is listed on WordPress.org, use the official plugin support forum.

== Screenshots ==

1. JOVEpay payment method shown on WooCommerce checkout.
2. JOVEpay gateway settings (API Key, theme, testnet).
3. JOVEpay gateway settings (IPN Secret).
4. Customer paying with cryptocurrency on the JOVEpay hosted checkout.

== Source code and development ==

Human-readable source for compiled JavaScript ships with this plugin and is also published publicly.

* Public repository: https://github.com/livialink/jovepay-plugins/tree/main/jovepay-for-woocommerce
* Blocks payment-method source: `resources/js/frontend/index.js`
* Compiled output: `build/blocks/frontend/blocks.js`

Build tools (`package.json`, `webpack.config.js`) are included so the compiled assets can be regenerated.

= Rebuild the Blocks script =

1. Install Node.js 18 or newer.
2. From the plugin directory, run `npm install`.
3. Run `npm run build`.

This uses `@wordpress/scripts` and `@woocommerce/dependency-extraction-webpack-plugin` to compile `resources/js/frontend/index.js` into `build/blocks/frontend/blocks.js`.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
Initial release of JOVEpay for WooCommerce.
