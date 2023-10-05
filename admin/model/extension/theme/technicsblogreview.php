<?php
class ModelExtensionThemeTechnicsblogReview extends Model {
	public function addReview($data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_comment SET author = '" . $this->db->escape($data['author']) . "', blog_id = '" . (int)$data['blog_id'] . "', text = '" . $this->db->escape(strip_tags($data['text'])) . "', status = '" . (int)$data['status'] . "', date_added = '" . $this->db->escape($data['date_added']) . "'");

		$comment_id = $this->db->getLastId();

		$this->cache->delete('blog');

		return $comment_id;
	}

	public function editReview($comment_id, $data) {
		$this->db->query("UPDATE " . DB_PREFIX . "technics_blog_comment SET author = '" . $this->db->escape($data['author']) . "', blog_id = '" . (int)$data['blog_id'] . "', text = '" . $this->db->escape(strip_tags($data['text'])) . "', status = '" . (int)$data['status'] . "', date_added = '" . $this->db->escape($data['date_added']) . "', date_modified = NOW() WHERE comment_id = '" . (int)$comment_id . "'");

		$this->cache->delete('blog');
	}

	public function deleteReview($comment_id) {
		$this->db->query("DELETE FROM " . DB_PREFIX . "technics_blog_comment WHERE comment_id = '" . (int)$comment_id . "'");

		$this->cache->delete('blog');
	}

	public function getReview($comment_id) {
		$query = $this->db->query("SELECT DISTINCT *, (SELECT pd.title FROM " . DB_PREFIX . "technics_blog_description pd WHERE pd.blog_id = r.blog_id AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "') AS blog FROM " . DB_PREFIX . "technics_blog_comment r WHERE r.comment_id = '" . (int)$comment_id . "'");

		return $query->row;
	}

	public function getReviews($data = array()) {
		$sql = "SELECT r.comment_id, pd.title, r.author, r.status, r.date_added FROM " . DB_PREFIX . "technics_blog_comment r LEFT JOIN " . DB_PREFIX . "technics_blog_description pd ON (r.blog_id = pd.blog_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_blog'])) {
			$sql .= " AND pd.title LIKE '" . $this->db->escape($data['filter_blog']) . "%'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND r.author LIKE '" . $this->db->escape($data['filter_author']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		$sort_data = array(
			'pd.title',
			'r.author',
			'r.status',
			'r.date_added'
		);

		if (isset($data['sort']) && in_array($data['sort'], $sort_data)) {
			$sql .= " ORDER BY " . $data['sort'];
		} else {
			$sql .= " ORDER BY r.date_added";
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

	public function getTotalReviews($data = array()) {
		
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
		
		$sql = "SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog_comment r LEFT JOIN " . DB_PREFIX . "technics_blog_description pd ON (r.blog_id = pd.blog_id) WHERE pd.language_id = '" . (int)$this->config->get('config_language_id') . "'";

		if (!empty($data['filter_blog'])) {
			$sql .= " AND pd.title LIKE '" . $this->db->escape($data['filter_blog']) . "%'";
		}

		if (!empty($data['filter_author'])) {
			$sql .= " AND r.author LIKE '" . $this->db->escape($data['filter_author']) . "%'";
		}

		if (isset($data['filter_status']) && !is_null($data['filter_status'])) {
			$sql .= " AND r.status = '" . (int)$data['filter_status'] . "'";
		}

		if (!empty($data['filter_date_added'])) {
			$sql .= " AND DATE(r.date_added) = DATE('" . $this->db->escape($data['filter_date_added']) . "')";
		}

		$query = $this->db->query($sql);

		return $query->row['total'];
	}

	public function getTotalReviewsAwaitingApproval() {
		$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog_commentWHERE status = '0'");

		return $query->row['total'];
	}
}