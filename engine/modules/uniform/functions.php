<?php
/*
 * DLE UniForm — унверсальные формы для DLE
 *
 * @author     pafnuty <pafnuty10@gmail.com>
 * @link       http://pafnuty.name/
 * @link       https://twitter.com/pafnuty_name
 * @version     2.1.4
 * @adapted    Adapted for DLE 21 / PHP 8.x by The88788.com (2026)
 */

if (!defined('DATALIFEENGINE')) {
	die('Что то пошло не так');
}
/**
 * Различные функции, используемые в файлах модуля.
 */

/**
 * Получение массиа из строки конфига
 *
 * @param  string  $string  Строка конфига
 * @param  string  $delimiter  Разделитель массива
 *
 * @return array             Массив
 */
function getArray($string, $delimiter = ',') {
	$arr = explode($delimiter, $string);
	foreach ($arr as $k => $v) {
		$arr[$k] = trim($v);
	}

	return array_filter($arr);
}

/**
 * Получение случайного CSRF-токена формы из активной сессии
 *
 * @param  string  $string  Токен
 *
 * @return string         Случайный токен
 */
function getToken($string) {
	if (session_status() !== PHP_SESSION_ACTIVE) {
		throw new RuntimeException('UniForm requires an active session');
	}
	$key = hash('sha256', $string);
	if (!isset($_SESSION['uniform_csrf'][$key])) {
		$_SESSION['uniform_csrf'][$key] = bin2hex(random_bytes(32));
	}
	return $_SESSION['uniform_csrf'][$key];
}

/**
 * Валидация CSRF-токена
 *
 * @param  string  $first  Первый токен
 * @param  string  $second  Второй токен
 *
 * @return bool           true|false
 */
function checkToken($first, $second) {
	$key = hash('sha256', $second);
	$expected = $_SESSION['uniform_csrf'][$key] ?? null;
	return session_status() === PHP_SESSION_ACTIVE && is_string($first)
		&& is_string($expected) && hash_equals($expected, $first);
}

/**
 * Валидация email-адреса
 *
 * @param  string  $email  Email-адрес
 *
 * @return bool          true|false
 */
function validEmain($email) {
	return is_string($email) && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** HTML text and attribute values; never use SQL escaping for output. */
function uniformEscape($value) {
	return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

/** Atomic per-IP reservations shared by all forms and sessions of this installation.
 * State contains timestamps only, outside the document root. Invalid/unavailable
 * storage fails closed; callers must never report successful mail in that case.
 */
function uniformRateLimit($phase, $limit, $window, $interval = 0, $now = null) {
	$denied = ['allowed' => false, 'retry' => 60, 'storage' => true];
	$ip = $_SERVER['REMOTE_ADDR'] ?? '';
	if (!is_string($ip) || !filter_var($ip, FILTER_VALIDATE_IP)
		|| !in_array($phase, ['requests', 'submissions'], true)) return $denied;
	$now = $now ?? time();
	$directory = rtrim(sys_get_temp_dir(), '/\\') . '/dle-uniform-' . substr(hash('sha256', realpath(ENGINE_DIR) ?: ENGINE_DIR), 0, 24);
	if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) return $denied;
	$secret = defined('SECURE_AUTH_KEY') ? SECURE_AUTH_KEY : hash('sha256', ENGINE_DIR);
	$key = hash_hmac('sha256', $ip, $secret);
	$file = $directory . '/' . $key . '.json';
	$handle = @fopen($file, 'c+');
	if (!$handle) return $denied;
	@chmod($file, 0600);
	if (!flock($handle, LOCK_EX | LOCK_NB)) { fclose($handle); return $denied; }
	try {
		$raw = stream_get_contents($handle);
		$state = $raw === '' ? [] : json_decode($raw, true);
		if (!is_array($state) || strlen($raw) > 65536) return $denied;
		foreach (['requests', 'submissions'] as $bucket) {
			if (isset($state[$bucket]) && !is_array($state[$bucket])) return $denied;
			$state[$bucket] = array_slice(array_values(array_filter($state[$bucket] ?? [], static function ($stamp) use ($now, $bucket, $phase, $window, $interval) {
				$age = $bucket === $phase ? max($window, $interval) : 3600;
				return is_int($stamp) && $stamp > $now - $age && $stamp <= $now;
			})), -1000);
		}
		$times = $state[$phase];
		$recent = array_values(array_filter($times, static function ($stamp) use ($now, $window) { return $stamp > $now - $window; }));
		$retry = count($recent) >= $limit ? max(1, $recent[0] + $window - $now) : 0;
		if ($times && $interval > 0) $retry = max($retry, end($times) + $interval - $now);
		if ($retry > 0) return ['allowed' => false, 'retry' => $retry, 'storage' => false];
		$state[$phase][] = $now;
		$json = json_encode($state);
		if (!is_string($json) || !rewind($handle) || !ftruncate($handle, 0)
			|| fwrite($handle, $json) !== strlen($json) || !fflush($handle)) return $denied;
		return ['allowed' => true, 'retry' => 0, 'storage' => false];
	} finally { flock($handle, LOCK_UN); fclose($handle); }
}

function uniformRateMessage($result) {
	return $result['storage'] ? 'Отправка временно недоступна. Повторите попытку позже.'
		: 'Слишком много попыток отправки. Повторите через ' . (int)$result['retry'] . ' сек.';
}

/** Validate a PHP upload before handing its temporary path to the mailer. */
function uniformValidAttachment($file, $allowedTypes, $maxBytes) {
	if (!is_array($file) || !isset($file['name'], $file['tmp_name'], $file['error'])
		|| !is_string($file['name']) || !is_string($file['tmp_name'])
		|| (int)$file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) {
		return false;
	}
	$size = filesize($file['tmp_name']);
	if ($size === false || $size <= 0 || $size > $maxBytes) return false;
	$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
	$mimeTypes = [
		'jpg' => ['image/jpeg'], 'jpeg' => ['image/jpeg'], 'png' => ['image/png'],
		'gif' => ['image/gif'], 'webp' => ['image/webp'], 'pdf' => ['application/pdf'],
		'txt' => ['text/plain'], 'zip' => ['application/zip', 'application/x-zip-compressed'],
		'doc' => ['application/msword', 'application/x-ole-storage', 'application/CDFV2'],
		'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/zip'],
	];
	if (!in_array($extension, $allowedTypes, true) || !isset($mimeTypes[$extension]) || !class_exists('finfo')) return false;
	$mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
	if (!in_array($mime, $mimeTypes[$extension], true)) return false;
	if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
		$image = @getimagesize($file['tmp_name']);
		if (!$image || ($image['mime'] ?? '') !== $mime) return false;
	}
	if ($extension === 'docx') {
		if (!class_exists('ZipArchive')) return false;
		$zip = new ZipArchive();
		if ($zip->open($file['tmp_name']) !== true) return false;
		$valid = $zip->locateName('[Content_Types].xml') !== false && $zip->locateName('word/document.xml') !== false;
		$zip->close();
		if (!$valid) return false;
	}
	return true;
}

/**
 * Добавляем скрытые поля в форму
 *
 * @param  array  $arFields  Поля, разрешенные в конфиге
 * @param  array  $requestArray  поля из реквеста
 *
 * @return string Поля, добавляемые в форму
 */
function addHiddenFields($arFields = [], $requestArray = []) {
	$hiddenInputs = '';
	if (count($arFields) > 0 && is_array($requestArray)) {
		foreach ($arFields as $field) {
			if (array_key_exists($field, $requestArray) && is_scalar($requestArray[$field])) {
				// Значение выводится в HTML-атрибут, а не в SQL-запрос.
				$hiddenInputs .= '<input type="hidden" name="'.htmlspecialchars($field, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'" value="'.htmlspecialchars((string)$requestArray[$field], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
					.'">';
				$hiddenInputs .= "\n";
			}
		}
	}

	return $hiddenInputs;
}

/**
 * Функция для назначения тегов, обрабатывающих селекты, чекбоксы и радиокнопки.
 * Код вынесен в функцию для избежания дублирования.
 *
 * @param  string  $k  Имя поля
 * @param  string  $val  Значение поля
 * @param  string  $fieldType  Тип поля
 * @param  array  $arFields  Массив, содержащий разрешенные ключи
 * @param  boolean  $parse  Объект класса parse.class.php
 * @param  boolean  $tpl  Объект шаблонизатора
 * @param  array  $arSendMail  Массив, отправляемый обработчику email
 *
 * @return array Массив для отправки по email
 */
function assignFiedls(
	$k = '',
	$val = '',
	$fieldType = '',
	$arFields = [],
	$parse = false,
	$tpl = false,
	$arSendMail = []
) {

	if (in_array($k, $arFields)) {
		// Если поле содержится в нужном массиве
		$arSendMailTmp = [];
		if (is_array($val)) {
			// Если поле множественное (множественный селект к примеру)
			foreach ($val as $valvalue) {
				// Нужно обработать поля, т.к. мы не обрабатывали массивы.
				// DLE 21 работает только в UTF-8, convert_unicode() удалена из движка
				$valvalue = $parse->process(trim($valvalue));
				// Добавим значение в массив для последующей отправки на email в нормальном виде.
				$arSendMailTmp[]    = $valvalue;
				$tpl->copy_template = str_replace("[uf_{$fieldType}_{$k}_{$valvalue}]", '', $tpl->copy_template);
				$tpl->copy_template = str_replace("[/uf_{$fieldType}_{$k}_{$valvalue}]", '', $tpl->copy_template);
			}
			// Добавляем данные из мультиселекта в массив для отправки в обработчик email т.к. ранее мы добавляли только обычные поля.
			$arSendMail[$k] = implode(', ', $arSendMailTmp);
		} else {
			$tpl->copy_template = str_replace("[uf_{$fieldType}_{$k}_{$val}]", '', $tpl->copy_template);
			$tpl->copy_template = str_replace("[/uf_{$fieldType}_{$k}_{$val}]", '', $tpl->copy_template);
		}

		$tpl->copy_template = preg_replace("'\\[uf_{$fieldType}_{$k}_(.*?)\\](.*?)\\[/uf_{$fieldType}_{$k}_(.*?)\\]'is",
			'', $tpl->copy_template);
	} else {
		// Удалем теги, которые не должны показываться
		$tpl->copy_template = preg_replace("'\\[uf_{$fieldType}_{$k}_(.*?)\\](.*?)\\[/uf_{$fieldType}_{$k}_(.*?)\\]'is",
			'', $tpl->copy_template);
	}

	return $arSendMail;
}

/**
 * Обрабатываем теги с условиями вывода
 *
 * @param  array  $data  массив с данными
 * @param  string  $fieldType  Тип поля
 * @param  array  $arFields  массив с полями, относящимися к выбранному типу поля
 * @param  obgect  $tpl  Объект шаблонизатора
 *
 * @return array Массив с удалёнными обработанными данными
 */
function setConditions($data, $fieldType = '', $arFields = [], $tpl = false) {

	if (count($data) > 0) {
		// Если есть данные — работаем
		foreach ($data as $k => $val) {
			if (in_array($k, $arFields) || $fieldType == 'field') {
				// Если поле находится в массиве разрешенных полей или является текстовым — работаем
				if (preg_match_all("#\\[uf_{$fieldType}_{$k}=['\"](.+?)['\"]\\](.+?)\\[/uf_{$fieldType}_{$k}\\]#si",
					$tpl->copy_template, $matches)
				) {
					// Проверем совпадения
					foreach ($matches[1] as $key => $match) {
						if (is_array($val)) {
							// Если поле множественное
							foreach ($val as $kk => $valvalue) {
								if ($match == $valvalue) {
									if (isset($matches[0][$key])) {
										$tpl->set($matches[0][$key], $matches[2][$key]);
									}
									// Удаляем ненужное, иначе будут глюки.
									unset($matches[0][$key]);
								} else {
									$tpl->set($matches[0][$key], '');
								}
							}
						} else {
							if ($match == $val) {
								$tpl->set($matches[0][$key], $matches[2][$key]);
							} else {
								$tpl->set($matches[0][$key], '');
							}
						}
					}
				}
				if ($fieldType == 'field' && $val != '') {
					$tpl->set("[uf_{$fieldType}_{$k}]", '');
					$tpl->set("[/uf_{$fieldType}_{$k}]", '');
				}
				// Удаляем обработанный элемент массива
				unset($data[$k]);
			}
		}
	}

	return $data;
}
