<?php
/**
 * @copyright: Copyright © 2026 JOVEpay. All rights reserved.
 * @author   : JOVEpay <support@jovepay.com>
 */

namespace JP\JovePay\Block;

class Redirect extends \Magento\Framework\View\Element\Template
{
    const PATH_TO_PAYMENT_CONFIG = 'payment/jove_pay/';

    /**
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry;


    /**
     * @var \Magento\Quote\Model\QuoteRepository
     */
    protected $quoteRepository;

    /**
     * redirect constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param \Magento\Quote\Model\QuoteRepository $quoteRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Quote\Model\QuoteRepository $quoteRepository,
        array $data = []
    ) {
        $this->quoteRepository = $quoteRepository;
        $this->_coreRegistry = $coreRegistry;
        parent::__construct($context, $data);
    }

    /**
     * @return mixed
     */
    public function getLastOrderId()
    {
        $lastSuccessOrderId = $this->_coreRegistry->registry('last_success_order_id');
        return $lastSuccessOrderId;
    }

    /**
     * @return string
     */
    public function getIpnUrl()
    {
        //$quoteModel = $this->getQuote();
        return $this->getUrl('rest/V1/jovepay/') . 'ipn';
    }

    /**
     * @return array
     */
    public function getPaymentData()
    {
        $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
        $data = [
            'merchant_id' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "merchant_id", $storeScope),
            'is_testnet' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "is_testnet", $storeScope),
            'is_dark' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "is_dark", $storeScope),
            'ipn_secret' => $this->_scopeConfig->getValue(self::PATH_TO_PAYMENT_CONFIG . "ipn_secret", $storeScope),
            'store_name' => $this->_scopeConfig->getValue('general/store_information/name', $storeScope),
            'store_id' => $this->_storeManager->getStore()->getId(),
            'currency_code' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
        ];
        return $data;
    }

    /**
     * @return \Magento\Quote\Api\Data\CartInterface|\Magento\Quote\Model\Quote
     */
    public function getQuote()
    {
        $quoteModel = $this->quoteRepository->get($this->_coreRegistry->registry('last_success_quote_id'));
        return $quoteModel;
    }

     /**
     * @return int
     */
    public function getShippingAmount()
    {
        $quoteModel     = $this->quoteRepository->get($this->_coreRegistry->registry('last_success_quote_id'));
        $shippingAmount = $quoteModel->getShippingAddress()->getShippingAmount();
        if ($quoteModel->getShippingAddress()->getShippingDiscountAmount()) {
            $shippingAmount = $shippingAmount - $quoteModel->getShippingAddress()->getShippingDiscountAmount();
        }

        return $shippingAmount;
    }

    /**
     * @return string
     */
    public function getSuccessUrl()
    {
        return $this->getUrl('checkout/onepage/success');
    }

    /**
     * @return string
     */
    public function getFailUrl()
    {
        return $this->getUrl('jovepay/checkout/failure');
    }
}
