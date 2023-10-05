<?php
class ControllerApiCity extends Controller {
	public function index() {
        $this->load->language('api/city');

		$json = array();

		$host = strtolower(isset($this->request->server['HTTPS']) && (($this->request->server['HTTPS'] == 'on') || ($this->request->server['HTTPS'] == '1')) ? HTTPS_SERVER : HTTP_SERVER);

		if (!isset($_SERVER['HTTP_REFERER']) || strpos(strtolower($_SERVER['HTTP_REFERER']), $host) !== 0) {
			$json['error'] = $this->language->get('error_permission');
		} else {
			$locationCity = $this->model_localisation_city->getCityByIp();

            $json['fiasId'] = $locationCity['fiasId']; 
			$json['city_name'] = $locationCity['name'];
			$json['city_id'] = $locationCity['city_id'];
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}
}
