<div class="page-head"><div><h2>Mi cuenta</h2><p>Actualice sus datos personales y contraseña.</p></div></div>
<div class="row g-4">
  <div class="col-lg-4">
    <div class="card-soft text-center p-4">
      <div class="avatar mx-auto mb-3" style="width:96px;height:96px;border-radius:28px;font-size:2rem"><?php if ($u['avatar']): ?><img src="<?= e(upload_url($u['avatar'])) ?>" alt=""><?php else: ?><?= e(initials($u['nombre'])) ?><?php endif; ?></div>
      <h5 class="fw-bold mb-0"><?= e($u['nombre']) ?></h5>
      <div class="text-muted small mb-3">@<?= e($u['usuario']) ?> · <?= e(Auth::user()['rol']) ?></div>
      <div class="small text-muted">Último acceso: <?= e(fdate($u['ultimo_acceso'], true)) ?></div>
    </div>
  </div>
  <div class="col-lg-8">
    <form method="post" enctype="multipart/form-data" class="card-soft">
      <?= csrf_field() ?>
      <div class="card-h"><h5>Datos personales</h5></div>
      <div class="card-b">
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Nombre completo</label><input class="form-control" name="nombre" value="<?= e($u['nombre']) ?>" required></div>
          <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" name="email" value="<?= e($u['email']) ?>"></div>
          <div class="col-12"><label class="form-label">Foto de perfil</label><input type="file" class="form-control" name="avatar" accept="image/*"></div>
        </div>
        <hr class="my-4">
        <h6 class="fw-bold mb-3">Cambiar contraseña <small class="text-muted fw-normal">(opcional)</small></h6>
        <div class="row g-3">
          <div class="col-md-4"><label class="form-label">Actual</label><input type="password" class="form-control" name="actual" autocomplete="current-password"></div>
          <div class="col-md-4"><label class="form-label">Nueva</label><input type="password" class="form-control" name="nueva" autocomplete="new-password"></div>
          <div class="col-md-4"><label class="form-label">Repetir nueva</label><input type="password" class="form-control" name="repetir" autocomplete="new-password"></div>
        </div>
        <div class="text-end mt-4"><button class="btn btn-brand" type="submit"><i class="bi bi-check2-circle me-1"></i>Guardar cambios</button></div>
      </div>
    </form>
  </div>
</div>
