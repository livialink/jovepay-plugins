<?php

class JovepayValidationModuleFrontController extends ModuleFrontController
{

        public function init(){
            parent::init();
            if(1==1){
                //Tools::redirect($this->context->link->getPageLink('order'));
            }
        }
        public function initContent(){
            parent::initContent();
            //$this->setTemplate('module:Jovepay/views/templates/front/validation.tpl');
        }
        public function setMedia(){
            parent::setMedia();
        }
        public function postProcess(){
            echo '<pre>';
            var_dump($this->context->cart->id);
            echo '</pre>';
            //$this->context->cart;
        }

}
