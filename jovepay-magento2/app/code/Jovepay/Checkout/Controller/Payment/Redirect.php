<?php
/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */
declare(strict_types=1);

namespace Jovepay\Checkout\Controller\Payment;

use Jovepay\Checkout\Helper\Data as JovePayHelper;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Shows the intermediate page that redirects the buyer to JOVEpay.
 */
class Redirect extends Action implements HttpGetActionInterface
{
    /**
     * @var Registry
     */
    private $coreRegistry;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var JovePayHelper
     */
    private $jovePayHelper;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param PageFactory $resultPageFactory
     * @param CheckoutSession $checkoutSession
     * @param LoggerInterface $logger
     * @param OrderRepositoryInterface $orderRepository
     * @param JovePayHelper $jovePayHelper
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        PageFactory $resultPageFactory,
        CheckoutSession $checkoutSession,
        LoggerInterface $logger,
        OrderRepositoryInterface $orderRepository,
        JovePayHelper $jovePayHelper
    ) {
        $this->coreRegistry = $coreRegistry;
        $this->resultPageFactory = $resultPageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->orderRepository = $orderRepository;
        $this->jovePayHelper = $jovePayHelper;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        if (!$this->checkoutSession->getLastSuccessQuoteId()) {
            /** @var ResultRedirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('checkout/cart');
        }

        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order || !$order->getId()) {
            /** @var ResultRedirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('checkout/cart');
        }

        if ($this->jovePayHelper->isDebugEnabled()) {
            $this->logger->info('JOVEpay redirect for order ID: ' . $order->getId());
        }

        $this->coreRegistry->register('last_success_quote_id', $this->checkoutSession->getLastSuccessQuoteId());
        $this->coreRegistry->register('last_success_order_id', $order->getId());

        $orderModel = $this->orderRepository->get((int)$order->getId());
        $placedStatus = (string)$this->jovePayHelper->getGeneralConfig('status_order_placed');
        $orderModel->setState(Order::STATE_NEW);
        $orderModel->setStatus($placedStatus !== '' ? $placedStatus : Order::STATE_NEW);
        $this->orderRepository->save($orderModel);

        /** @var Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('Pay with JOVEpay'));

        return $resultPage;
    }
}
