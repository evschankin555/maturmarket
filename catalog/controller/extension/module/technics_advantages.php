<?php
class ControllerExtensionModuleTechnicsAdvantages extends Controller {
	public function index($setting) {
		static $module = 0;		

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if(!isset($setting['title' . $this->config->get('config_language_id')])){ return; }
		$data['title'] = $setting['title' . $this->config->get('config_language_id')];
		$data['icon_type'] = $setting['icon_type'];
		
		if (!empty($setting['grid']) && isset($setting['grid'])) {
			$data['grid'] = $setting['grid'] == 'auto' ? 'col-auto' : 'col-sm-' . $setting['grid'];
		} else {
			$data['grid'] = 'col-sm-4';
		}
		
		$data['advantages'] = array();

		if(isset($setting['advantages_image'])){
			foreach ($setting['advantages_image'] as  $result) {
				if(!isset($result['language'][$this->config->get('config_language_id')])){ continue; }
				$result = $result['language'][$this->config->get('config_language_id')];
				if ( ($setting['icon_type'] == 'img' && is_file(DIR_IMAGE . $result['image'])) ||  ($setting['icon_type'] == 'html' && $result['html'])   ) { 
					$data['advantages'][$result['sort_order']][] = array(
					'title' 			=> $result['title'],
					'html' 				=> html_entity_decode($result['html'], ENT_QUOTES, 'UTF-8'),
					'link'  			=> $result['link'],
					'image' 			=> $this->model_tool_image->resize($result['image'], $setting['width'], $setting['height'])
				);
				}
			}			
		}
		
		ksort($data['advantages']);
		$data['module'] = $module++;

		if(isset($settings['layout']) && strpos($settings['layout'],'column_') !== false){
			return $this->load->view('extension/module/technics_advantages_column', $data);
		}else{
			return $this->load->view('extension/module/technics_advantages', $data);
		}
	}
}
?>
