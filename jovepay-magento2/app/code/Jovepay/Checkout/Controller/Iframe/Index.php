<?php
/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */
declare(strict_types=1);

namespace Jovepay\Checkout\Controller\Iframe;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;

/**
 * Returns checkout helper text for the JOVEpay payment method.
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @param Context $context
     * @param JsonFactory $resultJsonFactory
     */
    public function __construct(
        Context $context,
        JsonFactory $resultJsonFactory
    ) {
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        /** @var Json $result */
        $result = $this->resultJsonFactory->create();

        return $result->setData([
            'message' => (string)__(
                'You will be transferred to jovepay.com to complete your purchase when using this payment method.'
            ),
        ]);
    }
}
