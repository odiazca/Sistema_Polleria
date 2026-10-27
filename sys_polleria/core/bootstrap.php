<?php
require_once dirname(__DIR__) . '/config/config.php';

if (APP_DEBUG) { ini_set('display_errors', '1'); error_reporting(E_ALL); }
else { ini_set('display_errors', '0'); }

session_name('POLLERIA_SID');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'path' => BASE_URL ?: '/']);
if (session_status() === PHP_SESSION_NONE) session_start();

require_once __DIR__ . '/helpers.php';

spl_autoload_register(function (string $class) {
    foreach ([BASE_PATH . '/core/', BASE_PATH . '/app/controllers/'] as $dir) {
        $f = $dir . $class . '.php';
        if (is_file($f)) { require_once $f; return; }
    }
});

mb_internal_encoding('UTF-8');
