<?php

/**
 * IPN/Webhook handler for YourGateway
 * Lives in store root, same level as ipn_main_handler.php
 */

// Bootstrap Zen Cart
require ('includes/application_top.php');

// Read raw POST body (for crypto gateways sending JSON)
$raw_payload = file_get_contents('php://input');
$post_data = json_decode($raw_payload, true);

// --- 1. Verify the webhook signature ---
$sig_header = $_SERVER['HTTP_X_JOVEPAY_SIG'] ?? '';
$secret = defined('MODULE_PAYMENT_JOVEPAY_IPN_SECRET')
    ? MODULE_PAYMENT_JOVEPAY_IPN_SECRET
    : '';

$expected = hash_hmac('sha256', $raw_payload, $secret);
if (!hash_equals($expected, trim($sig_header))) {
    http_response_code(403);
    exit('Invalid signature');
}

// --- 2. Parse the event type ---
$order_ref = str_replace('ZEN-', '', $post_data['orderId'] ?? '');  // your stored reference
$payment_status = $post_data['paymentStatus'] ?? '';
$amount_paid = $post_data['payAmount'] ?? 0;
$pay_currency = $post_data['payCurrency'] ?? '';
$priceAmount = $post_data['priceAmount'] ?? 0;

// --- 3. Look up the Zen Cart order ---
$order_query = $db->Execute(
    'SELECT orders_id, order_total, orders_status 
     FROM ' . TABLE_ORDERS . "
     WHERE orders_id = '" . (int) $order_ref . "' LIMIT 1"
);

if ($order_query->EOF) {
    http_response_code(404);
    exit('Order not found');
}

$zc_order = $order_query->fields;

// --- 4. Handle payment events ---
switch ($payment_status) {
    case 'finished':
        $new_status = (int) MODULE_PAYMENT_JOVEPAY_ORDER_FINISHED_STATUS_ID;
        $comment = 'Payment confirmed via JOVEpay. TX: '
            . ($post_data['paymentId'] ?? 'N/A')
            . ' Amount: ' . $amount_paid . ' ' . $pay_currency;
        zen_update_orders_history(
            $zc_order['orders_id'],
            $comment,
            null,
            $new_status,
            0  // 0 = don't notify customer; 1 = notify
        );

        // Update the order status itself
        $db->Execute(
            'UPDATE ' . TABLE_ORDERS . "
             SET orders_status = '" . $new_status . "',
                 last_modified = NOW()
             WHERE orders_id = '" . (int) $order_ref . "'"
        );
        break;

    case 'waiting':
    case 'confirming':
        $pending_status = (int) MODULE_PAYMENT_JOVEPAY_ORDER_CONFIRMING_STATUS_ID;
        zen_update_orders_history(
            $zc_order['orders_id'],
            'Payment pending — awaiting confirmation.',
            null,
            $pending_status,
            0
        );
        break;

    case 'failed':
    case 'expired':
        zen_update_orders_history(
            $zc_order['orders_id'],
            'Payment ' . $event_type . ' reported by JOVEpay.',
            null,
            (int) MODULE_PAYMENT_JOVEPAY_FAILED_STATUS_ID,
            0
        );
        break;
}

// --- 5. Acknowledge receipt (required by most gateways) ---
http_response_code(200);
echo 'OK';
exit;
