<?php

class jovepay extends base {

  var $code;
  var $moduleVersion = '1.00';
  var $title;
  var $description;
  var $enabled;
  var $order_status;

  function __construct() {
    global $order;

    $this->code = 'jovepay';

    if (IS_ADMIN_FLAG === true) {
      $this->description = MODULE_PAYMENT_JOVEPAY_TEXT_DESCRIPTION;
      $this->title = MODULE_PAYMENT_JOVEPAY_TEXT_ADMIN_TITLE; // Payment module title in Admin
    } else {
      $this->title = MODULE_PAYMENT_JOVEPAY_TEXT_CATALOG_TITLE; // Payment module title in Catalog
    }

    $this->enabled = (MODULE_PAYMENT_JOVEPAY_STATUS == 'True');
    $this->sort_order = MODULE_PAYMENT_JOVEPAY_SORT_ORDER;

    if ((int)MODULE_PAYMENT_JOVEPAY_ORDER_STATUS_ID > 0) {
      $this->order_status = MODULE_PAYMENT_JOVEPAY_ORDER_STATUS_ID;
    }

    if (is_object($order)) $this->update_status();
    
  }

  function update_status() {
    global $order, $db;

    if ($this->enabled && (int)MODULE_PAYMENT_JOVEPAY_ZONE > 0 && isset($order->billing['country']['id'])) {
      $check_flag = false;
      $check = $db->Execute("SELECT zone_id FROM " . TABLE_ZONES_TO_GEO_ZONES . " WHERE geo_zone_id = '" . MODULE_PAYMENT_JOVEPAY_ZONE . "' AND zone_country_id = '" . (int)$order->billing['country']['id'] . "' ORDER BY zone_id");
      while (!$check->EOF) {
        if ($check->fields['zone_id'] < 1) {
          $check_flag = true;
          break;
        } elseif ($check->fields['zone_id'] == $order->billing['zone_id']) {
          $check_flag = true;
          break;
        }
        $check->MoveNext();
      }

      if ($check_flag == false) {
        $this->enabled = false;
      }
    }

    // Create order ID to reconcile with JOVEpay
    $order_reference = 'JP-' . $_SESSION['customer_id'] . '-' . time();
    $_SESSION['jovepay_reference'] = $order_reference;

    $json = array();
    $json['dataSource'] = STORE_NAME;
    $json['ipnCallbackUrl'] = HTTPS_SERVER . DIR_WS_HTTPS_CATALOG . 'ipn_jovepay.php';
    $json['priceCurrency'] = $_SESSION['currency'];
    $json['successUrl'] = zen_href_link ( FILENAME_CHECKOUT_PROCESS );
    $json['cancelUrl'] = zen_href_link ( FILENAME_CHECKOUT_PAYMENT );
    $json['orderId'] = $order_reference;
    $json['apiKey'] = MODULE_PAYMENT_JOVEPAY_API_KEY;
    $json['customerName'] = $order->billing['firstname'] . ' ' . $order->billing['lastname'];
    $json['customerEmail'] = $order->customer['email_address'];

    if ( isset ( $_SESSION['cc_id'] ) ) {
      require_once(DIR_WS_MODULES . '/order_total/ot_coupon.php');
      $ot_coupon = new ot_coupon;
      $applied_coupon = $ot_coupon->calculate_deductions ( $_SESSION['cart']->show_total() );
      $discount = $applied_coupon['total'];
      $json['priceAmount'] = $order->info['total'] - $discount;
    } else {
      $json['priceAmount'] = $order->info['total'];
    }

    $json['shipping'] = $order->info['shipping_cost'];
    $json['tax'] = $order->info['tax'];

    $this->form_action_url = 'https://www.jovepay.com/pay/payment/?data=' . urlencode ( json_encode ( $json ) );

  }

  function javascript_validation() {
    // No validation required
    return '';
  }

  function selection() {
    return array( 'id' => $this->code, 'module' => $this->title );
  }

  function pre_confirmation_check() {
    // Nothing required
    return true;
  }

  function confirmation() {
    // Nothing required    
  }

  function process_button() {
    // Nothing required
    
  }

  function before_process() {
    return true;
  }

  function after_process() {
    global $db, $order, $insert_id;

    $sql = "INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . " (comments, orders_id, orders_status_id, customer_notified, date_added) values (:orderComments, :orderID, :orderStatus, 0, now() )";
    $sql = $db->bindVars($sql, ':orderComments', $_SESSION['jovepay_reference'], 'string');
    $sql = $db->bindVars($sql, ':orderID', $insert_id, 'integer');
    $sql = $db->bindVars($sql, ':orderStatus', $this->order_status, 'integer');
    $db->Execute($sql);
    unset ( $_SESSION['jovepay_reference'] );
    return false;

  }

  function check() {
    global $db;
    if (!isset($this->_check)) {
      $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_JOVEPAY_STATUS'");
      $this->_check = $check_query->RecordCount();
    }
    return $this->_check;
  }

  function install() {
    global $db, $messageStack;
    if (defined('MODULE_PAYMENT_JOVEPAY_STATUS')) {
      $messageStack->add_session('JOVEpay module already installed.', 'error');
      zen_redirect(zen_href_link(FILENAME_MODULES, 'set=payment&module=jovepay'));
      return 'failed';
    }
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added) values ('Enable JOVEpay Module', 'MODULE_PAYMENT_JOVEPAY_STATUS', 'True', 'Do you want to accept payments via JOVEpay?', '6', '0', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('Sort order of display.', 'MODULE_PAYMENT_JOVEPAY_SORT_ORDER', '0', 'Sort order of displaying payment options to the customer. Lowest is displayed first.', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added) values ('Default Order Status', 'MODULE_PAYMENT_JOVEPAY_ORDER_STATUS_ID', '1', 'Set the status of orders made with this payment module to this value', '6', '0', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, use_function, set_function, date_added) values ('Payment Zone', 'MODULE_PAYMENT_JOVEPAY_ZONE', '0', 'If a zone is selected, only enable this payment method for that zone.', '6', '2', 'zen_get_zone_class_title', 'zen_cfg_pull_down_zone_classes(', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('JOVEpay API Key', 'MODULE_PAYMENT_JOVEPAY_API_KEY', '', '', '6', '0', now())");
    $db->Execute("insert into " . TABLE_CONFIGURATION . " (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added) values ('JOVEpay IPN Secret', 'MODULE_PAYMENT_JOVEPAY_IPN_SECRET', '', '', '6', '0', now())");
  }

  function remove() {
    global $db;
    $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key in ('" . implode("', '", $this->keys()) . "')");
  }

  function keys() {
    return array('MODULE_PAYMENT_JOVEPAY_STATUS',
            'MODULE_PAYMENT_JOVEPAY_SORT_ORDER',
            'MODULE_PAYMENT_JOVEPAY_ORDER_STATUS_ID',
            'MODULE_PAYMENT_JOVEPAY_ZONE',
            'MODULE_PAYMENT_JOVEPAY_API_KEY',
            'MODULE_PAYMENT_JOVEPAY_IPN_SECRET'
            );
  }

}