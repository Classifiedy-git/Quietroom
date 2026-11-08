/* Quietroom: dashboard widgets */
(function () {
  'use strict';

  const chart = document.getElementById('dash-chart');
  if (chart && window.QuietroomCharts) {
    const cfg = window.CLASSY_DASH_CHART || {};
    QuietroomCharts.line(chart, {
      labels: cfg.labels || [],
      series: [
        { name: 'Mood', data: cfg.mood || [], color: 'var(--accent-fg)', fill: true },
        { name: 'Anxiety', data: cfg.anxiety || [], color: 'var(--coral-500)' }
      ],
      yMin: 0,
      yMax: 10,
      height: 190,
      aria: 'Mood and anxiety over the last 14 days'
    });
  }

  const list = document.getElementById('plan-list');
  if (list) {
    list.addEventListener('click', async (e) => {
      const btn = e.target.closest('[data-behaviour-done]');
      if (!btn) return;
      const id = btn.getAttribute('data-behaviour-done');
      const row = btn.closest('[data-behaviour]');
      try {
        await Quietroom.post(window.CLASSY_API_BASE + 'api/behavior', { id: id, action: 'complete' });
        btn.innerHTML = Quietroom.icon('check', 'icon-sm');
        btn.classList.remove('btn-ghost');
        btn.classList.add('btn-soft');
        row.style.opacity = '.6';
      } catch (err) {
        alert(err.message);
      }
    });
  }
})();
