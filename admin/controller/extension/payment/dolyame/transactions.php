<?php

class ControllerExtensionPaymentDolyameTransactions extends Controller
{
	private $error = array();

	private $dolyamelib = null;

	public function __construct($registry)
	{

		$this->registry = $registry;
		if (method_exists($this->load, "library")) {
			$this->load->library('dolyamelib');
		}
		$this->dolyamelib = new Dolyamelib($this->registry);
	}

	public function index()
	{
		$data = [];
		$this->load->language('extension/payment/dolyame/transactions');
		$this->load->model('extension/payment/dolyame/transactions');

		$this->document->setTitle($this->language->get('heading_title'));

		if (isset($this->request->get['filter_order_id'])) {
			$filter_order_id = $this->request->get['filter_order_id'];
		} else {
			$filter_order_id = null;
		}

		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'o.order_id';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'DESC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['filter_order_id'])) {
			$url .= '&filter_order_id=' . $this->request->get['filter_order_id'];
		}

		$url .= '&sort=' . $sort;

		$url .= '&page=' . $page;

		if ($order == 'ASC') {
			$url .= '&order=DESC';
		} else {
			$url .= '&order=ASC';
		}

		$tokenKey = $this->dolyamelib->getTokenKey();

		$data['sort_order']  = $this->url->link('extension/payment/dolyame/transactions', $tokenKey.'=' . $this->session->data[$tokenKey] . '&sort=o.order_id' . $url, true);
		$data['sort_status'] = $this->url->link('extension/payment/dolyame/transactions', $tokenKey.'=' . $this->session->data[$tokenKey] . '&sort=o.status' . $url, true);
		$data['sort_total']  = $this->url->link('extension/payment/dolyame/transactions', $tokenKey.'=' . $this->session->data[$tokenKey] . '&sort=o.amount' . $url, true);

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $tokenKey.'=' . $this->session->data[$tokenKey], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/dolyame/transactions', $tokenKey.'=' . $this->session->data[$tokenKey] . $url, true),
		);

		$data['transactions'] = array();

		$filter_data = array(
			'filter_order_id' => $filter_order_id,
			'sort'            => $sort,
			'order'           => $order,
			'start'           => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'           => $this->config->get('config_limit_admin'),
		);

		$order_total = $this->model_extension_payment_dolyame_transactions->getTotalTransactions($filter_data);

		$results = $this->model_extension_payment_dolyame_transactions->getTransactions($filter_data);

		foreach ($results as $result) {
			$info = [
				'status' => $this->language->get('payment_status_' . $result['status']),
				'amount' => $result['amount'],
				'view'   => $this->url->link('sale/order/info', $tokenKey.'=' . $this->session->data[$tokenKey] . '&order_id=' . $result['order_id'] . $url, true),
				'edit'   => $this->url->link('sale/order/edit', $tokenKey.'=' . $this->session->data[$tokenKey] . '&order_id=' . $result['order_id'] . $url, true),
			];

			if ($result['status'] == 'committed') {
				$info['refund'] = $this->url->link('extension/payment/dolyame/transactions/refund', $tokenKey.'=' . $this->session->data[$tokenKey] . '&order_id=' . $result['order_id'] . $url, true);
			}

			$result                 = array_merge($result, $info);
			$data['transactions'][] = $result;
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list']          = $this->language->get('text_list');
		$data['text_no_results']    = $this->language->get('text_no_results');
		$data['text_confirm']       = $this->language->get('text_confirm');
		$data['text_missing']       = $this->language->get('text_missing');
		$data['text_loading']       = $this->language->get('text_loading');
		$data['text_refund_action'] = $this->language->get('text_refund_action');

		$data['column_order_id']       = $this->language->get('column_order_id');
		$data['column_status']         = $this->language->get('column_status');
		$data['column_total']          = $this->language->get('column_total');
		$data['column_action']         = $this->language->get('column_action');
		$data['column_payment_status'] = $this->language->get('column_payment_status');

		$data['entry_order_id'] = $this->language->get('entry_order_id');

		$data['button_invoice_print']  = $this->language->get('button_invoice_print');
		$data['button_shipping_print'] = $this->language->get('button_shipping_print');
		$data['button_add']            = $this->language->get('button_add');
		$data['button_edit']           = $this->language->get('button_edit');
		$data['button_delete']         = $this->language->get('button_delete');
		$data['button_filter']         = $this->language->get('button_filter');
		$data['button_view']           = $this->language->get('button_view');
		$data['button_ip_add']         = $this->language->get('button_ip_add');
		$data['text_ip_add'] = sprintf($this->language->get('text_ip_add'), $this->request->server['REMOTE_ADDR']);

		$data['user_token'] = $this->session->data[$tokenKey];

		$pagination        = new Pagination();
		$pagination->total = $order_total;
		$pagination->page  = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url   = $this->url->link('extension/payment/dolyame/transactions', $tokenKey.'=' . $this->session->data[$tokenKey] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($order_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($order_total - $this->config->get('config_limit_admin'))) ? $order_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $order_total, ceil($order_total / $this->config->get('config_limit_admin')));

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array) $this->request->post['selected'];
		} else {
			$data['selected'] = array();
		}

		$data['filter_order_id'] = $filter_order_id;
		$data['sort']            = $sort;
		$data['order']           = $order;

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		if (isset($this->session->data['success'])) {
			$data['success'] = $this->session->data['success'];

			unset($this->session->data['success']);
		} else {
			$data['success'] = '';
		}

		if (isset($this->session->data['error_warning'])) {
			$data['error_warning'] = $this->session->data['error_warning'];

			unset($this->session->data['error_warning']);
		} else {
			$data['error_warning'] = '';
		}

		$data['catalog'] = $this->request->server['HTTPS'] ? HTTPS_CATALOG : HTTP_CATALOG;

		$data['refund_status'] = $this->dolyamelib->getConfig('dolyame_order_refund_status_id');

		$this->load->model('user/api');

		$api_info = $this->model_user_api->getApi($this->config->get('config_api_id'));

		if ($api_info) {
			$data['api_id'] = $api_info['api_id'];
			$data['api_key'] = $api_info['key'];
			$data['api_ip'] = $this->request->server['REMOTE_ADDR'];
			if (!$this->dolyamelib->is23()) {
				$session = new Session($this->config->get('session_engine'), $this->registry);
				$session->start();
				if (isset($this->model_user_api->deleteApiSessionBySessonId)) {
					$this->model_user_api->deleteApiSessionBySessonId($session->getId());
				} else {
					$this->model_user_api->deleteApiSessionBySessionId($session->getId());
				}
				$this->model_user_api->addApiSession($api_info['api_id'], $session->getId(), $this->request->server['REMOTE_ADDR']);
				$session->data['api_id'] = $api_info['api_id'];
				$data['api_token'] = $session->getId();
			}
		} else {
			$data['api_id'] = '';
			$data['api_key'] = '';
			$data['api_ip'] = '';
			$data['api_token'] = '';
		}


		$this->response->setOutput($this->load->view('extension/payment/dolyame/transactions', $data));
	}

	public function refund()
	{
		$this->load->language('extension/payment/dolyame/transactions');
		$orderId = $this->request->post['order_id'];

		$data = $this->prepareRefundData($_POST);
		$json  = ['order_id' => $orderId];

		try {
			$client   = $this->dolyamelib->initClient();
			$response = $client->refund($this->dolyamelib->getConfig('dolyame_prefix').$orderId, $data);
			$this->db->query("UPDATE " . DB_PREFIX . "dolyame set status='refunded', refund_id='" . $this->db->escape($response['refund_id']) . "' where order_id=" . intval($orderId));
			$json['comment'] = $this->language->get('text_refund_info').$response['refund_id'];
		} catch (\Exception $e) {
			$json['error'] = $this->language->get('text_action_error') . $e->getMessage();
		}
		$this->response->addHeader('Content-Type: application/json');
        $this->response->setOutput(json_encode($json));
	}

	private function prepareRefundData($post)
	{
		$refundableSum = 0;
		$refundItems   = [];
		foreach ($post['selected'] as $position) {
			$refundableSum += $post['price'][$position] * $post['quantity'][$position];
			$item = [
				"name"     => $post['name'][$position],
				"quantity" => $post['quantity'][$position],
				"price"    => $post['price'][$position],
			];
			if ($post['sku'][$position]) {
				$item['sku'] = $post['sku'][$position];
			}
			$refundItems[] = $item;
		}

		$refundableSum -= $post['refunded_prepaid_amount'];

		$data = [
			'amount'                  => number_format($refundableSum, 2, '.', ''),
			'returned_items'          => $refundItems,
			'refunded_prepaid_amount' => $post['refunded_prepaid_amount'],
		];
		return $data;
	}

	public function items()
	{
		$tokenKey = $this->dolyamelib->getTokenKey();
		$orderId = $this->request->get['order_id'];
		$this->load->language('extension/payment/dolyame/transactions');
		$this->load->model('extension/payment/dolyame/transactions');
		$this->load->model('sale/order');
		$items = $this->model_extension_payment_dolyame_transactions->getOrderItems($orderId);
		$order = $this->model_sale_order->getOrder($orderId);
		$amount = $this->currency->format($order['total'], $order['currency_code'], $order['currency_value'], false);
		$prepaid = $this->dolyamelib->calcPrepaidAmount($amount, $items);
		$data  = [
			'items'                       => $items,
			'column_transaction_name'     => $this->language->get('column_transaction_name'),
			'column_transaction_quantity' => $this->language->get('column_transaction_quantity'),
			'column_transaction_price'    => $this->language->get('column_transaction_price'),
			'text_total_sum'              => $this->language->get('text_total_sum'),
			'text_return_action'          => $this->language->get('text_return_action'),
			'text_prepaid_amount'         => $this->language->get('text_prepaid_amount'),
			'user_token'                  => $this->session->data[$tokenKey],
			'prepaid'                     => $prepaid,
			'order_id'                    => $orderId,
		];
		$this->response->setOutput($this->load->view('extension/payment/dolyame/items', $data));
	}

}
