# JOVEpay Payment Gateway for OpenCart

[Version](https://www.jovepay.com)
[OpenCart](https://www.opencart.com)
[PHP](https://www.php.net)

Accept cryptocurrency payments with confidence using JOVEpay. This OpenCart extension enables your store to accept 100+ cryptocurrencies including USDT, USDC, ETH, BNB, LTC, and many more.

## Features

- ✅ **100+ Cryptocurrencies Supported** - Accept Bitcoin, Ethereum, USDT, USDC, BNB, LTC, and many more
- ✅ **Instant Payouts** - Receive payments directly to your wallet or bank account
- ✅ **Secure IPN Verification** - HMAC signature verification for all payment notifications
- ✅ **Multiple Payment Statuses** - Comprehensive order status management (finished, partially paid, confirming, confirmed, sending, failed)
- ✅ **Easy Configuration** - Simple setup through OpenCart admin panel
- ✅ **24/7 Support** - Dedicated support team and personal account manager
- ✅ **Low Fees** - Competitive rates in the crypto payment market

## Requirements

- **OpenCart Version**: 3.0.0.0 or higher (tested up to 3.0.4.x)
- **PHP Version**: 7.1.0 or higher (PHP 8.0+ recommended)
- **Extensions Required**: 
  - cURL extension
  - JSON extension
  - OpenSSL extension

## Installation

### Method 1: Extension Installer (Recommended)

1. Download the extension package
2. Log in to your OpenCart admin panel
3. Navigate to **Extensions** → **Installer**
4. Upload the extension ZIP file
5. Navigate to **Extensions** → **Extensions** → **Payments**
6. Find **JOVEpay** in the list and click the **Install** button (green plus icon)
7. Click the **Edit** button (blue pencil icon) to configure the extension

### Method 2: Manual Installation

1. Extract the extension package
2. Upload all files from the `upload` folder to your OpenCart root directory, maintaining the directory structure:
  ```
   upload/
   ├── admin/
   └── catalog/
  ```
3. Log in to your OpenCart admin panel
4. Navigate to **Extensions** → **Extensions** → **Payments**
5. Find **JOVEpay** in the list and click the **Install** button
6. Click the **Edit** button to configure the extension

## Configuration

### Getting Your API Credentials

1. Sign up for a JOVEpay account at [https://www.jovepay.com](https://www.jovepay.com)
2. Log in to your JOVEpay merchant dashboard
3. Navigate to **Settings** → **API Keys**
4. Generate or copy your **API Key** and **IPN Secret**

### Configuring the Extension

1. In OpenCart admin, go to **Extensions** → **Extensions** → **Payments**
2. Find **JOVEpay** and click **Edit**
3. Fill in the required fields:
  **General Tab:**
  - **API Key** (Required): Your JOVEpay API key
  - **IPN Secret** (Required): Your JOVEpay IPN secret key
  - **E-Mail**: Email address to receive IPN error notifications (optional)
  - **Status**: Enable or disable the payment method
  - **Sort Order**: Display order in checkout (lower numbers appear first)
   **Order Status Tab:**
  - **Finished Status**: Order status when payment is fully completed
  - **Partially Paid Status**: Order status when payment is partially received
  - **Confirming Status**: Order status when payment is being confirmed
  - **Confirmed Status**: Order status when payment is confirmed
  - **Sending Status**: Order status when payment is being sent
  - **Failed Status**: Order status when payment fails
4. Click **Save** to apply changes

## Supported Cryptocurrencies

### Popular Coins

- Bitcoin (BTC)
- Ethereum (ETH)
- XRP (XRP)
- Tether (USDT - OMNI, ERC20, TRC20)
- Litecoin (LTC)
- Bitcoin Cash (BCH)
- Dogecoin (DOGE)
- Binance Coin (BNB)
- Solana (SOL)
- Cardano (ADA)
- And 90+ more...

### Stable Coins

- USD Coin (USDC)
- Binance USD (BUSD)
- DAI (DAI)
- TrueUSD (TUSD)
- Pax Dollar (USDP)
- Gemini Dollar (GUSD)

For a complete list of supported cryptocurrencies, visit [JOVEpay.com](https://www.jovepay.com)

## How It Works

1. **Customer Checkout**: Customer selects JOVEpay as payment method during checkout
2. **Payment Redirect**: Customer is redirected to JOVEpay payment page
3. **Cryptocurrency Payment**: Customer completes payment using their preferred cryptocurrency
4. **IPN Notification**: JOVEpay sends an Instant Payment Notification (IPN) to your store
5. **Order Update**: Order status is automatically updated based on payment status
6. **Order Confirmation**: Customer is redirected back to your store's success page

## IPN (Instant Payment Notification)

The extension uses secure HMAC signature verification for all IPN requests. The IPN callback URL is automatically generated and sent to JOVEpay during the payment process.

**IPN Endpoint**: `https://yourstore.com/index.php?route=extension/payment/jovepay/callback`

## Troubleshooting

### Payment Method Not Showing

- Ensure the extension is **Installed** and **Enabled** in Extensions → Extensions → Payments
- Check that your store currency is supported
- Verify that the minimum order total requirements are met

### IPN Not Working

- Verify your **IPN Secret** is correctly configured
- Check that your server can receive POST requests from JOVEpay servers
- Ensure your server's firewall allows incoming connections from JOVEpay
- Check OpenCart error logs for detailed error messages
- If email notifications are configured, check your email for IPN error reports

### Order Status Not Updating

- Verify API credentials are correct
- Check OpenCart order history for any error messages
- Ensure order statuses are properly configured in the extension settings
- Review OpenCart system logs for detailed error information

### Common Issues

**Issue**: "API key required!" error

- **Solution**: Ensure your API Key is correctly entered in the extension settings

**Issue**: "IPN Secret key required!" error

- **Solution**: Ensure your IPN Secret is correctly entered in the extension settings

**Issue**: HMAC signature verification fails

- **Solution**: Double-check your IPN Secret key matches the one in your JOVEpay dashboard

## Support

For technical support, documentation, and assistance:

- **Website**: [https://www.jovepay.com](https://www.jovepay.com)
- **Email**: [support@jovepay.com](mailto:support@jovepay.com)
- **Documentation**: [https://www.jovepay.com/docs](https://www.jovepay.com/docs)

## Security

- All IPN requests are verified using HMAC SHA-256 signatures
- API credentials are stored securely in OpenCart's configuration system
- Payment amounts and currencies are validated before order status updates
- The extension follows OpenCart security best practices

## Changelog

### Version 1.0.0 (Initial Release)

- Initial release
- Support for OpenCart 3.x
- Full IPN integration with HMAC verification
- Multiple payment status support
- Admin configuration interface
- Multi-currency support

## License

This extension is provided by JOVEpay. Please refer to JOVEpay's terms of service for licensing information.

## Credits

Developed by **JOVEpay**

- Website: [https://www.jovepay.com](https://www.jovepay.com)
- Support: [support@jovepay.com](mailto:support@jovepay.com)

---

**Note**: This extension requires an active JOVEpay merchant account. Sign up at [https://www.jovepay.com](https://www.jovepay.com) to get started.