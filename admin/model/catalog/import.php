<?php
class ModelCatalogImport extends Model {	
	public function addImportHistory($supplier, $filename) {
		$this->db->query("INSERT INTO `" . DB_PREFIX . "import_history` SET `supplier` = '" . $this->db->escape($supplier) . "',`filename` = '" . $this->db->escape($filename) . "', `date_added` = NOW(), `user_id` = " . $this->session->data['user_id'] . ", `status` = 'Начали загрузку'");
	
		return $this->db->getLastId();
	}

    public function updateImportHistoryStatus($import_history_id, $status) {
		$this->db->query("UPDATE `" . DB_PREFIX . "import_history` SET `status` = '" . $status . "' WHERE import_history_id = " . $import_history_id);
	}

	public function getImportHistory($start = 0, $limit = 10) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 10;
		}		
		
		$query = $this->db->query("SELECT his.*, usr.username FROM `" . DB_PREFIX . "import_history` his inner join `" . DB_PREFIX . "user` usr on his.user_id = usr.user_id ORDER BY his.date_added DESC LIMIT " . (int)$start . "," . (int)$limit);
	
		return $query->rows;
	}
		
	public function getTotalImportHistories() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "import_history`");

		return $query->row['total'];
	}
}