<?php
class ControllerExtensionModuleTechnicsBrands extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technics_brands');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addStyle('view/stylesheet/technics/technics.css?v' . $this->config->get('theme_technics_version'));
				
		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('technics_brands', $this->request->post);
			} else {
				$this->model_setting_module->editModule($this->request->get['module_id'], $this->request->post);
			}

			$this->session->data['success'] = $this->language->get('text_success');

			$this->response->redirect($this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_edit'] = $this->language->get('text_edit');
		$data['text_enabled'] = $this->language->get('text_enabled');
		$data['text_disabled'] = $this->language->get('text_disabled');

		$data['entry_name'] = $this->language->get('entry_name');
		$data['entry_width'] = $this->language->get('entry_width');
		$data['entry_height'] = $this->language->get('entry_height');
		$data['entry_status'] = $this->language->get('entry_status');

		$data['button_save'] = $this->language->get('button_save');
		$data['button_cancel'] = $this->language->get('button_cancel');

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['name'])) {
			$data['error_name'] = $this->error['name'];
		} else {
			$data['error_name'] = '';
		}

		if (isset($this->error['width'])) {
			$data['error_width'] = $this->error['width'];
		} else {
			$data['error_width'] = '';
		}

		if (isset($this->error['height'])) {
			$data['error_height'] = $this->error['height'];
		} else {
			$data['error_height'] = '';
		}

		if (isset($this->error['slidestoshow'])) {
			$data['error_slidestoshow'] = $this->error['slidestoshow'];
		} else {
			$data['error_slidestoshow'] = '';
		}

		if (isset($this->error['brands_image'])) {
			$data['error_brands_image'] = $this->error['brands_image'];
		} else {
			$data['error_brands_image'] = array();
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

		if (!isset($this->request->get['module_id'])) {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/technics_brands', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/technics_brands', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/technics_brands', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics_brands', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}
		
		$this->load->model('localisation/language');

		$languages = $this->model_localisation_language->getLanguages(); 

		$this->load->model('tool/image');

        if (isset($this->request->post['brands_image'])) {
            $brands_images = $this->request->post['brands_image'];
        } elseif (!empty($module_info) && isset($module_info['brands_image'])) {
            $brands_images = $module_info['brands_image'];
        } else {
            $brands_images = array();
        }
		
		$data['brands_images'] = $brands_images;


		foreach ($brands_images as $key => $brands_image_l) {
			foreach($brands_image_l['language'] as $language_id => $brands_image){
				if (isset($brands_image['image']) && is_file(DIR_IMAGE . $brands_image['image'])) {
					$image = $brands_image['image'];
					$thumb = $brands_image['image'];
				} else {
					$image = '';
					$thumb = 'no_image.png';
				}			
				$data['brands_images'][$key]['language'][$language_id]["thumb"] = $this->model_tool_image->resize($thumb, 100, 100);
				$data['brands_images'][$key]['language'][$language_id]["image"] = $image;				
			}

		}

		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		
		$data['languages'] = $languages;
		
		if (isset($this->request->post['name'])) {
			$data['name'] = $this->request->post['name'];
		} elseif (!empty($module_info)) {
			$data['name'] = $module_info['name'];
		} else {
			$data['name'] = '';
		}

		if (isset($this->request->post['width'])) {
			$data['width'] = $this->request->post['width'];
		} elseif (!empty($module_info)) {
			$data['width'] = $module_info['width'];
		} else {
			$data['width'] = '150';
		}

		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($module_info)) {
			$data['height'] = $module_info['height'];
		} else {
			$data['height'] = '40';
		}

		if (isset($this->request->post['slidestoshow'])) {
			$data['slidestoshow'] = $this->request->post['slidestoshow'];
		} elseif (!empty($module_info)&&isset($module_info['slidestoshow'])) {
			$data['slidestoshow'] = $module_info['slidestoshow'];
		} else {
			$data['slidestoshow'] = '6';
		}

		if (isset($this->request->post['autoplay'])) {
			$data['autoplay'] = $this->request->post['autoplay'];
		} elseif (!empty($module_info)&&isset($module_info['autoplay'])) {
			$data['autoplay'] = $module_info['autoplay'];
		} else {
			$data['autoplay'] = '5';
		}

		if (isset($this->request->post['speed'])) {
			$data['speed'] = $this->request->post['speed'];
		} elseif (!empty($module_info)&&isset($module_info['speed'])) {
			$data['speed'] = $module_info['speed'];
		} else {
			$data['speed'] = '5';
		}
		
		foreach ($languages as $language) {
			if (isset($this->request->post['title'])) {
				$data['titles']['title' . $language['language_id']] = $this->request->post['title' . $language['language_id']];
			} elseif (!empty($module_info)) {
				$data['titles']['title' . $language['language_id']] = $module_info['title' . $language['language_id']];
			} else {
				$data['titles']['title' . $language['language_id']] = '';
			} 
		}
		
		if (isset($this->request->post['status'])) {
			$data['status'] = $this->request->post['status'];
		} elseif (!empty($module_info)) {
			$data['status'] = $module_info['status'];
		} else {
			$data['status'] = '1';
		}

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/module/technics_brands', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics_brands')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 64)) {
			$this->error['name'] = $this->language->get('error_name');
		}

		if (!$this->request->post['width']) {
			$this->error['width'] = $this->language->get('error_width');
		}

		if (!$this->request->post['height']) {
			$this->error['height'] = $this->language->get('error_height');
		}
		
		if (!$this->request->post['slidestoshow']) {
			$this->error['slidestoshow'] = $this->language->get('error_slidestoshow');
		}
		
		return !$this->error;
	}
}
