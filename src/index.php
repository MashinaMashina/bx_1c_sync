<?php
/*
 * Далее ввести пароль
 */
$password = '';


require('./app.php');
require('./actions.php');
$app = new Syncapp;

$app->setConfig('frontfile', basename(__FILE__));
$app->setConfig('password', $password);

$pwdhash = isset($_GET['pwdhash']) ? $_GET['pwdhash'] : null;
if (! $app->checkPwdhash($pwdhash))
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

require('./page.html');
