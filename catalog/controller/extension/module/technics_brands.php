<?php
class ControllerExtensionModuleTechnicsBrands extends Controller {
	public function index($setting) {
		static $module = 0;		

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if(!isset($setting['title' . $this->config->get('config_language_id')])){ return; }
		$data['title'] = $setting['title' . $this->config->get('config_language_id')];
		
		if (isset($setting['slidestoshow'])) {
			$data['slidestoshow'] = $setting['slidestoshow'];
		} else {
			$data['slidestoshow'] = '6';
		}
		if (isset($setting['autoplay'])) {
			$data['autoplay'] = $setting['autoplay'];
		} else {
			$data['autoplay'] = '5';
		}
		if (isset($setting['speed'])) {
			$data['speed'] = $setting['speed'];
		} else {
			$data['speed'] = '5';
		}

		$data['width'] = $setting['width']/10;
		$data['height'] = $setting['height']/10;
		
		$data['banners'] = array();

		if(isset($setting['brands_image'])){
			foreach ($setting['brands_image'] as  $result) {
				if(!isset($result['language'][$this->config->get('config_language_id')])){ continue; }
				$result = $result['language'][$this->config->get('config_language_id')];
				if (is_file(DIR_IMAGE . $result['image'])) { 
					$data['banners'][$result['sort_order']][] = array(
					'title' 			=> $result['title'],
					'link'  			=> $result['link'],
					'image' 			=> $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])
				);
				}
			}			
		}
		
		ksort($data['banners']);
		$data['module'] = $module++;

		return $this->load->view('extension/module/technics_brands', $data);
		
	}
}
?>
