<?php
define('APP_ROOT', '/');
define('DEBUG', false);
define('DISABLE_ERROR_EMAILS', false);
define('REQUIRE_JAVASCRIPT', false); //Setting to 'true' causes a full page pop up, preventing site usage, if JS is disabled.

//Database
define('DB_HOST', 'localhost');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_DATABASE', '');
define('DB_TYPE', 'sqlsrv');
define('DB_PORT', 1433);
define('DB_TRUST_CERT', 1);
define('DB_CHARSET', 'utf8mb4');

//LDAP
DEFINE('LDAP_SERVER', '');
DEFINE('LDAP_USERNAME', '');
DEFINE('LDAP_PASSWORD', '');
DEFINE('LDAP_BASE_DN', '');
