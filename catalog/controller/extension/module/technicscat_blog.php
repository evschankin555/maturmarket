<?php
class ControllerExtensionModuleTechnicscatblog extends Controller {
	public function index() {
		$this->load->language('extension/module/technics_blog');

		$data['heading_title'] = $this->language->get('heading_title_cat');

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

		$this->load->model('extension/module/technicscatblog');

		$this->load->model('extension/module/technicsblog');

		$this->load->model('catalog/product');

		$data['blogcategories'] = array();

		$blogcategories = $this->model_extension_module_technicscatblog->getBlogCategories(0);

		foreach ($blogcategories as $blogcategory) {
			$children_data = array();

			if ($blogcategory['category_id'] == $data['category_id']) {
				$children = $this->model_extension_module_technicscatblog->getBlogCategories($blogcategory['category_id']);

				foreach($children as $child) {
					$filter_data = array('filter_category_id' => $child['category_id'], 'filter_sub_category' => true);

					$children_data[] = array(
						'category_id' => $child['category_id'],
						'name' => $child['name'],
						'href' => $this->url->link('extension/module/technicscat_blog', 'lbpath=' . $blogcategory['category_id'] . '_' . $child['category_id'])
					);
				}
			}

			$filter_data = array(
				'filter_category_id'  => $blogcategory['category_id'],
				'filter_sub_category' => true
			);

			$data['blogcategories'][] = array(
				'category_id' => $blogcategory['category_id'],
				'name'        => $blogcategory['name'],
				'count'        => $this->model_extension_module_technicsblog->getTotalBlogs($filter_data),
				'children'    => $children_data,
				'href'        => $this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=' . $blogcategory['category_id'])
			);
		}

		return $this->load->view('extension/module/technicscat_blog', $data);
	}

	public function getcat() {
		$this->load->language('extension/module/technics_blog');

		$data['heading_title'] = $this->language->get('heading_title');

		$this->load->model('extension/module/technicsblog');
		$this->load->model('extension/module/technicscatblog');
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
			'href' => $this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=0')
		);

		$data['schema'] = $this->config->get('theme_technics_schema');
		$data['lazyload'] = $this->config->get('theme_technics_lazyload');

		if (isset($this->request->get['page'])) {
			$page = $this->request->get['page'];
		} else {
			$page = 1;
		}
		$category_id = 0;
		
		if (isset($this->request->get['lbtag'])) {
			$filtertag = $this->request->get['lbtag'];
		} else {
			$filtertag = false;
		}

		if(isset($this->request->get['lbpath'])){
			$url = '';

			if (isset($this->request->get['sort'])) {
				$url .= '&sort=' . $this->request->get['sort'];
			}

			if (isset($this->request->get['order'])) {
				$url .= '&order=' . $this->request->get['order'];
			}

			if (isset($this->request->get['limit'])) {
				$url .= '&limit=' . $this->request->get['limit'];
			}
//technics start
			if (isset($this->request->get['min_price'])) {
				$url .= '&min_price=' . $this->request->get['min_price'];
			} 

			if (isset($this->request->get['max_price'])) {
				$url .= '&max_price=' . $this->request->get['max_price'];
			} 

			$path = '';

			$parts = explode('_', (string)$this->request->get['lbpath']);

			$category_id = (int)array_pop($parts);
			
			if ($category_id) {
				$category_info = $this->model_extension_module_technicscatblog->getBlogCategory($category_id);


				if ($category_info["meta_h1"]) {
					$data['heading_title'] = $category_info["meta_h1"];
				}else{
					$data['heading_title'] = $category_info['name'];
				}

				if ($category_info["meta_title"]) {
					$this->document->setTitle($category_info["meta_title"]);
				} else {
					$this->document->setTitle($this->language->get('heading_title'));
				}

				if ($category_info["meta_description"]) {
					$this->document->setDescription($category_info["meta_description"]);
				} else {
					$this->document->setDescription($this->language->get('heading_title'));
				}

				if ($category_info["meta_keyword"]) {
					$this->document->setKeywords($category_info["meta_keyword"]);
				} 

				$data['category_description'] = html_entity_decode($category_info['description'], ENT_QUOTES, 'UTF-8');


				$blogCat = array();
				$catinfo = $this->model_extension_module_technicscatblog->getBlogCategory($category_id);
				if($catinfo){
					$blogCat = array(
						'name'		=>	$catinfo['name'],
						'href'		=>	$this->url->link('extension/module/technics_blog/getcat', 'lbpath=' . $catinfo['category_id'])
					);
					
					if ($catinfo['meta_title']) {
						$this->document->setTitle($catinfo['meta_title']);
					} else {
						$this->document->setTitle($catinfo['name']);
					}

					$this->document->setDescription($catinfo['meta_description']);
					$this->document->setKeywords($catinfo['meta_keyword']);
				}


				$data['breadcrumbs'][] = array(
					'text' => $blogCat['name'],
					'href' => $blogCat['href']
				);


			}else{
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
				$data['category_description'] =  '';
				$blogCat = array();				
			}


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
						'href' => $this->url->link('extension/module/technics_blog/getcat', 'lbpath=' . $path . $url)
					);
				}		
			}
		}

		$data['catblogmod'] = $this->config->get('technicscat_blog_status');



		
		$limit = $this->config->get('theme_technics_blog_limit');

		
		$order = 'DESC';
		
			$filter_data = array(
				'filter_category_id'  => $category_id,
				'filtertag'          => $filtertag,
				'order'              => $order,
				'start'              => ($page - 1) * $limit,
				'limit'              => $limit
			);

		$data['blogs'] = array();

		$blogs = $this->model_extension_module_technicsblog->getBlogs($filter_data);

		if ($blogs) {
			foreach ($this->model_extension_module_technicsblog->getBlogs($filter_data) as $result) {

				$commentCount = $this->model_extension_module_lbcomment->getTotalCommentsByBlogId($result['blog_id']);	

				$blogCat = array();
				$catinfo = $this->model_extension_module_technicsblog->getBlogCat($result['blog_id']);

				if($catinfo){
					$blogCat = array(
						'name'		=>	$catinfo['name'],
						'href'		=>	$this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=' . $catinfo['category_id'])
					);
				}			

				if($result['image']){
					$image = $this->config->get('theme_technics_image_blog_cat_resize') ? $this->model_tool_image->technics_resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height')) : $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height'));
					$image_3x = $this->config->get('theme_technics_image_blog_cat_resize') ? $this->model_tool_image->technics_resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width')*3, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height')*3) : $this->model_tool_image->resize($result['image'], $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width')*3, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height')*3);
				} else {
					$image = $this->model_tool_image->resize('no_image.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width'), $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height'));
					$image_3x = $this->model_tool_image->resize('no_image.png', $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width')*3, $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_height')*3);
				}

				if($this->config->get('theme_technics_blog_rev_st')){
					$commenrtsenable = true;
				} else {
					$commenrtsenable = false;
				}

				$hrefBlog = $this->url->link('extension/module/technics_blog/getblog', 'blog_id=' . $result['blog_id']);
				if($this->config->get($this->config->get('config_theme') . '_blog_path')){
					$hrefBlog = $this->url->link('extension/module/technics_blog/getblog','lbpath=' . $category_id . '&blog_id=' . $result['blog_id']);
				}
				
				$data['blogs'][] = array(
					'title' => $result['title'],
					'image' => $image,
					'image_3x' => $this->config->get('theme_' . $this->config->get('config_theme') . '_image_blog_cat_width') == 960 ? $image : $image_3x,
					'viewed' => $result['viewed'],
					'blogcat' => $blogCat,
					'commentcount' => $commentCount,
					'commenrtsenable' => $commenrtsenable,
					'date_added' => $this->rus_date("j F, Y ", strtotime($result['date_added'])),
					'description' => utf8_substr(strip_tags(html_entity_decode($result['description'], ENT_QUOTES, 'UTF-8')), 0, 150) . '...',
					'href'  => $hrefBlog
				);
			}

	
		
			$totalBlogs = $this->model_extension_module_technicsblog->getTotalBlogs($filter_data);
				$pagination = new Pagination();
				$pagination->total = $totalBlogs;
				$pagination->page = $page;
				$pagination->limit = $limit;
				$pagination->url = $this->url->link('extension/module/technicscat_blog/getcat', 'lbpath=' . $category_id . '&page={page}');

				$data['pagination'] = $pagination->render();
				
				$data['column_left'] = $this->load->controller('common/column_left');
				$data['column_right'] = $this->load->controller('common/column_right');
				$data['content_top'] = $this->load->controller('common/content_top');
				$data['content_bottom'] = $this->load->controller('common/content_bottom');
				$data['footer'] = $this->load->controller('common/footer');
				$data['header'] = $this->load->controller('common/header');

			$this->response->setOutput($this->load->view('extension/module/technics_blog_list_main', $data));
		}else{
			$this->load->language('extension/theme/technics');
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
	
			if (is_file(DIR_IMAGE . $this->config->get('theme_technics_logo_404'))) {
				$data['logo_404'] = isset($this->request->server['HTTPS']) ? HTTPS_SERVER : HTTP_SERVER . 'image/' . $this->config->get('theme_technics_logo_404');
			} else {
				$data['logo_404'] = '';
			}
			$data['text_404'] = sprintf($this->language->get('text_404'), $this->url->link('information/contact', '', true), $this->url->link('product/search', '', true), $this->url->link('common/home', '', true));
			$this->response->setOutput($this->load->view('error/404', $data));

		}
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