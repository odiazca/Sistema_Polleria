<?php
$tipoBadge = ['manual' => ['info', 'Manual'], 'auto' => ['violet', 'Automática'], 'pre-restauracion' => ['warn', 'Previa a restauración'], 'pre-reseteo' => ['bad', 'Previa a reseteo']];
$ultima = $files[0] ?? null;
?>
<div class="bk-hero mb-4">
  <div class="big"><i class="bi bi-database-fill-check"></i></div>
  <div class="flex-grow-1">
    <h3 class="fw-800 mb-1" style="letter-spacing:-.02em">Copias de seguridad</h3>
    <p class="text-muted mb-3">Proteja la información de su negocio: cree respaldos, restaure datos anteriores o inicie el sistema para una nueva empresa.</p>
    <div class="d-flex flex-wrap gap-2">
      <span class="chip"><i class="bi bi-clock-history text-primary"></i><?= $ultima ? 'Última copia: ' . e(date('d/m/Y H:i', $ultima['time'])) : 'Aún sin copias' ?></span>
      <span class="chip"><i class="bi bi-files text-primary"></i><?= count($files) ?> copias · <?= e(human_size((int)$total)) ?></span>
      <span class="chip"><i class="bi bi-hdd-stack text-primary"></i>Base de datos: <?= e(human_size($dbBytes)) ?> · <?= $dbTablas ?> tablas</span>
    </div>
  </div>
</div>

<div class="row g-3 mb-4">
  <div class="col-lg-4">
    <div class="bk-act">
      <div class="ai t-ok"><i class="bi bi-cloud-arrow-down-fill"></i></div>
      <h5>Crear copia de seguridad</h5>
      <p class="text-muted small flex-grow-1">Genera un archivo con toda la base de datos (ventas, productos, clientes, usuarios y configuración). Puede incluir también las imágenes subidas.</p>
      <form method="post" action="<?= e(url('backup/crear')) ?>">
        <?= csrf_field() ?>
        <div class="form-check form-switch mb-3">
          <input class="form-check-input" type="checkbox" name="con_imagenes" id="conImg" <?= $zipOk ? '' : 'disabled' ?>>
          <label class="form-check-label small" for="conImg">Incluir imágenes y logo (ZIP) <?= $zipOk ? '' : '· ZIP no disponible' ?></label>
        </div>
        <button class="btn btn-ok w-100" type="submit"><i class="bi bi-download me-1"></i>Crear copia ahora</button>
      </form>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="bk-act">
      <div class="ai t-info"><i class="bi bi-arrow-counterclockwise"></i></div>
      <h5>Restaurar copia</h5>
      <p class="text-muted small flex-grow-1">Recupere el sistema a un estado anterior desde una copia guardada o subiendo un archivo <code>.sql</code> / <code>.zip</code>. Antes se crea una copia de seguridad automática.</p>
      <button class="btn btn-soft w-100" data-bs-toggle="modal" data-bs-target="#mdRestore" data-file=""><i class="bi bi-upload me-1"></i>Restaurar desde archivo</button>
    </div>
  </div>
  <div class="col-lg-4">
    <div class="bk-act danger">
      <div class="ai t-bad"><i class="bi bi-trash3-fill"></i></div>
      <h5>Resetear sistema</h5>
      <p class="text-muted small flex-grow-1">Borra ventas, clientes, compras y movimientos para empezar con una <b>empresa nueva</b>. Esta acción no se puede deshacer (se guarda una copia previa).</p>
      <button class="btn btn-danger-soft w-100" data-bs-toggle="modal" data-bs-target="#mdReset"><i class="bi bi-exclamation-octagon me-1"></i>Resetear para nueva empresa</button>
    </div>
  </div>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card-soft">
      <div class="card-h"><div><h5>Copias disponibles</h5><small>Se conservan las últimas <?= (int)cfg('backup_retencion', 10) ?> (configurable en Configuración → Sistema)</small></div></div>
      <div class="card-b pt-3">
        <div class="table-wrap"><table class="table table-modern">
          <thead><tr><th>Archivo</th><th>Tipo</th><th>Fecha</th><th>Tamaño</th><th class="text-end">Acciones</th></tr></thead>
          <tbody>
          <?php foreach ($files as $f): [$bc, $bl] = $tipoBadge[$f['tipo']] ?? ['gray', $f['tipo']]; ?>
            <tr>
              <td><div class="d-flex align-items-center gap-2"><span class="thumb" style="width:38px;height:38px"><i class="bi <?= $f['zip'] ? 'bi-file-earmark-zip' : 'bi-filetype-sql' ?>"></i></span><span class="fw-semibold small text-break"><?= e($f['name']) ?></span></div></td>
              <td><span class="bsoft <?= $bc ?>"><?= e($bl) ?></span></td>
              <td class="text-muted small"><?= e(date('d/m/Y H:i', $f['time'])) ?></td>
              <td class="small"><?= e(human_size($f['size'])) ?></td>
              <td class="text-end text-nowrap">
                <a class="btn btn-ghost btn-sm" href="<?= e(url('backup/descargar', ['f' => $f['name']])) ?>" title="Descargar"><i class="bi bi-download"></i></a>
                <button class="btn btn-soft btn-sm" data-bs-toggle="modal" data-bs-target="#mdRestore" data-file="<?= e($f['name']) ?>" title="Restaurar"><i class="bi bi-arrow-counterclockwise"></i></button>
                <form method="post" action="<?= e(url('backup/eliminar')) ?>" class="d-inline" data-confirm="Se eliminará el archivo «<?= e($f['name']) ?>»." data-danger="1" data-btn="Eliminar">
                  <?= csrf_field() ?><input type="hidden" name="f" value="<?= e($f['name']) ?>">
                  <button class="btn btn-danger-soft btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (!$files): ?><tr><td colspan="5"><div class="empty"><i class="bi bi-inbox"></i>Todavía no hay copias. Cree la primera con el botón verde.</div></td></tr><?php endif; ?>
          </tbody>
        </table></div>
      </div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card-soft mb-3">
      <div class="card-h"><div><h5>Contenido protegido</h5><small>Registros actuales en la base de datos</small></div></div>
      <div class="card-b pt-2">
        <?php foreach ($contenido as $c): ?>
          <div class="list-row"><span class="rank"><i class="bi <?= e($c['icon']) ?>"></i></span><div class="flex-grow-1 fw-semibold small"><?= e($c['label']) ?></div><b><?= number_format($c['n'], 0, '.', ' ') ?></b></div>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="card-soft p-3" style="background:#FFFBEB;border-color:#FDE68A">
      <div class="d-flex gap-2"><i class="bi bi-lightbulb-fill text-warning fs-5"></i><div class="small text-muted"><b class="text-dark">Recomendación:</b> cree una copia al cerrar el día y guarde una copia descargada fuera de esta computadora (USB o nube). Para copias automáticas programe <code>php backup_cron.php</code> en el Programador de tareas de Windows.</div></div>
    </div>
  </div>
</div>

<!-- Modal restaurar -->
<div class="modal fade" id="mdRestore" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" enctype="multipart/form-data" action="<?= e(url('backup/restaurar')) ?>">
  <?= csrf_field() ?><input type="hidden" name="f" id="rsFile">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-arrow-counterclockwise text-primary me-2"></i>Restaurar copia de seguridad</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="alert border-0 small" style="background:#FFFBEB;color:#92400E;border-radius:14px"><i class="bi bi-exclamation-triangle-fill me-1"></i>Los datos actuales serán <b>reemplazados</b> por los de la copia. Se creará un respaldo previo automáticamente y deberá iniciar sesión otra vez.</div>
    <div id="rsSelected" class="mb-3 d-none"><label class="form-label">Copia seleccionada</label><div class="p-3 rounded-3 bg-light fw-semibold small" id="rsName"></div></div>
    <div id="rsUpload" class="mb-3">
      <label class="form-label">Subir archivo de respaldo (.sql / .zip)</label>
      <label class="drop w-100" id="dropZone"><i class="bi bi-cloud-arrow-up"></i><span id="dropTxt">Haga clic o arrastre el archivo aquí</span><input type="file" name="archivo" id="rsInput" accept=".sql,.zip" hidden></label>
    </div>
    <div class="row g-3">
      <div class="col-6"><label class="form-label">Su contraseña</label><input type="password" class="form-control" name="password" required autocomplete="current-password"></div>
      <div class="col-6"><label class="form-label">Escriba <b>RESTAURAR</b></label><input class="form-control" name="confirmacion" required autocomplete="off" placeholder="RESTAURAR"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-arrow-counterclockwise me-1"></i>Restaurar ahora</button></div>
</form></div></div>

<!-- Modal reset -->
<div class="modal fade" id="mdReset" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="post" action="<?= e(url('backup/resetear')) ?>">
  <?= csrf_field() ?>
  <div class="modal-header" style="background:#FEF2F2"><h5 class="modal-title text-danger"><i class="bi bi-exclamation-octagon-fill me-2"></i>Resetear sistema para nueva empresa</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <p class="small text-muted">Se eliminarán <b>pedidos, ventas, cajas, compras, gastos, clientes, proveedores, repartidores y movimientos</b>. Se guarda automáticamente una copia completa (con imágenes) antes de borrar.</p>
    <label class="form-label">Catálogo de productos</label>
    <div class="d-grid gap-2 mb-3">
      <label class="p-3 border rounded-3 d-flex gap-2 cursor"><input class="form-check-input mt-1" type="radio" name="catalogo" value="base" checked><span><b class="small">Catálogo base de pollería</b><br><span class="text-muted small">Productos, categorías, mesas e insumos de ejemplo</span></span></label>
      <label class="p-3 border rounded-3 d-flex gap-2 cursor"><input class="form-check-input mt-1" type="radio" name="catalogo" value="vacio"><span><b class="small">Empezar vacío</b><br><span class="text-muted small">Sin productos; usted registra todo desde cero</span></span></label>
      <label class="p-3 border rounded-3 d-flex gap-2 cursor"><input class="form-check-input mt-1" type="radio" name="catalogo" value="mantener"><span><b class="small">Mantener el catálogo actual</b><br><span class="text-muted small">Conserva productos, categorías, mesas e insumos</span></span></label>
    </div>
    <div class="form-check form-switch mb-2"><input class="form-check-input" type="checkbox" name="mantener_usuarios" id="mkU"><label class="form-check-label small" for="mkU">Mantener los usuarios actuales <span class="text-muted">(si no, solo queda su usuario)</span></label></div>
    <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="mantener_config" id="mkC"><label class="form-check-label small" for="mkC">Mantener datos y configuración de la empresa</label></div>
    <div class="row g-3">
      <div class="col-6"><label class="form-label">Su contraseña</label><input type="password" class="form-control" name="password" required autocomplete="current-password"></div>
      <div class="col-6"><label class="form-label">Escriba <b class="text-danger">RESETEAR</b></label><input class="form-control" name="confirmacion" required autocomplete="off" placeholder="RESETEAR"></div>
    </div>
  </div>
  <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-danger"><i class="bi bi-trash3 me-1"></i>Sí, resetear el sistema</button></div>
</form></div></div>

<script>
(function(){
  var md = document.getElementById('mdRestore');
  md.addEventListener('show.bs.modal', function(ev){
    var f = ev.relatedTarget ? ev.relatedTarget.getAttribute('data-file') : '';
    document.getElementById('rsFile').value = f || '';
    document.getElementById('rsSelected').classList.toggle('d-none', !f);
    document.getElementById('rsUpload').classList.toggle('d-none', !!f);
    document.getElementById('rsName').textContent = f || '';
  });
  var dz = document.getElementById('dropZone'), inp = document.getElementById('rsInput'), tx = document.getElementById('dropTxt');
  inp.addEventListener('change', function(){ if(inp.files[0]) tx.textContent = inp.files[0].name + ' (' + Math.round(inp.files[0].size/1024) + ' KB)'; });
  ['dragover','dragenter'].forEach(function(e){ dz.addEventListener(e, function(ev){ ev.preventDefault(); dz.classList.add('over'); }); });
  ['dragleave','drop'].forEach(function(e){ dz.addEventListener(e, function(){ dz.classList.remove('over'); }); });
  dz.addEventListener('drop', function(ev){ ev.preventDefault(); if(ev.dataTransfer.files[0]){ inp.files = ev.dataTransfer.files; inp.dispatchEvent(new Event('change')); } });
})();
</script>
