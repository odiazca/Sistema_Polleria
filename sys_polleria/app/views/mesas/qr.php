<div class="page-head">
  <div><h2>Códigos QR de mesas</h2><p>Imprima cada código y colóquelo en su mesa: el cliente escanea, elige y el mozo confirma.</p></div>
  <div class="d-flex gap-2"><a href="<?= e(url('mesas/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a><button class="btn btn-brand" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button></div>
</div>
<div class="card-soft mb-4 no-print">
  <form method="post" action="<?= e(url('solicitudes/guardarQr')) ?>" class="row g-3 align-items-end">
    <?= csrf_field() ?>
    <div class="col-lg-7"><label class="form-label small fw-semibold">Dirección base del sistema (para los celulares)</label>
      <input class="form-control" name="base_url" value="<?= e($baseCfg) ?>" placeholder="http://192.168.1.10/sys_polleria">
      <div class="form-text">Actual: <code><?= e($base) ?></code>. Use la IP de su PC en la red WiFi (no «localhost») para que los celulares puedan abrir la carta.</div></div>
    <div class="col-lg-3"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="activo" id="act" <?= $activo ? 'checked' : '' ?>><label class="form-check-label" for="act">Pedidos por QR activos</label></div></div>
    <div class="col-lg-2"><button class="btn btn-brand w-100">Guardar</button></div>
  </form>
</div>
<div class="row g-3" id="qrGrid">
<?php foreach ($mesas as $m): $link = $base . '/index.php?r=qr/menu&t=' . $m['qr_token']; ?>
  <div class="col-6 col-md-4 col-xl-3">
    <div class="card-soft text-center qr-card">
      <div class="fw-bold text-brand mb-1"><?= e(cfg('empresa_nombre', APP_NAME)) ?></div>
      <div class="qrbox mx-auto my-2" data-link="<?= e($link) ?>"></div>
      <div class="fw-800 fs-5"><?= e($m['nombre']) ?></div>
      <div class="small text-muted">Escanee y pida desde su celular</div>
    </div>
  </div>
<?php endforeach; ?>
</div>
<style>@media print{.sidebar,.topbar,.footer,.no-print,.page-head .btn{display:none!important}.content{padding:0!important}.main-wrap,.main{margin:0!important}.qr-card{break-inside:avoid;box-shadow:none!important;border:1px dashed #999!important}}</style>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>document.addEventListener('DOMContentLoaded',function(){document.querySelectorAll('.qrbox').forEach(function(el){new QRCode(el,{text:el.dataset.link,width:170,height:170,correctLevel:QRCode.CorrectLevel.M})})});</script>
