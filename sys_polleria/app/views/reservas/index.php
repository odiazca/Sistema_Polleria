<?php
/** Reservaciones: vista del día, semana, mesas y formulario */
$meses = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
$dias = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
$ts = strtotime($fecha);
$tit = ucfirst($dias[(int)date('w', $ts)]) . ' ' . date('j', $ts) . ' de ' . $meses[(int)date('n', $ts) - 1] . ' de ' . date('Y', $ts);
$boot = ['fecha' => $fecha, 'hoy' => date('Y-m-d'), 'rc' => $rc, 'franjas' => $franjas, 'mesas' => $mesas, 'semana' => $semana, 'ocasiones' => Reservas::OCASIONES, 'estados' => Reservas::ESTADOS, 'canPos' => Auth::can('pos'), 'admin' => Auth::isAdmin(), 'empresa' => cfg('empresa_nombre', APP_NAME), 'publica' => $publica, 'pend' => $pendTotal];
?>
<div class="page-head">
  <div><h2>Reservaciones</h2><p>Organice las reservas del local, asigne mesas y reciba reservas en línea.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <button class="btn btn-ghost" id="rvLink"><i class="bi bi-link-45deg me-1"></i>Enlace para clientes</button>
    <?php if (Auth::isAdmin()): ?><button class="btn btn-ghost" id="rvCfg"><i class="bi bi-gear me-1"></i>Configuración</button><?php endif; ?>
    <button class="btn btn-brand" id="rvNew"><i class="bi bi-plus-lg me-1"></i>Nueva reserva</button>
  </div>
</div>

<div class="rv-nav card-soft mb-3">
  <div class="rv-date">
    <button class="icon-btn" id="rvPrev" aria-label="Día anterior"><i class="bi bi-chevron-left"></i></button>
    <div class="rv-title"><b id="rvTit"><?= e($tit) ?></b><small id="rvSub"></small></div>
    <button class="icon-btn" id="rvNext" aria-label="Día siguiente"><i class="bi bi-chevron-right"></i></button>
    <input type="date" id="rvDate" class="form-control form-control-sm" value="<?= e($fecha) ?>">
    <button class="btn btn-sm btn-ghost" id="rvHoy">Hoy</button>
  </div>
  <div class="rv-week" id="rvWeek"></div>
</div>

<div class="row g-3 mb-3" id="rvKpis">
  <div class="col-6 col-xl-3"><?= kpi_g('orange', 'Reservas del día', '<span id="kRes">0</span>', 'bi-calendar-heart-fill', '', 'sin contar canceladas') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('blue', 'Comensales esperados', '<span id="kPer">0</span>', 'bi-people-fill', '', 'personas') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('rose', 'Por confirmar', '<span id="kPen">0</span>', 'bi-hourglass-split', '', 'llámelos o escríbales') ?></div>
  <div class="col-6 col-xl-3"><?= kpi_g('green', 'Confirmadas', '<span id="kCon">0</span>', 'bi-patch-check-fill', '', 'listas para recibir') ?></div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card-soft">
      <div class="card-h flex-wrap gap-2">
        <div><h5>Agenda del día</h5><small>Ordenada por hora de llegada</small></div>
        <div class="d-flex gap-2 flex-wrap align-items-center">
          <div class="rv-tabs" id="rvTabs">
            <button data-f="todas" class="on">Todas</button><button data-f="pendiente">Por confirmar</button><button data-f="confirmada">Confirmadas</button><button data-f="sentada">En mesa</button><button data-f="cerradas">Cerradas</button>
          </div>
          <div class="search-box"><i class="bi bi-search"></i><input type="search" id="rvQ" class="form-control form-control-sm" placeholder="Buscar nombre o teléfono…"></div>
        </div>
      </div>
      <div class="card-b pt-2"><div id="rvList"></div></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft mb-3">
      <div class="card-h"><div><h5>Mesas y horarios</h5><small>Quién ocupa cada mesa hoy</small></div></div>
      <div class="card-b pt-1" id="rvMesas"></div>
    </div>
    <div class="card-soft rv-tip">
      <div class="d-flex gap-3"><i class="bi bi-lightbulb-fill fs-4" style="color:#F59E0B"></i>
        <div class="small"><b>Consejo:</b> asigne mesa al confirmar. Al pulsar <b>Llegó</b>, el sistema abre el punto de venta en esa mesa para tomar el pedido. Las reservas con más de 20 minutos de retraso se resaltan.</div></div>
    </div>
  </div>
</div>

<!-- Modal reserva -->
<div class="modal fade" id="rvModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
  <form class="modal-content" id="rvForm" novalidate>
    <div class="modal-header"><h5 class="modal-title fw-bold" id="rvMt"><i class="bi bi-calendar-plus me-2 text-brand"></i>Nueva reserva</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><input type="hidden" name="id">
      <div class="row g-3">
        <div class="col-md-7"><label class="form-label">Nombre del cliente *</label><input class="form-control" name="nombre" maxlength="120" required autocomplete="off" placeholder="Ej. María Pérez"></div>
        <div class="col-md-5"><label class="form-label">Teléfono / WhatsApp</label><input class="form-control" name="telefono" maxlength="30" inputmode="tel" placeholder="987 654 321"></div>
        <div class="col-md-5"><label class="form-label">Fecha *</label><input type="date" class="form-control" name="fecha" required></div>
        <div class="col-md-4"><label class="form-label">Hora *</label><select class="form-select" name="hora" required></select></div>
        <div class="col-md-3"><label class="form-label">Personas *</label><input type="number" class="form-control" name="personas" min="1" max="<?= (int)$rc['max'] ?>" value="2" required></div>
        <div class="col-12">
          <label class="form-label d-flex justify-content-between"><span>Mesa</span><small class="text-muted fw-normal" id="rvMesaHint"></small></label>
          <div class="rv-mesas" id="rvMesaSel"></div><input type="hidden" name="mesa_id" value="">
        </div>
        <div class="col-md-6"><label class="form-label">Ocasión</label><select class="form-select" name="ocasion"><option value="">— Ninguna —</option><?php foreach (Reservas::OCASIONES as $o): ?><option><?= e($o) ?></option><?php endforeach; ?></select></div>
        <div class="col-md-6"><label class="form-label">Correo (opcional)</label><input type="email" class="form-control" name="email" maxlength="120"></div>
        <div class="col-md-6"><label class="form-label">Canal</label><select class="form-select" name="origen"><option value="telefono">Teléfono</option><option value="whatsapp">WhatsApp</option><option value="local">En el local</option><option value="web">Web</option></select></div>
        <div class="col-md-6"><label class="form-label">Estado</label><select class="form-select" name="estado"><?php foreach (Reservas::ESTADOS as $k => $v): ?><option value="<?= e($k) ?>"><?= e($v[1]) ?></option><?php endforeach; ?></select></div>
        <div class="col-12"><label class="form-label">Notas</label><textarea class="form-control" name="notas" rows="2" maxlength="300" placeholder="Silla para bebé, torta de cumpleaños, alergias…"></textarea></div>
      </div>
      <div class="text-danger small mt-2" id="rvErr"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar reserva</button></div>
  </form>
</div></div>

<!-- Modal configuración -->
<?php if (Auth::isAdmin()): ?>
<div class="modal fade" id="rvCfgModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
  <form class="modal-content" id="rvCfgForm">
    <div class="modal-header"><h5 class="modal-title fw-bold"><i class="bi bi-gear me-2 text-brand"></i>Configurar reservaciones</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><div class="row g-3">
      <div class="col-6"><label class="form-label">Hora de apertura</label><input type="time" class="form-control" name="apertura" value="<?= e($rc['apertura']) ?>" required></div>
      <div class="col-6"><label class="form-label">Última reserva</label><input type="time" class="form-control" name="cierre" value="<?= e($rc['cierre']) ?>" required></div>
      <div class="col-6"><label class="form-label">Duración de la mesa (min)</label><input type="number" class="form-control" name="duracion" min="30" max="360" step="15" value="<?= (int)$rc['duracion'] ?>"></div>
      <div class="col-6"><label class="form-label">Intervalo de horarios</label><select class="form-select" name="intervalo"><?php foreach ([15, 30, 60] as $i): ?><option value="<?= $i ?>" <?= $rc['intervalo'] === $i ? 'selected' : '' ?>><?= $i ?> minutos</option><?php endforeach; ?></select></div>
      <div class="col-6"><label class="form-label">Máx. personas por reserva</label><input type="number" class="form-control" name="max" min="1" max="200" value="<?= (int)$rc['max'] ?>"></div>
      <div class="col-6"><label class="form-label">Reservar hasta con (días)</label><input type="number" class="form-control" name="dias" min="1" max="365" value="<?= (int)$rc['dias'] ?>"></div>
      <div class="col-12"><label class="form-label">Anticipación mínima en línea (horas)</label><input type="number" class="form-control" name="horas_min" min="0" max="72" value="<?= (int)$rc['horas_min'] ?>"></div>
      <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="web" id="cfgWeb" <?= $rc['web'] ? 'checked' : '' ?>><label class="form-check-label" for="cfgWeb">Permitir que los clientes reserven en línea</label></div>
        <div class="form-text">Las reservas en línea llegan como «Por confirmar» y aparecen con un aviso en el menú.</div></div>
    </div></div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cerrar</button><button class="btn btn-brand">Guardar</button></div>
  </form>
</div></div>
<?php endif; ?>

<!-- Modal enlace -->
<div class="modal fade" id="rvLinkModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered">
  <div class="modal-content"><div class="modal-header"><h5 class="modal-title fw-bold"><i class="bi bi-link-45deg me-2 text-brand"></i>Reservas en línea</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body">
      <p class="small text-muted">Comparta este enlace por WhatsApp, redes sociales o colóquelo en su web. Sus clientes podrán elegir fecha, hora y número de personas.</p>
      <div class="input-group mb-3"><input class="form-control" id="rvUrl" readonly value="<?= e($publica) ?>"><button class="btn btn-brand" id="rvCopy"><i class="bi bi-clipboard me-1"></i>Copiar</button></div>
      <div class="d-flex gap-2"><a class="btn btn-ghost flex-fill" target="_blank" rel="noopener" href="<?= e($publica) ?>"><i class="bi bi-box-arrow-up-right me-1"></i>Abrir página</a>
        <a class="btn btn-ghost flex-fill" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode('Reserva tu mesa en ' . cfg('empresa_nombre', APP_NAME) . ': ' . $publica) ?>"><i class="bi bi-whatsapp me-1"></i>Compartir</a></div>
      <?php if (!$rc['web']): ?><div class="alert alert-warning small mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>Las reservas en línea están desactivadas. Actívelas en Configuración.</div><?php endif; ?>
    </div></div>
</div></div>

<script>window.RV = <?= json_encode($boot, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
