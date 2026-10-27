<?php
$w = cfg('ticket_ancho', '80') === '58' ? '58mm' : '80mm';
$compLbl = ['ticket' => 'TICKET DE VENTA', 'boleta' => 'BOLETA DE VENTA', 'factura' => 'FACTURA DE VENTA'];
$tipoLbl = ['mesa' => 'Mesa', 'llevar' => 'Para llevar', 'delivery' => 'Delivery'];
$logo = cfg('empresa_logo');
?><!doctype html>
<html lang="es"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $precuenta ? 'Pre-cuenta' : 'Comprobante' ?> · <?= e(Calc::numeroCompleto($p['serie'], $p['numero'])) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="<?= asset('css/app.css') ?>" rel="stylesheet">
<style>@page{size:<?= $w ?> auto;margin:2mm}</style></head>
<body>
<div class="ticket-page">
  <div class="text-center mb-3 no-print">
    <button class="btn btn-brand" onclick="window.print()"><i class="bi bi-printer me-1"></i>Imprimir</button>
    <a class="btn btn-ghost" href="<?= e(url('pos/index')) ?>"><i class="bi bi-plus-lg me-1"></i>Nueva venta</a>
    <?php if (Auth::can('ventas')): ?><a class="btn btn-ghost" href="<?= e(url('ventas/index')) ?>">Historial</a><?php endif; ?>
  </div>
  <div class="ticket" style="width:<?= $w ?>">
    <div class="c">
      <?php if ($logo): ?><img src="<?= e(upload_url($logo)) ?>" style="max-width:60%;max-height:60px;margin-bottom:4px" alt=""><br><?php endif; ?>
      <b style="font-size:14px"><?= e(cfg('empresa_nombre')) ?></b><br>
      <?php if (cfg('empresa_razon_social')): ?><?= e(cfg('empresa_razon_social')) ?><br><?php endif; ?>
      <?php if (cfg('empresa_ruc')): ?>RUC: <?= e(cfg('empresa_ruc')) ?><br><?php endif; ?>
      <?= e(cfg('empresa_direccion')) ?><br>
      <?php if (cfg('empresa_telefono')): ?>Tel: <?= e(cfg('empresa_telefono')) ?><br><?php endif; ?>
    </div>
    <hr>
    <div class="c"><b><?= $precuenta ? 'PRE-CUENTA' : e($compLbl[$p['comprobante']] ?? 'COMPROBANTE') ?></b><br>
      <?php if (!$precuenta): ?><b><?= e(Calc::numeroCompleto($p['serie'], $p['numero'])) ?></b><?php else: ?>Pedido #<?= (int)$p['id'] ?> (no válido como comprobante)<?php endif; ?></div>
    <hr>
    <div>Fecha: <?= e(fdate($p['fecha_pago'] ?: $p['fecha_pedido'], true)) ?></div>
    <div>Atiende: <?= e($p['cajero'] ?: '—') ?></div>
    <div>Tipo: <?= e($tipoLbl[$p['tipo']]) ?><?= $p['mesa'] ? ' · ' . e($p['mesa']) : '' ?></div>
    <div>Cliente: <?= e($p['cliente'] ?: 'Público general') ?></div>
    <?php if ($p['documento']): ?><div><?= e($p['tipo_doc']) ?>: <?= e($p['documento']) ?></div><?php endif; ?>
    <?php if ($p['direccion_entrega']): ?><div>Entrega: <?= e($p['direccion_entrega']) ?></div><?php endif; ?>
    <hr>
    <table>
      <tr><td><b>Cant</b></td><td><b>Descripción</b></td><td class="r"><b>Importe</b></td></tr>
      <?php foreach ($items as $i): ?>
        <tr><td><?= e(rtrim(rtrim(number_format($i['cantidad'], 2, '.', ''), '0'), '.')) ?></td><td><?= e($i['nombre']) ?><?= $i['nota'] ? '<br><small>* ' . e($i['nota']) . '</small>' : '' ?></td><td class="r"><?= e(money($i['subtotal'], false)) ?></td></tr>
      <?php endforeach; ?>
    </table>
    <hr>
    <table>
      <tr><td>Subtotal</td><td class="r"><?= e(money($p['subtotal'])) ?></td></tr>
      <?php if ($p['descuento'] > 0): ?><tr><td>Descuento</td><td class="r">-<?= e(money($p['descuento'])) ?></td></tr><?php endif; ?>
      <?php if ($p['costo_delivery'] > 0): ?><tr><td>Delivery</td><td class="r"><?= e(money($p['costo_delivery'])) ?></td></tr><?php endif; ?>
      <?php if ((float)$p['igv'] > 0): ?><tr><td><?= cfg('igv_incluido', '1') === '1' ? 'IGV incluido (' . e(cfg('igv_porcentaje')) . '%)' : 'IGV (' . e(cfg('igv_porcentaje')) . '%)' ?></td><td class="r"><?= e(money($p['igv'])) ?></td></tr><?php endif; ?>
      <tr><td><b style="font-size:14px">TOTAL</b></td><td class="r"><b style="font-size:14px"><?= e(money($p['total'])) ?></b></td></tr>
    </table>
    <?php if ($pagos): ?><hr><?php foreach ($pagos as $pg): ?><div><?= e(ucfirst($pg['metodo'])) ?>: <?= e(money($pg['monto'])) ?></div><?php endforeach; ?><?php endif; ?>
    <hr>
    <?php if (!empty($p['fe_hash']) || !empty($p['fe_qr'])): ?>
      <div class="c" style="font-size:10px">
        <?php if (!empty($p['fe_qr'])): ?><div id="feqr" style="display:inline-block;margin:4px 0"></div><br><?php endif; ?>
        Representación impresa de la <?= $p['comprobante'] === 'factura' ? 'factura' : 'boleta de venta' ?> electrónica<?= cfg('fe_modo') === 'simulado' ? ' <b>(SIMULADA - sin validez tributaria)</b>' : '' ?><br>
        <?php if (!empty($p['fe_hash'])): ?>Hash: <?= e($p['fe_hash']) ?><br><?php endif; ?>
        <?php if (!empty($p['fe_enlace'])): ?>Consulte en: <?= e($p['fe_enlace']) ?><?php endif; ?>
      </div><hr>
    <?php endif; ?>
    <div class="c"><?= e(cfg('ticket_mensaje')) ?></div>
  </div>
</div>
<?php if (!empty($p['fe_qr'])): ?><script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script><script>window.addEventListener('load',function(){try{new QRCode(document.getElementById('feqr'),{text:<?= json_encode($p['fe_qr']) ?>,width:96,height:96})}catch(e){}});</script><?php endif; ?>
<?php if ($auto): ?><script>window.addEventListener('load',function(){setTimeout(function(){window.print();},350);});</script><?php endif; ?>
</body></html>
