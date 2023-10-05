<?php
class ControllerApiCategory extends Controller {
	public function index() {
		$this->load->language('api/category');

		$json = array();
		
		$this->load->model('catalog/category');

		$category_list = $this->model_catalog_category->getCategories();
		$json['list'] = $this->language->get($category_list);
		

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
