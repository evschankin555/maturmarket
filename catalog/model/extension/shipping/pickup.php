<?php
class ModelExtensionShippingPickup extends Model {
	function getQuote($address) {
		$this->load->language('extension/shipping/pickup');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int)$this->config->get('shipping_pickup_geo_zone_id') . "' AND country_id = '" . (int)$address['country_id'] . "' AND (zone_id = '" . (int)$address['zone_id'] . "' OR zone_id = '0')");

		if (!$this->config->get('shipping_pickup_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {

			$content = '<div class="shipping-text">При оформлении заказа до 20.00 самовывоз возможен со следующего рабочего дня и в течение 7 дней. ПН-ПТ с 10.00 до 17.00 по адресу г. Казань, ул. Некрасова д. 26, офис 3</div>';

			$quote_data = array();

			$quote_data['pickup'] = array(
				'code'         => 'pickup.pickup',
				'title'        => $this->language->get('text_description'),
				'cost'         => 0.00,
				'tax_class_id' => 0,
				'text'         => $this->currency->format(0.00, $this->session->data['currency']),
				'content'	   => $content
			);

			$method_data = array(
				'code'       => 'pickup',
				'title'      => $this->language->get('text_title'),
				'quote'      => $quote_data,
				'sort_order' => $this->config->get('shipping_pickup_sort_order'),
				'error'      => false
			);
		}

		return $method_data;
	}
}