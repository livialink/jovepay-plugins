<?php
namespace Opencart\Catalog\Controller\Extension\JovePay\Payment;

class Jovepay extends \Opencart\System\Engine\Controller
{
	public function __construct($registry)
	{
		parent::__construct($registry);

		$this->load->model('checkout/order');
		$this->load->model('extension/jovepay/payment/jovepay');
	}

	/*
	 * --------------------------------------------------------------------
	 * Checkout entry
	 * ------------------------------------------------------------------
	 */

	public function index()
	{
		if (empty($this->session->data['order_id'])) {
			return;
		}

		$this->load->language('extension/jovepay/payment/jovepay');

		$order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if (!$order) {
			return;
		}

		$locale = explode('-', $this->config->get('config_language'))[0];
		$separator = version_compare(VERSION, '4.0.2', '>') ? '.' : '|';

		$args = [
			'dataSource' => 'opencart',
			'orderId' => 'OC-' . $order['order_id'],
			'priceCurrency' => strtoupper($order['currency_code']),
			'priceAmount' => 0,
			'shipping' => 0,
			'tax' => 0,
			'apiKey' => $this->config->get('payment_jovepay_api_key'),
			'isTestnet' => $this->config->get('payment_jovepay_mode') !== '1',
			'customerName' => $order['payment_firstname'],
			'customerEmail' => $order['email'],
			'ipnCallbackUrl' => $this->config->get('config_url')
				. 'index.php?route=extension/jovepay/payment/jovepay'
				. $separator . 'callback',
			'successUrl' => $this->config->get('config_url')
				. 'index.php?route=extension/jovepay/payment/jovepay'
				. $separator . 'confirm&order_id=' . $order['order_id'],
			'cancelUrl' => $this->url->link('checkout/checkout', '', true),
			'products' => [],
		];

		foreach ($this->model_checkout_order->getTotals($order['order_id']) as $total) {
			if ($total['code'] === 'shipping') {
				$args['shipping'] += (float) $total['value'];
			} elseif ($total['code'] === 'tax') {
				$args['tax'] += (float) $total['value'];
			} elseif ($total['code'] === 'total') {
				$args['priceAmount'] += (float) $total['value'];
			}
		}

		foreach ($this->cart->getProducts() as $product) {
			$args['products'][] = [
				'name' => htmlspecialchars($product['name']),
				'quantity' => (int) $product['quantity'],
				'price' => $product['price'] * $product['quantity'],
				'pricePerItem' => $product['price'],
			];
		}

		$data['button_confirm'] = $this->language->get('button_confirm');
		$data['jovepay_link'] =
			'https://www.jovepay.com/pay/payment?locale=' . $locale
			. '&theme=' . $this->config->get('payment_jovepay_theme')
			. '&params=' . urlencode(base64_encode(json_encode($args)));

		return $this->load->view('extension/jovepay/payment/jovepay', $data);
	}

	/*
	 * --------------------------------------------------------------------
	 * IPN / Webhook
	 * ------------------------------------------------------------------
	 */

	public function callback()
	{
		if (version_compare(PHP_VERSION, '7.1', '>=')) {
			ini_set('serialize_precision', -1);
		}

		try {
			$payload = $this->validateIpn();
			$this->processIpn($payload);

			$this->jsonResponse(200, ['status' => 'ok']);
		} catch (\RuntimeException $e) {
			$this->jsonResponse(
				$e->getCode() ?: 400,
				['error' => $e->getMessage()]
			);
		}
	}

	private function validateIpn(): array
	{
		if (empty($_SERVER['HTTP_X_JOVEPAY_SIG'])) {
			throw new \RuntimeException('Missing HMAC signature', 403);
		}

		$raw = file_get_contents('php://input');
		if (!$raw) {
			throw new \RuntimeException('Empty request body', 400);
		}

		$expected = hash_hmac(
			'sha256',
			$raw,
			trim($this->config->get('payment_jovepay_ipn_secret'))
		);

		if (!hash_equals($expected, trim($_SERVER['HTTP_X_JOVEPAY_SIG']))) {
			throw new \RuntimeException('Invalid HMAC signature', 403);
		}

		$data = json_decode($raw, true);
		if (!is_array($data)) {
			throw new \RuntimeException('Invalid JSON payload', 400);
		}

		return $data;
	}

	private function processIpn(array $data): void
	{
		if (empty($data['orderId'])) {
			throw new \RuntimeException('Missing orderId', 400);
		}

		$order_id = (int) str_replace('OC-', '', $data['orderId']);
		$order = $this->model_checkout_order->getOrder($order_id);

		if (!$order) {
			throw new \RuntimeException('Order not found', 404);
		}

		if (!$this->shouldUpdateStatus($order)) {
			throw new \RuntimeException('Order already processed', 400);
		}

		if (strtoupper($data['priceCurrency']) !== strtoupper($order['currency_code'])) {
			throw new \RuntimeException('Currency mismatch', 400);
		}

		if ((float) $data['priceAmount'] < (float) $order['total']) {
			throw new \RuntimeException('Amount less than order total', 400);
		}

		$this->applyPaymentStatus($order_id, $data['paymentStatus'], $data);
	}

	private function applyPaymentStatus(int $order_id, string $status, array $data): void
	{
		if ($data['paymentStatus'] == 'finished') {
			$this->update_status($order_id, 'finished', 'Order has been paid.');
		} else if ($data['paymentStatus'] == 'partially_paid') {
			$this->update_status($order_id, 'partially_paid', 'Your payment is partially paid. Please contact support@jovepay.com Amount received: ' . $request_data['actually_paid']);
		} else if ($data['paymentStatus'] == 'confirming') {
			$this->update_status($order_id, 'confirming', 'Order is processing.');
		} else if ($data['paymentStatus'] == 'confirmed') {
			$this->update_status($order_id, 'confirmed', 'Order is processing.');
		} else if ($data['paymentStatus'] == 'sending') {
			$this->update_status($order_id, 'sending', 'Order is processing.');
		} else if ($data['paymentStatus'] == 'failed') {
			$this->update_status($order_id, 'failed', 'Order is failed. Please contact support@jovepay.com');
		}
	}

	private function shouldUpdateStatus(array $order): bool
	{
		$terminal = [
			(int) $this->config->get('payment_jovepay_finished_status_id'),
			(int) $this->config->get('payment_jovepay_failed_status_id'),
		];

		return !in_array((int) $order['order_status_id'], $terminal, true);
	}

	/*
	 * --------------------------------------------------------------------
	 * Return flow
	 * ------------------------------------------------------------------
	 */

	public function confirm()
	{
		if (!empty($this->request->get['order_id'])) {
			$this->session->data['order_id'] = (int) $this->request->get['order_id'];
		}

		if (empty($this->session->data['order_id'])) {
			return $this->redirectError('Order not found.');
		}

		$this->load->model('checkout/order');

		$order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
		if (!$order) {
			return $this->redirectError('Order not found.');
		}
		if (version_compare(VERSION, '4.0.2', '>')) {
			if (!isset($order['payment_method']['code']) || $order['payment_method']['code'] != 'jovepay.jovepay') {
				return $this->redirectError('Invalid payment method.');
			}
		} else {
			if (!isset($order['payment_method']) || $order['payment_method'] != 'jovepay') {
				return $this->redirectError('Invalid payment method.');
			}
		}
		//TODO: Ensure that this order update is the first thing to happen before backend sends status update
		$this->model_checkout_order->addHistory(
			$order['order_id'],
			$this->config->get('payment_jovepay_confirmed_status_id')
		);

		$this->response->redirect($this->url->link('checkout/success', '', true));
	}

	/*
	 * --------------------------------------------------------------------
	 * Utilities
	 * ------------------------------------------------------------------
	 */

	private function update_status(int $order_id, string $status, string $comment): void
	{
		$order_status_id = $this->config->get('payment_jovepay_' . $status . '_status_id');
		$this->model_checkout_order->addHistory($order_id, $order_status_id, $comment, true);
	}

	private function jsonResponse(int $status, array $payload): void
	{
		$texts = [
			200 => 'OK',
			400 => 'Bad Request',
			403 => 'Forbidden',
			404 => 'Not Found',
			500 => 'Internal Server Error',
		];

		$this->response->addHeader(
			'HTTP/1.1 ' . $status . ' ' . ($texts[$status] ?? '')
		);
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($payload));
	}

	private function redirectError(string $message)
	{
		$this->session->data['error'] = $message;
		if ($this->config->get('quickcheckout_status') == 1) {
			$this->response->redirect($this->url->link('checkout/cart', '', true));
		}
		$this->response->redirect($this->url->link('checkout/checkout', '', true));
	}
}
