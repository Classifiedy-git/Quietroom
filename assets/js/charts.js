/* ==========================================================================
   Quietroom: tiny dependency-free SVG charts
   ========================================================================== */
(function () {
  'use strict';

  const NS = 'http://www.w3.org/2000/svg';

  const ESC = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' };
  function esc(s) {
    if (window.Quietroom && typeof window.Quietroom.escape === 'function') {
      return window.Quietroom.escape(s);
    }
    return String(s == null ? '' : s).replace(/[&<>"']/g, (c) => ESC[c]);
  }

  function el(name, attrs, text) {
    const node = document.createElementNS(NS, name);
    Object.keys(attrs || {}).forEach((k) => node.setAttribute(k, attrs[k]));
    if (text !== undefined) node.textContent = text;
    return node;
  }

  function niceMax(v) {
    if (v <= 5) return 5;
    if (v <= 10) return 10;
    if (v <= 20) return 20;
    if (v <= 50) return 50;
    if (v <= 100) return 100;
    return Math.ceil(v / 20) * 20;
  }

  /**
   * Line / area chart.
   * opts: { labels:[], series:[{name,color,data:[],fill?}], yMin, yMax, height, unit }
   */
  function line(target, opts) {
    const host = typeof target === 'string' ? document.querySelector(target) : target;
    if (!host) return;
    host.innerHTML = '';

    const labels = opts.labels || [];
    const series = opts.series || [];
    const W = 720, H = opts.height || 240;
    const padL = 34, padR = 14, padT = 14, padB = 30;

    let yMax = opts.yMax, yMin = opts.yMin;
    const allVals = series.reduce((acc, s) => acc.concat(s.data.filter((v) => v !== null && v !== undefined)), []);
    if (yMax === undefined) yMax = niceMax(Math.max.apply(null, allVals.concat([1])));
    if (yMin === undefined) yMin = 0;

    const svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'chart', role: 'img', 'aria-label': opts.aria || 'Chart' });
    const plotW = W - padL - padR, plotH = H - padT - padB;

    const yOf = (v) => padT + plotH - ((v - yMin) / Math.max(1, (yMax - yMin))) * plotH;
    const xOf = (i) => labels.length <= 1 ? padL + plotW / 2 : padL + (i / (labels.length - 1)) * plotW;

    // horizontal grid + y labels
    const ticks = 4;
    for (let t = 0; t <= ticks; t++) {
      const v = yMin + ((yMax - yMin) * t) / ticks;
      const y = yOf(v);
      svg.appendChild(el('line', { x1: padL, y1: y, x2: W - padR, y2: y, class: 'grid-line' }));
      svg.appendChild(el('text', { x: padL - 7, y: y + 3.5, 'text-anchor': 'end', class: 'axis-text' }, String(Math.round(v * 10) / 10)));
    }

    series.forEach((s) => {
      const pts = [];
      s.data.forEach((v, i) => { if (v !== null && v !== undefined) pts.push([xOf(i), yOf(v), i, v]); });
      if (!pts.length) return;

      if (s.fill) {
        const d = 'M ' + pts.map((p) => p[0] + ' ' + p[1]).join(' L ') +
          ' L ' + pts[pts.length - 1][0] + ' ' + (padT + plotH) +
          ' L ' + pts[0][0] + ' ' + (padT + plotH) + ' Z';
        svg.appendChild(el('path', { d: d, class: 'area', fill: s.color || 'var(--accent-fg)', 'fill-opacity': 0.14 }));
      }
      const d = 'M ' + pts.map((p) => p[0] + ' ' + p[1]).join(' L ');
      svg.appendChild(el('path', { d: d, class: 'line', stroke: s.color || 'var(--accent-fg)' }));

      pts.forEach((p) => {
        const dot = el('circle', { cx: p[0], cy: p[1], r: 3.4, class: 'dot', stroke: s.color || 'var(--accent-fg)' });
        dot.appendChild(el('title', {}, (opts.unit || '') + p[3] + ' · ' + (labels[p[2]] || '')));
        svg.appendChild(dot);
      });
    });

    // x labels (thinned)
    const every = Math.max(1, Math.ceil(labels.length / 7));
    labels.forEach((lbl, i) => {
      if (i % every !== 0 && i !== labels.length - 1) return;
      svg.appendChild(el('text', { x: xOf(i), y: H - 9, 'text-anchor': 'middle', class: 'axis-text' }, lbl));
    });

    host.appendChild(svg);

    if (series.length > 1) {
      const legend = document.createElement('div');
      legend.className = 'legend';
      series.forEach((s) => {
        const i = document.createElement('span');
        i.innerHTML = '<i style="background:' + (s.color || 'var(--accent-fg)') + '"></i>' + esc(s.name || '');
        legend.appendChild(i);
      });
      host.appendChild(legend);
    }
  }

  /** Bar chart. opts: { labels:[], data:[], colors:[], height, unit } */
  function bars(target, opts) {
    const host = typeof target === 'string' ? document.querySelector(target) : target;
    if (!host) return;
    host.innerHTML = '';

    const labels = opts.labels || [];
    const data = opts.data || [];
    const W = 720, H = opts.height || 220;
    const padL = 30, padR = 12, padT = 12, padB = 42;
    const svg = el('svg', { viewBox: '0 0 ' + W + ' ' + H, class: 'chart', role: 'img', 'aria-label': opts.aria || 'Chart' });
    const plotW = W - padL - padR, plotH = H - padT - padB;
    const max = niceMax(Math.max.apply(null, data.concat([1])));
    const slot = plotW / Math.max(1, data.length);
    const barW = Math.min(38, slot * 0.62);

    for (let t = 0; t <= 4; t++) {
      const v = (max * t) / 4;
      const y = padT + plotH - (v / max) * plotH;
      svg.appendChild(el('line', { x1: padL, y1: y, x2: W - padR, y2: y, class: 'grid-line' }));
      svg.appendChild(el('text', { x: padL - 6, y: y + 3.5, 'text-anchor': 'end', class: 'axis-text' }, String(Math.round(v * 10) / 10)));
    }

    data.forEach((v, i) => {
      const h = (v / max) * plotH;
      const x = padL + slot * i + (slot - barW) / 2;
      const y = padT + plotH - h;
      const r = el('rect', { x: x, y: y, width: barW, height: Math.max(1, h), class: 'bar', fill: (opts.colors && opts.colors[i]) || 'var(--accent-fg)' });
      r.appendChild(el('title', {}, (opts.unit || '') + v + ' · ' + (labels[i] || '')));
      svg.appendChild(r);

      if (labels[i]) {
        const t = el('text', { x: x + barW / 2, y: H - 22, 'text-anchor': 'end', class: 'axis-text' }, labels[i]);
        t.setAttribute('transform', 'rotate(-40 ' + (x + barW / 2) + ' ' + (H - 22) + ')');
        svg.appendChild(t);
      }
    });

    host.appendChild(svg);
  }

  /** Horizontal bars: good for trigger frequency. */
  function hbars(target, opts) {
    const host = typeof target === 'string' ? document.querySelector(target) : target;
    if (!host) return;
    host.innerHTML = '';
    const rows = opts.rows || [];
    if (!rows.length) {
      host.innerHTML = '<p class="muted small">Nothing to show yet.</p>';
      return;
    }
    const max = Math.max.apply(null, rows.map((r) => r.value).concat([1]));
    const wrap = document.createElement('div');
    wrap.className = 'stack-sm';
    rows.forEach((r) => {
      const row = document.createElement('div');
      const pct = (r.value / max) * 100;
      row.innerHTML =
        '<div class="row-between small"><span>' + esc(r.label) + '</span><span class="muted">' + r.value + (opts.unit || '') + '</span></div>' +
        '<div class="bar-track">' +
        '<div class="bar-fill" style="width:' + pct + '%;background:' + (r.color || 'var(--accent-fg)') + '"></div></div>';
      wrap.appendChild(row);
    });
    host.appendChild(wrap);
  }

  window.QuietroomCharts = { line: line, bars: bars, hbars: hbars };
})();
