<?php
class ControllerExtensionModuleTechnicsNews extends Controller {
	public function index($settings) {
		$this->load->language('extension/module/technics_news');

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_all_news'] = $this->language->get('text_all_news');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		$this->load->model('extension/module/technicsnews');
		
		$data['news_href'] = $this->url->link('extension/module/technics_news/getnewslist');
		$limit = isset($settings['limit']) ? $settings['limit'] : 3;
		$page = 1;
		$order = 'DESC';
		
			$filter_data = array(
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);


		$data['newss'] = array();

		foreach ($this->model_extension_module_technicsnews->getNewss($filter_data) as $result) {
			$data['newss'][] = array(
				'title' => $result['title'],
				'date_added' => $this->rus_date("j F, Y ", strtotime($result['date_added'])),
				'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
				'href'  => $this->url->link('extension/module/technics_news/getnews', 'news_id=' . $result['news_id'])
			);
		}
		if(isset($settings['layout']) && strpos($settings['layout'],'column_') !== false){
			return $this->load->view('extension/module/technics_news_column', $data);
		}else{
			return $this->load->view('extension/module/technics_news_list', $data);
		}

		
	}
	public function getnews() {
		$this->load->language('extension/module/technics_news');

		$this->load->model('extension/module/technicsnews');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');

		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics_news/getnewslist')
		);

		$data['text_related'] = $this->language->get('text_related');
		
		if (isset($this->request->get['news_id'])) {
			$news_id = (int)$this->request->get['news_id'];
		} else {
			$news_id = 0;
		}

		$news_info = $this->model_extension_module_technicsnews->getNews($news_id);
	
		// technics
		$data['schema'] = $this->config->get('theme_technics_schema');
		// technics end
		
		if ($news_info) {

			if ($news_info['meta_title']) {
				$this->document->setTitle($news_info['meta_title']);
			} else {
				$this->document->setTitle($news_info['title']);
			}

			$this->document->setDescription($news_info['meta_description']);
			$this->document->setKeywords($news_info['meta_keyword']);

			$data['breadcrumbs'][] = array(
				'text' => $news_info['title'],
				'href' => $this->url->link('extension/module/technics_news/getnews', 'news_id=' .  $news_id)
			);

			if ($news_info['meta_h1']) {
				$data['heading_title'] = $news_info['meta_h1'];
			} else {
				$data['heading_title'] = $news_info['title'];
			}

			$data['products'] = array();
			
	// technics
		$this->load->language('extension/theme/technics');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');
		// labels
			$this->load->model('extension/module/technics');
			$labelsInfo = array();
			if($this->config->get('theme_technics_label')){
				$labelsInfo = $this->config->get('theme_technics_label');
			}
			$data['language_id'] = $this->config->get('config_language_id');
			$newest = array();
			$sales = false;
			if(isset($labelsInfo['new']['period']) && $labelsInfo['new']['status']){
				$newest = $this->model_catalog_product->getNewestProducts($labelsInfo['new']['period']);			
			}
			if(isset($labelsInfo['sale']['status']) && $labelsInfo['sale']['status']){
				$sales = true;				
			}	
			$data['labelsinfo'] = $labelsInfo;		
		      if (isset($labelsInfo['hit']) && $labelsInfo['hit']['status']) {
		        $hits = $this->model_extension_module_technics->getHitProducts($labelsInfo['hit']['period'],$labelsInfo['hit']['qty']);
		      }	
		// labels	
		// technics end			
		
			$results = $this->model_extension_module_technicsnews->getProductRelated($this->request->get['news_id']);

			foreach ($results as $result_id) {
				$result = $this->model_catalog_product->getProduct($result_id);

				if ($this->config->get('theme_technics_image_related_resize')) {
					if ($result['image']) {
						$image = $this->model_tool_image->technics_resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
					} else {
						$image = $this->model_tool_image->technics_resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
					}
				} else {
					if ($result['image']) {
						$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_related_height'));
					}
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
				
				// technics
				
					$extraImages = array();				
					$images = $this->model_catalog_product->getProductImages($result['product_id']);
					foreach($images as $imageX){
						$extraImages[] = $this->config->get('theme_technics_image_product_resize') ? $this->model_tool_image->technics_resize($imageX['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')) : $this->model_tool_image->resize($imageX['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
					}
				
					if (in_array($result['product_id'], $newest)) {
						$isNewest = true;
					} else {
						$isNewest = false;
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
						if($labelsInfo['sale']['extra'] == 1){
							$discount = round((($result['price'] - $result['special'])/$result['price'])*100);
							$discount = $discount. ' %';
						}
						if($labelsInfo['sale']['extra'] == 2){
							$discount = $this->currency->format($this->tax->calculate(($result['price'] - $result['special']), $result['tax_class_id'], $this->config->get('config_tax')), $this->session->data['currency']);
						}					
					}							
					// technics end
					
				$data['products'][] = array(
					'product_id'  => $result['product_id'],
					'thumb'       => $image,
					'name'        => $result['name'],
					'isnewest'    => $isNewest,// technics
					'sales'       => $sales,// technics
					'discount'    => $discount,// technics
					'catch'       => $catch,// technics
					'popular'     => $popular,// technics
					'hit'    	  => $hit,// technics
					'nocatch'     => $nocatch,// technics
					'manufacturer'=> $manufacturer,// technics
					'reward'      => $result['reward'],// technics
					'buy_btn'	  => $buy_btn,// technics
					'images'      => $extraImages,// technics	
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			$data['button_continue'] = $this->language->get('button_continue');

			$data['description'] = html_entity_decode($news_info['description'], ENT_QUOTES, 'UTF-8');
			$data['date_added'] = $this->rus_date("j F, Y ", strtotime($news_info['date_added']));
			
			$data['continue'] = $this->url->link('common/home');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('extension/module/technics_news', $data));
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('information/information', 'news_id=' . $news_id)
			);

			$this->document->setTitle($this->language->get('text_error'));

			$data['heading_title'] = $this->language->get('text_error');

			$data['text_error'] = $this->language->get('text_error');

			$data['button_continue'] = $this->language->get('button_continue');

			$data['continue'] = $this->url->link('common/home');

			$this->response->addHeader($this->request->server['SERVER_PROTOCOL'] . ' 404 Not Found');

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');
	
		$this->response->setOutput($this->load->view('error/404', $data));

		}		
	}
	public function getnewslist() { 
		$this->load->language('extension/module/technics_news');

		if ($this->config->get('theme_technics_news_meta_title' . $this->config->get('config_language_id'))) {
			$this->document->setTitle($this->config->get('theme_technics_news_meta_title' . $this->config->get('config_language_id')));
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}
		
		if ($this->config->get('theme_technics_news_meta_description' . $this->config->get('config_language_id'))) {
			$this->document->setDescription($this->config->get('theme_technics_news_meta_description' . $this->config->get('config_language_id')));
		}
		
		if ($this->config->get('theme_technics_news_meta_keyword' . $this->config->get('config_language_id'))) {
			$this->document->setKeywords($this->config->get('theme_technics_news_meta_keyword' . $this->config->get('config_language_id')));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$this->load->model('extension/module/technicsnews');
		
		if(!$this->model_extension_module_technicsnews->isModuleSet()){
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technics_news/getnewslist')
		);
		$data['schema'] = $this->config->get('theme_technics_schema');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		
		$limit = $this->config->get('theme_technics_news_limit');
		
		$order = 'DESC';
		
			$filter_data = array(
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

		$data['newss'] = array();
		foreach ($this->model_extension_module_technicsnews->getNewss($filter_data) as $result) {
			$data['newss'][] = array(
				'title' => $result['title'],
				'date_added' => $this->rus_date("j F, Y ", strtotime($result['date_added'])),
				'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 150) . '...',
				'href'  => $this->url->link('extension/module/technics_news/getnews', 'news_id=' . $result['news_id'])
			);
		}	
		
		$totalNews = $this->model_extension_module_technicsnews->getNewssTotal();
			$pagination = new Pagination();
			$pagination->total = $totalNews;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('extension/module/technics_news/getnewslist&page={page}');

			$data['pagination'] = $pagination->render();
			
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

//		return $this->load->view('extension/module/technics_news_list_main', $data);
		$this->response->setOutput($this->load->view('extension/module/technics_news_list_main', $data));		
	}
	
	public function rus_date() {
		$this->load->language('extension/module/technics_news');
			// Перевод
			 $translate = array(
					 'am' => $this->language->get('text_am'),
					 'pm' => $this->language->get('text_pm'),
					 'AM' => $this->language->get('text_AM'),
					 'PM' => $this->language->get('text_PM'),
					 'Monday' => $this->language->get('text_Monday'),
					 'Mon' => $this->language->get('text_Mon'),
					 'Tuesday' => $this->language->get('text_Tuesday'),
					 'Tue' => $this->language->get('text_Tue'),
					 'Wednesday' => $this->language->get('text_Wednesday'),
					 'Wed' => $this->language->get('text_Wed'),
					 'Thursday' => $this->language->get('text_Thursday'),
					 'Thu' => $this->language->get('text_Thu'),
					 'Friday' => $this->language->get('text_Friday'),
					 'Fri' => $this->language->get('text_Fri'),
					 'Saturday' => $this->language->get('text_Saturday'),
					 'Sat' => $this->language->get('text_Sat'),
					 'Sunday' => $this->language->get('text_Sunday'),
					 'Sun' => $this->language->get('text_Sun'),
					 'January' => $this->language->get('text_January'),
					 'Jan' => $this->language->get('text_Jan'),
					 'February' => $this->language->get('text_February'),
					 'Feb' => $this->language->get('text_Feb'),
					 'March' => $this->language->get('text_March'),
					 'Mar' => $this->language->get('text_Mar'),
					 'April' => $this->language->get('text_April'),
					 'Apr' => $this->language->get('text_Apr'),
					 'May' => $this->language->get('text_May'),
					 'June' => $this->language->get('text_June'),
					 'Jun' => $this->language->get('text_Jun'),
					 'July' => $this->language->get('text_July'),
					 'Jul' => $this->language->get('text_Jul'),
					 'August' => $this->language->get('text_August'),
					 'Aug' => $this->language->get('text_Aug'),
					 'September' => $this->language->get('text_September'),
					 'Sep' => $this->language->get('text_Sep'),
					 'October' => $this->language->get('text_October'),
					 'Oct' => $this->language->get('text_Oct'),
					 'November' => $this->language->get('text_November'),
					 'Nov' => $this->language->get('text_Nov'),
					 'December' => $this->language->get('text_December'),
					 'Dec' => $this->language->get('text_Dec'),
					 'st' => $this->language->get('text_st'),
					 'nd' => $this->language->get('text_nd'),
					 'rd' => $this->language->get('text_rd'),
					 'th' => $this->language->get('text_th'),
			 );
			 // если передали дату, то переводим ее
			 if (func_num_args() > 1) {
				$timestamp = func_get_arg(1);
			 return strtr(date(func_get_arg(0), $timestamp), $translate);
			 } else {
			// иначе текущую дату
				return strtr(date(func_get_arg(0)), $translate);
			 }
	}
}
