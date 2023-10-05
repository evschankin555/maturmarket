<?php

class ControllerExtensionPaymentDolyame extends Controller
{
	private $error  = array();
	private $prefix = '';

	public function __construct($registry)
	{
		$this->prefix = $this->is23() ? '' : 'payment_';
		parent::__construct($registry);
	}

	private function is23()
	{
		return version_compare(VERSION, '3.0.0.0') == -1;
	}

	private function getTokenKey()
	{
		return $this->is23() ? 'token' : 'user_token';
	}

	public function index()
	{
		$data = [];
		$this->load->language('extension/payment/dolyame');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$tokenKey   = $this->getTokenKey();
		$returnPath = $this->is23() ? 'extension/extension' : 'marketplace/extension';

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting($this->prefix . 'dolyame', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');
			$this->response->redirect($this->url->link($returnPath, $tokenKey . '=' . $this->session->data[$tokenKey] . '&type=payment', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled']   = $this->language->get('text_enabled');
		$data['help_total']     = $this->language->get('help_total');
		$data['help_key_path']  = $this->language->get('help_key_path');
		$data['help_cert_path'] = $this->language->get('help_cert_path');
		$data['text_disabled']  = $this->language->get('text_disabled');
		$data['text_all_zones'] = $this->language->get('text_all_zones');

		$data['entry_order_status']        = $this->language->get('entry_order_status');
		$data['entry_order_refund_status'] = $this->language->get('entry_order_refund_status');
		$data['entry_geo_zone']            = $this->language->get('entry_geo_zone');

		$data['text_edit'] = $this->language->get('text_edit');

		$data['button_save']   = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['paymentname'])) {
			$data['error_paymentname'] = $this->error['paymentname'];
		} else {
			$data['error_paymentname'] = '';
		}

		if (isset($this->error['key_path'])) {
			$data['error_key_path'] = $this->error['key_path'];
		} else {
			$data['error_key_path'] = '';
		}

		if (isset($this->error['cert_path'])) {
			$data['error_cert_path'] = $this->error['cert_path'];
		} else {
			$data['error_cert_path'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', $tokenKey . '=' . $this->session->data[$tokenKey], true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link($returnPath, $tokenKey . '=' . $this->session->data[$tokenKey] . '&type=payment', true),
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/payment/dolyame', $tokenKey . '=' . $this->session->data[$tokenKey], true),
		);

		$data['action'] = $this->url->link('extension/payment/dolyame', $tokenKey . '=' . $this->session->data[$tokenKey], true);

		$data['cancel'] = $this->url->link($returnPath, $tokenKey . '=' . $this->session->data[$tokenKey] . '&type=payment', true);

		$settings = array(
			'paymentname'            => $this->language->get('text_paymentname'),
			'login'                  => '',
			'password'               => '',
			'cert_path'              => '',
			'key_path'               => '',
			'prefix'               => '',
			'total'                  => '',
			'order_status_id'        => '5', //Complete
			'order_refund_status_id' => '11', //Refunded
			'geo_zone_id'            => 0,
			'status'                 => 0,
			'sort_order'             => '',
		);

		foreach ($settings as $key => $default) {
			$data['entry_' . $key] = $this->language->get('entry_' . $key);

			if (isset($this->request->post[$this->prefix . 'dolyame_' . $key])) {
				$data[$this->prefix . 'dolyame_' . $key] = $this->request->post[$this->prefix . 'dolyame_' . $key];
			} elseif ($this->config->has($this->prefix . 'dolyame_' . $key)) {
				$data[$this->prefix . 'dolyame_' . $key] = $this->config->get($this->prefix . 'dolyame_' . $key);
			} else {
				$data[$this->prefix . 'dolyame_' . $key] = $default;
			}
		}

		$this->load->model('localisation/order_status');

		$data['order_statuses'] = $this->model_localisation_order_status->getOrderStatuses();

		$this->load->model('localisation/geo_zone');

		$data['geo_zones'] = $this->model_localisation_geo_zone->getGeoZones();

		$data['header']      = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer']      = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/payment/dolyame', $data));
	}

	protected function validate()
	{
		if (!$this->user->hasPermission('modify', 'extension/payment/dolyame')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post[$this->prefix . 'dolyame_paymentname']) {
			$this->error['paymentname'] = $this->language->get('error_paymentname');
		}

		$certPath = isset($this->request->post[$this->prefix . 'dolyame_cert_path']) ? $this->request->post[$this->prefix . 'dolyame_cert_path']: '';
		$certPath = DIR_APPLICATION . '../' . $certPath;

		if (!file_exists($certPath)) {
			$this->error['cert_path'] = $this->language->get('error_cert_path');
		}

		$keyPath = isset($this->request->post[$this->prefix . 'dolyame_key_path']) ?$this->request->post[$this->prefix . 'dolyame_key_path']: '';
		$keyPath = DIR_APPLICATION . '../' . $keyPath;

		if (!file_exists($keyPath)) {
			$this->error['key_path'] = $this->language->get('error_key_path');
		}

		return !$this->error;
	}

	public function install()
	{
		$this->db->query("
			CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "dolyame` (
				`order_id` int(11) NOT NULL,
				`status` varchar(20)  NOT NULL,
				`items` text NOT NULL,
				`receipt` text NULL,
				`date` datetime NOT NULL,
				`amount` decimal(10,2) NOT NULL,
				`refund_id` varchar(36) DEFAULT '',
				`payment_schedule` text NULL,
				PRIMARY KEY (`order_id`)
		)  ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ");

	}

	public function uninstall()
	{
		$this->db->query(' drop table IF EXISTS `' . DB_PREFIX . 'dolyame`');
	}
}
