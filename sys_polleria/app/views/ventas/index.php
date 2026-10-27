<?php
$eb = ['pagado' => ['ok', 'Pagado'], 'anulado' => ['bad', 'Anulado'], 'abierto' => ['warn', 'En curso']];
$tl = ['mesa' => ['info', 'Mesa'], 'llevar' => ['warn', 'Llevar'], 'delivery' => ['violet', 'Delivery']];
?>
<div class="page-head">
  <div><h2>Historial de ventas</h2><p>Consulte, reimprima o anule comprobantes.</p></div>
</div>
<form class="card-soft p-3 mb-3" method="get">
  <input type="hidden" name="r" value="ventas/index">
  <div class="row g-2 align-items-end">
    <div class="col-6 col-md-2"><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= e($f['desde']) ?>"></div>
    <div class="col-6 col-md-2"><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= e($f['hasta']) ?>"></div>
    <div class="col-6 col-md-2"><label class="form-label">Estado</label><select class="form-select" name="estado"><option value="">Todos</option><?php foreach ($eb as $k => $v): ?><option value="<?= $k ?>" <?= $f['estado'] === $k ? 'selected' : '' ?>><?= $v[1] ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-2"><label class="form-label">Tipo</label><select class="form-select" name="tipo"><option value="">Todos</option><?php foreach ($tl as $k => $v): ?><option value="<?= $k ?>" <?= $f['tipo'] === $k ? 'selected' : '' ?>><?= $v[1] ?></option><?php endforeach; ?></select></div>
    <div class="col-8 col-md-3"><label class="form-label">Buscar</label><input class="form-control" name="q" value="<?= e($f['q']) ?>" placeholder="N°, cliente…"></div>
    <div class="col-4 col-md-1"><button class="btn btn-brand w-100"><i class="bi bi-funnel"></i></button></div>
  </div>
</form>
<div class="row g-3 mb-3">
  <div class="col-md-4"><?= kpi_g('green', 'Ventas cobradas', e(money($sum['t'])), 'bi-cash-coin') ?></div>
  <div class="col-md-4"><?= kpi_g('blue', 'Comprobantes', (string)(int)$sum['n'], 'bi-receipt-cutoff') ?></div>
  <div class="col-md-4"><?= kpi_g('rose', 'Anulados', (string)(int)$sum['a'], 'bi-x-octagon') ?></div>
</div>
<div class="card-soft"><div class="table-wrap"><table class="table table-modern">
  <thead><tr><th>Comprobante</th><th>Fecha</th><th>Cliente / Mesa</th><th>Tipo</th><th>Pago</th><th>Estado</th><th class="text-end">Total</th><th class="text-end">Acciones</th></tr></thead>
  <tbody>
  <?php foreach ($rows as $r): [$ec, $el] = $eb[$r['estado']]; [$tc, $tn] = $tl[$r['tipo']]; ?>
    <tr class="<?= $r['estado'] === 'anulado' ? 'text-muted' : '' ?>">
      <td class="fw-semibold"><?= $r['numero'] ? e(Calc::numeroCompleto($r['serie'], $r['numero'])) : '<span class="text-muted">Pedido #' . (int)$r['id'] . '</span>' ?></td>
      <td class="small"><?= e(fdate($r['fecha_pago'] ?: $r['fecha_pedido'], true)) ?></td>
      <td><?= e($r['cliente'] ?: ($r['mesa'] ?: 'Público general')) ?></td>
      <td><span class="bsoft <?= $tc ?>"><?= $tn ?></span></td>
      <td class="small text-capitalize"><?= e($r['metodos'] ?: '—') ?></td>
      <td><span class="bsoft <?= $ec ?>"><?= $el ?></span><?php if ($r['fe_estado']): [$fc, $fl] = Sunat::badge($r['fe_estado']); ?><div><span class="bsoft <?= $fc ?>" title="<?= e($r['fe_mensaje']) ?>"><i class="bi bi-file-earmark-check"></i> SUNAT: <?= $fl ?></span></div><?php endif; ?><?php if ($r['motivo_anulacion']): ?><div class="small text-muted" style="max-width:160px"><?= e($r['motivo_anulacion']) ?></div><?php endif; ?></td>
      <td class="text-end fw-bold"><?= e(money($r['total'])) ?></td>
      <td class="text-end text-nowrap">
        <a class="btn btn-ghost btn-sm" target="_blank" href="<?= e(url('pos/ticket', ['id' => $r['id']])) ?>" title="Ver / imprimir"><i class="bi bi-printer"></i></a>
        <?php if ($r['estado'] !== 'anulado'): ?>
          <button class="btn btn-danger-soft btn-sm" onclick="anular(<?= (int)$r['id'] ?>)" title="Anular"><i class="bi bi-x-octagon"></i></button>
        <?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="8"><div class="empty"><i class="bi bi-receipt"></i>No se encontraron ventas con esos filtros</div></td></tr><?php endif; ?>
  </tbody></table></div></div>
<form method="post" action="<?= e(url('ventas/anular')) ?>" id="frmAnular"><?= csrf_field() ?><input type="hidden" name="id" id="anId"><input type="hidden" name="motivo" id="anMotivo"></form>
<script>
async function anular(id) {
  var r = await Swal.fire({ title: 'Anular pedido #' + id, text: 'Esta acción repone el stock y quita la venta de los reportes.', input: 'text', inputPlaceholder: 'Motivo de la anulación *',
    icon: 'warning', showCancelButton: true, confirmButtonText: 'Anular', cancelButtonText: 'Cancelar', confirmButtonColor: '#EF4444',
    inputValidator: function (v) { if (!v.trim()) return 'Debe indicar el motivo'; } });
  if (r.isConfirmed) { document.getElementById('anId').value = id; document.getElementById('anMotivo').value = r.value; document.getElementById('frmAnular').submit(); }
}
</script>
