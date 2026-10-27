<?php $tl = ['entrada' => ['ok', 'Entrada', 'bi-box-arrow-in-down'], 'salida' => ['bad', 'Salida', 'bi-box-arrow-up'], 'ajuste' => ['info', 'Ajuste', 'bi-sliders']]; ?>
<div class="page-head">
  <div><h2><?= e($ins['nombre']) ?></h2><p>Stock actual: <b><?= e(num($ins['stock'], 2)) ?> <?= e($ins['unidad']) ?></b> · Mínimo: <?= e(num($ins['stock_minimo'], 2)) ?></p></div>
  <a href="<?= e(url('insumos/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver a insumos</a>
</div>
<div class="row g-4">
  <div class="col-lg-4">
    <form method="post" action="<?= e(url('insumos/ajustar')) ?>" class="card-soft">
      <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$ins['id'] ?>">
      <div class="card-h"><h5>Registrar movimiento</h5></div>
      <div class="card-b">
        <label class="form-label">Tipo</label>
        <select class="form-select mb-3" name="tipo">
          <option value="entrada">Entrada (suma al stock)</option>
          <option value="salida">Salida / merma / consumo (resta)</option>
          <option value="ajuste">Ajuste (fija el stock exacto)</option>
        </select>
        <label class="form-label">Cantidad (<?= e($ins['unidad']) ?>)</label>
        <input type="number" step="0.001" min="0" class="form-control mb-3" name="cantidad" required>
        <label class="form-label">Motivo</label>
        <input class="form-control mb-3" name="motivo" placeholder="Ej: Merma, conteo físico, uso en cocina">
        <button class="btn btn-brand w-100"><i class="bi bi-check2-circle me-1"></i>Registrar</button>
      </div>
    </form>
  </div>
  <div class="col-lg-8">
    <div class="card-soft">
      <div class="card-h"><h5>Historial reciente</h5></div>
      <div class="card-b pt-3"><div class="table-wrap"><table class="table table-modern">
        <thead><tr><th>Fecha</th><th>Tipo</th><th>Cantidad</th><th>Motivo</th><th>Usuario</th></tr></thead>
        <tbody>
        <?php foreach ($movs as $m): [$c, $l, $i] = $tl[$m['tipo']]; ?>
          <tr><td class="text-muted small"><?= e(fdate($m['fecha'], true)) ?></td><td><span class="bsoft <?= $c ?>"><i class="bi <?= $i ?>"></i><?= $l ?></span></td><td class="fw-bold"><?= e(num($m['cantidad'], 2)) ?></td><td><?= e($m['motivo'] ?: '—') ?></td><td class="text-muted small"><?= e($m['usuario'] ?: '—') ?></td></tr>
        <?php endforeach; ?>
        <?php if (!$movs): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-clock-history"></i>Sin movimientos registrados</div></td></tr><?php endif; ?>
        </tbody></table></div></div>
    </div>
  </div>
</div>
