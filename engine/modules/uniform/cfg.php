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

/** @global array $arConf */
$cfg = [
	'formConfig'       => !empty($formConfig) ? $formConfig
		: ((isset($_REQUEST['formConfig'])) ? $_REQUEST['formConfig'] : 'feedback'),
	'role'             => !empty($role) ? $role : ((isset($arConf['role'])) ? $arConf['role'] : 'feedback'),
	'templateFolder'   => !empty($templateFolder) ? $templateFolder
		: ((isset($arConf['templateFolder'])) ? $arConf['templateFolder'] : 'feedback'),
	'nocache'          => !empty($nocache) ? $nocache : ((isset($arConf['nocache'])) ? $arConf['nocache'] : false),
	'debug'            => !empty($debug) ? $debug : ((isset($arConf['debug'])) ? $arConf['debug'] : false),
	'required'         => !empty($required) ? $required : ((isset($arConf['required'])) ? $arConf['required'] : false),
	'hidden'           => !empty($hidden) ? $hidden : ((isset($arConf['hidden'])) ? $arConf['hidden'] : false),
	'sendmail'         => !empty($sendmail) ? $sendmail : ((isset($arConf['sendmail'])) ? $arConf['sendmail'] : false),
	'emails'           => !empty($emails) ? $emails : ((isset($arConf['emails'])) ? $arConf['emails'] : false),
	'selectFields'     => !empty($selectFields) ? $selectFields
		: ((isset($arConf['selectFields'])) ? $arConf['selectFields'] : false),
	'checkboxFields'   => !empty($checkboxFields) ? $checkboxFields
		: ((isset($arConf['checkboxFields'])) ? $arConf['checkboxFields'] : false),
	'radioFields'      => !empty($radioFields) ? $radioFields
		: ((isset($arConf['radioFields'])) ? $arConf['radioFields'] : false),
	'sendAsPlain'      => !empty($sendAsPlain) ? true : ((isset($arConf['sendAsPlain'])) ? true : false),
	'sendToAuthor'     => !empty($sendToAuthor) ? true : ((isset($arConf['sendToAuthor'])) ? true : false),
	'sendToSender'     => !empty($sendToSender) ? true : ((isset($arConf['sendToSender'])) ? true : false),
	'allowAttachments' => !empty($allowAttachments) ? true : ((isset($arConf['allowAttachments'])) ? true : false),
	'maxFileSize'      => !empty($maxFileSize) ? $maxFileSize
		: ((isset($arConf['maxFileSize'])) ? $arConf['maxFileSize'] : false),
	'allowedFileTypes' => !empty($allowedFileTypes) ? $allowedFileTypes
		: ((isset($arConf['allowedFileTypes'])) ? $arConf['allowedFileTypes'] : false),
	'parseSendMail'    => !empty($parseSendMail) ? $parseSendMail
		: ((isset($arConf['parseSendMail'])) ? $arConf['parseSendMail'] : false),
	// Trusted form configuration only; never read limiter settings from a request.
	'rateLimitRequests' => max(1, min(1000, (int)($arConf['rateLimitRequests'] ?? 60))),
	'rateLimitRequestWindow' => max(1, min(3600, (int)($arConf['rateLimitRequestWindow'] ?? 300))),
	'rateLimitSubmissions' => max(1, min(1000, (int)($arConf['rateLimitSubmissions'] ?? 5))),
	'rateLimitSubmissionWindow' => max(1, min(3600, (int)($arConf['rateLimitSubmissionWindow'] ?? 600))),
	'rateLimitInterval' => max(0, min(3600, (int)($arConf['rateLimitInterval'] ?? 15))),
];
