<?php
if (session_status() !== PHP_SESSION_ACTIVE)
	session_start();
require_once(__DIR__ . '/models.php');
require_once(__DIR__ . '/config.php');
if (defined('ERROR_ENABLE_DESTINATION') && ERROR_ENABLE_DESTINATION !== '')
	require_once(__DIR__ . '/error_handler.php');
require_once(__DIR__ . '/globals.php');
require_once(__DIR__ . '/template_functions.php');
require_once(__DIR__ . '/functions.php');

if (((isset($GLOBALS['disable_auth']) && $GLOBALS['disable_auth'] !== true) && auth() === false))
	reauth();
