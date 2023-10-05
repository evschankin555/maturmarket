<?php
class ControllerExtensionModuleTechnicsPromo extends Controller {
	public function index($setting) {
		static $module = 0;		

		$this->load->model('design/banner');
		$this->load->model('tool/image');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		$data['banners'] = array();
		$images = array();

		if (isset($setting['promo_column'])) {
			foreach ($setting['promo_column'] as $key => $promo_column) {
				$images = array();
				if(isset($promo_column['promo_image'])){
					foreach ($promo_column['promo_image'] as  $result) {
						if(!isset($result['language'][$this->config->get('config_language_id')])){ continue; }
						$result = $result['language'][$this->config->get('config_language_id')];
						
						if ($result['width']) {
							$width = $result['width'];
						}else{
							$width =  $this->config->get('theme_technics_image_category_width');
						}
						
						if ($result['height']) {
							$height = $result['height'];
						}else{
							$height =  $this->config->get('theme_technics_image_category_height');
						}
						
						if ($result['text_position'] == 1) {
							$position = 'promo__item-desc--top-left';
						}elseif ($result['text_position'] == 2) {
							$position = 'promo__item-desc--top-right';
						}elseif ($result['text_position'] == 3) {
							$position = 'promo__item-desc--bottom-left';
						}else {
							$position = 'promo__item-desc--bottom-right';
						}
						
						if ($result['text_color'] == 2) {
							$color = 'promo__item-desc--white';
						}else {
							$color = 'promo__item-desc--black';
						}
						
						if (is_file(DIR_IMAGE . $result['image'])) { 
							$images[(int)$result['sort_order']] = array(
							'title' 			=> $result['title'],
							'link'  			=> $result['link'],
							'text_big' 		    => html_entity_decode($result['text_big'], ENT_QUOTES, 'UTF-8'),
							'text_small'  		=> html_entity_decode($result['text_small'], ENT_QUOTES, 'UTF-8'),
							'width'  		    => $width,
							'position'      	=> $position,
							'color' 	        => $color,
							'image' 			=> $this->model_tool_image->resize($result['image'], $width, $height)
						);
						}
					}			
				}
				$data['banners'][$key]['images'] = $images;
				$data['banners'][$key]['column_width'] = $promo_column["language"][$this->config->get('config_language_id')]["column_width"];
			}
		}
		
		ksort($data['banners']);
		$data['module'] = $module++;

		return $this->load->view('extension/module/technics_promo', $data);
		
	}
}
?>
