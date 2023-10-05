<?php
class ControllerApiExport extends Controller {
	const VK_DB_VERSION = '1.0';
    const VK_MODULE_VERSION = '1.0';

	public function index() {
		$json = array();

		// Проверим авторизацию напрямую, не через сессию как у других апи (чтобы вызывать экспорт одним запросом, без предваритльного логина)
		$error_message = $this->checkAuthAndLogin();
		if ($error_message){
			$json['error'] = $error_message;
			$this->response->addHeader('Content-Type: application/json');
			$this->response->setOutput(json_encode($json));
			$this->log->write($error_message);
			return;
		}

		$this->load->language('api/export');

		$error_message = $this->language->get('error_no_type');
		if ($this->request->post['type'] == 'vk'){
			$error_message = $this->exportVk();
		} else if ($this->request->post['type'] == 'vk_install'){
			$error_message = $this->install();
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
		$this->load->language('api/export');

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
		if (!$this->user->hasPermission('modify', 'api/export')) {
			return $this->language->get('error_permission');
		}
		
		return null;
	}

	private function exportVk(){
		$this->log->write('VK sync started');
		$this->load->library('vk/vk');		
		if ($this->checkModifiedCategoryList() || !$this->checkFileProductsForExport()) {
            $categories = $this->vk->createAlbum();
            $this->vk->addProducts($categories);
        } else {
            $this->vk->addProducts($this->checkFileProductsForExport(), 'products');
        }
			
		return null;
	}

	/**
     * Check products for export from file
     *
     * @return array|bool|mixed
     */
    private function checkFileProductsForExport()
    {
        if (file_exists(DIR_SYSTEM . '/vk_cron/products_for_export.json')) {
            $products = json_decode(file_get_contents(DIR_SYSTEM . '/vk_cron/products_for_export.json'), true);
        } else {
            $products = [];
        }

        return !empty($products) ? $products : false;
    }

    /**
     * Have settings changed since the last export
     *
     * @return bool
     */
    private function checkModifiedCategoryList()
    {
        $this->load->model('setting/setting');

        $settings = $this->model_setting_setting->getSetting('vk_settings');

        $category_list_current = isset($settings['vk_settings_category-list']) ? $settings['vk_settings_category-list'] : array();

        if (file_exists(DIR_SYSTEM . '/vk_cron/category_setting.json')) {
            $category_list = json_decode(file_get_contents(DIR_SYSTEM . '/vk_cron/category_setting.json'), true);
        }

        if (!isset($category_list) || $category_list != $category_list_current) {
            file_put_contents(DIR_SYSTEM . '/vk_cron/category_setting.json', json_encode($category_list_current));

            return true;
        }

        return false;
    }

	/**
     * Install method
     *
     * @return void
     */
    public function install()
    {
		$this->load->model('setting/setting');

		if ($this->model_setting_setting->getSettingValue('vk_status') == 1){
			$this->load->language('api/export');
			return $this->language->get('error_already_installed');
		}		

        $this->load->model('extension/vk/tables');

        $this->model_extension_vk_tables->createTables();

        $this->model_setting_setting->editSetting(
            'vk',
            array(
                'vk_status' => 1,
                'vk_country' => array($this->config->get('config_country_id')),
                'vk_db_version' => self::VK_DB_VERSION,
                'vk_module_version' => self::VK_MODULE_VERSION,
                'vk_statistic' => 0
            )
        );

        $this->model_setting_setting->editSetting(
            'vk_event',
            array(
                'vk_event_status' => 0
            )
        );

		return null;
    }
}
