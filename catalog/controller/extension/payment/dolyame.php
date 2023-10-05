<?php

class ControllerExtensionPaymentdolyame extends Controller
{
	private $dolyamelib = null;

	public function __construct($registry) {

		$this->registry = $registry;
		if (method_exists($this->load, "library")) {
			$this->load->library('dolyamelib');
		}
		$this->dolyamelib = new DolyameLib($this->registry);
	}

	public function index()
	{

		$this->load->model('checkout/order');
		$this->load->model('extension/payment/dolyame');

		$data = [
			'button_confirm' => $this->language->get('button_confirm'),
			'text_loading'   => $this->language->get('text_loading'),
		];

		return $this->load->view('extension/payment/dolyame', $data);
	}

	public function send()
	{
		$this->load->model('checkout/order');
		$this->load->model('extension/payment/dolyame');

		$orderId   = (int) $this->session->data['order_id'];
		$orderInfo = $this->model_checkout_order->getOrder($orderId);
		$data = $this->dolyamelib->prepareData($orderInfo);
		try {
			$paymentData = $this->dolyamelib->createPayment($data);
		} catch (\Exception $e){
			echo json_encode([
				'error'  => $e->getMessage(),
				]);
			exit();
		}

		$this->dolyamelib->saveTransaction($data, $paymentData);

		$orderStatusId = $this->config->get('config_order_status_id');
		//$this->model_checkout_order->addOrderHistory($orderId, $orderStatusId, $paymentData['link'], true);

		echo json_encode([
			'error'  => false,
			'link' => $paymentData['link'],
		]);
		exit();
	}

	public function callback()
	{
		$this->load->model('checkout/order');
		$orderId = $_REQUEST['order_id'];
		$order = $this->model_checkout_order->getOrder($orderId);
		if (!$order) {
			throw new \Exception('Order not found');
		}
		$orderInfo = $this->dolyamelib->getOrderInfo($orderId);

		$this->dolyamelib->updateTransaction($orderInfo, $orderId);
		if (
			$orderInfo['status'] === 'waiting_for_commit' ||
			$orderInfo['status'] === 'wait_for_commit'
		) {
			$commitResult = $this->dolyamelib->commitPayment($order);
		}


		if ($orderInfo['status'] === 'committed') {
			$shipping_code = explode('.', $order['shipping_code'])[0];
			$this->load->language('extension/shipping/'.$shipping_code);
			$shipping_order_status_id = $this->config->get('shipping_'.$shipping_code.'_order_status_id');
			$status = $shipping_order_status_id ? $shipping_order_status_id : $this->dolyamelib->getConfig('dolyame_order_status_id');

			$this->model_checkout_order->addOrderHistory($orderId, $status, '', true);
		}
		echo 'ok';
	}


}
