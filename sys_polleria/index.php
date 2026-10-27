<?php
/** Front controller - Pollería POS (MVC) */
require_once __DIR__ . '/core/bootstrap.php';

// Verificar instalación
try {
    DB::pdo();
    Setting::load();
    Migrate::run();
} catch (Throwable $e) {
    header('Location: ' . BASE_URL . '/install.php');
    exit;
}
date_default_timezone_set(cfg('zona_horaria', 'America/Lima'));

$route = $_GET['r'] ?? '';
if ($route === '') {
    $route = Auth::check() ? Auth::home() : 'auth/login';
}
$parts = explode('/', $route);
$ctrl = $parts[0] ?? '';
$act  = $parts[1] ?? 'index';

if (!preg_match('/^[a-z_]+$/', $ctrl) || !preg_match('/^[a-zA-Z_]+$/', $act)) {
    http_response_code(404);
    exit('Ruta inválida');
}

$class = ucfirst($ctrl) . 'Controller';
$file = BASE_PATH . '/app/controllers/' . $class . '.php';
if (!is_file($file)) {
    http_response_code(404);
    require BASE_PATH . '/app/views/errors/404.php';
    exit;
}
require_once $file;
$obj = new $class();
$method = $act . 'Action';
if (!method_exists($obj, $method)) {
    http_response_code(404);
    require BASE_PATH . '/app/views/errors/404.php';
    exit;
}
try {
    $obj->$method();
} catch (Throwable $e) {
    http_response_code(500);
    if (is_ajax()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'msg' => APP_DEBUG ? $e->getMessage() : 'Error interno']);
    } else {
        echo '<pre style="padding:20px;font-family:monospace">' . (APP_DEBUG ? e($e->getMessage() . "\n" . $e->getFile() . ':' . $e->getLine()) : 'Error interno del servidor') . '</pre>';
    }
}
