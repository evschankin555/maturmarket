<?php
class ControllerExtensionModuleTechnicsMainSlider extends Controller {
	public function index($setting) {
		static $module = 0;		

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		
		$data['autoplay'] = $setting['autoplay'];
		$data['speed'] = $setting['speed'];
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if (!empty($setting['resizable']) && isset($setting['resizable'])) {
			$data['scale'] = $setting['height']/$setting['width']*100;
		} else {
			$data['scale'] = '';
		}
		
		if (isset($setting['img_link'])) {
			$data['img_link'] = $setting['img_link'];
		} else {
			$data['img_link'] = '';
		}
		
		$data['slide_width'] = $setting['slide_width'];
		$data['slide_fade'] = $setting['slide_fade'];
		$data['banners'] = array();

		if(isset($setting['slider_image'])){
			foreach ($setting['slider_image'] as  $result) {
				if(!isset($result['language'][$this->config->get('config_language_id')])){ continue; }
				$result = $result['language'][$this->config->get('config_language_id')];
				if (is_file(DIR_IMAGE . $result['image'])) { 
					$data['banners'][$result['sort_order']][] = array(
						'title' => $result['title'],
						'link'  => $result['link'],
						'slider_text'  => html_entity_decode($result['slider_text'], ENT_QUOTES, 'UTF-8'),
						'btn_text'  => html_entity_decode($result['btn_text'], ENT_QUOTES, 'UTF-8'),
						'text_color'  => $result['text_color'],
						'image' => $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])
					);
				}
			}			
		}


		ksort($data['banners']);

		$data['module'] = $module++;

		return $this->load->view('extension/module/technics_main_slider', $data);
		
	}
}
?>
