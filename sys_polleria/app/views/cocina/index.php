<div class="page-head">
  <div><h2>Pantalla de cocina</h2><p>Pedidos en preparación · se actualiza automáticamente cada 8 segundos.</p></div>
  <div class="d-flex gap-2 align-items-center">
    <span class="chip"><span class="pulse"></span>En vivo</span>
    <button class="btn btn-ghost" id="btnSound" title="Sonido de nuevos pedidos"><i class="bi bi-volume-up-fill"></i></button>
  </div>
</div>
<div id="kdsBox"><?php $this->partial('cocina/lista', ['pedidos' => $pedidos]); ?></div>
<script>
(function () {
  var box = document.getElementById('kdsBox'), sound = true, last = <?= count($pedidos) ?>;
  var btn = document.getElementById('btnSound');
  btn.onclick = function () { sound = !sound; btn.innerHTML = '<i class="bi bi-volume-' + (sound ? 'up' : 'mute') + '-fill"></i>'; };
  function beep() {
    try { var c = new (window.AudioContext || window.webkitAudioContext)(), o = c.createOscillator(), g = c.createGain();
      o.connect(g); g.connect(c.destination); o.frequency.value = 880; g.gain.value = .15; o.start(); setTimeout(function () { o.stop(); c.close(); }, 250); } catch (e) {}
  }
  async function refresh() {
    try {
      var r = await fetch(APP.base + '/index.php?r=cocina/lista', { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!r.ok) return; box.innerHTML = await r.text();
      var n = box.querySelectorAll('.kds').length;
      if (n > last && sound) beep(); last = n;
    } catch (e) {}
  }
  setInterval(refresh, 8000);
  box.addEventListener('click', async function (e) {
    var b = e.target.closest('[data-set]'); if (!b) return;
    var body = b.dataset.pedido ? { pedido_id: +b.dataset.pedido, estado: b.dataset.set } : { id: +b.dataset.id, estado: b.dataset.set };
    var r = await api('cocina/item', body); if (r.ok) refresh(); else toast('error', r.msg);
  });
})();
</script>
