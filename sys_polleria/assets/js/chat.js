/* Chat IA: burbuja flotante en todas las pantallas (administrador) o página completa (#chatPage) */
(() => {
  if (window.__chatIA) return; window.__chatIA = true;

  /** Llamada a la API que lanza Error si el servidor responde ok:false */
  window.iaApi = async (route, data) => { const j = await api(route, data); if (!j || j.ok === false) throw new Error((j && (j.msg || j.message)) || 'Error de conexión'); return j; };
  /** Activa Gemini con solo la clave (valores gratuitos por defecto) y prueba la conexión */
  window.iaActivar = async (clave) => {
    await iaApi('asistente/guardarConfig', { proveedor: 'gemini', url: 'https://generativelanguage.googleapis.com/v1beta/openai', modelo: 'gemini-3.5-flash-lite', clave, resumen: 1 });
    await iaApi('asistente/probar', {});
  };

  const SUG = ['¿Cuánto vendimos hoy?', '¿Cuál es el plato más vendido del mes?', '¿Qué insumos tienen stock bajo?', '¿Cuántos pedidos de delivery hubo esta semana?', '¿Cómo registro una compra?'];
  const page = document.getElementById('chatPage'); let ST = null, root, box, inp, vistos = false, enviando = false;
  const fmtTxt = (t) => esc(t).replace(/\*\*(.+?)\*\*/g, '<b>$1</b>').replace(/\n/g, '<br>');
  const MONEY = /(total|importe|ingreso|venta|monto|utilidad|costo|precio|valor|deuda|gasto|margen|saldo|promedio|igv|descuento)/i;
  const cel = (v, c) => v === null ? '—' : typeof v === 'number' ? (MONEY.test(c) ? fmtMoney(v) : fmtNum(v, Number.isInteger(v) ? 0 : 2)) : esc(String(v));

  function build() {
    if (root) return;
    root = document.createElement('div'); root.className = 'chat-root' + (page ? ' chat-page' : '');
    root.innerHTML = `<div class="chat-panel" id="chatPanel"${page ? '' : ' hidden'}>
      <div class="chat-head"><div class="chat-av"><i class="bi bi-stars"></i></div><div class="flex-grow-1"><b>Asistente</b><small id="chatSub">Pregúnteme sobre su pollería</small></div>
        <button type="button" id="chatClr" title="Nueva conversación"><i class="bi bi-arrow-counterclockwise"></i></button>${page ? '' : '<button type="button" id="chatX" title="Cerrar"><i class="bi bi-x-lg"></i></button>'}</div>
      <div class="chat-msgs" id="chatMsgs"></div>
      <form class="chat-in" id="chatForm"><textarea id="chatInp" rows="1" maxlength="400" placeholder="Escriba su pregunta…" aria-label="Mensaje"></textarea><button id="chatGo" aria-label="Enviar"><i class="bi bi-send-fill"></i></button></form></div>
      ${page ? '' : '<button type="button" class="chat-fab" id="chatFab" aria-label="Abrir chat con IA"><i class="bi bi-chat-dots-fill"></i></button>'}`;
    (page || document.body).appendChild(root);
    box = root.querySelector('#chatMsgs'); inp = root.querySelector('#chatInp');
    root.querySelector('#chatFab')?.addEventListener('click', toggle); root.querySelector('#chatX')?.addEventListener('click', toggle);
    root.querySelector('#chatClr').onclick = limpiar;
    root.querySelector('#chatForm').onsubmit = (e) => { e.preventDefault(); enviar(); };
    inp.addEventListener('keydown', (e) => { if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); enviar(); } });
    inp.addEventListener('input', () => { inp.style.height = 'auto'; inp.style.height = Math.min(110, inp.scrollHeight) + 'px'; });
    box.addEventListener('click', (e) => {
      const c = e.target.closest('.chat-sug'); if (c) { inp.value = c.textContent; enviar(); return; }
      const t = e.target.closest('[data-tg]'); if (t) { const n = t.nextElementSibling; n.hidden = !n.hidden; t.classList.toggle('on', !n.hidden); }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape' && !page && !root.querySelector('#chatPanel').hidden) toggle(); });
    if (page) cargar();
  }
  async function toggle() {
    const p = root.querySelector('#chatPanel'); p.hidden = !p.hidden; document.body.classList.toggle('chat-open', !p.hidden);
    if (!p.hidden) { if (!vistos) await cargar(); setTimeout(() => inp.focus(), 100); }
  }
  async function cargar() {
    vistos = true; box.innerHTML = '';
    try { ST = await iaApi('chat/estado'); } catch (x) { return bot('No pude conectarme al sistema: ' + x.message); }
    root.querySelector('#chatSub').textContent = ST.listo ? ST.proveedor : 'Falta activar la IA';
    if (!ST.listo) return setup();
    if (!ST.historial.length) { bot(`¡Hola! Soy el asistente de **${ST.empresa}**. Puedo consultar sus ventas, platos, mesas, insumos, clientes, compras y gastos, o explicarle cómo usar el sistema.`); sugerencias(); }
    else { ST.historial.forEach((h) => { user(h.q); bot(h.a); }); }
    scroll();
  }
  function setup() {
    const d = document.createElement('div'); d.className = 'chat-m bot';
    d.innerHTML = `<div class="chat-b"><b>Active la IA gratis en 1 minuto</b><br>1. Entre a <a href="https://aistudio.google.com/apikey" target="_blank" rel="noopener">aistudio.google.com/apikey</a> y cree una clave gratuita.<br>2. Péguela aquí:
      <form class="mt-2"><input type="password" class="form-control form-control-sm" placeholder="Clave de Gemini" autocomplete="new-password"><button class="btn btn-brand btn-sm mt-2 w-100">Activar</button><div class="small text-danger mt-1" data-err></div></form></div>`;
    box.appendChild(d);
    d.querySelector('form').onsubmit = async (e) => {
      e.preventDefault(); const f = e.target, k = f.querySelector('input').value.trim(), er = f.querySelector('[data-err]'), b = f.querySelector('button'); er.textContent = '';
      if (k.length < 8) { er.textContent = 'Pegue la clave completa.'; return; } b.disabled = true; b.textContent = 'Probando…';
      try { await window.iaActivar(k); toast('success', 'IA activada'); cargar(); } catch (x) { er.textContent = x.message; b.disabled = false; b.textContent = 'Activar'; }
    };
  }
  function scroll() { box.scrollTop = box.scrollHeight; }
  function user(t) { const d = document.createElement('div'); d.className = 'chat-m me'; d.innerHTML = `<div class="chat-b">${esc(t)}</div>`; box.appendChild(d); }
  function bot(t, extra = '') { const d = document.createElement('div'); d.className = 'chat-m bot'; d.innerHTML = `<div class="chat-b">${fmtTxt(t)}${extra}</div>`; box.appendChild(d); return d; }
  function sugerencias() { const d = document.createElement('div'); d.className = 'chat-sugs'; d.innerHTML = SUG.map((s) => `<button type="button" class="chat-sug">${esc(s)}</button>`).join(''); box.appendChild(d); }
  function tabla(t) {
    if (!t || !t.filas.length || (t.filas.length === 1 && t.columnas.length === 1)) return '';
    return `<button type="button" class="chat-tg" data-tg><i class="bi bi-table"></i> Ver datos (${t.total})</button><div class="chat-tbl" hidden><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr>${t.columnas.map((c) => `<th>${esc(c.replace(/_/g, ' '))}</th>`).join('')}</tr></thead><tbody>${t.filas.map((f) => `<tr>${f.map((v, i) => `<td class="${typeof v === 'number' ? 'text-end' : ''}">${cel(v, t.columnas[i])}</td>`).join('')}</tr>`).join('')}</tbody></table></div>${t.total > t.filas.length ? `<div class="small text-muted px-2 pb-1">Se muestran ${t.filas.length} de ${t.total}. Para el detalle completo use el Asistente de reportes.</div>` : ''}</div>`;
  }
  async function enviar() {
    const m = inp.value.trim(); if (!m || enviando) return;
    if (ST && !ST.listo) { toast('warning', 'Primero active la IA'); return; }
    box.querySelector('.chat-sugs')?.remove(); user(m); inp.value = ''; inp.style.height = 'auto'; enviando = true; root.querySelector('#chatGo').disabled = true;
    const t = bot(''); t.querySelector('.chat-b').innerHTML = '<span class="ia-dots"><span></span><span></span><span></span></span>'; scroll();
    try { const r = await iaApi('chat/enviar', { mensaje: m }); t.querySelector('.chat-b').innerHTML = fmtTxt(r.texto) + tabla(r.tabla); }
    catch (x) { t.querySelector('.chat-b').innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle me-1"></i>${esc(x.message)}</span>`; }
    enviando = false; root.querySelector('#chatGo').disabled = false; scroll(); inp.focus();
  }
  async function limpiar() { try { await iaApi('chat/limpiar', {}); } catch (x) {} box.innerHTML = ''; vistos = false; cargar(); }

  if (document.readyState !== 'loading') build(); else document.addEventListener('DOMContentLoaded', build);
})();
