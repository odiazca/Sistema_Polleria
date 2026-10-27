<?php
/** @var string $content */
$mods = Auth::modules();
$groups = [];
foreach ($mods as $key => $m) { if (Auth::can($key)) $groups[$m[2]][$key] = $m; }
$user = Auth::user();
$empresa = cfg('empresa_nombre', APP_NAME);
$logo = cfg('empresa_logo');
$brand = preg_match('/^#[0-9a-fA-F]{6}$/', cfg('color_principal', '#F97316')) ? cfg('color_principal', '#F97316') : '#F97316';
$cajaAbierta = DB::one("SELECT id, fecha_apertura FROM cajas WHERE usuario_id = ? AND estado = 'abierta' ORDER BY id DESC LIMIT 1", [Auth::id()]);
$alertas = 0;
if (Auth::can('insumos')) {
    $alertas += (int)DB::val('SELECT COUNT(*) FROM insumos WHERE estado=1 AND stock <= stock_minimo');
    $alertas += (int)DB::val('SELECT COUNT(*) FROM productos WHERE estado=1 AND stock_control=1 AND stock <= stock_minimo');
}
$cocinaPend = Auth::can('cocina') ? (int)DB::val("SELECT COUNT(DISTINCT pedido_id) FROM pedido_detalle d JOIN pedidos p ON p.id=d.pedido_id WHERE p.estado='abierto' AND d.estado IN ('pendiente','preparando')") : 0;
$qrPend = Auth::can('mesas') ? Qr::pendientes() : 0;
$resPend = Auth::can('reservas') ? Reservas::pendientes() : 0;
$crmPend = Auth::can('crm') ? Crm::pendientesHoy() : 0;
$crumb = $mods[$module][2] ?? 'Principal';
$flashes = flashes();
?><!doctype html>
<html lang="es">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title) ?> · <?= e($empresa) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>:root{--brand:<?= e($brand) ?>}</style>
<script>
  window.APP = {
    base: <?= json_encode(BASE_URL) ?>, csrf: <?= json_encode(csrf_token()) ?>,
    money: { symbol: <?= json_encode(cfg('moneda_simbolo', 'S/')) ?>, pos: <?= json_encode(cfg('moneda_posicion', 'antes')) ?>, dec: <?= (int)cfg('decimales', 2) ?>, decSep: <?= json_encode(cfg('sep_decimal', '.')) ?>, thSep: <?= json_encode(cfg('sep_miles', ',')) ?> },
    flashes: <?= json_encode($flashes, JSON_UNESCAPED_UNICODE) ?>
  };
</script>
<script>document.addEventListener('DOMContentLoaded',function(){ try{ if(localStorage.getItem('sbc')==='1' && innerWidth>991) document.body.classList.add('sb-collapsed'); }catch(e){} });</script>
</head>
<body>
<div class="app">
  <aside class="sidebar" id="sidebar">
    <div class="sb-brand">
      <div class="sb-logo"><?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" alt=""><?php else: ?><i class="bi bi-fire"></i><?php endif; ?></div>
      <div style="min-width:0">
        <div class="sb-title text-truncate"><?= e($empresa) ?></div>
        <div class="sb-sub">🍗 Pollos a la brasa · POS</div>
      </div>
    </div>
    <?php if (Auth::can('pos')): ?>
    <div class="sb-cta-wrap">
      <a href="<?= e(url('pos/index')) ?>" class="sb-cta" title="Nueva venta"><span class="ic"><i class="bi bi-plus-lg"></i></span><span class="tx">Nueva venta</span><i class="bi bi-arrow-right-short go"></i></a>
    </div>
    <?php endif; ?>
    <nav class="sb-nav">
      <?php
      $mc = ['dashboard' => '#F97316', 'pos' => '#EA580C', 'mesas' => '#8B5CF6', 'reservas' => '#EC4899', 'cocina' => '#EF4444', 'delivery' => '#0D9488', 'ventas' => '#3B82F6', 'caja' => '#16A34A',
             'productos' => '#F59E0B', 'categorias' => '#EC4899', 'clientes' => '#0EA5E9', 'repartidores' => '#14B8A6', 'insumos' => '#B45309', 'recetas' => '#F97316',
             'compras' => '#6366F1', 'proveedores' => '#64748B', 'gastos' => '#E11D48', 'reportes' => '#7C3AED', 'facturacion' => '#059669', 'crm' => '#E11D48', 'campanas' => '#16A34A', 'asistente' => '#7C3AED', 'chat' => '#6366F1', 'usuarios' => '#2563EB', 'empresa' => '#475569', 'backup' => '#0891B2'];
      $gi = ['Principal' => 'bi-house-heart-fill', 'Ventas' => 'bi-bag-heart-fill', 'Catálogo' => 'bi-journal-bookmark-fill', 'Inventario' => 'bi-boxes', 'Finanzas' => 'bi-piggy-bank-fill', 'CRM' => 'bi-heart-pulse-fill', 'Inteligencia' => 'bi-stars', 'Administración' => 'bi-gear-wide-connected'];
      foreach ($groups as $g => $items): $hasActive = isset($items[$module]); ?>
        <div class="sb-sec <?= $hasActive ? 'has-active' : '' ?>" data-g="<?= e($g) ?>">
          <button type="button" class="sb-group"><i class="bi <?= e($gi[$g] ?? 'bi-grid') ?> gi"></i><span><?= e($g) ?></span><i class="bi bi-chevron-down chev"></i></button>
          <div class="sb-items">
          <?php foreach ($items as $key => $m): ?>
            <a class="sb-link <?= $module === $key ? 'active' : '' ?>" style="--c:<?= $mc[$key] ?? '#F97316' ?>" href="<?= e(url($m[3])) ?>" title="<?= e($m[0]) ?>">
              <div class="ti"><i class="bi <?= e($m[1]) ?>"></i></div><span class="lb"><?= e($m[0]) ?></span>
              <?php if ($key === 'mesas'): ?><span class="sb-badge sb-qr" style="background:var(--bad);<?= $qrPend ? '' : 'display:none' ?>"><?= $qrPend ?></span><?php endif; ?>
              <?php if ($key === 'crm' && $crmPend): ?><span class="sb-badge" style="background:var(--bad)"><?= $crmPend ?></span><?php endif; ?>
              <?php if ($key === 'reservas' && $resPend): ?><span class="sb-badge" style="background:var(--bad)"><?= $resPend ?></span><?php endif; ?>
              <?php if ($key === 'cocina' && $cocinaPend): ?><span class="sb-badge"><?= $cocinaPend ?></span><?php endif; ?>
              <?php if ($key === 'insumos' && $alertas): ?><span class="sb-badge" style="background:var(--bad)"><?= $alertas ?></span><?php endif; ?>
            </a>
          <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </nav>
    <div class="sb-user">
      <div class="avatar"><?php if (!empty($user['avatar'])): ?><img src="<?= e(upload_url($user['avatar'])) ?>" alt=""><?php else: ?><?= e(initials($user['nombre'])) ?><?php endif; ?></div>
      <div class="info" style="min-width:0;flex:1">
        <div class="nm"><?= e($user['nombre']) ?></div>
        <div class="rl"><?= e($user['rol']) ?></div>
      </div>
      <a href="<?= e(url('auth/logout')) ?>" class="text-muted info" title="Cerrar sesión" style="font-size:1.2rem"><i class="bi bi-box-arrow-right"></i></a>
    </div>
  </aside>
  <div class="sb-backdrop" id="sbBackdrop"></div>

  <div class="main">
    <header class="topbar">
      <button class="icon-btn" id="sbToggle" type="button" aria-label="Menú"><i class="bi bi-list fs-5"></i></button>
      <div class="me-auto" style="min-width:0">
        <h1 class="tb-title text-truncate"><?= e($title) ?></h1>
        <div class="tb-crumb d-none d-sm-block"><?= e($crumb) ?> <i class="bi bi-chevron-right" style="font-size:.6rem"></i> <?= e($title) ?></div>
      </div>
      <span class="chip d-none d-md-inline-flex"><i class="bi bi-clock"></i><span id="clock">--:--</span></span>
      <?php if (Auth::can('caja')): ?>
        <a href="<?= e(url('caja/index')) ?>" class="chip <?= $cajaAbierta ? '' : 'off' ?> text-decoration-none">
          <span class="pulse"></span><?= $cajaAbierta ? 'Caja abierta' : 'Caja cerrada' ?>
        </a>
      <?php endif; ?>
      <?php if (Auth::can('insumos')): ?>
        <a href="<?= e(url('insumos/index')) ?>" class="icon-btn" title="Alertas de stock"><i class="bi bi-bell"></i><?php if ($alertas): ?><span class="dot"></span><?php endif; ?></a>
      <?php endif; ?>
      <div class="dropdown">
        <button class="icon-btn p-0 border-0 bg-transparent" data-bs-toggle="dropdown" aria-expanded="false">
          <span class="avatar" style="width:40px;height:40px;border-radius:12px"><?php if (!empty($user['avatar'])): ?><img src="<?= e(upload_url($user['avatar'])) ?>" alt=""><?php else: ?><?= e(initials($user['nombre'])) ?><?php endif; ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2" style="border-radius:16px;min-width:220px">
          <li class="px-3 py-2"><div class="fw-bold"><?= e($user['nombre']) ?></div><div class="small text-muted"><?= e($user['rol']) ?> · @<?= e($user['usuario']) ?></div></li>
          <li><hr class="dropdown-divider"></li>
          <li><a class="dropdown-item rounded-3" href="<?= e(url('auth/cuenta')) ?>"><i class="bi bi-person-circle me-2"></i>Mi cuenta</a></li>
          <li><a class="dropdown-item rounded-3 text-danger" href="<?= e(url('auth/logout')) ?>"><i class="bi bi-box-arrow-right me-2"></i>Cerrar sesión</a></li>
        </ul>
      </div>
    </header>

    <main class="content"><?= $content ?></main>
    <footer class="footer d-flex justify-content-between flex-wrap gap-2">
      <span>© <?= date('Y') ?> <?= e($empresa) ?> · <?= e(APP_NAME) ?> v<?= APP_VERSION ?></span>
      <span><?= e(cfg('empresa_eslogan')) ?></span>
    </footer>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded',function(){
  var st={};try{st=JSON.parse(localStorage.getItem('sbg')||'{}')}catch(e){}
  document.querySelectorAll('.sb-sec').forEach(function(sec){
    var g=sec.dataset.g;if(st[g]===0&&!sec.classList.contains('has-active'))sec.classList.add('closed');
    sec.querySelector('.sb-group').addEventListener('click',function(){sec.classList.toggle('closed');st[g]=sec.classList.contains('closed')?0:1;try{localStorage.setItem('sbg',JSON.stringify(st))}catch(e){}});
  });
});
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.12.4/dist/sweetalert2.all.min.js"></script>
<?php if (!empty($useCharts)): ?><script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script><?php endif; ?>
<script src="<?= asset('js/app.js') ?>"></script>
<?php if (Auth::isAdmin() && $module !== 'pos'): ?><script src="<?= asset('js/chat.js') ?>"></script><?php endif; ?>
<?php if (!empty($scripts)) foreach ($scripts as $s): ?><script src="<?= e(asset($s)) ?>"></script><?php endforeach; ?>
<?php if (!empty($inlineJs)): ?><script><?= $inlineJs ?></script><?php endif; ?>
</body>
</html>
