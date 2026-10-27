<?php
/** Instalador - Pollería POS */
require_once __DIR__ . '/core/bootstrap.php';

$errors = [];
$done = false;
$installed = false;

try {
    $pdo = DB::pdo();
    $installed = (int)$pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0;
} catch (Throwable $e) { $installed = false; }

$checks = [
    'PHP 8.0 o superior (' . PHP_VERSION . ')' => version_compare(PHP_VERSION, '8.0.0', '>='),
    'Extensión PDO MySQL' => extension_loaded('pdo_mysql'),
    'Extensión mbstring' => extension_loaded('mbstring'),
    'Extensión ZIP (copias con imágenes)' => class_exists('ZipArchive'),
    'Carpeta uploads/ con permisos de escritura' => is_writable(BASE_PATH . '/uploads'),
    'Carpeta backups/ con permisos de escritura' => is_writable(BASE_PATH . '/backups'),
];

if (!$installed && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre'] ?? '');
    $usuario = trim($_POST['usuario'] ?? '');
    $clave = (string)($_POST['clave'] ?? '');
    if ($nombre === '' || $usuario === '' || strlen($clave) < 6) $errors[] = 'Complete el administrador (clave mínima de 6 caracteres).';
    if (!$errors) {
        try {
            $srv = DB::server();
            $srv->exec('CREATE DATABASE IF NOT EXISTS `' . DB_NAME . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');
            $srv->exec('USE `' . DB_NAME . '`');
            DB::runScript(file_get_contents(BASE_PATH . '/database/schema.sql'), $srv);
            DB::runScript(file_get_contents(BASE_PATH . '/database/seed_base.sql'), $srv);
            if (!empty($_POST['catalogo'])) { DB::runScript(file_get_contents(BASE_PATH . '/database/seed_catalog.sql'), $srv); $seeded = true; }
            if (!empty($seeded)) { $_SESSION = []; Setting::load(true); Receta::recalcTodos(); }
            $st = $srv->prepare('INSERT INTO usuarios (rol_id, nombre, usuario, clave, estado) VALUES (1, ?, ?, ?, 1)');
            $st->execute([$nombre, $usuario, password_hash($clave, PASSWORD_DEFAULT)]);
            $_SESSION = [];
            Setting::load(true);
            if (!empty($_POST['demo'])) {
                if (empty($seeded)) DB::runScript(file_get_contents(BASE_PATH . '/database/seed_catalog.sql'), $srv);
                Receta::recalcTodos();
                Demo::run(30);
            }
            $done = true;
        } catch (Throwable $e) {
            $errors[] = 'Error durante la instalación: ' . $e->getMessage();
        }
    }
}
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Instalación · <?= e(APP_NAME) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<style>
 body{font-family:'Plus Jakarta Sans',system-ui,sans-serif;background:linear-gradient(135deg,#FFF7ED,#FEF3C7 55%,#FFFBEB);min-height:100vh;display:flex;align-items:center;justify-content:center;padding:24px}
 .box{background:#fff;border-radius:24px;box-shadow:0 30px 80px -20px rgba(234,88,12,.25);max-width:620px;width:100%;padding:36px}
 .logo{width:64px;height:64px;border-radius:18px;background:linear-gradient(135deg,#FB923C,#F97316);color:#fff;display:grid;place-items:center;font-size:30px;box-shadow:0 12px 24px -8px rgba(249,115,22,.6)}
 .btn-brand{background:linear-gradient(135deg,#FB923C,#EA580C);border:0;color:#fff;font-weight:700;border-radius:14px;padding:12px 22px}
 .btn-brand:hover{color:#fff;filter:brightness(1.05)}
 .form-control{border-radius:12px;padding:10px 14px}
</style>
</head>
<body>
<div class="box">
  <div class="d-flex align-items-center gap-3 mb-4">
    <div class="logo"><i class="bi bi-fire"></i></div>
    <div><h4 class="fw-bold mb-0">Instalación del sistema</h4><div class="text-muted small"><?= e(APP_NAME) ?> · v<?= APP_VERSION ?></div></div>
  </div>

<?php if ($done): ?>
  <div class="alert alert-success"><i class="bi bi-check-circle-fill me-2"></i>¡Instalación completada! La base de datos <b><?= e(DB_NAME) ?></b> fue creada.</div>
  <p class="text-muted">Ingrese con el usuario administrador que acaba de crear.<?= !empty($_POST['demo']) ? '<br>Usuarios demo: <code>cajero / cajero123</code>, <code>mozo / mozo123</code>, <code>cocina / cocina123</code>.' : '' ?></p>
  <a class="btn btn-brand w-100" href="<?= e(url('auth/login')) ?>">Ir al inicio de sesión <i class="bi bi-arrow-right ms-1"></i></a>
<?php elseif ($installed): ?>
  <div class="alert alert-info"><i class="bi bi-info-circle-fill me-2"></i>El sistema ya está instalado. Para empezar de cero use el módulo <b>Copia de seguridad → Resetear sistema</b>.</div>
  <a class="btn btn-brand w-100" href="<?= e(url('auth/login')) ?>">Ir al inicio de sesión</a>
<?php else: ?>
  <?php foreach ($errors as $er): ?><div class="alert alert-danger"><?= e($er) ?></div><?php endforeach; ?>
  <h6 class="fw-bold text-uppercase text-muted small mb-2">Requisitos</h6>
  <ul class="list-unstyled small mb-4">
    <?php foreach ($checks as $label => $ok): ?>
      <li class="mb-1"><i class="bi <?= $ok ? 'bi-check-circle-fill text-success' : 'bi-x-circle-fill text-danger' ?> me-2"></i><?= e($label) ?></li>
    <?php endforeach; ?>
  </ul>
  <h6 class="fw-bold text-uppercase text-muted small mb-2">Servidor MySQL</h6>
  <div class="small text-muted mb-4"><code><?= e(DB_USER) ?>@<?= e(DB_HOST) ?>:<?= DB_PORT ?></code> → base de datos <code><?= e(DB_NAME) ?></code> (se creará automáticamente)</div>
  <form method="post">
    <h6 class="fw-bold text-uppercase text-muted small mb-2">Usuario administrador</h6>
    <div class="row g-3 mb-3">
      <div class="col-12"><label class="form-label small fw-semibold">Nombre completo</label><input class="form-control" name="nombre" value="Administrador" required></div>
      <div class="col-6"><label class="form-label small fw-semibold">Usuario</label><input class="form-control" name="usuario" value="admin" required></div>
      <div class="col-6"><label class="form-label small fw-semibold">Contraseña</label><input class="form-control" type="password" name="clave" value="admin123" minlength="6" required></div>
    </div>
    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="catalogo" id="cat" checked><label class="form-check-label" for="cat">Cargar catálogo inicial de pollería (productos, categorías, mesas, insumos)</label></div>
    <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" name="demo" id="demo" checked><label class="form-check-label" for="demo">Cargar datos de demostración (ventas de 30 días para ver los gráficos)</label></div>
    <button class="btn btn-brand w-100" type="submit"><i class="bi bi-rocket-takeoff me-2"></i>Instalar sistema</button>
  </form>
<?php endif; ?>
</div>
</body>
</html>
