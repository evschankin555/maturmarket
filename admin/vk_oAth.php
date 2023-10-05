<?php
/**
 * Данный файл нужно удалить с сервера после вызова
 * Файл инициализирует токены синхронизации с ВК
 * 
 * Предварительно нужно внести настройки в БД:
 * delete from oc_setting where `key` = 'vk_settings_category-list';
 * insert into oc_setting (store_id, code, `key`, value, serialized) select 0, 'vk_settings', 'vk_settings_category-list', '[{"category_id": 201, "vk_title": "Татарстан - Паркмахерам и колористам - товары из раздела Парикмахерам", "city_filter": "260"},{"category_id": 202, "vk_title": "Татарстан - Маникюр и педикюр - товары из раздела Маникюр и педикюр", "city_filter": "260"}]', 1;
 * select * from oc_setting where `key` = 'vk_settings_category-list';
 * 
 * delete from oc_setting where `key` = 'vk_settings_last_upload';
 * insert into oc_setting (store_id, code, `key`, value, serialized) select 0, 'vk_settings', 'vk_settings_last_upload', '2000-01-01', 0;
 * select * from oc_setting where `key` = 'vk_settings_last_upload';
 * 
 * delete from oc_setting where code = 'vk_oath';
 * insert into oc_setting (store_id, code, `key`, value, serialized) select 0, 'vk_oath', 'vk_oath_id_application', '8153060', 0;
 * insert into oc_setting (store_id, code, `key`, value, serialized) select 0, 'vk_oath', 'vk_oath_secret_key', 'eSGgO5ZWvdOHrfoPqiEl', 0;
 * insert into oc_setting (store_id, code, `key`, value, serialized) select 0, 'vk_oath', 'vk_oath_id_group', '-210954832', 0;
 * insert into oc_setting (store_id, code, `key`, value, serialized) select 0, 'vk_oath', 'vk_oath_back_link', 'http://maturmarket.local/admin/index.php?route=common/dashboard', 0;
 * select * from oc_setting where code = 'vk_oath';
 * 
 * Далее выполнить инициализацию модуля:
 * POST https://www.maturmarket.ru/admin/index.php?route=api/export  code = '' type = 'vk_install'
 * 
 * Далее вызываем этот файл
 * 
 * И после этого можно синхронизироваться:
 * POST https://www.maturmarket.ru/admin/index.php?route=api/export  code = '' type = 'vk'
 * 
 */
if (is_file(__DIR__.'/config.php')) {
    require_once(__DIR__.'/config.php');
}

require_once DIR_SYSTEM . 'startup.php';
require_once DIR_CONFIG . 'admin.php';

require_once DIR_SYSTEM . 'library/vk/vk.php';

$db = new DB($_['db_engine'], $_['db_hostname'], $_['db_username'], $_['db_password'], $_['db_database'], $_['db_port']);
$settings = $db->query("SELECT * FROM " . DB_PREFIX . "setting WHERE store_id = '" . 0 . "' AND `code` = '" . $db->escape('vk_oath') . "'");

foreach ($settings->rows as $row) {
     $setting_data[$row['key']] = $row['value'];
}

# 1 шаг авторизации
if (!$_GET) {
    $parameters = array(
        'client_id' => $setting_data['vk_oath_id_application'],
        'display' => 'page',
        'redirect_uri' => HTTPS_SERVER . 'vk_oAth.php',
        'scope' => 'offline,market,photos,groups',
        'response_type' => 'code',
        'v' => '5.131'
    );

    if (isset($setting_data['vk_oath_access_token'])) {
        $parameters['group_ids'] = ltrim($setting_data['vk_oath_id_group'], '-');
        $parameters['scope'] = 'manage';
    }

    $url = 'https://oauth.vk.com/authorize';
    $url .= '?' . http_build_query($parameters, '', '&');

    header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $url), true, 302);
    exit();

# 2 шаг авторизации
} elseif (isset($_GET['code'])) {
    $parameters = array(
        'client_id' => $setting_data['vk_oath_id_application'],
        'client_secret' => $setting_data['vk_oath_secret_key'],
        'redirect_uri' => HTTPS_SERVER . 'vk_oAth.php',
        'code' => $_GET['code']
    );

    $url = 'https://oauth.vk.com/access_token';
    $url .= '?' . http_build_query($parameters, '', '&');
    $response = file_get_contents($url);

    if (!isset($setting_data['vk_oath_access_token'])) {
        $accessToken = json_decode($response, true)['access_token'];

        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . 0 . "' AND `code` = '" . $db->escape('vk_oath') . "' AND `key` = '" . $db->escape('vk_oath_access_token') . "'");
        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . 0 . "' AND `code` = '" . $db->escape('vk_oath') . "' AND `key` = '" . $db->escape('vk_oath_access_token_info') . "'");
        $db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . 0 . "', `code` = '" . $db->escape('vk_oath') . "', `key` = '" . $db->escape('vk_oath_access_token') . "', `value` = '" . $db->escape($accessToken) . "'");
        $db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . 0 . "', `code` = '" . $db->escape('vk_oath') . "', `key` = '" . $db->escape('vk_oath_access_token_info') . "', `value` = '" . $db->escape($response) . "', serialized = '1'");

        header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), HTTPS_SERVER . 'vk_oAth.php'), true, 302);
        exit();
    } else {
        $accessToken = json_decode($response, true)['access_token_' . ltrim($setting_data['vk_oath_id_group'], '-')];

        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . 0 . "' AND `code` = '" . $db->escape('vk_oath') . "' AND `key` = '" . $db->escape('vk_oath_access_token_group') . "'");
        $db->query("INSERT INTO " . DB_PREFIX . "setting SET store_id = '" . 0 . "', `code` = '" . $db->escape('vk_oath') . "', `key` = '" . $db->escape('vk_oath_access_token_group') . "', `value` = '" . $db->escape($accessToken) . "'");
        $db->query("DELETE FROM `" . DB_PREFIX . "setting` WHERE store_id = '" . 0 . "' AND `code` = '" . $db->escape('vk_oath') . "' AND `key` = '" . $db->escape('vk_oath_back_link') . "'");

        header('Location: ' . str_replace(array('&amp;', "\n", "\r"), array('&', '', ''), $setting_data['vk_oath_back_link']), true, 302);
        exit();
    }
}


