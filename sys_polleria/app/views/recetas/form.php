<div class="page-head">
  <div><h2><?= e($p['nombre']) ?></h2><p><?= e($p['categoria']) ?> · Precio de venta <b><?= e(money($p['precio'])) ?></b> · cantidades por <b>1 unidad vendida</b></p></div>
  <a href="<?= e(url('recetas/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a>
</div>
<form method="post" action="<?= e(url('recetas/guardar')) ?>">
  <?= csrf_field() ?><input type="hidden" name="producto_id" value="<?= (int)$p['id'] ?>">
  <div class="row g-3">
    <div class="col-lg-8">
      <div class="card-soft"><div class="card-h"><h5>Insumos del plato</h5><button type="button" class="btn btn-soft btn-sm" id="addRow"><i class="bi bi-plus-lg me-1"></i>Agregar insumo</button></div>
      <div class="card-b"><div class="table-wrap"><table class="table table-modern" id="tbl">
        <thead><tr><th style="width:45%">Insumo</th><th>Cantidad</th><th>Unidad</th><th class="text-end">Costo</th><th></th></tr></thead><tbody></tbody></table></div>
        <div class="text-muted small mt-2">Ej.: ¼ de pollo → Pollo fresco 0.4 kg, Papa 0.25 kg, Aceite 0.03 lt, Carbón 0.0125 saco.</div>
      </div></div>
    </div>
    <div class="col-lg-4">
      <div class="card-soft" style="background:linear-gradient(180deg,var(--brand-50),#fff)"><div class="card-h"><h5>Costeo</h5></div><div class="card-b">
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Costo de receta</span><b id="cCosto">0</b></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Precio de venta</span><b><?= e(money($p['precio'])) ?></b></div>
        <div class="d-flex justify-content-between py-2 border-bottom"><span class="text-muted">Ganancia</span><b id="cGan">0</b></div>
        <div class="d-flex justify-content-between py-2"><span class="text-muted">Costo / precio</span><b id="cPct">0%</b></div>
        <button class="btn btn-brand w-100 mt-3"><i class="bi bi-check2-circle me-1"></i>Guardar receta</button>
        <div class="small text-muted mt-2">Al guardar, el costo del producto se actualiza con este valor. Dejar la lista vacía elimina la receta.</div>
      </div></div>
    </div>
  </div>
</form>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var ins = <?= json_encode($insumos, JSON_UNESCAPED_UNICODE) ?>, price = <?= json_encode((float)$p['precio']) ?>, init = <?= json_encode($lineas) ?>;
  var tb = document.querySelector('#tbl tbody');
  function row(iid, q) {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><select class="form-select form-select-sm" name="insumo_id[]" required><option value="">— Seleccione —</option>' + ins.map(function (i) { return '<option value="' + i.id + '" data-c="' + i.costo + '" data-u="' + esc(i.unidad) + '"' + (+i.id === +iid ? ' selected' : '') + '>' + esc(i.nombre) + '</option>'; }).join('') + '</select></td>' +
      '<td><input type="number" step="0.0001" min="0" class="form-control form-control-sm" name="cantidad[]" value="' + (q || '') + '" required></td><td class="un text-muted">—</td><td class="text-end fw-bold st">0</td>' +
      '<td class="text-end"><button type="button" class="btn btn-link text-danger p-0 rm"><i class="bi bi-x-circle"></i></button></td>';
    tb.appendChild(tr);
  }
  function calc() {
    var t = 0;
    tb.querySelectorAll('tr').forEach(function (tr) {
      var o = tr.querySelector('select').selectedOptions[0], q = parseFloat(tr.querySelector('[name="cantidad[]"]').value) || 0, c = o ? parseFloat(o.dataset.c) || 0 : 0;
      tr.querySelector('.un').textContent = o && o.dataset.u ? o.dataset.u : '—'; tr.querySelector('.st').textContent = fmtMoney(q * c); t += q * c;
    });
    document.getElementById('cCosto').textContent = fmtMoney(t);
    document.getElementById('cGan').textContent = fmtMoney(price - t);
    document.getElementById('cPct').textContent = price > 0 ? Math.round(t / price * 100) + '%' : '—';
  }
  init.forEach(function (l) { row(l.insumo_id, l.cantidad); });
  if (!init.length) row();
  calc();
  document.getElementById('addRow').onclick = function () { row(); calc(); };
  tb.addEventListener('input', calc); tb.addEventListener('change', calc);
  tb.addEventListener('click', function (e) { var b = e.target.closest('.rm'); if (b) { b.closest('tr').remove(); calc(); } });
});
</script>
