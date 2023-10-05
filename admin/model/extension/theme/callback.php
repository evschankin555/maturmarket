<?php
class ModelExtensionThemeCallback extends Model {

	public function editCallback($callback_id,$data) {
      	$this->db->query("UPDATE " . DB_PREFIX . "callback SET comment = '" . $this->db->escape($data['comment']) . "', status_id = '" . (int)$data['status_id'] . "', date_modified = NOW() WHERE call_id = '" . (int)$callback_id . "'");	
		}

	
	public function editCallbacks($callback_id) {
      	$this->db->query("UPDATE " . DB_PREFIX . "callback SET status_id = '1', date_modified = NOW() WHERE call_id = '" . (int)$callback_id . "'");


	}
	
	public function deleteCallback($callback_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "callback WHERE call_id = '" . (int)$callback_id . "'");

			
		$this->cache->delete('callback');
	}	
	
	public function getCallback($callback_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "callback WHERE call_id = '" . (int)$callback_id . "'");
		
		return $query->row;
	}
	
	public function getCallbackNew() {
		$query = $this->db->query("
		CREATE TABLE IF NOT EXISTS  " . DB_PREFIX . "callback (call_id int(11) NOT NULL
		 primary key AUTO_INCREMENT,name varchar(30) NOT NULL,telephone varchar(30) NOT NULL,
		 date_added datetime NOT NULL,date_modified datetime NOT NULL,
		 status_id int(11) NOT NULL,store_id int(2) NOT NULL DEFAULT '0',comment text NOT NULL)");

		$query = $this->db->query("SELECT count(*) as c FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='" . DB_DATABASE . "' AND TABLE_NAME='" . DB_PREFIX . "callback' AND COLUMN_NAME='store_id'");
	    if (!$query->row['c']){
	        $query = $this->db->query("ALTER TABLE " . DB_PREFIX . "callback ADD store_id int(2) NOT NULL DEFAULT '0'");
	    }

		$query = $this->db->query("SELECT COUNT(status_id) as total FROM " . DB_PREFIX . "callback WHERE status_id = '0'");
		
		return $query->row;
	}
	
	public function getCallbacks($data = array()) {

		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "callback";
			
			$sort_data = array(
				'call_id',
				'store_id',
				'name',
				'telephone'
			);	
			
			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];	
			} else {
				$sql .= " ORDER BY call_id";	
			}
			
			if (isset($data['order']) && ($data['order'] == 'DESC')) {
				$sql .= " DESC";
			} else {
				$sql .= " ASC";
			}
			
			if (isset($data['start']) || isset($data['limit'])) {
				if ($data['start'] < 0) {
					$data['start'] = 0;
				}					

				if ($data['limit'] < 1) {
					$data['limit'] = 20;
				}	
			
				$sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
			}				
			
			$query = $this->db->query($sql);
		
			return $query->rows;
		} else {
			$callback_data = $this->cache->get('callback');
		
			if (!$callback_data) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "callback ORDER BY call_id");
	
				$callback_data = $query->rows;
			
				$this->cache->set('callback', $callback_data);
			}
		 
			return $callback_data;
		}
	}
	
	public function getManufacturerStores($manufacturer_id) {
		$manufacturer_store_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "manufacturer_to_store WHERE manufacturer_id = '" . (int)$manufacturer_id . "'");

		foreach ($query->rows as $result) {
			$manufacturer_store_data[] = $result['store_id'];
		}
		
		return $manufacturer_store_data;
	}
	
	public function getTotalManufacturersByImageId($image_id) {
      	$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "manufacturer WHERE image_id = '" . (int)$image_id . "'");

		return $query->row['total'];
	}

	public function getTotalCallbacks() {

      	$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "callback");

      	if ($query->row['total']) { // Change table to new format if need
      	 	$queryCheck = $this->db->query("SELECT * FROM " . DB_PREFIX . "callback");
      	 	if (!isset($queryCheck->row['store_id'])) {
      	 		$this->db->query("ALTER TABLE " . DB_PREFIX . "callback ADD `store_id` INT(2) NOT NULL DEFAULT '0' AFTER `status_id`");
      	 	}
      	 } 
		
		return $query->row['total'];
	}	
}
?>
