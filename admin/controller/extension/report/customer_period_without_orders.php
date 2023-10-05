<?php
class ControllerExtensionReportCustomerPeriodWithoutOrders extends Controller {
	public function index() {
		$this->load->language('extension/report/customer_period_without_orders');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('report_customer_period_without_orders', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true));
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/report/customer_period_without_orders', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/report/customer_period_without_orders', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=report', true);

		if (isset($this->request->post['report_customer_period_without_orders_status'])) {
			$data['report_customer_period_without_orders_status'] = $this->request->post['report_customer_period_without_orders_status'];
		} else {
			$data['report_customer_period_without_orders_status'] = $this->config->get('report_customer_period_without_orders_status');
		}

		if (isset($this->request->post['report_customer_period_without_orders_sort_order'])) {
			$data['report_customer_period_without_orders_sort_order'] = $this->request->post['report_customer_period_without_orders_sort_order'];
		} else {
			$data['report_customer_period_without_orders_sort_order'] = $this->config->get('report_customer_period_without_orders_sort_order');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/report/customer_period_without_orders_form', $data));
	}
	
	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/report/customer_period_without_orders')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
		
	public function report() {
		$this->load->language('extension/report/customer_period_without_orders');

		if (isset($this->request->get['filter_customer_group_id'])) {
			$filter_customer_group_id = $this->request->get['filter_customer_group_id'];
		} else {
			$filter_customer_group_id = '';
		}

		if (isset($this->request->get['filter_period_without_orders'])) {
			$filter_period_without_orders = $this->request->get['filter_period_without_orders'];
		} else {
			$filter_period_without_orders = '30';
		}

        if (isset($this->request->get['filter_orders_count'])) {
			$filter_orders_count = $this->request->get['filter_orders_count'];
		} else {
			$filter_orders_count = '1';
		}

		if (isset($this->request->get['page'])) {
			$page = (int)$this->request->get['page'];
		} else {
			$page = 1;
		}

		$this->load->model('extension/report/customer');

		$data['customers'] = array();

		$filter_data = array(
			'filter_customer_group_id'	=> $filter_customer_group_id,
			'filter_period_without_orders'	=> $filter_period_without_orders,
            'filter_orders_count'	=> $filter_orders_count,
			'start'				=> ($page - 1) * $this->config->get('config_limit_admin'),
			'limit'				=> $this->config->get('config_limit_admin')
		);

		$customer_total = $this->model_extension_report_customer->getTotalByPeriodWithoutOrders($filter_data);

		$results = $this->model_extension_report_customer->getByPeriodWithoutOrders($filter_data);

		foreach ($results as $result) {
			$data['customers'][] = array(
				'customer'       => $result['customer'],
				'phone'          => $result['telephone'],
				'customer_group' => $result['customer_group'],
				'status'         => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
				'last_order_date'         => $result['last_order_date'],
				'orders'         => $result['orders_count'],
				'total'          => $this->currency->format($result['orders_total'], $this->config->get('config_currency')),
				'edit'           => $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $result['customer_id'], true),
				'customer_purchases' => isset($result['customer_id']) ? $this->url->link('sale/order', 'user_token=' . $this->session->data['user_token'] . '&filter_customer=' . $result['customer'] , true) : ''
			);
		}

		$data['user_token'] = $this->session->data['user_token'];

        $this->load->model('customer/customer_group');
        $data['customer_groups'] = $this->model_customer_customer_group->getCustomerGroups(array('sort' => 'cg.sort_order'));

		$url = '';

		if (isset($this->request->get['filter_customer_group_id'])) {
			$url .= '&filter_customer_group_id=' . $this->request->get['filter_customer_group_id'];
		}

		if (isset($this->request->get['filter_period_without_orders'])) {
			$url .= '&filter_period_without_orders=' . $this->request->get['filter_period_without_orders'];
		}

		if (isset($this->request->get['filter_orders_count'])) {
			$url .= '&filter_orders_count=' . urlencode($this->request->get['filter_orders_count']);
		}

		$pagination = new Pagination();
		$pagination->total = $customer_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('report/report', 'user_token=' . $this->session->data['user_token'] . '&code=customer_period_without_orders' . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($customer_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($customer_total - $this->config->get('config_limit_admin'))) ? $customer_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $customer_total, ceil($customer_total / $this->config->get('config_limit_admin')));

		$data['filter_customer_group_id'] = $filter_customer_group_id;
		$data['filter_period_without_orders'] = $filter_period_without_orders;
		$data['filter_orders_count'] = $filter_orders_count;
	
		return $this->load->view('extension/report/customer_period_without_orders_info', $data);
	}
}