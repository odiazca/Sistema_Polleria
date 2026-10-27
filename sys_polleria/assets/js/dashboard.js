/* Dashboard: gráficos estadísticos (Chart.js) */
(function () {
  const css = getComputedStyle(document.documentElement);
  const BRAND = css.getPropertyValue('--brand').trim() || '#F97316';
  const PAL = [BRAND, '#3B82F6', '#10B981', '#8B5CF6', '#F59E0B', '#EC4899', '#14B8A6'];
  const TIPO = { mesa: 'Mesa', llevar: 'Para llevar', delivery: 'Delivery' };
  const MET = { efectivo: 'Efectivo', tarjeta: 'Tarjeta', yape: 'Yape', plin: 'Plin', transferencia: 'Transferencia' };

  Chart.defaults.font.family = "'Plus Jakarta Sans', system-ui, sans-serif";
  Chart.defaults.font.size = 12;
  Chart.defaults.color = '#64748B';
  Chart.defaults.plugins.legend.display = false;
  Chart.defaults.plugins.tooltip.backgroundColor = '#0F172A';
  Chart.defaults.plugins.tooltip.padding = 10;
  Chart.defaults.plugins.tooltip.cornerRadius = 10;
  Chart.defaults.plugins.tooltip.titleFont = { weight: '700' };

  const charts = {};
  const grid = { color: '#EEF1F7', drawBorder: false };

  function gradient(ctx, color) {
    const g = ctx.createLinearGradient(0, 0, 0, 300);
    g.addColorStop(0, color + '55'); g.addColorStop(1, color + '00');
    return g;
  }
  function legend(el, items) {
    document.getElementById(el).innerHTML = items.map((i, n) =>
      `<div class="d-flex justify-content-between py-1"><span><span class="legend-dot" style="background:${PAL[n % PAL.length]}"></span>${esc(i.l)}</span><b>${esc(i.v)}</b></div>`).join('');
  }

  function render(d) {
    // Ventas por día (área + barras de pedidos)
    const c1 = document.getElementById('chVentas');
    charts.v?.destroy();
    charts.v = new Chart(c1, {
      data: {
        labels: d.labels,
        datasets: [
          { type: 'line', label: 'Ventas', data: d.ventas, borderColor: BRAND, backgroundColor: gradient(c1.getContext('2d'), BRAND.length === 7 ? BRAND : '#F97316'), fill: true, tension: .35, borderWidth: 3, pointRadius: d.labels.length > 15 ? 0 : 4, pointBackgroundColor: '#fff', pointBorderColor: BRAND, pointBorderWidth: 2, yAxisID: 'y' },
          { type: 'bar', label: 'Pedidos', data: d.pedidos, backgroundColor: '#DBEAFE', borderRadius: 6, barPercentage: .5, yAxisID: 'y1' }
        ]
      },
      options: {
        responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
        plugins: { legend: { display: true, position: 'top', align: 'end', labels: { usePointStyle: true, boxWidth: 8 } }, tooltip: { callbacks: { label: c => c.dataset.label + ': ' + (c.dataset.yAxisID === 'y' ? fmtMoney(c.parsed.y) : c.parsed.y) } } },
        scales: { x: { grid: { display: false } }, y: { grid, beginAtZero: true, ticks: { callback: v => fmtNum(v, 0) } }, y1: { position: 'right', grid: { display: false }, beginAtZero: true, ticks: { precision: 0 } } }
      }
    });

    // Tipos
    charts.t?.destroy();
    const totT = d.tipos.reduce((a, b) => a + b.t, 0) || 1;
    charts.t = new Chart(document.getElementById('chTipos'), {
      type: 'doughnut',
      data: { labels: d.tipos.map(x => TIPO[x.k] || x.k), datasets: [{ data: d.tipos.map(x => x.t), backgroundColor: PAL, borderWidth: 4, borderColor: '#fff', hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + fmtMoney(c.parsed) } } } }
    });
    legend('lgTipos', d.tipos.map(x => ({ l: TIPO[x.k] || x.k, v: Math.round(x.t / totT * 100) + '% · ' + fmtMoney(x.t) })));

    // Top productos
    charts.p?.destroy();
    charts.p = new Chart(document.getElementById('chTop'), {
      type: 'bar',
      data: { labels: d.top.map(x => x.k.length > 22 ? x.k.slice(0, 21) + '…' : x.k), datasets: [{ data: d.top.map(x => x.q), backgroundColor: PAL.map(c => c + 'DD'), borderRadius: 8, barThickness: 18 }] },
      options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, scales: { x: { grid, beginAtZero: true, ticks: { precision: 0 } }, y: { grid: { display: false } } }, plugins: { tooltip: { callbacks: { label: c => ' ' + c.parsed.x + ' und · ' + fmtMoney(d.top[c.dataIndex].t) } } } }
    });

    // Horas
    charts.h?.destroy();
    const max = Math.max(...d.horas.v, 0);
    charts.h = new Chart(document.getElementById('chHoras'), {
      type: 'bar',
      data: { labels: d.horas.labels, datasets: [{ data: d.horas.v, backgroundColor: d.horas.v.map(v => v === max && max > 0 ? BRAND : BRAND + '55'), borderRadius: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, scales: { x: { grid: { display: false }, ticks: { maxRotation: 0, autoSkip: true, maxTicksLimit: 8 } }, y: { grid, beginAtZero: true, ticks: { callback: v => fmtNum(v, 0) } } }, plugins: { tooltip: { callbacks: { label: c => ' ' + fmtMoney(c.parsed.y) } } } }
    });

    // Métodos
    charts.m?.destroy();
    const totM = d.metodos.reduce((a, b) => a + b.t, 0) || 1;
    charts.m = new Chart(document.getElementById('chMetodos'), {
      type: 'doughnut',
      data: { labels: d.metodos.map(x => MET[x.k] || x.k), datasets: [{ data: d.metodos.map(x => x.t), backgroundColor: PAL, borderWidth: 4, borderColor: '#fff', hoverOffset: 6 }] },
      options: { responsive: true, maintainAspectRatio: false, cutout: '68%', plugins: { tooltip: { callbacks: { label: c => ' ' + c.label + ': ' + fmtMoney(c.parsed) } } } }
    });
    legend('lgMetodos', d.metodos.map(x => ({ l: MET[x.k] || x.k, v: Math.round(x.t / totM * 100) + '%' })));
  }

  render(window.DASH);
  document.getElementById('periodo')?.addEventListener('change', async e => {
    const r = await api('dashboard/charts&dias=' + e.target.value);
    if (r.ok) render(r);
  });
})();
