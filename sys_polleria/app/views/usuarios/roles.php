<div class="page-head">
  <div><h2>Roles y permisos</h2><p>Defina qué módulos puede ver cada rol. El rol Administrador siempre tiene acceso total.</p></div>
  <a href="<?= e(url('usuarios/index')) ?>" class="btn btn-ghost"><i class="bi bi-arrow-left me-1"></i>Volver a usuarios</a>
</div>
<form method="post" action="<?= e(url('usuarios/guardarRoles')) ?>" class="card-soft">
  <?= csrf_field() ?>
  <div class="table-wrap"><table class="table table-modern align-middle">
    <thead><tr><th>Módulo</th>
      <?php foreach ($roles as $r): ?><th class="text-center"><?= e($r['nombre']) ?></th><?php endforeach; ?>
    </tr></thead>
    <tbody>
    <?php $lastG = ''; foreach ($mods as $key => $m):
        if (!empty($m[4])) continue; // solo administrador
        if ($m[2] !== $lastG): $lastG = $m[2]; ?>
        <tr><td colspan="<?= count($roles) + 1 ?>" class="bg-light fw-bold small text-uppercase text-muted" style="letter-spacing:.07em"><?= e($m[2]) ?></td></tr>
      <?php endif; ?>
      <tr>
        <td><i class="bi <?= e($m[1]) ?> text-brand me-2"></i><?= e($m[0]) ?></td>
        <?php foreach ($roles as $r):
            $all = $r['permisos'] === '*';
            $has = $all || in_array($key, array_map('trim', explode(',', $r['permisos'])), true); ?>
          <td class="text-center">
            <input class="form-check-input" type="checkbox" <?= $all ? 'checked disabled' : 'name="perm[' . (int)$r['id'] . '][]" value="' . e($key) . '"' . ($has ? ' checked' : '') ?>>
          </td>
        <?php endforeach; ?>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table></div>
  <div class="p-3 text-end border-top"><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar permisos</button></div>
</form>
