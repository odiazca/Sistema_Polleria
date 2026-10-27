<div class="page-head"><div><h2>Compra #<?= (int)$c['id'] ?></h2><p><?= e(fdate($c['fecha'])) ?> · <?= e($c['proveedor'] ?: 'Sin proveedor') ?> <?= $c['comprobante'] ? '· ' . e($c['comprobante']) : '' ?></p></div>
  <div class="d-flex gap-2"><button class="btn btn-ghost" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button><a href="<?= e(url('compras/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a></div></div>
<div class="card-soft"><div class="table-wrap"><table class="table table-modern">
  <thead><tr><th>Insumo</th><th>Cantidad</th><th>Costo unit.</th><th class="text-end">Subtotal</th></tr></thead><tbody>
  <?php foreach ($items as $i): ?><tr><td class="fw-semibold"><?= e($i['nombre']) ?></td><td><?= e(num($i['cantidad'], 2)) ?> <?= e($i['unidad']) ?></td><td><?= e(money($i['costo'])) ?></td><td class="text-end fw-bold"><?= e(money($i['subtotal'])) ?></td></tr><?php endforeach; ?>
  </tbody><tfoot><tr><td colspan="3" class="text-end fw-bold">TOTAL</td><td class="text-end fw-800 fs-5"><?= e(money($c['total'])) ?></td></tr></tfoot></table></div>
  <?php if ($c['notas']): ?><div class="p-3 border-top small text-muted"><i class="bi bi-chat-left-text"></i> <?= e($c['notas']) ?></div><?php endif; ?></div>
