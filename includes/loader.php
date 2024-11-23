<?php
require_once(__DIR__ . '/config.php');
require_once(__DIR__ . '/globals.php');
require_once(__DIR__ . '/template_functions.php');
require_once(__DIR__ . '/functions.php');
require_once(__DIR__ . '/models.php');

if (((isset($GLOBALS['disable_auth']) && $GLOBALS['disable_auth'] !== false) && auth() === false))
	reauth();
