/* Configuración de empresa: vista previa en vivo */
(function () {
  const $ = id => document.getElementById(id);

  function fmt(n) {
    const dec = parseInt($('mDec').value, 10), ds = $('mSd').value, ts = $('mSm').value;
    let [i, d] = Math.abs(n).toFixed(dec).split('.');
    i = i.replace(/\B(?=(\d{3})+(?!\d))/g, ts);
    const s = (n < 0 ? '-' : '') + i + (dec > 0 ? ds + d : '');
    const sym = $('mSim').value || '';
    return $('mPos').value === 'despues' ? s + ' ' + sym : sym + ' ' + s;
  }
  function refresh() {
    $('pvMoney').textContent = fmt(1234567.891);
    $('pvMoney2').textContent = 'Pollo entero: ' + fmt(68);
    $('pvMoney3').textContent = 'Total del día: ' + fmt(15432.5);
    $('pvNombre').textContent = $('inNombre').value;
    $('pvRuc').textContent = 'RUC ' + $('inRuc').value;
    $('pvDir').textContent = $('inDir').value;
  }
  ['mSim', 'mPos', 'mDec', 'mSd', 'mSm', 'inNombre', 'inRuc', 'inDir'].forEach(id => { $(id).addEventListener('input', refresh); $(id).addEventListener('change', refresh); });

  // Evitar mismo separador
  function guard(ev) {
    if ($('mSd').value === $('mSm').value) {
      const other = ev.target.id === 'mSd' ? $('mSm') : $('mSd');
      other.value = ev.target.value === '.' ? ',' : '.';
      if (other.id === 'mSm' && other.value === '') other.value = ',';
    }
    refresh();
  }
  $('mSd').addEventListener('change', guard); $('mSm').addEventListener('change', guard);

  document.querySelectorAll('[data-preset]').forEach(b => b.addEventListener('click', () => {
    const [cod, sim, nom] = JSON.parse(b.dataset.preset);
    $('mCod').value = cod; $('mSim').value = sim; $('mNom').value = nom; refresh();
  }));

  // Color
  document.querySelectorAll('.swatch').forEach(s => s.addEventListener('click', () => {
    $('colorMain').value = s.dataset.color;
    document.querySelectorAll('.swatch').forEach(x => x.classList.toggle('active', x === s));
    document.documentElement.style.setProperty('--brand', s.dataset.color);
  }));
  $('colorMain').addEventListener('input', e => document.documentElement.style.setProperty('--brand', e.target.value));

  // Logo preview lateral
  document.querySelector('input[name=logo]').addEventListener('change', e => {
    if (e.target.files[0]) $('logoPrev2').innerHTML = '<img src="' + URL.createObjectURL(e.target.files[0]) + '" alt="">';
  });

  // Pestañas: recordar con hash
  document.querySelectorAll('#cfgTabs [data-tab]').forEach(b => b.addEventListener('shown.bs.tab', () => { $('tabInput').value = b.dataset.tab; history.replaceState(null, '', '#' + b.dataset.tab); }));
  const h = location.hash.replace('#', '');
  if (h) { const t = document.querySelector('#cfgTabs [data-tab="' + h + '"]'); if (t) bootstrap.Tab.getOrCreateInstance(t).show(); }

  refresh();
})();
