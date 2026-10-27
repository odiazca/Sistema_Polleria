<div class="page-head"><div><h2>Nueva compra</h2><p>Los insumos comprados se suman al stock y actualizan el costo.</p></div><a href="<?= e(url('compras/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver</a></div>
<form method="post" action="<?= e(url('compras/guardar')) ?>" id="frmCompra">
  <?= csrf_field() ?>
  <div class="row g-3">
    <div class="col-lg-4">
      <div class="card-soft"><div class="card-h"><h5>Datos de la compra</h5></div><div class="card-b">
        <label class="form-label">Proveedor</label>
        <select class="form-select mb-3" name="proveedor_id"><option value="">— Sin proveedor —</option><?php foreach ($provs as $p): ?><option value="<?= (int)$p['id'] ?>"><?= e($p['razon_social']) ?></option><?php endforeach; ?></select>
        <label class="form-label">Fecha</label><input type="date" class="form-control mb-3" name="fecha" value="<?= date('Y-m-d') ?>" required>
        <label class="form-label">N° de comprobante</label><input class="form-control mb-3" name="comprobante" placeholder="F001-12345">
        <label class="form-label">Notas</label><textarea class="form-control" rows="2" name="notas"></textarea>
      </div></div>
    </div>
    <div class="col-lg-8">
      <div class="card-soft"><div class="card-h"><h5>Insumos comprados</h5><button type="button" class="btn btn-soft btn-sm" id="addRow"><i class="bi bi-plus-lg me-1"></i>Agregar línea</button></div>
      <div class="card-b"><div class="table-wrap"><table class="table table-modern" id="tbl">
        <thead><tr><th style="width:42%">Insumo</th><th>Cantidad</th><th>Costo unit.</th><th class="text-end">Subtotal</th><th></th></tr></thead><tbody></tbody>
        <tfoot><tr><td colspan="3" class="text-end fw-bold">TOTAL</td><td class="text-end fw-800 fs-5" id="gt">0</td><td></td></tr></tfoot>
      </table></div>
      <div class="text-end mt-3"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Registrar compra</button></div></div></div>
    </div>
  </div>
</form>
<script>
document.addEventListener("DOMContentLoaded", function () {
  var ins = <?= json_encode($insumos, JSON_UNESCAPED_UNICODE) ?>, tb = document.querySelector('#tbl tbody');
  function row() {
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><select class="form-select form-select-sm" name="insumo_id[]" required><option value="">— Seleccione —</option>' + ins.map(function (i) { return '<option value="' + i.id + '" data-c="' + i.costo + '" data-u="' + esc(i.unidad) + '">' + esc(i.nombre) + ' (' + esc(i.unidad) + ')</option>'; }).join('') + '</select></td>' +
      '<td><input type="number" step="0.001" min="0" class="form-control form-control-sm" name="cantidad[]" value="1" required></td>' +
      '<td><input type="number" step="0.01" min="0" class="form-control form-control-sm" name="costo[]" value="0" required></td>' +
      '<td class="text-end fw-bold st">0</td><td class="text-end"><button type="button" class="btn btn-link text-danger p-0 rm"><i class="bi bi-x-circle"></i></button></td>';
    tb.appendChild(tr);
  }
  function calc() {
    var t = 0; tb.querySelectorAll('tr').forEach(function (tr) {
      var q = parseFloat(tr.querySelector('[name="cantidad[]"]').value) || 0, c = parseFloat(tr.querySelector('[name="costo[]"]').value) || 0;
      tr.querySelector('.st').textContent = fmtMoney(q * c); t += q * c; });
    document.getElementById('gt').textContent = fmtMoney(t);
  }
  document.getElementById('addRow').onclick = function () { row(); calc(); };
  tb.addEventListener('input', calc);
  tb.addEventListener('change', function (e) { if (e.target.name === 'insumo_id[]') { var o = e.target.selectedOptions[0]; e.target.closest('tr').querySelector('[name="costo[]"]').value = o.dataset.c || 0; calc(); } });
  tb.addEventListener('click', function (e) { var b = e.target.closest('.rm'); if (b) { b.closest('tr').remove(); calc(); } });
  row(); calc();
});
</script>
