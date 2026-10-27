<?php
$tl = ['mesa' => 'Mesa', 'llevar' => 'Para llevar', 'delivery' => 'Delivery'];
$next = ['pendiente' => ['preparando', 'Preparar', 'btn-soft', 'bi-play-fill'], 'preparando' => ['listo', 'Listo', 'btn-ok', 'bi-check2'], 'listo' => ['entregado', 'Entregar', 'btn-ghost', 'bi-box-arrow-right']];
$badge = ['pendiente' => 'warn', 'preparando' => 'info', 'listo' => 'ok'];
?>
<?php if (!$pedidos): ?>
  <div class="card-soft"><div class="empty" style="padding:80px 20px"><i class="bi bi-emoji-smile" style="font-size:3.5rem;color:#10B981"></i><h5 class="fw-bold text-dark mt-2">¡Todo al día!</h5>No hay pedidos pendientes en cocina.</div></div>
<?php else: ?>
<div class="row g-3">
<?php foreach ($pedidos as $p):
    $todoListo = !array_filter($p['items'], fn($i) => $i['estado'] !== 'listo');
    $late = $p['mins'] >= 20; ?>
  <div class="col-md-6 col-xl-4 col-xxl-3">
    <div class="kds <?= $todoListo ? 'done' : ($late ? 'late' : '') ?>">
      <div class="kds-h">
        <div>
          <div class="fw-800">#<?= (int)$p['id'] ?> · <?= $p['tipo'] === 'mesa' ? e($p['mesa'] ?: 'Mesa') : e($tl[$p['tipo']]) ?></div>
          <div class="small text-muted"><?= e($p['mozo'] ?: '') ?><?= $p['cliente'] ? ' · ' . e($p['cliente']) : '' ?></div>
        </div>
        <span class="bsoft <?= $todoListo ? 'ok' : ($late ? 'bad' : 'warn') ?>"><i class="bi bi-clock"></i><?= $p['mins'] ?> min</span>
      </div>
      <div class="flex-grow-1">
        <?php foreach ($p['items'] as $i): ?>
          <div class="kds-i">
            <div class="kds-q"><?= e(rtrim(rtrim(number_format($i['cantidad'], 2, '.', ''), '0'), '.')) ?>×</div>
            <div class="flex-grow-1">
              <div class="fw-bold"><?= e($i['nombre']) ?></div>
              <?php if ($i['nota']): ?><span class="kds-note"><i class="bi bi-exclamation-circle"></i> <?= e($i['nota']) ?></span><?php endif; ?>
            </div>
            <?php [$ns, $nl, $nc, $ni] = $next[$i['estado']]; ?>
            <button class="btn btn-sm <?= $nc ?>" data-id="<?= (int)$i['id'] ?>" data-set="<?= $ns ?>"><i class="bi <?= $ni ?>"></i> <?= $nl ?></button>
          </div>
        <?php endforeach; ?>
      </div>
      <?php if ($p['notas']): ?><div class="px-3 py-2 small" style="background:#FFFBEB;color:#92400E"><i class="bi bi-chat-left-text"></i> <?= e($p['notas']) ?></div><?php endif; ?>
      <div class="p-2 d-flex gap-2">
        <?php if (!$todoListo): ?><button class="btn btn-ok btn-sm flex-fill" data-pedido="<?= (int)$p['id'] ?>" data-set="listo"><i class="bi bi-check2-all me-1"></i>Todo listo</button><?php endif; ?>
        <button class="btn btn-ghost btn-sm flex-fill" data-pedido="<?= (int)$p['id'] ?>" data-set="entregado"><i class="bi bi-box-arrow-right me-1"></i>Entregar todo</button>
      </div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<?php endif; ?>
