<div class="page-head">
  <div><h2>Configuración de facturación</h2><p>Conecte el sistema con su OSE/PSE (Nubefact) para emitir boletas y facturas electrónicas.</p></div>
  <a href="<?= e(url('facturacion/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>
<div class="row g-3">
  <div class="col-lg-7"><div class="card-soft p-4">
    <form method="post" action="<?= e(url('facturacion/guardar')) ?>">
      <?= csrf_field() ?>
      <div class="form-check form-switch mb-3"><input class="form-check-input" type="checkbox" name="fe_activo" id="fa" <?= Sunat::activo() ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="fa">Activar facturación electrónica (boletas y facturas)</label></div>
      <div class="form-check form-switch mb-4"><input class="form-check-input" type="checkbox" name="fe_auto" id="fu" <?= cfg('fe_auto', '1') === '1' ? 'checked' : '' ?>><label class="form-check-label" for="fu">Enviar automáticamente al cobrar (si falla, queda pendiente)</label></div>
      <label class="form-label fw-semibold">Modo</label>
      <div class="row g-2 mb-3">
        <div class="col-md-6"><label class="border rounded-4 p-3 d-block h-100"><input class="form-check-input me-2" type="radio" name="fe_modo" value="simulado" <?= cfg('fe_modo', 'simulado') === 'simulado' ? 'checked' : '' ?>><b>Simulado</b><div class="small text-muted">Para probar el flujo sin enviar nada a SUNAT. Los comprobantes no tienen validez tributaria.</div></label></div>
        <div class="col-md-6"><label class="border rounded-4 p-3 d-block h-100"><input class="form-check-input me-2" type="radio" name="fe_modo" value="nubefact" <?= cfg('fe_modo') === 'nubefact' ? 'checked' : '' ?>><b>Nubefact</b><div class="small text-muted">Envía a SUNAT mediante la API JSON de Nubefact (demo o producción).</div></label></div>
      </div>
      <div class="mb-3"><label class="form-label fw-semibold">Ruta de la API</label><input class="form-control" name="fe_url" value="<?= e(cfg('fe_url', '')) ?>" placeholder="https://api.nubefact.com/api/v1/xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx"></div>
      <div class="mb-4"><label class="form-label fw-semibold">Token</label><input class="form-control" type="password" name="fe_token" autocomplete="off" placeholder="<?= $tokenSet ? '•••••••• (guardado; deje vacío para conservarlo)' : 'Pegue aquí el token de Nubefact' ?>"></div>
      <button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar configuración</button>
    </form>
  </div></div>
  <div class="col-lg-5"><div class="card-soft p-4">
    <h6 class="fw-bold mb-3"><i class="bi bi-info-circle me-1 text-brand"></i>Antes de emitir en real</h6>
    <ol class="small text-muted ps-3 mb-0">
      <li class="mb-2">Contrate un OSE/PSE (por ejemplo Nubefact) y obtenga la <b>ruta</b> y el <b>token</b> de su cuenta (primero en su entorno demo).</li>
      <li class="mb-2">Registre en el proveedor las mismas series que usa este sistema: boleta <b><?= e(cfg('serie_boleta')) ?></b> y factura <b><?= e(cfg('serie_factura')) ?></b> (se pueden cambiar en Configuración).</li>
      <li class="mb-2">Verifique en <b>Configuración</b> el RUC, razón social y dirección de la empresa, y el IGV.</li>
      <li class="mb-2">Para facturas, el cliente debe tener RUC registrado. Las boletas sin cliente salen a nombre de «CLIENTES VARIOS».</li>
      <li>Haga primero algunas ventas en modo demo y revise que SUNAT las acepte antes de pasar a producción.</li>
    </ol>
  </div></div>
</div>
