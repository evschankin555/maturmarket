<?php

class ModelExtensionPaymentDolyame extends Model
{
	private $prefix = '';
	public function __construct($registry)
	{
		$this->prefix = version_compare(VERSION, '3.0.0.0') == -1 ? '' : 'payment_';
		parent::__construct($registry);
	}

	public function getMethod($address, $total)
	{
		$this->load->language('extension/payment/dolyame');

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "zone_to_geo_zone WHERE geo_zone_id = '" . (int) $this->config->get($this->prefix.'dolyame_geo_zone_id') . "' AND country_id = '" . (int) $address['country_id'] . "' AND (zone_id = '" . (int) $address['zone_id'] . "' OR zone_id = '0')");

		if ($this->config->get($this->prefix.'dolyame_total') > 0 && $this->config->get($this->prefix.'dolyame_total') > $total) {
			$status = false;
		} elseif (!$this->config->get($this->prefix.'dolyame_geo_zone_id')) {
			$status = true;
		} elseif ($query->num_rows) {
			$status = true;
		} else {
			$status = false;
		}

		$method_data = array();

		if ($status) {
			$method_data = array(
				'code'       => 'dolyame',
				'title'      => $this->config->get($this->prefix.'dolyame_paymentname'),
				'terms'      => '',
				'sort_order' => $this->config->get($this->prefix.'dolyame_sort_order'),
				'group_text' => $this->language->get('group_text')
			);
		}

		return $method_data;
	}
}
