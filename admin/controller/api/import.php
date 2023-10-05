<?php
class ControllerApiImport extends Controller {
	public function index() {
		$json = array();

		// Проверим авторизацию напрямую, не через сессию как у других апи (чтобы вызывать импорт одним запросом, без предваритльного логина)
		$error_message = $this->checkAuthAndLogin();
		if ($error_message){
			$json['error'] = $error_message;
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			$this->log->write($error_message);
			return;
		}

		$this->load->language('api/import');

		$error_message = $this->language->get('error_no_type');
		if ($this->request->post['type'] == 'erfolg'){
			$error_message = $this->importErfolgFull();
		} else if ($this->request->post['type'] == 'erfolg_changes'){
			$error_message = $this->importErfolgChanges();
		}

		if ($error_message) {
			$json['error'] = $error_message;
			$this->log->write($error_message);
		} else {
			$json['success'] = $this->language->get('text_success');			
		}

		$this->response->addHeader('Content-Type: application/json');
		$this->response->setOutput(json_encode($json));
	}

	private function checkAuthAndLogin(){
		$this->load->language('api/import');

		if(!isset($this->request->post['code']) || !$this->request->post['code']) {
			return $this->language->get('error_login');
		} else {
			$this->load->model('user/user');

			// Check how many login attempts have been made.
			$login_info = $this->model_user_user->getLoginAttempts($this->request->post['code']);

			if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
				return $this->language->get('error_attempts');
			}
		}

		if (!$this->user->loginByCode(html_entity_decode($this->request->post['code'], ENT_QUOTES, 'UTF-8'))) {
			$this->model_user_user->addLoginAttempt($this->request->post['code']);

			unset($this->session->data['user_token']);

			return $this->language->get('error_login');
		} else {
			$this->model_user_user->deleteLoginAttempts($this->request->post['code']);
			$this->session->data['user_token'] = token(32);
		}

		// И проверим права доступа к апи
		if (!$this->user->hasPermission('modify', 'api/import')) {
			return $this->language->get('error_permission');
		}
		
		return null;
	}

	private function importErfolgFull(){
		$this->load->language('catalog/import');
		// Загрузим файл
		$this->session->data['import'] = token(10);
		$this->session->data['import_original_filename'] = 'erfolg_' . $this->session->data['import'] . '.xml';
		$this->session->data['supplier'] = $this->language->get('supplier_erfolg');

		$ERFOLG_FILE_URL = 'https://erfolg-c.ru/yandex_market/95c06bab-72de-43cd-bdd9-bb8a34749f8e.xml';
		$file = DIR_UPLOAD . $this->session->data['import'] . '.tmp-import';
				
		file_put_contents($file, file_get_contents($ERFOLG_FILE_URL));

		if (is_file($file)) {
			$this->load->model('catalog/import');
			
			$import_history_id = $this->model_catalog_import->addImportHistory($this->session->data['supplier'], $this->session->data['import_original_filename']);
			$this->session->data['import_history_id'] = $import_history_id;

			// Далее вызовем те же методы, что и при ручной загрузке
			$this->load->load_controller('catalog/import');
			$this->request->get['import_history_id'] = $import_history_id;
			$this->controller_catalog_import->import();
			$this->controller_catalog_import->unzip();
			$this->controller_catalog_import->save();

			return null;
		} else {
			$this->load->language('api/import');
			return $this->language->get('error_file');
		}
	}

	private function importErfolgChanges(){
		$this->load->language('catalog/import');
		$this->load->model('setting/setting');

		// Загрузим файл
		$this->session->data['import'] = token(10);
		$this->session->data['import_original_filename'] = 'erfolg_' . $this->session->data['import'] . '.json';
		$this->session->data['supplier'] = $this->language->get('supplier_erfolg_changes');

		$settings = $this->model_setting_setting->getSetting('import_settings');
		$last_upload = $settings['erfolg_last_upload'];

		$ERFOLG_FILE_URL = 'https://erfolg-c.ru/matur/offers/' . ($last_upload ? '?time=' . str_replace(' ', '%20', $last_upload) : '');	
		
		$file = DIR_UPLOAD . $this->session->data['import'] . '.tmp-import';
				
		file_put_contents($file, file_get_contents($ERFOLG_FILE_URL));

		if (is_file($file)) {
			$this->load->model('catalog/import');
			
			$import_history_id = $this->model_catalog_import->addImportHistory($this->session->data['supplier'], $this->session->data['import_original_filename']);
			$this->session->data['import_history_id'] = $import_history_id;

			// Далее вызовем те же методы, что и при ручной загрузке
			$this->load->load_controller('catalog/import');
			$this->request->get['import_history_id'] = $import_history_id;
			$this->controller_catalog_import->import();
			$this->controller_catalog_import->unzip();
			$this->controller_catalog_import->save();

			return null;
		} else {
			$this->load->language('api/import');
			return $this->language->get('error_file');
		}
		
	}
}
