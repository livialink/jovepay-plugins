<?php
/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */
declare(strict_types=1);

namespace Jovepay\Checkout\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Store\Model\ScopeInterface;

/**
 * Configuration helper for the JOVEpay payment method.
 */
class Data extends AbstractHelper
{
    public const XML_PATH_PAYMENT = 'payment/jove_pay/';

    /**
     * Config keys stored with Magento\Config\Model\Config\Backend\Encrypted.
     */
    private const ENCRYPTED_FIELDS = [
        'merchant_id',
        'ipn_secret',
    ];

    /**
     * @var EncryptorInterface
     */
    private $encryptor;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     */
    public function __construct(
        Context $context,
        EncryptorInterface $encryptor
    ) {
        parent::__construct($context);
        $this->encryptor = $encryptor;
    }

    /**
     * Read a payment method config value.
     *
     * @param string $code Config field code under payment/jove_pay/
     * @param int|string|null $storeId
     * @return mixed
     */
    public function getGeneralConfig(string $code, $storeId = null)
    {
        $value = $this->scopeConfig->getValue(
            self::XML_PATH_PAYMENT . $code,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($value !== null && $value !== '' && in_array($code, self::ENCRYPTED_FIELDS, true)) {
            $stringValue = (string)$value;
            // Encrypted Magento values use colon-delimited segments; keep legacy plaintext as-is.
            if (substr_count($stringValue, ':') >= 2) {
                return $this->encryptor->decrypt($stringValue);
            }

            return $stringValue;
        }

        return $value;
    }

    /**
     * Whether debug logging is enabled.
     *
     * @param int|string|null $storeId
     * @return bool
     */
    public function isDebugEnabled($storeId = null): bool
    {
        return (bool)$this->getGeneralConfig('debug', $storeId);
    }
}
