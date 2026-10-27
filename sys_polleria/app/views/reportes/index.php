<?php
$utilidad = $k['t'] - $costo - $gastos;
$tipoL = ['mesa' => 'Mesa', 'llevar' => 'Para llevar', 'delivery' => 'Delivery'];
$metL = ['efectivo' => 'Efectivo', 'tarjeta' => 'Tarjeta', 'yape' => 'Yape', 'plin' => 'Plin', 'transferencia' => 'Transferencia'];
$chart = [
  'dias' => array_map(fn($r) => date('d/m', strtotime($r['d'])), $porDia), 'ventas' => array_map(fn($r) => round((float)$r['t'], 2), $porDia), 'pedidos' => array_map(fn($r) => (int)$r['n'], $porDia),
  'cats' => array_map(fn($r) => $r['nombre'], $porCat), 'catv' => array_map(fn($r) => round((float)$r['t'], 2), $porCat),
  'usr' => array_map(fn($r) => $r['nombre'], $porUsuario), 'usrv' => array_map(fn($r) => round((float)$r['t'], 2), $porUsuario),
  'dow' => $dow,
];
?>
<div class="page-head">
  <div><h2>Reportes</h2><p>Análisis de ventas, productos y rentabilidad por período.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <a class="btn btn-ghost" href="<?= e(url('reportes/exportar', ['tipo' => 'ventas', 'desde' => $desde, 'hasta' => $hasta])) ?>"><i class="bi bi-filetype-csv me-1"></i>Ventas CSV</a>
    <a class="btn btn-ghost" href="<?= e(url('reportes/exportar', ['tipo' => 'productos', 'desde' => $desde, 'hasta' => $hasta])) ?>"><i class="bi bi-filetype-csv me-1"></i>Productos CSV</a>
    <button class="btn btn-ghost" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
  </div>
</div>
<form class="card-soft p-3 mb-3 d-flex gap-2 align-items-end flex-wrap" method="get">
  <input type="hidden" name="r" value="reportes/index">
  <div><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= e($desde) ?>"></div>
  <div><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= e($hasta) ?>"></div>
  <button class="btn btn-brand"><i class="bi bi-funnel me-1"></i>Aplicar</button>
  <?php foreach (['Hoy' => [date('Y-m-d'), date('Y-m-d')], '7 días' => [date('Y-m-d', strtotime('-6 days')), date('Y-m-d')], 'Este mes' => [date('Y-m-01'), date('Y-m-d')], 'Mes anterior' => [date('Y-m-01', strtotime('first day of last month')), date('Y-m-t', strtotime('first day of last month'))]] as $l => [$d1, $d2]): ?>
    <a class="btn btn-ghost btn-sm" href="<?= e(url('reportes/index', ['desde' => $d1, 'hasta' => $d2])) ?>"><?= $l ?></a>
  <?php endforeach; ?>
</form>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><?= kpi_g('green', 'Ventas', e(money($k['t'])), 'bi-currency-dollar', '', (int)$k['n'] . ' pedidos · ticket prom. ' . e(money($k['n'] ? $k['t'] / $k['n'] : 0))) ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Costo de productos', e(money($costo)), 'bi-basket', '', 'Margen bruto ' . ($k['t'] > 0 ? round(($k['t'] - $costo) / $k['t'] * 100, 1) : 0) . '%') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('rose', 'Gastos', e(money($gastos)), 'bi-wallet2', '', 'Compras de insumos: ' . e(money($compras))) ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g($utilidad >= 0 ? 'blue' : 'rose', 'Utilidad estimada', e(money($utilidad)), 'bi-graph-up-arrow', '', 'Ventas − costo − gastos · ' . (int)$anulados . ' anulados') ?></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-8"><div class="card-soft h-100"><div class="card-h"><div><h5>Evolución de ventas</h5><small>Por día del período</small></div></div><div class="card-b"><div class="chart-box"><canvas id="rDia"></canvas></div></div></div></div>
  <div class="col-xl-4"><div class="card-soft h-100"><div class="card-h"><div><h5>Ventas por categoría</h5></div></div><div class="card-b"><div class="chart-box sm"><canvas id="rCat"></canvas></div></div></div></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-lg-6"><div class="card-soft h-100"><div class="card-h"><div><h5>Ventas por día de la semana</h5></div></div><div class="card-b"><div class="chart-box sm"><canvas id="rDow"></canvas></div></div></div></div>
  <div class="col-lg-6"><div class="card-soft h-100"><div class="card-h"><div><h5>Ventas por vendedor</h5></div></div><div class="card-b"><div class="chart-box sm"><canvas id="rUsr"></canvas></div></div></div></div>
</div>
<div class="row g-3">
  <div class="col-xl-7"><div class="card-soft h-100"><div class="card-h"><div><h5>Productos más vendidos</h5></div></div><div class="card-b pt-3"><div class="table-wrap"><table class="table table-modern">
    <thead><tr><th>#</th><th>Producto</th><th class="text-end">Cant.</th><th class="text-end">Importe</th></tr></thead><tbody>
    <?php foreach ($top as $n => $t): ?><tr><td><span class="rank"><?= $n + 1 ?></span></td><td class="fw-semibold"><?= e($t['nombre']) ?></td><td class="text-end"><?= e(num($t['q'], 0)) ?></td><td class="text-end fw-bold"><?= e(money($t['t'])) ?></td></tr><?php endforeach; ?>
    <?php if (!$top): ?><tr><td colspan="4"><div class="empty py-4">Sin datos en el período</div></td></tr><?php endif; ?></tbody></table></div></div></div></div>
  <div class="col-xl-5">
    <div class="card-soft mb-3"><div class="card-h"><div><h5>Por método de pago</h5></div></div><div class="card-b pt-2">
      <?php foreach ($porMetodo as $m): ?><div class="list-row"><div class="flex-grow-1 fw-semibold"><?= e($metL[$m['metodo']] ?? $m['metodo']) ?></div><b><?= e(money($m['t'])) ?></b></div><?php endforeach; ?>
      <?php if (!$porMetodo): ?><div class="text-muted small py-2">Sin datos</div><?php endif; ?></div></div>
    <div class="card-soft"><div class="card-h"><div><h5>Por tipo de pedido</h5></div></div><div class="card-b pt-2">
      <?php foreach ($porTipo as $m): ?><div class="list-row"><div class="flex-grow-1 fw-semibold"><?= e($tipoL[$m['tipo']]) ?> <span class="text-muted fw-normal small">· <?= (int)$m['n'] ?> pedidos</span></div><b><?= e(money($m['t'])) ?></b></div><?php endforeach; ?>
      <?php if (!$porTipo): ?><div class="text-muted small py-2">Sin datos</div><?php endif; ?></div></div>
  </div>
</div>

<div class="card-soft mt-3"><div class="card-h"><div><h5>Consumo de insumos por ventas</h5><small>Descontado automáticamente según las recetas</small></div></div>
  <div class="card-b pt-3"><div class="table-wrap"><table class="table table-modern"><thead><tr><th>Insumo</th><th class="text-end">Consumo</th><th class="text-end">Costo estimado</th></tr></thead><tbody>
  <?php foreach ($consumo as $c): ?><tr><td class="fw-semibold"><?= e($c['nombre']) ?></td><td class="text-end"><?= e(num($c['q'], 2)) ?> <?= e($c['unidad']) ?></td><td class="text-end fw-bold"><?= e(money($c['t'])) ?></td></tr><?php endforeach; ?>
  <?php if (!$consumo): ?><tr><td colspan="3"><div class="empty py-4">Sin consumo registrado (cree recetas en el módulo Recetas)</div></td></tr><?php endif; ?>
  </tbody></table></div></div></div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  var D = <?= json_encode($chart, JSON_UNESCAPED_UNICODE) ?>;
  var BR = getComputedStyle(document.documentElement).getPropertyValue('--brand').trim() || '#F97316';
  var PAL = [BR, '#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EC4899', '#14B8A6'];
  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif"; Chart.defaults.color = '#64748B'; Chart.defaults.plugins.legend.display = false;
  var grid = { color: '#EEF1F7' }, money = function (c) { return ' ' + fmtMoney(c.parsed.y !== undefined ? c.parsed.y : c.parsed); };
  var g = document.getElementById('rDia').getContext('2d'), gr = g.createLinearGradient(0, 0, 0, 300); gr.addColorStop(0, BR + '55'); gr.addColorStop(1, BR + '00');
  new Chart(g, { type: 'line', data: { labels: D.dias, datasets: [{ data: D.ventas, borderColor: BR, backgroundColor: gr, fill: true, tension: .35, borderWidth: 3, pointRadius: D.dias.length > 20 ? 0 : 3 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, y: { grid: grid, beginAtZero: true, ticks: { callback: function (v) { return fmtNum(v, 0); } } } }, plugins: { tooltip: { callbacks: { label: money } } } } });
  new Chart(document.getElementById('rCat'), { type: 'doughnut', data: { labels: D.cats, datasets: [{ data: D.catv, backgroundColor: PAL, borderWidth: 4, borderColor: '#fff' }] },
    options: { responsive: true, maintainAspectRatio: false, cutout: '62%', plugins: { legend: { display: true, position: 'bottom', labels: { boxWidth: 10, usePointStyle: true } }, tooltip: { callbacks: { label: money } } } } });
  new Chart(document.getElementById('rDow'), { type: 'bar', data: { labels: ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'], datasets: [{ data: D.dow, backgroundColor: D.dow.map(function (v) { return v === Math.max.apply(null, D.dow) && v > 0 ? BR : BR + '55'; }), borderRadius: 8 }] },
    options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false } }, y: { grid: grid, beginAtZero: true, ticks: { callback: function (v) { return fmtNum(v, 0); } } } }, plugins: { tooltip: { callbacks: { label: money } } } } });
  new Chart(document.getElementById('rUsr'), { type: 'bar', data: { labels: D.usr, datasets: [{ data: D.usrv, backgroundColor: PAL, borderRadius: 8, barThickness: 22 }] },
    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { grid: grid, beginAtZero: true, ticks: { callback: function (v) { return fmtNum(v, 0); } } }, y: { grid: { display: false } } }, plugins: { tooltip: { callbacks: { label: function (c) { return ' ' + fmtMoney(c.parsed.x); } } } } } });
});
</script>
