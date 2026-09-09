# JOVEpay WHMCS Payment Gateway

Third-party payment gateway module for WHMCS 8.x.

## Requirements

- WHMCS 8.0 or later
- PHP 7.2 or later (match your WHMCS installation requirements)
- A JOVEpay account with API key and IPN secret

## Installation

1. Copy the contents of `modules/` into your WHMCS root directory so you have:

   - `modules/gateways/jovepay.php`
   - `modules/gateways/callback/jovepay.php`
   - `modules/gateways/jovepay/whmcs.json`
   - `modules/gateways/jovepay/logo.png`

2. In the WHMCS admin area, go to **Configuration () > System Settings > Apps & Integrations**.

3. Find **JOVEpay** and click **Activate**, or go to **Configuration () > System Settings > Payment Gateways**, select **JOVEpay** from **Activate New Gateway**, and click **Save Changes**.

4. Enter your **API Key** and **IPN Secret** from your JOVEpay account settings.

5. In **Configuration () > System Settings > General Settings**, set **Domain** / **System URL** to the public URL of your WHMCS client area (for example `https://example.com/whmcs`). This URL is used to build the IPN callback and logo paths.

6. Save the gateway configuration.

## IPN callback

WHMCS receives payment notifications at:

`https://your-domain.com/whmcs/modules/gateways/callback/jovepay.php`

The callback URL is sent automatically when a customer starts checkout. Requests are verified with the `X-JOVEPAY-SIG` HMAC header using your configured IPN secret.

## Notes

- Invoice references sent to JOVEpay use the format `WHMCS-{invoiceId}`.
- Completed and partially paid notifications are recorded with `addInvoicePayment`.
- Duplicate transaction IDs are ignored via WHMCS callback validation.
