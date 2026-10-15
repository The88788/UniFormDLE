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
 * Информация из DLE, доступная в модуле
 *
 * @global boolean $is_logged  Является ли посетитель авторизованным пользователем или гостем.
 * @global array   $member_id  Массив с информацией о авторизованном пользователе, включая всю его информацию из
 *         профиля.
 * @global object  $db         Класс DLE для работы с базой данных.
 * @global array   $config     Информация обо всех настройках скрипта.
 * @global array   $user_group Информация о всех группах пользователей и их настройках.
 * @global integer $_TIME      Содержит текущее время в UNIX формате с учетом настроек смещения в настройках скрипта.
 */

// Подключаем конфиг модуля
include (DLEPlugins::Check(ENGINE_DIR . '/modules/uniform/cfg.php'));

// Подключаем функции модуля
include (DLEPlugins::Check(ENGINE_DIR . '/modules/uniform/functions.php'));

// Имя кеша
/** @var array $cfg */
$cacheName = md5('security-v2_' . implode('_', $cfg));
// ID сессии
$sessionId = session_id();

$uniform      = false;
$hiddenInputs = '';
// Если данные передаются постом — "запомним" это.
$isPost = ($_SERVER['REQUEST_METHOD'] === 'POST') ? true : false;
$rateError = '';
$mailError = '';
$rateRetry = 0;
if ($isPost) {
	$rate = uniformRateLimit('requests', $cfg['rateLimitRequests'], $cfg['rateLimitRequestWindow']);
	if (!$rate['allowed']) {
		$rateError = uniformRateMessage($rate);
		$rateRetry = $rate['retry'];
	}
}

// Определяемся с шаблоном сайта
// Проверим куку пользователя и налочие параметра skin в реквесте.
$currentSiteSkin = (isset($_COOKIE['dle_skin'])) ? trim(totranslit($_COOKIE['dle_skin'], false, false)) : ((isset($_REQUEST['skin'])) ? trim(totranslit($_REQUEST['skin'], false, false)) : $config['skin']);

$config['skin'] = ($currentSiteSkin == '') ? $config['skin'] : $currentSiteSkin;

// Personalised forms must never use the shared guest cache.
if (!empty($is_logged)) $cfg['nocache'] = true;

if (!$cfg['nocache'] && !$isPost) {
	// Проверим кеш, если это требуется
	$uniform = dle_cache($cfg['role'], $cacheName . $config['skin'], true);
}
if (!$uniform) {
	if (!defined('TEMPLATE_DIR')) {
		require_once (DLEPlugins::Check(ENGINE_DIR . '/classes/templates.class.php'));
		$tpl      = new dle_template();
		$tpl->dir = $template_dir;
		define('TEMPLATE_DIR', $tpl->dir);
	}

	if (file_exists(TEMPLATE_DIR . '/uniform/' . $cfg['templateFolder'] . '/form.tpl')) {
		// Если файл шаблона существует — работаем.
		$tpl->result['uniform'] = '';
		$tpl->load_template('uniform/' . $cfg['templateFolder'] . '/form.tpl');

		// Пользовательские скрытые поля
		$arHidden = getArray($cfg['hidden']);

		// Дебаг
		$debug    = false; // Never expose config or submitted data in public forms.
		$debugTag = '';
		if ($debug) {
			$tpl->set('[debug]', '');
			$tpl->set('[/debug]', '');
			$debugTag .= '<pre class="uf-pre"><h4>config:</h4>';
			$debugTag .= print_r($cfg, true);
			$debugTag .= '</pre>';
		} else {
			$tpl->set_block("'\\[debug\\](.*?)\\[/debug\\]'si", '');
		}

		// Массив для отправки в sendmail.php
		$arSendMail = [];

		if ($isPost) {
			// Переменная-индикатор ошибок.
			$error = false;
			if ($debug) {
				$debugTag .= '<pre class="uf-pre"><h4>POST:</h4>';
				$debugTag .= print_r($_POST, true);
				$debugTag .= '</pre>';
			}

			// DLE 21: ParseFilter и HTMLPurifier подключаются автозагрузчиком (прямой require parse.class.php обходит его и ломает HTMLPurifier_Config)
			$parse = new ParseFilter();

			if (!checkToken(isset($_POST['csrfToken']) ? $_POST['csrfToken'] : '', $cacheName . $config['skin'] . $sessionId)) {
				// Проверяем токен
				$error = true;
				$tpl->set('[uf_token_error]', '');
				$tpl->set('[/uf_token_error]', '');
			} else {
				$tpl->set_block("'\\[uf_token_error\\](.*?)\\[/uf_token_error\\]'si", '');
			}

			// Определяем переменную для передачи в различные функции
			$post = $mailPost = $_POST;
			unset($post['csrfToken'], $post['formConfig'], $mailPost['csrfToken'], $mailPost['formConfig']);

			// Если в посте передано поле newsId, нужно получить информацию из новости
			if (isset($post['newsId']) && (int)$post['newsId'] > 0) {
				$newsItem = $db->super_query('SELECT p.id, p.autor, p.title, u.name, u.email, u.allow_mail FROM ' . PREFIX . '_post p LEFT JOIN ' . USERPREFIX . '_users u ON (p.autor=u.name) WHERE id = ' . (int)$post['newsId']);
				// Если запрос нашёл новость — работаем
				if (is_array($newsItem) && !empty($newsItem['id'])) {
					// Если автор новости разрешил отправлять ему письма и в конфиге есть sendToAuthor — добавим ещё одного получателя
					if ($newsItem['allow_mail'] && $cfg['sendToAuthor']) {
						$cfg['emails'] .= ',' . $newsItem['email'];
					}

					// Добавим в массив $_POST данные из новости
					foreach ($newsItem as $key => $value) {
						if ($key == 'title') {
							$value = stripslashes($value);
						}
						$_POST['news_' . $key] = $value;
					}
				}
			}

			// Добавляем данные из конфига DLE для возможности использовать в email сообщении
			$_POST['site_home_title']    = $config['home_title'];
			$_POST['site_http_home_url'] = $config['http_home_url'];
			$_POST['site_short_title']   = $config['short_title'];

			// Получаем массив обязательных полей
			$arRequired = getArray($cfg['required']);
			// Missing keys must fail exactly like empty submitted fields.
			// jquery.form serializes an unselected file input as attachments[]="".
			// Only discard empty placeholders; never accept paths/content from POST.
			if (isset($_POST['attachments'])) {
				$placeholder = $_POST['attachments'];
				if ($placeholder === '' || (is_array($placeholder) && count($placeholder) <= 10
					&& count(array_filter($placeholder, static function ($value) { return !is_string($value) || $value !== ''; })) === 0)) {
					unset($_POST['attachments']);
				}
			}
			foreach ($arRequired as $requiredField) {
				if (!array_key_exists($requiredField, $_POST)) $_POST[$requiredField] = '';
			}
			foreach ($_POST as $field => $value) {
				if (!preg_match('/^[a-zA-Z0-9_]+$/D', $field)) {
					$error = true;
					unset($_POST[$field]);
					continue;
				}
				if (is_array($value)) {
					$arrayFields = array_merge(getArray($cfg['selectFields']), getArray($cfg['checkboxFields']), getArray($cfg['radioFields']));
					if (!in_array($field, $arrayFields, true) || count($value) > 100 || count(array_filter($value, 'is_string')) !== count($value)) {
						$error = true;
						$_POST[$field] = '';
					} else {
						$_POST[$field] = array_map('trim', $value);
					}
				}
			}
			$post = $mailPost = $_POST;
			unset($post['csrfToken'], $post['formConfig'], $mailPost['csrfToken'], $mailPost['formConfig']);

			// Получаем массив, содержащий поля-селекты
			$arSelectFields = getArray($cfg['selectFields']);
			// Проверяем условия для селектов
			$post = setConditions($post, 'select', $arSelectFields, $tpl);

			// Получаем массив, содержащий поля-чекбоксы
			$arCheckboxFields = getArray($cfg['checkboxFields']);
			// Проверяем условия для чекбоксов
			$post = setConditions($post, 'checkbox', $arCheckboxFields, $tpl);

			// Получаем массив, содержащий поля-радиокнопки
			$arRadioFields = getArray($cfg['radioFields']);
			// Проверяем условия для радиокнопок
			$post = setConditions($post, 'radio', $arRadioFields, $tpl);

			// Проверяем условия для простых полей
			$post = setConditions($post, 'field', [], $tpl);

			// Проверяем обязательные поля
			foreach ($_POST as $k => $val) {
				// Поля csrfToken и formConfig нас не интересуют.
				if (in_array($k, ['csrfToken', 'formConfig'])) {
					continue;
				}
				// Остальные поля надо бы обработать
				if (!is_array($val)) {
					if (strlen((string)$val) > 20000) {
						$error = true;
						$val = '';
					}
					// DLE 21 работает только в UTF-8, convert_unicode() удалена из движка
					$val            = $parse->process(trim($val));
					$arSendMail[$k] = $val;
				}

				if (in_array($k, $arRequired) && ($val === '' || (is_array($val) && !count(array_filter($val, static function ($item) { return trim($item) !== ''; }))))) {
					// Если поле обязательное, но значение не установлено — значит произошла ошибка заполнения формы
					$error = true;
					// Показываем то, что заключено в теги ошибок.
					$tpl->copy_template = str_replace("[uf_error_{$k}]", '', $tpl->copy_template);
					$tpl->copy_template = str_replace("[/uf_error_{$k}]", '', $tpl->copy_template);
				} else {
					// Удалем теги, которые не должны показываться
					$tpl->copy_template = preg_replace("'\\[uf_error_{$k}\\](.*?)\\[/uf_error_{$k}\\]'is", '', $tpl->copy_template);
				}

				if (count($arSelectFields) > 0) {
					// Назначаем обработку полей селектов и добавляем данные в массив для отправки на почту
					$arSendMail = assignFiedls($k, $val, 'select', $arSelectFields, $parse, $tpl, $arSendMail);
					// setConditions($k, $val, 'select', $arSelectFields, $tpl);
				}

				if (count($arCheckboxFields) > 0) {
					// Назначаем обработку полей чекбоксов и добавляем данные в массив для отправки на почту
					$arSendMail = assignFiedls($k, $val, 'checkbox', $arCheckboxFields, $parse, $tpl, $arSendMail);
				}

				if (count($arRadioFields) > 0) {
					// Назначаем обработку полей чекбоксов и добавляем данные в массив для отправки на почту
					$arSendMail = assignFiedls($k, $val, 'radio', $arRadioFields, $parse, $tpl, $arSendMail);
					// setConditions($k, $val, 'radio', $arRadioFields,  $tpl);
				}

				if ($k == 'email') {
					if (in_array($k, $arRequired) && !validEmain($val)) {
						// Проверем email, если это требуется (в настройках задано обязательное поле с именем email)
						$error = true;
						$tpl->set('[uf_email_error]', '');
						$tpl->set('[/uf_email_error]', '');
					} else {
						$tpl->set_block("'\\[uf_email_error\\](.*?)\\[/uf_email_error\\]'si", '');
					}
				}

				// Заполняем поля нужными данными
				$tpl->copy_template = str_replace("{uf_field_{$k}}", uniformEscape(is_array($val) ? implode(', ', $val) : $val), $tpl->copy_template);
			}

			// Массив для прикрепленных файлов
			$arSendAttachments = [];
			// Массив для файлов, не прикреплённых по разным причинам
			$arNotAttachedFiles = [];
			// Массив с разрешенными типами файлов
			$arAllowedTypes = getArray($cfg['allowedFileTypes']);

			// Validate the real uploaded file, independently of MAX_FILE_SIZE.
			$arAllowedTypes = array_map('strtolower', $arAllowedTypes);
			$attachmentLimit = (int)$cfg['maxFileSize'] > 0 ? (int)$cfg['maxFileSize'] * 1024 : 5 * 1024 * 1024;
			$totalAttachmentBytes = 0;
			$attachmentCount = 0;
			foreach ($_FILES as $fileItem) {
				if (!is_array($fileItem) || !isset($fileItem['name'], $fileItem['error'], $fileItem['tmp_name'])) {
					$arNotAttachedFiles[] = 'Некорректное вложение';
					continue;
				}
				$indexes = is_array($fileItem['name']) ? array_keys($fileItem['name']) : [null];
				foreach ($indexes as $i) {
					$file = $i === null ? $fileItem : [
						'name' => $fileItem['name'][$i] ?? null,
						'tmp_name' => $fileItem['tmp_name'][$i] ?? null,
						'error' => $fileItem['error'][$i] ?? null,
					];
					if (isset($file['error']) && !is_array($file['error']) && (int)$file['error'] === UPLOAD_ERR_NO_FILE) continue;
					$attachmentCount++;
					$name = is_string($file['name'] ?? null) ? basename(str_replace('\\', '/', $file['name'])) : 'Некорректное вложение';
					if (!$cfg['allowAttachments'] || $attachmentCount > 10
						|| !uniformValidAttachment($file, $arAllowedTypes, $attachmentLimit)) {
						$arNotAttachedFiles[] = $name;
						continue;
					}
					$totalAttachmentBytes += filesize($file['tmp_name']);
					if ($totalAttachmentBytes > 20 * 1024 * 1024) {
						$arNotAttachedFiles[] = $name;
						continue;
					}
					$arSendAttachments[] = ['tmp_name' => $file['tmp_name'], 'name' => preg_replace('/[\x00-\x1F\x7F]/', '', $name)];
				}
			}

			$arSendMail['not_attached_files'] = implode(', ', $arNotAttachedFiles);

			if (count($arNotAttachedFiles)) {
				$error = true;
				$tpl->set('[attachments_error]', '');
				$tpl->set('[/attachments_error]', '');
				$tpl->set('{not_attached_files}', uniformEscape($arSendMail['not_attached_files']));
			} else {
				$tpl->set('{not_attached_files}', '');
				$tpl->set_block("'\\[attachments_error\\](.*?)\\[/attachments_error\\]'si", '');
			}

			if ($error == false && $rateError === '') {
				$rate = uniformRateLimit('submissions', $cfg['rateLimitSubmissions'], $cfg['rateLimitSubmissionWindow'], $cfg['rateLimitInterval']);
				if (!$rate['allowed']) {
					$rateError = uniformRateMessage($rate);
					$rateRetry = $rate['retry'];
				}
			}
			if ($rateError !== '') {
				if (!$error) $tpl->set_block("'\\[error\\](.*?)\\[/error\\]'si", '');
				$error = true;
			}
			if (!$error && $cfg['sendmail']) {
                $arMails = getArray($cfg['emails']);
                $uniformMailResult = ['attempted'=>0, 'sent'=>0, 'failed'=>0];
                if ($arMails) {
                    try { include (DLEPlugins::Check(ENGINE_DIR . '/modules/uniform/sendmail.php')); }
                    catch (Throwable $exception) { $uniformMailResult['failed']++; }
                }
                if (!$uniformMailResult['attempted'] || $uniformMailResult['failed']) {
                    $mailError = $uniformMailResult['sent']
                        ? 'Сообщение передано не всем получателям. Не повторяйте отправку сразу: свяжитесь с нами другим способом.'
                        : 'Не удалось передать сообщение почтовому обработчику. Попробуйте позже или свяжитесь с нами другим способом.';
                    $tpl->set_block("'\\[error\\](.*?)\\[/error\\]'si", '');
                    $error = true;
                }
            }
			if ($error == false) {

				// Если нет ошибок — значит форма отправлена удачно, нужно об этом сообщить
				$tpl->copy_template = preg_replace("'\\{uf_field_(.*?)\\}'si", '', $tpl->copy_template);
				$tpl->copy_template = preg_replace("'\\[uf_error_(.*?)\\](.*?)\\[/uf_error_(.*?)\\]'is", '', $tpl->copy_template);

				$tpl->set_block("'\\[form\\](.*?)\\[/form\\]'si", '');
				$tpl->set_block("'\\[error\\](.*?)\\[/error\\]'si", '');
				$tpl->set('[success]', '');
				$tpl->set('[/success]', '');

				unset($arSendMail);

			} else {
				// Нсли есть ошибки — выводим форму и ошибки
				$tpl->set_block("'\\[success\\](.*?)\\[/success\\]'si", '');
				$tpl->set('[form]', '');
				$tpl->set('[/form]', '');
				$tpl->set('[error]', '');
				$tpl->set('[/error]', '');
			}
			$tpl->copy_template = preg_replace("'\\[uf_default_value\\](.*?)\\[/uf_default_value\\]'is", '', $tpl->copy_template);
			// Добавляем пользовательские скрытые поля
			$hiddenInputs = addHiddenFields($arHidden, $_POST);

		} else {
			// Если в реквесте нет поста — значит форму только что открыли
			$tpl->set_block("'\\[uf_token_error\\](.*?)\\[/uf_token_error\\]'si", '');
			$tpl->set_block("'\\[success\\](.*?)\\[/success\\]'si", '');
			$tpl->set_block("'\\[error\\](.*?)\\[/error\\]'si", '');
			$tpl->set_block("'\\[attachments_error\\](.*?)\\[/attachments_error\\]'si", '');
			$tpl->set('[form]', '');
			$tpl->set('[/form]', '');
			$tpl->set('[uf_default_value]', '');
			$tpl->set('[/uf_default_value]', '');
			$tpl->set('{not_attached_files}', '');

			// Если пользователь авторизован — подставим его email в поле email.
			if ($member_id['user_group'] !== 5) {
				$tpl->copy_template = str_replace('{uf_field_email}', uniformEscape($member_id['email']), $tpl->copy_template);
			}
			$tpl->copy_template = preg_replace("'\\{uf_field_(.*?)\\}'si", '', $tpl->copy_template);
			$tpl->copy_template = preg_replace("'\\[uf_error_(.*?)\\](.*?)\\[/uf_error_(.*?)\\]'is", '', $tpl->copy_template);
			$tpl->copy_template = preg_replace("'\\[uf_email_error\\](.*?)\\[/uf_email_error\\]'is", '', $tpl->copy_template);
			$tpl->copy_template = preg_replace("'\\[uf_select_(.*?)\\](.*?)\\[/uf_select_(.*?)\\]'is", '', $tpl->copy_template);
			$tpl->copy_template = preg_replace("'\\[uf_checkbox_(.*?)\\](.*?)\\[/uf_checkbox_(.*?)\\]'is", '', $tpl->copy_template);
			$tpl->copy_template = preg_replace("'\\[uf_radio_(.*?)\\](.*?)\\[/uf_radio_(.*?)\\]'is", '', $tpl->copy_template);
			$tpl->copy_template = preg_replace("'\\[uf_field_(.*?)\\](.*?)\\[/uf_field_(.*?)\\]'is", '', $tpl->copy_template);

			// Добавляем пользовательские скрытые поля
			$requestFields = (isset($_REQUEST['fields']) && is_array($_REQUEST['fields'])) ? $_REQUEST['fields'] : [];
			$hiddenInputs = addHiddenFields($arHidden, $requestFields);
		}

		$tpl->set('{debug}', $debugTag);
		// Обрабатываем комментарии в шаблоне, которые не должны попасть в вывод
		$tpl->copy_template = preg_replace("'\\{\\*(.*?)\\*\\}'si", '', $tpl->copy_template);

		// Компилим шаблон и выводим результат
		$tpl->compile('uniform');
		$uniform = $tpl->result['uniform'];
		// Костыль, но подругому никак, нельзя выловить то, чего нет.
		// Поэтому приходится пробегать по тегам, оставшимся от работы функции setConditions
		$uniform = preg_replace("'\\[uf_field_(.*?)\\](.*?)\\[/uf_field_(.*?)\\]'is", '', $uniform);
		$uniform = preg_replace("'\\[uf_select_(.*?)\\](.*?)\\[/uf_select_(.*?)\\]'is", '', $uniform);
		$uniform = preg_replace("'\\[uf_checkbox_(.*?)\\](.*?)\\[/uf_checkbox_(.*?)\\]'is", '', $uniform);
		$uniform = preg_replace("'\\[uf_radio_(.*?)\\](.*?)\\[/uf_radio_(.*?)\\]'is", '', $uniform);

		if (!$cfg['nocache'] && !$isPost) {
			// Если нужно — создаём кеш
			create_cache($cfg['role'], $uniform, $cacheName . $config['skin'], true);
		}
		$tpl->clear();
	} else {
		http_response_code(404);
		$uniform = 'Форма временно недоступна';
	}
}
// Добавляем спцальный атрибут для корректной загрузки файлов
$multipart = ($cfg['allowAttachments']) ? 'enctype="multipart/form-data"' : '';

// Per-request fields are not part of the shared cached form body.
$requestFields = $isPost ? $_POST : (isset($_REQUEST['fields']) && is_array($_REQUEST['fields']) ? $_REQUEST['fields'] : []);
$hiddenInputs = addHiddenFields(getArray($cfg['hidden']), $requestFields);

// Добавляем инпут с указанием максимально возможного веса файла
$cfg['maxFileSize'] = (int)$cfg['maxFileSize'];
$maxFileSize        = ($cfg['maxFileSize'] > 0) ? $cfg['maxFileSize'] : 0;


// DLE 21: ajax-запросы идут через index.php?controller=ajax (прямой вызов engine/ajax/controller.php запрещён)
$form = '
	<form action="/index.php?controller=ajax&mod=uniform" data-uf-form method="POST" ' . $multipart . '>
	<input type="hidden" name="csrfToken" value="' . getToken($cacheName . $config['skin'] . $sessionId) . '">
	<input type="hidden" name="formConfig" value="' . $cfg['formConfig'] . '">
';
$form .= ($cfg['allowAttachments'] && $maxFileSize > 0) ? '<input type="hidden" name="MAX_FILE_SIZE" value="' . $maxFileSize * 1024 . '" />' : '';
$form .= $hiddenInputs;
if ($mailError !== '') {
    $form .= '<div class="uf-alert uf-alert-error" role="alert" data-uf-mail-error>' . uniformEscape($mailError) . '</div>';
}
if ($rateError !== '') {
	header('Retry-After: ' . (int)$rateRetry);
	$form .= '<div class="uf-alert uf-alert-error" role="alert" data-uf-rate-error>' . uniformEscape($rateError) . '</div>';
}
$form .= $uniform;
$form .= '</form>';
echo $form;
