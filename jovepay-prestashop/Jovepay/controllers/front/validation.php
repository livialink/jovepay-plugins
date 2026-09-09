<?php
/**
* 2007-2020 PrestaShop
*
* NOTICE OF LICENSE
*
* This source file is subject to the Academic Free License (AFL 3.0)
* that is bundled with this package in the file LICENSE.txt.
* It is also available through the world-wide-web at this URL:
* http://opensource.org/licenses/afl-3.0.php
* If you did not receive a copy of the license and are unable to
* obtain it through the world-wide-web, please send an email
* to license@prestashop.com so we can send you a copy immediately.
*
* DISCLAIMER
*
* Do not edit or add to this file if you wish to upgrade PrestaShop to newer
* versions in the future. If you wish to customize PrestaShop for your
* needs please refer to http://www.prestashop.com for more information.
*
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2020 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*/
class JovepayValidationModuleFrontController extends ModuleFrontController
{
    /**
     * This class should be use by your Instant Payment
     * Notification system to validate the order remotely
     */
    public function postProcess()
    {
        /*
         * If the module is not active anymore, no need to process anything.
         */
        if ($this->module->active == false) {
            die;
        }
  
        //exit;
        /**
         * Since it is an example, we choose sample data,
         * You'll have to get the correct values :)
         */
        //$cart_id = 1;
        //$customer_id = 1;
        $cart_id = $this->context->cart->id;
        //$customer_id = Tools::getValue('customer_id');
        $customer_id = $this->context->cart->id_customer;
        echo '<pre>';
        //var_dump($params['cart']->getProducts(true));
        echo '</pre>';
        //exit;
        $amc = $this->context->cart;
        $amount = (float) $amc->getOrderTotal(true, Cart::BOTH);
        
        //var_dump($isOrderX['id_order']);
        //exit;
        /*
         * Restore the context from the $cart_id & the $customer_id to process the validation properly.
         */
        Context::getContext()->cart = new Cart((int) $cart_id);
        Context::getContext()->customer = new Customer((int) $customer_id);
        Context::getContext()->currency = new Currency((int) Context::getContext()->cart->id_currency);
        Context::getContext()->language = new Language((int) Context::getContext()->customer->id_lang);

        $cuskey = new Customer($amc->id_customer);
        $secure_key = $cuskey->secure_key;
        $isOrderX= array();
        $isOrderX = Db::getInstance()->getRow(' SELECT * FROM '._DB_PREFIX_.'orders WHERE id_customer = '.$customer_id.' ORDER BY id_order DESC ');
       
        
        $payment_status = 3; // processing in progress
        $message = null;

        
        $module_name = $this->module->displayName;
        $currency_id = (int) $this->context->currency->id;

        $currr = Currency::getCurrency($amc->id_currency);
        //var_dump($this->context->customer->email);
        
        //exit;
        $this->module->validateOrder((int)$cart_id, $payment_status, $amount, $module_name, $message, array(), $this->context->currency->id, false, $secure_key);
        
         // jovepay.com Args
         $jovepay_args = array(
            'dataSource' => "prestashop",
            'ipnCallbackUrl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'module/Jovepay/ipn',
            'priceCurrency' => $currr['iso_code'],
            'priceAmount' => $amount,
            'successUrl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'module/Jovepay/success',
            'cancelUrl' => Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__ . 'module/Jovepay/cancel',
            'orderId' => $cart_id,
            'orderDescription' => "Apple Macbook Pro 2019 x 1",
            'apiKey' => Configuration::get('JOVEPAY_API_KEY', ''),
            'isTestnet' => (bool) Configuration::get('JOVEPAY_TESTNET_MODE', false),
            'locale' => $this->context->language->locale,
            'theme'  => Configuration::get('JOVEPAY_DARK_MODE') ? 'light' : 'dark',
            // Billing Address info
            'customerName' => $this->context->customer->firstname,
            'customerEmail' => $this->context->customer->email,
        );

        $jovepay_adr = urlencode(base64_encode(json_encode($jovepay_args)));
        
        $locale = explode('-', $jovepay_args['locale'])[0];
            
        Tools::redirect('https://www.jovepay.com/pay/payment/?params='.$jovepay_adr.'&locale='.$locale.'&theme='.$jovepay_args['theme']);
    }

    protected function isValidOrder()
    {
        /*
         * Add your checks right there
         */
        return true;
    }
}
