<?php
class ModelExtensionModuleTechnicscatblog extends Model {
	public function getBlogCategory($blogcategory_id) {
		$query = $this->db->query("SELECT DISTINCT * FROM " . DB_PREFIX . "technicscat_blog c LEFT JOIN " . DB_PREFIX . "technicscat_blog_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "technicscat_blog_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.category_id = '" . (int)$blogcategory_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row;
	}

	public function getBlogCategories($parent_id = 0) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technicscat_blog c LEFT JOIN " . DB_PREFIX . "technicscat_blog_description cd ON (c.category_id = cd.category_id) LEFT JOIN " . DB_PREFIX . "technicscat_blog_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND cd.language_id = '" . (int)$this->config->get('config_language_id') . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "'  AND c.status = '1' ORDER BY c.sort_order, LCASE(cd.name)");

		return $query->rows;
	}

	public function getBlogCategoryFilters($blogcategory_id) {
		$implode = array();

		$query = $this->db->query("SELECT filter_id FROM " . DB_PREFIX . "technicscat_blog_filter WHERE category_id = '" . (int)$blogcategory_id . "'");

		foreach ($query->rows as $result) {
			$implode[] = (int)$result['filter_id'];
		}

		$filter_group_data = array();

		if ($implode) {
			$filter_group_query = $this->db->query("SELECT DISTINCT f.filter_group_id, fgd.name, fg.sort_order FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_group fg ON (f.filter_group_id = fg.filter_group_id) LEFT JOIN " . DB_PREFIX . "filter_group_description fgd ON (fg.filter_group_id = fgd.filter_group_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND fgd.language_id = '" . (int)$this->config->get('config_language_id') . "' GROUP BY f.filter_group_id ORDER BY fg.sort_order, LCASE(fgd.name)");

			foreach ($filter_group_query->rows as $filter_group) {
				$filter_data = array();

				$filter_query = $this->db->query("SELECT DISTINCT f.filter_id, fd.name FROM " . DB_PREFIX . "filter f LEFT JOIN " . DB_PREFIX . "filter_description fd ON (f.filter_id = fd.filter_id) WHERE f.filter_id IN (" . implode(',', $implode) . ") AND f.filter_group_id = '" . (int)$filter_group['filter_group_id'] . "' AND fd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY f.sort_order, LCASE(fd.name)");

				foreach ($filter_query->rows as $filter) {
					$filter_data[] = array(
						'filter_id' => $filter['filter_id'],
						'name'      => $filter['name']
					);
				}

				if ($filter_data) {
					$filter_group_data[] = array(
						'filter_group_id' => $filter_group['filter_group_id'],
						'name'            => $filter_group['name'],
						'filter'          => $filter_data
					);
				}
			}
		}

		return $filter_group_data;
	}

	public function getBlogCategoryLayoutId($blogcategory_id) {
		$query = $this->db->query("SELECT * FROM " . DB_PREFIX . "technicscat_blog_to_layout WHERE category_id = '" . (int)$blogcategory_id . "' AND store_id = '" . (int)$this->config->get('config_store_id') . "'");

		if ($query->num_rows) {
			return $query->row['layout_id'];
		} else {
			return 0;
		}
	}

	
	public function getBlogsTotalByCategoryId($category_id = 0) {
		if ($category_id) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog b LEFT JOIN " . DB_PREFIX . "technics_blog_to_store b2s ON (b.blog_id = b2s.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_to_category b2c ON (b.blog_id = b2c.blog_id) WHERE b2c.category_id = '" . (int)$category_id . "' AND b2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND b.status = '1'");
		}else{
			$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog  WHERE status = '1'");
		}

		return $query->row['total'];
	}
	
	public function getBlogTotalCategoriesByCategoryId($parent_id = 0) {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technicscat_blog c LEFT JOIN " . DB_PREFIX . "technicscat_blog_to_store c2s ON (c.category_id = c2s.category_id) WHERE c.parent_id = '" . (int)$parent_id . "' AND c2s.store_id = '" . (int)$this->config->get('config_store_id') . "' AND c.status = '1'");

		return $query->row['total'];
	}

	public function getPathByBlog($blog_id) {
		$blog_id = (int)$blog_id;
		if ($blog_id < 1) return false;

		static $path = null;
		$query = $this->db->query("SELECT category_id FROM " . DB_PREFIX . "technics_blog_to_category WHERE blog_id = '" . (int)$blog_id . "' ORDER BY main_category DESC LIMIT 1");

		$path[$blog_id] = $this->getPathByCategory($query->num_rows ? (int)$query->row['category_id'] : 0);

		return $path[$blog_id];
	}

	private function getPathByCategory($category_id) {
		$category_id = (int)$category_id;
		if ($category_id < 1) return false;

		static $path = null;

			$max_level = 10;

			$sql = "SELECT CONCAT_WS('_'";
			for ($i = $max_level-1; $i >= 0; --$i) {
				$sql .= ",t$i.category_id";
			}
			$sql .= ") AS path FROM " . DB_PREFIX . "technicscat_blog t0";
			for ($i = 1; $i < $max_level; ++$i) {
				$sql .= " LEFT JOIN " . DB_PREFIX . "technicscat_blog t$i ON (t$i.category_id = t" . ($i-1) . ".parent_id)";
			}
			$sql .= " WHERE t0.category_id = '" . (int)$category_id . "'";

			$query = $this->db->query($sql);

			$path[$category_id] = $query->num_rows ? $query->row['path'] : false;

		return $path[$category_id];
	}
	
	public function isModuleSet() {
		$isSet = false;
		$query = $this->db->query("SHOW TABLES LIKE  '" . DB_PREFIX . "technicscat_blog'");
		if($query->num_rows){
			$isSet = true;
		}

		return $isSet;
	}

}