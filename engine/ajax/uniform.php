<?php
/*
 * DLE UniForm — унверсальные формы для DLE
 *
 * @author     pafnuty <pafnuty10@gmail.com>
 * @link       http://pafnuty.name/
 * @link       https://twitter.com/pafnuty_name
 * @version     2.1.0
 * @adapted    Adapted for DLE 21 / PHP 8.x by The88788.com (2026)
 */

if (!defined('DATALIFEENGINE')) {
	header('HTTP/1.1 403 Forbidden');
	header('Location: ../../');
	die('Hacking attempt!');
}


$banned_info = get_vars('banned');

if (!is_array($banned_info)) {
	$banned_info = [];

	$db->query("SELECT * FROM ".USERPREFIX."_banned");
	while ($row = $db->get_row()) {

		if ($row['users_id']) {

			$banned_info['users_id'][$row['users_id']] = [
				'users_id' => $row['users_id'],
				'descr'    => stripslashes($row['descr']),
				'date'     => $row['date'],
			];

		} else {

			if (count(explode(".", $row['ip'])) == 4) {
				$banned_info['ip'][$row['ip']] = [
					'ip'    => $row['ip'],
					'descr' => stripslashes($row['descr']),
					'date'  => $row['date'],
				];
			} elseif (strpos($row['ip'], "@") !== false) {
				$banned_info['email'][$row['ip']] = [
					'email' => $row['ip'],
					'descr' => stripslashes($row['descr']),
					'date'  => $row['date'],
				];
			} else {
				$banned_info['name'][$row['ip']] = [
					'name'  => $row['ip'],
					'descr' => stripslashes($row['descr']),
					'date'  => $row['date'],
				];
			}

		}

	}
	set_vars('banned', $banned_info);
	$db->free();
}

if (!empty($banned_info['ip']) && check_ip($banned_info['ip'])) {
	die('error_ban');
}

if ($is_logged AND $member_id['banned'] == 'yes') {
	die('error_ban');
}

if (!$config['allow_registration']) {
	$dle_login_hash = sha1(SECURE_AUTH_KEY.$_IP);
}

$template_dir = ROOT_DIR.'/templates/'.$config['skin'];

// Санитизация имени конфига формы (только буквы, цифры, _, -)
$formConfig = isset($_REQUEST['formConfig']) && is_string($_REQUEST['formConfig']) ? preg_replace("/[^a-z0-9_\-]+/mi", '', $_REQUEST['formConfig']) : '';
$_REQUEST['formConfig'] = $formConfig;

// Пытаемся получить даные из шаблона с настройками
if ($formConfig !== '' && file_exists($template_dir.'/uniform/'.$formConfig.'/config.tpl')) {
	// Если файл существует - берём из него контент с настройками
	$preset = file_get_contents($template_dir.'/uniform/'.$formConfig.'/config.tpl');
	$arConf = [];
} else {
	http_response_code(404);
	die('Форма не найдена');
}
// Разбиваем полученные из файла нестройки по строкам
$preset = explode("\n", $preset);

// Пробегаем по массиву и формируем список настроек
foreach ($preset as $v) {
	$_v = explode('=', $v);
	if (isset($_v[1])) {
		$arConf[trim($_v[0])] = trim($_v[1]);
	}
}

// Подключаем основной модуль
include(DLEPlugins::Check(ENGINE_DIR.'/modules/uniform/uniform.php'));

