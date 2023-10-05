<?php
class ModelExtensionModuleLbcomment extends Model {
	public function addComment($blog_id, $data) {
		$this->db->query("INSERT INTO " . DB_PREFIX . "technics_blog_comment SET author = '" . $this->db->escape($data['name']) . "', customer_id = '" . (int)$this->customer->getId() . "', blog_id = '" . (int)$blog_id . "', text = '" . $this->db->escape($data['text']) . "', rating = 0, date_added = NOW()");

		$comment_id = $this->db->getLastId();

		if (in_array('comment', (array)$this->config->get('config_mail_alert'))) {
			$this->load->language('mail/review');
			$this->load->model('catalog/product');
			
			$product_info = $this->model_catalog_product->getProduct($blog_id);

			$subject = sprintf($this->language->get('text_subject'), html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));

			$message  = $this->language->get('text_waiting') . "\n";
			$message .= sprintf($this->language->get('text_product'), html_entity_decode($product_info['name'], ENT_QUOTES, 'UTF-8')) . "\n";
			$message .= sprintf($this->language->get('text_reviewer'), html_entity_decode($data['name'], ENT_QUOTES, 'UTF-8')) . "\n";
			$message .= sprintf($this->language->get('text_rating'), $data['rating']) . "\n";
			$message .= $this->language->get('text_review') . "\n";
			$message .= html_entity_decode($data['text'], ENT_QUOTES, 'UTF-8') . "\n\n";

			$mail = new Mail();
			$mail->protocol = $this->config->get('config_mail_protocol');
			$mail->parameter = $this->config->get('config_mail_parameter');
			$mail->smtp_hostname = $this->config->get('config_mail_smtp_hostname');
			$mail->smtp_username = $this->config->get('config_mail_smtp_username');
			$mail->smtp_password = html_entity_decode($this->config->get('config_mail_smtp_password'), ENT_QUOTES, 'UTF-8');
			$mail->smtp_port = $this->config->get('config_mail_smtp_port');
			$mail->smtp_timeout = $this->config->get('config_mail_smtp_timeout');

			$mail->setTo($this->config->get('config_email'));
			$mail->setFrom($this->config->get('config_email'));
			$mail->setSender(html_entity_decode($this->config->get('config_name'), ENT_QUOTES, 'UTF-8'));
			$mail->setSubject($subject);
			$mail->setText($message);
			$mail->send();

			// Send to additional alert emails
			$emails = explode(',', $this->config->get('config_alert_email'));

			foreach ($emails as $email) {
				if ($email && preg_match($this->config->get('config_mail_regexp'), $email)) {
					$mail->setTo($email);
					$mail->send();
				}
			}
		}
	}

	public function getCommentsByBlogId($blog_id, $start = 0, $limit = 20) {
		if ($start < 0) {
			$start = 0;
		}

		if ($limit < 1) {
			$limit = 20;
		}

		$sql = "SELECT r.comment_id, r.author, r.rating, r.text, p.blog_id, pd.title, r.date_added FROM " . DB_PREFIX . "technics_blog_comment r LEFT JOIN " . DB_PREFIX . "technics_blog p ON (r.blog_id = p.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_description pd ON (p.blog_id = pd.blog_id) WHERE p.blog_id = '" . (int)$blog_id . "'";

		if (!$this->config->get('theme_technics_blog_rev_moder')) {
			$sql .= " AND r.status = '1' ";
		}

		$sql .= " AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "' ORDER BY r.date_added DESC LIMIT " . (int)$start . "," . (int)$limit . "";
//var_dump($sql);
		$query = $this->db->query($sql);

		return $query->rows;
	}

	public function getTotalCommentsByBlogId($blog_id) {

		if ($this->config->get('theme_technics_blog_rev_moder')) {
			$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog_comment r LEFT JOIN " . DB_PREFIX . "technics_blog p ON (r.blog_id = p.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_description pd ON (p.blog_id = pd.blog_id) WHERE p.blog_id = '" . (int)$blog_id . "'  AND p.status = '1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		}else{
			$query = $this->db->query("SELECT COUNT(*) AS total FROM " . DB_PREFIX . "technics_blog_comment r LEFT JOIN " . DB_PREFIX . "technics_blog p ON (r.blog_id = p.blog_id) LEFT JOIN " . DB_PREFIX . "technics_blog_description pd ON (p.blog_id = pd.blog_id) WHERE p.blog_id = '" . (int)$blog_id . "'  AND p.status = '1' AND r.status = '1' AND pd.language_id = '" . (int)$this->config->get('config_language_id') . "'");
		}

		return $query->row['total'];
	}
}