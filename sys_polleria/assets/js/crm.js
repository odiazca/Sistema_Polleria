/* CRM: clientes 360, ficha, campañas y recompensas */
(() => {
  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => [...c.querySelectorAll(s)];
  const ask = async (title, text, btn = 'Confirmar', danger = false) => (await Swal.fire({ title, text, icon: danger ? 'warning' : 'question', showCancelButton: true, confirmButtonText: btn, cancelButtonText: 'Volver', confirmButtonColor: danger ? '#EF4444' : '#F97316' })).isConfirmed;
  const done = (j, reload = true) => { toast(j.ok ? 'success' : 'error', j.msg || (j.ok ? 'Listo' : 'Error')); if (j.ok && reload) setTimeout(() => location.reload(), 500); return j.ok; };

  // ---------- Clientes 360: filtros
  const tbl = $('#crmTbl');
  if (tbl) {
    let seg = (window.CRM && CRM.filtro) || '', q = '';
    const rows = $$('tbody tr', tbl);
    const apply = () => {
      let n = 0;
      rows.forEach((r) => { const ok = (!seg || r.dataset.seg === seg) && (!q || r.dataset.q.includes(q)); r.style.display = ok ? '' : 'none'; if (ok) n++; });
      $('#crmEmpty').classList.toggle('d-none', n > 0);
    };
    $$('#crmChips button').forEach((b) => { b.classList.toggle('on', b.dataset.s === seg); b.onclick = () => { seg = b.dataset.s; $$('#crmChips button').forEach((x) => x.classList.toggle('on', x === b)); apply(); }; });
    $('#crmQ').oninput = (e) => { q = e.target.value.trim().toLowerCase(); apply(); };
    $('#crmSort').onchange = (e) => {
      const k = e.target.value, body = tbl.tBodies[0];
      rows.sort((a, b) => k === 'nombre' ? a.dataset.nombre.localeCompare(b.dataset.nombre, 'es') : k === 'dias' ? a.dataset.dias - b.dataset.dias : b.dataset[k] - a.dataset[k]).forEach((r) => body.appendChild(r));
    };
    apply();
  }

  // ---------- Modal de cliente (alta / edición)
  const mf = $('#crmForm');
  if (mf) {
    let mdl;
    const open = (c) => {
      mdl = mdl || new bootstrap.Modal('#crmModal'); mf.reset(); $('#crmErr').textContent = '';
      $('#crmMt').innerHTML = `<i class="bi bi-person-${c ? 'gear' : 'plus'} me-2 text-brand"></i>${c ? 'Editar cliente' : 'Nuevo cliente'}`;
      mf.id.value = c ? c.id : ''; mf.nombre.value = c ? c.nombre : ''; mf.documento.value = c ? c.documento || '' : ''; mf.documento.disabled = !!c;
      mf.telefono.value = c ? c.telefono || '' : ''; mf.email.value = c ? c.email || '' : ''; mf.fecha_nacimiento.value = c ? c.fecha_nacimiento || '' : '';
      mf.etiquetas.value = c ? c.etiquetas || '' : ''; mf.acepta_marketing.checked = c ? !!c.acepta_marketing : true;
      mdl.show(); setTimeout(() => mf.nombre.focus(), 300);
    };
    $('#crmNew')?.addEventListener('click', () => open());
    $('#crmEdit')?.addEventListener('click', (e) => open(JSON.parse(e.currentTarget.dataset.c)));
    mf.onsubmit = async (e) => {
      e.preventDefault(); const b = mf.querySelector('.btn-brand'); b.disabled = true;
      const body = Object.fromEntries(new FormData(mf)); body.acepta_marketing = mf.acepta_marketing.checked ? 1 : 0; body.id = mf.id.value;
      const j = await api('crm/guardar', body); b.disabled = false;
      if (!j.ok) { $('#crmErr').textContent = j.msg; return; }
      toast('success', j.msg); setTimeout(() => { if (window.CRM && CRM.cid) location.reload(); else location.href = APP.base + '/index.php?r=crm/ficha&id=' + j.id; }, 400);
    };
  }

  // ---------- Ficha: notas
  const nf = $('#crmNota');
  if (nf) {
    nf.onsubmit = async (e) => {
      e.preventDefault(); const body = Object.fromEntries(new FormData(nf)); body.cliente_id = +nf.dataset.cid;
      const j = await api('crm/nota', body); if (done(j, false)) { sessionStorage.setItem('crmTab', '#tNotas'); location.reload(); }
    };
    document.addEventListener('click', async (e) => {
      const h = e.target.closest('[data-nota-hecho]'); const d = e.target.closest('[data-nota-del]');
      if (h) { const j = await api('crm/notaHecho', { id: +h.dataset.notaHecho, hecho: +h.dataset.v }); if (j.ok) { sessionStorage.setItem('crmTab', '#tNotas'); location.reload(); } }
      if (d && await ask('¿Eliminar la nota?', 'No se puede deshacer.', 'Eliminar', true)) { const j = await api('crm/notaBorrar', { id: +d.dataset.notaDel }); if (j.ok) { sessionStorage.setItem('crmTab', '#tNotas'); location.reload(); } }
    });
    const t = sessionStorage.getItem('crmTab'); sessionStorage.removeItem('crmTab');
    if (t) $(`[data-bs-target="${t}"]`)?.click();
  }

  // ---------- Ficha: puntos
  document.addEventListener('click', async (e) => {
    const c = e.target.closest('[data-canje]'); if (!c || !window.CRM || !CRM.cid) return;
    if (!(await ask('¿Canjear «' + c.dataset.n + '»?', 'Se descontarán los puntos del cliente. Aplique el beneficio en su pedido.', 'Canjear'))) return;
    done(await api('crm/puntos', { tipo: 'canje', cliente_id: CRM.cid, recompensa_id: +c.dataset.canje }));
  });
  const aj = $('#crmAj');
  if (aj) aj.onsubmit = async (e) => { e.preventDefault(); const b = Object.fromEntries(new FormData(aj)); b.tipo = 'ajuste'; b.cliente_id = +aj.dataset.cid; done(await api('crm/puntos', b)); };

  // ---------- Campañas
  const cf = $('#cmpForm');
  if (cf) {
    const prev = () => {
      const m = $('#cmpMsg').value.trim();
      $('#cmpPrev').textContent = m ? m.replace(/\{nombre\}/g, 'María').replace(/\{puntos\}/g, '120').replace(/\{empresa\}/g, (window.CRM && CRM.empresa) || '') : 'Su mensaje aparecerá aquí…';
    };
    $('#cmpMsg').addEventListener('input', prev);
    $$('.crm-plant button').forEach((b) => b.onclick = () => { $('#cmpMsg').value = b.dataset.t; prev(); });
    cf.onsubmit = async (e) => {
      e.preventDefault(); $('#cmpErr').textContent = ''; const b = cf.querySelector('.btn-brand'); b.disabled = true;
      const j = await api('campanas/crear', Object.fromEntries(new FormData(cf))); b.disabled = false;
      if (!j.ok) { $('#cmpErr').textContent = j.msg; return; }
      toast('success', j.msg); setTimeout(() => location.href = APP.base + '/index.php?r=campanas/ver&id=' + j.id, 400);
    };
  }
  const del = $('#cmpDel');
  if (del) del.onclick = async () => { if (await ask('¿Eliminar la campaña?', 'Se borrará la lista de envíos.', 'Eliminar', true)) { const j = await api('campanas/eliminar', { id: +del.dataset.id }); if (j.ok) location.href = APP.base + '/index.php?r=campanas/index'; } };
  const ct = $('#cmpTbl');
  if (ct) {
    const upd = () => { const all = $$('tbody tr', ct), ok = all.filter((r) => r.classList.contains('crm-sent')).length; $('#cmpOk').textContent = ok; $('#cmpBar').style.width = (all.length ? Math.round(ok / all.length * 100) : 0) + '%'; };
    const mark = async (id, v) => {
      const j = await api('campanas/enviado', { id, enviado: v }); if (!j.ok) return;
      const tr = ct.querySelector(`tr[data-id="${id}"]`); tr.classList.toggle('crm-sent', !!v);
      const tg = tr.querySelector('[data-env-tg]'); tg.dataset.v = v ? 0 : 1; tg.innerHTML = `<i class="bi ${v ? 'bi-check-circle-fill text-success' : 'bi-circle'}"></i>`;
      tr.querySelector('[data-env]').lastChild.textContent = v ? 'Reenviar' : 'Enviar'; upd();
    };
    ct.addEventListener('click', (e) => {
      const a = e.target.closest('[data-env]'); if (a) setTimeout(() => mark(+a.dataset.env, 1), 400);
      const t = e.target.closest('[data-env-tg]'); if (t) mark(+t.dataset.envTg, +t.dataset.v);
    });
  }

  // ---------- Reglas y recompensas
  const rg = $('#rgForm');
  if (rg) rg.onsubmit = async (e) => { e.preventDefault(); done(await api('campanas/reglas', Object.fromEntries(new FormData(rg)))); };
  const pf = $('#prForm');
  if (pf) {
    let mdl; const open = (p) => { mdl = mdl || new bootstrap.Modal('#prModal'); pf.reset(); pf.id.value = p ? p.id : ''; pf.nombre.value = p ? p.nombre : ''; pf.puntos.value = p ? p.puntos : ''; pf.activa.checked = p ? !!+p.estado : true; mdl.show(); };
    $('#prNew')?.addEventListener('click', () => open());
    document.addEventListener('click', async (e) => {
      const b = e.target.closest('[data-pr]'); if (b) open(JSON.parse(b.dataset.pr));
      const d = e.target.closest('[data-pr-del]'); if (d && await ask('¿Eliminar la recompensa?', '', 'Eliminar', true)) done(await api('campanas/premio', { id: +d.dataset.prDel, borrar: 1 }));
    });
    pf.onsubmit = async (e) => { e.preventDefault(); done(await api('campanas/premio', { id: pf.id.value, nombre: pf.nombre.value, puntos: pf.puntos.value, inactivo: pf.activa.checked ? 0 : 1 })); };
  }
})();
