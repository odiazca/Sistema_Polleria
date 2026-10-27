<?php
/** Vista genérica de listado + modal de alta/edición. Variables: $c, $rows */
$cols = $c['columns'];

function crud_cell(array $col, array $row): string
{
    $k = $col['key'];
    $v = $row[$k] ?? null;
    $t = $col['type'] ?? 'text';
    switch ($t) {
        case 'strong': return '<span class="fw-bold">' . e($v) . '</span>' . (!empty($col['sub']) && !empty($row[$col['sub']]) ? '<div class="text-muted small">' . e($row[$col['sub']]) . '</div>' : '');
        case 'money':  return '<span class="fw-bold">' . e(money($v)) . '</span>';
        case 'number': return e(num($v, $col['dec'] ?? 0)) . (!empty($col['unit']) ? ' ' . e($row[$col['unit']] ?? $col['unit']) : '');
        case 'bool':   return $v ? '<span class="bsoft ok"><i class="bi bi-check-circle-fill"></i>Activo</span>' : '<span class="bsoft gray"><i class="bi bi-dash-circle"></i>Inactivo</span>';
        case 'img':    return $v ? '<img class="thumb" src="' . e(upload_url($v)) . '" alt="">' : '<span class="thumb"><i class="bi ' . e($col['icon'] ?? 'bi-image') . '"></i></span>';
        case 'badge':  return $v !== null && $v !== '' ? '<span class="bsoft ' . e($col['class'] ?? 'brand') . '">' . e($v) . '</span>' : '<span class="text-muted">—</span>';
        case 'map':
            [$cls, $lbl] = $col['map'][$v] ?? ['gray', (string)$v];
            return '<span class="bsoft ' . $cls . '">' . e($lbl) . '</span>';
        case 'color':  return '<span class="d-inline-flex align-items-center gap-2"><span style="width:18px;height:18px;border-radius:6px;background:' . e($v) . '"></span><span class="small text-muted">' . e($v) . '</span></span>';
        case 'date':   return e(fdate($v));
        case 'stock':
            if (isset($row['stock_control']) && !(int)$row['stock_control']) return '<span class="text-muted">—</span>';
            $low = isset($row['stock_minimo']) && (float)$v <= (float)$row['stock_minimo'];
            $unit = !empty($col['unit']) ? ' ' . e($row[$col['unit']] ?? '') : '';
            return '<span class="bsoft ' . ($low ? 'bad' : 'ok') . '">' . e(num($v, $col['dec'] ?? 0)) . $unit . '</span>';
        case 'muted':  return '<span class="text-muted">' . e($v ?: '—') . '</span>';
        default:       return e($v ?? '') ?: '<span class="text-muted">—</span>';
    }
}
?>
<div class="page-head">
  <div><h2><?= e($c['title']) ?></h2><p><?= e($c['subtitle'] ?: 'Gestione el listado de ' . mb_strtolower($c['title'])) ?></p></div>
  <div class="d-flex gap-2 flex-wrap align-items-center">
    <div class="search-box"><i class="bi bi-search"></i><input type="search" class="form-control" placeholder="Buscar…" data-filter-table="#crudTable" style="min-width:230px"></div>
    <?= $c['header_actions'] ?>
    <button class="btn btn-brand" id="btnNew"><i class="bi bi-plus-lg me-1"></i>Nuevo <?= e($c['singular']) ?></button>
  </div>
</div>

<div class="card-soft">
  <div class="table-wrap">
    <table class="table table-modern" id="crudTable">
      <thead><tr>
        <?php foreach ($cols as $col): ?><th class="<?= e($col['th'] ?? '') ?>"><?= e($col['label']) ?></th><?php endforeach; ?>
        <th class="text-end">Acciones</th>
      </tr></thead>
      <tbody>
      <?php foreach ($rows as $r): ?>
        <tr data-row="<?= e(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>">
          <?php foreach ($cols as $col): ?><td class="<?= e($col['td'] ?? '') ?>"><?= crud_cell($col, $r) ?></td><?php endforeach; ?>
          <td class="text-end text-nowrap">
            <?php foreach ($c['extra_actions'] as $ea): ?>
              <a class="btn btn-ghost btn-sm" href="<?= e($ea['url']($r)) ?>" title="<?= e($ea['label']) ?>"><i class="bi <?= e($ea['icon']) ?>"></i></a>
            <?php endforeach; ?>
            <button class="btn btn-soft btn-sm btn-edit" title="Editar"><i class="bi bi-pencil-square"></i></button>
            <?php if ($c['can_delete']): ?>
            <form method="post" action="<?= e(url($c['route'] . '/delete')) ?>" class="d-inline" data-confirm="Se eliminará este registro de forma permanente." data-danger="1" data-btn="Eliminar">
              <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn btn-danger-soft btn-sm" title="Eliminar"><i class="bi bi-trash"></i></button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$rows): ?><tr><td colspan="<?= count($cols) + 1 ?>"><div class="empty"><i class="bi <?= e($c['icon']) ?>"></i>No hay registros todavía. Pulse «Nuevo <?= e($c['singular']) ?>».</div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="modal fade" id="crudModal" tabindex="-1">
  <div class="modal-dialog modal-dialog-centered <?= e($c['modal'] ?? 'modal-lg') ?> modal-dialog-scrollable">
    <form class="modal-content" method="post" enctype="multipart/form-data" action="<?= e(url($c['route'] . '/save')) ?>" id="crudForm">
      <?= csrf_field() ?><input type="hidden" name="id" id="f_id">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi <?= e($c['icon']) ?> text-brand me-2"></i><span id="crudTitle">Nuevo <?= e($c['singular']) ?></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <?php foreach ($c['fields'] as $f):
            $n = $f['name']; $type = $f['type'] ?? 'text'; $col = $f['col'] ?? 6; $req = !empty($f['required']) ? 'required' : '';
            $def = $f['default'] ?? ''; ?>
            <div class="col-md-<?= (int)$col ?>">
              <?php if ($type === 'switch'): ?>
                <div class="form-check form-switch mt-md-4 pt-md-1">
                  <input class="form-check-input" type="checkbox" name="<?= e($n) ?>" id="f_<?= e($n) ?>" data-default="<?= (int)($f['default'] ?? 1) ?>" <?= ($f['default'] ?? 1) ? 'checked' : '' ?>>
                  <label class="form-check-label fw-semibold" for="f_<?= e($n) ?>"><?= e($f['label']) ?></label>
                </div>
              <?php else: ?>
                <label class="form-label" for="f_<?= e($n) ?>"><?= e($f['label']) ?><?= $req ? ' *' : '' ?></label>
                <?php if ($type === 'select'): ?>
                  <select class="form-select" name="<?= e($n) ?>" id="f_<?= e($n) ?>" <?= $req ?> data-default="<?= e($def) ?>">
                    <?php if (empty($f['required']) || !empty($f['blank'])): ?><option value="">— Seleccione —</option><?php endif; ?>
                    <?php foreach ($f['options'] as $ov => $ol): ?><option value="<?= e($ov) ?>" <?= (string)$def === (string)$ov ? 'selected' : '' ?>><?= e($ol) ?></option><?php endforeach; ?>
                  </select>
                <?php elseif ($type === 'textarea'): ?>
                  <textarea class="form-control" rows="2" name="<?= e($n) ?>" id="f_<?= e($n) ?>" <?= $req ?>></textarea>
                <?php elseif ($type === 'image'): ?>
                  <div class="d-flex align-items-center gap-3">
                    <div class="thumb" style="width:64px;height:64px" id="prev_<?= e($n) ?>"><i class="bi bi-image"></i></div>
                    <input type="file" class="form-control" name="<?= e($n) ?>" id="f_<?= e($n) ?>" accept="image/*" data-preview="#prev_<?= e($n) ?>">
                  </div>
                <?php elseif ($type === 'password'): ?>
                  <input type="password" class="form-control" name="<?= e($n) ?>" id="f_<?= e($n) ?>" autocomplete="new-password" placeholder="<?= e($f['placeholder'] ?? '') ?>">
                <?php elseif ($type === 'color'): ?>
                  <input type="color" class="form-control form-control-color w-100" name="<?= e($n) ?>" id="f_<?= e($n) ?>" value="<?= e($def ?: '#F97316') ?>" data-default="<?= e($def ?: '#F97316') ?>">
                <?php else:
                  $itype = in_array($type, ['email', 'tel', 'date', 'number'], true) ? $type : ($type === 'money' ? 'number' : 'text'); ?>
                  <input type="<?= $itype ?>" class="form-control" name="<?= e($n) ?>" id="f_<?= e($n) ?>" value="<?= e($def) ?>" data-default="<?= e($def) ?>"
                         <?= in_array($type, ['money', 'number'], true) ? 'step="' . e($f['step'] ?? ($type === 'money' ? '0.01' : 'any')) . '" min="' . e($f['min'] ?? '0') . '"' : '' ?>
                         placeholder="<?= e($f['placeholder'] ?? '') ?>" <?= $req ?>>
                <?php endif; ?>
                <?php if (!empty($f['help'])): ?><div class="form-text"><?= e($f['help']) ?></div><?php endif; ?>
              <?php endif; ?>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-brand" type="submit"><i class="bi bi-check2-circle me-1"></i>Guardar</button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
  var modal = new bootstrap.Modal(document.getElementById('crudModal'));
  var form = document.getElementById('crudForm');
  var singular = <?= json_encode($c['singular']) ?>;
  var imgFields = <?= json_encode(array_values(array_map(fn($f) => $f['name'], array_filter($c['fields'], fn($f) => ($f['type'] ?? '') === 'image')))) ?>;
  var pwFields = <?= json_encode(array_values(array_map(fn($f) => $f['name'], array_filter($c['fields'], fn($f) => ($f['type'] ?? '') === 'password')))) ?>;

  function reset() {
    form.reset();
    document.getElementById('f_id').value = '';
    form.querySelectorAll('[data-default]').forEach(function (el) {
      if (el.type === 'checkbox') el.checked = el.dataset.default === '1'; else el.value = el.dataset.default;
    });
    imgFields.forEach(function (n) { document.getElementById('prev_' + n).innerHTML = '<i class="bi bi-image"></i>'; });
    pwFields.forEach(function (n) { var i = document.getElementById('f_' + n); i.required = <?= json_encode(array_values(array_map(fn($f) => !empty($f['required']), array_filter($c['fields'], fn($f) => ($f['type'] ?? '') === 'password')))[0] ?? false) ?>; });
  }
  document.getElementById('btnNew').addEventListener('click', function () {
    reset(); document.getElementById('crudTitle').textContent = 'Nuevo ' + singular; modal.show();
  });
  document.querySelectorAll('.btn-edit').forEach(function (b) {
    b.addEventListener('click', function () {
      var row = JSON.parse(b.closest('tr').dataset.row);
      reset();
      document.getElementById('crudTitle').textContent = 'Editar ' + singular;
      document.getElementById('f_id').value = row.id;
      form.querySelectorAll('[name]').forEach(function (el) {
        var n = el.name; if (n === 'id' || n === '_token' || !(n in row)) return;
        if (el.type === 'checkbox') el.checked = String(row[n]) === '1';
        else if (el.type === 'file' || el.type === 'password') return;
        else el.value = row[n] === null ? '' : row[n];
      });
      imgFields.forEach(function (n) { if (row[n]) document.getElementById('prev_' + n).innerHTML = '<img src="' + APP.base + '/uploads/' + row[n] + '" alt="">'; });
      pwFields.forEach(function (n) { document.getElementById('f_' + n).required = false; });
      modal.show();
    });
  });
});
</script>
