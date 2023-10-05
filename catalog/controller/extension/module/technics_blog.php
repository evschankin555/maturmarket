<?php
class ControllerExtensionModuleTechnicsBlog extends Controller {
	public function index($settings) {
		$this->load->language('extension/module/technics_blog');

		$data['heading_title'] = $this->language->get('heading_title');
		$data['text_all_blog'] = $this->language->get('text_all_blog');

		$this->load->model('extension/module/technicsblog');
		
		$data['blog_href'] = $this->url->link('extension/module/technicscat_blog/getcat&lbpath=0');
		$limit = 3;
		$page = 1;
		$order = 'DESC';
		
			$filter_data = array(
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);


		$data['blogs'] = array();

		foreach ($this->model_extension_module_technicsblog->getBlogs($filter_data) as $result) {
			$data['blogs'][] = array(
				'title' => $result['title'],
				'date_added' => $this->rus_date("j F, Y ", strtotime($result['date_added'])),
				'description' => html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8'),
				'href'  => $this->url->link('extension/module/technics_blog/getblog', 'blog_id=' . $result['blog_id'])
			);
		}

		if(isset($settings['layout']) && strpos($settings['layout'],'column_') !== false){
			return $this->load->view('extension/module/technics_blog_column', $data);
		}else{
			return $this->load->view('extension/module/technics_blog_list', $data);
		}

		
	}

	public function write() {
		$this->load->language('extension/module/technics_blog');

		$json = array();

		if ($this->request->server['REQUEST_METHOD'] == 'POST') {

			if (!$this->config->get('theme_technics_blog_rev_guest') && !$this->customer->isLogged()) {
				$json['error'] = $this->language->get('error_logged');
			}

			if ((utf8_strlen($this->request->post['name']) < 3) || (utf8_strlen($this->request->post['name']) > 25)) {
				$json['error'] = $this->language->get('error_name');
			}

			if ((utf8_strlen($this->request->post['text']) < 25) || (utf8_strlen($this->request->post['text']) > 1000)) {
				$json['error'] = $this->language->get('error_text');
			}
			
			// Captcha
			if ($this->config->get($this->config->get('theme_technics_config_captcha_cblog') . '_status')) {
				$captcha = $this->load->controller('extension/captcha/' . $this->config->get('theme_technics_config_captcha_cblog') . '/validate');

				if ($captcha) {
					$json['error'] = $captcha;
				}
			}

			$this->request->post['email'] = '';

			if (!isset($json['error'])) {
				$this->load->model('extension/module/lbcomment');

				$this->model_extension_module_lbcomment->addComment($this->request->get['blog_id'], $this->request->post);

				if ($this->config->get('theme_technics_blog_rev_moder')) {
					$json['redirect'] = 1;
				}else{
					$json['success'] = $this->language->get('text_success');
				}
			}
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}


	public function getblog() {
		$this->load->language('extension/module/technics_blog');

		$this->load->model('extension/module/technicsblog');
		$this->load->model('extension/module/technicscatblog');
		$this->load->model('tool/image');
		$this->load->model('catalog/product');

		$data['text_related'] = $this->language->get('text_related');
		$data['text_related_products'] = $this->language->get('text_related_products');
		$data['text_tags'] = $this->language->get('text_tags');
		$data['text_share'] = $this->language->get('text_share');
		$data['text_comments'] = $this->language->get('text_comments');
		$data['text_first_comment'] = $this->language->get('text_first_comment');
		$data['text_comment_add'] = $this->language->get('text_comment_add');
		$data['text_comment_name'] = $this->language->get('text_comment_name');
		$data['text_comment_text'] = $this->language->get('text_comment_text');


		$data['breadcrumbs'] = array();

		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technicscat_blog/getcat&lbpath=0')
		);
		
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');
		
		if (isset($this->request->get['blog_id'])) {
			$blog_id = (int)$this->request->get['blog_id'];
		} else {
			$blog_id = 0;
		}

		$data['blog_id'] = $blog_id;
		
		if($this->config->get('config_seo_url_type') != 'seo_pro' || !file_exists(DIR_APPLICATION . 'controller/startup/seo_pro.php')){
			$this->document->addLink($this->url->link('extension/module/technics_blog/getblog', 'blog_id=' . $blog_id), 'canonical');
		}

		$data['commenrtsenable'] = $this->config->get('theme_technics_blog_rev_st');
		$data['blog_background'] = $this->config->get('theme_technics_blog_background');
		$data['soc_share_blog'] = $this->config->get('theme_technics_soc_share_blog');
		$data['soc_share_code'] = html_entity_decode($this->config->get('theme_technics_soc_share_code'), ENT_QUOTES, 'UTF-8');

		$pathInfo = $this->model_extension_module_technicscatblog->getPathByBlog($blog_id);

		$category_id = 0;

		if($pathInfo){

			$path = '';

			$parts = explode('_', (string)$pathInfo);

			$category_id = (int)array_pop($parts);

			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}
				$category_info = $this->model_extension_module_technicscatblog->getBlogCategory($path_id);
				if ($category_info) {
					$data['breadcrumbs'][] = array(
						'text' => $category_info['name'],
						'href' => $this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=' . $path )
					);
				}		
			}
		}

		$category_info = $this->model_extension_module_technicscatblog->getBlogCategory($category_id);
		if ($category_info) {
			$data['breadcrumbs'][] = array(
				'text' => $category_info['name'],
				'href' => $this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=' . $category_id)
			);
		}			


		$blog_info = $this->model_extension_module_technicsblog->getBlog($blog_id);

		$data['entry_comment'] = $this->language->get('entry_comment');

		$this->load->model('extension/module/lbcomment');

		$data['comment_status'] = $this->config->get('config_comment_status');

			if ($this->config->get('config_comment_guest') || $this->customer->isLogged()) {
				$data['comment_guest'] = true;
			} else {
				$data['comment_guest'] = false;
			}

			if ($this->customer->isLogged()) {
				$data['customer_name'] = $this->customer->getFirstName() . '&nbsp;' . $this->customer->getLastName();
			} else {
				$data['customer_name'] = '';
			}

		$data['comments'] = $this->model_extension_module_lbcomment->getCommentsByBlogId($blog_id);
		foreach ($data['comments'] as $key => $comment) {
			$data['comments'][$key]['date_added'] = $this->rus_date("j F, Y ", strtotime($comment['date_added']));
		}	

		$data['totalComments'] = $this->model_extension_module_lbcomment->getTotalCommentsByBlogId($blog_id);

		$data['schema'] = $this->config->get('theme_technics_schema');



		if ($blog_info) {

			$data['date_added'] = $this->rus_date("j F, Y ", strtotime($blog_info['date_added']));
		
			if ($blog_info['meta_title']) {
				$this->document->setTitle($blog_info['meta_title']);
			} else {
				$this->document->setTitle($blog_info['title']);
			}

			$this->document->setDescription($blog_info['meta_description']);
			$this->document->setKeywords($blog_info['meta_keyword']);

			$data['breadcrumbs'][] = array(
				'text' => $blog_info['title'],
				'href' => $this->url->link('extension/module/technics_blog/getblog', 'blog_id=' .  $blog_id)
			);

			if ($blog_info['meta_h1']) {
				$data['heading_title'] = $blog_info['meta_h1'];
			} else {
				$data['heading_title'] = $blog_info['title'];
			}

		if ($this->config->get('theme_technics_blog_pdata')) {
			$this->load->language('extension/theme/technics');
			$this->load->model('catalog/information');

			$information_info = $this->model_catalog_information->getInformation($this->config->get('theme_technics_blog_pdata'));

			if ($information_info) {
				$data['text_technics_pdata'] = sprintf($this->language->get('text_technics_pdata'), $this->language->get('text_comment_add'), $this->url->link('information/information/agree', 'information_id=' . $this->config->get('theme_technics_blog_pdata'), true), $information_info['title'], $information_info['title']);
			} else {
				$data['text_technics_pdata'] = '';
			}
		} else {
			$data['text_technics_pdata'] = '';
		}

			$data['tags'] = array();
			$tag_info = $this->model_extension_module_technicsblog->getBlogTag($blog_id);
			if($tag_info){
				foreach($tag_info as $tag){
					$data['tags'][] = array(
						'title'			=>  $tag['tag'],
						'href'			=>  $this->url->link('extension/module/technicscat_blog/getcat&lbpath=0', 'lbtag=' . trim($tag['tag']))
					);
				}
				
			}



			$data['blogs'] = array();

			$results = $this->model_extension_module_technicsblog->getBlogRelated($this->request->get['blog_id']);

		// technics
		$this->load->language('extension/theme/technics');
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
		
			foreach ($results as $result_id) {
				$result = $this->model_extension_module_technicsblog->getBlog($result_id);

				if ($this->config->get('theme_technics_image_blog_related_resize')) {
					if ($result['image']) {
						$image = $this->model_tool_image->technics_resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_height'));
					} else {
						$image = $this->model_tool_image->technics_resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_height'));
					}
				} else {
					if ($result['image']) {
							$image = $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_height'));
					} else {
						$image = $this->model_tool_image->resize('placeholder.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_related_height'));
					}
				}

				$commentCount = $this->model_extension_module_lbcomment->getTotalCommentsByBlogId($result['blog_id']);
				
				if($this->config->get('theme_technics_blog_rev_st')){
					$commenrtsenable = true;
				} else {
					$commenrtsenable = false;
				}
			
				$data['blogs'][] = array(
					'blog_id'  => $result['blog_id'],
					'thumb'       => $image,
					'name'        => $result['title'],
					'date'        => $this->rus_date("j F, Y ", strtotime($result['date_added'])),
					'commentcount' => $commentCount,
					'commenrtsenable' => $commenrtsenable,
					'viewed' => $result['viewed'],
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'href'        => $this->url->link('extension/module/technics_blog/getblog', 'blog_id=' . $result['blog_id'])
				);
			}


			$data['products'] = array();

			$results = $this->model_extension_module_technicsblog->getBlogRelatedProd($this->request->get['blog_id']);

			foreach ($results as $result_id) {
				$result = $this->model_catalog_product->getProduct($result_id);
				if (!$result['product_id']) {continue;}

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
					if (in_array($result['product_id'], $newest)) {
						$isNewest = true;
					} else {
						$isNewest = false;
					}
					
					$extraImages = array();				
					$images = $this->model_catalog_product->getProductImages($result['product_id']);
					foreach($images as $imageX){
						$extraImages[] = $this->config->get('theme_technics_image_product_resize') ? $this->model_tool_image->technics_resize($imageX['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height')) : $this->model_tool_image->resize($imageX['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_product_height'));
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
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, $this->config->get('theme_' . $this->config->get('config_theme') . '_product_description_length')) . '..',
					'price'       => $price,
					'isnewest'       => $isNewest,// technics
					'sales'       => $sales,// technics
					'discount'       => $discount,// technics
					'catch'       => $catch,// technics
					'popular'   => $popular,// technics
					'hit'    	  => $hit,// technics
					'nocatch'       => $nocatch,// technics
					'manufacturer'=> $manufacturer,// technics
					'reward'      => $result['reward'],// technics
					'buy_btn'	  => $buy_btn,// technics
					'images'      => $extraImages,// technics	
					'special'     => $special,
					'tax'         => $tax,
					'minimum'     => $result['minimum'] > 0 ? $result['minimum'] : 1,
					'rating'      => $rating,
					'href'        => $this->url->link('product/product', 'product_id=' . $result['product_id'])
				);
			}

			if ($blog_info['image']) {
				$image = $this->config->get('theme_technics_image_blog_item_resize') ? $this->model_tool_image->technics_resize($blog_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_item_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_item_height')) : $this->model_tool_image->resize($blog_info['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_item_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_item_height'));
					if ($this->config->get('theme_technics_og')) { //Technics added this
						$this->document->setOgImage($image);
					} //Technics added this
			} else {
				$image = $this->model_tool_image->resize('placeholder.png', 100, 100);
			}

			$data['image'] = $image;

			$data['button_continue'] = $this->language->get('button_continue');

			$data['description'] = html_entity_decode($blog_info['description'], ENT_QUOTES, 'UTF-8');

			$data['href'] = $this->url->link('extension/module/technics_blog/getblog', 'blog_id=' . $blog_id);
			
			$data['store'] = $this->config->get('config_name');
			
			$data['schema_description'] = str_replace(array("\r\n", "\r", "\n", "\""),' ', strip_tags(html_entity_decode($blog_info['description'], ENT_QUOTES, 'UTF-8')));
			
			$data['schema_meta_description'] = str_replace(array("\r\n", "\r", "\n", "\""),' ', strip_tags(html_entity_decode($blog_info['meta_description'], ENT_QUOTES, 'UTF-8')));
			
			$data['schema_date_added'] = date('Y-m-d', strtotime($blog_info['date_added']));
			
			if ($this->request->server['HTTPS']) {
				$server = $this->config->get('config_ssl');
			} else {
				$server = $this->config->get('config_url');
			}
			if (is_file(DIR_IMAGE . $this->config->get('theme_technics_header_logo'))) {
				$data['schema_logo'] = $server . 'image/' . $this->config->get('theme_technics_header_logo');
			} else {
				$data['schema_logo'] = $server . 'image/' . $this->config->get('config_logo');
			}

			$data['continue'] = $this->url->link('common/home');

			$this->model_extension_module_technicsblog->updateViewed($blog_id);

			$data['viewed'] = $blog_info['viewed'];
			
			// Captcha
			if ($this->config->get($this->config->get('theme_technics_config_captcha_cblog') . '_status'))  {
				$data['captcha'] = $this->load->controller('extension/captcha/' . $this->config->get('theme_technics_config_captcha_cblog'));
			} else {
				$data['captcha'] = '';
			}

			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('extension/module/technics_blog', $data));
		} else {
			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_error'),
				'href' => $this->url->link('information/information', 'blog_id=' . $blog_id)
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
	public function getbloglist() { 
		$this->load->language('extension/module/technics_blog');
		
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if ($this->config->get('theme_technics_blog_meta_title' . $this->config->get('config_language_id'))) {
			$this->document->setTitle($this->config->get('theme_technics_blog_meta_title' . $this->config->get('config_language_id')));
		} else {
			$this->document->setTitle($this->language->get('heading_title'));
		}
		
		if ($this->config->get('theme_technics_blog_meta_description' . $this->config->get('config_language_id'))) {
			$this->document->setDescription($this->config->get('theme_technics_blog_meta_description' . $this->config->get('config_language_id')));
		}
		
		if ($this->config->get('theme_technics_blog_meta_keyword' . $this->config->get('config_language_id'))) {
			$this->document->setKeywords($this->config->get('theme_technics_blog_meta_keyword' . $this->config->get('config_language_id')));
		}

		$data['heading_title'] = $this->language->get('heading_title');

		$this->load->model('extension/module/technicsblog');
		$this->load->model('extension/module/lbcomment');
		$this->load->model('tool/image');
		
		if(!$this->model_extension_module_technicsblog->isModuleSet()){
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}
		
		$data['breadcrumbs'] = array();
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('text_home'),
			'href' => $this->url->link('common/home')
		);
		$data['breadcrumbs'][] = array(
			'text' => $this->language->get('heading_title'),
			'href' => $this->url->link('extension/module/technicscat_blog/getcat&lbpath=0')
		);
		$data['schema'] = $this->config->get('theme_technics_schema');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}

		if (isset($this->request->get['lbtag'])) {
			$filtertag = $this->request->get['lbtag'];
		} else {
			$filtertag = false;
		}
		
		$limit = $this->config->get('theme_technics_blog_limit');
		
		$order = 'DESC';
		
			$filter_data = array(
				'filtertag'          => $filtertag,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

		$data['catblogmod'] = $this->config->get('technicscat_blog_status');
		
		$data['blogs'] = array();

		foreach ($this->model_extension_module_technicsblog->getBlogs($filter_data) as $result) {

			$blogCat = array();
			$catinfo = $this->model_extension_module_technicsblog->getBlogCat($result['blog_id']);

			$commentCount = $this->model_extension_module_lbcomment->getTotalCommentsByBlogId($result['blog_id']);

			if($catinfo){
				$blogCat = array(
					'name'		=>	$catinfo['name'],
					'href'		=>	$this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=' . $catinfo['category_id'])
				);
			}

			if($result['image']){
				$image = $this->config->get('theme_technics_image_blog_cat_resize') ? $this->model_tool_image->technics_resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height')) : $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height'));
			} else {
				$image = $this->model_tool_image->resize('no_image.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height'));
			}

			if($this->config->get('theme_technics_blog_rev_st')){
				$commenrtsenable = true;
			} else {
				$commenrtsenable = false;
			}

			$data['blogs'][] = array(
				'title' => $result['title'],
				'image' => $image,
				'blogcat' => $blogCat,
				'commentcount' => $commentCount,
				'commenrtsenable' => $commenrtsenable,
				'viewed' => $result['viewed'],
				'date_added' => $this->rus_date("j F, Y ", strtotime($result['date_added'])),
				'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 150) . '...',
				'href'  => $this->url->link('extension/module/technics_blog/getblog', 'blog_id=' . $result['blog_id'])
			);
		}	
		
		$totalBlogs = $this->model_extension_module_technicsblog->getTotalBlogs($filter_data);
			$pagination = new Pagination();
			$pagination->total = $totalBlogs;
			$pagination->page = $page;
			$pagination->limit = $limit;
			$pagination->url = $this->url->link('extension/module/technicscat_blog/getcat&lbpath=0&page={page}');

			$data['pagination'] = $pagination->render();
			
			$data['column_left'] = $this->load->controller('common/column_left');
			$data['column_right'] = $this->load->controller('common/column_right');
			$data['content_top'] = $this->load->controller('common/content_top');
			$data['content_bottom'] = $this->load->controller('common/content_bottom');
			$data['footer'] = $this->load->controller('common/footer');
			$data['header'] = $this->load->controller('common/header');

		$this->response->setOutput($this->load->view('extension/module/technics_blog_list_main', $data));		
	}
	
	public function rus_date() {
		$this->load->language('extension/module/technics_blog');
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
