<?php

if (!defined('WHMCS')) {
    die('This file cannot be accessed directly');
}

/**
 * Define module related meta data.
 *
 * @see https://developers.whmcs.com/payment-gateways/meta-data-params/
 *
 * @return array
 */
function jovepay_MetaData()
{
    return [
        'DisplayName' => 'JOVEpay',
        'APIVersion' => '1.1',
        'DisableLocalCreditCardInput' => true,
        'TokenisedStorage' => false,
        'failedEmail' => 'Credit Card Payment Failed',
        'successEmail' => 'Invoice Payment Confirmation',
        'pendingEmail' => 'Credit Card Payment Pending',
    ];
}

/**
 * Define gateway configuration options.
 *
 * @see https://developers.whmcs.com/payment-gateways/configuration/
 *
 * @return array
 */
function jovepay_config()
{
    return [
        'FriendlyName' => [
            'Type' => 'System',
            'Value' => 'JOVEpay',
        ],
        'isTestnet' => [
            'FriendlyName' => 'Is Testnet?',
            'Type' => 'yesno',
            'Description' => 'Is Testnet?',
            'Default' => 'no',
        ],
        'theme' => [
            'FriendlyName' => 'Theme',
            'Type' => 'dropdown',
            'Description' => 'Theme',
            'Default' => 'light',
            'Options' => [
                'light' => 'Light',
                'dark' => 'Dark',
            ],
        ],
        'locale' => [
            'FriendlyName' => 'Locale',
            'Type' => 'dropdown',
            'Description' => 'Locale',
            'Default' => 'en',
            'Options' => [
                'en' => 'English',
                'es' => 'Spanish',
                'fr' => 'French',
                'de' => 'German',
                'it' => 'Italian',
                'pt' => 'Portuguese',
                'ru' => 'Russian',
                'zh' => 'Chinese',
                'ja' => 'Japanese',
                'ko' => 'Korean',
                'ar' => 'Arabic',
                'hi' => 'Hindi',
                'bn' => 'Bengali',
                'ru' => 'Russian',
            ],
        ],
        'apiKey' => [
            'FriendlyName' => 'API Key',
            'Type' => 'text',
            'Size' => '64',
            'Default' => '',
            'Description' => 'Enter your JOVEpay API key',
        ],
        'ipnSecret' => [
            'FriendlyName' => 'IPN Secret',
            'Type' => 'password',
            'Size' => '64',
            'Default' => '',
            'Description' => 'Enter your JOVEpay IPN secret',
        ],
    ];
}

/**
 * Payment link.
 *
 * Required by third party payment gateway modules only.
 *
 * @param array $params Payment Gateway Module Parameters
 *
 * @see https://developers.whmcs.com/payment-gateways/third-party-gateway/
 *
 * @return string
 */
function jovepay_link($params)
{
    $apiKey = $params['apiKey'];
    $invoiceId = $params['invoiceid'];
    $amount = $params['amount'];
    $currencyCode = $params['currency'];
    $returnUrl = $params['returnurl'];
    $systemUrl = rtrim($params['systemurl'], '/');
    $moduleName = $params['paymentmethod'];

    $logoUrl = $systemUrl . '/modules/gateways/jovepay/logo.png';
    $ipnUrl = $systemUrl . '/modules/gateways/callback/' . $moduleName . '.php';

    $jovepayArgs = [
        'isTestnet' => $params['isTestnet'] == 'yes' ? true : false,
        'ipnCallbackUrl' => $ipnUrl,
        'successUrl' => $returnUrl,
        'cancelUrl' => $systemUrl,
        'dataSource' => 'whmcs',
        'priceCurrency' => mb_strtoupper($currencyCode),
        'apiKey' => $apiKey,
        'customerName' => $params['clientdetails']['firstname'] . ' ' . $params['clientdetails']['lastname'],
        'customerEmail' => $params['clientdetails']['email'],
        'priceAmount' => $amount,
        'orderId' => 'WHMCS-' . $invoiceId,
    ];

    $paymentUrl = 'https://www.jovepay.com/pay/payment?params='
        . urlencode(base64_encode(json_encode($jovepayArgs)))
        . '&locale=' . $params['locale']
        . '&theme=' . $params['theme']
        . '&isTestnet=' . $params['isTestnet'] == 'yes' ? 'true' : 'false';

    $htmlOutput = '<a href="' . htmlspecialchars($paymentUrl, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">';
    $htmlOutput .= '<img src="' . htmlspecialchars($logoUrl, ENT_QUOTES, 'UTF-8') . '" alt="JOVEpay" />';
    $htmlOutput .= '</a>';

    return $htmlOutput;
}
