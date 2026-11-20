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
    async function doSummary(force) {
      btn.disabled = true;
      btn.innerHTML = Quietroom.labelled('sparkle', 'Reading your week…');
      out.classList.remove('hidden');
      out.innerHTML = '<p class="muted small">Looking over your entries…</p>';
      try {
        const fd = new FormData();
        fd.append('days', btn.getAttribute('data-days') || '7');
        fd.append('_token', Quietroom.csrf());
        if (force) fd.append('force', '1');
        const res = await fetch(base + 'api/insights/summary', {
          method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not generate a summary.');
        const cachedNote = json.cached ? 'Previously generated from the same data.' : 'Also saved to your journal.';
        out.innerHTML = '<div class="entry-reflection"><div class="pre-wrap">' +
          Quietroom.escape(json.text) + '</div></div>' +
          '<p class="tiny faint mt-2">' + cachedNote + '</p>';
        const pdfBtn = document.getElementById('pdf-btn');
        if (pdfBtn) pdfBtn.disabled = false;
      } catch (err) {
        out.innerHTML = '<div class="notice notice-warn">' + Quietroom.escape(err.message) + '</div>';
      }
      btn.disabled = false;
      btn.innerHTML = Quietroom.labelled('sparkle', 'Write my summary');
    }
    btn.addEventListener('click', () => doSummary(false));
  }

  const insightsBtn = document.getElementById('insights-btn');
  if (insightsBtn) {
    const insightsOut = document.getElementById('insights-out');
    insightsBtn.addEventListener('click', async () => {
      insightsBtn.disabled = true;
      insightsBtn.innerHTML = Quietroom.labelled('sparkle', 'Reading your patterns…');
      insightsOut.classList.remove('hidden');
      insightsOut.innerHTML = '<p class="muted small">Looking over your check-ins…</p>';
      try {
        const body = Quietroom.body({ days: insightsBtn.getAttribute('data-days') || '14', _token: Quietroom.csrf() });
        const res = await fetch(base + 'api/mood-insights', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: body
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not generate insights.');
        insightsOut.innerHTML = '<div class="entry-reflection"><div class="pre-wrap">' +
          Quietroom.escape(json.text) + '</div></div>';
      } catch (err) {
        insightsOut.innerHTML = '<div class="notice notice-warn">' + Quietroom.escape(err.message) + '</div>';
      }
      insightsBtn.disabled = false;
      insightsBtn.innerHTML = Quietroom.labelled('sparkle', 'Get insights');
    });
  }

  // Enable PDF button if summary already rendered
  (function() {
    const out = document.getElementById('summary-out');
    const pdfBtn = document.getElementById('pdf-btn');
    if (out && pdfBtn && out.querySelector('.pre-wrap')) {
      pdfBtn.disabled = false;
    }
  })();

  /* ---------- PDF export of weekly summary ---------- */
  const pdfBtn = document.getElementById('pdf-btn');
  if (pdfBtn) {
    pdfBtn.addEventListener('click', async () => {
      const out = document.getElementById('summary-out');
      if (!out) return;
      const textEl = out.querySelector('.pre-wrap');
      const text = textEl ? textEl.textContent.trim() : '';
      if (!text) return;

      pdfBtn.disabled = true;
      pdfBtn.innerHTML = Quietroom.labelled('download', 'Generating…');

      try {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a4' });
        const pageW = doc.internal.pageSize.getWidth();
        const pageH = doc.internal.pageSize.getHeight();
        const margin = 20;
        const contentW = pageW - margin * 2;

        // Logo: convert SVG to PNG via canvas
        let logoData = null;
        try {
          const svgText = await fetch((window.CLASSY_API_BASE || '/') + 'assets/img/logo.svg').then(r => r.text());
          const svgBlob = new Blob([svgText], { type: 'image/svg+xml;charset=utf-8' });
          const url = URL.createObjectURL(svgBlob);
          const img = new Image();
          await new Promise((resolve, reject) => {
            img.onload = resolve;
            img.onerror = reject;
            img.src = url;
          });
          const canvas = document.createElement('canvas');
          canvas.width = 64; canvas.height = 64;
          const ctx = canvas.getContext('2d');
          ctx.drawImage(img, 0, 0, 64, 64);
          logoData = canvas.toDataURL('image/png');
          URL.revokeObjectURL(url);
        } catch (e) { logoData = null; }

        // Header
        let y = margin;
        if (logoData) {
          doc.addImage(logoData, 'PNG', margin, y, 12, 12);
        }
        doc.setFont('helvetica', 'bold');
        doc.setFontSize(18);
        doc.setTextColor(27, 125, 135);
        doc.text('Quietroom', margin + (logoData ? 16 : 0), y + 8);
        doc.setFontSize(11);
        doc.setTextColor(100, 100, 100);
        doc.text('Weekly Summary Report', margin + (logoData ? 16 : 0), y + 14);
        y += 20;

        // Horizontal rule
        doc.setDrawColor(200, 200, 200);
        doc.setLineWidth(0.3);
        doc.line(margin, y, pageW - margin, y);
        y += 8;

        // Metadata
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(10);
        doc.setTextColor(80, 80, 80);
        const meta = [
          ['Prepared for', (window.CLASSY_USER_NAME || 'You')],
          ['Date range', (document.getElementById('summary-btn') ? document.getElementById('summary-btn').getAttribute('data-since') : '') + ' to ' + (document.getElementById('summary-btn') ? document.getElementById('summary-btn').getAttribute('data-until') : new Date().toLocaleDateString())],
          ['Generated', new Date().toLocaleDateString('en-AU', { day: 'numeric', month: 'long', year: 'numeric' })]
        ];
        meta.forEach(([label, value]) => {
          doc.setFont('helvetica', 'bold');
          doc.text(label + ':', margin, y);
          doc.setFont('helvetica', 'normal');
          doc.text(value, margin + 32, y);
          y += 6;
        });
        y += 4;

        // Body
        doc.setFont('helvetica', 'normal');
        doc.setFontSize(11);
        doc.setTextColor(30, 30, 30);
        const lines = doc.splitTextToSize(text, contentW);
        const lineHeight = 6;
        lines.forEach((line) => {
          if (y + lineHeight > pageH - margin - 12) {
            doc.addPage();
            y = margin;
          }
          doc.text(line, margin, y);
          y += lineHeight;
        });

        // Footer on all pages
        const totalPages = doc.internal.getNumberOfPages();
        for (let i = 1; i <= totalPages; i++) {
          doc.setPage(i);
          doc.setFontSize(8);
          doc.setTextColor(150, 150, 150);
          doc.text('Generated by Quietroom · quietroom.byethost7.com', margin, pageH - 10);
          doc.text('Page ' + i + ' of ' + totalPages, pageW - margin, pageH - 10, { align: 'right' });
        }

        const dateStr = new Date().toISOString().slice(0, 10);
        doc.save('quietroom-summary-' + dateStr + '.pdf');
      } catch (err) {
        alert('Could not generate PDF: ' + (err && err.message ? err.message : 'unknown error'));
      }

      pdfBtn.disabled = false;
      pdfBtn.innerHTML = Quietroom.labelled('download', 'Save as PDF');
    });
  }

  // Enable PDF button when summary is generated
  const origSummaryHandler = null;
})();
