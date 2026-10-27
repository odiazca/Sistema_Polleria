<?php /** Parcial: solicitudes QR pendientes */ if (!$solicitudes) return; ?>
<div class="card-soft mb-4" style="border:2px solid var(--brand);background:linear-gradient(135deg,#FFF7ED,#fff)">
  <div class="d-flex align-items-center gap-2 mb-3"><i class="bi bi-qr-code-scan fs-4 text-brand"></i><h6 class="fw-bold mb-0">Pedidos por QR esperando confirmación (<?= count($solicitudes) ?>)</h6></div>
  <div class="row g-3">
  <?php foreach ($solicitudes as $s): ?>
    <div class="col-md-6 col-xl-4">
      <div class="border rounded-4 p-3 bg-white h-100">
        <div class="d-flex justify-content-between"><b><?= e($s['mesa']) ?> <small class="text-muted fw-normal">· <?= e($s['zona']) ?></small></b><span class="bsoft info">hace <?= (int)$s['mins'] ?> min</span></div>
        <?php if ($s['cliente_nombre']): ?><div class="small text-muted"><i class="bi bi-person"></i> <?= e($s['cliente_nombre']) ?></div><?php endif; ?>
        <ul class="list-unstyled small my-2">
          <?php foreach ($s['lista'] as $i): ?><li><b><?= (int)$i['cantidad'] ?>×</b> <?= e($i['nombre']) ?><?php if (!empty($i['nota'])): ?> <em class="text-muted">(<?= e($i['nota']) ?>)</em><?php endif; ?></li><?php endforeach; ?>
        </ul>
        <?php if ($s['notas']): ?><div class="small bg-light rounded-3 p-2 mb-2"><i class="bi bi-chat-left-text"></i> <?= e($s['notas']) ?></div><?php endif; ?>
        <div class="d-flex justify-content-between align-items-center">
          <b class="text-brand"><?= e(money($s['total'])) ?></b>
          <div class="d-flex gap-1">
            <button class="btn btn-sm btn-ghost" data-qr-rej="<?= (int)$s['id'] ?>"><i class="bi bi-x-lg"></i></button>
            <button class="btn btn-sm btn-brand" data-qr-ok="<?= (int)$s['id'] ?>"><i class="bi bi-check-lg me-1"></i>Aceptar</button>
          </div>
        </div>
      </div>
    </div>
  <?php endforeach; ?>
  </div>
</div>
