/* Punto de venta */
(function () {
  'use strict';
  const P = window.POS;
  const $ = id => document.getElementById(id);
  const S = { tipo: P.tipo, mesa_id: P.mesaSel, cliente: null, items: [], pedido_id: null, cat: 0, q: '', pagos: [], comp: 'ticket' };
  const catMap = {}; P.cats.forEach(c => catMap[c.id] = c);
  const ESTADO = { pendiente: ['warn', 'En cola'], preparando: ['info', 'Preparando'], listo: ['ok', 'Listo'], entregado: ['gray', 'Entregado'] };

  // ---------- Estado inicial desde pedido existente
  if (P.pedido) {
    const o = P.pedido;
    S.pedido_id = o.id; S.tipo = o.tipo; S.mesa_id = o.mesa_id; S.cliente = o.cliente;
    $('inDesc').value = o.descuento || 0;
    $('dirEntrega').value = o.direccion || ''; $('telEntrega').value = o.telefono || ''; $('feeEntrega').value = o.costo_delivery || '';
    $('notas').value = o.notas || '';
    S.items = P.items.map(i => ({ detalle_id: +i.id, producto_id: +i.producto_id, nombre: i.nombre, precio: +i.precio, cantidad: +i.cantidad, nota: i.nota || '', estado: i.estado }));
  } else if (P.deliveryFee) {
    $('feeEntrega').value = P.deliveryFee;
  }

  // ---------- Catálogo
  function renderCats() {
    const all = [{ id: 0, nombre: 'Todos', icono: 'bi-grid', color: '#64748B' }].concat(P.cats);
    $('cats').innerHTML = all.map(c => `<div class="cat-pill ${S.cat === c.id ? 'active' : ''}" data-id="${c.id}"><i class="bi ${esc(c.icono)}"></i>${esc(c.nombre)}</div>`).join('');
  }
  function renderGrid() {
    const q = S.q.toLowerCase();
    const list = P.prods.filter(p => (!S.cat || +p.categoria_id === S.cat) && (!q || (p.nombre + ' ' + (p.descripcion || '')).toLowerCase().includes(q)));
    if (!list.length) { $('grid').innerHTML = '<div class="empty" style="grid-column:1/-1"><i class="bi bi-search"></i>Sin resultados</div>'; return; }
    $('grid').innerHTML = list.map(p => {
      const c = catMap[p.categoria_id] || { color: '#F97316', icono: 'bi-egg-fried' };
      const out = +p.stock_control && +p.stock <= 0;
      const img = p.imagen ? `<img src="${APP.base}/uploads/${esc(p.imagen)}" alt="">` : `<i class="bi ${esc(c.icono)}" style="color:${esc(c.color)}"></i>`;
      const stk = +p.stock_control ? `<span class="ps ${out ? 'text-danger' : 'text-muted'}">${out ? 'Agotado' : 'Stock ' + (+p.stock)}</span>` : '';
      return `<div class="prod ${out ? 'agotado' : ''}" data-id="${p.id}"><div class="pi" style="background:${esc(c.color)}18">${img}</div>${stk}<div class="pn">${esc(p.nombre)}</div><div class="pp">${fmtMoney(p.precio)}</div></div>`;
    }).join('');
  }
  $('cats').addEventListener('click', e => { const el = e.target.closest('.cat-pill'); if (!el) return; S.cat = +el.dataset.id; renderCats(); renderGrid(); });
  $('grid').addEventListener('click', e => { const el = e.target.closest('.prod'); if (el) addProd(+el.dataset.id); });
  $('q').addEventListener('input', e => { S.q = e.target.value.trim(); renderGrid(); });
  $('q').addEventListener('keydown', e => {
    if (e.key === 'Enter') { const t = $('grid').querySelectorAll('.prod'); if (t.length === 1) { addProd(+t[0].dataset.id); $('q').value = ''; S.q = ''; renderGrid(); } }
  });
  document.addEventListener('keydown', e => { if (e.key === 'F2') { e.preventDefault(); $('q').focus(); } });

  // ---------- Carrito
  function addProd(id) {
    const p = P.prods.find(x => +x.id === id); if (!p) return;
    let it = S.items.find(i => i.producto_id === id && !i.detalle_id);
    if (it) {
      if (+p.stock_control && it.cantidad + 1 > +p.stock) { toast('warning', 'Stock máximo alcanzado'); return; }
      it.cantidad++;
    } else {
      S.items.push({ detalle_id: 0, producto_id: id, nombre: p.nombre, precio: +p.precio, cantidad: 1, nota: '', estado: 'pendiente' });
    }
    renderCart();
  }
  const locked = i => i.detalle_id && i.estado !== 'pendiente';

  function renderCart() {
    const cart = $('cart');
    if (!S.items.length) { cart.innerHTML = '<div class="empty"><i class="bi bi-cart3"></i>Seleccione productos del menú</div>'; totals(); return; }
    cart.innerHTML = S.items.map((i, n) => {
      const lk = locked(i); const est = ESTADO[i.estado] || ESTADO.pendiente;
      return `<div class="cl">
        <div class="flex-grow-1" style="min-width:0">
          <div class="nm">${esc(i.nombre)} ${i.detalle_id ? `<span class="bsoft ${est[0]}" style="font-size:.62rem;padding:2px 7px">${est[1]}</span>` : ''}</div>
          ${i.nota ? `<div class="nt"><i class="bi bi-chat-left-text"></i> ${esc(i.nota)}</div>` : ''}
          <div class="d-flex align-items-center gap-2 mt-1">
            ${lk ? `<span class="lock"><i class="bi bi-lock-fill"></i> Ya en cocina · x${+i.cantidad}</span>` :
              `<div class="qty"><button data-a="minus" data-n="${n}">−</button><span>${+i.cantidad}</span><button data-a="plus" data-n="${n}">+</button></div>`}
            <button class="btn btn-link btn-sm p-0 text-muted" data-a="note" data-n="${n}" title="Nota"><i class="bi bi-pencil-square"></i></button>
          </div>
        </div>
        <div class="text-end"><div class="fw-bold">${fmtMoney(i.precio * i.cantidad)}</div>
          ${lk ? '' : `<button class="btn btn-link btn-sm p-0 text-danger" data-a="del" data-n="${n}"><i class="bi bi-x-circle"></i></button>`}</div>
      </div>`;
    }).join('');
    totals();
  }
  $('cart').addEventListener('click', async e => {
    const b = e.target.closest('[data-a]'); if (!b) return;
    const n = +b.dataset.n, it = S.items[n], a = b.dataset.a;
    if (a === 'plus') { const p = P.prods.find(x => +x.id === it.producto_id); if (p && +p.stock_control && it.cantidad + 1 > +p.stock) { toast('warning', 'Stock máximo alcanzado'); return; } it.cantidad++; }
    if (a === 'minus') { it.cantidad--; if (it.cantidad <= 0) S.items.splice(n, 1); }
    if (a === 'del') S.items.splice(n, 1);
    if (a === 'note') {
      const r = await Swal.fire({ title: 'Nota para cocina', input: 'text', inputValue: it.nota, inputPlaceholder: 'Ej: sin ají, bien dorado, para llevar…', showCancelButton: true, confirmButtonText: 'Guardar', cancelButtonText: 'Cancelar' });
      if (r.isConfirmed) it.nota = (r.value || '').trim();
    }
    renderCart();
  });

  function calc() {
    const sub = S.items.reduce((a, i) => a + i.precio * i.cantidad, 0);
    const desc = Math.min(sub, Math.max(0, +$('inDesc').value || 0));
    const fee = S.tipo === 'delivery' ? Math.max(0, +$('feeEntrega').value || 0) : 0;
    const base = Math.max(0, sub - desc) + fee;
    let igv = 0, total = base;
    if (P.igv.activo) {
      const r = P.igv.pct / 100;
      if (P.igv.incluido) igv = base - base / (1 + r); else { igv = base * r; total = base + igv; }
    }
    return { sub, desc, fee, igv, total: Math.round(total * 100) / 100 };
  }
  function totals() {
    const t = calc();
    $('tSub').textContent = fmtMoney(t.sub);
    $('tFee').textContent = fmtMoney(t.fee);
    $('rowFee').style.display = S.tipo === 'delivery' ? '' : 'none';
    $('rowIgv').style.display = P.igv.activo ? '' : 'none';
    $('lblIgv').textContent = (P.igv.incluido ? 'IGV incluido (' : 'IGV (') + P.igv.pct + '%)';
    $('tIgv').textContent = fmtMoney(t.igv);
    $('tTotal').textContent = fmtMoney(t.total);
  }
  ['inDesc', 'feeEntrega'].forEach(id => $(id).addEventListener('input', totals));

  // ---------- Tipo de pedido / mesa
  function renderTipo() {
    document.querySelectorAll('#tipoSeg button').forEach(b => b.classList.toggle('active', b.dataset.tipo === S.tipo));
    $('boxMesa').classList.toggle('d-none', S.tipo !== 'mesa');
    $('boxDelivery').classList.toggle('d-none', S.tipo !== 'delivery');
    $('ordTitle').textContent = S.pedido_id ? 'Pedido #' + S.pedido_id : 'Nuevo pedido';
    totals();
  }
  $('tipoSeg').addEventListener('click', e => { const b = e.target.closest('button'); if (!b) return; S.tipo = b.dataset.tipo; renderTipo(); });

  const zonas = {};
  P.mesas.forEach(m => (zonas[m.zona] = zonas[m.zona] || []).push(m));
  $('mesaSel').innerHTML = '<option value="">— Seleccione mesa —</option>' + Object.keys(zonas).map(z =>
    `<optgroup label="${esc(z)}">` + zonas[z].map(m => {
      const busy = m.pedido_id && +m.pedido_id !== S.pedido_id;
      return `<option value="${m.id}" ${busy ? 'disabled' : ''} ${+m.id === +S.mesa_id ? 'selected' : ''}>${esc(m.nombre)} (${m.capacidad} pers.)${busy ? ' · ocupada' : ''}</option>`;
    }).join('') + '</optgroup>').join('');
  $('mesaSel').addEventListener('change', e => S.mesa_id = e.target.value ? +e.target.value : null);

  // ---------- Cliente
  const mdCli = new bootstrap.Modal($('mdCli'));
  function setCliente(c) {
    S.cliente = c;
    $('cliTxt').textContent = c ? c.nombre + (c.documento ? ' · ' + c.documento : '') : 'Público general';
    if (c && S.tipo === 'delivery') {
      if (!$('dirEntrega').value && c.direccion) $('dirEntrega').value = c.direccion;
      if (!$('telEntrega').value && c.telefono) $('telEntrega').value = c.telefono;
    }
    mdCli.hide();
  }
  async function buscarClientes() {
    const r = await api('api/clientes&q=' + encodeURIComponent($('cliQ').value.trim()));
    $('cliList').innerHTML = (r.data || []).map(c => `<div class="list-row cursor" data-id="${c.id}"><span class="rank"><i class="bi bi-person"></i></span><div class="flex-grow-1"><div class="fw-bold small">${esc(c.nombre)}</div><div class="text-muted" style="font-size:.74rem">${esc(c.tipo_doc)} ${esc(c.documento || '—')} · ${esc(c.telefono || '')}</div></div></div>`).join('') || '<div class="text-muted small p-2">Sin resultados</div>';
    $('cliList').dataset.rows = JSON.stringify(r.data || []);
  }
  let tmr; $('cliQ').addEventListener('input', () => { clearTimeout(tmr); tmr = setTimeout(buscarClientes, 250); });
  $('cliList').addEventListener('click', e => { const el = e.target.closest('[data-id]'); if (!el) return; const c = JSON.parse($('cliList').dataset.rows).find(x => x.id == el.dataset.id); setCliente(c); });
  $('btnCliente').addEventListener('click', () => { mdCli.show(); $('cliQ').value = ''; buscarClientes(); });
  $('cliNone').addEventListener('click', () => setCliente(null));
  $('ncSave').addEventListener('click', async () => {
    const r = await api('api/clienteNuevo', { tipo_doc: $('ncTipo').value, documento: $('ncDoc').value, nombre: $('ncNom').value, telefono: $('ncTel').value, direccion: $('ncDir').value });
    if (!r.ok) { toast('error', r.msg); return; }
    if (r.msg) toast('info', r.msg);
    ['ncDoc', 'ncNom', 'ncTel', 'ncDir'].forEach(i => $(i).value = '');
    setCliente(r.data);
  });
  if (S.cliente) setCliente(S.cliente), mdCli.hide();

  // ---------- Guardar / enviar
  function payload() {
    return {
      pedido_id: S.pedido_id, tipo: S.tipo, mesa_id: S.mesa_id, cliente_id: S.cliente ? S.cliente.id : null,
      descuento: +$('inDesc').value || 0, costo_delivery: +$('feeEntrega').value || 0,
      direccion: $('dirEntrega').value, telefono: $('telEntrega').value, notas: $('notas').value,
      items: S.items.map(i => ({ detalle_id: i.detalle_id || 0, producto_id: i.producto_id, cantidad: i.cantidad, nota: i.nota }))
    };
  }
  function validar() {
    if (!S.items.length) { toast('warning', 'Agregue productos al pedido'); return false; }
    if (S.tipo === 'mesa' && !S.mesa_id) { toast('warning', 'Seleccione una mesa'); $('mesaSel').focus(); return false; }
    if (S.tipo === 'delivery' && !$('dirEntrega').value.trim()) { toast('warning', 'Ingrese la dirección de entrega'); $('dirEntrega').focus(); return false; }
    return true;
  }
  function destino(id) {
    if (S.tipo === 'mesa') return APP.base + '/index.php?r=mesas/index';
    if (S.tipo === 'delivery') return APP.base + '/index.php?r=delivery/index';
    return APP.base + '/index.php?r=pos/index&pedido=' + id;
  }
  let busy = false;
  async function guardar() {
    if (busy || !validar()) return null;
    busy = true;
    try { const r = await api('pos/guardar', payload()); if (!r.ok) { toast('error', r.msg); return null; } S.pedido_id = r.pedido_id; return r; }
    finally { busy = false; }
  }
  $('btnSend').addEventListener('click', async () => {
    const r = await guardar(); if (!r) return;
    await Swal.fire({ icon: 'success', title: 'Pedido enviado a cocina', text: 'Pedido #' + r.pedido_id + ' · ' + fmtMoney(r.total), timer: 1400, showConfirmButton: false });
    location.href = destino(r.pedido_id);
  });
  $('btnPre').addEventListener('click', async () => {
    const r = await guardar(); if (!r) return;
    window.open(APP.base + '/index.php?r=pos/ticket&id=' + r.pedido_id + '&print=1', '_blank');
    renderTipo();
  });
  $('btnClear').addEventListener('click', async () => {
    if (!S.items.length) return;
    const r = await Swal.fire({ title: '¿Vaciar el pedido?', text: 'Se quitarán los productos que aún no están en cocina.', icon: 'question', showCancelButton: true, confirmButtonText: 'Vaciar', cancelButtonText: 'Cancelar' });
    if (r.isConfirmed) { S.items = S.items.filter(locked); renderCart(); }
  });

  // ---------- Cobro
  const btnPay = $('btnPay');
  if (btnPay) {
    const mdPay = new bootstrap.Modal($('mdPay'));
    const METODOS = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', yape: 'Yape', plin: 'Plin', transferencia: 'Transferencia' };
    const total = () => calc().total;

    function renderPay() {
      const t = total(), rec = S.pagos.reduce((a, p) => a + (+p.monto || 0), 0);
      $('pTotal').textContent = fmtMoney(t);
      $('payMethods').querySelectorAll('.pay-m').forEach(el => el.classList.toggle('active', S.pagos.some(p => p.metodo === el.dataset.m)));
      $('payLines').innerHTML = S.pagos.map((p, n) => `<div class="input-group mb-2"><span class="input-group-text" style="min-width:120px">${METODOS[p.metodo]}</span><input type="number" step="0.01" min="0" class="form-control text-end fw-bold" data-n="${n}" value="${p.monto}"><button class="btn btn-ghost" data-rm="${n}" type="button"><i class="bi bi-x-lg"></i></button></div>`).join('');
      const hasCash = S.pagos.some(p => p.metodo === 'efectivo');
      $('quick').innerHTML = hasCash ? '<button type="button" class="btn btn-soft btn-sm" data-q="exact">Exacto</button>' + [10, 20, 50, 100, 200].filter(v => v >= t || true).map(v => `<button type="button" class="btn btn-ghost btn-sm" data-q="${v}">${fmtNum(v, 0)}</button>`).join('') : '';
      $('pRec').textContent = fmtMoney(rec);
      const dif = Math.round((rec - t) * 100) / 100;
      $('pDifLbl').textContent = dif >= 0 ? 'Vuelto' : 'Falta';
      $('pDif').textContent = fmtMoney(Math.abs(dif));
      $('pDif').className = 'fw-800 fs-4 ' + (dif >= 0 ? 'text-success' : 'text-danger');
      const fac = S.comp === 'factura' && !(S.cliente && S.cliente.documento);
      $('facWarn').classList.toggle('d-none', !fac);
      $('btnConfirmPay').disabled = dif < 0 || fac || !S.pagos.length;
    }
    btnPay.addEventListener('click', () => {
      if (!validar()) return;
      if (!P.caja) { Swal.fire({ icon: 'warning', title: 'Caja cerrada', text: 'Debe abrir su caja para poder cobrar.', confirmButtonText: 'Ir a Caja' }).then(() => location.href = APP.base + '/index.php?r=caja/index'); return; }
      S.pagos = [{ metodo: 'efectivo', monto: total() }];
      S.comp = 'ticket';
      $('compSeg').querySelectorAll('button').forEach(b => b.classList.toggle('active', b.dataset.c === 'ticket'));
      renderPay(); mdPay.show();
    });
    $('compSeg').addEventListener('click', e => { const b = e.target.closest('button'); if (!b) return; S.comp = b.dataset.c; $('compSeg').querySelectorAll('button').forEach(x => x.classList.toggle('active', x === b)); renderPay(); });
    $('payMethods').addEventListener('click', e => {
      const el = e.target.closest('.pay-m'); if (!el) return; const m = el.dataset.m;
      const i = S.pagos.findIndex(p => p.metodo === m);
      if (i >= 0) S.pagos.splice(i, 1);
      else { const rest = Math.max(0, Math.round((total() - S.pagos.reduce((a, p) => a + (+p.monto || 0), 0)) * 100) / 100); S.pagos.push({ metodo: m, monto: rest || total() }); }
      renderPay();
    });
    $('payLines').addEventListener('input', e => { const n = e.target.dataset.n; if (n == null) return; S.pagos[n].monto = +e.target.value || 0; const keep = document.activeElement; renderPayTotalsOnly(); });
    function renderPayTotalsOnly() {
      const t = total(), rec = S.pagos.reduce((a, p) => a + (+p.monto || 0), 0), dif = Math.round((rec - t) * 100) / 100;
      $('pRec').textContent = fmtMoney(rec); $('pDifLbl').textContent = dif >= 0 ? 'Vuelto' : 'Falta'; $('pDif').textContent = fmtMoney(Math.abs(dif));
      $('pDif').className = 'fw-800 fs-4 ' + (dif >= 0 ? 'text-success' : 'text-danger');
      const fac = S.comp === 'factura' && !(S.cliente && S.cliente.documento);
      $('btnConfirmPay').disabled = dif < 0 || fac || !S.pagos.length;
    }
    $('payLines').addEventListener('click', e => { const b = e.target.closest('[data-rm]'); if (!b) return; S.pagos.splice(+b.dataset.rm, 1); renderPay(); });
    $('quick').addEventListener('click', e => {
      const b = e.target.closest('[data-q]'); if (!b) return;
      const cash = S.pagos.find(p => p.metodo === 'efectivo'); if (!cash) return;
      const others = S.pagos.filter(p => p !== cash).reduce((a, p) => a + (+p.monto || 0), 0);
      cash.monto = b.dataset.q === 'exact' ? Math.max(0, Math.round((total() - others) * 100) / 100) : +b.dataset.q;
      renderPay();
    });
    $('btnConfirmPay').addEventListener('click', async () => {
      if (busy) return; busy = true; $('btnConfirmPay').disabled = true;
      try {
        const body = Object.assign(payload(), { comprobante: S.comp, pagos: S.pagos });
        const r = await api('pos/cobrar', body);
        if (!r.ok) { toast('error', r.msg); return; }
        mdPay.hide();
        const res = await Swal.fire({
          icon: 'success', title: '¡Cobro registrado!', html: r.vuelto > 0 ? `Vuelto a entregar: <b style="font-size:1.4rem">${fmtMoney(r.vuelto)}</b>` : 'Pago exacto recibido',
          showCancelButton: true, confirmButtonText: '<i class="bi bi-printer"></i> Imprimir ticket', cancelButtonText: 'Nueva venta', allowOutsideClick: false
        });
        if (res.isConfirmed) window.open(r.ticket, '_blank');
        location.href = S.tipo === 'mesa' ? APP.base + '/index.php?r=mesas/index' : APP.base + '/index.php?r=pos/index';
      } finally { busy = false; $('btnConfirmPay').disabled = false; }
    });
  }

  // ---------- Init
  renderCats(); renderGrid(); renderCart(); renderTipo();
})();
