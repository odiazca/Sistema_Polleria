<div class="page-head">
  <div><h2>Facturación electrónica</h2><p>Estado de boletas y facturas ante SUNAT.
    <?php if (!Sunat::activo()): ?><span class="bsoft warn ms-1">Desactivada</span><?php elseif (cfg('fe_modo') === 'simulado'): ?><span class="bsoft info ms-1">Modo simulado</span><?php else: ?><span class="bsoft ok ms-1">Producción / proveedor</span><?php endif; ?></p></div>
  <div class="d-flex gap-2 flex-wrap">
    <form method="post" action="<?= e(url('facturacion/enviarPendientes')) ?>"><?= csrf_field() ?><button class="btn btn-brand"><i class="bi bi-send me-1"></i>Enviar pendientes</button></form>
    <a href="<?= e(url('facturacion/config')) ?>" class="btn btn-ghost"><i class="bi bi-gear me-1"></i>Configuración</a>
  </div>
</div>
<div class="row g-3 mb-3">
  <div class="col-6 col-xl-3"><?= kpi_g('green', 'Aceptados', (string)(int)($cnt['aceptado'] ?? 0), 'bi-check2-circle') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Pendientes / sin enviar', (string)((int)($cnt['pendiente'] ?? 0) + (int)($cnt['sin'] ?? 0)), 'bi-hourglass-split') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('rose', 'Rechazados / error', (string)((int)($cnt['rechazado'] ?? 0) + (int)($cnt['error'] ?? 0)), 'bi-exclamation-octagon') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('blue', 'Comprobantes', (string)array_sum($cnt), 'bi-file-earmark-text') ?></div>
</div>
<div class="card-soft mb-3 p-3">
  <form class="row g-2 align-items-end" method="get"><input type="hidden" name="r" value="facturacion/index">
    <div class="col-6 col-md-3"><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= e($f['desde']) ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= e($f['hasta']) ?>"></div>
    <div class="col-6 col-md-3"><label class="form-label">Estado SUNAT</label><select class="form-select" name="estado"><option value="">Todos</option>
      <?php foreach (['sinenvio' => 'Sin enviar', 'pendiente' => 'Pendiente', 'aceptado' => 'Aceptado', 'rechazado' => 'Rechazado', 'error' => 'Error', 'anulado' => 'Baja'] as $k => $v): ?><option value="<?= $k ?>" <?= $f['est'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?></select></div>
    <div class="col-6 col-md-3"><button class="btn btn-brand w-100"><i class="bi bi-funnel me-1"></i>Filtrar</button></div>
  </form>
</div>
<div class="card-soft"><div class="table-responsive"><table class="table align-middle mb-0">
  <thead><tr><th>Comprobante</th><th>Fecha</th><th>Cliente</th><th>Tipo</th><th class="text-end">Total</th><th>SUNAT</th><th class="text-end"></th></tr></thead><tbody>
  <?php foreach ($rows as $r): [$bc, $bl] = Sunat::badge($r['fe_estado']); ?>
    <tr>
      <td class="fw-semibold"><?= e(Calc::numeroCompleto($r['serie'], $r['numero'])) ?></td>
      <td class="small"><?= e(fdate($r['fecha_pago'], true)) ?></td>
      <td><?= e($r['cliente'] ?: 'Clientes varios') ?><?php if ($r['documento']): ?><div class="small text-muted"><?= e($r['documento']) ?></div><?php endif; ?></td>
      <td><span class="bsoft <?= $r['comprobante'] === 'factura' ? 'violet' : 'info' ?> text-capitalize"><?= e($r['comprobante']) ?></span></td>
      <td class="text-end fw-bold"><?= e(money($r['total'])) ?></td>
      <td><span class="bsoft <?= $r['fe_estado'] ? $bc : 'gray' ?>"><?= $r['fe_estado'] ? $bl : 'Sin enviar' ?></span><?php if ($r['fe_mensaje']): ?><div class="small text-muted" style="max-width:260px"><?= e($r['fe_mensaje']) ?></div><?php endif; ?></td>
      <td class="text-end text-nowrap">
        <?php if ($r['fe_enlace']): ?><a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="<?= e($r['fe_enlace']) ?>" title="Ver PDF/XML"><i class="bi bi-file-earmark-pdf"></i></a><?php endif; ?>
        <?php if ($r['estado'] === 'pagado' && !in_array($r['fe_estado'], ['aceptado', 'anulado'], true)): ?><button class="btn btn-brand btn-sm" data-fe="<?= (int)$r['id'] ?>"><i class="bi bi-send"></i> Enviar</button><?php endif; ?>
      </td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7"><div class="empty"><i class="bi bi-file-earmark-text"></i>No hay boletas ni facturas en este rango.</div></td></tr><?php endif; ?>
  </tbody></table></div></div>
<script>
document.addEventListener('DOMContentLoaded',function(){
  document.querySelectorAll('[data-fe]').forEach(function(b){b.addEventListener('click',function(){
    b.disabled=true;b.innerHTML='<span class="spinner-border spinner-border-sm"></span>';
    fetch(APP.base+'/index.php?r=facturacion/enviar',{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-Token':APP.csrf},body:JSON.stringify({id:+b.dataset.fe})})
    .then(function(r){return r.json()}).then(function(d){Swal.fire({toast:true,position:'top-end',icon:d.ok?'success':'error',title:d.msg||'Listo',showConfirmButton:false,timer:3500});setTimeout(function(){location.reload()},1200)})
    .catch(function(){b.disabled=false;b.textContent='Enviar'})})});
});
</script>
