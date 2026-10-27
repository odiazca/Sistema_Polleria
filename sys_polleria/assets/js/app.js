/* Pollería POS · utilidades globales */
(function () {
  'use strict';
  const A = window.APP || {};

  // ---- Formato de dinero (mismos separadores configurados en la empresa)
  window.fmtNum = function (n, dec) {
    dec = dec == null ? A.money.dec : dec;
    n = Number(n) || 0;
    const neg = n < 0; n = Math.abs(n);
    let [i, d] = n.toFixed(dec).split('.');
    i = i.replace(/\B(?=(\d{3})+(?!\d))/g, A.money.thSep);
    return (neg ? '-' : '') + i + (dec > 0 ? A.money.decSep + d : '');
  };
  window.fmtMoney = function (n) {
    const s = fmtNum(n);
    return A.money.pos === 'despues' ? s + ' ' + A.money.symbol : A.money.symbol + ' ' + s;
  };
  window.esc = function (s) { return String(s ?? '').replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c])); };

  // ---- Toast
  const Toast = Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 3500, timerProgressBar: true });
  window.toast = (icon, title) => Toast.fire({ icon, title });
  (A.flashes || []).forEach(f => toast({ danger: 'error', success: 'success', warning: 'warning', info: 'info' }[f[0]] || 'info', f[1]));

  // ---- Fetch JSON con CSRF
  window.api = async function (route, data, opts) {
    const url = A.base + '/index.php?r=' + route;
    const o = Object.assign({ method: data ? 'POST' : 'GET', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-Token': A.csrf } }, opts || {});
    if (data) { o.headers['Content-Type'] = 'application/json'; o.body = JSON.stringify(data); }
    const r = await fetch(url, o);
    let j; try { j = await r.json(); } catch (e) { j = { ok: false, msg: 'Respuesta inválida del servidor' }; }
    return j;
  };

  // ---- Sidebar
  const body = document.body;
  document.getElementById('sbToggle')?.addEventListener('click', () => {
    if (window.innerWidth > 991) {
      body.classList.toggle('sb-collapsed');
      try { localStorage.setItem('sbc', body.classList.contains('sb-collapsed') ? '1' : '0'); } catch (e) {}
    } else body.classList.toggle('sb-open');
  });
  document.getElementById('sbBackdrop')?.addEventListener('click', () => body.classList.remove('sb-open'));

  // ---- Reloj
  const clock = document.getElementById('clock');
  if (clock) { const t = () => clock.textContent = new Date().toLocaleString('es-PE', { weekday: 'short', day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }); t(); setInterval(t, 20000); }

  // ---- Confirmaciones
  document.addEventListener('submit', function (ev) {
    const f = ev.target;
    if (f.dataset.confirm && !f.dataset.ok) {
      ev.preventDefault();
      Swal.fire({
        title: f.dataset.title || '¿Está seguro?', text: f.dataset.confirm, icon: f.dataset.icon || 'warning',
        showCancelButton: true, confirmButtonText: f.dataset.btn || 'Sí, continuar', cancelButtonText: 'Cancelar', reverseButtons: true,
        confirmButtonColor: f.dataset.danger ? '#EF4444' : undefined
      }).then(r => { if (r.isConfirmed) { f.dataset.ok = '1'; f.requestSubmit ? f.requestSubmit() : f.submit(); } });
    }
  });

  // ---- Buscador de tablas
  document.querySelectorAll('[data-filter-table]').forEach(inp => {
    const tbl = document.querySelector(inp.dataset.filterTable);
    if (!tbl) return;
    inp.addEventListener('input', () => {
      const q = inp.value.trim().toLowerCase();
      tbl.querySelectorAll('tbody tr').forEach(tr => { tr.style.display = tr.textContent.toLowerCase().includes(q) ? '' : 'none'; });
    });
  });

  // ---- Mostrar/ocultar contraseña
  document.querySelectorAll('[data-pw-toggle]').forEach(b => b.addEventListener('click', () => {
    const i = document.querySelector(b.dataset.pwToggle); if (!i) return;
    i.type = i.type === 'password' ? 'text' : 'password';
    b.querySelector('i').className = 'bi ' + (i.type === 'password' ? 'bi-eye' : 'bi-eye-slash');
  }));

  // ---- Vista previa de imágenes
  document.querySelectorAll('input[type=file][data-preview]').forEach(inp => inp.addEventListener('change', () => {
    const t = document.querySelector(inp.dataset.preview); if (!t || !inp.files[0]) return;
    t.innerHTML = '<img src="' + URL.createObjectURL(inp.files[0]) + '" alt="">';
  }));
})();
