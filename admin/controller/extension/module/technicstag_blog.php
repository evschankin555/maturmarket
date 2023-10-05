<?php
class ControllerExtensionModuleTechnicstagblog extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technicstag_blog');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			$this->model_setting_setting->editSetting('module_technicstag_blog', $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_limit'] = $this->language->get('entry_limit');

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
			'href' => $this->url->link('extension/module/technicstag_blog', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['action'] = $this->url->link('extension/module/technicstag_blog', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->post['module_technicstag_blog_status'])) {
			$data['module_technicstag_blog_status'] = $this->request->post['module_technicstag_blog_status'];
		} else {
			$data['module_technicstag_blog_status'] = $this->config->get('module_technicstag_blog_status');
		}

		if (isset($this->request->post['module_technicstag_blog_limit'])) {
			$data['module_technicstag_blog_limit'] = $this->request->post['module_technicstag_blog_limit'];
		} elseif($this->config->get('module_technicstag_blog_limit')) {
			$data['module_technicstag_blog_limit'] = $this->config->get('module_technicstag_blog_limit');
		} else {
			$data['module_technicstag_blog_limit'] = 20;
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicstag_blog', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/technicstag_blog')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function install() {

		$this->load->model('user/user_group');

		$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'catalog/technicstag_blog');
		$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'catalog/technicstag_blog');
		$this->model_user_user_group->addPermission($this->user->getId(), 'access', 'extension/module/technicstag_blog');
		$this->model_user_user_group->addPermission($this->user->getId(), 'modify', 'extension/module/technicstag_blog'); 
	}

	public function uninstall() {

	}
}