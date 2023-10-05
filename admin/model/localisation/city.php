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

	public function getCityByName($city_name) {
		$cities = $this->getCities();
		foreach ( $cities as $city ) {			
			if( $city_name == $city['name'] ) {
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
}