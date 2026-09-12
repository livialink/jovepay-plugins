<?php

/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */

declare(strict_types=1);

namespace Jovepay\Checkout\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\Result\Redirect as ResultRedirect;
use Jovepay\Checkout\Helper\Data as JovePayHelper;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Psr\Log\LoggerInterface;

/**
 * Cancels the last checkout order when the buyer leaves JOVEpay unpaid.
 */
class Failure extends Action implements HttpGetActionInterface
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PageFactory
     */
    private $resultPageFactory;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var JovePayHelper
     */
    private $jovePayHelper;

    /**
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param OrderRepositoryInterface $orderRepository
     * @param PageFactory $resultPageFactory
     * @param LoggerInterface $logger
     * @param JovePayHelper $jovePayHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        OrderRepositoryInterface $orderRepository,
        PageFactory $resultPageFactory,
        LoggerInterface $logger,
        JovePayHelper $jovePayHelper
    ) {
        $this->checkoutSession = $checkoutSession;
        $this->orderRepository = $orderRepository;
        $this->resultPageFactory = $resultPageFactory;
        $this->logger = $logger;
        $this->jovePayHelper = $jovePayHelper;
        parent::__construct($context);
    }

    /**
     * @inheritdoc
     */
    public function execute(): ResultInterface
    {
        $lastOrderId = (int) $this->checkoutSession->getLastOrderId();
        if (!$lastOrderId) {
            /** @var ResultRedirect $resultRedirect */
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('checkout/cart');
        }

        try {
            $order = $this->orderRepository->get($lastOrderId);
            $paidStatus = (string) $this->jovePayHelper->getGeneralConfig('status_order_paid');

            $this->logger->info('Order status: ' . $order->getStatus(). ' Paid status: ' . $paidStatus);

            if ($order->getStatus() === $paidStatus) {
                $this->logger->info('Order already paid, redirecting to success page');
                return $this->resultPageFactory->create();
            }
            if ($order->canCancel()) {
                $order->cancel();
                $order->addCommentToStatusHistory(__('Order canceled after JOVEpay payment failure or cancel.'));
                $this->orderRepository->save($order);
            } elseif ($order->getState() !== Order::STATE_CANCELED) {
                $order->setState(Order::STATE_CANCELED);
                $order->setStatus(Order::STATE_CANCELED);
                $order->addCommentToStatusHistory(__('Order marked canceled after JOVEpay payment failure or cancel.'));
                $this->orderRepository->save($order);
            }
        } catch (\Exception $exception) {
            $this->logger->error('JOVEpay failure page could not update order: ' . $exception->getMessage());
        }

        /** @var Page $resultPage */
        return $this->resultPageFactory->create();
    }
}
