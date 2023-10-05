<?php
class ControllerExtensionModuleTechnicsTechnicsTab extends Controller {    
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technics/technics_tab');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		$this->getList();
	}

	public function add() {
		$this->load->language('extension/module/technics/technics_tab');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_theme_technics->saveLsCustTabs($this->request->post,false);

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

			$this->response->redirect($this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/module/technics/technics_tab');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) { 
			$this->model_extension_theme_technics->saveLsCustTabs($this->request->post,$this->request->get['cust_tab_id']);
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

			$this->response->redirect($this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('extension/theme/technics');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technics');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $cust_tab_id) {
				$this->model_extension_theme_technics->deleteTab($cust_tab_id);
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

			$this->response->redirect($this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url, true));
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

		if (isset($this->request->get['filter_instanse_id'])) {
			$filter_instanse_id = $this->request->get['filter_instanse_id'];
		} else {
			$filter_instanse_id = 0;
		}

		if (isset($this->request->get['filter_instanse'])) {
			$filter_instanse = $this->request->get['filter_instanse'];
		} else {
			$filter_instanse = '';
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

		if (isset($this->request->get['filter_instanse_id'])) {
			$url .= '&filter_instanse_id=' . $this->request->get['filter_instanse_id'];
		}

		if (isset($this->request->get['filter_instanse'])) {
			$url .= '&filter_instanse=' . $this->request->get['filter_instanse'];
		}

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('extension/module/technics/technics_tab/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/module/technics/technics_tab/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['filter_instanse_id'] = $filter_instanse_id;

		$data['filter_instanse'] = $filter_instanse;
		$data['tabs'] = array();

		$filter_data = array(
			'filter_instanse_id'  => $filter_instanse_id,
			'sort'  => $sort,
			'order' => $order,
			'start' => ($page - 1) * $this->config->get('config_limit_admin'),
			'limit' => $this->config->get('config_limit_admin'),
			'store_id' => $this->config->get('store_id')
		);

		$results = $this->model_extension_theme_technics->getLsTabs($filter_data);


		$tab_total = $this->model_extension_theme_technics->getTotalLsTab();


		foreach ($results as $result) {
			$data['tabs'][] = array(
				'cust_tab_id' => $result['cust_tab_id'],
				'title'          => $result['title'],
				'status'          => $result['status'],
				'instanses'      => $result['instanses'],
				'mode'      	=> $result['mode'],
				'sort_order'     => $result['sort_order'],
				'edit'           => $this->url->link('extension/module/technics/technics_tab/edit', 'user_token=' . $this->session->data['user_token'] . '&cust_tab_id=' . $result['cust_tab_id'] . $url, true)
			);
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_instanse'] = $this->language->get('text_instanse');


		$data['column_title'] = $this->language->get('column_title');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_action'] = $this->language->get('column_action');
		$data['column_instanses'] = $this->language->get('column_instanses');
		$data['column_status'] = $this->language->get('column_status');
		$data['column_mode'] = $this->language->get('column_mode');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_filter'] = $this->language->get('button_filter');
		$data['button_clear'] = $this->language->get('button_clear');

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

		$data['sort_title'] = $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . '&sort=id.title' . $url, true);
		$data['sort_date_added'] = $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . '&sort=i.date_added' . $url, true);
		$data['sort_sort_order'] = $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . '&sort=i.sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		$data['user_token'] = $this->session->data['user_token'];

		$pagination = new Pagination();
		$pagination->total = $tab_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();
		$data['reset'] = $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] , true);

		$data['results'] = sprintf($this->language->get('text_pagination'), ($tab_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($tab_total - $this->config->get('config_limit_admin'))) ? $tab_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $tab_total, ceil($tab_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/technics_tab_list', $data));
	}

	protected function getForm() {

    //CKEditor
    if ($this->config->get('config_editor_default')) {
        $this->document->addScript('view/javascript/ckeditor/ckeditor.js');
        $this->document->addScript('view/javascript/ckeditor/ckeditor_init.js');
    } else {
        $this->document->addScript('view/javascript/summernote/summernote.js');
		$this->document->addScript('view/javascript/summernote/summernote-image-attributes.js');
        $this->document->addScript('view/javascript/summernote/lang/summernote-' . $this->language->get('lang') . '.js');
        $this->document->addScript('view/javascript/summernote/opencart.js');
        $this->document->addStyle('view/javascript/summernote/summernote.css');
    }		

    	$this->load->model('catalog/product');

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['cust_tab_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$tab_info = array();

		$data['text_default'] = $this->language->get('text_default');
		$data['text_sort_order'] = $this->language->get('text_sort_order');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');
		$data['text_tabs_popup'] = $this->language->get('text_tabs_popup');
		$data['text_tabs_video'] = $this->language->get('text_tabs_video');
		$data['text_name'] = $this->language->get('text_name');
		$data['text_video'] = $this->language->get('text_video');
		$data['text_video_help'] = $this->language->get('text_video_help');
		$data['text_description'] = $this->language->get('text_description');
		$data['text_categories'] = $this->language->get('text_categories');
		$data['text_tabs_links'] = $this->language->get('text_tabs_links');
		$data['text_products'] = $this->language->get('text_products');
		$data['text_tabs_shablon'] = $this->language->get('text_tabs_shablon');
		$data['text_tabs_tab'] = $this->language->get('text_tabs_tab');
		$data['text_tabs_to_products'] = $this->language->get('text_tabs_to_products');
		$data['text_tabs_tab_to_categories'] = $this->language->get('text_tabs_tab_to_categories');
		$data['help_product_tabs_main'] = $this->language->get('help_product_tabs_main');
		$data['help_product_tabs_cat_select'] = $this->language->get('help_product_tabs_cat_select');


		$data['entry_title'] = $this->language->get('entry_title');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_mode'] = $this->language->get('entry_mode');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_layout'] = $this->language->get('entry_layout');
		$data['entry_date_added'] = $this->language->get('entry_date_added');
		$data['entry_related'] = $this->language->get('entry_related');

		$data['entry_product'] = $this->language->get('entry_product');
		$data['entry_product_qty'] = $this->language->get('entry_product_qty');

		$data['help_product_tabs_select'] = $this->language->get('help_product_tabs_select');
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

		if (isset($this->error['instanses'])) {
			$data['error_instanses'] = $this->error['instanses'];
		} else {
			$data['error_instanses'] = '';
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
			'href' => $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['cust_tab_id'])) {
			$data['action'] = $this->url->link('extension/module/technics/technics_tab/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics/technics_tab/edit', 'user_token=' . $this->session->data['user_token'] . '&cust_tab_id=' . $this->request->get['cust_tab_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/module/technics/technics_tab', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['cust_tab_id'])) {
			$tab_info = $this->model_extension_theme_technics->getLsTab($this->request->get['cust_tab_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['ckeditor'] = $this->config->get('config_editor_default');

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['lang'] = $this->language->get('lang');

		if (isset($this->request->post['description'])) {
			$data['description'] = $this->request->post['description'];
		} elseif (isset($this->request->get['cust_tab_id'])) {
			$data['description'] = $tab_info['description'];
		} else {
			$data['description'] = array();
		}

		if (isset($this->request->post['title'])) {
			$data['title'] = $this->request->post['title'];
		} elseif (isset($this->request->get['cust_tab_id'])) {
			$data['title'] = $tab_info['title'];
		} else {
			$data['title'] = array();
		}

		$this->load->model('setting/store');

		$data['stores'] = $this->model_setting_store->getStores();

		if (isset($this->request->post['tab_store'])) {
			$data['tab_store'] = $this->request->post['tab_store'];
		} elseif (isset($this->request->get['cust_tab_id'])) {
			$data['tab_store'] = $tab_info['stories'];
		} else {
			$data['tab_store'] = array(0);
		}


		if (isset($this->request->post['mode'])) {
			$data['mode'] = $this->request->post['mode'];
		} elseif (!empty($tab_info)) {
			$data['mode'] = $tab_info['mode'];
		} else {
			$data['mode'] = 'products';
		}


		if (isset($this->request->post['instanses'])) {
			$instanses = $this->request->post['instanses'];
		} elseif (isset($this->request->get['cust_tab_id'])) {
			$instanses = $tab_info['instanses'];
		} else {
			$instanses = array();
		}

		$data['instanses'] = array();
		if (!empty($instanses)) {
			$this->load->model('catalog/category');
			$this->load->model('catalog/product');

			foreach ($instanses[$data['mode']] as $instanse_id) { 
				if ($data['mode'] == 'products' ) {
					$instanse_info = $this->model_catalog_product->getProduct($instanse_id); 
				}else{
					$instanse_info = $this->model_catalog_category->getCategory($instanse_id);
				}

				if ($instanse_info) {
					$data['instanses'][] = array(
						'id' => $instanse_id,
						'name'      => $instanse_info['name']
					);
				}
			}
		}




		if (isset($this->request->post['view'])) {
			$data['view'] = $this->request->post['view'];
		} elseif (!empty($tab_info)) {
			$data['view'] = $tab_info['view'];
		} else {
			$data['view'] = 'tab';
		}

		if (isset($this->request->post['date_added'])) {
			$data['date_added'] = $this->request->post['date_added'];
		} elseif (!empty($tab_info)) {
			$data['date_added'] = $tab_info['date_added'];
		} else {
			$data['date_added'] = '';
		}

		
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($tab_info)) {
			$data['status'] = $tab_info['status'];
		} else {
			$data['status'] = 1;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($tab_info)) {
			$data['sort_order'] = $tab_info['sort_order'];
		} else {
			$data['sort_order'] = '0';
		}




		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/technics_tab_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technics_tab')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['title'] as $language_id => $value) {
			if ((utf8_strlen($value) < 3) || (utf8_strlen($value) > 200)) {
				$this->error['title'][$language_id] = $this->language->get('error_title');
			}
		}

		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}

		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technics_tab')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_instanse'])) {
			$this->load->model('extension/theme/technics');

			if (isset($this->request->get['filter_instanse'])) {
				$filter_instanse = $this->request->get['filter_instanse'];
			} else {
				$filter_instanse = '';
			}

			if (isset($this->request->get['limit'])) {
				$limit = $this->request->get['limit'];
			} else {
				$limit = 5;
			}

			$filter_data = array(
				'filter_instanse'  => $filter_instanse,
				'start'        => 0,
				'limit'        => $limit
			);

			$results = $this->model_extension_theme_technics->getLsTabInstanses($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'id' => $result['id'],
					'name'       => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8')),
					'mode' => $result['mode']
				);
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
