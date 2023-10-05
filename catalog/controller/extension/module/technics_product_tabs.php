<?php
		if (! function_exists('array_column')) {  // To compatibility with PHP < 5.5 
		    function array_column(array $input, $columnKey, $indexKey = null) {
		        $array = array();
		        foreach ($input as $value) {
		            if ( !array_key_exists($columnKey, $value)) {
		                trigger_error("Key \"$columnKey\" does not exist in array");
		                return false;
		            }
		            if (is_null($indexKey)) {
		                $array[] = $value[$columnKey];
		            }
		            else {
		                if ( !array_key_exists($indexKey, $value)) {
		                    trigger_error("Key \"$indexKey\" does not exist in array");
		                    return false;
		                }
		                if ( ! is_scalar($value[$indexKey])) {
		                    trigger_error("Key \"$indexKey\" does not contain scalar value");
		                    return false;
		                }
		                $array[$value[$indexKey]] = $value[$columnKey];
		            }
		        }
		        return $array;
		    }
		}

class ControllerExtensionModuleTechnicsProductTabs extends Controller {
	public function index($setting) {
		$this->load->language('extension/module/featured');
		$this->load->language('extension/theme/technics');

		$data['heading_title'] = $this->language->get('heading_title');

		$data['text_tax'] = $this->language->get('text_tax');

		$data['button_cart'] = $this->language->get('button_cart');
		$data['button_wishlist'] = $this->language->get('button_wishlist');
		$data['button_compare'] = $this->language->get('button_compare');
		$data['text_reward'] = $this->language->get('text_reward');

		$data['language_id'] = $this->config->get('config_language_id');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');
		$data['category_time'] = $this->config->get('theme_technics_category_time');
		$data['time_text_1'] = $this->language->get('text_time_text_1');
		$data['time_text_2'] = $this->language->get('text_time_text_2');
		
		if (isset($setting['view'])) {
			$data['view'] = $setting['view'];
		} else {
			$data['view'] = '';
		}

		if (isset($setting['tp_limit'])) {
			$data['tp_limit'] = $setting['tp_limit'];
		} else {
			$data['tp_limit'] = 3;
		}

		if (isset($setting['images_status'])) {
			$images_status = $setting['images_status'];
		} else {
			$images_status = 1;
		}
		
		if(isset($setting['title' . $this->config->get('config_language_id')])){ 
			$data['title'] = $setting['title' . $this->config->get('config_language_id')];
		}	
		
		// labels
			$labelsInfo = array();
			if($this->config->get('theme_technics_label')){
				$labelsInfo = $this->config->get('theme_technics_label');
			}
			$data['labelsinfo'] = $labelsInfo ;
		// labels

		
		foreach($setting['theme_technics_product_tabs'] as $tab => $product_tab){			
				if (isset($dataTabs[$product_tab['sort']])) {
					$dataTabs[] = $product_tab; 
				}else{
					$dataTabs[$product_tab['sort']] = $product_tab;
				}
		}	
		ksort($dataTabs);
		
		$data['product_tabs'] = $dataTabs;
		
		
		foreach($setting['theme_technics_product_tabs'] as $tab => $product_tab){
			$product_tab['images_status'] = $images_status;
			$tabProducts = $this->getProducts($product_tab);
			if(!empty($tabProducts)){
				if (isset($data['products'][$product_tab['sort']])) {
					$data['products'][] = $tabProducts; 
				}else{
					$data['products'][$product_tab['sort']] = $tabProducts;
				}
			}
		}
		

		if (isset($data['products']) && $data['products']) {
			if(isset($setting['layout']) && strpos($setting['layout'],'column_') !== false){
				return $this->load->view('extension/module/technics_product_tabs_column', $data);
			}else{
				return $this->load->view('extension/module/technics_product_tabs', $data);
			}
		}
	}

	public function sortProducts($productsInfo,$sort,$order) { 
		if (!$order || $order == 'DESC') {
			$order = 3;
		}else{
			$order = 4;
		}
		if ($sort == 'random') {
			shuffle($productsInfo);
		}elseif($sort == 'p.viewed' || $sort == 'p.date_added' || $sort == 'p.date_added'){ 
				$sort = explode('.', $sort); 
				$setsort  = array_column($productsInfo, $sort[1]);
				array_multisort($setsort, $order, $productsInfo);
		}
		return $productsInfo;
	}


	public function getProducts($setting) {
			$this->load->model('catalog/product');
			$this->load->model('tool/image');
      		$this->load->model('extension/module/technics');

			$data['products'] = array();

			if (!$setting['limit']) {
				$limit = 4;
			}else{
				$limit = $setting['limit'];
			}
			$results = array();
			$order = 'ASC';
			if($setting['target'] == 0 || $setting['target'] == 2 ){ //Вывести все продукты
				$page = 1;
				$filter_category_ids = false;
				if($setting['sortorder'] == 'discount' || $setting['sortorder'] == 'p.viewed' || $setting['sortorder'] == 'p.date_added'){
					$order = 'DESC';
				}
				if($setting['target'] == '2'){					
					if(isset($setting['categories'])){
						$filter_category_ids = $setting['categories'];						
					}else{
						$filter_category_ids = array('-1');
					}						
				}				
				$filter_data = array(
					'filter_category_ids' => $filter_category_ids,
					'sort'               => $setting['sortorder'],
					'order'              => $order,
					'start'              => ($page - 1) * $limit,
					'limit'              => $limit
				); 
				if($setting['sortorder'] == 'bestseller'){ 
					$results = $this->model_catalog_product->getBestSellerProductsLS($filter_data);
				}elseif($setting['sortorder'] == 'discount'){ 
					$results = $this->model_catalog_product->getProductSpecials($filter_data);
				}else{ 
					if (!$filter_category_ids) { // Make cache for caregoriesless requests
					    $results = $this->cache->get('prodtabs.' . $setting['sortorder'] . '.' . $order . '.'  .  (int)($page - 1) * $limit  . '.'  .  $limit . '.'  . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->session->data['city_id']);
					    if (!$results) {
						    $results = $this->model_catalog_product->getProducts($filter_data);
						    $this->cache->set('prodtabs.' . $setting['sortorder'] . '.' . $order . '.'  .  (int)($page - 1) * $limit  . '.'  .  $limit . '.'  . (int)$this->config->get('config_language_id') . '.' . (int)$this->config->get('config_store_id') . '.' . $this->session->data['city_id'], $results);
					    }
					}else{
						$results = $this->model_catalog_product->getProducts($filter_data);
					}
				}
				shuffle($results);
				 
			}elseif($setting['target'] == '1' && isset($setting['products'])){ //Вывести только указанные продукты.
					$resultsTemp = [];
					foreach($setting['products'] as $product_id){
						$productInCity = $this->model_catalog_product->getProduct($product_id, true); // Возьмем продукт только по текущему городу
						if ($productInCity){
							$resultsTemp[] = $productInCity;
						}
					}
					$results = $this->sortProducts($resultsTemp,$setting['sortorder'],$order);
					$results = array_slice($results, 0, $limit);
			}elseif($setting['target'] == '3'){ //Вывести просмотренные.
				$cookies = array();
				if(isset($_COOKIE["899ProductsVieded"])){
					$i = 0;
					$cookies = explode(',',$_COOKIE['899ProductsVieded']);

					krsort($cookies);
					
					foreach($cookies as $product_id){
						$prodInfo = $this->model_catalog_product->getProduct($product_id, true); // Возьмем продукт только по текущему городу
						if(!$prodInfo || $i >= $limit){ continue;}
						$resultsTemp[] = $prodInfo;
						$i++;
					}
					$results = $this->sortProducts($resultsTemp,$setting['sortorder'],$order);
				}
			}elseif ($setting['target'] == '4'){ //Вывести С этим товаром покупают.
				$products_id = array();
				$results = array();
				if (isset($this->request->get['product_id'])) {
					$products_id[] = (int)$this->request->get['product_id'];
					$results = $this->model_catalog_product->getAlsoOrderedProducts($products_id,$setting['sortorder']);
				} 

				if(empty($results)) { 
					$products = $this->cart->getProducts(); 
					foreach ($products as  $product) {
						$products_id[] =  $product['product_id'];
					}
					$results = $this->model_catalog_product->getAlsoOrderedProducts($products_id,$setting['sortorder']);
				}
				
			}


		// labels
			$labelsInfo = array();
			if($this->config->get('theme_technics_label')){
				$labelsInfo = $this->config->get('theme_technics_label');  
			}
			$data['labelsinfo'] = $labelsInfo ;
			$data['language_id'] = $this->config->get('config_language_id');
			$newest = array();
			$sales = false;
			if(isset($labelsInfo['new']['period']) && $labelsInfo['new']['status']){
				$newest = $this->model_catalog_product->getNewestProducts($labelsInfo['new']['period']);	
			}
			if(isset($labelsInfo['sale']['status']) && $labelsInfo['sale']['status']){
				$sales = true;				
			}
		    if (isset($labelsInfo['hit']) && $labelsInfo['hit']['status']) {
		       $hits = $this->model_extension_module_technics->getHitProducts($labelsInfo['hit']['period'],$labelsInfo['hit']['qty']);
		    }
		// labels



			foreach ($results as $result) {

				if (!$result['product_id']) { continue; }

				if ($result['image']) {
					$image = $this->config->get('theme_technics_image_product_resize') ? $this->model_tool_image->technics_resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')) : $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));// technics

				} else {
					$image = $this->config->get('theme_technics_image_product_resize') ? $this->model_tool_image->technics_resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')) : $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));// technics

				}

				if ($this->customer->isLogged() || !$this->config->get('config_customer_price')) {
					$price = $this->currency->format($this->tax->calculate($result['price'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$price = false;
				}

				if ((float)$result['special']) {
					$special = $this->currency->format($this->tax->calculate($result['special'], $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
				} else {
					$special = false;
				}

				if ($this->config->get('config_tax')) {
					$tax = $this->currency->format((float)$result['special'] ? $result['special'] : $result['price'], $this->session->data['currency']);
				} else {
					$tax = false;
				}

				if ($this->config->get('config_review_status')) {
					$rating = (int)$result['rating'];
				} else {
					$rating = false;
				}
				
				$extraImages = array();	
				if ($setting['images_status']) {		
					$images = $this->model_catalog_product->getProductImages($result['product_id']);
					foreach($images as $imageX){
						$extraImages[] = $this->config->get('theme_technics_image_product_resize') ? $this->model_tool_image->technics_resize($imageX['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')) : $this->model_tool_image->resize($imageX['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
					}
				}	
				
				if (in_array($result['product_id'], $newest)) {
					$isNewest = true;
				} else {
					$isNewest = false;
				}			
								
				if ($result['quantity'] <= 0) {
					$stock = $result['stock_status'];
				} elseif ($this->config->get('config_stock_display')) {
					$stock = $result['quantity'];
				} else {
					$stock = $this->language->get('text_instock');
				}	
				
				if ($result['quantity'] <= 0 && !$this->config->get('config_stock_checkout')) {
					$buy_btn = $result['stock_status'];
				} else {
					$buy_btn = '';
				}
				
				if ($this->config->get('theme_technics_manufacturer') == 1) {
					$manufacturer = $result['model'];
				} elseif ($this->config->get('theme_technics_manufacturer') == 2) {
					$manufacturer = $result['manufacturer'];
				} else {
					$manufacturer = false;
				}

				$discount = '';
				if($sales && $special){
					$special_date_end = false;
					$action = $this->model_catalog_product->getProductActions($result['product_id']);
					if ($action['date_end'] != '0000-00-00') {
						$special_date_end = $action['date_end'];
					}		

					if($labelsInfo['sale']['extra'] == 1){
						$discount = round((($result['price'] - $result['special'])/$result['price'])*100);
						$discount = $discount. ' %';

					}
					if($labelsInfo['sale']['extra'] == 2){
						$discount = $this->currency->format($this->tax->calculate(($result['price'] - $result['special']), $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
					}					
				} else {
					$special_date_end = false;
				}
				$catch = false;
				$nocatch = false;
				if (isset($labelsInfo['catch']) && $labelsInfo['catch']['status'] && $result['quantity'] <= $labelsInfo['catch']['qty']) {
					if($result['quantity'] > 0){
						$catch = $labelsInfo['catch']['name'][$this->config->get('config_language_id')];
					}else{
						$catch = $labelsInfo['catch']['name1'][$this->config->get('config_language_id')];
						$nocatch = true;
					}
				}

				$popular = false;
				if (isset($labelsInfo['popular']) && $labelsInfo['popular']['status'] && $result['viewed'] >= $labelsInfo['popular']['views']) {
					$popular = $labelsInfo['popular']['name'][$this->config->get('config_language_id')];
				}

				$hit = false;
				if (isset($labelsInfo['hit']) && $labelsInfo['hit']['status']) {
					if (isset($hits[$result['product_id']])) {
						$hit = $labelsInfo['hit']['name'][$this->config->get('config_language_id')];
					}
				}

				if($setting['target'] == 0 && $setting['sortorder'] == 'discount' && !$special){ continue;} //Фильтр - добавлять только акционные товары
					
				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					
					'manufacturer'  => $manufacturer,// technics
					'brand_name'	=> $result['brand_name'],
					'isProductHasPriceForMasters'	=> $result['has_price_for_master'], 
					'quantity'        => $result['quantity'],// technics
					'stock'        => $stock,// technics
					'images'       => $extraImages,// technics	
					'isnewest'       => $isNewest,// technics
					'sales'       => $sales,// technics
					'discount'       => $discount,// technics
					'catch'       => $catch,// technics
					'nocatch'       => $nocatch,// technics
					'popular'	  => $popular,// technics
					'hit'	 	  => $hit,// technics
					'buy_btn'	  => $buy_btn,// technics
					'reward'      => $result['reward'],// technics
					'special_date_end'      => $special_date_end,// technics
					
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => ($result['minimum'] > 0) ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', '&product_id=' . $result['product_id'] )
				);
			}
			
		return $data['products'];
	}
}
