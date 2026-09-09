<?php
class ControllerExtensionPaymentJovepay extends Controller
{
	public function index()
	{
		$this->load->language('extension/payment/jovepay');

		$data['button_confirm'] = $this->language->get('button_confirm');

		$locale = explode('-', $this->config->get('config_language'))[0];

		$jovepay_adr = 'https://www.jovepay.com/pay/payment?locale=' . $locale . '&theme=' . $this->config->get('payment_jovepay_theme') . '&params=';

		$this->load->model('checkout/order');

		$order_info = $this->model_checkout_order->getOrder($this->session->data['order_id']);

		if ($order_info) {
			// jovepay.com Args
			$jovepay_args = array(
				'dataSource' => 'opencart',
				'orderId' => 'OC-' . $order_info['order_id'],
				'priceCurrency' => strtoupper($order_info['currency_code']),
				'priceAmount' => 0,
				'shipping' => 0,
				'tax' => 0,
				'apiKey' => $this->config->get('payment_jovepay_api_key'),
				'isTestnet' => $this->config->get('payment_jovepay_mode') !== '1',
				'customerName' => $order_info['payment_firstname'],
				'customerEmail' => $order_info['email'],
				'ipnCallbackUrl' => $this->url->link('extension/payment/jovepay/callback', '', true),
				'successUrl' => $this->url->link('extension/payment/jovepay/confirm', '&order_id=' . $order_info['order_id'], true),
				'cancelUrl' => $this->url->link('checkout/checkout', '', true),
				'products' => [],
			);

			$order_totals = $this->model_checkout_order->getOrderTotals($order_info['order_id']);

			foreach ($order_totals as $order_total) {
				if ($order_total['code'] == 'shipping') {
					$jovepay_args['shipping'] += $order_total['value'];
				} elseif ($order_total['code'] == 'tax') {
					$jovepay_args['tax'] += $order_total['value'];
				} elseif ($order_total['code'] == 'total') {
					$jovepay_args['priceAmount'] += $order_total['value'];
				}
			}

			foreach ($this->cart->getProducts() as $product) {
				$jovepay_args['products'][] = array(
					'name' => htmlspecialchars($product['name']),
					'quantity' => $product['quantity'],
					'price' => $product['quantity'] * $product['price'],
					'pricePerItem' => $product['price'],
				);
			}

			$jovepay_adr .= urlencode(base64_encode(json_encode($jovepay_args)));

			$data['jovepay_link'] = $jovepay_adr;

			return $this->load->view('extension/payment/jovepay', $data);
		}
	}

	public function callback()
	{
		if (version_compare(phpversion(), '7.1', '>=')) {
			ini_set('serialize_precision', -1);
		}

		$this->load->model('checkout/order');
		try {
			$payload = $this->validateIpn();

			$this->successful_request($payload);
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

	function successful_request(array $request_data)
	{
		if (empty($request_data['orderId'])) {
			throw new \RuntimeException('Missing orderId', 400);
		}

		$valid_order_id = (int) str_replace('OC-', '', $request_data['orderId']);
		$order = $this->model_checkout_order->getOrder($valid_order_id);

		if (!$order) {
			throw new \RuntimeException('Order not found', 404);
		}

		if (!$this->shouldUpdateStatus($order)) {
			throw new \RuntimeException('Order already processed', 400);
		}

		if (strtoupper($request_data['priceCurrency']) !== strtoupper($order['currency_code'])) {
			throw new \RuntimeException('Currency mismatch', 400);
		}

		if ((float) $request_data['priceAmount'] < (float) $order['total']) {
			throw new \RuntimeException('Amount less than order total', 400);
		}

		if ($request_data['paymentStatus'] == 'finished') {
			$this->update_status($valid_order_id, 'finished', 'Order has been paid.');
		} else if ($request_data['paymentStatus'] == 'partially_paid') {
			$this->update_status($valid_order_id, 'partially_paid', 'Your payment is partially paid. Please contact support@jovepay.com Amount received: ' . $request_data['actually_paid']);
		} else if ($request_data['paymentStatus'] == 'confirming') {
			$this->update_status($valid_order_id, 'confirming', 'Order is processing.');
		} else if ($request_data['paymentStatus'] == 'confirmed') {
			$this->update_status($valid_order_id, 'confirmed', 'Order is processing.');
		} else if ($request_data['paymentStatus'] == 'sending') {
			$this->update_status($valid_order_id, 'sending', 'Order is processing.');
		} else if ($request_data['paymentStatus'] == 'failed') {
			$this->update_status($valid_order_id, 'failed', 'Order is failed. Please contact support@jovepay.com');
		}
	}

	public function confirm()
	{
		if ($this->session->data['payment_method']['code'] == 'jovepay') {
			$this->load->model('checkout/order');

			// $order = $this->model_checkout_order->getOrder($this->session->data['order_id']);
            //TODO: Ensure that this order update is the first thing to happen before backend sends status update. Currently, this is not the case.		
			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $this->config->get('payment_jovepay_confirmed_status_id'), '', '');

			$this->response->redirect($this->url->link('checkout/success'));
		}
	}

	function update_status($order_id, $status, $comment)
	{
		$order_status_id = $this->config->get('payment_jovepay_' . $status . '_status_id');
		$this->model_checkout_order->addOrderHistory($order_id, $order_status_id, $comment, true);
	}

	private function shouldUpdateStatus(array $order): bool
	{
		$terminal = [
			(int) $this->config->get('payment_jovepay_finished_status_id'),
			(int) $this->config->get('payment_jovepay_failed_status_id'),
		];

		return !in_array((int) $order['order_status_id'], $terminal, true);
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
