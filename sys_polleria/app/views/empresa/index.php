<?php
$v = fn($k, $d = '') => e($s[$k] ?? $d);
$zonas = ['America/Lima' => 'Perú (Lima)', 'America/Bogota' => 'Colombia (Bogotá)', 'America/Guayaquil' => 'Ecuador (Guayaquil)', 'America/Mexico_City' => 'México (CDMX)',
    'America/Santiago' => 'Chile (Santiago)', 'America/Argentina/Buenos_Aires' => 'Argentina (Buenos Aires)', 'America/La_Paz' => 'Bolivia (La Paz)', 'America/Caracas' => 'Venezuela (Caracas)',
    'America/Montevideo' => 'Uruguay (Montevideo)', 'America/Asuncion' => 'Paraguay (Asunción)', 'America/Panama' => 'Panamá', 'America/Costa_Rica' => 'Costa Rica', 'America/Guatemala' => 'Guatemala',
    'America/Santo_Domingo' => 'Rep. Dominicana', 'America/New_York' => 'EE. UU. (Este)', 'Europe/Madrid' => 'España (Madrid)'];
$colores = ['#F97316', '#EF4444', '#F59E0B', '#10B981', '#14B8A6', '#3B82F6', '#6366F1', '#8B5CF6', '#EC4899'];
?>
<div class="page-head">
  <div><h2>Configuración de la empresa</h2><p>Datos del negocio, moneda, impuestos, comprobantes y apariencia del sistema.</p></div>
</div>

<form method="post" enctype="multipart/form-data" action="<?= e(url('empresa/guardar')) ?>" id="frmEmpresa">
  <?= csrf_field() ?><input type="hidden" name="tab" id="tabInput" value="datos">
  <div class="row g-4">
    <div class="col-xl-3">
      <div class="card-soft p-3 mb-3">
        <div class="nav nav-pills nav-pills-soft flex-column gap-1" id="cfgTabs" role="tablist">
          <button type="button" class="nav-link active text-start" data-bs-toggle="pill" data-bs-target="#tab-datos" data-tab="datos"><i class="bi bi-building me-2"></i>Datos de la empresa</button>
          <button type="button" class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-moneda" data-tab="moneda"><i class="bi bi-currency-exchange me-2"></i>Moneda y formato</button>
          <button type="button" class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-impuestos" data-tab="impuestos"><i class="bi bi-percent me-2"></i>Impuestos y comprobantes</button>
          <button type="button" class="nav-link text-start" data-bs-toggle="pill" data-bs-target="#tab-sistema" data-tab="sistema"><i class="bi bi-palette me-2"></i>Sistema y apariencia</button>
        </div>
      </div>
      <div class="card-soft p-4 text-center">
        <div class="text-muted small fw-bold text-uppercase mb-3" style="letter-spacing:.06em">Vista previa</div>
        <div class="logo-preview mx-auto mb-3" id="logoPrev2"><?php if (!empty($s['empresa_logo'])): ?><img src="<?= e(upload_url($s['empresa_logo'])) ?>" alt=""><?php else: ?><i class="bi bi-fire" style="color:var(--brand)"></i><?php endif; ?></div>
        <div class="fw-800" id="pvNombre"><?= $v('empresa_nombre') ?></div>
        <div class="small text-muted" id="pvRuc">RUC <?= $v('empresa_ruc') ?></div>
        <div class="small text-muted mt-1" id="pvDir"><?= $v('empresa_direccion') ?></div>
      </div>
    </div>

    <div class="col-xl-9">
      <div class="tab-content">
        <!-- DATOS -->
        <div class="tab-pane fade show active" id="tab-datos">
          <div class="card-soft">
            <div class="card-h"><div><h5>Datos generales</h5><small>Aparecen en tickets, comprobantes y en el menú</small></div></div>
            <div class="card-b">
              <div class="row g-4">
                <div class="col-md-4">
                  <label class="form-label">Logo de la empresa</label>
                  <div class="logo-preview mb-2" id="logoPrev"><?php if (!empty($s['empresa_logo'])): ?><img src="<?= e(upload_url($s['empresa_logo'])) ?>" alt=""><?php else: ?><i class="bi bi-image"></i><?php endif; ?></div>
                  <input type="file" class="form-control form-control-sm" name="logo" accept="image/*" data-preview="#logoPrev">
                  <div class="form-text">PNG, JPG, WEBP o SVG · máx. 2 MB</div>
                  <?php if (!empty($s['empresa_logo'])): ?><div class="form-check mt-2"><input class="form-check-input" type="checkbox" name="quitar_logo" id="qlogo"><label class="form-check-label small" for="qlogo">Quitar logo actual</label></div><?php endif; ?>
                </div>
                <div class="col-md-8">
                  <div class="row g-3">
                    <div class="col-12"><label class="form-label">Nombre comercial *</label><input class="form-control" name="empresa_nombre" id="inNombre" value="<?= $v('empresa_nombre') ?>" required></div>
                    <div class="col-md-8"><label class="form-label">Razón social</label><input class="form-control" name="empresa_razon_social" value="<?= $v('empresa_razon_social') ?>"></div>
                    <div class="col-md-4"><label class="form-label">RUC / NIT / ID fiscal</label><input class="form-control" name="empresa_ruc" id="inRuc" value="<?= $v('empresa_ruc') ?>"></div>
                    <div class="col-12"><label class="form-label">Eslogan</label><input class="form-control" name="empresa_eslogan" value="<?= $v('empresa_eslogan') ?>"></div>
                  </div>
                </div>
                <div class="col-md-8"><label class="form-label">Dirección</label><input class="form-control" name="empresa_direccion" id="inDir" value="<?= $v('empresa_direccion') ?>"></div>
                <div class="col-md-4"><label class="form-label">Ciudad / País</label><input class="form-control" name="empresa_ciudad" value="<?= $v('empresa_ciudad') ?>"></div>
                <div class="col-md-4"><label class="form-label">Teléfono</label><input class="form-control" name="empresa_telefono" value="<?= $v('empresa_telefono') ?>"></div>
                <div class="col-md-4"><label class="form-label">Celular / WhatsApp</label><input class="form-control" name="empresa_celular" value="<?= $v('empresa_celular') ?>"></div>
                <div class="col-md-4"><label class="form-label">Correo</label><input type="email" class="form-control" name="empresa_email" value="<?= $v('empresa_email') ?>"></div>
                <div class="col-12"><label class="form-label">Sitio web / redes</label><input class="form-control" name="empresa_web" value="<?= $v('empresa_web') ?>" placeholder="www.mipolleria.com"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- MONEDA -->
        <div class="tab-pane fade" id="tab-moneda">
          <div class="card-soft">
            <div class="card-h"><div><h5>Moneda y formato de números</h5><small>Se aplica a todo el sistema: ventas, reportes y tickets</small></div></div>
            <div class="card-b">
              <div class="mb-3">
                <label class="form-label">Moneda rápida</label>
                <div class="d-flex flex-wrap gap-2" id="presets">
                  <?php foreach ([['PEN', 'S/', 'Sol peruano'], ['USD', '$', 'Dólar estadounidense'], ['EUR', '€', 'Euro'], ['MXN', '$', 'Peso mexicano'], ['COP', '$', 'Peso colombiano'], ['CLP', '$', 'Peso chileno'], ['ARS', '$', 'Peso argentino'], ['BOB', 'Bs', 'Boliviano'], ['GTQ', 'Q', 'Quetzal'], ['DOP', 'RD$', 'Peso dominicano']] as $p): ?>
                    <button type="button" class="btn btn-ghost btn-sm" data-preset='<?= e(json_encode($p)) ?>'><?= e($p[1]) ?> <span class="text-muted"><?= e($p[0]) ?></span></button>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="row g-3">
                <div class="col-md-4"><label class="form-label">Código ISO</label><input class="form-control" name="moneda_codigo" id="mCod" value="<?= $v('moneda_codigo', 'PEN') ?>" maxlength="5"></div>
                <div class="col-md-4"><label class="form-label">Símbolo *</label><input class="form-control" name="moneda_simbolo" id="mSim" value="<?= $v('moneda_simbolo', 'S/') ?>" maxlength="6" required></div>
                <div class="col-md-4"><label class="form-label">Nombre de la moneda</label><input class="form-control" name="moneda_nombre" id="mNom" value="<?= $v('moneda_nombre', 'Sol peruano') ?>"></div>
                <div class="col-md-3"><label class="form-label">Posición del símbolo</label>
                  <select class="form-select" name="moneda_posicion" id="mPos"><option value="antes" <?= ($s['moneda_posicion'] ?? '') === 'antes' ? 'selected' : '' ?>>Antes (S/ 10.00)</option><option value="despues" <?= ($s['moneda_posicion'] ?? '') === 'despues' ? 'selected' : '' ?>>Después (10.00 S/)</option></select></div>
                <div class="col-md-3"><label class="form-label">Decimales</label>
                  <select class="form-select" name="decimales" id="mDec"><?php foreach ([0, 1, 2, 3, 4] as $n): ?><option value="<?= $n ?>" <?= (int)($s['decimales'] ?? 2) === $n ? 'selected' : '' ?>><?= $n ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Separador decimal</label>
                  <select class="form-select" name="sep_decimal" id="mSd"><option value="." <?= ($s['sep_decimal'] ?? '.') === '.' ? 'selected' : '' ?>>Punto ( . )</option><option value="," <?= ($s['sep_decimal'] ?? '.') === ',' ? 'selected' : '' ?>>Coma ( , )</option></select></div>
                <div class="col-md-3"><label class="form-label">Separador de miles</label>
                  <select class="form-select" name="sep_miles" id="mSm">
                    <?php foreach ([',' => 'Coma ( , )', '.' => 'Punto ( . )', ' ' => 'Espacio', '' => 'Ninguno'] as $k => $l): ?><option value="<?= e($k) ?>" <?= ($s['sep_miles'] ?? ',') === (string)$k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?>
                  </select></div>
              </div>
              <div class="preview-money mt-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div><div class="small text-muted fw-bold text-uppercase" style="letter-spacing:.06em">Así se verán los importes</div><div class="fs-2 fw-800" id="pvMoney">—</div></div>
                <div class="text-end small text-muted"><span id="pvMoney2"></span><br><span id="pvMoney3"></span></div>
              </div>
            </div>
          </div>
        </div>

        <!-- IMPUESTOS -->
        <div class="tab-pane fade" id="tab-impuestos">
          <div class="card-soft">
            <div class="card-h"><div><h5>Impuestos y comprobantes</h5><small>IGV/IVA, series de numeración y diseño del ticket</small></div></div>
            <div class="card-b">
              <div class="row g-3">
                <div class="col-md-4"><div class="form-check form-switch mt-md-4"><input class="form-check-input" type="checkbox" name="igv_activo" id="igvA" <?= ($s['igv_activo'] ?? '1') === '1' ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="igvA">Aplicar impuesto (IGV / IVA)</label></div></div>
                <div class="col-md-4"><label class="form-label">Porcentaje (%)</label><input type="number" step="0.01" min="0" max="100" class="form-control" name="igv_porcentaje" value="<?= $v('igv_porcentaje', '18') ?>"></div>
                <div class="col-md-4"><div class="form-check form-switch mt-md-4"><input class="form-check-input" type="checkbox" name="igv_incluido" id="igvI" <?= ($s['igv_incluido'] ?? '1') === '1' ? 'checked' : '' ?>><label class="form-check-label fw-semibold" for="igvI">Precios ya incluyen impuesto</label></div></div>
                <div class="col-12"><hr class="my-1"></div>
                <div class="col-md-4"><label class="form-label">Serie de tickets</label><input class="form-control" name="serie_ticket" value="<?= $v('serie_ticket', 'T001') ?>" maxlength="8"></div>
                <div class="col-md-4"><label class="form-label">Serie de boletas</label><input class="form-control" name="serie_boleta" value="<?= $v('serie_boleta', 'B001') ?>" maxlength="8"></div>
                <div class="col-md-4"><label class="form-label">Serie de facturas</label><input class="form-control" name="serie_factura" value="<?= $v('serie_factura', 'F001') ?>" maxlength="8"></div>
                <div class="col-md-4"><label class="form-label">Ancho del ticket</label>
                  <select class="form-select" name="ticket_ancho"><option value="80" <?= ($s['ticket_ancho'] ?? '80') === '80' ? 'selected' : '' ?>>80 mm</option><option value="58" <?= ($s['ticket_ancho'] ?? '') === '58' ? 'selected' : '' ?>>58 mm</option></select></div>
                <div class="col-md-8"><label class="form-label">Mensaje al pie del ticket</label><input class="form-control" name="ticket_mensaje" value="<?= $v('ticket_mensaje') ?>"></div>
                <div class="col-md-4"><label class="form-label">Costo de delivery por defecto</label><input type="number" step="0.01" min="0" class="form-control" name="costo_delivery" value="<?= $v('costo_delivery', '0') ?>"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- SISTEMA -->
        <div class="tab-pane fade" id="tab-sistema">
          <div class="card-soft">
            <div class="card-h"><div><h5>Sistema y apariencia</h5><small>Zona horaria, formato de fecha y color del sistema</small></div></div>
            <div class="card-b">
              <div class="row g-3">
                <div class="col-md-6"><label class="form-label">Zona horaria</label>
                  <select class="form-select" name="zona_horaria"><?php foreach ($zonas as $k => $l): ?><option value="<?= e($k) ?>" <?= ($s['zona_horaria'] ?? '') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Formato de fecha</label>
                  <select class="form-select" name="formato_fecha"><?php foreach (['d/m/Y' => '31/12/2026', 'm/d/Y' => '12/31/2026', 'Y-m-d' => '2026-12-31', 'd-m-Y' => '31-12-2026'] as $k => $l): ?><option value="<?= e($k) ?>" <?= ($s['formato_fecha'] ?? 'd/m/Y') === $k ? 'selected' : '' ?>><?= e($l) ?></option><?php endforeach; ?></select></div>
                <div class="col-md-3"><label class="form-label">Copias a conservar</label><input type="number" min="3" max="100" class="form-control" name="backup_retencion" value="<?= $v('backup_retencion', '10') ?>"></div>
                <div class="col-12">
                  <label class="form-label">Color principal del sistema</label>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php foreach ($colores as $c): ?><span class="swatch <?= strtolower($s['color_principal'] ?? '') === strtolower($c) ? 'active' : '' ?>" style="background:<?= $c ?>" data-color="<?= $c ?>"></span><?php endforeach; ?>
                    <input type="color" class="form-control form-control-color ms-2" name="color_principal" id="colorMain" value="<?= $v('color_principal', '#F97316') ?>" style="width:54px">
                  </div>
                  <div class="form-text">Se aplica al menú, botones y gráficos tras guardar.</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex justify-content-end gap-2 mt-3">
        <a href="<?= e(url('dashboard/index')) ?>" class="btn btn-ghost">Cancelar</a>
        <button class="btn btn-brand" type="submit"><i class="bi bi-check2-circle me-1"></i>Guardar configuración</button>
      </div>
    </div>
  </div>
</form>
