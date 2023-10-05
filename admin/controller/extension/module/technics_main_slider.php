<?php
class ControllerExtensionModuleTechnicsMainSlider extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/module/technics_main_slider');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->document->addStyle('view/stylesheet/technics/technics.css?v' . $this->config->get('theme_technics_version'));

		$this->load->model('setting/module');

		if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {//var_dump($this->request->post['slider_image']);die;
			if (!isset($this->request->get['module_id'])) {
				$this->model_setting_module->addModule('technics_main_slider', $this->request->post);
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

		if (isset($this->error['slider_image'])) {
			$data['error_slider_image'] = $this->error['slider_image'];
		} else {
			$data['error_slider_image'] = array();
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
				'href' => $this->url->link('extension/module/technics_main_slider', 'user_token=' . $this->session->data['user_token'], true)
			);
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/module/technics_main_slider', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true)
			);
		}

		if (!isset($this->request->get['module_id'])) {
			$data['action'] = $this->url->link('extension/module/technics_main_slider', 'user_token=' . $this->session->data['user_token'], true);
		} else {
			$data['action'] = $this->url->link('extension/module/technics_main_slider', 'user_token=' . $this->session->data['user_token'] . '&module_id=' . $this->request->get['module_id'], true);
		}

		$data['cancel'] = $this->url->link('marketplace/extension', 'user_token=' . $this->session->data['user_token'] . '&type=module', true);

		if (isset($this->request->get['module_id']) && ($this->request->server['REQUEST_METHOD'] != 'POST')) {
			$module_info = $this->model_setting_module->getModule($this->request->get['module_id']);
		}
		
		$this->load->model('localisation/language');

		$data['languages'] = $this->model_localisation_language->getLanguages();

		$this->load->model('tool/image');

        if (isset($this->request->post['slider_image'])) {
            $slider_images = $this->request->post['slider_image'];
        } elseif (!empty($module_info)&&isset($module_info['slider_image'])) {
            $slider_images = $module_info['slider_image'];
        } else {
            $slider_images = array();
        }
		
		$data['slider_images'] = $slider_images;



		foreach ($slider_images as $key => $slider_image_l) {
			foreach($slider_image_l['language'] as $language_id => $slider_image){//var_dump($slider_image);die;
				if (isset($slider_image['image']) && is_file(DIR_IMAGE . $slider_image['image'])) {
					$image = $slider_image['image'];
					$thumb = $slider_image['image'];
				} else {
					$image = '';
					$thumb = 'no_image.png';
				}
//				var_dump($slider_image_l);die;
//				$data['slider_images'][$key][$language_id]["thumb"] = '';
//				$data['slider_images'][$key][$language_id]["image"] = '';				
				$data['slider_images'][$key]['language'][$language_id]["thumb"] = $this->model_tool_image->resize($thumb, 100, 100);
				$data['slider_images'][$key]['language'][$language_id]["image"] = $image;				
			}

		}
//var_dump($data['slider_images']);die;
		$data['placeholder'] = $this->model_tool_image->resize('no_image.png', 100, 100);
		
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
			$data['width'] = '1920';
		}

		if (isset($this->request->post['height'])) {
			$data['height'] = $this->request->post['height'];
		} elseif (!empty($module_info)) {
			$data['height'] = $module_info['height'];
		} else {
			$data['height'] = '570'; 
		}

		if (isset($this->request->post['autoplay'])) {
			$data['autoplay'] = $this->request->post['autoplay'];
		} elseif (!empty($module_info)) {
			$data['autoplay'] = $module_info['autoplay'];
		} else {
			$data['autoplay'] = '5';
		}

		if (isset($this->request->post['speed'])) {
			$data['speed'] = $this->request->post['speed'];
		} elseif (!empty($module_info)) {
			$data['speed'] = $module_info['speed'];
		} else {
			$data['speed'] = '5';
		}

		if (isset($this->request->post['slide_width'])) {
			$data['slide_width'] = $this->request->post['slide_width'];
		} elseif (!empty($module_info) && isset($module_info['slide_width'])) {
			$data['slide_width'] = $module_info['slide_width'];
		} else {
			$data['slide_width'] = 'full';
		}

		if (isset($this->request->post['slide_fade'])) {
			$data['slide_fade'] = $this->request->post['slide_fade'];
		} elseif (!empty($module_info) && isset($module_info['slide_fade'])) {
			$data['slide_fade'] = $module_info['slide_fade'];
		} else {
			$data['slide_fade'] = 0;
		}

		if (isset($this->request->post['resize'])) {
			$data['resize'] = $this->request->post['resize'];
		} elseif (!empty($module_info) && isset($module_info['resize'])) {
			$data['resize'] = $module_info['resize'];
		} else {
			$data['resize'] = 0;
		}
		
		if (isset($this->request->post['img_link'])) {
			$data['img_link'] = $this->request->post['img_link'];
		} elseif (!empty($module_info) && isset($module_info['img_link'])) {
			$data['img_link'] = $module_info['img_link'];
		} else {
			$data['img_link'] = 0;
		}
		
		if (isset($this->request->post['resizable'])) {
			$data['resizable'] = $this->request->post['resizable'];
		} elseif (!empty($module_info) && isset($module_info['resizable'])) {
			$data['resizable'] = $module_info['resizable'];
		} else {
			$data['resizable'] = 0;
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

		$this->response->setOutput($this->load->view('extension/module/technics_main_slider', $data));
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/module/technics_main_slider')) {
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
		
		return !$this->error;
	}
}
