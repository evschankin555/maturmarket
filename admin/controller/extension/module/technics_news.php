<?php
class ControllerExtensionModuleTechnicsNews extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technics_news');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');
		
		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('technics_news', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');
		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_limit'] = $this->language->get('entry_limit');
		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

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
			'href' => $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics_news', 'user_token=' . $this->session->data['user_token'], true)
		);
		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}	
		
		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		} 	
		
		if (isset($this->request->post['limit'])) {
			$data['limit'] = $this->request->post['limit'];
		} elseif (!empty($module_info) && isset($module_info['limit'])) {
			$data['limit'] = $module_info['limit'];
		} else {
			$data['limit'] = 3;
		} 

		if (isset($this->error['news_name'])) {
			$data['error_news_name'] = $this->error['news_name'];
		} else {
			$data['error_news_name'] = '';
		}
		
		if (isset($this->error['news_limit'])) {
			$data['error_news_limit'] = $this->error['news_limit'];
		} else {
			$data['error_news_limit'] = '';
		}
		
//		$data['action'] = $this->url->link('extension/module/technics_news', 'user_token=' . $this->session->data['user_token'], true);
		
		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/technics_news', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics_news', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = 0;
		} 

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technics_news', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics_news')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}
		
		if (!$this->request->post['name']) {
			$this->error['news_name'] = $this->language->get('error_name');
		}
		
		if (!$this->request->post['limit']) {
			$this->error['news_limit'] = $this->language->get('error_limit');
		}
		return !$this->error;
	}
	
	public function install() {
		$this->load->model('extension/theme/technics');

		$this->model_extension_theme_technics->installNews();

		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'catalog/technics_news');
		$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'catalog/technics_news');
	}

	public function uninstall() {
		$this->load->model('extension/theme/technics');

		$this->model_extension_theme_technics->uninstallNews();
	}
}
