<?php
class ControllerExtensionPaymentCod extends Controller {
	public function index() {
		return $this->load->view('extension/payment/cod');
	}

	public function confirm() {
		$json = array();
		
		if (isset($this->session->data['payment_method']['code']) && $this->session->data['payment_method']['code'] == 'cod') {

			$shipping_code = explode('.', $this->session->data['shipping_method']['code'])[0];
			$this->load->language('extension/shipping/'.$shipping_code);
			$shipping_order_status_id = $this->config->get('shipping_'.$shipping_code.'_order_status_id');

			$this->load->model('checkout/order');

			$this->model_checkout_order->addOrderHistory($this->session->data['order_id'], $shipping_order_status_id ? $shipping_order_status_id : $this->config->get('payment_cod_order_status_id'));
		
			$json['redirect'] = $this->url->link('checkout/success');
		}
		
		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));		
	}
}
