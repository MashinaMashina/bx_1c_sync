var syncapp = {
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
 * Событие на выборе файла
 */
syncapp.onFileSelect = function() {
	if ($('#sync_file')[0].files[0].type === 'application/x-zip-compressed') {
		$('#sync_zipfile_block').show();
	} else {
		$('#sync_zipfile_block').hide();
	}
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
