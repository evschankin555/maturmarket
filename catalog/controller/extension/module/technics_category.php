<?php
class ControllerExtensionModuleTechnicsCategory extends Controller {
	public function index($settings) {
		$this->load->language('extension/module/technics_category');

		if (isset($this->request->get['path'])) {
			$parts = explode('_', (string)$this->request->get['path']);
		} else {
			$parts = array();
		}

		if (isset($parts[0])) {
			$data['category_id'] = $parts[0];
		} else {
			$data['category_id'] = 0;
		}

		if (isset($parts[1])) {
			$data['child_id'] = $parts[1];
		} else {
			$data['child_id'] = 0;
		}

		foreach ($parts as $part) {
			$data['path_ids'][] = $part;
		}

		$this->load->model('catalog/category');

		$this->load->model('catalog/product');

		$this->load->model('tool/image');

		$data['categories'] = array();

		$categories = $this->model_catalog_category->getCategories(0);

		foreach ($categories as $category) {
			$children_data = array();

			// if ($category['category_id'] == $data['category_id']) {
			if ($category['category_id']) { // Раскроем все категории
				$children = $this->model_catalog_category->getCategories($category['category_id']);
/*
				foreach($children as $child) {
					$filter_data = array('filter_category_id' => $child['category_id'], 'filter_sub_category' => true);

					$children_data[] = array(
						'category_id' => $child['category_id'],
						'name' => $child['name'],
						'totalitems'  => $this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '0',
						'href' => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'])
					);
				}
*/

		        foreach ($children as $child) {  // Level 2       
		          $children2_data = array();
		          $children2 = $this->model_catalog_category->getCategories($child['category_id']);
		          foreach ($children2 as $child2) {    // Level 3 
		//Level 4 start
		            $children3_data = array();
		            $children3 = $this->model_catalog_category->getCategories($child2['category_id']);               
		              foreach ($children3 as $child3) { 
		                $filter_data = array(
		                  'filter_category_id'  => $child3['category_id'],
		                  'filter_sub_category' => true
		                );
						$active = 0;

							if (isset($data['path_ids'][3]) && $child3['category_id'] == $data['path_ids'][2]) {
								$active = 1;
							}	

		                $children3_data[$child3['category_id']] = array(
		                  'category_id' => $child3['category_id'],
		                  'name'  => $child3['name'],
		                  'active'      => $active,
		                  'totalitems'  => $this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '0',
		                  'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child2['category_id'] . '_' . $child3['category_id'])
		                );
		              }
		//Level 4 end
		            $filter_data = array(
		              'filter_category_id'  => $child2['category_id'],
		              'filter_sub_category' => true
		            );
		            // Level 3
					$active = 0;

						if (isset($data['path_ids'][2]) && $child2['category_id'] == $data['path_ids'][2]) {
							$active = 1;
						}	


		            $children2_data[$child2['category_id']] = array(
		              'category_id' => $child2['category_id'],
		              'name'  => $child2['name'],
		              'active'      => $active,
		              'totalitems'  => $this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '0',
		              'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id'] . '_' . $child2['category_id']),
		              'children' => $children3_data
		            );
		          }         
		                  
		          $filter_data = array(
		            'filter_category_id'  => $child['category_id'],
		            'filter_sub_category' => true
		          );
		          // Level 2
					$active = 0;

						if (isset($data['path_ids'][1]) && $child['category_id'] == $data['path_ids'][1]) {
							$active = 1;
						}	
        
		          $children_data[$child['category_id']] = array(// <--technics change this
		          	'category_id' => $child['category_id'],
		            'name'  => $child['name'],
		            'active'      => $active,
		            'totalitems'  => $this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '0',
		            'href'  => $this->url->link('product/category', 'path=' . $category['category_id'] . '_' . $child['category_id']),
		            'children' => $children2_data // <--technics add this
		          );

		        }






			}

			$filter_data = array(
				'filter_category_id'  => $category['category_id'],
				'filter_sub_category' => true
			);
			$active = 0;
			if (!empty($children_data) && $category['category_id'] == $data['path_ids'][0]) {
				$active = 1;
			}
			$data['categories'][] = array(
				'category_id' => $category['category_id'],
				'name'        => $category['name'],
				'active'      => $active,
				'totalitems'  => $this->config->get('config_product_count') ? ' (' . $this->model_catalog_product->getTotalProducts($filter_data) . ')' : '0',
				'children'    => $children_data,
				'thumb'       => $this->config->get('theme_technics_image_category_resize') ? $this->model_tool_image->technics_resize(($category['image'] == '' ? 'no_image.png' : $category['image']), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'),  $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')) : $this->model_tool_image->resize(($category['image'] == '' ? 'no_image.png' : $category['image']), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_width'),  $this->config->get('theme_' . $this->config->get('config_theme') . '_image_category_height')),
				'href'        => $this->url->link('product/category', 'path=' . $category['category_id'])
			);
		}

		$data['showcount'] = $this->config->get('config_product_count');

		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		$data['title'] = '';
	
		if(isset($settings['title' . $this->config->get('config_language_id')])){ 
			$data['title'] = $settings['title' . $this->config->get('config_language_id')];
		}

		$data['view'] = $settings['view'];
		
			if(isset($settings['layout']) && strpos($settings['layout'],'column_') !== false){
				return $this->load->view('extension/module/technics_category_column', $data);
			}else{
				return $this->load->view('extension/module/technics_category', $data);
			}

					
	}
}