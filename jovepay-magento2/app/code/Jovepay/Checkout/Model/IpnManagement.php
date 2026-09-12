<?php
/**
 * Copyright © JOVEpay. Licensed under GPLv3 or later. See LICENSE.txt.
 */
declare(strict_types=1);

namespace Jovepay\Checkout\Model;

use Jovepay\Checkout\Api\IpnManagementInterface;
use Jovepay\Checkout\Helper\Data as JovePayHelper;
use Jovepay\Checkout\Logger\Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Sales\Api\InvoiceManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Invoice;
use Magento\Sales\Model\Service\InvoiceService;

/**
 * Handles signed IPN callbacks from JOVEpay and updates Magento orders.
 */
class IpnManagement implements IpnManagementInterface
{
    /**
     * @var RequestInterface
     */
    private $request;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var JovePayHelper
     */
    private $helper;

    /**
     * @var InvoiceService
     */
    private $invoiceService;

    /**
     * @var TransactionFactory
     */
    private $transactionFactory;

    /**
     * @var InvoiceManagementInterface
     */
    private $invoiceManagement;

    /**
     * @var Json
     */
    private $json;

    /**
     * @param RequestInterface $request
     * @param OrderRepositoryInterface $orderRepository
     * @param Logger $logger
     * @param JovePayHelper $helper
     * @param InvoiceService $invoiceService
     * @param TransactionFactory $transactionFactory
     * @param InvoiceManagementInterface $invoiceManagement
     * @param Json $json
     */
    public function __construct(
        RequestInterface $request,
        OrderRepositoryInterface $orderRepository,
        Logger $logger,
        JovePayHelper $helper,
        InvoiceService $invoiceService,
        TransactionFactory $transactionFactory,
        InvoiceManagementInterface $invoiceManagement,
        Json $json
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->logger = $logger;
        $this->helper = $helper;
        $this->invoiceService = $invoiceService;
        $this->transactionFactory = $transactionFactory;
        $this->invoiceManagement = $invoiceManagement;
        $this->json = $json;
    }

    /**
     * @inheritdoc
     */
    public function getPost($param = null)
    {
        try {
            if (!$this->isIpnRequestValid()) {
                throw new WebapiException(
                    __('Invalid signature.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            $rawBody = (string)$this->request->getContent();
            $this->logInfo('IPN payload received.');

            try {
                $ipnData = $this->json->unserialize($rawBody);
            } catch (\InvalidArgumentException $exception) {
                throw new WebapiException(
                    __('Invalid IPN payload.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            if (!is_array($ipnData) || empty($ipnData['orderId'])) {
                throw new WebapiException(
                    __('Missing order identifier.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            $orderId = (int)$ipnData['orderId'];
            $order = $this->orderRepository->get($orderId);

            if ($order->getState() !== Order::STATE_NEW
                && $order->getState() !== Order::STATE_PENDING_PAYMENT
            ) {
                $this->logInfo('Order is no longer awaiting payment.', $order);
                return $this->json->serialize([
                    'error' => false,
                    'status' => '200',
                    'msg' => 'Order is no longer awaiting payment.',
                ]);
            }

            $priceCurrency = isset($ipnData['priceCurrency']) ? strtoupper((string)$ipnData['priceCurrency']) : '';
            if ($priceCurrency !== strtoupper((string)$order->getBaseCurrencyCode())) {
                $this->logInfo('Currency code does not match.', $order);
                throw new WebapiException(
                    __('Currency code does not match.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            $priceAmount = isset($ipnData['priceAmount']) ? (float)$ipnData['priceAmount'] : 0.0;
            if ($priceAmount + 0.0001 < (float)$order->getBaseGrandTotal()) {
                $this->logInfo('Amount paid is less than order total.', $order);
                throw new WebapiException(
                    __('Amount paid is less than order total.'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            $status = isset($ipnData['paymentStatus']) ? (string)$ipnData['paymentStatus'] : '';
            $this->updateStatus($status, $order, $ipnData);

            return $this->json->serialize([
                'error' => false,
                'status' => '200',
                'msg' => 'ipn updated',
            ]);
        } catch (WebapiException $exception) {
            throw $exception;
        } catch (LocalizedException $exception) {
            $this->logInfo('Localized exception: ' . $exception->getLogMessage());
            throw new WebapiException(
                __('Unable to process IPN.'),
                0,
                WebapiException::HTTP_BAD_REQUEST
            );
        } catch (\Exception $exception) {
            $this->logInfo('Unexpected exception while processing IPN.');
            throw new WebapiException(
                __('Unable to process IPN.'),
                0,
                WebapiException::HTTP_INTERNAL_ERROR
            );
        }
    }

    /**
     * Validate the HMAC signature on the IPN request.
     *
     * @return bool
     */
    private function isIpnRequestValid(): bool
    {
        $signature = (string)$this->request->getHeader('x-jovepay-sig');
        if ($signature === '') {
            $this->logInfo('Missing x-jovepay-sig header.');
            return false;
        }

        $rawRequest = (string)$this->request->getContent();
        if ($rawRequest === '') {
            $this->logInfo('Empty IPN request body.');
            return false;
        }

        $secret = trim((string)$this->helper->getGeneralConfig('ipn_secret'));
        if ($secret === '') {
            $this->logInfo('IPN secret is not configured.');
            return false;
        }

        $hmac = hash_hmac('sha256', $rawRequest, $secret);

        if (!hash_equals($hmac, trim($signature))) {
            $this->logInfo('Invalid IPN signature.');
            return false;
        }

        return true;
    }

    /**
     * Update Magento order state based on JOVEpay payment status.
     *
     * @param string $status
     * @param Order $order
     * @param array $ipnData
     * @return void
     * @throws LocalizedException
     */
    private function updateStatus(string $status, Order $order, array $ipnData): void
    {
        if ($status === 'failed') {
            $order->cancel();
            $order->addCommentToStatusHistory(__('JOVEpay payment status: failed.'));
            $this->orderRepository->save($order);
            $this->logInfo('Order canceled after failed payment.', $order);
            return;
        }

        $comment = $this->buildStatusComment($status, $ipnData);

        if ($status === 'finished') {
            $paidStatus = (string)$this->helper->getGeneralConfig('status_order_paid');
            $order->setState(Order::STATE_PROCESSING);
            $order->setStatus($paidStatus !== '' ? $paidStatus : Order::STATE_PROCESSING);
            $order->addCommentToStatusHistory($comment, $order->getStatus());
            $this->orderRepository->save($order);
            $this->createInvoice($order);
            $this->logInfo('Order marked as paid.', $order);
            return;
        }

        $placedStatus = (string)$this->helper->getGeneralConfig('status_order_placed');
        $order->addCommentToStatusHistory(
            $comment,
            $placedStatus !== '' ? $placedStatus : false
        );
        $this->orderRepository->save($order);
        $this->logInfo('Order payment still pending. Status: ' . $status, $order);
    }

    /**
     * Create and notify an invoice when payment is complete.
     *
     * @param Order $order
     * @return void
     * @throws LocalizedException
     */
    private function createInvoice(Order $order): void
    {
        if (!$order->canInvoice()) {
            $this->logInfo('Order cannot be invoiced.', $order);
            return;
        }

        $invoice = $this->invoiceService->prepareInvoice($order);
        if (!$invoice || !$invoice->getTotalQty()) {
            throw new LocalizedException(__('Cannot create an invoice without products.'));
        }

        $invoice->setRequestedCaptureCase(Invoice::CAPTURE_OFFLINE);
        $invoice->register();

        $transaction = $this->transactionFactory->create();
        $transaction->addObject($invoice)->addObject($order)->save();

        $order->addCommentToStatusHistory(
            __('Notified customer about invoice #%1.', $invoice->getEntityId())
        )->setIsCustomerNotified(true);
        $this->orderRepository->save($order);

        if ($invoice->getEntityId()) {
            $this->invoiceManagement->notify((int)$invoice->getEntityId());
        }

        $this->logInfo('Invoice created.', $order);
    }

    /**
     * Build a sanitized status history comment from IPN fields.
     *
     * @param string $status
     * @param array $ipnData
     * @return string
     */
    private function buildStatusComment(string $status, array $ipnData): string
    {
        $allowedKeys = [
            'paymentStatus',
            'priceAmount',
            'priceCurrency',
            'orderId',
            'transactionId',
            'paymentId',
        ];

        $parts = [(string)__('JOVEpay payment status: %1', $status)];
        foreach ($allowedKeys as $key) {
            if (!array_key_exists($key, $ipnData)) {
                continue;
            }
            $value = $ipnData[$key];
            if (is_scalar($value)) {
                $parts[] = (string)__('%1 = %2', $key, (string)$value);
            }
        }

        return implode(' | ', $parts);
    }

    /**
     * Write a debug log entry when debug mode is enabled.
     *
     * @param string $message
     * @param Order|null $order
     * @return void
     */
    private function logInfo(string $message, ?Order $order = null): void
    {
        if (!$this->helper->isDebugEnabled()) {
            return;
        }

        if ($order !== null) {
            $message = 'Order ID: ' . $order->getEntityId() . ' ' . $message;
        }

        $this->logger->info($message);
    }
}
