<?php
class ModelExtensionModuleTechnicsblog extends Model {

	public function updateViewed($blog_id) {
		$this->db->query("UPDATE " . DB_PREFIX . "technics_blog SET viewed = (viewed + 1) WHERE blog_id = '" . (int)$blog_id . "'");
	}

	public function getBlog($blog_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "technics_blog i LEFT JOIN " . DB_PREFIX . "technics_blog_description id ON (i.blog_id = id.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_to_store i2s ON (i.blog_id = i2s.blog_id) WHERE i.blog_id = '" . (int)$blog_id . "' AND id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1'");

		return $query->row;
	}

	public function getBlogs($data) { 

		$sql = "SELECT * ";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "technicscat_blog_path cp LEFT JOIN " . DB_PREFIX . "technics_blog_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "technics_blog_to_category p2c";
			}

			$sql .= " LEFT JOIN " . DB_PREFIX . "technics_blog i ON (p2c.blog_id = i.blog_id)";

		} else {
			$sql .= " FROM " . DB_PREFIX . "technics_blog i";
		}

		$sql .= "  LEFT JOIN " . DB_PREFIX . "technics_blog_description id ON (i.blog_id = id.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_tag bt ON (i.blog_id = bt.blog_id)  LEFT JOIN " . DB_PREFIX . "technics_blog_to_store i2s ON (i.blog_id = i2s.blog_id) WHERE id.language_id = '" . (int)$this->config->get('config_language_id') . "' AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' ";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

		}

		if (!empty($data['filtertag'])) {
				$sql .= " AND bt.tag = '" . $this->db->escape($data['filtertag']) . "'";
		}

		if (!empty($data['filter_name'])) {
				$sql .= " AND id.description LIKE '%" . $this->db->escape($data['filter_name']) . "%'";
		}

		$sql .= "  GROUP BY i.blog_id ORDER BY i.date_added DESC LIMIT ".(int)$data['start'].",".(int)$data['limit']."";

		$query = $this->db->query($sql);

		return $query->rows;
	}
	public function getBlogsTotal() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog  WHERE status = '1'");

		return $query->row['total'];
	}

	public function getBlogLayoutId($blog_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_to_layout WHERE blog_id = '" . (int)$blog_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return 0;
		}
	}
	public function isModuleSet() {
		$isSet = false;
		$query = $this->db->query("SHOW TABLES LIKE  '" . DB_PREFIX . "technics_blog'");
		if($query->num_rows){
			$isSet = true;
		}

		return $isSet;
	}
	public function getBlogRelated($blog_id) {
		$product_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_related pr LEFT JOIN " . DB_PREFIX . "technics_blog p ON (pr.blog_id = p.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_to_store p2s ON (p.blog_id = p2s.blog_id) WHERE pr.blog_id = '" . (int)$blog_id . "' AND p.status = '1' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		$querys = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "technics_key'");
		if($querys->num_rows){$queryss = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_key WHERE 1");}
		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $result['related_id'];
		}
		if(!isset($queryss->row['value']) || !$queryss->row['value']){$product_data = array();}
		return $product_data;
	}

	public function getBlogRelatedProd($blog_id) {
		$product_data = array();

		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_related_prod pr LEFT JOIN " . DB_PREFIX . "technics_blog p ON (pr.blog_id = p.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_to_store p2s ON (p.blog_id = p2s.blog_id) WHERE pr.blog_id = '" . (int)$blog_id . "' AND p.status = '1' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'");
		$querys = $this->db->query("SHOW TABLES LIKE '" . DB_PREFIX . "technics_key'");
		if($querys->num_rows){$queryss = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_key WHERE 1");}
		foreach ($query->rows as $result) {
			$product_data[$result['related_id']] = $result['related_id'];
		}
		if(!isset($queryss->row['value']) || !$queryss->row['value']){$product_data = array();}
		return $product_data;
	}


	public function getTotalBlogs($data = array()) {


		$sql = "SELECT COUNT(DISTINCT p.blog_id) AS total";


		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "technicscat_blog_path cp LEFT JOIN " . DB_PREFIX . "technics_blog_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "technics_blog_to_category p2c";
			}

			$sql .= " LEFT JOIN " . DB_PREFIX . "technics_blog p ON (p2c.blog_id = p.blog_id)";

		} else {
			$sql .= " FROM " . DB_PREFIX . "technics_blog p";
		}

		$sql .= " LEFT JOIN " . DB_PREFIX . "technics_blog_description pd ON (p.blog_id = pd.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_tag bt ON (p.blog_id = bt.blog_id)  LEFT JOIN " . DB_PREFIX . "technics_blog_to_store p2s ON (p.blog_id = p2s.blog_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND p.status = '1' AND p2s.store_id = '" . (int)$this->config->get('config_store_id') . "'";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

		}

		if (!empty($data['filtertag'])) {
				$sql .= " AND bt.tag = '" . $this->db->escape($data['filtertag']) . "'";
		}

		$query = $this->db->query($sql);

		$total = 0;

		if(isset($query->row['total'])){
			$total = $query->row['total'];
		}

		return $total;
	}	
	public function getBlogsTag($data) {

		$sql = "SELECT *,COUNT(i.blog_id) as total  ";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " FROM " . DB_PREFIX . "technicscat_blog_path cp LEFT JOIN " . DB_PREFIX . "technics_blog_to_category p2c ON (cp.category_id = p2c.category_id)";
			} else {
				$sql .= " FROM " . DB_PREFIX . "technics_blog_to_category p2c";
			}

			$sql .= " LEFT JOIN " . DB_PREFIX . "technics_blog i ON (p2c.blog_id = i.blog_id)";

		} else {
			$sql .= " FROM " . DB_PREFIX . "technics_blog i";
		}

		$sql .= "   LEFT JOIN " . DB_PREFIX . "technics_blog_tag bt ON (i.blog_id = bt.blog_id)  LEFT JOIN " . DB_PREFIX . "technics_blog_to_store i2s ON (i.blog_id = i2s.blog_id) WHERE bt.language_id = '" . (int)$this->config->get('config_language_id') . "'  AND bt.tag IS NOT NULL AND i2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND i.status = '1' ";

		if (!empty($data['filter_category_id'])) {
			if (!empty($data['filter_sub_category'])) {
				$sql .= " AND cp.path_id = '" . (int)$data['filter_category_id'] . "'";
			} else {
				$sql .= " AND p2c.category_id = '" . (int)$data['filter_category_id'] . "'";
			}

		}

		$sql .= " GROUP BY bt.tag ORDER BY total  DESC LIMIT ".(int)$data['start'].",".(int)$data['limit']."";

		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getBlogTag($blog_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_tag  WHERE language_id = '" . (int)$this->config->get('config_language_id') . "'  AND  blog_id = '" . (int)$blog_id . "'");

		return $query->rows;
	}

	public function getBlogCat($blog_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technics_blog_to_category b2c  LEFT JOIN " . DB_PREFIX . "technicscat_blog_description cbd ON (b2c.category_id  = cbd.category_id ) WHERE cbd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND b2c.blog_id= '" . (int)$blog_id . "'");

		return $query->row;
	}

	public function getTotalReviewsByBlogId($blog_id) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog_comment c LEFT JOIN " . DB_PREFIX . "technics_blog b ON (c.blog_id = b.blog_id) WHERE b.blog_id = '" . (int)$blog_id . "'  AND c.status = '1'");

		return $query->row['total'];
	}

}
