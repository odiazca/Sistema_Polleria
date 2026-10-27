/* Reservaciones: agenda del día, mesas y formulario */
(() => {
  const R = window.RV; if (!R) return;
  const $ = (s, c = document) => c.querySelector(s);
  const MES = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'septiembre', 'octubre', 'noviembre', 'diciembre'];
  const DIA = ['domingo', 'lunes', 'martes', 'miércoles', 'jueves', 'viernes', 'sábado'];
  const OC = { 'Cumpleaños': '🎂', 'Aniversario': '💞', 'Reunión familiar': '👨‍👩‍👧', 'Reunión de trabajo': '💼', 'Celebración': '🎉' };
  const CANAL = { web: ['bi-globe2', 'Web'], whatsapp: ['bi-whatsapp', 'WhatsApp'], telefono: ['bi-telephone-fill', 'Teléfono'], local: ['bi-shop', 'Local'] };
  let fecha = R.fecha, rows = [], filtro = 'todas', q = '', mdl, mdlCfg, mdlLink;

  const addDays = (f, n) => { const d = new Date(f + 'T12:00:00'); d.setDate(d.getDate() + n); return d.toISOString().slice(0, 10); };
  const largo = (f) => { const d = new Date(f + 'T12:00:00'); return DIA[d.getDay()].replace(/^./, (c) => c.toUpperCase()) + ' ' + d.getDate() + ' de ' + MES[d.getMonth()] + ' de ' + d.getFullYear(); };
  const hhmm = (h) => { const [a, b] = h.split(':').map(Number); return (a % 12 || 12) + ':' + String(b).padStart(2, '0') + (a >= 12 ? ' p. m.' : ' a. m.'); };
  const tel = (t) => { let n = String(t || '').replace(/\D/g, ''); if (n.length === 9) n = '51' + n; return n; };

  async function cargar(f) {
    if (f) fecha = f;
    const j = await api('reservas/dia&fecha=' + fecha);
    if (!j.ok) return toast('error', j.msg || 'No se pudo cargar');
    rows = j.rows; R.semana = j.semana; R.pend = j.pend;
    $('#rvDate').value = fecha; $('#rvTit').textContent = largo(fecha);
    history.replaceState(null, '', '?r=reservas/index&fecha=' + fecha);
    pintar();
  }

  function pintar() {
    // semana
    $('#rvWeek').innerHTML = R.semana.map((d) => `<button class="rv-day${d.sel ? ' on' : ''}${d.hoy ? ' hoy' : ''}" data-f="${d.f}"><span>${d.dia}</span><b>${d.n}</b>${d.c ? `<i class="${d.p ? 'p' : ''}">${d.c}</i>` : '<i class="z">·</i>'}</button>`).join('');
    const act = rows.filter((r) => !['cancelada', 'no_asistio'].includes(r.estado));
    $('#kRes').textContent = act.length; $('#kPer').textContent = act.reduce((s, r) => s + r.personas, 0);
    $('#kPen').textContent = rows.filter((r) => r.estado === 'pendiente').length; $('#kCon').textContent = rows.filter((r) => r.estado === 'confirmada').length;
    $('#rvSub').textContent = fecha === R.hoy ? 'Hoy' : (fecha < R.hoy ? 'Fecha pasada' : '');
    lista(); mesas();
  }

  function lista() {
    const ql = q.toLowerCase();
    const vis = rows.filter((r) => (filtro === 'todas' ? true : filtro === 'cerradas' ? ['completada', 'cancelada', 'no_asistio'].includes(r.estado) : r.estado === filtro)
      && (!ql || (r.nombre + ' ' + (r.telefono || '') + ' ' + (r.codigo || '')).toLowerCase().includes(ql)));
    const box = $('#rvList');
    if (!vis.length) { box.innerHTML = `<div class="empty"><i class="bi bi-calendar2-x"></i>${rows.length ? 'No hay reservas con ese filtro' : 'No hay reservas para este día'}<div class="mt-2"><button class="btn btn-sm btn-brand" data-act="new"><i class="bi bi-plus-lg me-1"></i>Nueva reserva</button></div></div>`; return; }
    const now = new Date(); const hoy = fecha === R.hoy; const nowM = now.getHours() * 60 + now.getMinutes();
    box.innerHTML = vis.map((r) => {
      const [ec, el, ei] = R.estados[r.estado]; const [h, m] = r.hora.split(':').map(Number);
      const tarde = hoy && ['pendiente', 'confirmada'].includes(r.estado) && nowM - (h * 60 + m) > 20;
      const act = ['pendiente', 'confirmada', 'sentada'].includes(r.estado);
      const wa = r.telefono ? `https://wa.me/${tel(r.telefono)}?text=${encodeURIComponent('Hola ' + r.nombre + ', le escribimos de ' + R.empresa + ' sobre su reserva del ' + largo(r.fecha) + ' a las ' + hhmm(r.hora) + ' para ' + r.personas + ' persona(s).')}` : '';
      const [ci, cl] = CANAL[r.origen] || CANAL.local;
      return `<div class="rv-item ${r.estado}${tarde ? ' tarde' : ''}" data-id="${r.id}">
        <div class="rv-hora"><b>${esc(hhmm(r.hora).split(' ')[0])}</b><small>${esc(hhmm(r.hora).split(' ').slice(1).join(' '))}</small></div>
        <div class="rv-body">
          <div class="d-flex flex-wrap align-items-center gap-2 mb-1"><b class="rv-nom">${esc(r.nombre)}</b>
            <span class="bsoft ${ec}"><i class="bi ${ei}"></i>${el}</span>${tarde ? '<span class="bsoft bad"><i class="bi bi-alarm"></i>Retrasada</span>' : ''}
            ${r.ocasion ? `<span class="bsoft violet">${OC[r.ocasion] || '✨'} ${esc(r.ocasion)}</span>` : ''}</div>
          <div class="rv-meta"><span><i class="bi bi-people-fill"></i> ${r.personas} personas</span>
            <span><i class="bi bi-grid-3x3-gap-fill"></i> ${r.mesa ? esc(r.mesa) + ' · ' + esc(r.zona) : '<em>Sin mesa asignada</em>'}</span>
            ${r.telefono ? `<span><i class="bi bi-telephone"></i> ${esc(r.telefono)}</span>` : ''}
            <span title="Canal"><i class="bi ${ci}"></i> ${cl}</span>${r.codigo ? `<span class="text-muted">${esc(r.codigo)}</span>` : ''}</div>
          ${r.notas ? `<div class="rv-nota"><i class="bi bi-chat-left-text"></i> ${esc(r.notas)}</div>` : ''}
        </div>
        <div class="rv-acts">
          ${r.estado === 'pendiente' ? `<button class="btn btn-sm btn-brand" data-act="estado" data-e="confirmada"><i class="bi bi-check2"></i> Confirmar</button>` : ''}
          ${['pendiente', 'confirmada'].includes(r.estado) ? `<button class="btn btn-sm btn-success" data-act="estado" data-e="sentada"><i class="bi bi-door-open-fill"></i> Llegó</button>` : ''}
          ${r.estado === 'sentada' ? `<button class="btn btn-sm btn-ghost" data-act="estado" data-e="completada"><i class="bi bi-check2-all"></i> Finalizar</button>${r.mesa_id && R.canPos ? `<a class="btn btn-sm btn-brand" href="${APP.base}/index.php?r=pos/index&mesa=${r.mesa_id}"><i class="bi bi-receipt"></i> Ir a la mesa</a>` : ''}` : ''}
          <div class="dropdown"><button class="btn btn-sm btn-ghost" data-bs-toggle="dropdown" aria-label="Más"><i class="bi bi-three-dots-vertical"></i></button>
            <ul class="dropdown-menu dropdown-menu-end">
              <li><a class="dropdown-item" href="#" data-act="edit"><i class="bi bi-pencil me-2"></i>Editar</a></li>
              ${wa ? `<li><a class="dropdown-item" target="_blank" rel="noopener" href="${wa}"><i class="bi bi-whatsapp me-2 text-success"></i>Escribir por WhatsApp</a></li>` : ''}
              ${r.telefono ? `<li><a class="dropdown-item" href="tel:${esc(r.telefono)}"><i class="bi bi-telephone me-2"></i>Llamar</a></li>` : ''}
              ${act && r.estado !== 'sentada' ? `<li><a class="dropdown-item" href="#" data-act="estado" data-e="no_asistio"><i class="bi bi-person-x me-2"></i>No asistió</a></li><li><a class="dropdown-item text-danger" href="#" data-act="estado" data-e="cancelada"><i class="bi bi-x-circle me-2"></i>Cancelar reserva</a></li>` : ''}
              ${['cancelada', 'no_asistio', 'completada'].includes(r.estado) ? `<li><a class="dropdown-item" href="#" data-act="estado" data-e="pendiente"><i class="bi bi-arrow-counterclockwise me-2"></i>Reabrir</a></li>` : ''}
              <li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#" data-act="del"><i class="bi bi-trash me-2"></i>Eliminar</a></li>
            </ul></div>
        </div></div>`;
    }).join('');
  }

  function mesas() {
    const act = rows.filter((r) => ['pendiente', 'confirmada', 'sentada'].includes(r.estado));
    const sin = act.filter((r) => !r.mesa_id);
    let h = R.mesas.map((m) => {
      const rs = act.filter((r) => r.mesa_id === m.id);
      return `<div class="rv-mrow"><div class="mn"><b>${esc(m.nombre)}</b><small>${esc(m.zona)} · ${m.capacidad} pers.</small></div><div class="mc">${rs.length ? rs.map((r) => `<span class="rv-chip ${r.estado}" title="${esc(r.nombre)} · ${r.personas} pers.">${r.hora}</span>`).join('') : '<small class="text-muted">Libre todo el día</small>'}</div></div>`;
    }).join('');
    if (sin.length) h += `<div class="rv-sin"><i class="bi bi-exclamation-circle-fill"></i> ${sin.length} reserva(s) sin mesa asignada</div>`;
    $('#rvMesas').innerHTML = h || '<div class="empty"><i class="bi bi-grid"></i>Sin mesas configuradas</div>';
  }

  // ---- Acciones
  document.addEventListener('click', async (e) => {
    const d = e.target.closest('[data-f]'); if (d && d.closest('#rvWeek')) { cargar(d.dataset.f); return; }
    const t = e.target.closest('#rvTabs button'); if (t) { filtro = t.dataset.f; document.querySelectorAll('#rvTabs button').forEach((b) => b.classList.toggle('on', b === t)); lista(); return; }
    const a = e.target.closest('[data-act]'); if (!a) return;
    e.preventDefault();
    const it = a.closest('.rv-item'); const r = it ? rows.find((x) => x.id === +it.dataset.id) : null;
    if (a.dataset.act === 'new') return abrir();
    if (a.dataset.act === 'edit') return abrir(r);
    if (a.dataset.act === 'estado') {
      if (['cancelada', 'no_asistio'].includes(a.dataset.e)) {
        const c = await Swal.fire({ title: a.dataset.e === 'cancelada' ? '¿Cancelar la reserva?' : '¿Marcar como no asistió?', text: r.nombre + ' · ' + hhmm(r.hora), icon: 'question', showCancelButton: true, confirmButtonText: 'Sí, continuar', cancelButtonText: 'Volver', confirmButtonColor: '#EF4444' });
        if (!c.isConfirmed) return;
      }
      const j = await api('reservas/estado', { id: r.id, estado: a.dataset.e });
      toast(j.ok ? 'success' : 'error', j.msg);
      if (j.ok && j.go) { location.href = j.go; return; }
      if (j.ok) cargar();
    }
    if (a.dataset.act === 'del') {
      const c = await Swal.fire({ title: '¿Eliminar la reserva?', text: 'Esta acción no se puede deshacer.', icon: 'warning', showCancelButton: true, confirmButtonText: 'Eliminar', cancelButtonText: 'Volver', confirmButtonColor: '#EF4444' });
      if (!c.isConfirmed) return;
      const j = await api('reservas/eliminar', { id: r.id }); toast(j.ok ? 'success' : 'error', j.msg); if (j.ok) cargar();
    }
  });
  $('#rvPrev').onclick = () => cargar(addDays(fecha, -1)); $('#rvNext').onclick = () => cargar(addDays(fecha, 1));
  $('#rvHoy').onclick = () => cargar(R.hoy); $('#rvDate').onchange = (e) => e.target.value && cargar(e.target.value);
  $('#rvQ').oninput = (e) => { q = e.target.value.trim(); lista(); };
  $('#rvNew').onclick = () => abrir();

  // ---- Formulario
  const F = () => $('#rvForm');
  function franjasHtml(sel) {
    let hs = R.franjas.slice(); if (sel && !hs.includes(sel)) { hs.push(sel); hs.sort(); }
    return hs.map((h) => `<option value="${h}" ${h === sel ? 'selected' : ''}>${hhmm(h)}</option>`).join('');
  }
  function abrir(r) {
    mdl = mdl || new bootstrap.Modal('#rvModal'); const f = F(); f.reset(); $('#rvErr').textContent = '';
    $('#rvMt').innerHTML = `<i class="bi bi-calendar-${r ? 'check' : 'plus'} me-2 text-brand"></i>${r ? 'Editar reserva' : 'Nueva reserva'}`;
    f.id.value = r ? r.id : ''; f.nombre.value = r ? r.nombre : ''; f.telefono.value = r ? r.telefono || '' : ''; f.email.value = r ? r.email || '' : '';
    f.fecha.value = r ? r.fecha : fecha; f.fecha.min = r ? '' : R.hoy; f.hora.innerHTML = franjasHtml(r ? r.hora : (R.franjas.find((h) => h >= '19:00') || R.franjas[0]));
    f.personas.value = r ? r.personas : 2; f.ocasion.value = r ? r.ocasion || '' : ''; f.notas.value = r ? r.notas || '' : '';
    f.origen.value = r ? r.origen : 'telefono'; f.estado.value = r ? r.estado : 'confirmada'; f.mesa_id.value = r && r.mesa_id ? r.mesa_id : '';
    mesasSel(); mdl.show(); setTimeout(() => f.nombre.focus(), 300);
  }
  let tmr;
  async function mesasSel() {
    const f = F(); clearTimeout(tmr);
    tmr = setTimeout(async () => {
      const p = Math.max(1, +f.personas.value || 1);
      const j = await api(`reservas/disponibilidad&fecha=${f.fecha.value}&hora=${f.hora.value}&personas=${p}&excluir=${f.id.value || 0}`);
      if (!j.ok) return; const sel = +f.mesa_id.value || 0;
      const aptas = j.mesas.filter((m) => m.apta && m.libre).length;
      $('#rvMesaHint').textContent = aptas ? aptas + ' mesa(s) disponible(s) para ' + p + ' pers.' : 'No hay mesas libres: puede dejarla sin mesa';
      $('#rvMesaSel').innerHTML = `<button type="button" class="rv-m${!sel ? ' on' : ''}" data-m=""><b>Sin asignar</b><small>Asignar luego</small></button>` + j.mesas.map((m) => {
        const bad = !m.libre || !m.apta; const why = !m.libre ? 'Ocupada ' + m.conflicto : (!m.apta ? 'Capacidad ' + m.capacidad : m.zona + ' · ' + m.capacidad + ' pers.');
        return `<button type="button" class="rv-m${sel === +m.id ? ' on' : ''}${bad ? ' off' : ''}" data-m="${m.id}" ${bad && sel !== +m.id ? 'disabled' : ''}><b>${esc(m.nombre)}</b><small>${esc(why)}</small></button>`;
      }).join('');
    }, 120);
  }
  $('#rvMesaSel').addEventListener('click', (e) => { const b = e.target.closest('.rv-m'); if (!b || b.disabled) return; F().mesa_id.value = b.dataset.m; document.querySelectorAll('#rvMesaSel .rv-m').forEach((x) => x.classList.toggle('on', x === b)); });
  ['fecha', 'hora', 'personas'].forEach((n) => F()[n].addEventListener('change', () => { F().mesa_id.value = F().mesa_id.value; mesasSel(); }));
  F().personas.addEventListener('input', mesasSel);
  F().onsubmit = async (e) => {
    e.preventDefault(); const f = F(); const b = f.querySelector('.btn-brand'); $('#rvErr').textContent = '';
    const body = Object.fromEntries(new FormData(f)); b.disabled = true;
    const j = await api('reservas/guardar', body); b.disabled = false;
    if (!j.ok) { $('#rvErr').textContent = j.msg; return; }
    toast('success', j.msg); mdl.hide(); cargar(body.fecha);
  };

  // ---- Configuración y enlace
  $('#rvCfg')?.addEventListener('click', () => { mdlCfg = mdlCfg || new bootstrap.Modal('#rvCfgModal'); mdlCfg.show(); });
  $('#rvCfgForm')?.addEventListener('submit', async (e) => {
    e.preventDefault(); const f = e.target; const body = Object.fromEntries(new FormData(f)); body.web = f.web.checked ? 1 : 0;
    const j = await api('reservas/config', body); toast(j.ok ? 'success' : 'error', j.msg); if (j.ok) setTimeout(() => location.reload(), 600);
  });
  $('#rvLink').onclick = () => { mdlLink = mdlLink || new bootstrap.Modal('#rvLinkModal'); mdlLink.show(); };
  $('#rvCopy').onclick = async () => { try { await navigator.clipboard.writeText($('#rvUrl').value); } catch (x) { $('#rvUrl').select(); document.execCommand('copy'); } toast('success', 'Enlace copiado'); };

  cargar();
  setInterval(() => { if (!document.hidden && !document.querySelector('.modal.show')) cargar(); }, 60000);
})();
