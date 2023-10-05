<?php
class ControllerExtensionAnalyticsYandexMetrica extends Controller {
	private $error = array();

	public function index() {
		$this->load->language('extension/analytics/yandex_metrica');

		$this->document->setTitle($this->language->get('heading_title'));

		$this->load->model('setting/setting');

		$opencart_major_version = (mb_substr(VERSION, 0, 1));

		if ($opencart_major_version == 2) {
			$this->load->model('extension/modification');
			$modification_info = $this->model_extension_modification->getModificationByCode('Yandex.Metrica');

			$token_name = 'token';
		} elseif ($opencart_major_version == 3) {
			$this->load->model('setting/modification');
			$modification_info = $this->model_setting_modification->getModificationByCode('Yandex.Metrica');

			$token_name = 'user_token';
		} else {
			$token_name = 'user_token';
		}

		$latest_module_version = file_get_contents('https://metrika-cms-plugins.s3.yandex.net/opencart/current-version.txt');

		if (isset($modification_info['version']) && (version_compare($latest_module_version, $modification_info['version']) === 1)) {
			$data['notify_module_version'] = $this->language->get('text_notify_module_version');
		}

		if ($this->config->get('config_admin_language') === 'ru-ru') {
			$data['logo'] = 'view/image/analytics/yandex_metrica/yandex-metrica-logo.svg';
		} else {
			$data['logo'] = 'view/image/analytics/yandex_metrica/yandex-metrica-logo-en.svg';
		}

		if ($opencart_major_version == 2) {
			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
				$this->model_setting_setting->editSetting('yandex_metrica', $this->request->post, $this->request->get['store_id']);
	
				$this->session->data['success'] = $this->language->get('text_success');
	
				$this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'] . '&type=analytics', true));
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', $token_name .'=' . $this->session->data[$token_name], true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('extension/extension', $token_name .'=' . $this->session->data[$token_name] . '&type=analytics', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/analytics/yandex_metrica', $token_name . '=' . $this->session->data[$token_name] . '&store_id=' . $this->request->get['store_id'], true)
			);

			$data['action'] = $this->url->link('extension/analytics/yandex_metrica', $token_name . '=' . $this->session->data[$token_name] . '&store_id=' . $this->request->get['store_id'], true);

			$data['cancel'] = $this->url->link('extension/extension', $token_name . '=' . $this->session->data[$token_name] . '&type=analytics', true);

			$data['heading_title'] = $this->language->get('heading_title');

			$data['text_extension'] = $this->language->get('text_extension');
			$data['text_success'] = $this->language->get('text_success');
			$data['text_edit'] = $this->language->get('text_edit');
			$data['text_signup'] = $this->language->get('text_signup');
			$data['text_default'] = $this->language->get('text_default');
			$data['text_find_metric'] = $this->language->get('text_find_metric');
			$data['text_write_logs'] = $this->language->get('text_write_logs');
			$data['text_logs'] = $this->language->get('text_logs');
			$data['text_start_of_search_metric'] = $this->language->get('text_start_of_search_metric');
			$data['text_end_of_search_metric'] = $this->language->get('text_end_of_search_metric');
			$data['text_metrics_found'] = $this->language->get('text_metrics_found');
			$data['text_metrics_not_found'] = $this->language->get('text_metrics_not_found');
			$data['text_counter_number'] = $this->language->get('text_counter_number');
			$data['text_enabled'] = $this->language->get('text_enabled');
			$data['text_disabled'] = $this->language->get('text_disabled');
			$data['text_yes'] = $this->language->get('text_yes');
			$data['text_no'] = $this->language->get('text_no');
			$data['text_instructions'] = $this->language->get('text_instructions');
			$data['button_save'] = $this->language->get('button_save');
			$data['button_cancel'] = $this->language->get('button_cancel');
			$data['button_remove'] = $this->language->get('button_remove');

			$data['tab_metrics'] = $this->language->get('tab_metrics');
			$data['tab_metric_search'] = $this->language->get('tab_metric_search');
			$data['tab_logging'] = $this->language->get('tab_logging');
			$data['tab_info'] = $this->language->get('tab_info');

			$data['column_metric_code'] = $this->language->get('column_metric_code');
			$data['column_settings'] = $this->language->get('column_settings');
			$data['column_name'] = $this->language->get('column_name');
			
			$data['entry_code'] = $this->language->get('entry_code');
			$data['entry_metrics_list'] = $this->language->get('entry_metrics_list');
			$data['entry_metrica'] = $this->language->get('entry_metrica');
			$data['entry_status'] = $this->language->get('entry_status');
			$data['entry_webvisor'] = $this->language->get('entry_webvisor');

			$data['error_permission'] = $this->language->get('error_permission');
			$data['error_metric_code'] = $this->language->get('error_metric_code');

			$data['has_settings'] = !!$this->model_setting_setting->getSetting('yandex_metrica', $this->request->get['store_id']);

			if (isset($this->request->post['yandex_metrica_status'])) {
				$data['yandex_metrica_status'] = $this->request->post['yandex_metrica_status'];
			} else {
				$data['yandex_metrica_status'] = $this->model_setting_setting->getSettingValue('yandex_metrica_status', $this->request->get['store_id']);
			}

			if (isset($this->request->post['yandex_metrica_code'])) {
				$data['yandex_metrica_code'] = $this->request->post['yandex_metrica_code'];
			} else {
				$data['yandex_metrica_code'] = $this->model_setting_setting->getSettingValue('yandex_metrica_code', $this->request->get['store_id']);
			}

			if (isset($this->request->post['yandex_metrica_codes'])) {
				$data['yandex_metrica_codes'] = $this->request->post['yandex_metrica_codes'];
			} else {
				$data['yandex_metrica_codes'] = json_decode($this->model_setting_setting->getSettingValue('yandex_metrica_codes', $this->request->get['store_id']), true);
			}

			if (isset($this->request->post['yandex_metrica_log'])) {
				$data['yandex_metrica_log'] = $this->request->post['yandex_metrica_log'];
			} else {
				$data['yandex_metrica_log'] = $this->model_setting_setting->getSettingValue('yandex_metrica_log', $this->request->get['store_id']);
			}
		} else {
			if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validate()) {
				$this->model_setting_setting->editSetting('analytics_yandex_metrica', $this->request->post, $this->request->get['store_id']);
				$this->session->data['success'] = $this->language->get('text_success');
	
				$this->response->redirect($this->url->link('marketplace/extension', $token_name .'=' . $this->session->data[$token_name] . '&type=analytics', true));
			}

			$data['breadcrumbs'] = array();

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_home'),
				'href' => $this->url->link('common/dashboard', $token_name .'=' . $this->session->data[$token_name], true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('text_extension'),
				'href' => $this->url->link('marketplace/extension', $token_name .'=' . $this->session->data[$token_name] . '&type=analytics', true)
			);

			$data['breadcrumbs'][] = array(
				'text' => $this->language->get('heading_title'),
				'href' => $this->url->link('extension/analytics/yandex_metrica', $token_name . '=' . $this->session->data[$token_name] . '&store_id=' . $this->request->get['store_id'], true)
			);

			$data['action'] = $this->url->link('extension/analytics/yandex_metrica', $token_name . '=' . $this->session->data[$token_name] . '&store_id=' . $this->request->get['store_id'], true);

			$data['cancel'] = $this->url->link('marketplace/extension', $token_name . '=' . $this->session->data[$token_name] . '&type=analytics', true);

			$data['has_settings'] = !!$this->model_setting_setting->getSetting('analytics_yandex_metrica', $this->request->get['store_id']);

			if (isset($this->request->post['analytics_yandex_metrica_status'])) {
				$data['analytics_yandex_metrica_status'] = $this->request->post['analytics_yandex_metrica_status'];
			} else {
				$data['analytics_yandex_metrica_status'] = $this->model_setting_setting->getSettingValue('analytics_yandex_metrica_status', $this->request->get['store_id']);
			}

			if (isset($this->request->post['analytics_yandex_metrica_code'])) {
				$data['analytics_yandex_metrica_code'] = $this->request->post['analytics_yandex_metrica_code'];
			} else {
				$data['analytics_yandex_metrica_code'] = $this->model_setting_setting->getSettingValue('analytics_yandex_metrica_code', $this->request->get['store_id']);
			}

			if (isset($this->request->post['analytics_yandex_metrica_codes'])) {
				$data['analytics_yandex_metrica_codes'] = $this->request->post['analytics_yandex_metrica_codes'];
			} else {
				$data['analytics_yandex_metrica_codes'] = json_decode($this->model_setting_setting->getSettingValue('analytics_yandex_metrica_codes', $this->request->get['store_id']), true);
			}

			if (isset($this->request->post['analytics_yandex_metrica_log'])) {
				$data['analytics_yandex_metrica_log'] = $this->request->post['analytics_yandex_metrica_log'];
			} else {
				$data['analytics_yandex_metrica_log'] = $this->model_setting_setting->getSettingValue('analytics_yandex_metrica_log', $this->request->get['store_id']);
			}
		}

		if (isset($this->error['warning'])) {
			$data['error_warning'] = $this->error['warning'];
		} else {
			$data['error_warning'] = '';
		}

		if (isset($this->error['metrica'])) {
			$data['error_metrica'] = $this->error['metrica'];
		} else {
			$data['error_metrica'] = '';
		}

		$data['log'] = '';

		$file = DIR_LOGS . 'log_yandex_metrica.log';

		if (file_exists($file)) {
			$size = filesize($file);

			if ($size >= 5242880) {
				$suffix = array(
					'B',
					'KB',
					'MB',
					'GB',
					'TB',
					'PB',
					'EB',
					'ZB',
					'YB'
				);

				$i = 0;

				while (($size / 1024) > 1) {
					$size = $size / 1024;
					$i++;
				}

				$data['error_warning'] = sprintf($this->language->get('error_warning'), basename($file), round(substr($size, 0, strpos($size, '.') + 4), 2) . $suffix[$i]);
			} else {
				$data['log'] = file_get_contents($file, FILE_USE_INCLUDE_PATH, null);
			}
		}
		
		$data[$token_name] = $this->session->data[$token_name];
		$data['store_id'] = $this->request->get['store_id'];

		$data['header'] = $this->load->controller('common/header');
		$data['column_left'] = $this->load->controller('common/column_left');
		$data['footer'] = $this->load->controller('common/footer');

		$this->response->setOutput($this->load->view('extension/analytics/yandex_metrica', $data));
	}
	
	public function find_ya_metricax() {
		$data = [];
		$count_of_metrik = 0;
		$codes_metrik = '';
		$page_code = $this->file_get_contents_curl(HTTP_CATALOG);
		$status = $this->has_metrika($page_code);

		if ($status) {
			$regexp = '/ym\((.+?), \"init\"/';
			$count_of_metrik = preg_match_all($regexp, $page_code, $matches);
			$codes_metrik = $matches[1];
		}

		$data = [
			'success' => $status,
			'count_of_metrik' => $count_of_metrik,
			'codes_metrik' => $codes_metrik,
		];

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($data));
	}

	protected function has_metrika($page_code) {
		$search_string = 'https://mc.yandex.ru/metrika/tag.js';
		$pos = strripos($page_code, $search_string);

		if ($pos === false) {
			return (bool)$pos;
		}

		return true;
	}

	protected function file_get_contents_curl($url) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $url);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'extension/analytics/yandex_metrica')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (isset($this->request->post['analytics_yandex_metrica_codes'])) {
			foreach ($this->request->post['analytics_yandex_metrica_codes'] as $row => $metrica) {
				if (empty($metrica['code'])) {
					$this->error['metrica'][$row]['code'] = $this->language->get('error_metric_code');
				}
			}
		}

		return !$this->error;
	}
}
