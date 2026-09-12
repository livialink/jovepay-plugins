<?php

/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */

declare(strict_types=1);

namespace Jovepay\Checkout\Block;

use Jovepay\Checkout\Helper\Data as JovePayHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Framework\Registry;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Quote\Api\CartRepositoryInterface;

/**
 * Builds the hosted payment redirect payload for JOVEpay.
 */
class Redirect extends Template
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var CartRepositoryInterface
     */
    private $quoteRepository;

    /**
     * @var JovePayHelper
     */
    private $jovePayHelper;

    /**
     * @var ResolverInterface
     */
    protected $localeResolver;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param CartRepositoryInterface $quoteRepository
     * @param JovePayHelper $jovePayHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        ResolverInterface $localeResolver,
        CartRepositoryInterface $quoteRepository,
        JovePayHelper $jovePayHelper,
        array $data = []
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->quoteRepository = $quoteRepository;
        $this->jovePayHelper = $jovePayHelper;
        $this->localeResolver = $localeResolver;
        parent::__construct($context, $data);
    }

    /**
     * Last placed Magento order entity id.
     *
     * @return int|null
     */
    public function getLastOrderId(): ?int
    {
        $orderId = $this->coreRegistry->registry('last_success_order_id');
        return $orderId !== null ? (int) $orderId : null;
    }

    /**
     * Public REST URL used by JOVEpay for IPN callbacks.
     *
     * @return string
     */
    public function getIpnUrl(): string
    {
        return $this->getUrl('rest/V1/jovepay/') . 'ipn';
    }

    /**
     * Non-sensitive store/payment settings for the hosted checkout redirect.
     *
     * @return array
     */
    public function getPaymentData(): array
    {
        return [
            'merchant_id' => (string) $this->jovePayHelper->getGeneralConfig('merchant_id'),
            'is_testnet' => (string) $this->jovePayHelper->getGeneralConfig('is_testnet'),
            'is_dark' => (string) $this->jovePayHelper->getGeneralConfig('is_dark'),
            'store_id' => (int) $this->_storeManager->getStore()->getId(),
            'currency_code' => (string) $this->_storeManager->getStore()->getCurrentCurrencyCode(),
        ];
    }

    /**
     * Quote associated with the last successful checkout.
     *
     * @return CartInterface
     * @throws NoSuchEntityException
     */
    public function getQuote(): CartInterface
    {
        $quoteId = (int) $this->coreRegistry->registry('last_success_quote_id');
        return $this->quoteRepository->get($quoteId);
    }

    /**
     * Shipping amount after discounts.
     *
     * @return float
     * @throws NoSuchEntityException
     */
    public function getShippingAmount(): float
    {
        $quote = $this->getQuote();
        $shippingAddress = $quote->getShippingAddress();
        $shippingAmount = (float) $shippingAddress->getShippingAmount();
        $shippingDiscount = (float) $shippingAddress->getShippingDiscountAmount();

        if ($shippingDiscount) {
            $shippingAmount -= $shippingDiscount;
        }

        return $shippingAmount;
    }

    /**
     * Magento success page URL.
     *
     * @return string
     */
    public function getSuccessUrl(): string
    {
        return $this->getUrl('checkout/onepage/success');
    }

    /**
     * Magento failure / cancel page URL.
     *
     * @return string
     */
    public function getFailUrl(): string
    {
        return $this->getUrl('jovepay/checkout/failure');
    }

    /**
     * Hosted JOVEpay payment base URL including theme.
     *
     * @return string
     */
    public function getPaymentBaseUrl(): string
    {
        $paymentData = $this->getPaymentData();
        $currentLocale = $this->localeResolver->getLocale();
        $languageCode = explode('_', $currentLocale)[0];

        $themeMode = $paymentData['is_dark'] === '1' ? 'dark' : 'light';

        return 'https://www.jovepay.com/pay/payment/?theme='
            . $themeMode . '&locale=' . $languageCode . '&params=';
    }

    /**
     * Base64 URL-safe payload for the hosted payment page.
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function getEncodedOrderPayload(): string
    {
        $paymentData = $this->getPaymentData();
        $quote = $this->getQuote();
        $shippingAddress = $quote->getShippingAddress();

        $orderData = [
            'dataSource' => 'magento',
            'ipnCallbackUrl' => $this->getIpnUrl(),
            'priceCurrency' => $paymentData['currency_code'],
            'successUrl' => $this->getSuccessUrl(),
            'cancelUrl' => $this->getFailUrl(),
            'orderId' => $this->getLastOrderId(),
            'apiKey' => $paymentData['merchant_id'],
            'isTestnet' => $paymentData['is_testnet'] === '1',
            'customerName' => trim(
                (string) $quote->getCustomerFirstname() . ' ' . (string) $quote->getCustomerLastname()
            ),
            'customerEmail' => (string) $quote->getCustomerEmail(),
            'priceAmount' => (float) $quote->getBaseGrandTotal(),
            'shipping' => $this->getShippingAmount(),
            'tax' => (float) $shippingAddress->getTaxAmount(),
            'subtotal' => (float) $quote->getSubtotal(),
        ];

        return rawurlencode(base64_encode((string) json_encode($orderData)));
    }
}
