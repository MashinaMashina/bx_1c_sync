<?php

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