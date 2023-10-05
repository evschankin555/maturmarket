<?php

class ModelExtensionPaymentDolyameTransactions extends Model {

	public function getTotalTransactions($data = array()) {
		$sql = "SELECT COUNT(*) AS total FROM `" . DB_PREFIX . "dolyame`";


		if (!empty($data['filter_order_id'])) {
			$sql .= " WHERE order_id = '" . (int)$data['filter_order_id'] . "'";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTransactions($data = array()) {
		$sql = "SELECT o.* FROM `" . DB_PREFIX . "dolyame` o";

		if (!empty($data['filter_order_id'])) {
			$sql .= " WHERE o.order_id= '" . (int)$data['filter_order_id'] . "'";
		}

		$sort_data = array(
			'o.order_id',
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY o.order_id";
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
	}

	public function getOrderItems($orderId)
	{
		$query = $this->db->query("SELECT * FROM `" . DB_PREFIX . "dolyame` WHERE order_id=".intval($orderId));
		if (empty($query->row)) {
			return false;
		}
		return json_decode($query->row['items'], true);
	}

}