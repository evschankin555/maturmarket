<?php
class ModelExtensionThemeTechnicsNews extends Model {
	public function addNews($data) {
		if($data['date_added']){
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news SET sort_order = 0, bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = '" . $this->db->escape($data['date_added']) . "'");
		}else{
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news SET sort_order = 0, bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = NOW()");
		}
		$news_id = $this->db->getLastId();

		foreach ($data['news_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_description SET news_id = '" . (int)$news_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		if (isset($data['news_store'])) {
			foreach ($data['news_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_to_store SET news_id = '" . (int)$news_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_related WHERE news_id = '" . (int)$news_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_related SET news_id = '" . (int)$news_id . "', related_id = '" . (int)$related_id . "'");
			}
		}

		if (isset($data['news_layout'])) {
			foreach ($data['news_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_to_layout SET news_id = '" . (int)$news_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		// SEO URL
		if (isset($data['news_seo_url'])) {
			foreach ($data['news_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'news_id=" . (int)$news_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		$this->cache->delete('news');

		return $news_id;
	}

	public function editNews($news_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "technics_news SET sort_order = 0, bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = '" . $this->db->escape($data['date_added']) . "' WHERE news_id = '" . (int)$news_id . "'");

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_description WHERE news_id = '" . (int)$news_id . "'");

		foreach ($data['news_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_description SET news_id = '" . (int)$news_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_to_store WHERE news_id = '" . (int)$news_id . "'");

		if (isset($data['news_store'])) {
			foreach ($data['news_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_to_store SET news_id = '" . (int)$news_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_to_layout WHERE news_id = '" . (int)$news_id . "'");

		if (isset($data['news_layout'])) {
			foreach ($data['news_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_to_layout SET news_id = '" . (int)$news_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_related WHERE news_id = '" . (int)$news_id . "'");


		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_related WHERE news_id = '" . (int)$news_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_news_related SET news_id = '" . (int)$news_id . "', related_id = '" . (int)$related_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'news_id=" . (int)$news_id . "'");

		if (isset($data['news_seo_url'])) {
			foreach ($data['news_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (trim($keyword)) {
						$this->db->query("INSERT INTO `" . DB_PREFIX . "seo_url` SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'news_id=" . (int)$news_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		$this->cache->delete('news');
	}

	public function deleteNews($news_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news WHERE news_id = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_description WHERE news_id = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_to_store WHERE news_id = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_to_layout WHERE news_id = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'news_id=" . (int)$news_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_related WHERE news_id = '" . (int)$news_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_news_related WHERE related_id = '" . (int)$news_id . "'");

		$this->cache->delete('news');
	}

	public function getNews($news_id) {
		$query = $this->db->query("SELECT DISTINCT *, (SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'news_id=" . (int)$news_id . "' LIMIT 1) AS keyword FROM " . DB_PREFIX . "technics_news WHERE news_id = '" . (int)$news_id . "'");

		return $query->row;
	}

	public function getNewsSeoUrls($news_id) {
		$news_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'news_id=" . (int)$news_id . "'");

		foreach ($query->rows as $result) {
			$news_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $news_seo_url_data;
	}

	public function getNewss($data = array()) {
		
		$this->createTable();
		
		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "technics_news i LEFT JOIN " . DB_PREFIX . "technics_news_description id ON (i.news_id = id.news_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

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
			$news_data = $this->cache->get('news.' . (int)$this->config->get('config_language_id'));

			if (!$news_data) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news i LEFT JOIN " . DB_PREFIX . "technics_news_description id ON (i.news_id = id.news_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY id.title");

				$news_data = $query->rows;

				$this->cache->set('news.' . (int)$this->config->get('config_language_id'), $news_data);
			}

			return $news_data;
		}
	}

	public function getNewsDescriptions($news_id) {
		$news_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news_description WHERE news_id = '" . (int)$news_id . "'");

		foreach ($query->rows as $result) {
			$news_description_data[$result['language_id']] = array(
				'title'            => $result['title'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_h1'          => $result['meta_h1'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword']
			);
		}

		return $news_description_data;
	}

	public function getNewsStores($news_id) {
		$news_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news_to_store WHERE news_id = '" . (int)$news_id . "'");

		foreach ($query->rows as $result) {
			$news_store_data[] = $result['store_id'];
		}

		return $news_store_data;
	}

	public function getNewsLayouts($news_id) {
		$news_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_news_to_layout WHERE news_id = '" . (int)$news_id . "'");

		foreach ($query->rows as $result) {
			$news_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $news_layout_data;
	}

	public function getTotalNewss() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_news");

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
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_news (
			  `news_id` int(11) NOT NULL AUTO_INCREMENT,
			  `bottom` int(1) NOT NULL DEFAULT '0',
			  `sort_order` int(3) NOT NULL DEFAULT '0',
			  `status` tinyint(1) NOT NULL DEFAULT '1',
			  `date_added` date NOT NULL,
			  PRIMARY KEY (`news_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_news_description (
			  `news_id` int(11) NOT NULL,
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
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_news_to_layout (
			  `news_id` int(11) NOT NULL,
			  `store_id` int(11) NOT NULL,
			  `layout_id` int(11) NOT NULL,
			  PRIMARY KEY (`news_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_news_to_store (
			  `news_id` int(11) NOT NULL,
			  `store_id` int(11) NOT NULL,
			  PRIMARY KEY (`news_id`)
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
