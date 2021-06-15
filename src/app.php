<?php

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