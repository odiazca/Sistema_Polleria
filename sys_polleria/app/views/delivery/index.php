<?php
$est = ['pendiente' => ['warn', 'Pendiente'], 'en_camino' => ['info', 'En camino'], 'entregado' => ['ok', 'Entregado'], 'cancelado' => ['bad', 'Cancelado']];
$tabs = ['activos' => 'Activos', 'pendiente' => 'Pendientes', 'en_camino' => 'En camino', 'entregado' => 'Entregados (48 h)'];
?>
<div class="page-head">
  <div><h2>Delivery</h2><p>Asigne repartidores y controle el estado de cada entrega.</p></div>
  <?php if (Auth::can('pos')): ?><a href="<?= e(url('pos/index', ['tipo' => 'delivery'])) ?>" class="btn btn-brand"><i class="bi bi-plus-lg me-1"></i>Nuevo pedido delivery</a><?php endif; ?>
</div>
<div class="d-flex gap-2 flex-wrap mb-3">
  <?php foreach ($tabs as $k => $l): ?>
    <a href="<?= e(url('delivery/index', ['estado' => $k])) ?>" class="btn btn-sm <?= $f === $k ? 'btn-brand' : 'btn-ghost' ?>"><?= e($l) ?>
      <?php if ($k === 'pendiente' && !empty($cont['pendiente'])): ?><span class="badge bg-light text-dark ms-1"><?= $cont['pendiente'] ?></span><?php endif; ?>
      <?php if ($k === 'en_camino' && !empty($cont['en_camino'])): ?><span class="badge bg-light text-dark ms-1"><?= $cont['en_camino'] ?></span><?php endif; ?></a>
  <?php endforeach; ?>
</div>
<div class="row g-3">
<?php foreach ($rows as $r): [$ec, $el] = $est[$r['delivery_estado'] ?? 'pendiente']; ?>
  <div class="col-md-6 col-xl-4">
    <div class="card-soft h-100 d-flex flex-column">
      <div class="card-h pb-2">
        <div><h5>Pedido #<?= (int)$r['id'] ?></h5><small><?= e(fdate($r['fecha_pedido'], true)) ?></small></div>
        <span class="bsoft <?= $ec ?>"><?= $el ?></span>
      </div>
      <div class="card-b flex-grow-1 pt-2">
        <div class="mb-2"><i class="bi bi-person text-muted me-1"></i><b><?= e($r['cliente'] ?: 'Cliente no registrado') ?></b></div>
        <div class="small mb-1"><i class="bi bi-geo-alt text-muted me-1"></i><?= e($r['direccion_entrega'] ?: '—') ?></div>
        <div class="small mb-3"><i class="bi bi-telephone text-muted me-1"></i><?= e($r['telefono_entrega'] ?: '—') ?></div>
        <div class="d-flex justify-content-between align-items-center p-2 rounded-3 bg-light mb-3">
          <span class="fw-800 fs-5"><?= e(money($r['total'])) ?></span>
          <?php if ($r['estado'] === 'pagado'): ?><span class="bsoft ok"><i class="bi bi-check-circle-fill"></i>Pagado</span>
          <?php else: ?><span class="bsoft warn"><i class="bi bi-hourglass-split"></i>Por cobrar</span><?php endif; ?>
        </div>
        <form method="post" action="<?= e(url('delivery/actualizar')) ?>" class="d-flex gap-2 mb-2">
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="<?= e($f) ?>">
          <select name="repartidor_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">— Repartidor —</option>
            <?php foreach ($reps as $rp): ?><option value="<?= (int)$rp['id'] ?>" <?= (int)$r['repartidor_id'] === (int)$rp['id'] ? 'selected' : '' ?>><?= e($rp['nombre']) ?></option><?php endforeach; ?>
          </select>
        </form>
      </div>
      <div class="p-3 pt-0 d-flex gap-2 flex-wrap">
        <?php if ($r['delivery_estado'] === 'pendiente'): ?>
          <form method="post" action="<?= e(url('delivery/actualizar')) ?>" class="flex-fill"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="<?= e($f) ?>"><input type="hidden" name="repartidor_id" value="<?= (int)$r['repartidor_id'] ?>"><input type="hidden" name="delivery_estado" value="en_camino"><button class="btn btn-soft btn-sm w-100"><i class="bi bi-bicycle me-1"></i>Enviar</button></form>
        <?php elseif ($r['delivery_estado'] === 'en_camino'): ?>
          <form method="post" action="<?= e(url('delivery/actualizar')) ?>" class="flex-fill"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>"><input type="hidden" name="back" value="<?= e($f) ?>"><input type="hidden" name="delivery_estado" value="entregado"><button class="btn btn-ok btn-sm w-100"><i class="bi bi-check2-circle me-1"></i>Marcar entregado</button></form>
        <?php endif; ?>
        <?php if ($r['estado'] === 'abierto' && Auth::can('pos')): ?><a href="<?= e(url('pos/index', ['pedido' => $r['id']])) ?>" class="btn btn-brand btn-sm flex-fill"><i class="bi bi-cash-coin me-1"></i>Cobrar / editar</a><?php endif; ?>
        <a href="<?= e(url('pos/ticket', ['id' => $r['id']])) ?>" target="_blank" class="btn btn-ghost btn-sm"><i class="bi bi-printer"></i></a>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php if (!$rows): ?><div class="card-soft"><div class="empty" style="padding:70px 20px"><i class="bi bi-bicycle"></i>No hay pedidos delivery en esta vista.</div></div><?php endif; ?>
