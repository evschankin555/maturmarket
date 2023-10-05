<?php
class ModelExtensionModuleCallback extends Model {	
	public function addCallback($data) {

    	  	$query = $this->db->query("INSERT INTO " . DB_PREFIX . "callback SET name = '" .  $this->db->escape($data['name'])  . "', telephone = '" .  $this->db->escape($data['phone']) . "', date_added = NOW(), date_modified = NOW(), status_id = '0', store_id = '" . (int)$this->config->get('config_store_id') . "' ,comment = '" . $this->db->escape($data['comment']) . "'");
	
		return $this->db->getLastId();
	}	
		
}
?>
