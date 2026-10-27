<?php /** Panel CRM */
$S = Crm::SEG;
$serieM = array_map(fn($x) => ['l' => mes_es($x['m']), 'n' => $x['n']], $serie);
?>
<div class="page-head">
  <div><h2>CRM · Relación con clientes</h2><p>Conozca a sus clientes, fidelícelos y recupere a los que dejaron de venir.</p></div>
  <div class="d-flex gap-2"><a href="<?= e(url('crm/clientes')) ?>" class="btn btn-ghost"><i class="bi bi-people me-1"></i>Ver clientes</a><?php if (Auth::can('campanas')): ?><a href="<?= e(url('campanas/index')) ?>" class="btn btn-brand"><i class="bi bi-megaphone me-1"></i>Nueva campaña</a><?php endif; ?></div>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><?= kpi_g('violet', 'Clientes registrados', (string)$total, 'bi-people-fill', '', $conTel . ' con teléfono', url('crm/clientes')) ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('green', 'Clientes activos', (string)$activos, 'bi-activity', '', 'compraron en 30 días') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Nuevos este mes', (string)$nuevosMes, 'bi-person-plus-fill', '', 'registrados') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('blue', 'Valor por cliente', e(money($ltv)), 'bi-gem', '', 'gasto promedio histórico') ?></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-4">
    <div class="card-soft h-100"><div class="card-h"><div><h5>Segmentos de clientes</h5><small>Clasificación automática</small></div></div>
      <div class="card-b"><div class="chart-box sm"><canvas id="chSeg"></canvas></div>
        <div class="crm-segs mt-3">
          <?php foreach ($S as $k => $s): ?><a href="<?= e(url('crm/clientes', ['seg' => $k])) ?>"><i style="background:<?= $s[2] ?>"></i><span><?= e($s[0]) ?></span><b><?= (int)$seg[$k] ?></b></a><?php endforeach; ?>
        </div></div></div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft h-100"><div class="card-h"><div><h5>Mejores clientes</h5><small>Por gasto acumulado</small></div></div>
      <div class="card-b pt-2">
        <?php foreach ($top as $c): $nv = Crm::NIVELES[$c['nivel']]; ?>
          <a href="<?= e(url('crm/ficha', ['id' => $c['id']])) ?>" class="crm-row">
            <span class="crm-av" style="background:<?= $S[$c['seg']][2] ?>"><?= e(Crm::iniciales($c['nombre'])) ?></span>
            <span class="flex-grow-1 min-w-0"><b class="d-block text-truncate text-dark"><?= e($c['nombre']) ?></b><small class="text-muted"><?= $c['n'] ?> visitas · <i class="bi <?= $nv[2] ?>" style="color:<?= $nv[1] ?>"></i> <?= $nv[0] ?></small></span>
            <b class="text-brand"><?= e(money($c['total'])) ?></b></a>
        <?php endforeach; ?>
        <?php if (!$top): ?><div class="empty"><i class="bi bi-person-heart"></i>Aún no hay ventas asociadas a clientes.<div class="small mt-1">Elija al cliente al cobrar en el punto de venta.</div></div><?php endif; ?>
      </div></div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft h-100"><div class="card-h"><div><h5>Nuevos clientes</h5><small>Últimos 6 meses</small></div></div>
      <div class="card-b"><div class="chart-box sm"><canvas id="chNuevos"></canvas></div></div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card-soft h-100"><div class="card-h"><div><h5><i class="bi bi-balloon-heart-fill text-danger me-1"></i>Cumpleaños próximos</h5><small>Siguientes 30 días</small></div></div>
      <div class="card-b pt-2">
        <?php foreach ($cumple as $c): $w = Crm::wa($c['telefono'], '¡Feliz cumpleaños ' . ucfirst(mb_strtolower(explode(' ', trim($c['nombre']))[0])) . '! 🎂 En ' . cfg('empresa_nombre', APP_NAME) . ' queremos celebrarlo contigo. Muéstranos este mensaje y recibe un postre de regalo.'); ?>
          <div class="crm-row"><span class="crm-av" style="background:#EC4899"><?= e(Crm::iniciales($c['nombre'])) ?></span>
            <span class="flex-grow-1 min-w-0"><a href="<?= e(url('crm/ficha', ['id' => $c['id']])) ?>" class="fw-bold text-dark d-block text-truncate"><?= e($c['nombre']) ?></a><small class="text-muted"><?= e($c['dia']) ?> · cumple <?= (int)$c['edad'] ?> años</small></span>
            <span class="bsoft <?= $c['en'] === 0 ? 'bad' : 'brand' ?>"><?= $c['en'] === 0 ? '¡Hoy!' : 'en ' . (int)$c['en'] . ' d' ?></span>
            <?php if ($w): ?><a class="btn btn-sm btn-ghost" target="_blank" rel="noopener" href="<?= e($w) ?>" title="Felicitar por WhatsApp"><i class="bi bi-whatsapp text-success"></i></a><?php endif; ?></div>
        <?php endforeach; ?>
        <?php if (!$cumple): ?><div class="empty"><i class="bi bi-balloon"></i>Sin cumpleaños próximos.<div class="small mt-1">Registre la fecha de nacimiento en la ficha del cliente.</div></div><?php endif; ?>
      </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft h-100"><div class="card-h"><div><h5><i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>Para recuperar</h5><small>Clientes en riesgo con más gasto</small></div></div>
      <div class="card-b pt-2">
        <?php foreach ($riesgo as $c): $w = Crm::wa($c['telefono'], Crm::plantilla('Hola {nombre}, te extrañamos en {empresa} 🍗. Vuelve esta semana y te regalamos una gaseosa con tu pedido.', $c)); ?>
          <div class="crm-row"><span class="crm-av" style="background:#F97316"><?= e(Crm::iniciales($c['nombre'])) ?></span>
            <span class="flex-grow-1 min-w-0"><a href="<?= e(url('crm/ficha', ['id' => $c['id']])) ?>" class="fw-bold text-dark d-block text-truncate"><?= e($c['nombre']) ?></a><small class="text-muted">hace <?= (int)$c['dias'] ?> días · <?= e(money($c['total'])) ?></small></span>
            <a class="btn btn-sm btn-ghost" target="_blank" rel="noopener" href="<?= e($w) ?>" title="Escribir por WhatsApp"><i class="bi bi-whatsapp text-success"></i></a></div>
        <?php endforeach; ?>
        <?php if (!$riesgo): ?><div class="empty"><i class="bi bi-emoji-smile"></i>¡Nadie en riesgo por ahora!</div><?php endif; ?>
      </div></div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft h-100"><div class="card-h"><div><h5><i class="bi bi-bell-fill text-info me-1"></i>Seguimientos pendientes</h5><small>Tareas con fecha</small></div></div>
      <div class="card-b pt-2">
        <?php foreach (array_slice($segs, 0, 6) as $n): $venc = $n['seguimiento'] < date('Y-m-d'); $ti = Crm::TIPOS_NOTA[$n['tipo']] ?? Crm::TIPOS_NOTA['nota']; ?>
          <div class="crm-row"><span class="crm-av" style="background:<?= $ti[2] ?>"><i class="bi <?= $ti[0] ?>"></i></span>
            <span class="flex-grow-1 min-w-0"><a href="<?= e(url('crm/ficha', ['id' => $n['cliente_id']])) ?>" class="fw-bold text-dark d-block text-truncate"><?= e($n['nombre']) ?></a><small class="text-muted d-block text-truncate"><?= e($n['texto']) ?></small></span>
            <span class="bsoft <?= $venc ? 'bad' : ($n['seguimiento'] === date('Y-m-d') ? 'warn' : 'gray') ?>"><?= e(date('d/m', strtotime($n['seguimiento']))) ?></span></div>
        <?php endforeach; ?>
        <?php if (!$segs): ?><div class="empty"><i class="bi bi-check2-circle"></i>Sin seguimientos pendientes</div><?php endif; ?>
      </div></div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var S = <?= json_encode(array_map(fn($k) => ['l' => $S[$k][0], 'c' => $S[$k][2], 'n' => (int)$seg[$k]], array_keys($S)), JSON_UNESCAPED_UNICODE) ?>;
  var N = <?= json_encode($serieM, JSON_UNESCAPED_UNICODE) ?>;
  if (typeof Chart === 'undefined') return;
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  new Chart(document.getElementById('chSeg'), { type: 'doughnut', data: { labels: S.map(function (x) { return x.l; }), datasets: [{ data: S.map(function (x) { return x.n; }), backgroundColor: S.map(function (x) { return x.c; }), borderWidth: 3, borderColor: '#fff' }] }, options: { maintainAspectRatio: false, cutout: '68%', plugins: { legend: { display: false } } } });
  new Chart(document.getElementById('chNuevos'), { type: 'bar', data: { labels: N.map(function (x) { return x.l; }), datasets: [{ data: N.map(function (x) { return x.n; }), backgroundColor: '#FDBA74', hoverBackgroundColor: '#F97316', borderRadius: 10, maxBarThickness: 38 }] }, options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: '#F1F5F9' } }, x: { grid: { display: false } } } } });
});
</script>
