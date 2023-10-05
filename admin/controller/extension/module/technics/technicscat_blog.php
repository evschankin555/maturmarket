<?php
class ControllerExtensionModuleTechnicsTechnicscatblog extends Controller {
	private $error = array();
	private $technicscatblog_id = 0;
	private $path = array();

	public function index() {
		$this->load->language('extension/module/technics/technicscat_blog');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicscatblog');

		$this->getList();
	}

	public function add() {
		$this->load->language('extension/module/technics/technicscat_blog');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicscatblog');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_theme_technicscatblog->addCategoryBlog($this->request->post);

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

			$this->response->redirect($this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function edit() {
		$this->load->language('extension/module/technics/technicscat_blog');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicscatblog');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
			$this->model_extension_theme_technicscatblog->editCategoryBlog($this->request->get['technicscatblog_id'], $this->request->post);

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

			$this->response->redirect($this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getForm();
	}

	public function delete() {
		$this->load->language('extension/module/technics/technicscat_blog');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicscatblog');

		if (isset($this->request->post['selected']) && $this->validateDelete()) {
			foreach ($this->request->post['selected'] as $technicscatblog_id) {
				$this->model_extension_theme_technicscatblog->deleteCategoryBlog($technicscatblog_id);
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

			$this->response->redirect($this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	public function repair() {
		$this->load->language('extension/module/technics/technicscat_blog');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('extension/theme/technicscatblog');

		if ($this->validateRepair()) {
			$this->model_extension_theme_technicscatblog->repairCategoryBlogs();

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

			$this->response->redirect($this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true));
		}

		$this->getList();
	}

	protected function getList() {
		if (isset($this->request->get['sort'])) {
			$sort = $this->request->get['sort'];
		} else {
			$sort = 'name';
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
			'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
		);

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		$data['add'] = $this->url->link('extension/module/technics/technicscat_blog/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['delete'] = $this->url->link('extension/module/technics/technicscat_blog/delete', 'user_token=' . $this->session->data['user_token'] . $url, true);
		$data['repair'] = $this->url->link('extension/module/technics/technicscat_blog/repair', 'user_token=' . $this->session->data['user_token'] . $url, true);

		$data['categories'] = array();

		if (isset($this->request->get['path'])) {
			if ($this->request->get['path'] != '') {
				$this->path = explode('_', $this->request->get['path']);
				$this->technicscatblog_id = end($this->path);
				$this->session->data['path'] = $this->request->get['path'];
			} else {
				unset($this->session->data['path']);
			}
		} elseif (isset($this->session->data['path'])) {
			$this->path = explode('_', $this->session->data['path']);
			$this->technicscatblog_id = end($this->path);
		}

		$data['categories'] = $this->getCategories(0);

		$technicscatblog_total = count($data['categories']);

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_list'] = $this->language->get('text_list');
		$data['text_no_results'] = $this->language->get('text_no_results');
		$data['text_confirm'] = $this->language->get('text_confirm');

		$data['column_name'] = $this->language->get('column_name');
		$data['column_sort_order'] = $this->language->get('column_sort_order');
		$data['column_action'] = $this->language->get('column_action');

		$data['button_add'] = $this->language->get('button_add');
		$data['button_edit'] = $this->language->get('button_edit');
		$data['button_delete'] = $this->language->get('button_delete');
		$data['button_rebuild'] = $this->language->get('button_rebuild');

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

		$data['sort_name'] = $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . '&sort=name' . $url, true);
		$data['sort_sort_order'] = $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . '&sort=sort_order' . $url, true);

		$url = '';

		if (isset($this->request->get['sort'])) {
			$url .= '&sort=' . $this->request->get['sort'];
		}

		if (isset($this->request->get['order'])) {
			$url .= '&order=' . $this->request->get['order'];
		}

		if (isset($this->request->get['path'])) {
			$url .= '&path=' . $this->request->get['path'];
		}

		$pagination = new Pagination();
		$pagination->total = $technicscatblog_total;
		$pagination->page = $page;
		$pagination->limit = $this->config->get('config_limit_admin');
		$pagination->url = $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url . '&page={page}', true);

		$data['pagination'] = $pagination->render();

		$data['results'] = sprintf($this->language->get('text_pagination'), ($technicscatblog_total) ? (($page - 1) * $this->config->get('config_limit_admin')) + 1 : 0, ((($page - 1) * $this->config->get('config_limit_admin')) > ($technicscatblog_total - $this->config->get('config_limit_admin'))) ? $technicscatblog_total : ((($page - 1) * $this->config->get('config_limit_admin')) + $this->config->get('config_limit_admin')), $technicscatblog_total, ceil($technicscatblog_total / $this->config->get('config_limit_admin')));

		$data['sort'] = $sort;
		$data['order'] = $order;

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/technicscatblog_list', $data));
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

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_form'] = !isset($this->request->get['technicscatblog_id']) ? $this->language->get('text_add') : $this->language->get('text_edit');
		$data['text_none'] = $this->language->get('text_none');
		$data['text_default'] = $this->language->get('text_default');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_description'] = $this->language->get('entry_description');
		$data['entry_meta_title'] = $this->language->get('entry_meta_title');
		$data['entry_meta_h1'] = $this->language->get('entry_meta_h1');
		$data['entry_meta_description'] = $this->language->get('entry_meta_description');
		$data['entry_meta_keyword'] = $this->language->get('entry_meta_keyword');
		$data['entry_keyword'] = $this->language->get('entry_keyword');
		$data['entry_parent'] = $this->language->get('entry_parent');
		$data['entry_filter'] = $this->language->get('entry_filter');
		$data['entry_store'] = $this->language->get('entry_store');
		$data['entry_image'] = $this->language->get('entry_image');
		$data['entry_top'] = $this->language->get('entry_top');
		$data['entry_column'] = $this->language->get('entry_column');
		$data['entry_sort_order'] = $this->language->get('entry_sort_order');
		$data['entry_status'] = $this->language->get('entry_status');
		$data['entry_layout'] = $this->language->get('entry_layout');

		$data['help_filter'] = $this->language->get('help_filter');
		$data['help_keyword'] = $this->language->get('help_keyword');
		$data['help_top'] = $this->language->get('help_top');
		$data['help_column'] = $this->language->get('help_column');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		$data['tab_general'] = $this->language->get('tab_general');
		$data['tab_data'] = $this->language->get('tab_data');
		$data['tab_design'] = $this->language->get('tab_design');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = array();
		}

		if (isset($this->error['keyword'])) {
			$data['error_keyword'] = $this->error['keyword'];
		} else {
			$data['error_keyword'] = '';
		}

		if (isset($this->error['parent'])) {
			$data['error_parent'] = $this->error['parent'];
		} else {
			$data['error_parent'] = '';
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
			'href' => $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true)
		);

		if (!isset($this->request->get['technicscatblog_id'])) {
			$data['action'] = $this->url->link('extension/module/technics/technicscat_blog/add', 'user_token=' . $this->session->data['user_token'] . $url, true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics/technicscat_blog/edit', 'user_token=' . $this->session->data['user_token'] . '&technicscatblog_id=' . $this->request->get['technicscatblog_id'] . $url, true);
		}

		$data['cancel'] = $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . $url, true);

		if (isset($this->request->get['technicscatblog_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$technicscatblog_info = $this->model_extension_theme_technicscatblog->getCategoryBlog($this->request->get['technicscatblog_id']);
		}

		$data['user_token'] = $this->session->data['user_token'];
		$data['ckeditor'] = $this->config->get('config_editor_default');

		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$data['lang'] = $this->language->get('lang');

		if (isset($this->request->post['technicscatblog_description'])) {
			$data['technicscatblog_description'] = $this->request->post['technicscatblog_description'];
		} elseif (isset($this->request->get['technicscatblog_id'])) {
			$data['technicscatblog_description'] = $this->model_extension_theme_technicscatblog->getCategoryBlogDescriptions($this->request->get['technicscatblog_id']);
		} else {
			$data['technicscatblog_description'] = array();
		}

		// Categories
		$categoriesblog = $this->model_extension_theme_technicscatblog->getAllCategoriesBlog();

		$data['categoriesblog'] = $this->getAllCategoriesBlog($categoriesblog);

		if (isset($technicscatblog_info) && isset($data['categoriesblog'][$technicscatblog_info['category_id']])) { //var_dump($data['categoriesblog']);die;
			unset($data['categoriesblog'][$technicscatblog_info['category_id']]);
		}

		if (isset($this->request->post['parent_id'])) {
			$data['parent_id'] = $this->request->post['parent_id'];
		} elseif (!empty($technicscatblog_info)) {
			$data['parent_id'] = $technicscatblog_info['parent_id'];
		} else {
			$data['parent_id'] = 0;
		}

		$this->load->model('catalog/filter');

		if (isset($this->request->post['technicscatblog_filter'])) {
			$filters = $this->request->post['technicscatblog_filter'];
		} elseif (isset($this->request->get['technicscatblog_id'])) {
			$filters = $this->model_extension_theme_technicscatblog->getCategoryBlogFilters($this->request->get['technicscatblog_id']);
		} else {
			$filters = array();
		}

		$data['technicscatblog_filters'] = array();

		foreach ($filters as $filter_id) {
			$filter_info = $this->model_catalog_filter->getFilter($filter_id);

			if ($filter_info) {
				$data['technicscatblog_filters'][] = array(
					'filter_id' => $filter_info['filter_id'],
					'name'      => $filter_info['group'] . ' &gt; ' . $filter_info['name']
				);
			}
		}

		$this->load->model('setting/store');

		$data['stores'] = array();
		
		$data['stores'][] = array(
			'store_id' => 0,
			'name'     => $this->language->get('text_default')
		);
		
		$stores = $this->model_setting_store->getStores();

		foreach ($stores as $store) {
			$data['stores'][] = array(
				'store_id' => $store['store_id'],
				'name'     => $store['name']
			);
		}

		if (isset($this->request->post['technicscatblog_store'])) {
			$data['technicscatblog_store'] = $this->request->post['technicscatblog_store'];
		} elseif (isset($this->request->get['technicscatblog_id'])) {
			$data['technicscatblog_store'] = $this->model_extension_theme_technicscatblog->getCategoryBlogStores($this->request->get['technicscatblog_id']);
		} else {
			$data['technicscatblog_store'] = array(0);
		}


		if (isset($this->request->post['category_seo_url'])) {
			$data['category_seo_url'] = $this->request->post['category_seo_url'];
		} elseif (isset($this->request->get['technicscatblog_id'])) {
			$data['category_seo_url'] = $this->model_extension_theme_technicscatblog->getCategorySeoUrls($this->request->get['technicscatblog_id']); 
		} else {
			$data['category_seo_url'] = array();
		}

		if (isset($this->request->post['image'])) {
			$data['image'] = $this->request->post['image'];
		} elseif (!empty($technicscatblog_info)) {
			$data['image'] = $technicscatblog_info['image'];
		} else {
			$data['image'] = '';
		}

		$this->load->model('tool/image');

		if (isset($this->request->post['image']) && is_file(DIR_IMAGE . $this->request->post['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($this->request->post['image'], 100, 100);
		} elseif (!empty($technicscatblog_info) && is_file(DIR_IMAGE . $technicscatblog_info['image'])) {
			$data['thumb'] = $this->model_tool_image->resize($technicscatblog_info['image'], 100, 100);
		} else {
			$data['thumb'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);

		if (isset($this->request->post['top'])) {
			$data['top'] = $this->request->post['top'];
		} elseif (!empty($technicscatblog_info)) {
			$data['top'] = $technicscatblog_info['top'];
		} else {
			$data['top'] = 0;
		}

		if (isset($this->request->post['column'])) {
			$data['column'] = $this->request->post['column'];
		} elseif (!empty($technicscatblog_info)) {
			$data['column'] = $technicscatblog_info['column'];
		} else {
			$data['column'] = 1;
		}

		if (isset($this->request->post['sort_order'])) {
			$data['sort_order'] = $this->request->post['sort_order'];
		} elseif (!empty($technicscatblog_info)) {
			$data['sort_order'] = $technicscatblog_info['sort_order'];
		} else {
			$data['sort_order'] = 0;
		}

		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($technicscatblog_info)) {
			$data['status'] = $technicscatblog_info['status'];
		} else {
			$data['status'] = true;
		}

		if (isset($this->request->post['category_layout'])) {
			$data['category_layout'] = $this->request->post['category_layout'];
		} elseif (isset($this->request->get['technicscatblog_id'])) {
			$data['category_layout'] = $this->model_extension_theme_technicscatblog->getCategoryBlogLayouts($this->request->get['technicscatblog_id']);
		} else {
			$data['category_layout'] = array();
		}

		$this->load->model('design/layout');

		$data['layouts'] = $this->model_design_layout->getLayouts();

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technicscatalog/technicscatblog_form', $data));
	}

	protected function validateForm() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technicscat_blog')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		foreach ($this->request->post['technicscatblog_description'] as $language_id => $value) {
			if ((utf8_strlen($value['name']) < 2) || (utf8_strlen($value['name']) > 255)) {
				$this->error['name'][$language_id] = $this->language->get('error_name');
			}
		}
//var_dump($this->request->post['parent_id']);die;
		if (isset($this->request->get['technicscatblog_id']) && $this->request->post['parent_id']) {
			$results = $this->model_extension_theme_technicscatblog->getCategoryBlogPath($this->request->post['parent_id']);
			
			foreach ($results as $result) {
				if ($result['path_id'] == $this->request->get['technicscatblog_id']) {
					$this->error['parent'] = $this->language->get('error_parent');
					
					break;
				}
			}
		}

		if ($this->request->post['category_seo_url']) {
			$this->load->model('design/seo_url');
			
			foreach ($this->request->post['category_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						if (count(array_keys($language, $keyword)) > 1) {
							$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_unique');
						}						
						
						$seo_urls = $this->model_design_seo_url->getSeoUrlsByKeyword($keyword);
						
						foreach ($seo_urls as $seo_url) {
							if (($seo_url['store_id'] == $store_id) && (!isset($this->request->get['technicscatblog_id']) || ($seo_url['query'] != 'technicscatblog_id=' . $this->request->get['technicscatblog_id']))) {
								$this->error['keyword'][$store_id][$language_id] = $this->language->get('error_keyword');
							}
						}
					}
				}
			}
		}
		
		if ($this->error && !isset($this->error['warning'])) {
			$this->error['warning'] = $this->language->get('error_warning');
		}
		
		return !$this->error;
	}

	protected function validateDelete() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technicscat_blog')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	protected function validateRepair() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics/technicscat_blog')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		return !$this->error;
	}

	private function getCategories($parent_id, $parent_path = '', $indent = '') {
		$technicscatblog_id = array_shift($this->path);

		$output = array();

		static $href_technicscatblog = null;
		static $href_action = null;

		if ($href_technicscatblog === null) {
			$href_technicscatblog = $this->url->link('extension/module/technics/technicscat_blog', 'user_token=' . $this->session->data['user_token'] . '&path=', 'SSL');
			$href_action = $this->url->link('extension/module/technics/technicscat_blog/update', 'user_token=' . $this->session->data['user_token'] . '&technicscatblog_id=', 'SSL');
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

		$results = $this->model_extension_theme_technicscatblog->getCategoriesBlogByParentId($parent_id);
//var_dump($results);die;
		foreach ($results as $result) {
			$path = $parent_path . $result['category_id'];

			$href = ($result['children']) ? $href_technicscatblog . $path : '';

			$name = $result['name'];

			if ($technicscatblog_id == $result['category_id']) {
				$name = '<b>' . $name . '</b>';

				$data['breadcrumbs'][] = array(
					'text'      => $result['name'],
					'href'      => $href,
					'separator' => ' :: '
				);

				$href = '';
			}

			$selected = isset($this->request->post['selected']) && in_array($result['category_id'], $this->request->post['selected']);

			$action = array();

			$action[] = array(
				'text' => $this->language->get('text_edit'),
				'href' => $href_action . $result['category_id']
			);

			$output[$result['category_id']] = array(
				'technicscatblog_id' => $result['category_id'],
				'name'        => $name,
				'sort_order'  => $result['sort_order'],
				'selected'    => $selected,
				'action'      => $action,
				'edit'        => $this->url->link('extension/module/technics/technicscat_blog/edit', 'user_token=' . $this->session->data['user_token'] . '&technicscatblog_id=' . $result['category_id'] . $url, 'SSL'),
				'delete'      => $this->url->link('extension/module/technics/technicscat_blog/delete', 'user_token=' . $this->session->data['user_token'] . '&technicscatblog_id=' . $result['category_id'] . $url, 'SSL'),
				'href'        => $href,
				'indent'      => $indent
			);

			if ($technicscatblog_id == $result['category_id']) {
				$output += $this->getCategories($result['category_id'], $path . '_', $indent . str_repeat('&nbsp;', 8));
			}
		}

		return $output;
	}

	private function getAllCategoriesBlog($categoriesBlog, $parent_id = 0, $parent_name = '') {
		$output = array();

		if (array_key_exists($parent_id, $categoriesBlog)) {
			if ($parent_name != '') {
				//$parent_name .= $this->language->get('text_separator');
				$parent_name .= ' &gt; ';
			}

			foreach ($categoriesBlog[$parent_id] as $technicscatblog) {
				$output[$technicscatblog['category_id']] = array(
					'technicscatblog_id' => $technicscatblog['category_id'],
					'name'        => $parent_name . $technicscatblog['name']
				);

				$output += $this->getAllCategoriesBlog($categoriesBlog, $technicscatblog['category_id'], $parent_name . $technicscatblog['name']);
			}
		}

    uasort($output, array($this, 'sortByName'));
    
		return $output;
	}

  function sortByName($a, $b) {
    return strcmp($a['name'], $b['name']);
  }

	public function autocomplete() {
		$json = array();

		if (isset($this->request->get['filter_name'])) {
			$this->load->model('extension/theme/technicscatblog');

			$filter_data = array(
				'filter_name' => $this->request->get['filter_name'],
				'sort'        => 'name',
				'order'       => 'ASC',
				'start'       => 0,
				'limit'       => 20
			);

			$results = $this->model_extension_theme_technicscatblog->getCategoriesBlog($filter_data);

			foreach ($results as $result) {
				$json[] = array(
					'technicscatblog_id' => $result['category_id'],
					'name'        => strip_tags(html_entity_decode($result['name'], ENT_QUOTES, 'UTF-8'))
				);
			}
		}

		$sort_order = array();

		foreach ($json as $key => $value) {
			$sort_order[$key] = $value['name'];
		}

		array_multisort($sort_order, SORT_ASC, $json);

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
