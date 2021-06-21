<?php /*

AUTOGENERATED.
About script: https://github.com/MashinaMashina/bx_1c_sync 

*/ ?><?php
/*
 * Далее ввести пароль
 */
$password = '1';


 /* app.php => */ ?><?php

class Syncapp
{
	protected $config;
	
	public function executeAjax($method, $data)
	{
		if ($this->getConfig('needauth'))
		{
			return $this->getFail('need_auth', 'Требуется авторизация');
		}
		
		$actions = new Actions($this);
		$method = 'action' . $method;
		
		if (method_exists($actions, $method))
		{
			return $actions->{$method}($data);
		}
		
		return $this->getFail('unsupported_method', 'Вызван неизвестный метод ' . htmlentities($method));
	}
	
	public function getPwdHash($password)
	{
		return md5($_SERVER['REMOTE_ADDR'] . $password . '~y1w%axZiYB8s%Gs{L6p8N2Vuup7~z');
	}
	
	public function checkPwdhash($pwdhash)
	{
		if (empty($pwdhash) or $pwdhash !== $this->getPwdHash($this->getConfig('password')))
		{
			return false;
		}
		
		return true;
	}
	
	public function getFail($code, $message)
	{
		return json_encode(['error' => true, 'code' => $code, 'message' => $message]);
	}
	
	public function getDone($code, $message)
	{
		return json_encode(['error' => false, 'code' => $code, 'message' => $message]);
	}
	
	public function setConfig($name, $value)
	{
		$this->config[$name] = $value;
	}
	
	public function getConfig($name)
	{
		return isset($this->config[$name]) ? $this->config[$name] : null;
	}
	
}
 /* actions.php => */ ?><?php

/*
 * В этом файле все AJAX методы
 */

class Actions
{
	protected $app;
	
	public function __construct($app)
	{
		$this->app = $app;
	}
	
	/*
	 * Удаляет все куки
	 *
	 * Удаление через сервер, так как удалить надо в том числе OnlyHttp куки
	 */
	public function actionClearCookie()
	{
		foreach ($_COOKIE as $key => $value)
		{
			setcookie($key, null, -1, '/');
		}
		
		return $this->app->getDone('success', '');
	}
	
	/*
	 * Проверка окружения
	 */
	public function actionCheckAuth()
	{
		foreach ($_COOKIE as $key => $value)
		{
			if (strpos($key, 'BITRIX_') === 0)
			{
				return $this->app->getFail('found_bitrix_auth', 'Найдены куки битрикс');
			}
		}
		
		return $this->app->getDone('success', '');
	}
	
}
$app = new Syncapp;

$app->setConfig('frontfile', basename(__FILE__));
$app->setConfig('password', $password);

$pwdhash = isset($_GET['pwdhash']) ? $_GET['pwdhash'] : null;
if ($app->getConfig('password') !== '' and ! $app->checkPwdhash($pwdhash))
{
	if (! empty($_POST['password']) and $_POST['password'] === $app->getConfig('password'))
	{
		header('Location: ' . $app->getConfig('frontfile') . '?pwdhash=' . $app->getPwdHash($_POST['password']));
		exit;
	}
	
	$app->setConfig('needauth', true);
}
else
{
	$app->setConfig('needauth', false);
}

$app->setConfig('pwdhash', htmlentities($pwdhash));

if (! empty($_POST) and ! empty($_GET['method']))
{
	$res = $app->executeAjax($_GET['method'], $_POST);
	
	die($res);
}

 /* page.html => */ ?><!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
		<meta name="description" content="">
		<meta name="author" content="">
		<title>Bitrix 1C Sync</title>
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/css/bootstrap.min.css" integrity="sha384-TX8t27EcRE3e/ihU7zmQxVncDAy5uIKz4rEkgIXeMed4M0jlfIDPvg6uqKI2xXr2" crossorigin="anonymous">
		<style>.main-wrapper {
	padding-top: 50px;
}
textarea.synclog {
	height: 500px;
	white-space: pre;
	font-size: 14px;
}
template {
	display: none;
}</style>
	</head>
	<body>
		<nav class="navbar navbar-expand-md navbar-dark bg-dark">
			<a class="navbar-brand" href="./">BX 1C Sync</a>
		</nav>
		<main role="main" class="container main-wrapper">
			<div class="row">
				<div class="col-md-8 order-md-2 mb-4">
					<h4 class="d-flex justify-content-between align-items-center mb-3">
						<span class="text-muted">Логи интеграции</span>
					</h4>
					<div class="form-group">
						<textarea id="sync_log" class="form-control synclog" placeholder="Пусто" readonly></textarea>
					</div>
					<button class="btn btn-secondary btn-sm float-md-left" onclick="syncapp.clearLog()">Очистить лог</button>
					<div class="float-md-right text-muted">
						<label for="sync_log_end" class="text-muted">Прокручивать в конец</label>
						<input type="checkbox" name="sync_log_end" id="sync_log_end" checked>
					</div>
				</div>
				<div class="col-md-4 order-md-1">
					<h4 class="mb-3">Рабочая область</h4>
					<div id="sync_alerts"></div>
					<div id="sync_workarea"></div>
				</div>
			</div>
		</main>
		
		<!-- Templates -->
		<template id="sync_alerts_tpl">
			<div class="alert alert-%type% alert-dismissible fade show" role="alert">
				%message%
				<button type="button" class="close" data-dismiss="alert" aria-label="Close">
					<span aria-hidden="true">&times;</span>
				</button>
			</div>
		</template>
		<template id="sync_login_tpl">
			<form method="POST">
				<div class="form-group">
					<label>Введите пароль
						<input name="password" class="form-control">
					</label>
				</div>
				<button class="btn btn-primary btn-sm" type="submit">Войти</button>
			</form>
		</template>
		
		<template id="sync_form_tpl">
			<form method="POST" enctype="multipart/form-data" onsubmit="event.preventDefault(); syncapp.startSync()">
				<div class="form-group">
					<label for="sync_path">Адрес выгрузки</label>
					<input type="text" name="sync_path" id="sync_path" class="form-control" placeholder="/bitrix/admin/1c_exchange.php" required value="/bitrix/admin/1c_exchange.php" />
				</div>
				
				<div class="form-group">
					<label for="sync_login">Логин пользователя для выгрузки</label>
					<input type="text" name="sync_login" id="sync_login" class="form-control" placeholder="1c_user" required >
				</div>
				
				<div class="form-group">
					<label for="sync_passwd">Пароль пользователя для выгрузки</label>
					<input type="password" name="sync_passwd" id="sync_passwd" class="form-control" placeholder="***" required autocomplete="on" />
				</div>
				
				<div class="form-group">
					<label for="sync_type">Выберите тип обмена</label>
					<select name="sync_type" id="sync_type" class="form-control" required onchange="syncapp.toggleDependencies(this); syncapp.updateSelect('#sync_operation')">
						<option value="catalog">[catalog] Импорт товаров</option>
						<option value="get_catalog">[get_catalog] Экспорт товаров</option>
						<option value="sale">[sale] Заказы</option>
						<option value="reference">[reference] Справочники</option>
					</select>
				</div>
				
				<div class="form-group">
					<label for="sync_operation">Выберите опрацию</label>
					<select name="sync_operation" id="sync_operation" class="form-control" required onchange="syncapp.toggleDependencies(this)">
					
						<option value="import" class="
							for-sync_type-catalog
							for-sync_type-sale
							for-sync_type-reference
						">[import] Импорт из файла на сайт</option>
						
						<option value="query" class="
							for-sync_type-sale
							for-sync_type-get_catalog
						" style="display:none">[query] Экспорт с сайта в файл</option>
						
						<option value="info" class="
							for-sync_type-sale
						" style="display:none">[info] Получение справочников магазина</option>
						
					</select>
				</div>
				
				<div class="form-group for-sync_operation-import">
					<label for="sync_file">Выберите файл</label>
					<input type="file" class="form-control-file" name="sync_file" id="sync_file">
				</div>
				
				<div class="form-group for-sync_operation-import">
					<label for="sync_zipfile">Имя файла в архиве</label>
					 <input type="text" class="form-control" name="sync_zipfile" id="sync_zipfile" >
				</div>
				
				<button class="btn btn-primary" type="submit">Выполнить</button>
			</form>
		</template>
		
		<!-- /Templates -->
		
		<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
		<script>/*!
 * jQuery Cookie Plugin v1.4.1
 * https://github.com/carhartl/jquery-cookie
 *
 * Copyright 2006, 2014 Klaus Hartl
 * Released under the MIT license
 */
(function (factory) {
	if (typeof define === 'function' && define.amd) {
		// AMD (Register as an anonymous module)
		define(['jquery'], factory);
	} else if (typeof exports === 'object') {
		// Node/CommonJS
		module.exports = factory(require('jquery'));
	} else {
		// Browser globals
		factory(jQuery);
	}
}(function ($) {

	var pluses = /\+/g;

	function encode(s) {
		return config.raw ? s : encodeURIComponent(s);
	}

	function decode(s) {
		return config.raw ? s : decodeURIComponent(s);
	}

	function stringifyCookieValue(value) {
		return encode(config.json ? JSON.stringify(value) : String(value));
	}

	function parseCookieValue(s) {
		if (s.indexOf('"') === 0) {
			// This is a quoted cookie as according to RFC2068, unescape...
			s = s.slice(1, -1).replace(/\\"/g, '"').replace(/\\\\/g, '\\');
		}

		try {
			// Replace server-side written pluses with spaces.
			// If we can't decode the cookie, ignore it, it's unusable.
			// If we can't parse the cookie, ignore it, it's unusable.
			s = decodeURIComponent(s.replace(pluses, ' '));
			return config.json ? JSON.parse(s) : s;
		} catch(e) {}
	}

	function read(s, converter) {
		var value = config.raw ? s : parseCookieValue(s);
		return $.isFunction(converter) ? converter(value) : value;
	}

	var config = $.cookie = function (key, value, options) {

		// Write

		if (arguments.length > 1 && !$.isFunction(value)) {
			options = $.extend({}, config.defaults, options);

			if (typeof options.expires === 'number') {
				var days = options.expires, t = options.expires = new Date();
				t.setMilliseconds(t.getMilliseconds() + days * 864e+5);
			}

			return (document.cookie = [
				encode(key), '=', stringifyCookieValue(value),
				options.expires ? '; expires=' + options.expires.toUTCString() : '', // use expires attribute, max-age is not supported by IE
				options.path    ? '; path=' + options.path : '',
				options.domain  ? '; domain=' + options.domain : '',
				options.secure  ? '; secure' : ''
			].join(''));
		}

		// Read

		var result = key ? undefined : {},
			// To prevent the for loop in the first place assign an empty array
			// in case there are no cookies at all. Also prevents odd result when
			// calling $.cookie().
			cookies = document.cookie ? document.cookie.split('; ') : [],
			i = 0,
			l = cookies.length;

		for (; i < l; i++) {
			var parts = cookies[i].split('='),
				name = decode(parts.shift()),
				cookie = parts.join('=');

			if (key === name) {
				// If second argument (value) is a function it's a converter...
				result = read(cookie, value);
				break;
			}

			// Prevent storing a cookie that we couldn't decode.
			if (!key && (cookie = read(cookie)) !== undefined) {
				result[name] = cookie;
			}
		}

		return result;
	};

	config.defaults = {};

	$.removeCookie = function (key, options) {
		// Must not alter options, thus extending a fresh object...
		$.cookie(key, '', $.extend({}, options, { expires: -1 }));
		return !$.cookie(key);
	};

}));</script>
		<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.3/dist/js/bootstrap.bundle.min.js"></script>
		<script>var syncapp = {
};

/*
 * Установка параметров
 */
syncapp.init = function(params) {
	syncapp.frontfile = params.frontfile;
	syncapp.pwdhash = params.pwdhash;
	syncapp.serverdir = params.serverdir;
	
	if (params.needauth)
	{
		syncapp.changeWorkarea('#sync_login_tpl', {})
	}
	else
	{
		syncapp.validate();
	}
}

/*
 * Запуск приложения обмена
 */
syncapp.start = function()
{
	syncapp.changeWorkarea('#sync_form_tpl', {});
}

/*
 * Переключение видимости зависимых элементов
 */
syncapp.toggleDependencies = function(select)
{
	var name = $(select).prop('name');
	
	$('option', select).each(function(){
		var option = this;
		$('.for-' + name + '-' + $(option).val()).hide();
	});
	
	$('.for-' + name + '-' + $(select).val()).show();
}

/*
 * Выбирает в select первый не скрытый вариант
 */
syncapp.updateSelect = function(select)
{
	$('option', select).each(function () {
		if ($(this).css('display') != 'none') {
			$(this).prop("selected", true);
			return false;
		}
	});
	$(select).change();
}

/*
 * Запуск операции обмена
 */
syncapp.startSync = function()
{
	var params = {};
	params.type = $('#sync_type').val();
	params.file = $('#sync_file')[0].files[0];
	params.login = $('#sync_login').val();
	params.passwd = $('#sync_passwd').val();
	params.path = $('#sync_path').val();
	params.zipfile = $('#sync_zipfile').val();
	params.operation = $('#sync_operation').val();
	
	if (params.zipfile === '' && params.file && params.file.type === 'application/x-zip-compressed')
	{
		params.zipfile = params.file.name.replace(/.zip/ig, '.xml');
		$('#sync_zipfile').val(params.zipfile);
	}
	
	sync.start(params);
}

/*
 * Доюавить информацию в лог
 */
syncapp.writeLog = function(message) {
	var logblock = $('#sync_log');
	
	logblock.val(logblock.val() + message + "\n");
	
	if ($('#sync_log_end').prop('checked')) {
		logblock.animate({scrollTop : logblock[0].scrollHeight - logblock[0].clientHeight}, 100);
	}
}

/*
 * Очистить лог
 */
syncapp.clearLog = function() {
	$('#sync_log').val('');
}

/*
 * Проверка окружения
 */
syncapp.validate = function() {
	syncapp.ajax('checkAuth', {}, function(result) {
		if (result.error)
		{
			var message = result.message;
			
			message += '<br><br>Авторизация в Битрикс будет искажать обмен. Для правильной работы откройте скрипт в режиме инкогнито.';
			
			syncapp.showAlert('danger', message);
		}
		
		syncapp.clearCookie();
	});
}

/*
 * Вывести сообщение пользователю
 * 
 * type - любой тип из bootstrap 4 alerts
 * https://getbootstrap.com/docs/4.0/components/alerts/
 */
syncapp.showAlert = function(type, message) {
	var template = $('#sync_alerts_tpl').html();
	
	template = template.replace(/%type%/g, type)
	template = template.replace(/%message%/g, message)
	
	$('#sync_alerts').append(template);
}

/*
 * Изменить контент на рабочем столе
 */
syncapp.changeWorkarea = function(templateId, fields) {
	var template = $(templateId).html();
	
	for (key in fields) {
		template = template.replace(new RegExp(k, 'g'), fields[k])
	}
	
	$('#sync_workarea').append(template);
}

/*
 * Ajax запрос на сервер
 */
syncapp.ajax = function(method, data, doneCallback, failCallback) {
	if (typeof failCallback === 'undefined') {
		var failCallback = function() {
			alert('Произошла ошибка при работе метода ' + method);
		}
	}
	
	data.isajax = 1;
	$.post(syncapp.frontfile + '?method=' + method + '&pwdhash=' + syncapp.pwdhash, data)
	.done(function(data) {
		var json = JSON.parse(data);
		
		if(doneCallback) doneCallback(json);
	})
	.fail(failCallback);
}

/*
 * Удаляет все куки, в том числе HttpOnly
 */
syncapp.clearCookie = function() {
	syncapp.ajax('clearCookie', {});
}
</script>
		<script>sync = {
	globalGetparams: {},
};

/*
 * Запуска обмена
 *
 * params.type
 * params.file
 * params.login
 * params.passwd
 * params.path
 * params.zipfile
 * params.operation
 */
sync.start = function (params) {
	sync.globalGetparams = {};
	
	Object.assign(sync, params);
	
	syncapp.writeLog('');
	syncapp.writeLog('Запуск обмена. Тип: '+sync.type+'; Путь: '+sync.path);
	syncapp.writeLog('');
	
	switch (params.operation)
	{
		case 'import':
			if (! sync.file)
			{
				syncapp.writeLog('Не выбран файл, импорт не возможен');
				return;
			}
		
			sync.nextQueue([
				sync.runCheckAuth,
				sync.runInit,
				sync.runFile,
				sync.runImport
			], false);
			break;
		
		case 'query':
			sync.nextQueue([
				sync.runCheckAuth,
				sync.runInit,
				sync.runQuery,
				sync.runQuery,
				sync.runQuery,
				sync.runQuery,
				sync.runQuery
			], false);
			break;
		
		case 'export':
			sync.nextQueue([
				sync.runCheckAuth,
				sync.runInit,
				sync.runExport
			], false);
			break;
		
		case 'info':
			sync.nextQueue([
				sync.runCheckAuth,
				sync.runInfo
			], false);
			break;
	}
}

/*
 * Переход к следующему шагу в очереди
 */
sync.nextQueue = function (queue, next_step) {
	if (typeof next_step === 'undefined')
	{
		var next_step = true;
	}
	
	if (next_step)
	{
		queue.shift();
	}
	
	if (queue.length)
	{
		setTimeout(function() {
			queue[0](queue);
		}, 1000);
	}
}

/*
 * Раскодирование ответа сервера
 *
 * Каждый параметр с новой строки
 * Возможно разделение параметра и значения символом "="
 */
sync.decodeAnswer = function (result) {
	var data = result.split("\n");
	
	for (var i = 0; i < data.length && i < 15; i++)
	{
		if (data[i].indexOf('=') == -1) continue;
		
		data[i] = data[i].split('=');
	}
	
	return data;
}

/*
 * Установить ошибку
 */
sync.setError = function (message) {
	if (typeof message == 'undefined' || message == '')
	{
		sync.hasError = false;
		return;
	}
	
	syncapp.writeLog(message);
	sync.hasError = true;
}

/*
 * Запуск операции Импорт
 */
sync.runImport = function (queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'import',
		filename: (sync.zipfile !== '' ? sync.zipfile : sync.file.name),
	}, sync.globalGetparams));
	
	syncapp.writeLog('Обмен данными. '+getparams);
	
	$.ajax({
		type: 'GET',
		url: sync.path + '?' + getparams,
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(result){
		var data = sync.decodeAnswer(result);
		
		if (data[0] === 'failure')
		{
			sync.setError('Ошибка обмена. '+data[1]);
			return;
		}
		
		if (data[0] === 'progress')
		{
			syncapp.writeLog('В процессе. ' + data[1]);
			syncapp.writeLog('');
			
			sync.nextQueue(queue, false);
			return;
		}
		
		if (data[0] === 'success')
		{
			syncapp.writeLog('Успешно. ' + (data.length > 1 ? data[1] : 'Без сообщения'));
			syncapp.writeLog('');
			
			sync.nextQueue(queue);
			return;
		}
		
		sync.setError('Ошибка сервера.');
		sync.setError(result);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}

/*
 * Запуск операции Инициализация обмена
 */
sync.runInit = function(queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'init',
		version: '3.1',
	}, sync.globalGetparams));
	
	syncapp.writeLog('Инициализация обмена. '+getparams);
	
	$.ajax({
		type: 'GET',
		url: sync.path + '?' + getparams,
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(result){
		var data = sync.decodeAnswer(result);
		
		if (data[0] === 'failure')
		{
			sync.setError('Ошибка. '+data[1]);
			return;
		}
		
		var message = 'Успешно. ';
		for (var i = 0; i < data.length; i++)
		{
			switch (data[i][0])
			{
				case 'zip':
					message += ' Требуется zip: ' + data[i][1];
					break;
				
				case 'file_limit':
					message += ' Максимальный размер файла: ' + data[i][1] +' байт';
					break;
				
				case 'version':
					message += ' Версия обмена: ' + data[i][1];
					break;
			}
		}
		
		syncapp.writeLog(message);
		syncapp.writeLog('');
		
		sync.nextQueue(queue);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}

/*
 * Запуск операции Проверка авторизации
 */
sync.runCheckAuth = function(queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'checkauth',
	}, sync.globalGetparams));
	
	syncapp.writeLog('Проверка авторизации. '+getparams);
	
	$.ajax({
		type: 'GET',
		url: sync.path + '?' + getparams,
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(result){
		var data = sync.decodeAnswer(result);
		
		if (data[0] !== 'success')
		{
			sync.setError('Не удалось авторизоваться. Проверьте логин и пароль');
			return;
		}
		
		// $.removeCookie(data[1]);
		$.cookie(data[1], data[2]);
		
		for (var i = 3; i < data.length; i++)
		{
			sync.globalGetparams[data[i][0]] = data[i][1];
		}
		
		syncapp.writeLog('Успешно');
		syncapp.writeLog('');
		
		sync.nextQueue(queue);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}

/*
 * Запуск операции загрузки файла на сервер
 */
sync.runFile = function (queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'file',
		filename: sync.file.name
	}, sync.globalGetparams))
	
	syncapp.writeLog('Загрузка файла. Размер: '+sync.file.size+'байт '+getparams);
	
	$.ajax({
		type: 'POST',
		url: sync.path + '?' + getparams,
		data: sync.file,
		processData: false,
		contentType: false,
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(result){
		var data = sync.decodeAnswer(result);
		
		if (data[0] !== 'success')
		{
			sync.setError('Ошибка загрузки файла. ' + data[1]);
			return;
		}
		
		syncapp.writeLog('Успешно');
		syncapp.writeLog('');
		
		sync.nextQueue(queue);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}

/*
 * Запуск операции получения информации
 */
sync.runInfo = function (queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'info'
	}, sync.globalGetparams))
	
	syncapp.writeLog('Получение информации по обмену. '+getparams);
	
	$.ajax({
		type: 'GET',
		url: sync.path + '?' + getparams,
		dataType: 'binary',
		xhrFields: {
			'responseType': 'blob'
		},
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(blob){
		var link = document.createElement('a');
		link.href = window.URL.createObjectURL(blob);
		link.download = 'download.xml';
		link.click();
		
		syncapp.writeLog('Успешно');
		syncapp.writeLog('');
		
		sync.nextQueue(queue);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}

/*
 * Запуск операции получения выгрузки
 */
sync.runQuery = function (queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'query'
	}, sync.globalGetparams))
	
	syncapp.writeLog('Получение информации по обмену. '+getparams);
	
	$.ajax({
		type: 'GET',
		url: sync.path + '?' + getparams,
		dataType: 'binary',
		xhrFields: {
			'responseType': 'blob'
		},
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(blob){
		var link = document.createElement('a');
		link.href = window.URL.createObjectURL(blob);
		link.download = 'download.xml';
		link.click();
		
		syncapp.writeLog('Успешно');
		syncapp.writeLog('');
		
		sync.nextQueue(queue);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}

/*
 * Запуск операции получения выгрузки
 */
sync.runExport = function (queue) {
	sync.hasError = false;
	
	var getparams = $.param(Object.assign({
		type: sync.type,
		mode: 'export'
	}, sync.globalGetparams))
	
	syncapp.writeLog('Получение информации по обмену. '+getparams);
	
	$.ajax({
		type: 'GET',
		url: sync.path + '?' + getparams,
		dataType: 'binary',
		xhrFields: {
			'responseType': 'blob'
		},
		headers: {
			'Authorization': 'Basic '+btoa(sync.login+':'+sync.passwd)
		}
	}).done(function(blob){
		var link = document.createElement('a');
		link.href = window.URL.createObjectURL(blob);
		link.download = 'download.xml';
		link.click();
		
		syncapp.writeLog('Успешно');
		syncapp.writeLog('');
		
		sync.nextQueue(queue);
	})
	.fail(function(xhr, status, error){
		syncapp.writeLog('Произошла ошибка. Не удалось отправить запрос, подробную информацию смотрите в консоли браузера');
		syncapp.writeLog('status: ' + status + '; error message: ' + error);
		syncapp.writeLog('');
	});
}</script>
		<script>
			syncapp.init({
				frontfile: './<?=$app->getConfig("frontfile")?>',
				serverdir: '/',
				pwdhash: '<?=$app->getConfig("pwdhash")?>',
				needauth: '<?=$app->getConfig("needauth")?>'
			});
		</script>
		
		
		<?php if ($app->getConfig('password') === ''): ?>
			<script>syncapp.showAlert('danger', 'Укажите пароль в текущем файле');</script>
		<?php endif; ?>
		
		<?php if (isset($_POST['password'])): ?>
			<script>syncapp.showAlert('danger', 'Не верный пароль');</script>
		<?php endif; ?>
		
		<?php if (! $app->getConfig("needauth")): ?>
			<script>syncapp.start();</script>
		<?php endif; ?>
		
	</body>
</html><?php
