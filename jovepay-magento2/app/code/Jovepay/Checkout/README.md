# JOVEpay for Magento / Adobe Commerce

Accept cryptocurrency payments through [JOVEpay](https://www.jovepay.com) on Magento Open Source and Adobe Commerce storefronts.

## Compatibility

- Magento Open Source / Adobe Commerce 2.4.x
- PHP 8.1 – 8.4

## Features

- Hosted JOVEpay checkout redirect
- HMAC-signed Instant Payment Notifications (IPN)
- Automatic invoice creation on successful payment
- Configurable order statuses and testnet mode
- Optional debug logging to `var/log/jovepay.log`

## Installation (Composer / Marketplace)

```bash
composer require jovepay/magento2
php bin/magento module:enable Jovepay_Checkout
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Installation (manual / app/code)

1. Copy the `Jovepay/Checkout` module into `app/code/Jovepay/Checkout`.
2. Run:

```bash
php bin/magento module:enable Jovepay_Checkout
php bin/magento setup:upgrade
php bin/magento setup:di:compile
php bin/magento setup:static-content:deploy -f
php bin/magento cache:flush
```

## Configuration

1. In Admin go to **Stores → Configuration → Sales → Payment Methods → JOVEpay**.
2. Enable the method.
3. Enter your JOVEpay **Merchant ID** and **IPN Secret** (from jovepay.com → Payment Settings).
4. Choose new/paid order statuses and save.

## Marketplace package

Zip the module root (the folder that contains `composer.json` and `registration.php`), not the Magento project root:

```bash
cd app/code/Jovepay/Checkout
zip -r jovepay-checkout-1.0.0.zip . \
  -x "*.DS_Store" \
  -x "__MACOSX*" \
  -x "*.git*"
```

Validate locally before submission:

```bash
phpcs --standard=Magento2 --extensions=php,phtml --error-severity=10 --ignore-annotations app/code/Jovepay/Checkout
php bin/magento deploy:mode:set production
```

## Support

- Email: dev@jovepay.com
- Website: https://www.jovepay.com

## License
