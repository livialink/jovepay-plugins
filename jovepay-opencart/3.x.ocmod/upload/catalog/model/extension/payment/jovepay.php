<?php
class ModelExtensionPaymentJovepay extends Model {
	public function getMethod($address, $total) {
		$this->load->language('extension/payment/jovepay');
		$method_data = array(
			'code'       => 'jovepay',
			'title'      => $this->config->get('payment_jovepay_title') ? $this->config->get('payment_jovepay_title') : $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_jovepay_sort_order')
		);
		
		return $method_data;
	}
}
