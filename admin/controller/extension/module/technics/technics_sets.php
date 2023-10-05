<?php
class ControllerExtensionModuleTechnicsTechnicsSets extends Controller {    
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technics/technics_sets');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicssets');

		$this->getList();
	}

	public function add() {
		$this->load->language('extension/module/technics/technics_sets');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicssets');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_theme_technicssets->addSet($this->request->post);

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

			$this->response->redirect($this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/module/technics/technics_sets');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicssets');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_theme_technicssets->editSet($this->request->get['set_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('extension/module/technics/technics_sets');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicssets');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $set_id) {
				$this->model_extension_theme_technicssets->deleteSet($set_id);
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

			$this->response->redirect($this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'i.date_added';
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('extension/module/technics/technics_sets/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/module/technics/technics_sets/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['sets'] = array();

		$filter_data = array(
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin')
		);

		$results = $this->model_extension_theme_technicssets->getSets($filter_data);


		$sets_total = $this->model_extension_theme_technicssets->getTotalSets();


		foreach ($results as $result) {
			$data['sets'][] = array(
				'set_id' => $result['set_id'],
				'title'          => $result['title'],
				'date_added'      => $result['date_added'],
				'status'          => $result['status'],
				'sort_order'     => $result['sort_order'],
				'edit'           => $this->url->link('extension/module/technics/technics_sets/edit', 'user_token=' . $this->session->data['user_token'] . '&set_id=' . $result['set_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_title'] = $this->language->get('column_title');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_action'] = $this->language->get('column_action');
		$data['column_date_added'] = $this->language->get('column_date_added');
		$data['column_status'] = $this->language->get('column_status');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
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

		if (isset($this->request->post['selected'])) {
			$data['selected'] = (array)$this->request->post['selected'];
		} else {
			$data['selected'] = array();
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

		$data['sort_title'] = $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . '&sort=id.title' . $url, true);
		$data['sort_date_added'] = $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . '&sort=i.date_added' . $url, true);
		$data['sort_sort_order'] = $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . '&sort=i.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$pagination = new Pagination();
		$pagination->total = $sets_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($sets_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($sets_total - $this->config->get('config_limit_admin'))) ? $sets_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $sets_total, ceil($sets_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/technics_sets_list', $data));
	}

	protected function getForm() {
    //CKEditor
    if ($this->config->get('config_editor_default')) {
        $this->document->addScript('view/javascript/ckeditor/ckeditor.js');
        $this->document->addScript('view/javascript/ckeditor/ckeditor_init.js');
    }
    	$this->load->model('catalog/product');

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['set_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_add_product'] = $this->language->get('text_add_product');
		$data['text_name'] = $this->language->get('text_name');
		$data['text_percent'] = $this->language->get('text_percent');
		$data['text_fix'] = $this->language->get('text_fix');
		$data['text_discount'] = $this->language->get('text_discount');

		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_meta_title'] = $this->language->get('entry_meta_title');
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_city'] = $this->language->get('entry_city');
		$data['entry_mode'] = $this->language->get('entry_mode');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_layout'] = $this->language->get('entry_layout');
		$data['entry_date_added'] = $this->language->get('entry_date_added');
		$data['entry_related'] = $this->language->get('entry_related');

		$data['entry_product'] = $this->language->get('entry_product');
		$data['entry_product_info'] = $this->language->get('entry_product_info');
		$data['entry_product_qty'] = $this->language->get('entry_product_qty');

		$data['help_keyword'] = $this->language->get('help_keyword');
		$data['help_bottom'] = $this->language->get('help_bottom');
		$data['help_date_added'] = $this->language->get('text_help');
		$data['help_related'] = $this->language->get('help_related');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');
		$data['button_remove'] = $this->language->get('button_remove');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_data'] = $this->language->get('tab_data');
		$data['tab_design'] = $this->language->get('tab_design');

		$data['button_delete'] = $this->language->get('button_delete');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['title'])) {
			$data['error_title'] = $this->error['title'];
		} else {
			$data['error_title'] = array();
		}

		if (isset($this->error['description'])) {
			$data['error_description'] = $this->error['description'];
		} else {
			$data['error_description'] = array();
		}

		if (isset($this->error['meta_title'])) {
			$data['error_meta_title'] = $this->error['meta_title'];
		} else {
			$data['error_meta_title'] = array();
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['set_id'])) {
			$data['action'] = $this->url->link('extension/module/technics/technics_sets/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics/technics_sets/edit', 'user_token=' . $this->session->data['user_token'] . '&set_id=' . $this->request->get['set_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/module/technics/technics_sets', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['set_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$set_info = $this->model_extension_theme_technicssets->getSet($this->request->get['set_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['ckeditor'] = $this->config->get('config_editor_default');

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['lang'] = $this->language->get('lang');

		if (isset($this->request->post['set_description'])) {
			$data['set_description'] = $this->request->post['set_description'];
		} elseif (isset($this->request->get['set_id'])) {
			$data['set_description'] = $this->model_extension_theme_technicssets->getSetDescriptions($this->request->get['set_id']);
		} else {
			$data['set_description'] = array();
		}

		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		if (isset($this->request->post['set_store'])) {
			$data['set_store'] = $this->request->post['set_store'];
		} elseif (isset($this->request->get['set_id'])) {
			$data['set_store'] = $this->model_extension_theme_technicssets->getSetStores($this->request->get['set_id']);
		} else {
			$data['set_store'] = array(0);
		}

		// city
		$this->load->model('localisation/city');
		$data['cities'] = $this->model_localisation_city->getCities();
		if (isset($this->request->post['set_city'])) {
			$data['set_city'] = $this->request->post['set_city'];
		} elseif (isset($this->request->get['set_id'])) {
			$data['set_city'] = $this->model_extension_theme_technicssets->getSetCities($this->request->get['set_id']);
		} else {
			$data['set_city'] = array(0);
		}


		if (isset($this->request->post['mode'])) {
			$data['mode'] = $this->request->post['mode'];
		} elseif (!empty($set_info)) {
			$data['mode'] = $set_info['mode'];
		} else {
			$data['mode'] = 0;
		}

		if (isset($this->request->post['discount'])) {
			$data['discount'] = $this->request->post['discount'];
		} elseif (!empty($set_info)) {
			$data['discount'] = $set_info['discount'];
		} else {
			$data['discount'] = 0;
		}

		if (isset($this->request->post['date_added'])) {
			$data['date_added'] = $this->request->post['date_added'];
		} elseif (!empty($set_info)) {
			$data['date_added'] = $set_info['date_added'];
		} else {
			$data['date_added'] = '';
		}

		
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($set_info)) {
			$data['status'] = $set_info['status'];
		} else {
			$data['status'] = true;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($set_info)) {
			$data['sort_order'] = $set_info['sort_order'];
		} else {
			$data['sort_order'] = '';
		}



		if (isset($this->request->post['product'])) {
			$products = $this->request->post['product'];
		} elseif (isset($this->request->get['set_id'])) {
			$products = $this->model_extension_theme_technicssets->getSetProduct($this->request->get['set_id']);
		} else {
			$products = array();
		}

		$data['products'] = array();

		foreach ($products as $key => $position) {
				$rowProducts = array();
				foreach ($position as $product) {
					$product_info = $this->model_catalog_product->getProduct($product['id']);
					if ($product_info) {
						$rowProducts[] = array(
							'product_id' => $product_info['product_id'],
							'qty' 		 => $product['qty'],
							'sort_order' => $product['sort_order'],
							'name'       => $product_info['name']
						);
					}
				}

				$data['products'][$key] = $rowProducts;
				$data['products'][$key] = array(
						'products'   => $rowProducts,
						'qty' 		 => $product['qty'],
						'sort_order' => $product['sort_order'],
						'name'       => $product_info['name']
				);
		}



		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/technics_sets_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technics_sets')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['set_description'] as $language_id => $value) {
			if ((utf8_strlen($value['title']) < 3) || (utf8_strlen($value['title']) > 200)) {
				$this->error['title'][$language_id] = $this->language->get('error_title');
			}
		}

		if ($this->request->post['mode'] && $this->request->post['discount'] > 100) {
			$this->error['warning'] = $this->language->get('error_percent');
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technics_sets')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}
}
