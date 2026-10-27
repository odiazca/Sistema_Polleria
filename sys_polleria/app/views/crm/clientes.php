<?php /** Clientes 360: lista segmentada */
$S = Crm::SEG;
?>
<div class="page-head">
  <div><h2>Clientes 360</h2><p>Todos sus clientes con su historial, nivel de fidelidad y segmento.</p></div>
  <button class="btn btn-brand" id="crmNew"><i class="bi bi-person-plus me-1"></i>Nuevo cliente</button>
</div>
<?php include __DIR__ . '/_nav.php'; ?>

<div class="crm-chips mb-3" id="crmChips">
  <button data-s="" class="on">Todos <b><?= (int)$total ?></b></button>
  <?php foreach ($S as $k => $s): ?><button data-s="<?= $k ?>" style="--c:<?= $s[2] ?>"><i class="bi <?= $s[3] ?>"></i><?= e($s[0]) ?> <b><?= (int)$seg[$k] ?></b></button><?php endforeach; ?>
</div>

<div class="card-soft">
  <div class="card-h flex-wrap gap-2">
    <div class="search-box"><i class="bi bi-search"></i><input type="search" id="crmQ" class="form-control" placeholder="Buscar por nombre, teléfono o etiqueta…" style="min-width:280px"></div>
    <div class="d-flex gap-2 align-items-center"><small class="text-muted">Ordenar por</small>
      <select id="crmSort" class="form-select form-select-sm" style="width:auto"><option value="nombre">Nombre</option><option value="total">Mayor gasto</option><option value="n">Más visitas</option><option value="dias">Compra más reciente</option><option value="puntos">Más puntos</option></select></div>
  </div>
  <div class="table-wrap"><table class="table table-modern" id="crmTbl">
    <thead><tr><th>Cliente</th><th>Segmento</th><th>Fidelidad</th><th class="text-end">Visitas</th><th class="text-end">Gasto total</th><th class="text-end">Ticket prom.</th><th>Última compra</th><th></th></tr></thead>
    <tbody>
    <?php foreach ($rows as $c): $sg = $S[$c['seg']]; $nv = Crm::NIVELES[$c['nivel']]; $w = Crm::wa($c['telefono'], 'Hola ' . ucfirst(mb_strtolower(explode(' ', trim($c['nombre']))[0])) . ', le saludamos de ' . cfg('empresa_nombre', APP_NAME) . '.'); ?>
      <tr data-seg="<?= $c['seg'] ?>" data-q="<?= e(mb_strtolower($c['nombre'] . ' ' . $c['telefono'] . ' ' . $c['etiquetas'] . ' ' . $c['documento'])) ?>" data-nombre="<?= e(mb_strtolower($c['nombre'])) ?>" data-total="<?= (float)$c['total'] ?>" data-n="<?= (int)$c['n'] ?>" data-dias="<?= $c['dias'] === null ? 99999 : (int)$c['dias'] ?>" data-puntos="<?= (int)$c['puntos'] ?>">
        <td><a href="<?= e(url('crm/ficha', ['id' => $c['id']])) ?>" class="d-flex align-items-center gap-2 text-decoration-none">
          <span class="crm-av" style="background:<?= $sg[2] ?>"><?= e(Crm::iniciales($c['nombre'])) ?></span>
          <span><b class="d-block text-dark"><?= e($c['nombre']) ?></b><small class="text-muted"><?= e($c['telefono'] ?: 'Sin teléfono') ?></small></span></a></td>
        <td><span class="bsoft <?= $sg[1] ?>"><i class="bi <?= $sg[3] ?>"></i><?= e($sg[0]) ?></span></td>
        <td><span class="crm-nv" style="color:<?= $nv[1] ?>"><i class="bi <?= $nv[2] ?>"></i> <?= $nv[0] ?></span><small class="text-muted d-block"><?= (int)$c['puntos'] ?> pts</small></td>
        <td class="text-end"><?= (int)$c['n'] ?></td>
        <td class="text-end fw-bold"><?= e(money($c['total'])) ?></td>
        <td class="text-end"><?= $c['n'] ? e(money($c['ticket'])) : '—' ?></td>
        <td><?= $c['ultima'] ? '<span>' . e(fdate($c['ultima'])) . '</span><small class="text-muted d-block">hace ' . (int)$c['dias'] . ' d</small>' : '<span class="text-muted">—</span>' ?></td>
        <td class="text-end text-nowrap"><?php if ($w): ?><a class="btn btn-sm btn-ghost" target="_blank" rel="noopener" href="<?= e($w) ?>" title="WhatsApp"><i class="bi bi-whatsapp text-success"></i></a> <?php endif; ?><a class="btn btn-sm btn-soft" href="<?= e(url('crm/ficha', ['id' => $c['id']])) ?>">Ver ficha</a></td>
      </tr>
    <?php endforeach; ?>
    </tbody></table></div>
  <div class="empty d-none" id="crmEmpty"><i class="bi bi-people"></i>No hay clientes con ese filtro</div>
</div>
<?php include __DIR__ . '/_modal.php'; ?>
<script>window.CRM = { filtro: <?= json_encode($filtro) ?> };</script>
