<?php /** Campañas y programa de fidelidad */
$S = Crm::SEG; $adm = Auth::isAdmin();
$plant = [
    'Te extrañamos' => 'Hola {nombre}, te extrañamos en {empresa} 🍗. Vuelve esta semana y te regalamos una gaseosa con tu pedido. ¡Te esperamos!',
    'Promo del día' => '¡Hola {nombre}! Hoy en {empresa} tenemos promo en pollo a la brasa 🍗🔥. Pide ahora y disfruta con tu familia.',
    'Cumpleaños' => '¡Feliz cumpleaños {nombre}! 🎂 En {empresa} queremos celebrarlo contigo. Muéstranos este mensaje y recibe un postre de regalo.',
    'Puntos' => 'Hola {nombre}, tienes {puntos} puntos acumulados en {empresa} ⭐. Canjéalos por premios en tu próxima visita.',
    'Gracias' => 'Gracias por elegirnos, {nombre} 💛. En {empresa} nos encanta atenderte. ¡Hasta pronto!',
];
?>
<div class="page-head">
  <div><h2>Campañas y fidelidad</h2><p>Envíe mensajes de WhatsApp a segmentos de clientes y configure su programa de puntos.</p></div>
</div>
<?php include __DIR__ . '/../crm/_nav.php'; ?>

<div class="row g-3 mb-3">
  <div class="col-xl-7">
    <div class="card-soft h-100"><div class="card-h"><div><h5><i class="bi bi-megaphone-fill text-brand me-1"></i>Nueva campaña</h5><small>Elija a quién escribirle y qué decirle</small></div></div>
      <form class="card-b pt-1" id="cmpForm">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nombre de la campaña</label><input class="form-control" name="nombre" maxlength="120" required placeholder="Ej: Recuperación octubre"></div>
          <div class="col-md-6"><label class="form-label">Destinatarios</label>
            <select class="form-select" name="segmento" id="cmpSeg">
              <?php foreach ($extra as $k => $l): ?><option value="<?= $k ?>" <?= $seg === $k ? 'selected' : '' ?>><?= e($l) ?> (<?= (int)$cnt[$k] ?>)</option><?php endforeach; ?>
              <?php foreach ($S as $k => $s): if ($k === 'prospecto') continue; ?><option value="<?= $k ?>" <?= $seg === $k ? 'selected' : '' ?>><?= e($s[0]) ?> (<?= (int)$cnt[$k] ?>)</option><?php endforeach; ?>
              <option value="prospecto">Sin compras (<?= (int)$cnt['prospecto'] ?>)</option>
            </select><div class="form-text">Solo clientes con teléfono que aceptan promociones.</div></div>
          <div class="col-12"><label class="form-label d-flex justify-content-between"><span>Mensaje</span><small class="text-muted fw-normal">Variables: {nombre} {puntos} {empresa}</small></label>
            <div class="crm-plant mb-2"><?php foreach ($plant as $n => $t): ?><button type="button" data-t="<?= e($t) ?>"><?= e($n) ?></button><?php endforeach; ?></div>
            <textarea class="form-control" name="mensaje" id="cmpMsg" rows="4" maxlength="700" required placeholder="Escriba el mensaje o elija una plantilla…"></textarea></div>
          <div class="col-12"><div class="crm-wa"><div class="crm-wa-h"><i class="bi bi-whatsapp"></i> Vista previa</div><div class="crm-wa-b" id="cmpPrev">Su mensaje aparecerá aquí…</div></div></div>
          <div class="col-12 text-danger small" id="cmpErr"></div>
          <div class="col-12 text-end"><button class="btn btn-brand"><i class="bi bi-send-check me-1"></i>Crear campaña y ver destinatarios</button></div>
        </div>
      </form></div>
  </div>
  <div class="col-xl-5">
    <div class="card-soft h-100"><div class="card-h"><div><h5>Mis campañas</h5><small>Historial y avance</small></div></div>
      <div class="card-b pt-1">
        <?php foreach ($camp as $c): $pct = $c['total'] ? round($c['enviados'] / $c['total'] * 100) : 0; $lb = $extra[$c['segmento']] ?? ($S[$c['segmento']][0] ?? $c['segmento']); ?>
          <div class="crm-camp"><div class="d-flex justify-content-between align-items-start gap-2"><div class="min-w-0"><a href="<?= e(url('campanas/ver', ['id' => $c['id']])) ?>" class="fw-bold text-dark d-block text-truncate"><?= e($c['nombre']) ?></a><small class="text-muted"><?= e($lb) ?> · <?= e(fdate($c['created_at'])) ?></small></div>
            <span class="bsoft <?= $pct >= 100 ? 'ok' : 'warn' ?>"><?= (int)$c['enviados'] ?>/<?= (int)$c['total'] ?></span></div>
            <div class="progress mt-2" style="height:6px"><div class="progress-bar bg-success" style="width:<?= $pct ?>%"></div></div></div>
        <?php endforeach; ?>
        <?php if (!$camp): ?><div class="empty"><i class="bi bi-megaphone"></i>Aún no ha creado campañas</div><?php endif; ?>
      </div></div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-6">
    <div class="card-soft h-100"><div class="card-h"><div><h5><i class="bi bi-gift-fill text-brand me-1"></i>Recompensas</h5><small>Premios que el cliente canjea con sus puntos</small></div>
      <?php if ($adm): ?><button class="btn btn-sm btn-brand" id="prNew"><i class="bi bi-plus-lg me-1"></i>Agregar</button><?php endif; ?></div>
      <div class="card-b pt-1">
        <?php foreach ($premios as $p): ?>
          <div class="crm-prem<?= $p['estado'] ? '' : ' off' ?>"><span class="flex-grow-1"><b><?= e($p['nombre']) ?></b><small class="d-block text-muted"><?= (int)$p['puntos'] ?> puntos<?= $p['estado'] ? '' : ' · inactiva' ?></small></span>
            <?php if ($adm): ?><button class="btn btn-sm btn-ghost" data-pr="<?= e(json_encode($p, JSON_UNESCAPED_UNICODE)) ?>"><i class="bi bi-pencil"></i></button><button class="btn btn-sm btn-ghost text-danger" data-pr-del="<?= (int)$p['id'] ?>"><i class="bi bi-trash"></i></button><?php endif; ?></div>
        <?php endforeach; ?>
        <?php if (!$premios): ?><div class="empty"><i class="bi bi-gift"></i>Sin recompensas</div><?php endif; ?>
      </div></div>
  </div>
  <div class="col-xl-6">
    <div class="card-soft h-100"><div class="card-h"><div><h5><i class="bi bi-sliders2 text-brand me-1"></i>Reglas del CRM</h5><small>Cómo se acumulan puntos y se clasifican los clientes</small></div></div>
      <form class="card-b pt-1" id="rgForm"><fieldset <?= $adm ? '' : 'disabled' ?>><div class="row g-3">
        <div class="col-6"><label class="form-label">Soles por cada punto</label><input type="number" step="0.5" min="1" class="form-control" name="soles" value="<?= e($r['soles_por_punto']) ?>"></div>
        <div class="col-6"><label class="form-label">Gasto para ser VIP (<?= e(cfg('moneda_simbolo', 'S/')) ?>)</label><input type="number" min="1" class="form-control" name="vip" value="<?= e($r['vip_gasto']) ?>"></div>
        <div class="col-6"><label class="form-label">Puntos nivel Plata</label><input type="number" min="1" class="form-control" name="plata" value="<?= (int)$r['plata'] ?>"></div>
        <div class="col-6"><label class="form-label">Puntos nivel Oro</label><input type="number" min="1" class="form-control" name="oro" value="<?= (int)$r['oro'] ?>"></div>
        <div class="col-4"><label class="form-label">Visitas «Frecuente»</label><input type="number" min="2" class="form-control" name="frecuente" value="<?= (int)$r['frecuente'] ?>"></div>
        <div class="col-4"><label class="form-label">Días «En riesgo»</label><input type="number" min="7" class="form-control" name="riesgo" value="<?= (int)$r['riesgo'] ?>"></div>
        <div class="col-4"><label class="form-label">Días «Perdido»</label><input type="number" min="14" class="form-control" name="perdido" value="<?= (int)$r['perdido'] ?>"></div>
        <?php if ($adm): ?><div class="col-12 text-end"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar reglas</button></div>
        <?php else: ?><div class="col-12 small text-muted">Solo el administrador puede cambiar estas reglas.</div><?php endif; ?>
      </div></fieldset></form></div>
  </div>
</div>

<div class="modal fade" id="prModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" id="prForm">
  <div class="modal-header"><h5 class="modal-title fw-bold"><i class="bi bi-gift me-2 text-brand"></i>Recompensa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body"><input type="hidden" name="id"><div class="row g-3">
    <div class="col-12"><label class="form-label">Nombre</label><input class="form-control" name="nombre" maxlength="100" required placeholder="Ej: 1/4 de pollo gratis"></div>
    <div class="col-6"><label class="form-label">Puntos requeridos</label><input type="number" min="1" class="form-control" name="puntos" required></div>
    <div class="col-6 d-flex align-items-end"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activa" id="prAct" checked><label class="form-check-label" for="prAct">Activa</label></div></div></div></div>
  <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand">Guardar</button></div></form></div></div>
<script>window.CRM = { empresa: <?= json_encode(cfg('empresa_nombre', APP_NAME), JSON_UNESCAPED_UNICODE) ?> };</script>
