sync = {
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
sync.nextQueue = function (queue, go_next_step) {
	if (typeof go_next_step === 'undefined')
	{
		var next_step = true;
	}
	
	if (go_next_step)
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
}