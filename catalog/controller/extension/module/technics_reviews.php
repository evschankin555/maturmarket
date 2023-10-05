<?php
class ControllerExtensionModuleTechnicsReviews extends Controller {
	public function index($settings) {
		$this->load->language('extension/module/technics_reviews');

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_all'] = $this->language->get('text_all');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		$this->load->model('catalog/review');
		$this->load->model('tool/image');

		if (isset($this->request->get['path'])) {           

			$path = '';

			$parts = explode('_', (string)$this->request->get['path']);

			$category_id = (int)array_pop($parts);
            
		} else {
			$category_id = 0;
		}
	
		$limit = isset($settings['limit']) ? $settings['limit'] : 6;

		$order = 'DESC';
		
			$filter_data = array(
				'order'              => $order,
				'filter_category_id'        => $category_id,
				'filter_sub_category'        => false,
				'start'              => 0,
				'limit'              => $limit
			);

			if($this->config->get('theme_technics_subcategory')){
				$filter_data['filter_sub_category'] = true;
			}

		$data['reviews'] = array();

		foreach ($this->model_catalog_review->getReviews($filter_data) as $result) {

				if ($result['image']) {
					
					$image = $this->model_tool_image->resize($result['image'], $settings['width'], $settings['height']);
            
				} else {
					
					$image = $this->model_tool_image->resize('placeholder.png', $settings['width'], $settings['height']);
            
				}


			$data['reviews'][] = array(
				'name' => $result['name'],
				'thumb' => $image,
				'author' => $result['author'],
				'count_bad' => $result['count_bad'],
				'count_good' => $result['count_good'],
				'date_added' => date("d.m.Y",strtotime($result['date_added'])),
				'description' => utf8_substr(strip_tags(html_entity_decode($result['text'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
				'href'  => $this->url->link('product/product', 'product_id=' . $result['product_id']) . '#review-' . $result['review_id']
			);
		}
		if(isset($settings['layout']) && strpos($settings['layout'],'column_') !== false){
			return $this->load->view('extension/module/technics_reviews_column', $data);
		}else{
			return $this->load->view('extension/module/technics_reviews', $data);
		}

		
	}

}
