<?php
class ControllerExtensionModuleTechnicsHomeText extends Controller {
	public function index($setting) {
		
		$this->load->model('tool/image');
		$this->load->language('extension/theme/technics');
		$data['img'] = false;
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if (isset($setting['module_description'][$this->config->get('config_language_id')])) {
			$data['heading_title'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['title'], ENT_QUOTES, 'UTF-8');
			$data['html'] = html_entity_decode($setting['module_description'][$this->config->get('config_language_id')]['description'], ENT_QUOTES, 'UTF-8');
		
	
			if (isset($setting['img']) && $setting['img']) {
				if (isset($setting['resize'])) {
					$data['img'] = $this->model_tool_image->technics_resize($setting['img'], $setting['width'], $setting['height']);
				} else {
					$data['img'] = $this->model_tool_image->resize($setting['img'], $setting['width'], $setting['height']);
				}
			}

			return $this->load->view('extension/module/technics_home_text', $data);
		}
	}
}