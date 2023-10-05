<?php
class ControllerExtensionModuleTechnicsSubscribe extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technics/subscribe');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		$this->getList();
	}

	public function insert() {
		$this->load->language('extension/module/technics/subscribe');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_theme_technics->addSubscribe($this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function update() {
		$this->load->language('extension/module/technics/subscribe');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			
			$this->model_extension_theme_technics->editSubscribe($this->request->get['subscribe_id'], $this->request->post);

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('extension/module/technics/subscribe');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $subscribe_id) {
				$this->model_extension_theme_technics->deleteSubscribe($subscribe_id);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$this->response->redirect($this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token']  . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'email';
		}

		if (isset($this->request->get['order'])) {
			$order = $this->request->get['order'];
		} else {
			$order = 'ASC';
		}

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] .  $url, true)
		);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['module_install'] = $this->model_extension_theme_technics->tableExists();

		if ($data['module_install']) {
			$data['send'] = $this->url->link('extension/module/technics/subscribe/send', 'user_token=' . $this->session->data['user_token'] . $url, true);
			$data['email'] = $this->url->link('extension/module/technics/subscribe/email', 'user_token=' . $this->session->data['user_token'] . $url, true);
			$data['authorization'] = $this->url->link('extension/module/technics/subscribe/authorizationEmail', 'user_token=' . $this->session->data['user_token'] . $url, true);
			$data['insert'] = $this->url->link('extension/module/technics/subscribe/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
			$data['delete'] = $this->url->link('extension/module/technics/subscribe/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

			$data['subscribers'] = array();

			$dataF = array(
				'sort' => $sort,
				'order' => $order,
				'start' => ($page - 1) * $this->config->get('config_limit_admin'),
				'limit' => $this->config->get('config_limit_admin')
			);

			$subscribe_total = $this->model_extension_theme_technics->getTotalSubscibe();

			$results = $this->model_extension_theme_technics->getSubscribers($dataF);

			foreach ($results as $result) {
				$action = array();

				$action[] = array(
					'text' => $this->language->get('button_edit'),
					'href' => $this->url->link('extension/module/technics/subscribe/update', 'user_token=' . $this->session->data['user_token'] . '&subscribe_id=' . $result['subscribe_id'] . $url, true)
				);

				$data['subscribers'][] = array(
					'email' => $result['email'],
					'status' => ($result['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
					'subscribe_id' => $result['subscribe_id'],
					'selected' => isset($this->request->post['selected']) && in_array($result['subscribe_id'], $this->request->post['selected']),
					'action' => $action
				);
			}

			$data['text_no_results'] = $this->language->get('text_no_results');
			$data['text_edit'] = $this->language->get('text_yes');

			$data['column_email'] = $this->language->get('column_email');
			$data['column_status'] = $this->language->get('column_status');
			$data['column_action'] = $this->language->get('column_action');

			$data['button_subscribe'] = $this->language->get('button_subscribe');
			$data['button_email'] = $this->language->get('button_email');
			$data['button_authorization'] = $this->language->get('button_authorization');
			$data['button_insert'] = $this->language->get('button_insert');
			$data['button_delete'] = $this->language->get('button_delete');

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

			$url = '';

			if ($order == 'ASC') {
				$url .= '&order=DESC';
			} else {
				$url .= '&order=ASC';
			}

			if (isset($this->request->get['page'])) {
				$url .= '&page=' . $this->request->get['page'];
			}

			$data['sort_email'] = $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . '&sort=email' . $url, true);
			$data['sort_status'] = $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . '&sort=status' . $url, true);

			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			$pagination = new Pagination();
			$pagination->total = $subscribe_total;
			$pagination->page = $page;
			$pagination->limit = $this->config->get('config_limit_admin');
			$pagination->text = $this->language->get('text_pagination');
			$pagination->url = $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

			$data['pagination'] = $pagination->render();

			$data['sort'] = $sort;
			$data['order'] = $order;
		} else {
			$data['text_module_not_exists'] = $this->language->get('text_module_not_exists');
		}

		$data['header'] = $this->load->controller('common/header');
		$data['footer'] = $this->load->controller('common/footer');
		$data['column_left'] = $this->load->controller('common/column_left');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/subscribe_list', $data));
	}

	protected function getForm() {

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_email'] = $this->language->get('entry_email');
		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['email'])) {
			$data['error_email'] = $this->error['email'];
		} else {
			$data['error_email'] = array();
		}

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url, true),
			'separator' => ' :: '
		);

		if (!isset($this->request->get['subscribe_id'])) {
			$data['action'] = $this->url->link('extension/module/technics/subscribe/insert', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics/subscribe/update', 'user_token=' . $this->session->data['user_token'] . '&subscribe_id=' . $this->request->get['subscribe_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['subscribe_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$subscribe_info = $this->model_extension_theme_technics->getSubscribe($this->request->get['subscribe_id']);
		}

		if (isset($this->request->post['email'])) {
			$data['email'] = $this->request->post['email'];
		} elseif (!empty($subscribe_info)) {
			$data['email'] = $subscribe_info['email'];
		} else {
			$data['email'] = '';
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($subscribe_info)) {
			$data['status'] = $subscribe_info['status'];
		} else {
			$data['status'] = 1;
		}


		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/subscribe_form', $data));
	}

	public function send() {
		$this->load->language('extension/module/technics/subscribe');

		$this->load->model('extension/theme/technics');

		$subscribers = $this->model_extension_theme_technics->getSubscribers();

		if ($this->validateSendMail($subscribers)) {
			foreach ($subscribers as $subscriber) {
				if ($subscriber['status']) {
					$subscribe_descriptions = $this->model_extension_theme_technics->getEmailDescription();
					$text_mail = $subscribe_descriptions[(int) $this->config->get('config_language_id')];
					$subject = sprintf($this->language->get('text_subject_mail'), $this->config->get('config_name'));

					$message = '<html dir="ltr" lang="en">' . "\n";
					$message .= '  <head>' . "\n";
					$message .= '    <title>' . $subject . '</title>' . "\n";
					$message .= '    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">' . "\n";
					$message .= '  </head>' . "\n";
					$message .= '  <body>' . html_entity_decode($text_mail, ENT_QUOTES, 'UTF-8') . '</body>' . "\n";
					$message .= '</html>' . "\n";

					$mail = new Mail();
					$mail->protocol = $this->config->get('config_mail_protocol');
					$mail->parameter = $this->config->get('config_mail_parameter');
					$mail->hostname = $this->config->get('config_smtp_host');
					$mail->username = $this->config->get('config_smtp_username');
					$mail->password = $this->config->get('config_smtp_password');
					$mail->port = $this->config->get('config_smtp_port');
					$mail->timeout = $this->config->get('config_smtp_timeout');
					$mail->setTo($subscriber['email']);
					$mail->setFrom($this->config->get('config_email'));
					$mail->setSender($this->config->get('config_name'));
					$mail->setSubject($subject);
					$mail->setHtml($message);
					$mail->send();

					$this->session->data['success'] = $this->language->get('text_send_success');
				}
			}
		}

		$this->getList();
	}

	public function authorizationEmail() {
		$this->load->language('extension/module/technics/subscribe');

		$this->document->setTitle($this->language->get('heading_title_authorization'));

		$this->load->model('extension/theme/technics');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateAuthorizEmailForm()) {
			$this->model_extension_theme_technics->addAuthDescription($this->request->post['subscribe_authorization']);

			$this->session->data['success'] = $this->language->get('text_success_emailform');

			$this->response->redirect($this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'], true));
		}

		$data['heading_title'] = $this->language->get('heading_title_authorization');

		$data['entry_text_mail_authorization'] = $this->language->get('entry_text_mail_authorization');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['error_authorization_description'])) {
			$data['error_authorization_description'] = $this->error['error_authorization_description'];
		} else {
			$data['error_authorization_description'] = array();
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], 'SSL'),
			'separator' => false
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url, 'SSL'),
			'separator' => ' :: '
		);

		if (isset($this->request->post['subscribe_authorization'])) {
			$data['subscribe_authorization'] = $this->request->post['subscribe_authorization'];
		} else {
			$data['subscribe_authorization'] = $this->model_extension_theme_technics->getAuthDescription();
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['action'] = $this->url->link('extension/module/technics/subscribe/authorizationEmail', 'user_token=' . $this->session->data['user_token'], 'SSL');

		$data['cancel'] = $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'], 'SSL');


			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
			$data['column_left'] = $this->load->controller('common/column_left');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/subscribe_mail_authorization', $data));
	}

	public function email() {
		$data['lang'] = $this->language->get('lang');
		$data['ckeditor'] = $this->config->get('config_editor_default');
		
    //CKEditor
    if ($this->config->get('config_editor_default')) {
        $this->document->addScript('view/javascript/ckeditor/ckeditor.js');
        $this->document->addScript('view/javascript/ckeditor/ckeditor_init.js');
    } else {
        $this->document->addScript('view/javascript/summernote/summernote.js');
        $this->document->addScript('view/javascript/summernote/lang/summernote-' . $this->language->get('lang') . '.js');
		$this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');
    }
		$this->load->language('extension/module/technics/subscribe');

		$this->document->setTitle($this->language->get('heading_title_mail'));

		$this->load->model('extension/theme/technics');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateEmailForm()) {
			$this->model_extension_theme_technics->addEmailDescription($this->request->post['subscribe_descriptions']);

			$this->session->data['success'] = $this->language->get('text_success_emailform');

			$this->response->redirect($this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] , true));
		}

		$data['heading_title'] = $this->language->get('heading_title_mail');

		$data['entry_text_mail'] = $this->language->get('entry_text_mail');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['error_email_description'])) {
			$data['error_email_description'] = $this->error['error_email_description'];
		} else {
			$data['error_email_description'] = array();
		}

		$data['user_token'] = $this->session->data['user_token'];

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['page'])) {
			$url .= '&page=' . $this->request->get['page'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'] . $url, true)

		);

		if (isset($this->request->post['subscribe_descriptions'])) {
			$data['subscribe_descriptions'] = $this->request->post['subscribe_descriptions'];
		} else {
			$data['subscribe_descriptions'] = $this->model_extension_theme_technics->getEmailDescription();
		}

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['action'] = $this->url->link('extension/module/technics/subscribe/email', 'user_token=' . $this->session->data['user_token'], true);

		$data['cancel'] = $this->url->link('extension/module/technics/subscribe', 'user_token=' . $this->session->data['user_token'], true);


		$data['footer'] = $this->load->controller('common/footer');
		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');


		$this->response->setOutput($this->load->view('extension/module/technicscatalog/subscribe_mail', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/subscribe')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!preg_match('/^[^\@]+@.*\.[a-z]{2,6}$/i', $this->request->post['email'])) {
			$this->error['email'] = $this->language->get('error_email');
		}

		if (isset($this->request->get['subscribe_id'])) {
			$subscribe_id = $this->request->get['subscribe_id'];
		} else {
			$subscribe_id = 0;
		}

		$check_email = $this->model_extension_theme_technics->checkEmail($this->request->post['email'], $subscribe_id);

		if (!isset($this->error['email']) && $check_email) {
			$this->error['email'] = $this->language->get('error_check_email');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/subscribe')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	protected function validateSendMail($subscribers) {
		$this->error['warning'] = $this->language->get('error_send');

		foreach ($subscribers as $subscriber) {
			if ($subscriber['status']) {
				$this->error = array();
			}
		}
		if (!$this->user->hasPermission('modify', 'extension/module/technics/subscribe')) {
			$this->error = array();
			$this->error['warning'] = $this->language->get('error_permission');
		}
		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	protected function validateEmailForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/subscribe')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['subscribe_descriptions'] as $language_id => $description) {
			if ((utf8_strlen($description) < 0) || (utf8_strlen($description) > 16000000)) {
				$this->error['error_email_description'][$language_id] = $this->language->get('error_email_description');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

	protected function validateAuthorizEmailForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/subscribe')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['subscribe_authorization'] as $language_id => $description) {
			if ((utf8_strlen($description) < 0) || (utf8_strlen($description) > 16000000)) {
				$this->error['error_authorization_description'][$language_id] = $this->language->get('error_authorization_description');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}

}

?>
