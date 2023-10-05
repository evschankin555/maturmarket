<?php
class ModelExtensionThemeTechnicsSets extends Model {
	public function addSet($data) {

		$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set SET sort_order = 0, mode = '" . (isset($data['mode']) ? (int)$data['mode'] : 0) . "', discount = '" . (isset($data['discount']) ? (int)$data['discount'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = NOW()");

		$set_id = $this->db->getLastId();

		foreach ($data['set_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set_description SET set_id = '" . (int)$set_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "'");
		}

		if (isset($data['set_store'])) {
			foreach ($data['set_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set_to_store SET set_id = '" . (int)$set_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['set_city'])) {
			foreach ($data['set_city'] as $city_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set_to_city SET set_id = '" . (int)$set_id . "', city_id = '" . (int)$city_id . "'");
			}
		}

		if (isset($data['product'])) {
			foreach ($data['product'] as $key => $rowProducts) { 
				if(!isset($rowProducts['items'])){ continue; }
				foreach ($rowProducts['items'] as $product) {

					$this->db->query("INSERT INTO " . DB_PREFIX . "technics_product_to_set SET set_id = '" . (int)$set_id . "', row_id = '" . (int)$key . "', product_id = '" . (int)$product['id'] . "', quantity = '" . (int)$rowProducts['qty'] . "', sort_order = " . (int)$rowProducts['sort_order'] . "");
				}
			}

		}


		$this->cache->delete('set');

		return $set_id;
	}

	public function editSet($set_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "technics_set SET  mode = '" . (isset($data['mode']) ? (int)$data['mode'] : 0) . "', discount = '" . (isset($data['discount']) ? (int)$data['discount'] : 0) . "', status = '" . (int)$data['status'] . "' WHERE set_id = '" . (int)$set_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_description WHERE set_id = '" . (int)$set_id . "'");

		foreach ($data['set_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set_description SET set_id = '" . (int)$set_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_to_store WHERE set_id = '" . (int)$set_id . "'");

		if (isset($data['set_store'])) {
			foreach ($data['set_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set_to_store SET set_id = '" . (int)$set_id . "', store_id = '" . (int)$store_id . "'");
			}
		}


		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_to_city WHERE set_id = '" . (int)$set_id . "'");

		if (isset($data['set_city'])) {
			foreach ($data['set_city'] as $city_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_set_to_city SET set_id = '" . (int)$set_id . "', city_id = '" . (int)$city_id . "'");
			}
		}


		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_product_to_set WHERE set_id = '" . (int)$set_id . "'");


		if (isset($data['product'])) {
			foreach ($data['product'] as $key => $rowProducts) { 
				if(!isset($rowProducts['items'])){ continue; }
				foreach ($rowProducts['items'] as $product) {

					$this->db->query("INSERT INTO " . DB_PREFIX . "technics_product_to_set SET set_id = '" . (int)$set_id . "', row_id = '" . (int)$key . "', product_id = '" . (int)$product['id'] . "', quantity = '" . (int)$rowProducts['qty'] . "', sort_order = " . (int)$rowProducts['sort_order'] . "");
				}
			}

		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'set_id=" . (int)$set_id . "'");


		$this->cache->delete('set');
	}

	public function deleteSet($set_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set WHERE set_id = '" . (int)$set_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_description WHERE set_id = '" . (int)$set_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_to_store WHERE set_id = '" . (int)$set_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_to_city WHERE set_id = '" . (int)$set_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_set_to_layout WHERE set_id = '" . (int)$set_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_product_to_set WHERE set_id = '" . (int)$set_id . "'");

		$this->cache->delete('set');
	}

	public function getSet($set_id) {

		$sql = "SELECT * FROM " . DB_PREFIX . "technics_set i LEFT JOIN " . DB_PREFIX . "technics_set_description id ON (i.set_id = id.set_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i.set_id = '" . (int)$set_id . "'";

		$query = $this->db->query($sql);

		return $query->row;

	}

	public function getSetProduct($set_id) {
		$products = array();

		$sql = "SELECT *  FROM " . DB_PREFIX . "technics_product_to_set WHERE set_id = '" . (int)$set_id . "'  ORDER BY row_id,set_product_id ASC";

		$query = $this->db->query($sql);

		foreach ($query->rows as $key => $product) {
			$products[$product['row_id']][] = array(
				'id' 	=> $product['product_id'],
				'qty' 	=> $product['quantity'],
				'sort_order' 	=> $product['sort_order']
			);
		}

		return $products;

	}

	public function getSets($data = array()) {
		
		$this->createTable();
		
		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "technics_set i LEFT JOIN " . DB_PREFIX . "technics_set_description id ON (i.set_id = id.set_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

			$sort_data = array(
				'id.title',
				'i.sort_order',
				'i.date_added'
			);

			if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
				$sql .= " ORDER BY " . $data['sort'];
			} else {
				$sql .= " ORDER BY i.date_added";
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
			$set_data = $this->cache->get('technicsset.' . (int)$this->config->get('config_language_id'));

			if (!$set_data) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_set i LEFT JOIN " . DB_PREFIX . "technics_set_description id ON (i.set_id = id.set_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY id.title");

				$set_data = $query->rows;

				$this->cache->set('technicsset.' . (int)$this->config->get('config_language_id'), $set_data);
			}

			return $set_data;
		}
	}

	public function getSetDescriptions($set_id) {
		$set_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_set_description WHERE set_id = '" . (int)$set_id . "'");

		foreach ($query->rows as $result) {
			$set_description_data[$result['language_id']] = array(
				'title'            => $result['title'],
			);
		}

		return $set_description_data;
	}

	public function getSetStores($set_id) {
		$set_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_set_to_store WHERE set_id = '" . (int)$set_id . "'");

		foreach ($query->rows as $result) {
			$set_store_data[] = $result['store_id'];
		}

		return $set_store_data;
	}

	public function getSetCities($set_id) {
		$set_citi_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_set_to_city WHERE set_id = '" . (int)$set_id . "'");

		foreach ($query->rows as $result) {
			$set_citi_data[] = $result['city_id'];
		}

		return $set_citi_data;
	}

	public function getTotalSets() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_set");

		return $query->row['total'];
	}

	public function getTotalNewssByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_news_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

		return $query->row['total'];
	}

	public function getProductRelated($news_id) {
		$product_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news_related WHERE news_id = '" . (int)$news_id . "'");

		foreach ($query->rows as $result) {
			$product_related_data[] = $result['related_id'];
		}

		return $product_related_data;
	}
	
	public function createTable() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_set (
			  `set_id` int(11) NOT NULL AUTO_INCREMENT,
			  `mode` int(1) NOT NULL DEFAULT '0',
			  `discount` int(11) NOT NULL DEFAULT '0',
			  `sort_order` int(3) NOT NULL DEFAULT '0',
			  `status` tinyint(1) NOT NULL DEFAULT '1',
			  `date_added` date NOT NULL,
			  PRIMARY KEY (`set_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_set_description (
			  `set_id` int(11) NOT NULL,
			  `language_id` int(11) NOT NULL,
			  `title` varchar(200) NOT NULL,
			  `description` text NOT NULL,
			  `meta_title` varchar(255) NOT NULL,
			  `meta_h1` varchar(255) NOT NULL,
			  `meta_description` varchar(255) NOT NULL,
			  `meta_keyword` varchar(255) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_product_to_set (
			  `set_product_id` int(11) NOT NULL AUTO_INCREMENT,
			  `set_id` int(11) NOT NULL,
			  `row_id` int(11) NOT NULL,
			  `product_id` int(11) NOT NULL,
			  `quantity` int(11) NOT NULL DEFAULT '0',
			  `sort_order` int(3) NOT NULL DEFAULT '0',
			  PRIMARY KEY (`set_product_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_set_to_layout (
			  `set_id` int(11) NOT NULL,
			  `store_id` int(11) NOT NULL,
			  `layout_id` int(11) NOT NULL,
			  PRIMARY KEY (`set_id`,`store_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_set_to_store (
			  `set_id` int(11) NOT NULL,
			  `store_id` int(11) NOT NULL,
			  PRIMARY KEY (`set_id`,`store_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");	
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_news_related (
			  `news_id` int(11) NOT NULL,
			  `related_id` int(11) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");	
	}
	
}
