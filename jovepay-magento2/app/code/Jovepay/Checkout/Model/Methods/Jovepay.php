<?php
/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */
declare(strict_types=1);

namespace Jovepay\Checkout\Model\Methods;

use Magento\Payment\Model\Method\AbstractMethod;
use Magento\Quote\Api\Data\CartInterface;

/**
 * JOVEpay offline redirect payment method.
 */
class Jovepay extends AbstractMethod
{
    public const CODE = 'jove_pay';

    /**
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * @var bool
     */
    protected $_isOffline = true;

    /**
     * @var bool
     */
    protected $_canUseCheckout = true;

    /**
     * @var bool
     */
    protected $_canUseInternal = false;

    /**
     * @var bool
     */
    protected $_canUseForMultishipping = true;

    /**
     * @inheritdoc
     */
    public function isAvailable(?CartInterface $quote = null)
    {
        if (!$this->getConfigData('merchant_id')) {
            return false;
        }

        return parent::isAvailable($quote);
    }

    /**
     * @inheritdoc
     */
    public function isActive($storeId = null)
    {
        return (bool)(int)$this->getConfigData('active', $storeId);
    }
}
