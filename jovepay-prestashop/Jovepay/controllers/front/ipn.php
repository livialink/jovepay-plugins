<?php 

class JovepayIpnModuleFrontController extends ModuleFrontController
{ 
    public function postProcess()
    {
        //$this->get_name();

        @ob_clean();
        if (!$this->check_ipn_request_is_valid()) {
            http_response_code(403);
            echo 'INVALID_IPN';
            exit;                
        } 
        $this->successful_request($_POST);  
        http_response_code(200);
        echo 'OK';
        exit;
            /*$objOrder = new Order(26);
            echo "<pre>";
            
            $currr = Currency::getCurrency($objOrder->id_currency);
            //var_dump($currr["iso_code"]);
            //var_dump($objOrder->total_paid);
            echo "</pre>";
            $history = new OrderHistory();
            $history->id_order = (int)$objOrder->id;
            $order_status = $objOrder->current_state;
            $history->changeIdOrderState(2, (int)($objOrder->id));*/
            
    }
    /**
     * Check Jovepay.com IPN validity
     **/
    function check_ipn_request_is_valid()
    {

        $order = false;
        $error_msg = "Unknown error";
        $auth_ok = false;
        $request_data = null;
        
        $headers = array_change_key_case(getallheaders(), CASE_LOWER);
        $signature = $headers['x-jovepay-sig'];

        if (isset($signature) && !empty($signature)) {
            $raw_request = file_get_contents('php://input');
            if ($raw_request !== false && !empty($raw_request)) {
                $hmac = hash_hmac("sha256", $raw_request, trim(Configuration::get('JOVEPAY_IPN_SECRET', '')));
                if (hash_equals($hmac, trim($signature))) {
                    $auth_ok = true;
                } else {
                    $error_msg = 'HMAC signature does not match';
                }
            } else {
                $error_msg = 'Error reading POST data';
            }
        } else {
            $error_msg = 'No HMAC signature sent.';
        }
        $objOrder='';
        if ($auth_ok) {
            $valid_order_id = str_replace("", "", $request_data["orderId"]);
            $objOrder = new Order((int)$valid_order_id);
            $objCurrency = Currency::getCurrency($objOrder->id_currency);
            $history = new OrderHistory();
            $history->id_order = (int)$objOrder->id;
            $order_status = $objOrder->current_state;
            
            
            if ($objOrder !== false) {                   
                // Get the currency from the order, not the active currency
                // NOTE: for backward compsatibility with WC 2.6 and earlier,
                $payment_currency = strtoupper($request_data["priceCurrency"]);
                if ($payment_currency == ($objCurrency["iso_code"] || $payment_currency)) {
                    if ($request_data["priceAmount"] >= $objOrder->total_paid) {
                        print "IPN check OK\n";
                        return true;
                    } else {
                        $error_msg = "Amount received is less than the total!";
                    }
                } else {
                    $error_msg = "Original currency doesn't match!";
                }
            } else {
                $error_msg = "Could not find order info for order ";
            }
        }

        $report = "Error Message: ".$error_msg."\n\n";

        if ($objOrder) {
            $history->changeIdOrderState(8, (int)($objOrder->id));
        }
        //if (!empty($this->debug_email)) { mail($this->debug_email, "Report", $report); };
        PrestaShopLogger::addLog(
            'Jovepay IPN failed: ' . $error_msg,
            3 // error
        );
        return false;
    }
    function successful_request()
    {
        
        $request_json = file_get_contents('php://input');
        $request_data = json_decode($request_json, true);
        $valid_order_id = str_replace("", "", $request_data["orderId"]);
        //$order = new WC_Order($valid_order_id);
        $objOrder = new Order((int)$valid_order_id);
        $objCurrency = Currency::getCurrency($objOrder->id_currency);
        //var_dump($objCurrency["iso_code"]);
        //var_dump($objOrder->total_paid);
        $history = new OrderHistory();
        $history->id_order = (int)$objOrder->id;
        $order_status = $objOrder->current_state;


        if ($request_data["paymentStatus"] == "finished") {
            //$order->update_status('processing', 'Order has been paid.');
            //$order->payment_complete();
            $history->changeIdOrderState(2, (int)($objOrder->id));
        } else if ($request_data["paymentStatus"] == "partially_paid") {
            //$order->update_status('on-hold', 'Order is holded.');
            $history->changeIdOrderState(3, (int)($objOrder->id));
            //$order->add_order_note('Your payment is partially paid. Please contact support@jovepay.com Amount received: ' . $request_data["actually_paid"]);
        } else if ($request_data["paymentStatus"] == "confirming") {
            //$order->update_status('processing', 'Order is processing.');
            $history->changeIdOrderState(3, (int)($objOrder->id));
        } else if ($request_data["paymentStatus"] == "confirmed") {
            //$order->update_status('processing', 'Order is processing.');
            $history->changeIdOrderState(3, (int)($objOrder->id));
        } else if ($request_data["paymentStatus"] == "sending") {
            //$order->update_status('processing', 'Order is processing.');
            $history->changeIdOrderState(3, (int)($objOrder->id));
        } else if ($request_data["paymentStatus"] == "failed") {
            //$order->update_status('on-hold', 'Order is failed. Please contact support@jovepay.com');
            $history->changeIdOrderState(15, (int)($objOrder->id));
        }

        //$order->add_order_note('jovepay.com Payment Status: ' . $request_data["paymentStatus"]);
    }


}