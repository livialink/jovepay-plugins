<?php
namespace JP\JovePay\Model;

use JP\JovePay\Helper\Data as JovePayHelper;
use JP\JovePay\Logger\Logger;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Webapi\Exception as WebapiException;
use Magento\Sales\Model\Order;

class IpnManagement
{
    /** @var \Magento\Framework\App\RequestInterface */
    protected $request;
    /** @var \Magento\Sales\Model\OrderRepository */
    private $orderRepository;
    /** @var Logger */
    private $log;
    /** @var JovePayHelper */
    private $helper;
    /** @var  \Magento\Framework\ObjectManagerInterface */
    private $_objectManager;

    /**
     * IpnManagement constructor.
     *
     * @param RequestInterface $request
     * @param \Magento\Sales\Model\OrderRepository $orderRepository
     * @param Logger $logger
     * @param JovePayHelper $helper
     */
    public function __construct(
        RequestInterface $request,
        \Magento\Sales\Model\OrderRepository $orderRepository,
        Logger $logger,
        JovePayHelper $helper,
        \Magento\Framework\ObjectManagerInterface $objectmanager
    ) {
        $this->request = $request;
        $this->orderRepository = $orderRepository;
        $this->log = $logger;
        $this->helper = $helper;
        $this->_objectManager = $objectmanager;
    }

    /**
     * {@inheritdoc}
     */
    public function getPost($param)
    {
        try {
            if (!$this->checkIpnRequestIsValid()) {
                throw new WebapiException(
                    __('Invalid signature'),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }

            // $ipndata = $_POST;
            $ipndata = file_get_contents('php://input');
            $this->logInfo('Data Received ' . $ipndata);
            $ipndata = json_decode($ipndata, true);

            // Payment was successful, so update the order's state, send order email and move to the success page
            $order_id = (int) $ipndata['orderId'];
            if ($order = $this->orderRepository->get($order_id)) {
                if ($order->getState() == Order::STATE_NEW || $order->getState() == 'pending') {
                    if (strtoupper($ipndata['priceCurrency']) == $order->getBaseCurrencyCode()) {
                        if ($ipndata['priceAmount'] >= $order->getBaseGrandTotal()) {
                            $status = $ipndata['paymentStatus'];
                            $this->updateStatus($status, $order, $ipndata);
                            $order->save();
                            $response = ['error' => false, 'status' => '200', 'msg' => 'ipn updated'];
                            return json_encode($response);
                        } else {
                            $this->logInfo('Amount paid is less than order total!', $order);
                            throw new WebapiException(
                                __('Amount paid is less than order total!'),
                                0,
                                WebapiException::HTTP_BAD_REQUEST
                            );
                        }
                    } else {
                        $this->logInfo('Currency code does not match!', $order);
                        throw new WebapiException(
                            __('Currency code does not match!'),
                            0,
                            WebapiException::HTTP_BAD_REQUEST
                        );
                    }
                } else {
                    $this->logInfo('Order is no longer new. (most likely IPN has already been processed)');
                    throw new WebapiException(
                        __('Order is no longer new. (most likely IPN has already been processed)'),
                        0,
                        WebapiException::HTTP_BAD_REQUEST
                    );
                }
            } else {
                $this->logInfo('Could not load order with ID: ' . $order_id);
                throw new WebapiException(
                    __('Could not load order with ID: ' . $order_id),
                    0,
                    WebapiException::HTTP_BAD_REQUEST
                );
            }
        } catch (\Exception $e) {
            $this->logInfo('Exception ' . $e->getMessage());
            throw new WebapiException(
                __($e->getMessage()),
                0,
                WebapiException::HTTP_BAD_REQUEST
            );
        }
        return json_encode($response);
    }

    /**
     * Check if the IPN request is valid
     * @return bool
     */
    private function checkIpnRequestIsValid(): bool
    {
        // Get the signature from the headers
        $signature = $this->request->getHeader('x-jovepay-sig');

        if (!$signature) {
            $this->logInfo('Missing x-jovepay-sig header');
            return false;
        }

        // Get raw POST body
        $rawRequest = $this->request->getContent();

        if (!$rawRequest) {
            $this->logInfo('Empty php://input');
            return false;
        }

        // Compute HMAC with your secret key
        $secret = trim($this->helper->getGeneralConfig('ipn_secret'));
        $hmac = hash_hmac('sha256', $rawRequest, $secret);

        // Compare HMAC with header signature securely
        if (!hash_equals($hmac, trim($signature))) {
            $this->logInfo('Invalid signature');
            return false;
        }

        return true;
    }

    /**
     * @param $status
     * @param $order
     */
    private function updateStatus($status, $order, $ipndata)
    {
        // $this->logInfo("Updating status ",$order);
        if ($status == 'failed') {
            // canceled or timed out
            $order->cancel();
            $order->setState(
                ORDER::STATE_CANCELED,
                true,
                'jovepay.com Payment Status: ' . $this->getRequest()->getParam(
                    'payment_status'
                )
            )->setStatus(ORDER::STATE_CANCELED);
            $order->save();
            $this->logInfo('Status Updated ' . $status, $order);
        } else {
            if ($status == 'finished') {
                $str = 'jovepay.com Payment Status: ' . $status . ' ';
                foreach ($ipndata as $key => $value) {
                    if (is_object($value)) {
                        $valueString = json_encode($value);
                    } elseif (is_array($value)) {
                        $valueString = json_encode($value);
                    } else {
                        $valueString = $value;
                    }
                    $str .= ' ' . $key . ' => ' . $valueString . ' </br>';
                }
                $order->setState(
                    $this->helper->getGeneralConfig('status_order_paid')
                )->setStatus($this->helper->getGeneralConfig('status_order_paid'));
                $order->addCommentToStatusHistory($str, $this->helper->getGeneralConfig('status_order_paid'));
                $order->save();
                // $this->logInfo("Updating status " . $order->canInvoice());
                if ($order->canInvoice()) {
                    $invoice = $this->_objectManager->create('\Magento\Sales\Model\Service\InvoiceService')->prepareInvoice($order);
                    $invoice->setRequestedCaptureCase(\Magento\Sales\Model\Order\Invoice::CAPTURE_ONLINE);
                    $invoice->register();
                    // $invoice->save();

                    $this->logInfo('created invoice ' . $invoice->getId(), $order);

                    $transactionSave = $this->_objectManager->create('\Magento\Framework\DB\Transaction')->addObject(
                        $invoice
                    )->addObject(
                        $order
                    );
                    $transactionSave->save();
                    $order
                        ->addStatusHistoryComment(
                            __('Notified customer about invoice #%1.', $invoice->getId())
                        )
                        ->setIsCustomerNotified(true)
                        ->save();
                    $this->_objectManager->create(
                        \Magento\Sales\Api\InvoiceManagementInterface::class
                    )->notify($invoice->getEntityId());
                } else {
                    $this->logInfo('Can not create invoice ', $order);
                }

                $this->logInfo('Status Updated ' . $status, $order);
            } else {
                $str = 'jovepay.com Payment Status: ' . $status . ' </br>';
                foreach ($ipndata as $key => $value) {
                    if (is_object($value)) {
                        $valueString = json_encode($value);
                    } elseif (is_array($value)) {
                        $valueString = json_encode($value);
                    } else {
                        $valueString = $value;
                    }
                    $str .= ' ' . $key . ' => ' . $valueString . ' </br>';
                }

                // order payment pending
                $order->addCommentToStatusHistory($str, $this->helper->getGeneralConfig('status_order_placed'));
                $order->save();
                $this->logInfo('Status Updated ' . $status, $order);
            }
        }
    }

    /**
     * @param      $msg
     * @param null $order
     */
    private function logInfo($msg, $order = null)
    {
        if ($this->helper->getGeneralConfig('debug')) {
            $messsageString = '';
            if ($order !== null) {
                $messsageString = 'Order ID: ' . $order->getId();
            }
            $messsageString .= $msg;
            $this->log->info($messsageString);
        }

        return;
    }
}
