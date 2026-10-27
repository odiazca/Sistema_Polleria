<div class="page-head">
  <div><h2>Compras de insumos</h2><p>Registre sus compras para actualizar automáticamente el inventario.</p></div>
  <a href="<?= e(url('compras/nueva')) ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Nueva compra</a>
</div>
<form class="card-soft p-3 mb-3 d-flex gap-2 align-items-end flex-wrap" method="get">
  <input type="hidden" name="r" value="compras/index">
  <div><label class="form-label">Desde</label><input type="date" class="form-control" name="desde" value="<?= e($desde) ?>"></div>
  <div><label class="form-label">Hasta</label><input type="date" class="form-control" name="hasta" value="<?= e($hasta) ?>"></div>
  <button class="btn btn-soft"><i class="bi bi-funnel me-1"></i>Filtrar</button>
  <div class="ms-auto text-end"><div class="small text-muted">Total del período</div><div class="fw-800 fs-4"><?= e(money($total)) ?></div></div>
</form>
<div class="card-soft"><div class="table-wrap"><table class="table table-modern">
  <thead><tr><th>#</th><th>Fecha</th><th>Proveedor</th><th>Comprobante</th><th>Ítems</th><th>Registró</th><th class="text-end">Total</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): ?>
    <tr><td class="fw-bold"><?= (int)$r['id'] ?></td><td><?= e(fdate($r['fecha'])) ?></td><td><?= e($r['proveedor'] ?: '—') ?></td><td class="text-muted"><?= e($r['comprobante'] ?: '—') ?></td><td><?= (int)$r['items'] ?></td><td class="small text-muted"><?= e($r['usuario']) ?></td><td class="text-end fw-bold"><?= e(money($r['total'])) ?></td>
      <td class="text-end text-nowrap"><a class="btn btn-ghost btn-sm" href="<?= e(url('compras/ver', ['id' => $r['id']])) ?>"><i class="bi bi-eye"></i></a>
        <?php if (Auth::isAdmin()): ?><form method="post" action="<?= e(url('compras/eliminar')) ?>" class="d-inline" data-confirm="Se eliminará la compra y se revertirá el stock ingresado." data-danger="1" data-btn="Eliminar"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><button class="btn btn-danger-soft btn-sm"><i class="bi bi-trash"></i></button></form><?php endif; ?></td></tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="8"><div class="empty"><i class="bi bi-cart"></i>No hay compras en este período</div></td></tr><?php endif; ?>
  </tbody></table></div></div>
