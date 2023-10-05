<?php
class ModelExtensionThemeTechnicsBlog extends Model {
	public function addBlog($data) { 
		if($data['date_added']){
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog SET sort_order = 0, bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = '" . $this->db->escape($data['date_added']) . "'");
		}else{
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog SET sort_order = 0, bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = NOW()");
		}
		$blog_id = $this->db->getLastId();

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "technics_blog SET image = '" . $this->db->escape($data['image']) . "' WHERE blog_id = '" . (int)$blog_id . "'");
		}

		foreach ($data['blog_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_description SET blog_id = '" . (int)$blog_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
			if($value['tag']){
				foreach (explode(',',$value['tag']) as $tag) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_tag SET blog_id = '" . (int)$blog_id . "', language_id = '" . (int)$language_id . "', tag = '" .  $this->db->escape(trim($tag)) . "'");
				}			
			}
		}

		if (isset($data['blog_store'])) {
			foreach ($data['blog_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_to_store SET blog_id = '" . (int)$blog_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		if (isset($data['blog_related'])) {
			foreach ($data['blog_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related WHERE blog_id = '" . (int)$blog_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_related SET blog_id = '" . (int)$blog_id . "', related_id = '" . (int)$related_id . "'");
			}
		}

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related_prod WHERE blog_id = '" . (int)$blog_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_related_prod SET blog_id = '" . (int)$blog_id . "', related_id = '" . (int)$related_id . "'");
			}
		}

		if (isset($data['blog_layout'])) {
			foreach ($data['blog_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_to_layout SET blog_id = '" . (int)$blog_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		// SEO URL
		if (isset($data['blog_seo_url'])) {
			foreach ($data['blog_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'blog_id=" . (int)$blog_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		if(isset($data['main_category_id']) && $data['main_category_id'] > 0) {
			$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_category WHERE blog_id = '" . (int)$blog_id . "' AND category_id = '" . (int)$data['main_category_id'] . "'");
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_to_category SET blog_id = '" . (int)$blog_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
		}

		$this->cache->delete('blog');

		return $blog_id;
	}

	public function editBlog($blog_id, $data) { 
		$this->db->query("UPDATE " . DB_PREFIX . "technics_blog SET sort_order = 0, bottom = '" . (isset($data['bottom']) ? (int)$data['bottom'] : 0) . "', status = '" . (int)$data['status'] . "', date_added = '" . $this->db->escape($data['date_added']) . "' WHERE blog_id = '" . (int)$blog_id . "'");

		if (isset($data['image'])) {
			$this->db->query("UPDATE " . DB_PREFIX . "technics_blog SET image = '" . $this->db->escape($data['image']) . "' WHERE blog_id = '" . (int)$blog_id . "'");
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_description WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_tag WHERE blog_id = '" . (int)$blog_id . "'");

		foreach ($data['blog_description'] as $language_id => $value) {
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_description SET blog_id = '" . (int)$blog_id . "', language_id = '" . (int)$language_id . "', title = '" . $this->db->escape($value['title']) . "', description = '" . $this->db->escape($value['description']) . "', meta_title = '" . $this->db->escape($value['meta_title']) . "', meta_h1 = '" . $this->db->escape($value['meta_h1']) . "', meta_description = '" . $this->db->escape($value['meta_description']) . "', meta_keyword = '" . $this->db->escape($value['meta_keyword']) . "'");
			if($value['tag']){
				foreach (explode(',',$value['tag']) as $tag) {
					$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_tag SET blog_id = '" . (int)$blog_id . "', language_id = '" . (int)$language_id . "', tag = '" .  $this->db->escape(trim($tag)) . "'");
				}			
			}
		}


		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_store WHERE blog_id = '" . (int)$blog_id . "'");

		if (isset($data['blog_store'])) {
			foreach ($data['blog_store'] as $store_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_to_store SET blog_id = '" . (int)$blog_id . "', store_id = '" . (int)$store_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_layout WHERE blog_id = '" . (int)$blog_id . "'");

		if (isset($data['blog_layout'])) {
			foreach ($data['blog_layout'] as $store_id => $layout_id) {
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_to_layout SET blog_id = '" . (int)$blog_id . "', store_id = '" . (int)$store_id . "', layout_id = '" . (int)$layout_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related WHERE blog_id = '" . (int)$blog_id . "'");


		if (isset($data['blog_related'])) {
			foreach ($data['blog_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related WHERE blog_id = '" . (int)$blog_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_related SET blog_id = '" . (int)$blog_id . "', related_id = '" . (int)$related_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related_prod WHERE blog_id = '" . (int)$blog_id . "'");

		if (isset($data['product_related'])) {
			foreach ($data['product_related'] as $related_id) {
				$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related_prod WHERE blog_id = '" . (int)$blog_id . "' AND related_id = '" . (int)$related_id . "'");
				$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_related_prod SET blog_id = '" . (int)$blog_id . "', related_id = '" . (int)$related_id . "'");
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_id=" . (int)$blog_id . "'");

		// SEO URL
		if (isset($data['blog_seo_url'])) {
			foreach ($data['blog_seo_url'] as $store_id => $language) {
				foreach ($language as $language_id => $keyword) {
					if (!empty($keyword)) {
						$this->db->query("INSERT INTO " . DB_PREFIX . "seo_url SET store_id = '" . (int)$store_id . "', language_id = '" . (int)$language_id . "', query = 'blog_id=" . (int)$blog_id . "', keyword = '" . $this->db->escape($keyword) . "'");
					}
				}
			}
		}

		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_category WHERE blog_id = '" . (int)$blog_id . "'");

		if(isset($data['main_category_id']) && $data['main_category_id'] > 0) {			
			$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_to_category SET blog_id = '" . (int)$blog_id . "', category_id = '" . (int)$data['main_category_id'] . "', main_category = 1");
		}

		$this->cache->delete('blog');
	}

	public function deleteBlog($blog_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_description WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_store WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_category WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_to_layout WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_id=" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_tag WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related WHERE related_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_related_prod WHERE blog_id = '" . (int)$blog_id . "'");
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_comment WHERE blog_id = '" . (int)$blog_id . "'");

		$this->cache->delete('blog');
	}

	public function getBlog($blog_id) {
		$query = $this->db->query("SELECT DISTINCT *, (SELECT keyword FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_id=" . (int)$blog_id . "' LIMIT 1) AS keyword FROM " . DB_PREFIX . "technics_blog  i LEFT JOIN " . DB_PREFIX . "technics_blog_description id ON (i.blog_id = id.blog_id) WHERE i.blog_id = '" . (int)$blog_id . "'");

		return $query->row;
	}

	public function getBlogTag($blog_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_tag  WHERE blog_id = '" . (int)$blog_id . "'");

		return $query->rows;
	}

	public function getBlogSeoUrls($blog_id) {
		$blog_seo_url_data = array();
		
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "seo_url WHERE query = 'blog_id=" . (int)$blog_id . "'");

		foreach ($query->rows as $result) {
			$blog_seo_url_data[$result['store_id']][$result['language_id']] = $result['keyword'];
		}

		return $blog_seo_url_data;
	}

	public function getBlogs($data = array()) {
		
		$this->createTable();
		
		if ($data) {
			$sql = "SELECT * FROM " . DB_PREFIX . "technics_blog i LEFT JOIN " . DB_PREFIX . "technics_blog_description id ON (i.blog_id = id.blog_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "'";

			if (!empty($data['filter_name'])) {
				$sql .= " AND id.title LIKE '" . $this->db->escape($data['filter_name']) . "%'";
			}

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
			$blog_data = $this->cache->get('blog.' . (int)$this->config->get('config_language_id'));

			if (!$blog_data) {
				$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog i LEFT JOIN " . DB_PREFIX . "technics_blog_description id ON (i.blog_id = id.blog_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY id.title");

				$blog_data = $query->rows;

				$this->cache->set('blog.' . (int)$this->config->get('config_language_id'), $blog_data);
			}

			return $blog_data;
		}
	}

	public function getBlogDescriptions($blog_id) {
		$blog_description_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_description WHERE blog_id = '" . (int)$blog_id . "'");

		foreach ($query->rows as $result) {

			$tag = '';
			$temp = array();
			$query1 = $this->db->query("SELECT tag FROM " . DB_PREFIX . "technics_blog_tag WHERE blog_id = '" . (int)$blog_id . "' AND language_id = '" . (int)$result['language_id'] . "'");

			if($query1->num_rows){
				foreach ($query1->rows as  $value) {
					$temp[] = $value['tag'];
				}
				$tag = implode(',', $temp);
			}

			$blog_description_data[$result['language_id']] = array(
				'title'            => $result['title'],
				'description'      => $result['description'],
				'meta_title'       => $result['meta_title'],
				'meta_h1'          => $result['meta_h1'],
				'meta_description' => $result['meta_description'],
				'meta_keyword'     => $result['meta_keyword'],
				'tag'   		  => $tag
			);
		}

		return $blog_description_data;
	}

	public function getBlogStores($blog_id) {
		$blog_store_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_to_store WHERE blog_id = '" . (int)$blog_id . "'");

		foreach ($query->rows as $result) {
			$blog_store_data[] = $result['store_id'];
		}

		return $blog_store_data;
	}

	public function getBlogLayouts($blog_id) {
		$blog_layout_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_to_layout WHERE blog_id = '" . (int)$blog_id . "'");

		foreach ($query->rows as $result) {
			$blog_layout_data[$result['store_id']] = $result['layout_id'];
		}

		return $blog_layout_data;
	}

	public function getTotalBlogs() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog");

		return $query->row['total'];
	}

	public function getTotalBlogsByLayoutId($layout_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog_to_layout WHERE layout_id = '" . (int)$layout_id . "'");

		return $query->row['total'];
	}

	public function getBlogRelated($blog_id) {
		$blog_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_related WHERE blog_id = '" . (int)$blog_id . "'");

		foreach ($query->rows as $result) {
			$blog_related_data[] = $result['related_id'];
		}

		return $blog_related_data;
	}

	public function getBlogRelatedProds($blog_id) {
		$product_related_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_related_prod WHERE blog_id = '" . (int)$blog_id . "'");

		foreach ($query->rows as $result) {
			$product_related_data[] = $result['related_id'];
		}

		return $product_related_data;
	}

	public function getBlogMainCategoryId($blog_id) {
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "technics_blog_to_category WHERE blog_id = '" . (int)$blog_id . "' AND main_category = '1' LIMIT 1");

		return ($query->num_rows ? (int)$query->row['category_id'] : 0);
	}

	public function getCommentNewCount() {
		
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_blog_comment (
			  `comment_id` int(11) NOT NULL AUTO_INCREMENT,
			  `blog_id` int(11) NOT NULL,
			  `customer_id` int(11) NOT NULL,
			  `author` varchar(64) NOT NULL,
			  `text` text NOT NULL,
			  `rating` int(1) NOT NULL,
			  `status` tinyint(1) NOT NULL DEFAULT '0',
			  `date_added` datetime NOT NULL,
			  `date_modified` datetime NOT NULL,
			  PRIMARY KEY (`comment_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");			

		$query = $this->db->query("SELECT COUNT(status) as total FROM " . DB_PREFIX . "technics_blog_comment WHERE status = '0'");
		
		return $query->row;
	}
	
	public function createTable() {
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_blog (
			  `blog_id` int(11) NOT NULL AUTO_INCREMENT,
			  `bottom` int(1) NOT NULL DEFAULT '0',
			  `sort_order` int(3) NOT NULL DEFAULT '0',
			  `status` tinyint(1) NOT NULL DEFAULT '1',
			  `date_added` date NOT NULL,
			  PRIMARY KEY (`blog_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_blog_description (
			  `blog_id` int(11) NOT NULL,
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
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_blog_to_layout (
			  `blog_id` int(11) NOT NULL,
			  `store_id` int(11) NOT NULL,
			  `layout_id` int(11) NOT NULL,
			  PRIMARY KEY (`blog_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_blog_to_store (
			  `blog_id` int(11) NOT NULL,
			  `store_id` int(11) NOT NULL,
			  PRIMARY KEY (`blog_id`)
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");	
		$this->db->query("
			CREATE TABLE IF NOT EXISTS " . DB_PREFIX . "technics_blog_related (
			  `news_id` int(11) NOT NULL,
			  `related_id` int(11) NOT NULL
			) ENGINE=MyISAM DEFAULT CHARSET=utf8;
		");	
	}
	
}
