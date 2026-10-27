<?php
$est = ['libre' => ['ok', 'Libre', 'bi-cup-hot'], 'ocupada' => ['brand', 'Ocupada', 'bi-people-fill'], 'reservada' => ['info', 'Reservada', 'bi-bookmark-star-fill']];
$tot = ['libre' => 0, 'ocupada' => 0, 'reservada' => 0];
foreach ($zonas as $ms) foreach ($ms as $m) $tot[$m['estado_real']]++;
?>
<div class="page-head">
  <div><h2>Mesas del local</h2><p>Toque una mesa libre para abrir un pedido, o una ocupada para continuar o cobrar.</p></div>
  <div class="d-flex gap-2 flex-wrap">
    <span class="bsoft ok py-2 px-3"><i class="bi bi-circle-fill" style="font-size:.5rem"></i>Libres <?= $tot['libre'] ?></span>
    <span class="bsoft brand py-2 px-3"><i class="bi bi-circle-fill" style="font-size:.5rem"></i>Ocupadas <?= $tot['ocupada'] ?></span>
    <span class="bsoft info py-2 px-3"><i class="bi bi-circle-fill" style="font-size:.5rem"></i>Reservadas <?= $tot['reservada'] ?></span>
    <a href="<?= e(url('solicitudes/qr')) ?>" class="btn btn-ghost"><i class="bi bi-qr-code me-1"></i>Códigos QR</a>
    <a href="<?= e(url('mesas/gestion')) ?>" class="btn btn-ghost"><i class="bi bi-gear me-1"></i>Administrar mesas</a>
  </div>
</div>
<div id="qrSol"><?php $solicitudes = $solicitudes ?? []; require BASE_PATH . '/app/views/mesas/solicitudes.php'; ?></div>
<?php foreach ($zonas as $zona => $ms): ?>
  <h6 class="fw-800 text-muted text-uppercase small mb-3 mt-4" style="letter-spacing:.08em"><i class="bi bi-geo-alt me-1"></i><?= e($zona) ?></h6>
  <div class="row g-3">
    <?php foreach ($ms as $m): [$cls, $lbl, $ico] = $est[$m['estado_real']];
        $href = $m['pedido_id'] ? url('pos/index', ['pedido' => $m['pedido_id']]) : url('pos/index', ['mesa' => $m['id']]); ?>
      <div class="col-6 col-md-4 col-xl-2">
        <a class="mesa <?= $m['estado_real'] ?>" href="<?= e($href) ?>">
          <div class="mi"><i class="bi <?= $ico ?>"></i></div>
          <div class="mn"><?= e($m['nombre']) ?></div>
          <div class="small text-muted mb-2"><i class="bi bi-person"></i> <?= (int)$m['capacidad'] ?> personas</div>
          <?php if ($m['pedido_id']): ?>
            <div class="fw-800 text-brand"><?= e(money($m['total'])) ?></div>
            <div class="small text-muted"><i class="bi bi-clock"></i> <?= e(date('H:i', strtotime($m['fecha_pedido']))) ?> · <?= (int)$m['items'] ?> ítems</div>
          <?php else: ?>
            <span class="bsoft <?= $cls ?>"><?= $lbl ?></span>
          <?php endif; ?>
          <?php if (!empty($proximas[$m['id']])): $pr = $proximas[$m['id']]; ?>
            <div class="small mt-2" style="color:#BE185D;font-weight:700" title="<?= e($pr['nombre']) ?> · <?= (int)$pr['personas'] ?> pers."><i class="bi bi-calendar-heart-fill"></i> <?= e($pr['hora']) ?> · <?= e(mb_strimwidth($pr['nombre'], 0, 16, '…')) ?></div>
          <?php endif; ?>
        </a>
        <?php if (!$m['pedido_id']): ?>
          <form method="post" action="<?= e(url('mesas/reservar')) ?>" class="text-center mt-1"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <button class="btn btn-link btn-sm text-muted p-0" style="font-size:.74rem"><?= $m['estado_real'] === 'reservada' ? 'Quitar reserva' : 'Marcar reservada' ?></button></form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endforeach; ?>
<?php if (!$zonas): ?><div class="card-soft"><div class="empty"><i class="bi bi-grid-3x3-gap"></i>No hay mesas configuradas. <a href="<?= e(url('mesas/gestion')) ?>">Crear mesas</a></div></div><?php endif; ?>
<script>
document.addEventListener('DOMContentLoaded',function(){
  var box=document.getElementById('qrSol'),last=<?= count($solicitudes ?? []) ?>;
  function beep(){try{var c=new (window.AudioContext||window.webkitAudioContext)(),o=c.createOscillator(),g=c.createGain();o.connect(g);g.connect(c.destination);o.frequency.value=880;g.gain.value=.15;o.start();setTimeout(function(){o.stop()},250)}catch(e){}}
  function post(act,id){return fetch(APP.base+'/index.php?r=solicitudes/'+act,{method:'POST',headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-Token':APP.csrf},body:JSON.stringify({id:id})}).then(function(r){return r.json()})}
  function load(){fetch(APP.base+'/index.php?r=solicitudes/lista').then(function(r){return r.text()}).then(function(h){box.innerHTML=h;var n=(h.match(/data-qr-ok/g)||[]).length;if(n>last)beep();last=n;var b=document.querySelector('.sb-qr');if(b){b.textContent=n;b.style.display=n?'':'none'}}).catch(function(){})}
  box.addEventListener('click',function(e){var ok=e.target.closest('[data-qr-ok]'),rj=e.target.closest('[data-qr-rej]');if(!ok&&!rj)return;
    var b=ok||rj;b.disabled=true;post(ok?'aceptar':'rechazar',+(ok?ok.dataset.qrOk:rj.dataset.qrRej)).then(function(d){if(window.Swal)Swal.fire({toast:true,position:'top-end',icon:d.ok?'success':'error',title:d.msg,showConfirmButton:false,timer:2500});load()})});
  setInterval(load,8000);
});
</script>
