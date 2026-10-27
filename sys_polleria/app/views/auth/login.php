<?php
$empresa = cfg('empresa_nombre', APP_NAME);
$logo = cfg('empresa_logo');
$brand = preg_match('/^#[0-9a-fA-F]{6}$/', cfg('color_principal', '#F97316')) ? cfg('color_principal', '#F97316') : '#F97316';
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Iniciar sesión · <?= e($empresa) ?></title>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>:root{--brand:<?= e($brand) ?>}</style>
</head>
<body>
<div class="login-wrap">
  <section class="login-art">
    <span class="blob b1"></span><span class="blob b2"></span><span class="blob b3"></span>
    <div class="la-top">
      <div class="la-brand">
        <div class="sb-logo"><?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt=""><?php else: ?><i class="bi bi-fire"></i><?php endif; ?></div>
        <span style="color:#7C2D12"><?= e($empresa) ?></span>
      </div>
    </div>
    <div class="la-float d-none d-xl-block">
      <div class="d-flex align-items-center gap-2 mb-2"><span class="avatar" style="background:#10B981"><i class="bi bi-graph-up-arrow"></i></span><div class="small fw-bold text-muted">Ventas de hoy</div></div>
      <div class="v"><?= e(money(3248.5)) ?></div>
      <div class="small text-success fw-bold mt-1"><i class="bi bi-arrow-up-right"></i> +12.4% vs. ayer</div>
      <div class="d-flex align-items-end gap-1 mt-3" style="height:46px">
        <?php foreach ([40, 62, 48, 80, 66, 92, 74] as $h): ?><div style="flex:1;height:<?= $h ?>%;background:linear-gradient(180deg,var(--brand),#FDBA74);border-radius:6px"></div><?php endforeach; ?>
      </div>
    </div>
    <div class="la-mid">
      <h1>Gestiona tu pollería<br>con <span>sabor y control</span></h1>
      <p>Punto de venta, mesas, cocina, delivery, inventario y reportes en un solo lugar. Rápido, claro y pensado para el día a día de tu negocio.</p>
      <div class="la-feats">
        <div class="la-feat"><i class="bi bi-shop-window"></i>Punto de venta ágil</div>
        <div class="la-feat"><i class="bi bi-fire"></i>Pantalla de cocina</div>
        <div class="la-feat"><i class="bi bi-bicycle"></i>Control de delivery</div>
        <div class="la-feat"><i class="bi bi-bar-chart-line"></i>Reportes en tiempo real</div>
      </div>
    </div>
    <div class="la-bot">© <?= date('Y') ?> <?= e(APP_NAME) ?> · v<?= APP_VERSION ?></div>
  </section>

  <section class="login-form-side">
    <div class="login-card">
      <div class="d-lg-none d-flex align-items-center gap-2 mb-4">
        <div class="sb-logo"><?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt=""><?php else: ?><i class="bi bi-fire"></i><?php endif; ?></div>
        <b><?= e($empresa) ?></b>
      </div>
      <span class="bsoft brand mb-3"><i class="bi bi-shield-lock"></i> Acceso seguro</span>
      <h3 class="mb-1">¡Bienvenido de nuevo!</h3>
      <p class="text-muted mb-4">Ingresa tus credenciales para continuar.</p>

      <?php foreach (flashes() as [$ft, $fm]): ?><div class="alert border-0 py-2 d-flex align-items-center gap-2" style="border-radius:12px;background:<?= $ft === 'success' ? '#ECFDF5;color:#047857' : '#FEF2F2;color:#B91C1C' ?>"><i class="bi bi-<?= $ft === 'success' ? 'check-circle-fill' : 'info-circle-fill' ?>"></i><?= e($fm) ?></div><?php endforeach; ?>
      <?php if ($error): ?><div class="alert alert-danger d-flex align-items-center gap-2 py-2 border-0" style="border-radius:12px;background:#FEF2F2;color:#B91C1C"><i class="bi bi-exclamation-triangle-fill"></i><?= e($error) ?></div><?php endif; ?>

      <form method="post" autocomplete="on">
        <?= csrf_field() ?>
        <label class="form-label">Usuario</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input type="text" class="form-control" name="usuario" value="<?= e($usuario) ?>" placeholder="Tu usuario" required autofocus autocomplete="username">
        </div>
        <label class="form-label">Contraseña</label>
        <div class="input-group mb-3">
          <span class="input-group-text"><i class="bi bi-key"></i></span>
          <input type="password" class="form-control" id="pw" name="clave" placeholder="••••••••" required autocomplete="current-password">
          <span class="input-group-text pw-toggle" data-pw-toggle="#pw"><i class="bi bi-eye"></i></span>
        </div>
        <button class="btn btn-brand btn-lg w-100 mt-2" type="submit">Ingresar al sistema <i class="bi bi-arrow-right ms-1"></i></button>
      </form>
      <div class="text-center text-muted small mt-4"><i class="bi bi-telephone me-1"></i><?= e(cfg('empresa_telefono')) ?> &nbsp;·&nbsp; <?= e(cfg('empresa_email')) ?></div>
    </div>
  </section>
</div>
<script>
document.querySelectorAll('[data-pw-toggle]').forEach(function(b){b.addEventListener('click',function(){var i=document.querySelector(b.dataset.pwToggle);i.type=i.type==='password'?'text':'password';b.querySelector('i').className='bi '+(i.type==='password'?'bi-eye':'bi-eye-slash');});});
</script>
</body>
</html>
