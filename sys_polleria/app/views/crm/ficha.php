<?php /** Ficha 360 del cliente */
$S = Crm::SEG; $sg = $S[$c['seg']]; $nv = Crm::NIVELES[$c['nivel']];
$sigMeta = $c['nivel'] === 'bronce' ? $r['plata'] : ($c['nivel'] === 'plata' ? $r['oro'] : 0);
$sigNom = $c['nivel'] === 'bronce' ? 'Plata' : 'Oro';
$ini = $c['nivel'] === 'bronce' ? 0 : ($c['nivel'] === 'plata' ? $r['plata'] : $r['oro']);
$pct = $sigMeta ? max(0, min(100, ($c['hist'] - $ini) / max(1, $sigMeta - $ini) * 100)) : 100;
$w = Crm::wa($c['telefono'], Crm::plantilla('Hola {nombre}, le saludamos de {empresa} 🍗.', $c));
$tags = array_filter(array_map('trim', explode(',', (string)$c['etiquetas'])));
$tipoN = ['mesa' => 'En mesa', 'llevar' => 'Para llevar', 'delivery' => 'Delivery'];
$serieJ = array_map(fn($x) => ['l' => mes_es($x['m']), 't' => $x['t']], $serie);
$cj = ['id' => (int)$c['id'], 'nombre' => $c['nombre'], 'documento' => $c['documento'], 'telefono' => $c['telefono'], 'email' => $c['email'], 'fecha_nacimiento' => $c['fecha_nacimiento'], 'etiquetas' => $c['etiquetas'], 'acepta_marketing' => (int)$c['acepta_marketing']];
?>
<div class="page-head">
  <div><h2>Ficha del cliente</h2><p>Vista 360: compras, preferencias, fidelidad y seguimiento.</p></div>
  <a href="<?= e(url('crm/clientes')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver a clientes</a>
</div>

<div class="card-soft crm-hero mb-3">
  <div class="d-flex flex-wrap gap-3 align-items-center">
    <span class="crm-av xl" style="background:<?= $sg[2] ?>"><?= e(Crm::iniciales($c['nombre'])) ?></span>
    <div class="flex-grow-1" style="min-width:230px">
      <h3 class="fw-800 mb-1"><?= e($c['nombre']) ?></h3>
      <div class="d-flex flex-wrap gap-2 mb-2"><span class="bsoft <?= $sg[1] ?>"><i class="bi <?= $sg[3] ?>"></i><?= e($sg[0]) ?></span>
        <span class="bsoft gray" style="color:<?= $nv[1] ?>"><i class="bi <?= $nv[2] ?>"></i>Nivel <?= $nv[0] ?></span>
        <?php foreach ($tags as $t): ?><span class="bsoft violet"><i class="bi bi-tag-fill"></i><?= e($t) ?></span><?php endforeach; ?>
        <?php if (!$c['acepta_marketing']): ?><span class="bsoft bad"><i class="bi bi-bell-slash-fill"></i>No desea promociones</span><?php endif; ?></div>
      <div class="crm-info">
        <span><i class="bi bi-telephone"></i><?= e($c['telefono'] ?: 'Sin teléfono') ?></span>
        <?php if ($c['email']): ?><span><i class="bi bi-envelope"></i><?= e($c['email']) ?></span><?php endif; ?>
        <?php if ($c['documento']): ?><span><i class="bi bi-person-vcard"></i><?= e($c['tipo_doc'] . ' ' . $c['documento']) ?></span><?php endif; ?>
        <?php if ($c['fecha_nacimiento']): ?><span><i class="bi bi-balloon-heart"></i><?= e(date('d/m/Y', strtotime($c['fecha_nacimiento']))) ?></span><?php endif; ?>
        <span><i class="bi bi-calendar-check"></i>Cliente desde <?= e(fdate($c['created_at'])) ?></span>
      </div>
    </div>
    <div class="d-flex gap-2 flex-wrap"><?php if ($w): ?><a class="btn btn-success" target="_blank" rel="noopener" href="<?= e($w) ?>"><i class="bi bi-whatsapp me-1"></i>WhatsApp</a><?php endif; ?>
      <button class="btn btn-ghost" id="crmEdit" data-c="<?= e(json_encode($cj, JSON_UNESCAPED_UNICODE)) ?>"><i class="bi bi-pencil me-1"></i>Editar</button></div>
  </div>
</div>

<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Gasto total', e(money($c['total'])), 'bi-cash-coin', '', $c['n'] . ' compras') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('blue', 'Ticket promedio', e(money($c['ticket'])), 'bi-receipt', '', 'por visita') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('green', 'Última compra', $c['ultima'] ? 'hace ' . (int)$c['dias'] . ' d' : '—', 'bi-clock-history', '', $c['ultima'] ? e(fdate($c['ultima'])) : 'sin compras') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('violet', 'Puntos disponibles', (string)(int)$c['puntos'], 'bi-stars', '', 'Nivel ' . $nv[0]) ?></div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <ul class="nav crm-tabs mb-3" role="tablist">
      <li><button class="active" data-bs-toggle="pill" data-bs-target="#tResumen" type="button"><i class="bi bi-graph-up"></i>Resumen</button></li>
      <li><button data-bs-toggle="pill" data-bs-target="#tPedidos" type="button"><i class="bi bi-receipt"></i>Pedidos <span><?= count($pedidos) ?></span></button></li>
      <li><button data-bs-toggle="pill" data-bs-target="#tNotas" type="button"><i class="bi bi-chat-square-text"></i>Notas y seguimiento <span><?= count($notas) ?></span></button></li>
      <li><button data-bs-toggle="pill" data-bs-target="#tRes" type="button"><i class="bi bi-calendar-heart"></i>Reservas <span><?= count($reservas) ?></span></button></li>
    </ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="tResumen">
        <div class="card-soft mb-3"><div class="card-h"><div><h5>Gasto mensual</h5><small>Últimos 12 meses</small></div></div><div class="card-b"><div class="chart-box sm"><canvas id="chGasto"></canvas></div></div></div>
        <div class="row g-3">
          <div class="col-md-7"><div class="card-soft h-100"><div class="card-h"><div><h5>Platos favoritos</h5><small>Lo que más pide</small></div></div><div class="card-b pt-1">
            <?php foreach ($fav as $i => $f): ?><div class="crm-fav"><span class="n"><?= $i + 1 ?></span><span class="flex-grow-1"><?= e($f['nombre']) ?></span><b><?= (int)$f['cant'] ?></b><small class="text-muted">uds.</small></div><?php endforeach; ?>
            <?php if (!$fav): ?><div class="empty"><i class="bi bi-egg-fried"></i>Sin compras registradas</div><?php endif; ?></div></div></div>
          <div class="col-md-5"><div class="card-soft h-100"><div class="card-h"><div><h5>¿Cómo compra?</h5><small>Canal preferido</small></div></div><div class="card-b pt-1">
            <?php $tt = array_sum(array_column($tipos, 'n')); foreach ($tipos as $t): $p = $tt ? round($t['n'] / $tt * 100) : 0; ?>
              <div class="mb-2"><div class="d-flex justify-content-between small"><span><?= e($tipoN[$t['tipo']] ?? $t['tipo']) ?></span><b><?= $p ?>%</b></div><div class="progress" style="height:8px"><div class="progress-bar" style="width:<?= $p ?>%;background:var(--brand)"></div></div></div>
            <?php endforeach; ?>
            <?php if (!$tipos): ?><div class="empty"><i class="bi bi-bag"></i>Sin datos</div><?php endif; ?></div></div></div>
        </div>
      </div>

      <div class="tab-pane fade" id="tPedidos"><div class="card-soft"><div class="table-wrap"><table class="table table-modern">
        <thead><tr><th>Pedido</th><th>Fecha</th><th>Tipo</th><th>Comprobante</th><th class="text-end">Total</th></tr></thead><tbody>
        <?php foreach ($pedidos as $p): ?><tr><td class="fw-bold">#<?= (int)$p['id'] ?></td><td><?= e(fdate($p['fecha_pago'], true)) ?></td><td><span class="bsoft gray"><?= e($tipoN[$p['tipo']] ?? $p['tipo']) ?></span></td><td class="text-muted"><?= $p['comprobante'] ? e(ucfirst($p['comprobante']) . ' ' . $p['serie'] . '-' . str_pad((string)$p['numero'], 6, '0', STR_PAD_LEFT)) : '—' ?></td><td class="text-end fw-bold"><?= e(money($p['total'])) ?></td></tr><?php endforeach; ?>
        <?php if (!$pedidos): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-receipt"></i>Este cliente aún no tiene compras. Seleccione al cliente al cobrar en el punto de venta.</div></td></tr><?php endif; ?></tbody></table></div></div></div>

      <div class="tab-pane fade" id="tNotas">
        <form class="card-soft p-3 mb-3" id="crmNota" data-cid="<?= (int)$c['id'] ?>"><div class="row g-2 align-items-end">
          <div class="col-md-3"><label class="form-label small mb-1">Tipo</label><select class="form-select form-select-sm" name="tipo"><?php foreach (Crm::TIPOS_NOTA as $k => $t): ?><option value="<?= $k ?>"><?= e($t[1]) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-9"><label class="form-label small mb-1">Detalle</label><input class="form-control form-control-sm" name="texto" maxlength="500" placeholder="Ej: Pidió que le guardemos mesa los viernes" required></div>
          <div class="col-md-5"><label class="form-label small mb-1">Recordar seguimiento el (opcional)</label><input type="date" class="form-control form-control-sm" name="seguimiento" min="<?= e(date('Y-m-d')) ?>"></div>
          <div class="col-md-7 text-end"><button class="btn btn-brand btn-sm"><i class="bi bi-plus-lg me-1"></i>Agregar nota</button></div></div></form>
        <div class="crm-tl">
          <?php foreach ($notas as $n): $ti = Crm::TIPOS_NOTA[$n['tipo']] ?? Crm::TIPOS_NOTA['nota']; ?>
            <div class="crm-tli<?= $n['hecho'] ? ' done' : '' ?>"><span class="ic" style="background:<?= $ti[2] ?>"><i class="bi <?= $ti[0] ?>"></i></span>
              <div class="flex-grow-1"><div class="d-flex flex-wrap gap-2 align-items-center"><b><?= e($ti[1]) ?></b><small class="text-muted"><?= e(fdate($n['created_at'], true)) ?><?= $n['autor'] ? ' · ' . e($n['autor']) : '' ?></small>
                <?php if ($n['seguimiento']): ?><span class="bsoft <?= $n['hecho'] ? 'ok' : ($n['seguimiento'] < date('Y-m-d') ? 'bad' : 'warn') ?>"><i class="bi bi-bell"></i>Seguimiento <?= e(date('d/m', strtotime($n['seguimiento']))) ?><?= $n['hecho'] ? ' ✓' : '' ?></span><?php endif; ?></div>
                <div class="mt-1"><?= e($n['texto']) ?></div></div>
              <div class="d-flex gap-1"><?php if ($n['seguimiento']): ?><button class="btn btn-sm btn-ghost" data-nota-hecho="<?= (int)$n['id'] ?>" data-v="<?= $n['hecho'] ? 0 : 1 ?>" title="<?= $n['hecho'] ? 'Reabrir' : 'Marcar como hecho' ?>"><i class="bi bi-<?= $n['hecho'] ? 'arrow-counterclockwise' : 'check2' ?>"></i></button><?php endif; ?>
                <button class="btn btn-sm btn-ghost text-danger" data-nota-del="<?= (int)$n['id'] ?>" title="Eliminar"><i class="bi bi-trash"></i></button></div></div>
          <?php endforeach; ?>
          <?php if (!$notas): ?><div class="empty"><i class="bi bi-chat-square-heart"></i>Aún no hay notas. Registre llamadas, reclamos y preferencias del cliente.</div><?php endif; ?>
        </div>
      </div>

      <div class="tab-pane fade" id="tRes"><div class="card-soft"><div class="table-wrap"><table class="table table-modern">
        <thead><tr><th>Código</th><th>Fecha</th><th>Hora</th><th>Personas</th><th>Estado</th></tr></thead><tbody>
        <?php foreach ($reservas as $x): [$ec, $el] = Reservas::ESTADOS[$x['estado']]; ?><tr><td class="fw-bold"><?= e($x['codigo']) ?></td><td><?= e(fdate($x['fecha'])) ?></td><td><?= e(substr($x['hora'], 0, 5)) ?></td><td><?= (int)$x['personas'] ?></td><td><span class="bsoft <?= $ec ?>"><?= e($el) ?></span></td></tr><?php endforeach; ?>
        <?php if (!$reservas): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-calendar2"></i>Sin reservas</div></td></tr><?php endif; ?></tbody></table></div></div></div>
    </div>
  </div>

  <div class="col-xl-4">
    <div class="card-soft crm-loy mb-3">
      <div class="card-h"><div><h5><i class="bi bi-stars me-1" style="color:#F59E0B"></i>Programa de fidelidad</h5><small>1 punto por cada <?= e(money($r['soles_por_punto'])) ?> de consumo</small></div></div>
      <div class="card-b pt-1">
        <div class="crm-pts"><b><?= (int)$c['puntos'] ?></b><span>puntos disponibles</span></div>
        <div class="d-flex justify-content-between small mb-1"><span style="color:<?= $nv[1] ?>;font-weight:700"><i class="bi <?= $nv[2] ?>"></i> <?= $nv[0] ?></span><span class="text-muted"><?= $sigMeta ? 'Faltan ' . max(0, $sigMeta - $c['hist']) . ' pts para ' . $sigNom : 'Nivel máximo 🏆' ?></span></div>
        <div class="progress mb-3" style="height:9px"><div class="progress-bar" style="width:<?= round($pct) ?>%;background:linear-gradient(90deg,#FBBF24,#F97316)"></div></div>
        <h6 class="small fw-800 text-uppercase text-muted mb-2" style="letter-spacing:.06em">Canjear recompensa</h6>
        <?php foreach ($premios as $p): $ok = $c['puntos'] >= (int)$p['puntos']; ?>
          <div class="crm-prem<?= $ok ? '' : ' off' ?>"><span class="flex-grow-1"><?= e($p['nombre']) ?><small class="d-block text-muted"><?= (int)$p['puntos'] ?> puntos</small></span>
            <button class="btn btn-sm <?= $ok ? 'btn-brand' : 'btn-ghost' ?>" data-canje="<?= (int)$p['id'] ?>" data-n="<?= e($p['nombre']) ?>" <?= $ok ? '' : 'disabled' ?>>Canjear</button></div>
        <?php endforeach; ?>
        <?php if (!$premios): ?><div class="small text-muted">No hay recompensas configuradas.</div><?php endif; ?>
        <details class="mt-3"><summary class="small fw-bold" style="cursor:pointer">Ajuste manual de puntos</summary>
          <form id="crmAj" class="mt-2 row g-2" data-cid="<?= (int)$c['id'] ?>"><div class="col-4"><input type="number" class="form-control form-control-sm" name="puntos" placeholder="± pts" required></div><div class="col-8"><input class="form-control form-control-sm" name="motivo" placeholder="Motivo" maxlength="140" required></div><div class="col-12"><button class="btn btn-ghost btn-sm w-100">Aplicar ajuste</button></div></form></details>
      </div>
    </div>
    <div class="card-soft"><div class="card-h"><div><h5>Movimientos de puntos</h5><small>Canjes y ajustes</small></div></div><div class="card-b pt-1">
      <?php foreach ($movs as $m): ?><div class="crm-mov"><span class="flex-grow-1"><?= e($m['motivo']) ?><small class="d-block text-muted"><?= e(fdate($m['created_at'], true)) ?></small></span><b class="<?= $m['puntos'] < 0 ? 'text-danger' : 'text-success' ?>"><?= $m['puntos'] > 0 ? '+' : '' ?><?= (int)$m['puntos'] ?></b></div><?php endforeach; ?>
      <?php if (!$movs): ?><div class="small text-muted py-2">Sin canjes ni ajustes. Los puntos por compras se suman automáticamente.</div><?php endif; ?></div></div>
  </div>
</div>
<?php include __DIR__ . '/_modal.php'; ?>
<script>
window.CRM = { cid: <?= (int)$c['id'] ?> };
document.addEventListener('DOMContentLoaded', function () {
  if (typeof Chart === 'undefined') return;
  var D = <?= json_encode($serieJ, JSON_UNESCAPED_UNICODE) ?>;
  new Chart(document.getElementById('chGasto'), { type: 'bar', data: { labels: D.map(function (x) { return x.l; }), datasets: [{ data: D.map(function (x) { return x.t; }), backgroundColor: '#FDBA74', hoverBackgroundColor: '#F97316', borderRadius: 8, maxBarThickness: 34 }] },
    options: { maintainAspectRatio: false, plugins: { legend: { display: false }, tooltip: { callbacks: { label: function (c) { return fmtMoney(c.parsed.y); } } } }, scales: { y: { beginAtZero: true, grid: { color: '#F1F5F9' }, ticks: { callback: function (v) { return fmtNum(v, 0); } } }, x: { grid: { display: false } } } } });
});
</script>
