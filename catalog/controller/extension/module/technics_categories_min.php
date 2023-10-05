<?php
class ControllerExtensionModuleTechnicsCategoriesMin extends Controller {
	public function index($setting) {
		static $module = 0;		

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if(!isset($setting['title' . $this->config->get('config_language_id')])){ return; }
		$data['title'] = $setting['title' . $this->config->get('config_language_id')];
		
		$data['column'] = $setting['column'];
				
		$data['banners'] = array();

		if(isset($setting['categories_min_image'])){
			foreach ($setting['categories_min_image'] as  $result) {
				if(!isset($result['language'][$this->config->get('config_language_id')])){ continue; }
				$result = $result['language'][$this->config->get('config_language_id')];
				if (is_file(DIR_IMAGE . $result['image'])) { 
					$data['banners'][$result['sort_order']][] = array(
					'title' 		    => html_entity_decode($result['title'], ENT_QUOTES, 'UTF-8'),
					'link'  			=> $result['link'],
					'width'  		    => $result['width'],
					'height'  		    => $result['height'],
					'full'  		    => isset($result['full']),
					'image' 			=> $this->model_tool_image->resize($result['image'], $result['width'], $result['height'])
				);
				}
			}			
		}
		
		ksort($data['banners']);
		$data['module'] = $module++;

		return $this->load->view('extension/module/technics_categories_min', $data);
		
	}
}
?>
