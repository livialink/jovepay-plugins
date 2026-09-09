<?php
namespace Opencart\Admin\Controller\Extension\JovePay\Payment;

const JOVEPAY_VERSION = '1.0.0';

class Jovepay extends \Opencart\System\Engine\Controller {

    private $error = array();

	public function index() {
		$this->load->language('extension/jovepay/payment/jovepay');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('payment_jovepay', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['api_key'])) {
			$data['error_api_key'] = $this->error['api_key'];
		} else {
			$data['error_api_key'] = '';
		}
		
		if (isset($this->error['ipn_secret'])) {
			$data['error_ipn_secret'] = $this->error['ipn_secret'];
		} else {
			$data['error_ipn_secret'] = '';
		}

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/jovepay/payment/jovepay', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/jovepay/payment/jovepay', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=payment', true);

		if (isset($this->request->post['payment_jovepay_mode'])) {
			$data['payment_jovepay_mode'] = $this->request->post['payment_jovepay_mode'];
		} else {
			$data['payment_jovepay_mode'] = $this->config->get('payment_jovepay_mode');
		}
		if (isset($this->request->post['payment_jovepay_theme'])) {
			$data['payment_jovepay_theme'] = $this->request->post['payment_jovepay_theme'];
		} else {
			$data['payment_jovepay_theme'] = $this->config->get('payment_jovepay_theme');
		}

		if (isset($this->request->post['payment_jovepay_api_key'])) {
			$data['payment_jovepay_api_key'] = $this->request->post['payment_jovepay_api_key'];
		} else {
			$data['payment_jovepay_api_key'] = $this->config->get('payment_jovepay_api_key');
		}
		
		if (isset($this->request->post['payment_jovepay_ipn_secret'])) {
			$data['payment_jovepay_ipn_secret'] = $this->request->post['payment_jovepay_ipn_secret'];
		} else {
			$data['payment_jovepay_ipn_secret'] = $this->config->get('payment_jovepay_ipn_secret');
		}
		
		if (isset($this->request->post['payment_jovepay_title'])) {
			$data['payment_jovepay_title'] = $this->request->post['payment_jovepay_title'];
		} else {
			$data['payment_jovepay_title'] = $this->config->get('payment_jovepay_title');
		}
		
		if (isset($this->request->post['payment_jovepay_email'])) {
			$data['payment_jovepay_email'] = $this->request->post['payment_jovepay_email'];
		} else {
			$data['payment_jovepay_email'] = $this->config->get('payment_jovepay_email');
		}


		$jovepay_statuses = array('finished', 'partially_paid', 'confirming', 'confirmed', 'sending', 'failed');
		
		foreach ($jovepay_statuses as $jovepay_status) {
			if (isset($this->request->post['payment_jovepay_' . $jovepay_status . '_status_id'])) {
				$data['payment_jovepay_' . $jovepay_status . '_status_id'] = $this->request->post['payment_jovepay_finished_status_id'];
			} elseif ($this->config->get('payment_jovepay_' . $jovepay_status . '_status_id')) {
				$data['payment_jovepay_' . $jovepay_status . '_status_id'] = $this->config->get('payment_jovepay_' . $jovepay_status . '_status_id');
			} else {
				$data['payment_jovepay_' . $jovepay_status . '_status_id'] = $this->config->get('config_order_status_id');
			}		
		}
		
		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		if (isset($this->request->post['payment_jovepay_status'])) {
			$data['payment_jovepay_status'] = $this->request->post['payment_jovepay_status'];
		} else {
			$data['payment_jovepay_status'] = $this->config->get('payment_jovepay_status');
		}

		if (isset($this->request->post['payment_jovepay_sort_order'])) {
			$data['payment_jovepay_sort_order'] = $this->request->post['payment_jovepay_sort_order'];
		} else {
			$data['payment_jovepay_sort_order'] = $this->config->get('payment_jovepay_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/jovepay/payment/jovepay', $data));
	}

	private function validate() {
		if (!$this->user->hasPermission('modify', 'extension/jovepay/payment/jovepay')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['payment_jovepay_ipn_secret']) {
			$this->error['ipn_secret'] = $this->language->get('error_ipn_secret');
		}

		if (!$this->request->post['payment_jovepay_title']) {
			$this->error['title'] = $this->language->get('error_title');
		}

		if (!$this->request->post['payment_jovepay_api_key']) {
			$this->error['api_key'] = $this->language->get('error_api_key');
		}

		return !$this->error;
	}

}
