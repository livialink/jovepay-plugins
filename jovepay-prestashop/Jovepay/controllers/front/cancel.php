<?php 

class JovepayCancelModuleFrontController extends ModuleFrontController
{ 
    public function postProcess()
    {
        //$this->get_name();
        @ob_clean();
            /*if ($this->check_ipn_request_is_valid()) {
                $this->successful_request($_POST);
            } else {
                wp_die("Jovepay.com IPN Request Failure");
            }*/
            //echo 'Cancel Controller';
            Tools::redirect(Tools::getShopDomainSsl(true, true) . __PS_BASE_URI__);
    }
    
    
}