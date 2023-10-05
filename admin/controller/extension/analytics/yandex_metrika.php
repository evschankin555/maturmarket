<?php
class ControllerExtensionAnalyticsYandexMetrika extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/analytics/yandex_metrika');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('analytics_yandex_metrika', $this->request->post, $this->request->get['store_id']);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true));

		}
		
		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_help'] = $this->language->get('text_help');
		$data['text_yes'] = $this->language->get('text_yes');
		$data['text_no'] = $this->language->get('text_no');
		$data['text_no_users'] = $this->language->get('text_no_users');
		$data['text_no_admin'] = $this->language->get('text_no_admin');
		$data['text_yandex_metrika'] = $this->language->get('text_yandex_metrika');
		$data['text_help_counter'] = $this->language->get('text_help_counter');

		$data['entry_code'] = $this->language->get('entry_code');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_no_admin'] = $this->language->get('entry_no_admin');
		$data['entry_no_users'] = $this->language->get('entry_no_users');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['code'])) {
			$data['error_code'] = $this->error['code'];
		} else {
			$data['error_code'] = '';
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_extension'),
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/analytics/yandex_metrika', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true)
		);

		$data['action'] = $this->url->link('extension/analytics/yandex_metrika', 'user_token=' . $this->session->data['user_token'] . '&store_id=' . $this->request->get['store_id'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=analytics', true);
		
		$data['user_token'] = $this->session->data['user_token'];

		if (isset($this->request->get['store_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$setting_info = $this->model_setting_setting->getSetting('analytics_yandex_metrika', $this->request->get['store_id']);
		}

		if (isset($this->request->post['analytics_yandex_metrika_code'])) {
			$data['analytics_yandex_metrika_code'] = $this->request->post['analytics_yandex_metrika_code'];
		} else {
			$data['analytics_yandex_metrika_code'] = $this->config->get('analytics_yandex_metrika_code');
		}
		
		if (isset($this->request->post['analytics_yandex_metrika_status'])) {
			$data['analytics_yandex_metrika_status'] = $this->request->post['analytics_yandex_metrika_status'];
		} else {
			$data['analytics_yandex_metrika_status'] = $this->config->get('analytics_yandex_metrika_status');
		}

		if (isset($this->request->post['analytics_yandex_metrika_no_admin'])) {
			$data['analytics_yandex_metrika_no_admin'] = $this->request->post['analytics_yandex_metrika_no_admin'];
		} elseif (isset($setting_info['analytics_yandex_metrika_no_admin'])) {
			$data['analytics_yandex_metrika_no_admin'] = $setting_info['analytics_yandex_metrika_no_admin'];
		} else {
			$data['analytics_yandex_metrika_no_admin'] = '';
		}
		
		if (isset($this->request->post['analytics_yandex_metrika_no_users'])) {
			$data['analytics_yandex_metrika_no_users'] = $this->request->post['analytics_yandex_metrika_no_users'];
		} elseif (isset($setting_info['analytics_yandex_metrika_no_users'])) {
			$data['analytics_yandex_metrika_no_users'] = $setting_info['analytics_yandex_metrika_no_users'];
		} else {
			$data['analytics_yandex_metrika_no_users'] = '';
		}
		
		if (isset($this->request->post['analytics_yandex_metrika_counter'])) {
			$data['analytics_yandex_metrika_counter'] = $this->request->post['analytics_yandex_metrika_counter'];
		} elseif (isset($setting_info['analytics_yandex_metrika_counter'])) {
			$data['analytics_yandex_metrika_counter'] = $setting_info['analytics_yandex_metrika_counter'];
		} else {
			$data['analytics_yandex_metrika_counter'] = '';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/analytics/yandex_metrika', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/analytics/yandex_metrika')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->request->post['analytics_yandex_metrika_code']) {
			$this->error['code'] = $this->language->get('error_code');
		}			

		return !$this->error;
	}
}
