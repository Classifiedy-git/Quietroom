/* Quietroom: check-in chart */
(function () {
  'use strict';
  const host = document.getElementById('checkin-chart');
  if (!host || !window.QuietroomCharts) return;
  const cfg = window.CLASSY_CHECKIN_CHART || {};
  QuietroomCharts.line(host, {
    labels: cfg.labels || [],
    series: [
      { name: 'Mood', data: cfg.mood || [], color: 'var(--accent-fg)', fill: true },
      { name: 'Anxiety', data: cfg.anxiety || [], color: 'var(--coral-500)' },
      { name: 'Energy', data: cfg.energy || [], color: 'var(--lav-500)' }
    ],
    yMin: 0, yMax: 10, height: 200,
    aria: 'Mood, anxiety and energy over the last 14 days'
  });
})();
