<?php
define('APP_ROOT', '/');
define('DEBUG', false);
define('DISABLE_ERROR_EMAILS', false);

//Database
define('DB_HOST', 'localhost');
define('DB_USERNAME', '');
define('DB_PASSWORD', '');
define('DB_DATABASE', '');
define('DB_TYPE', 'sqlsrv');
define('DB_PORT', 1433);
define('DB_TRUST_CERT', 1);
define('DB_CHARSET', 'utf8mb4');
////Audit DB
define('AUDIT_DB_TABLES', ['']);
define('AUDIT_DB_ENABLE_DEBUG_TIMES', false);

//LDAP
DEFINE('LDAP_SERVER', '');
DEFINE('LDAP_USERNAME', '');
DEFINE('LDAP_PASSWORD', '');
DEFINE('LDAP_BASE_DN', '');
