# Changelog

## 1.0.0

- Marketplace / EQP readiness: add `composer.json`, ACL, GPL-3.0 license, README, and i18n
- Make user-facing strings translatable (PHP, JS, Knockout, admin config XML)
- Fix invalid `module.xml` sequence declaration
- Harden IPN handling (HMAC validation, no ObjectManager, repository/invoice services)
- Stop exposing IPN secret to the storefront
- Encrypt-friendly obscure admin fields for Merchant ID and IPN Secret
- Escape redirect payload output and remove XSS-prone HTML bindings
- Controllers implement `HttpGetActionInterface`; payment disabled by default until configured
- Default paid order status set to processing; debug logging gated by config
