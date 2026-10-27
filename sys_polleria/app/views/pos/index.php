<div class="pos" id="pos">
  <section class="pos-left">
    <div class="d-flex gap-2 mb-3 flex-wrap">
      <div class="search-box flex-grow-1" style="min-width:220px"><i class="bi bi-search"></i><input type="search" id="q" class="form-control" placeholder="Buscar producto o código… (F2)" autocomplete="off"></div>
      <?php if (!$pos['caja'] && $pos['canCobrar']): ?>
        <a href="<?= e(url('caja/index')) ?>" class="btn btn-danger-soft"><i class="bi bi-exclamation-triangle me-1"></i>Caja cerrada · Abrir caja</a>
      <?php endif; ?>
    </div>
    <div class="cat-bar" id="cats"></div>
    <div class="prod-grid" id="grid"></div>
  </section>

  <aside class="pos-right">
    <div class="pr-head">
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="fw-800" id="ordTitle">Nuevo pedido</div>
        <button class="btn btn-ghost btn-sm" id="btnClear" title="Vaciar"><i class="bi bi-trash3"></i></button>
      </div>
      <div class="seg mb-2" id="tipoSeg">
        <button type="button" data-tipo="mesa"><i class="bi bi-grid-3x3-gap me-1"></i>Mesa</button>
        <button type="button" data-tipo="llevar"><i class="bi bi-bag me-1"></i>Llevar</button>
        <button type="button" data-tipo="delivery"><i class="bi bi-bicycle me-1"></i>Delivery</button>
      </div>
      <div id="boxMesa"><select class="form-select form-select-sm" id="mesaSel"></select></div>
      <div id="boxDelivery" class="d-none">
        <input class="form-control form-control-sm mb-2" id="dirEntrega" placeholder="Dirección de entrega *">
        <div class="row g-2"><div class="col-6"><input class="form-control form-control-sm" id="telEntrega" placeholder="Teléfono"></div>
        <div class="col-6"><div class="input-group input-group-sm"><span class="input-group-text"><?= e(cfg('moneda_simbolo')) ?></span><input type="number" step="0.01" min="0" class="form-control" id="feeEntrega" placeholder="Envío"></div></div></div>
      </div>
      <button class="btn btn-ghost btn-sm w-100 mt-2 text-start d-flex align-items-center gap-2" id="btnCliente"><i class="bi bi-person-circle text-brand"></i><span class="flex-grow-1 text-truncate" id="cliTxt">Público general</span><i class="bi bi-chevron-right text-muted"></i></button>
    </div>
    <div class="cart" id="cart"></div>
    <div class="pr-foot">
      <div class="tot-row"><span>Subtotal</span><b id="tSub">0</b></div>
      <div class="tot-row align-items-center"><span>Descuento</span><input type="number" step="0.01" min="0" id="inDesc" class="form-control form-control-sm text-end" style="width:110px" value="0"></div>
      <div class="tot-row" id="rowFee" style="display:none"><span>Delivery</span><b id="tFee">0</b></div>
      <div class="tot-row" id="rowIgv"><span id="lblIgv">IGV</span><span id="tIgv">0</span></div>
      <div class="tot-big"><span>Total</span><span id="tTotal" class="text-brand">0</span></div>
      <input class="form-control form-control-sm mb-3" id="notas" placeholder="Notas del pedido (opcional)">
      <div class="d-flex gap-2">
        <button class="btn btn-ghost flex-fill" id="btnPre" title="Imprimir pre-cuenta"><i class="bi bi-printer"></i></button>
        <button class="btn btn-soft flex-fill" id="btnSend"><i class="bi bi-fire me-1"></i>Enviar a cocina</button>
        <?php if ($pos['canCobrar']): ?><button class="btn btn-ok flex-fill" id="btnPay" style="flex:1.4"><i class="bi bi-cash-coin me-1"></i>Cobrar</button><?php endif; ?>
      </div>
    </div>
  </aside>
</div>

<!-- Cliente -->
<div class="modal fade" id="mdCli" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-person-lines-fill text-brand me-2"></i>Cliente</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="search-box mb-3"><i class="bi bi-search"></i><input id="cliQ" class="form-control" placeholder="Buscar por nombre, documento o teléfono"></div>
    <div id="cliList" style="max-height:230px;overflow:auto"></div>
    <hr>
    <div class="fw-bold small mb-2">Registrar cliente nuevo</div>
    <div class="row g-2">
      <div class="col-4"><select id="ncTipo" class="form-select form-select-sm"><option>DNI</option><option>RUC</option><option>CE</option><option>OTRO</option></select></div>
      <div class="col-8"><input id="ncDoc" class="form-control form-control-sm" placeholder="Número de documento"></div>
      <div class="col-12"><input id="ncNom" class="form-control form-control-sm" placeholder="Nombre o razón social *"></div>
      <div class="col-5"><input id="ncTel" class="form-control form-control-sm" placeholder="Teléfono"></div>
      <div class="col-7"><input id="ncDir" class="form-control form-control-sm" placeholder="Dirección"></div>
      <div class="col-12"><button class="btn btn-soft btn-sm w-100" id="ncSave"><i class="bi bi-person-plus me-1"></i>Guardar y seleccionar</button></div>
    </div>
  </div>
  <div class="modal-footer"><button class="btn btn-ghost" id="cliNone">Público general</button></div>
</div></div></div>

<!-- Cobro -->
<div class="modal fade" id="mdPay" tabindex="-1"><div class="modal-dialog modal-dialog-centered"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title"><i class="bi bi-cash-coin text-success me-2"></i>Cobrar pedido</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
  <div class="modal-body">
    <div class="text-center p-3 mb-3 rounded-4" style="background:var(--brand-50)"><div class="small text-muted fw-bold text-uppercase">Total a pagar</div><div class="fw-800" style="font-size:2.3rem;letter-spacing:-.03em" id="pTotal">0</div></div>
    <label class="form-label">Comprobante</label>
    <div class="seg mb-3" id="compSeg"><button type="button" data-c="ticket" class="active">Ticket</button><button type="button" data-c="boleta">Boleta</button><button type="button" data-c="factura">Factura</button></div>
    <div class="alert alert-warning py-2 small border-0 d-none" id="facWarn" style="border-radius:12px">La factura requiere un cliente con RUC. Seleccione el cliente en el pedido.</div>
    <label class="form-label">Forma de pago</label>
    <div class="row g-2 mb-3" id="payMethods">
      <div class="col"><div class="pay-m" data-m="efectivo"><i class="bi bi-cash-stack"></i>Efectivo</div></div>
      <div class="col"><div class="pay-m" data-m="tarjeta"><i class="bi bi-credit-card"></i>Tarjeta</div></div>
      <div class="col"><div class="pay-m" data-m="yape"><i class="bi bi-phone"></i>Yape</div></div>
      <div class="col"><div class="pay-m" data-m="plin"><i class="bi bi-phone-vibrate"></i>Plin</div></div>
      <div class="col"><div class="pay-m" data-m="transferencia"><i class="bi bi-bank"></i>Transf.</div></div>
    </div>
    <div id="payLines"></div>
    <div class="d-flex gap-2 flex-wrap mb-3" id="quick"></div>
    <div class="d-flex justify-content-between p-3 rounded-4 bg-light"><div><div class="small text-muted">Recibido</div><div class="fw-800" id="pRec">0</div></div><div class="text-end"><div class="small text-muted" id="pDifLbl">Vuelto</div><div class="fw-800 fs-4" id="pDif">0</div></div></div>
  </div>
  <div class="modal-footer"><button class="btn btn-ghost" data-bs-dismiss="modal">Cancelar</button><button class="btn btn-ok" id="btnConfirmPay"><i class="bi bi-check2-circle me-1"></i>Confirmar cobro</button></div>
</div></div></div>

<script>window.POS = <?= json_encode($pos, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;</script>
