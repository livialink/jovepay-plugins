<?php
/**
 * WHMCS JOVEpay Payment Callback File
 *
 * @see https://developers.whmcs.com/payment-gateways/callbacks/
 */

require_once __DIR__ . '/../../../init.php';
App::load_function('gateway');
App::load_function('invoice');

$gatewayModuleName = basename(__FILE__, '.php');
$gatewayParams = getGatewayVariables($gatewayModuleName);

if (!$gatewayParams['type']) {
    die('Module Not Activated');
}

$rawRequest = file_get_contents('php://input');
if ($rawRequest === false || $rawRequest === '') {
    logTransaction($gatewayParams['name'], [], 'Error reading POST data');
    die('Error reading POST data');
}

$receivedHmac = $_SERVER['HTTP_X_JOVEPAY_SIG'] ?? '';
if ($receivedHmac === '') {
    logTransaction($gatewayParams['name'], $rawRequest, 'No HMAC signature sent');
    die('No HMAC signature sent');
}

$expectedHmac = hash_hmac('sha256', $rawRequest, trim($gatewayParams['ipnSecret']));
if (!hash_equals($expectedHmac, trim($receivedHmac))) {
    logTransaction($gatewayParams['name'], $rawRequest, 'HMAC signature does not match');
    die('HMAC signature does not match');
}

$requestData = json_decode($rawRequest, true);
if (!is_array($requestData)) {
    logTransaction($gatewayParams['name'], $rawRequest, 'Invalid JSON payload');
    die('Invalid JSON payload');
}

if (empty($requestData['orderId'])) {
    logTransaction($gatewayParams['name'], $requestData, 'Missing orderId');
    die('Missing orderId');
}

$invoiceId = str_replace('WHMCS-', '', $requestData['orderId']);
$invoiceId = checkCbInvoiceID($invoiceId, $gatewayParams['name']);

$transactionId = $requestData['paymentId'] ?? '';
$status = $requestData['paymentStatus'] ?? '';
$priceAmount = $requestData['priceAmount'] ?? 0;
$paymentAmount = $requestData['payAmount'] ?? 0;
$currency = mb_strtoupper($requestData['priceCurrency'] ?? '');

switch ($status) {
    case 'finished':
        if ($transactionId !== '') {
            checkCbTransID($transactionId);
        }

        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $priceAmount,
            null,
            $gatewayModuleName
        );

        $message = "Invoice {$invoiceId} has been paid. Amount received: {$paymentAmount} {$currency}";
        logTransaction($gatewayParams['name'], $requestData, $message);
        break;

    case 'partially_paid':
        if ($transactionId !== '') {
            checkCbTransID($transactionId);
        }

        addInvoicePayment(
            $invoiceId,
            $transactionId,
            $paymentAmount,
            null,
            $gatewayModuleName
        );

        $message = "Invoice {$invoiceId} is partially paid. Expected amount: {$priceAmount} {$currency}. "
            . "Amount received: {$paymentAmount} {$currency}. Payment ID: {$transactionId}. "
            . 'Please contact support@jovepay.com.';
        logTransaction($gatewayParams['name'], $requestData, $message);
        break;

    case 'confirming':
        logTransaction($gatewayParams['name'], $requestData, 'Order is processing (confirming).');
        break;

    case 'confirmed':
        logTransaction($gatewayParams['name'], $requestData, 'Order is processing (confirmed).');
        break;

    case 'sending':
        logTransaction($gatewayParams['name'], $requestData, 'Order is processing (sending).');
        break;

    case 'failed':
        logTransaction($gatewayParams['name'], $requestData, 'Order failed. Please contact support@jovepay.com');
        break;

    case 'waiting':
        logTransaction($gatewayParams['name'], $requestData, 'Waiting for payment.');
        break;

    default:
        logTransaction($gatewayParams['name'], $requestData, 'Unhandled payment status: ' . $status);
        break;
}
