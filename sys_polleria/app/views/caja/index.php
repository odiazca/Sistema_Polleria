<?php
$mlbl = ['efectivo' => ['Efectivo', 'bi-cash-stack', 't-ok'], 'tarjeta' => ['Tarjeta', 'bi-credit-card', 't-info'], 'yape' => ['Yape', 'bi-phone', 't-violet'], 'plin' => ['Plin', 'bi-phone-vibrate', 't-info'], 'transferencia' => ['Transferencia', 'bi-bank', 't-gray']];
$c = $r['caja'] ?? null;
$abiertaVista = $c && $c['estado'] === 'abierta';
$propia = $c && (int)$c['usuario_id'] === Auth::id();
?>
<div class="page-head">
  <div><h2>Caja</h2><p>Apertura, movimientos de efectivo y arqueo al cierre del turno.</p></div>
  <?php if ($c && !$abierta): ?><a href="<?= e(url('caja/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a><?php endif; ?>
</div>

<?php if (!$c): ?>
  <div class="row justify-content-center"><div class="col-lg-5">
    <div class="card-soft text-center p-4">
      <div class="mx-auto mb-3 t-ok" style="width:76px;height:76px;border-radius:24px;display:grid;place-items:center;font-size:2.2rem"><i class="bi bi-unlock-fill"></i></div>
      <h4 class="fw-800">Abrir caja</h4>
      <p class="text-muted">Registre el dinero con el que inicia su turno para poder cobrar.</p>
      <form method="post" action="<?= e(url('caja/abrir')) ?>">
        <?= csrf_field() ?>
        <label class="form-label text-start d-block">Monto inicial en efectivo</label>
        <div class="input-group input-group-lg mb-3"><span class="input-group-text"><?= e(cfg('moneda_simbolo')) ?></span><input type="number" step="0.01" min="0" name="monto" class="form-control fw-bold" value="0" required autofocus></div>
        <button class="btn btn-ok btn-lg w-100"><i class="bi bi-unlock me-1"></i>Abrir caja</button>
      </form>
    </div>
  </div></div>
<?php else: ?>
  <div class="d-flex align-items-center gap-2 mb-3">
    <span class="bsoft <?= $abiertaVista ? 'ok' : 'gray' ?> py-2 px-3"><i class="bi bi-circle-fill" style="font-size:.5rem"></i><?= $abiertaVista ? 'Caja abierta' : 'Caja cerrada' ?></span>
    <span class="text-muted small">Caja #<?= (int)$c['id'] ?> · apertura <?= e(fdate($c['fecha_apertura'], true)) ?><?= $c['fecha_cierre'] ? ' · cierre ' . e(fdate($c['fecha_cierre'], true)) : '' ?></span>
  </div>
  <div class="row g-3 mb-3">
    <div class="col-6 col-xl-3"><?= kpi_g('blue', 'Monto inicial', e(money($c['monto_inicial'])), 'bi-box-arrow-in-down') ?></div>
    <div class="col-6 col-xl-3"><?= kpi_g('green', 'Ventas', e(money($r['ventasT'])), 'bi-receipt', '', (int)$r['ventasN'] . ' comprobantes') ?></div>
    <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Ingresos / egresos', '<small>+' . e(money($r['ing'])) . ' · −' . e(money($r['egr'])) . '</small>', 'bi-arrow-left-right') ?></div>
    <div class="col-6 col-xl-3"><?= kpi_g('brand', 'Efectivo esperado', e(money($c['estado'] === 'cerrada' ? $c['monto_esperado'] : $r['esperado'])), 'bi-cash-stack') ?></div>
  </div>

  <div class="row g-3">
    <div class="col-xl-7">
      <div class="card-soft mb-3">
        <div class="card-h"><div><h5>Ventas por método de pago</h5><small>Solo comprobantes cobrados en esta caja</small></div></div>
        <div class="card-b"><div class="row g-2">
          <?php foreach ($mlbl as $k => [$l, $i, $cl]): $v = (float)($r['pm'][$k] ?? 0); ?>
            <div class="col-6 col-md-4"><div class="p-3 rounded-4 border d-flex align-items-center gap-2"><span class="rank <?= $cl ?>"><i class="bi <?= $i ?>"></i></span><div><div class="small text-muted"><?= $l ?></div><div class="fw-bold"><?= e(money($v)) ?></div></div></div></div>
          <?php endforeach; ?>
        </div></div>
      </div>
      <div class="card-soft">
        <div class="card-h"><div><h5>Movimientos de caja</h5><small>Ingresos y egresos manuales</small></div></div>
        <div class="card-b pt-3">
          <?php if ($abiertaVista && $propia): ?>
          <form method="post" action="<?= e(url('caja/movimiento')) ?>" class="row g-2 mb-3">
            <?= csrf_field() ?>
            <div class="col-md-3"><select name="tipo" class="form-select"><option value="egreso">Egreso (salida)</option><option value="ingreso">Ingreso</option></select></div>
            <div class="col-md-4"><input name="concepto" class="form-control" placeholder="Concepto (ej: compra de hielo)" required></div>
            <div class="col-md-3"><input type="number" step="0.01" min="0.01" name="monto" class="form-control" placeholder="Monto" required></div>
            <div class="col-md-2"><button class="btn btn-soft w-100"><i class="bi bi-plus-lg"></i></button></div>
          </form>
          <?php endif; ?>
          <div class="table-wrap"><table class="table table-modern"><thead><tr><th>Hora</th><th>Tipo</th><th>Concepto</th><th class="text-end">Monto</th></tr></thead><tbody>
            <?php foreach ($r['movs'] as $m): ?><tr><td class="small text-muted"><?= e(date('H:i', strtotime($m['fecha']))) ?></td><td><span class="bsoft <?= $m['tipo'] === 'ingreso' ? 'ok' : 'bad' ?>"><?= ucfirst($m['tipo']) ?></span></td><td><?= e($m['concepto']) ?></td><td class="text-end fw-bold"><?= e(money($m['monto'])) ?></td></tr><?php endforeach; ?>
            <?php if (!$r['movs']): ?><tr><td colspan="4"><div class="empty py-4">Sin movimientos manuales</div></td></tr><?php endif; ?>
          </tbody></table></div>
        </div>
      </div>
    </div>
    <div class="col-xl-5">
      <?php if ($abiertaVista && $propia): ?>
        <form method="post" action="<?= e(url('caja/cerrar')) ?>" class="card-soft" data-confirm="Se cerrará su caja y se registrará el arqueo." data-btn="Cerrar caja" data-icon="question">
          <?= csrf_field() ?>
          <div class="card-h"><div><h5>Cerrar caja (arqueo)</h5><small>Cuente el efectivo y registre el monto</small></div></div>
          <div class="card-b">
            <?php if ($pendientes): ?><div class="alert border-0 small" style="background:#FFFBEB;color:#92400E;border-radius:14px"><i class="bi bi-exclamation-triangle-fill me-1"></i>Hay <?= $pendientes ?> pedido(s) en curso sin cobrar.</div><?php endif; ?>
            <div class="d-flex justify-content-between p-3 rounded-4 mb-3" style="background:var(--brand-50)"><span class="fw-semibold">Efectivo esperado</span><b><?= e(money($r['esperado'])) ?></b></div>
            <label class="form-label">Efectivo contado</label>
            <div class="input-group input-group-lg mb-3"><span class="input-group-text"><?= e(cfg('moneda_simbolo')) ?></span><input type="number" step="0.01" min="0" name="contado" id="contado" class="form-control fw-bold" required></div>
            <div class="d-flex justify-content-between mb-3"><span class="text-muted">Diferencia</span><b id="dif">—</b></div>
            <label class="form-label">Observaciones</label>
            <textarea name="observacion" rows="2" class="form-control mb-3"></textarea>
            <button class="btn btn-danger w-100"><i class="bi bi-lock-fill me-1"></i>Cerrar caja</button>
          </div>
        </form>
        <script>document.getElementById('contado').addEventListener('input',function(e){var d=(parseFloat(e.target.value)||0)-<?= json_encode(round($r['esperado'], 2)) ?>,el=document.getElementById('dif');el.textContent=fmtMoney(d);el.className=d<0?'text-danger':(d>0?'text-success':'');});</script>
      <?php elseif ($c['estado'] === 'cerrada'): ?>
        <div class="card-soft">
          <div class="card-h"><h5>Resultado del arqueo</h5></div>
          <div class="card-b">
            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Esperado</span><b><?= e(money($c['monto_esperado'])) ?></b></div>
            <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Contado</span><b><?= e(money($c['monto_contado'])) ?></b></div>
            <div class="d-flex justify-content-between py-2"><span class="text-muted">Diferencia</span><b class="<?= $c['diferencia'] < 0 ? 'text-danger' : ($c['diferencia'] > 0 ? 'text-success' : '') ?>"><?= e(money($c['diferencia'])) ?></b></div>
            <?php if ($c['observacion']): ?><div class="small text-muted mt-2"><i class="bi bi-chat-left-text"></i> <?= e($c['observacion']) ?></div><?php endif; ?>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php endif; ?>

<div class="card-soft mt-4">
  <div class="card-h"><div><h5>Historial de cajas</h5></div></div>
  <div class="card-b pt-3"><div class="table-wrap"><table class="table table-modern">
    <thead><tr><th>#</th><th>Cajero</th><th>Apertura</th><th>Cierre</th><th>Inicial</th><th>Esperado</th><th>Contado</th><th>Dif.</th><th>Estado</th><th></th></tr></thead><tbody>
    <?php foreach ($hist as $h): ?>
      <tr><td class="fw-bold"><?= (int)$h['id'] ?></td><td><?= e($h['usuario']) ?></td><td class="small"><?= e(fdate($h['fecha_apertura'], true)) ?></td><td class="small"><?= e(fdate($h['fecha_cierre'], true)) ?></td>
        <td><?= e(money($h['monto_inicial'])) ?></td><td><?= $h['monto_esperado'] !== null ? e(money($h['monto_esperado'])) : '—' ?></td><td><?= $h['monto_contado'] !== null ? e(money($h['monto_contado'])) : '—' ?></td>
        <td class="<?= (float)$h['diferencia'] < 0 ? 'text-danger' : '' ?>"><?= $h['diferencia'] !== null ? e(money($h['diferencia'])) : '—' ?></td>
        <td><span class="bsoft <?= $h['estado'] === 'abierta' ? 'ok' : 'gray' ?>"><?= ucfirst($h['estado']) ?></span></td>
        <td class="text-end"><a class="btn btn-ghost btn-sm" href="<?= e(url('caja/index', ['id' => $h['id']])) ?>"><i class="bi bi-eye"></i></a></td></tr>
    <?php endforeach; ?>
    <?php if (!$hist): ?><tr><td colspan="10"><div class="empty py-4">Aún no hay cajas registradas</div></td></tr><?php endif; ?>
    </tbody></table></div></div>
</div>
