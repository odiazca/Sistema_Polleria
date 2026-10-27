<?php
/**
 * Configuración general - Sistema TPV Pollería
 * Servidor MySQL: Laragon (localhost / root / sin clave)
 */
define('BASE_PATH', dirname(__DIR__));

define('DB_HOST', 'localhost');
define('DB_PORT', 3306);
define('DB_NAME', 'sys_polleria');
define('DB_USER', 'root');
define('DB_PASS', '');

define('APP_NAME', 'Pollería POS');
define('APP_VERSION', '2.6.0');
define('APP_DEBUG', true);

date_default_timezone_set('America/Lima');

$__script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
define('BASE_URL', rtrim($__script, '/'));
unset($__script);
