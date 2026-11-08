/* ==========================================================================
   Quietroom: core interactions
   ========================================================================== */
(function () {
  'use strict';

  const $  = (sel, root) => (root || document).querySelector(sel);
  const $$ = (sel, root) => Array.from((root || document).querySelectorAll(sel));

  /* ---------- toast / flash ---------- */
  function dismissFlash(el) {
    el.style.transition = 'opacity .2s ease, transform .2s ease';
    el.style.opacity = '0';
    el.style.transform = 'translateY(-6px)';
    setTimeout(() => el.remove(), 220);
  }
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('.flash-close');
    if (btn) dismissFlash(btn.closest('.flash'));
  });
  setTimeout(() => $$('.flash').forEach((f) => dismissFlash(f)), 7000);

  /* ---------- user menu ---------- */
  const userMenu = $('[data-usermenu]');
  if (userMenu) {
    const btn = $('.usermenu-btn', userMenu);
    btn.addEventListener('click', (e) => {
      e.stopPropagation();
      const open = userMenu.classList.toggle('is-open');
      btn.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
    document.addEventListener('click', (e) => {
      if (!userMenu.contains(e.target)) {
        userMenu.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        userMenu.classList.remove('is-open');
        btn.setAttribute('aria-expanded', 'false');
      }
    });
  }

  /* ---------- tabs ---------- */
  document.addEventListener('click', (e) => {
    const tabBtn = e.target.closest('[data-tab]');
    if (!tabBtn) return;
    const group = tabBtn.closest('[data-tabgroup]');
    if (!group) return;
    const target = tabBtn.getAttribute('data-tab');
    $$('[data-tab]', group).forEach((b) => b.classList.toggle('is-active', b === tabBtn));
    $$('[data-panel]', group).forEach((p) => {
      p.classList.toggle('hidden', p.getAttribute('data-panel') !== target);
    });
    try { localStorage.setItem('classy_tab_' + group.getAttribute('data-tabgroup'), target); } catch (err) {}
  });
  $$('[data-tabgroup]').forEach((group) => {
    let saved = null;
    try { saved = localStorage.getItem('classy_tab_' + group.getAttribute('data-tabgroup')); } catch (err) {}
    if (saved) {
      const btn = $('[data-tab="' + saved + '"]', group);
      if (btn) btn.click();
    }
  });

  /* ---------- mood scale ---------- */
  document.addEventListener('click', (e) => {
    const opt = e.target.closest('.mood-opt');
    if (!opt) return;
    const wrap = opt.closest('.mood-scale');
    if (!wrap) return;
    const value = opt.getAttribute('data-value');
    $$('.mood-opt', wrap).forEach((o) => {
      const on = o === opt;
      o.classList.toggle('is-active', on);
      o.setAttribute('aria-checked', on ? 'true' : 'false');
    });
    const input = wrap.getAttribute('data-input');
    if (input && document.getElementById(input)) {
      document.getElementById(input).value = value;
      document.getElementById(input).dispatchEvent(new Event('change', { bubbles: true }));
    }
    const readout = wrap.getAttribute('data-readout');
    if (readout && document.getElementById(readout)) {
      const box = document.getElementById(readout);
      const face = box.querySelector('[data-mood-face] use');
      const iconName = opt.getAttribute('data-icon');
      if (face && iconName) face.setAttribute('href', '#ic-' + iconName);
      const label = box.querySelector('[data-mood-label]');
      if (label) label.textContent = opt.getAttribute('data-label') || '';
    }
  });

  /* ---------- range sliders with live readout ---------- */
  function bindRange(input) {
    const out = document.getElementById(input.getAttribute('data-output'));
    const min = parseInt(input.min || '0', 10);
    const max = parseInt(input.max || '10', 10);
    function paint() {
      const pct = ((input.value - min) / (max - min)) * 100;
      input.style.background = 'transparent';
      if (out) {
        out.textContent = input.getAttribute('data-prefix')
          ? input.getAttribute('data-prefix') + input.value + input.getAttribute('data-suffix', '')
          : input.value + input.getAttribute('data-suffix', '');
      }
      const track = input.parentElement && input.parentElement.querySelector('.range-track');
      if (track) track.style.setProperty('--pct', pct + '%');
    }
    input.addEventListener('input', paint);
    paint();
  }
  $$('input[type=range][data-output]').forEach(bindRange);

  /* ---------- password strength ---------- */
  const pw = $('[data-password]');
  if (pw) {
    const meter = $('[data-strength]');
    pw.addEventListener('input', () => {
      const v = pw.value;
      let score = 0;
      if (v.length >= 8) score++;
      if (v.length >= 12) score++;
      if (/[A-Z]/.test(v) && /[a-z]/.test(v)) score++;
      if (/\d/.test(v)) score++;
      if (/[^A-Za-z0-9]/.test(v)) score++;
      if (!meter) return;
      meter.className = 'pwd-strength' + (score >= 4 ? ' s3' : score >= 3 ? ' s2' : '');
      meter.querySelector('i').style.width = Math.min(100, (score / 5) * 100) + '%';
    });
  }

  /* ---------- copy to clipboard ---------- */
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('[data-copy]');
    if (!btn) return;
    const sel = btn.getAttribute('data-copy');
    const src = sel ? document.querySelector(sel) : null;
    const text = src ? (src.value !== undefined ? src.value : src.textContent) : '';
    try {
      await navigator.clipboard.writeText(text);
      const old = btn.innerHTML;
      btn.innerHTML = Quietroom.labelled('check', 'Copied');
      setTimeout(() => { btn.innerHTML = old; }, 1600);
    } catch (err) {
      const ta = document.createElement('textarea');
      ta.value = text; document.body.appendChild(ta); ta.select();
      try { document.execCommand('copy'); btn.innerHTML = Quietroom.labelled('check', 'Copied'); } catch (e2) {}
      document.body.removeChild(ta);
      setTimeout(() => { btn.innerHTML = Quietroom.labelled('check', 'Copied'); }, 1600);
    }
  });

  /* ---------- confirm ---------- */
  document.addEventListener('submit', (e) => {
    const form = e.target;
    const msg = form.getAttribute('data-confirm');
    if (msg && !window.confirm(msg)) {
      e.preventDefault();
      e.stopPropagation();
    }
  });

  /* ---------- json fetch helper ---------- */
  window.Quietroom = {
    /**
     * Read a JSON response. A server error page is HTML, and letting the
     * browser parse it throws "Unexpected token '<'", which tells the user
     * nothing. Say what actually happened instead.
     */
    async json(res) {
      const body = await res.text();
      try {
        return JSON.parse(body);
      } catch (e) {
        throw new Error(res.ok
          ? 'The server sent an unexpected response.'
          : 'The server could not complete that request.');
      }
    },
    /**
     * Build a request body that every host accepts.
     *
     * Some shared hosts sit behind a WAF that answers *any* POST whose
     * Content-Type is multipart/form-data with a 403 before the request
     * ever reaches PHP. The server reads $_POST, and PHP fills $_POST from
     * application/x-www-form-urlencoded just as happily, so every call
     * goes out urlencoded. Array values keep the "name[]" shape that PHP
     * turns back into an array.
     */
    body(data) {
      const body = new URLSearchParams();
      Object.keys(data || {}).forEach((k) => {
        const v = data[k];
        if (v === null || v === undefined) return;
        if (Array.isArray(v)) v.forEach((item) => body.append(k + '[]', item));
        else body.append(k, v);
      });
      return body;
    },
    /**
     * The same, for a form element. FormData is only used here to read
     * the fields; the body that goes out is still urlencoded.
     */
    formBody(form) {
      const body = new URLSearchParams();
      new FormData(form).forEach((v, k) => {
        if (typeof v === 'string') body.append(k, v);
      });
      return body;
    },
    async post(url, data) {
      const body = this.body(data);
      if (!body.has('_token')) body.set('_token', this.csrf());
      const res = await fetch(url, {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8',
          'X-CSRF-TOKEN': this.csrf()
        },
        body: body
      });
      const json = await Quietroom.json(res);
      if (!res.ok || json.ok === false) throw new Error(json.error || 'Request failed.');
      return json;
    },
    get(url) {
      return fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
        .then((r) => r.json());
    },
    csrf() {
      const m = document.querySelector('meta[name="csrf-token"]');
      return m ? m.getAttribute('content') : (window.CLASSY_CSRF || '');
    },
    escape(s) {
      const d = document.createElement('div');
      d.textContent = s == null ? '' : String(s);
      return d.innerHTML;
    },
    /** Markup for one of the sprite icons, for use from JavaScript. */
    icon(name, cls) {
      return '<svg class="icon ' + (cls || '') + '" aria-hidden="true" focusable="false">'
        + '<use href="#ic-' + name + '"></use></svg>';
    },
    /** A button label that keeps its icon: icon + text in one innerHTML. */
    labelled(name, text, cls) {
      return this.icon(name, cls) + '<span>' + this.escape(text) + '</span>';
    }
  };

  /* ---------- auto-submit on select change ---------- */
  document.addEventListener('change', (e) => {
    const el = e.target;
    if (el.matches('[data-autosubmit]')) {
      const f = el.closest('form');
      if (f) f.submit();
    }
  });

  /* ---------- print ---------- */
  document.addEventListener('click', (e) => {
    const el = e.target.closest('[data-print]');
    if (el) window.print();
  });

  /* ---------- keyboard shortcuts ---------- */
  document.addEventListener('keydown', (e) => {
    if (e.target.matches('input, textarea')) return;
    if (e.key === '/') {
      const s = $('[data-search]');
      if (s) { e.preventDefault(); s.focus(); }
    }
  });

  /* ---------- auto grow textareas ---------- */
  function autoGrow(el) { el.style.height = 'auto'; el.style.height = Math.min(el.scrollHeight, 320) + 'px'; }
  $$('textarea[data-autogrow]').forEach((t) => {
    autoGrow(t);
    t.addEventListener('input', () => autoGrow(t));
  });

  /* ---------- avatar upload ---------- */
  /**
   * Shrink a picture to fit inside a square and return it as a JPEG data
   * URL. The server does the same thing again, but doing it here keeps
   * the request small enough to travel as base64 in a normal POST.
   */
  function shrinkImage(file, maxDim, quality) {
    return new Promise((resolve, reject) => {
      const url = URL.createObjectURL(file);
      const img = new Image();
      img.onload = () => {
        URL.revokeObjectURL(url);
        const scale = Math.min(1, maxDim / Math.max(img.width || 1, img.height || 1));
        const w = Math.max(1, Math.round((img.width || 1) * scale));
        const h = Math.max(1, Math.round((img.height || 1) * scale));
        const canvas = document.createElement('canvas');
        canvas.width = w;
        canvas.height = h;
        const ctx = canvas.getContext('2d');
        if (!ctx) { reject(new Error('This browser cannot prepare the picture.')); return; }
        ctx.fillStyle = '#ffffff';
        ctx.fillRect(0, 0, w, h);
        ctx.drawImage(img, 0, 0, w, h);
        resolve(canvas.toDataURL('image/jpeg', quality));
      };
      img.onerror = () => {
        URL.revokeObjectURL(url);
        reject(new Error('That image could not be read. Please try a different one.'));
      };
      img.src = url;
    });
  }

  const avatarInput = $('#avatar');
  const avatarPreview = $('#avatar-preview');
  const avatarBtn = $('.avatar-btn');
  const avatarStatus = $('#avatar-status');
  if (avatarInput && avatarPreview) {
    // The <label for="avatar"> opens the picker natively; no JS needed.
    avatarInput.addEventListener('change', async function () {
      const file = this.files && this.files[0];
      if (!file) return;
      if (!/^image\/(jpeg|png|gif|webp)$/i.test(file.type || '')) {
        if (avatarStatus) {
          avatarStatus.className = 'small mt-2 text-danger';
          avatarStatus.textContent = 'Please choose a JPEG, PNG, GIF or WEBP image.';
        }
        return;
      }

      // Show the picture straight away, then send it.
      const url = URL.createObjectURL(file);
      const preview = document.createElement('img');
      preview.className = 'avatar-preview';
      preview.id = 'avatar-preview';
      preview.src = url;
      preview.alt = 'Your profile picture';
      preview.onload = () => URL.revokeObjectURL(url);
      avatarPreview.parentNode.replaceChild(preview, avatarPreview);

      if (avatarStatus) {
        avatarStatus.className = 'small mt-2 muted';
        avatarStatus.textContent = 'Saving your picture…';
      }

      try {
        const image = await shrinkImage(file, 200, 0.85);
        const json = await window.Quietroom.post((window.CLASSY_API_BASE || '/') + 'api/avatar', { image: image });
        // Show the picture we just sent so it is visible straight away,
        // even if the host is slow to serve the saved file.
        preview.src = image;
        if (avatarStatus) {
          avatarStatus.className = 'small mt-2 text-good';
          avatarStatus.textContent = 'Your profile picture has been updated.';
        }
      } catch (err) {
        if (avatarStatus) {
          avatarStatus.className = 'small mt-2 text-danger';
          avatarStatus.textContent = err && err.message ? err.message : 'The picture could not be saved.';
        }
      }
    });
  }
})();
