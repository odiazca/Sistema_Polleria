<?php
$con = count(array_filter($rows, fn($r) => $r['n_insumos'] > 0));
?>
<div class="page-head">
  <div><h2>Recetas</h2><p>Defina qué insumos usa cada plato: al vender se descuentan del inventario y el costo se calcula solo.</p></div>
  <div class="search-box"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar plato…" data-filter-table="#tbl" style="min-width:230px"></div>
</div>
<div class="row g-3 mb-3">
  <div class="col-md-4"><div class="card-soft kpi"><div class="lbl">Platos con receta</div><div class="val"><?= $con ?> <small class="text-muted fs-6">/ <?= count($rows) ?></small></div><div class="progress mt-2"><div class="progress-bar" style="width:<?= count($rows) ? round($con / count($rows) * 100) : 0 ?>%"></div></div></div></div>
  <div class="col-md-8"><div class="card-soft p-3 h-100 d-flex gap-3 align-items-center" style="background:#F0F9FF;border-color:#BAE6FD"><i class="bi bi-info-circle-fill fs-4 text-primary"></i><div class="small text-muted">Las bebidas con <b>control de stock</b> se descuentan solas y no necesitan receta. Los platos <b>sin receta</b> se venden sin mover el inventario de insumos.</div></div></div>
</div>
<div class="card-soft"><div class="table-wrap"><table class="table table-modern" id="tbl">
  <thead><tr><th>Plato</th><th>Categoría</th><th>Precio</th><th>Costo</th><th>Margen</th><th>Receta</th><th></th></tr></thead><tbody>
  <?php foreach ($rows as $r): $m = $r['precio'] > 0 ? ($r['precio'] - $r['costo']) / $r['precio'] * 100 : 0; ?>
    <tr>
      <td class="fw-bold"><?= e($r['nombre']) ?></td>
      <td><span class="bsoft gray"><?= e($r['categoria']) ?></span></td>
      <td><?= e(money($r['precio'])) ?></td>
      <td><?= e(money($r['costo'])) ?></td>
      <td><span class="bsoft <?= $m >= 50 ? 'ok' : ($m >= 30 ? 'warn' : 'bad') ?>"><?= round($m) ?>%</span></td>
      <td><?= $r['n_insumos'] ? '<span class="bsoft ok"><i class="bi bi-check-circle-fill"></i>' . (int)$r['n_insumos'] . ' insumos</span>' : '<span class="bsoft gray">Sin receta</span>' ?></td>
      <td class="text-end"><a class="btn btn-soft btn-sm" href="<?= e(url('recetas/editar', ['id' => $r['id']])) ?>"><i class="bi bi-journal-richtext me-1"></i><?= $r['n_insumos'] ? 'Editar' : 'Crear' ?></a></td>
    </tr>
  <?php endforeach; ?>
  <?php if (!$rows): ?><tr><td colspan="7"><div class="empty"><i class="bi bi-journal"></i>No hay productos activos</div></td></tr><?php endif; ?>
  </tbody></table></div></div>
