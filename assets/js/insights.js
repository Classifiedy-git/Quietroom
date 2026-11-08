/* Quietroom: insights charts */
(function () {
  'use strict';
  const cfg = window.CLASSY_INSIGHTS || {};
  const base = window.CLASSY_API_BASE || '/';

  const main = document.getElementById('chart-main');
  if (main && window.QuietroomCharts) {
    QuietroomCharts.line(main, {
      labels: cfg.labels || [],
      series: [
        { name: 'Mood', data: cfg.mood || [], color: 'var(--accent-fg)', fill: true },
        { name: 'Anxiety', data: cfg.anxiety || [], color: 'var(--coral-500)' },
        { name: 'Energy', data: cfg.energy || [], color: 'var(--lav-500)' }
      ],
      yMin: 0, yMax: 10, height: 250,
      aria: 'Mood, anxiety and energy over time'
    });
  }

  const sleep = document.getElementById('chart-sleep');
  if (sleep && window.QuietroomCharts) {
    QuietroomCharts.line(sleep, {
      labels: cfg.labels || [],
      series: [{ name: 'Hours of sleep', data: cfg.sleep || [], color: 'var(--blue-500)', fill: true }],
      yMin: 0, yMax: 12, height: 200,
      unit: 'h',
      aria: 'Hours of sleep over time'
    });
  }

  const trig = document.getElementById('chart-triggers');
  if (trig && window.QuietroomCharts) {
    const rows = Object.keys(cfg.triggers || {}).map((k) => ({ label: k, value: cfg.triggers[k] }));
    QuietroomCharts.hbars(trig, { rows: rows, unit: '' });
  }

  const ex = document.getElementById('chart-exercises');
  if (ex && window.QuietroomCharts) {
    if ((cfg.exData || []).length) {
      QuietroomCharts.bars(ex, {
        labels: cfg.exLabels || [],
        data: cfg.exData || [],
        height: 220,
        aria: 'How often each exercise was used'
      });
    } else {
      ex.innerHTML = '<p class="muted small">No exercises logged in this window yet. ' +
        'Try one from the <a href="' + base + 'tools">toolkit</a>.</p>';
    }
  }

  const btn = document.getElementById('summary-btn');
  if (btn) {
    const out = document.getElementById('summary-out');
    btn.addEventListener('click', async () => {
      btn.disabled = true;
      btn.innerHTML = Quietroom.labelled('sparkle', 'Reading your week…');
      out.classList.remove('hidden');
      out.innerHTML = '<p class="muted small">Looking over your entries…</p>';
      try {
        const fd = new FormData();
        fd.append('days', btn.getAttribute('data-days') || '7');
        fd.append('_token', Quietroom.csrf());
        const res = await fetch(base + 'api/insights/summary', {
          method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not generate a summary.');
        out.innerHTML = '<div class="entry-reflection"><div class="pre-wrap">' +
          Quietroom.escape(json.text) + '</div></div>' +
          '<p class="tiny faint mt-2">Also saved to your journal.</p>';
      } catch (err) {
        out.innerHTML = '<div class="notice notice-warn">' + Quietroom.escape(err.message) + '</div>';
      }
      btn.disabled = false;
      btn.innerHTML = Quietroom.labelled('sparkle', 'Write my summary');
    });
  }
})();
