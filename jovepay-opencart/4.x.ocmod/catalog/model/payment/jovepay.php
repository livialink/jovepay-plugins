<?php
namespace Opencart\Catalog\Model\Extension\JovePay\Payment;



class Jovepay extends \Opencart\System\Engine\Model {

	// OC 4.0.1
	public function getMethod($address, $total) {
		$this->load->language('extension/jovepay/payment/jovepay');
		$method_data = array(
			'code'       => 'jovepay',
			'title'      => $this->config->get('payment_jovepay_title') ? $this->config->get('payment_jovepay_title') : $this->language->get('text_title'),
			'terms'      => '',
			'sort_order' => $this->config->get('payment_jovepay_sort_order')
		);
		
		return $method_data;
	}

    // OC 4.0.2
    public function getMethods(array $address): array
    {
        $this->load->language('extension/jovepay/payment/jovepay');
        $logo = $this->config->get('config_url') . 'extension/jovepay/admin/view/image/jovepay/jovepay.png';
        $title = $this->config->get('payment_jovepay_title') ? $this->config->get('payment_jovepay_title') : $this->language->get('text_title');

        $option_data['jovepay'] = [
            'code' => 'jovepay.jovepay',
            'name' => $title
        ];

        $method_data = [
            'code' => 'jovepay',
            'name' => $title, //"<img src='$logo' alt='Jovepay' title='Jovepay' style='width: 30%' /> &nbsp;" . $title,
            'option' => $option_data,
            'sort_order' => $this->config->get('payment_jovepay_sort_order')
        ];

        return $method_data;
    }

}
