<?php /** Detalle de campaña: lista de envío por WhatsApp */
$S = Crm::SEG;
$tot = count($env); $ok = count(array_filter($env, fn($e) => $e['enviado'])); $pct = $tot ? round($ok / $tot * 100) : 0;
?>
<div class="page-head">
  <div><h2><?= e($c['nombre']) ?></h2><p>Pulse <b>Enviar</b> en cada cliente: se abre WhatsApp con el mensaje ya personalizado y la campaña lleva la cuenta de los enviados.</p></div>
  <div class="d-flex gap-2"><a href="<?= e(url('campanas/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a><button class="btn btn-ghost text-danger" id="cmpDel" data-id="<?= (int)$c['id'] ?>"><i class="bi bi-trash me-1"></i>Eliminar</button></div>
</div>
<?php $tab = 'campanas'; include __DIR__ . '/../crm/_nav.php'; ?>
<div class="row g-3">
  <div class="col-xl-4">
    <div class="card-soft mb-3"><div class="card-b">
      <div class="d-flex justify-content-between align-items-end mb-1"><b class="fs-3" id="cmpOk"><?= $ok ?></b><span class="text-muted">de <?= $tot ?> enviados</span></div>
      <div class="progress mb-3" style="height:10px"><div class="progress-bar bg-success" id="cmpBar" style="width:<?= $pct ?>%"></div></div>
      <div class="small text-muted mb-2">Mensaje (<?= e(fdate($c['created_at'], true)) ?>)</div>
      <div class="crm-wa"><div class="crm-wa-h"><i class="bi bi-whatsapp"></i> Plantilla</div><div class="crm-wa-b"><?= nl2br(e($c['mensaje'])) ?></div></div>
    </div></div>
    <div class="card-soft rv-tip"><div class="card-b small"><i class="bi bi-lightbulb-fill text-warning me-1"></i><b>Consejo:</b> envíe a pocos clientes por vez y personalice cuando pueda. Respete a quienes no desean promociones: el sistema ya los excluye.</div></div>
  </div>
  <div class="col-xl-8">
    <div class="card-soft"><div class="table-wrap"><table class="table table-modern align-middle" id="cmpTbl">
      <thead><tr><th>Cliente</th><th>Segmento</th><th>Mensaje</th><th class="text-end">Envío</th></tr></thead><tbody>
      <?php foreach ($env as $e): $x = $e['c']; if (!$x) continue; $sg = $S[$x['seg']]; ?>
        <tr data-id="<?= (int)$e['id'] ?>" class="<?= $e['enviado'] ? 'crm-sent' : '' ?>">
          <td><a href="<?= e(url('crm/ficha', ['id' => $x['id']])) ?>" class="d-flex align-items-center gap-2 text-decoration-none"><span class="crm-av" style="background:<?= $sg[2] ?>"><?= e(Crm::iniciales($x['nombre'])) ?></span><span><b class="d-block text-dark"><?= e($x['nombre']) ?></b><small class="text-muted"><?= e($x['telefono']) ?></small></span></a></td>
          <td><span class="bsoft <?= $sg[1] ?>"><?= e($sg[0]) ?></span></td>
          <td class="small text-muted" style="max-width:280px"><?= e(mb_strimwidth(Crm::plantilla($c['mensaje'], $x), 0, 90, '…')) ?></td>
          <td class="text-end text-nowrap">
            <a class="btn btn-sm btn-success" target="_blank" rel="noopener" href="<?= e($e['link']) ?>" data-env="<?= (int)$e['id'] ?>"><i class="bi bi-whatsapp me-1"></i><?= $e['enviado'] ? 'Reenviar' : 'Enviar' ?></a>
            <button class="btn btn-sm btn-ghost" data-env-tg="<?= (int)$e['id'] ?>" data-v="<?= $e['enviado'] ? 0 : 1 ?>" title="<?= $e['enviado'] ? 'Quitar marca' : 'Marcar como enviado' ?>"><i class="bi <?= $e['enviado'] ? 'bi-check-circle-fill text-success' : 'bi-circle' ?>"></i></button></td></tr>
      <?php endforeach; ?>
      </tbody></table></div></div>
  </div>
</div>
