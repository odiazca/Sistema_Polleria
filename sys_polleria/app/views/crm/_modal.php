<?php /** Modal de alta/edición de cliente (CRM) */ ?>
<div class="modal fade" id="crmModal" tabindex="-1"><div class="modal-dialog modal-dialog-centered modal-lg modal-dialog-scrollable">
  <form class="modal-content" id="crmForm" novalidate>
    <div class="modal-header"><h5 class="modal-title fw-bold" id="crmMt"><i class="bi bi-person-plus me-2 text-brand"></i>Nuevo cliente</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <div class="modal-body"><input type="hidden" name="id">
      <div class="row g-3">
        <div class="col-md-8"><label class="form-label">Nombre completo *</label><input class="form-control" name="nombre" maxlength="150" required autocomplete="off"></div>
        <div class="col-md-4"><label class="form-label">DNI / Documento</label><input class="form-control" name="documento" maxlength="20" autocomplete="off"></div>
        <div class="col-md-6"><label class="form-label">Teléfono / WhatsApp</label><input class="form-control" name="telefono" maxlength="30" inputmode="tel" placeholder="987 654 321"></div>
        <div class="col-md-6"><label class="form-label">Correo</label><input type="email" class="form-control" name="email" maxlength="120"></div>
        <div class="col-md-6"><label class="form-label">Fecha de nacimiento</label><input type="date" class="form-control" name="fecha_nacimiento" max="<?= e(date('Y-m-d')) ?>"><div class="form-text">Para felicitarlo en su cumpleaños.</div></div>
        <div class="col-md-6"><label class="form-label">Etiquetas</label><input class="form-control" name="etiquetas" maxlength="200" placeholder="Ej: familia, oficina, delivery"><div class="form-text">Sepárelas con comas.</div></div>
        <div class="col-12"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="acepta_marketing" id="crmMk" checked><label class="form-check-label" for="crmMk">Acepta recibir promociones y mensajes</label></div></div>
      </div>
      <div class="text-danger small mt-2" id="crmErr"></div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-brand"><i class="bi bi-check2-circle me-1"></i>Guardar</button></div>
  </form>
</div></div>
