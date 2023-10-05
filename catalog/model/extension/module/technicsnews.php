<?php
class ModelExtensionModuleTechnicsnews extends Model {
	public function getNews($news_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "technics_news i LEFT JOIN " . DB_PREFIX . "technics_news_description id ON (i.news_id = id.news_id) LEFT JOIN " . DB_PREFIX . "technics_news_to_store i2s ON (i.news_id = i2s.news_id) WHERE i.news_id = '" . (int)$news_id . "' AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1'");

		return $query->row;
	}

	public function getNewss($data) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news i LEFT JOIN " . DB_PREFIX . "technics_news_description id ON (i.news_id = id.news_id) LEFT JOIN " . DB_PREFIX . "technics_news_to_store i2s ON (i.news_id = i2s.news_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' ORDER BY i.date_added DESC LIMIT ".(int)$data['start'].",".(int)$data['limit']."");

		return $query->rows;
	}
	public function getNewssTotal() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_news  WHERE status = '1'");

		return $query->row['total'];
	}

	public function getNewsLayoutId($news_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news_to_layout WHERE news_id = '" . (int)$news_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return 0;
		}
	}
	public function isModuleSet() {
		$isSet = false;
		$query = $this->db->query("SHOW TABLES LIKE  '" . DB_PREFIX . "technics_news'");
		if($query->num_rows){
			$isSet = true;
		}

		return $isSet;
	}
	public function getProductRelated($news_id) {
		$product_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news_related pr LEFT JOIN " . DB_PREFIX . "product p ON (pr.related_id = p.product_id) LEFT JOIN " . DB_PREFIX . "product_to_store p2s ON (p.product_id = p2s.product_id) WHERE pr.news_id = '" . (int)$news_id . "' AND p.status = '1' AND p.date_available <= NOW() AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");

		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $result['related_id'];
		}
		return $product_data;
	}

}
