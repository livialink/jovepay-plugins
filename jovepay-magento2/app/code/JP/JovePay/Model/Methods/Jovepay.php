<?php
/**
 * @copyright: Copyright © 2026 JOVEpay. All rights reserved.
 * @author   : JOVEpay <support@jovepay.com>
 */

namespace JP\JovePay\Model\Methods;

use Magento\Framework\Pricing\PriceCurrencyInterface;
use Magento\Framework\HTTP\ZendClientFactory;
use Magento\Payment\Model\Method\ConfigInterface;
use Magento\Payment\Model\Method\TransparentInterface;
use Magento\Sales\Model\Order\Email\Sender\OrderSender;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Sales\Model\Order;

class Jovepay extends AbstractMethod
{

    /**
     * Method code
     */
    const CODE = 'jove_pay';

    /**
     * @var string
     */
    protected $_code = self::CODE;
    

    /**
     * @param CartInterface|null $quote
     * @return bool
     */
    public function isAvailable(\Magento\Quote\Api\Data\CartInterface $quote = null)
    {
        return parent::isAvailable($quote);
    }

    /**
     * @param null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }
}
