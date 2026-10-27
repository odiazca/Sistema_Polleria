<?php
$tipoLbl = ['mesa' => 'Mesa', 'llevar' => 'Para llevar', 'delivery' => 'Delivery'];
$tipoCls = ['mesa' => 'info', 'llevar' => 'warn', 'delivery' => 'violet'];
$ticketProm = $t['n'] ? $t['t'] / $t['n'] : 0;
function trendBadge(?float $v, string $suffix = 'vs. ayer'): string
{
    if ($v === null) return '<span class="text-muted small">Sin datos previos</span>';
    $up = $v >= 0;
    return '<span class="trend ' . ($up ? 'up' : 'down') . '"><i class="bi bi-arrow-' . ($up ? 'up' : 'down') . '-right"></i>' . abs($v) . '%</span> <span class="small text-muted">' . $suffix . '</span>';
}
?>
<div class="hero mb-3">
  <div class="hero-txt">
    <div class="hero-eyebrow"><i class="bi bi-fire"></i> <?= e(mb_strtoupper(mb_substr(strftime_es(), 0, 1)) . mb_substr(strftime_es(), 1)) ?></div>
    <h3><?= e($saludo) ?>, <?= e(explode(' ', Auth::user()['nombre'])[0]) ?> <span class="wave">👋</span></h3>
    <p>Así va <b><?= e(cfg('empresa_nombre')) ?></b> hoy. ¡A encender las brasas!</p>
    <div class="hero-stats">
      <div class="hs"><span class="v"><?= e(money($t['t'])) ?></span><span class="l">vendido hoy</span></div>
      <div class="hs"><span class="v"><?= (int)$t['n'] ?></span><span class="l">pedidos</span></div>
      <div class="hs"><span class="v"><?= e(money($ticketProm)) ?></span><span class="l">ticket promedio</span></div>
      <div class="hs"><span class="v"><?= $cajaAb ? '<i class="bi bi-unlock-fill"></i>' : '<i class="bi bi-lock-fill"></i>' ?></span><span class="l"><?= $cajaAb ? 'caja abierta' : 'caja cerrada' ?></span></div>
    </div>
  </div>
  <div class="hero-art" aria-hidden="true"><span class="ring r1"></span><span class="ring r2"></span><span class="dish">🍗</span><span class="f f1">🔥</span><span class="f f2">🍟</span><span class="f f3">🥤</span><span class="f f4">🌶️</span></div>
</div>

<div class="row g-3 mb-3 row-cols-2 row-cols-md-3 row-cols-xl-6">
  <?php $qa = [
    ['pos', 'pos/index', '#EA580C', 'bi-shop-window', 'Nueva venta', 'Punto de venta'],
    ['mesas', 'mesas/index', '#8B5CF6', 'bi-grid-3x3-gap-fill', 'Mesas', (int)$mesasOc . '/' . (int)$mesasTot . ' ocupadas'],
    ['reservas', 'reservas/index', '#EC4899', 'bi-calendar-heart-fill', 'Reservas', (int)$resHoy . ' hoy'],
    ['cocina', 'cocina/index', '#EF4444', 'bi-fire', 'Cocina', (int)$abiertos . ' en curso'],
    ['delivery', 'delivery/index', '#0D9488', 'bi-bicycle', 'Delivery', (int)$deliveryPend . ' pendientes'],
    ['caja', 'caja/index', '#16A34A', 'bi-cash-coin', 'Caja', $cajaAb ? 'Abierta ' . date('H:i', strtotime($cajaAb['fecha_apertura'])) : 'Cerrada'],
  ]; foreach ($qa as [$mod, $rt, $col, $ic, $lb, $sb]): if (!Auth::can($mod)) continue; ?>
    <div class="col"><a href="<?= e(url($rt)) ?>" class="qa" style="--c:<?= $col ?>"><span class="qi"><i class="bi <?= $ic ?>"></i></span><span class="qt"><b><?= e($lb) ?></b><small><?= e($sb) ?></small></span><i class="bi bi-chevron-right qg"></i></a></div>
  <?php endforeach; ?>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><?= kpi_g('green', 'Ventas de hoy', e(money($t['t'])), 'bi-currency-dollar', kpi_trend($vHoy), 'vs. ayer') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Pedidos hoy', (string)(int)$t['n'], 'bi-receipt', '', 'Ticket promedio ' . e(money($ticketProm))) ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('blue', 'Ventas del mes', e(money($m['t'])), 'bi-calendar2-check', kpi_trend($vMes), 'vs. mes anterior') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('rose', 'Gastos del mes', e(money($gastosMes)), 'bi-wallet2', e(money($m['t'] - $gastosMes)), 'Utilidad aprox.') ?></div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-8">
    <div class="card-soft h-100">
      <div class="card-h">
        <div><h5>Ventas por día</h5><small>Ingresos y número de pedidos</small></div>
        <select id="periodo" class="form-select form-select-sm" style="width:auto">
          <option value="7">Últimos 7 días</option><option value="15">Últimos 15 días</option><option value="30">Últimos 30 días</option>
        </select>
      </div>
      <div class="card-b"><div class="chart-box"><canvas id="chVentas"></canvas></div></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Ventas por tipo</h5><small>Mesa · llevar · delivery</small></div></div>
      <div class="card-b"><div class="chart-box sm"><canvas id="chTipos"></canvas></div><div id="lgTipos" class="mt-3 small"></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Productos más vendidos</h5><small>Unidades en el período</small></div></div>
      <div class="card-b"><div class="chart-box sm"><canvas id="chTop"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Horas pico</h5><small>Ventas por hora</small></div></div>
      <div class="card-b"><div class="chart-box sm"><canvas id="chHoras"></canvas></div></div>
    </div>
  </div>
  <div class="col-lg-3">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Métodos de pago</h5><small>Participación</small></div></div>
      <div class="card-b"><div class="chart-box sm"><canvas id="chMetodos"></canvas></div><div id="lgMetodos" class="mt-2 small"></div></div>
    </div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-xl-5">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Plano de mesas</h5><small>Estado en vivo del salón</small></div>
        <div class="d-none d-sm-flex gap-2 small text-muted"><span><i class="legend-dot" style="background:#34D399"></i>Libre</span><span><i class="legend-dot" style="background:#FB923C"></i>Ocupada</span><span><i class="legend-dot" style="background:#60A5FA"></i>Reservada</span></div></div>
      <div class="card-b pt-3"><div class="mini-mesas">
        <?php foreach ($mesasList as $ml): $href = $ml['pedido_id'] ? url('pos/index', ['pedido' => $ml['pedido_id']]) : url('pos/index', ['mesa' => $ml['id']]); ?>
          <a href="<?= e($href) ?>" class="mm <?= $ml['real'] ?>" title="<?= e($ml['zona']) ?>"><i class="bi bi-<?= $ml['real'] === 'ocupada' ? 'people-fill' : ($ml['real'] === 'reservada' ? 'bookmark-star-fill' : 'cup-hot') ?>"></i><b><?= e(preg_replace('/^Mesa\s*/i', '', $ml['nombre'])) ?></b><small><?= $ml['real'] === 'ocupada' ? e(money($ml['total'], false)) : (int)$ml['capacidad'] . ' pers.' ?></small><?php if ($ml['real'] === 'ocupada'): ?><em><?= (int)$ml['mins'] ?>′</em><?php endif; ?></a>
        <?php endforeach; ?>
        <?php if (!$mesasList): ?><div class="empty w-100"><i class="bi bi-grid-3x3-gap"></i>Sin mesas configuradas</div><?php endif; ?>
      </div></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Cocina en vivo</h5><small>Pedidos por preparar</small></div><?php if (Auth::can('cocina')): ?><a href="<?= e(url('cocina/index')) ?>" class="btn btn-soft btn-sm">Abrir</a><?php endif; ?></div>
      <div class="card-b pt-2">
        <?php foreach ($cocina as $ck): $late = $ck['mins'] >= 20; ?>
          <div class="list-row"><span class="rank <?= $late ? 't-bad' : 't-brand' ?>"><i class="bi bi-fire"></i></span>
            <div class="flex-grow-1"><div class="fw-bold small">#<?= (int)$ck['id'] ?> · <?= e($ck['mesa'] ?: ($ck['tipo'] === 'delivery' ? 'Delivery' : 'Para llevar')) ?></div>
              <div class="text-muted" style="font-size:.74rem"><?= (int)$ck['pend'] ?> por preparar<?= (int)$ck['listos'] ? ' · ' . (int)$ck['listos'] . ' listos' : '' ?></div></div>
            <span class="bsoft <?= $late ? 'bad' : ((int)$ck['listos'] && !(int)$ck['pend'] ? 'ok' : 'warn') ?>"><i class="bi bi-stopwatch"></i> <?= (int)$ck['mins'] ?> min</span></div>
        <?php endforeach; ?>
        <?php if (!$cocina): ?><div class="empty"><i class="bi bi-emoji-smile" style="color:#10B981"></i>Cocina al día, sin pedidos pendientes</div><?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-xl-3">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Insumos clave</h5><small>Pollo, papa, aceite y más</small></div></div>
      <div class="card-b pt-3">
        <?php foreach ($insumosKey as $ik): $min = max((float)$ik['stock_minimo'], 0.001); $pct = max(4, min(100, round($ik['stock'] / ($min * 3) * 100))); $st = $ik['stock'] <= $ik['stock_minimo'] ? 'bad' : ($ik['stock'] <= $ik['stock_minimo'] * 1.6 ? 'warn' : 'ok'); ?>
          <div class="mb-3"><div class="d-flex justify-content-between small"><b><?= e($ik['nombre']) ?></b><span class="text-muted"><?= e(num($ik['stock'], 1)) ?> <?= e($ik['unidad']) ?></span></div>
            <div class="bar-s <?= $st ?>"><i style="width:<?= $pct ?>%"></i></div>
            <?php if ((float)$ik['usado_hoy'] > 0): ?><div class="text-muted" style="font-size:.7rem">Hoy se usaron <?= e(num($ik['usado_hoy'], 1)) ?> <?= e($ik['unidad']) ?></div><?php endif; ?></div>
        <?php endforeach; ?>
        <?php if (!$insumosKey): ?><div class="empty"><i class="bi bi-box-seam"></i>Sin insumos registrados</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Últimas ventas</h5><small>Comprobantes recientes</small></div><?php if (Auth::can('ventas')): ?><a href="<?= e(url('ventas/index')) ?>" class="btn btn-soft btn-sm">Ver todas</a><?php endif; ?></div>
      <div class="card-b pb-2 pt-3">
        <div class="table-wrap"><table class="table table-modern">
          <thead><tr><th>Comprobante</th><th>Cliente / Mesa</th><th>Tipo</th><th>Hora</th><th class="text-end">Total</th></tr></thead>
          <tbody>
          <?php foreach ($ultimas as $v): ?>
            <tr>
              <td class="fw-semibold"><?= e(Calc::numeroCompleto($v['serie'], $v['numero'])) ?></td>
              <td><?= e($v['cliente'] ?: ($v['mesa'] ?: 'Público general')) ?></td>
              <td><span class="bsoft <?= $tipoCls[$v['tipo']] ?>"><?= $tipoLbl[$v['tipo']] ?></span></td>
              <td class="text-muted"><?= e(fdate($v['fecha_pago'], true)) ?></td>
              <td class="text-end fw-bold"><?= e(money($v['total'])) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$ultimas): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-receipt"></i>Aún no hay ventas registradas</div></td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft h-100">
      <div class="card-h"><div><h5>Stock bajo</h5><small>Reponer pronto</small></div><span class="bsoft bad"><?= count($stockBajo) + count($prodBajo) ?></span></div>
      <div class="card-b pt-2">
        <?php foreach ($stockBajo as $s): ?>
          <div class="list-row"><span class="rank t-bad"><i class="bi bi-box-seam"></i></span><div class="flex-grow-1"><div class="fw-bold small"><?= e($s['nombre']) ?></div><div class="text-muted" style="font-size:.74rem">Mínimo: <?= e(num($s['stock_minimo'], 1)) ?> <?= e($s['unidad']) ?></div></div><span class="bsoft bad"><?= e(num($s['stock'], 1)) ?> <?= e($s['unidad']) ?></span></div>
        <?php endforeach; ?>
        <?php foreach ($prodBajo as $s): ?>
          <div class="list-row"><span class="rank t-warn"><i class="bi bi-cup-straw"></i></span><div class="flex-grow-1"><div class="fw-bold small"><?= e($s['nombre']) ?></div><div class="text-muted" style="font-size:.74rem">Mínimo: <?= e(num($s['stock_minimo'], 0)) ?> und</div></div><span class="bsoft warn"><?= e(num($s['stock'], 0)) ?> und</span></div>
        <?php endforeach; ?>
        <?php if (!$stockBajo && !$prodBajo): ?><div class="empty"><i class="bi bi-check2-circle" style="color:#10B981"></i>Todo el inventario está en orden</div><?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>window.DASH = <?= json_encode($chart, JSON_UNESCAPED_UNICODE) ?>;</script>
