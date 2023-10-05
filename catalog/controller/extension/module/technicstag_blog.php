<?php
class ControllerExtensionModuleTechnicstagblog extends Controller {

	public function index() {
		$this->load->language('extension/module/technics_blog');


		$this->load->model('extension/module/technicsblog');

		
		if(!$this->model_extension_module_technicsblog->isModuleSet()){
			$this->response->redirect($this->url->link('error/not_found', '', true));
		}

		$data['heading_title'] = $this->language->get('heading_title_cloud');

		$category_id = 0;
		
		if(isset($this->request->get['lbpath'])){
			$path = '';

			$parts = explode('_', (string)$this->request->get['lbpath']);

			$category_id = (int)array_pop($parts);
			$id = 0;
			foreach ($parts as $path_id) {
				if (!$path) {
					$path = (int)$path_id;
				} else {
					$path .= '_' . (int)$path_id;
				}
			}			
		}

		
//		$limit = $this->config->get('theme_technics_blog_limit');
		$limit = 100;
		
		$order = 'DESC';
		
			$filter_data = array(
				'filter_category_id'  => $category_id,
				'order'              => $order,
				'start'              => 0,
				'limit'              => $limit
			);

		$data['tags'] = array();

		foreach ($this->model_extension_module_technicsblog->getBlogsTag($filter_data) as $result) {

			$data['tags'][] = array(
				'tag' => trim($result['tag']),
				'total' => $result['total'],
				'href'  => $this->url->link('extension/module/technicscat_blog/getcat&lbpath=' . $category_id, 'lbtag=' . trim($result['tag']))
			);
		}	
		
		$totalBlogs = $this->model_extension_module_technicsblog->getBlogsTotal();


		return $this->load->view('extension/module/technicstag_blog', $data);
	}


}