<?php
class ModelLocalisationCity extends Model {
	public function getCityById($citId){
		$cities = $this->getCities();
		foreach ( $cities as $city ) {			
			if( $citId == $city['city_id'] ) {
				return $city;
			}			
		}

		return null;
	}

	public function getCities() {
		$city_data = $this->cache->get('city.catalog');

		if (!$city_data) {
			$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "city WHERE status = '1' ORDER BY sort_order, name ASC");

			$city_data = $query->rows;

			$this->cache->set('city.catalog', $city_data);
		}

		return $city_data;
	}

	public function getCityByIp(){		
		$client_ip = $_SERVER['REMOTE_ADDR'];
		// $client_ip = '188.162.37.245'; // ip Самары

		// Поищем в кеше
		$current_or_default_city = $this->cache->get('city.byip.' . $client_ip);
		if ($current_or_default_city) {
			return $current_or_default_city;
		}

		// Если в кеше не нашли, получим данные от дадаты
		require_once(DIR_SYSTEM . 'library/dadata.php');
		$token = "878b965e6d56f2950009a6d7921bf12bf6807f21";
		$secret = "8faf95bf4bff8382878fb7abac54175c97a13077";

		$dadata = new Dadata($token, $secret);
		$dadata->init();			

		// Получим текущее местоположение
		$location_data = $dadata->iplocate($client_ip);
		$dadata->close();

		$location_city_fias_id = !is_null( $location_data['location'] ) ? $location_data['location']['data']['city_fias_id'] : '';

		// Если город из местоположения пользователя есть в доступных городах, заполним инфу по этому городу
		$cities = $this->getCities();
		foreach ( $cities as $city ) {
			if( $location_city_fias_id == $city['fiasId'] ) {
				$current_or_default_city = $city;				
				break;
			}			
		}

		// Если город пользователя не в списке доступных используем дефолтный город
		if (empty($current_or_default_city)){
			$current_or_default_city = $this->getCityById($this->config->get('config_city_id'));
		}

		// Положим в кеш
		$this->cache->set('city.byip.' . $client_ip, $current_or_default_city);

		return $current_or_default_city;
	}

	public function isAvailableCityById($citId){
		$cities = $this->getCities();
		foreach ( $cities as $city ) {			
			if( $citId == $city['city_id'] ) {
				return true;
			}			
		}

		return false;
	}
}