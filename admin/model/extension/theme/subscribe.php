<?php
class ModelExtensionThemeSubscribe extends Model {

	public function getSubscribe($subscribe_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "subscribe WHERE subscribe_id='" . (int) $subscribe_id . "'");

		return $query->row;
	}

	public function getEmailDescription() {
		$subscribe_descriptions = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "subscribe_email_description");

		foreach ($query->rows as $result) {
			$subscribe_descriptions[$result['language_id']] = $result['subscribe_descriptions'];
		}

		return $subscribe_descriptions;
	}

	public function addEmailDescription($descriptions) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "subscribe_email_description");

		foreach ($descriptions as $language_id => $description) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "subscribe_email_description SET subscribe_descriptions = '" . $this->db->escape($description) . "', language_id = '" . (int) $language_id . "'");
		}
	}

	public function getAuthDescription() {
		$subscribe_authorization = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "subscribe_auth_description");

		foreach ($query->rows as $result) {
			$subscribe_authorization[$result['language_id']] = $result['subscribe_authorization'];
		}

		return $subscribe_authorization;
	}

	public function addAuthDescription($descriptions) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "subscribe_auth_description");

		foreach ($descriptions as $language_id => $description) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "subscribe_auth_description SET subscribe_authorization = '" . $this->db->escape($description) . "', language_id = '" . (int) $language_id . "'");
		}
	}

	public function addSubscribe($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "subscribe SET email = '" . $this->db->escape($data['email']) . "', status = '" . (int) $data['status'] . "'");
	}

	public function editSubscribe($subscribe_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "subscribe SET email = '" . $this->db->escape($data['email']) . "', status = '" . (int) $data['status'] . "' WHERE subscribe_id = '" . (int) $subscribe_id . "'");
	}

	public function deleteSubscribe($subscribe_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "subscribe WHERE subscribe_id = '" . (int) $subscribe_id . "'");
	}

	public function getSubscribers($data = array()) {
		$sql = "SELECT * FROM " . DB_PREFIX . "subscribe ";

		$sort_data = array(
			'name',
			'status'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= "ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY email";
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

			$sql .= " LIMIT " . (int) $data['start'] . "," . (int) $data['limit'];
		}

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalSubscibe($data = array()) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "subscribe`");

		return $query->row['total'];
	}

	public function checkEmail($email, $subscribe_id) {
		$sql = "SELECT email FROM " . DB_PREFIX . "subscribe WHERE email='" . $this->db->escape($email) . "'";

		if ($subscribe_id) {
			$sql .= " AND subscribe_id !='" . (int)$subscribe_id . "'";
		}

		$query = $this->db->query($sql);

		return isset($query->row['email']) ? $query->row['email'] : 0;
	}

	public function tableExists() {
		$query = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "subscribe'");

		return $query->num_rows;
	}

	public function installSubscribe() {
		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "subscribe` (
			`subscribe_id` int(11) NOT NULL AUTO_INCREMENT,
			`email` text NOT NULL ,
			`status` tinyint(1) NOT NULL,
			PRIMARY KEY (`subscribe_id`)
		) DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "subscribe_email_description` (
			`subscribe_desc_id` int(11) NOT NULL AUTO_INCREMENT,
			`language_id` int(2) NOT NULL,
			`subscribe_descriptions` MEDIUMTEXT NOT NULL,
			PRIMARY KEY (`subscribe_desc_id`)
		) DEFAULT COLLATE=utf8_general_ci;");

		$this->db->query("
		CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "subscribe_auth_description` (
			`subscribe_auth_id` int(11) NOT NULL AUTO_INCREMENT,
			`language_id` int(2) NOT NULL,
			`subscribe_authorization` MEDIUMTEXT NOT NULL,
			PRIMARY KEY (`subscribe_auth_id`)
		) DEFAULT COLLATE=utf8_general_ci;");
	}

	public function uninstallSubscribe() {
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "subscribe`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "subscribe_email_description`");
		$this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "subscribe_auth_description`");
	}

}

?>
