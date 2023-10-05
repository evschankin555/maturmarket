<?php
class ModelExtensionModuleYandexMetrica extends Model {
	public function getProductFromCart($cart_id) {
		$query = $this->db->query("SELECT DISTINCT product_id, quantity FROM " . DB_PREFIX . "cart WHERE cart_id = '" . (int)$cart_id . "'");

		return $query->row;
	}

	public function getOrder($order_id) {
		$order_query = $this->db->query("SELECT `order_id`, `total`, `currency_code` FROM `" . DB_PREFIX . "order` WHERE order_id = '" . (int)$order_id . "' AND order_status_id > '0'");

		if ($order_query->num_rows) {

			return array(
				'order_id'                => $order_query->row['order_id'],
				'total'                   => $order_query->row['total'],
				'currency_code'           => $order_query->row['currency_code']
			);
		} else {
			return false;
		}
	}
}
