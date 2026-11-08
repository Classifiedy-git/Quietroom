/* Quietroom: tool page interactions */
(function () {
  'use strict';

  const base = window.CLASSY_API_BASE || '/';
  const key = document.body.getAttribute('data-tool-key') || '';

  /* ---- log exercise completion ---- */
  const player = document.querySelector('[data-player]');
  if (player) {
    player.addEventListener('exercise:complete', async (e) => {
      try {
        await Quietroom.post(base + 'api/exercise', {
          exercise_key: player.getAttribute('data-exercise') || key,
          duration_seconds: e.detail.seconds,
          completed: 1
        });
      } catch (err) { /* logging is best-effort */ }
    });
  }

  /* ---- thought record ---- */
  const thoughtForm = document.querySelector('[data-thought-form]');
  if (thoughtForm) {
    const result = document.getElementById('thought-result');

    thoughtForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = thoughtForm.querySelector('button[type=submit]');
      btn.disabled = true; btn.innerHTML = Quietroom.labelled('save', 'Saving…');
      const fd = new FormData(thoughtForm);
      fd.append('_token', Quietroom.csrf());
      try {
        const res = await fetch(base + 'api/thought', {
          method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not save.');
        btn.innerHTML = Quietroom.labelled('check', 'Saved');
        setTimeout(() => { btn.innerHTML = Quietroom.labelled('save', 'Save thought record'); }, 1800);
      } catch (err) {
        alert(err.message);
        btn.disabled = false; btn.innerHTML = Quietroom.labelled('save', 'Save thought record');
      }
    });

    const coachBtn = document.getElementById('coach-btn');
    if (coachBtn) {
      coachBtn.addEventListener('click', async () => {
        const fd = new FormData(thoughtForm);
        fd.append('_token', Quietroom.csrf());
        coachBtn.disabled = true; coachBtn.innerHTML = Quietroom.labelled('sparkle', 'Thinking…');
        result.classList.remove('hidden');
        result.innerHTML = '<p class="muted small">Looking at this with you…</p>';
        try {
          const res = await fetch(base + 'api/thought/ai', {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd
          });
          const json = await Quietroom.json(res);
          if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not get a reflection.');
          result.innerHTML = '<div class="entry-reflection"><div class="pre-wrap">' +
            Quietroom.escape(json.text) + '</div></div>';
        } catch (err) {
          result.innerHTML = '<div class="notice notice-warn">' + Quietroom.escape(err.message) + '</div>';
        }
        coachBtn.disabled = false; coachBtn.innerHTML = Quietroom.labelled('sparkle', 'Have the AI coach me');
      });
    }
  }

  /* ---- behavioural activation ---- */
  const behaviourForm = document.querySelector('[data-behaviour-form]');
  if (behaviourForm) {
    const out = document.getElementById('behaviour-result');
    behaviourForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const fd = new FormData(behaviourForm);
      fd.append('_token', Quietroom.csrf());
      try {
        const res = await fetch(base + 'api/behavior', {
          method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not add it.');
        out.innerHTML = '<span class="text-good">' + Quietroom.labelled('check', 'Added to your plan.') + '</span>';
        behaviourForm.reset();
      } catch (err) {
        out.innerHTML = '<span class="text-danger">' + Quietroom.escape(err.message) + '</span>';
      }
    });
  }

  /* ---- balanced reframe ---- */
  const reframeForm = document.querySelector('[data-reframe-form]');
  if (reframeForm) {
    const out = document.getElementById('reframe-result');
    reframeForm.addEventListener('submit', async (e) => {
      e.preventDefault();
      const btn = reframeForm.querySelector('button[type=submit]');
      btn.disabled = true; btn.innerHTML = Quietroom.labelled('sparkle', 'Working…');
      out.classList.remove('hidden');
      out.innerHTML = '<p class="muted small">Looking at this with you…</p>';
      const fd = new FormData(reframeForm);
      fd.append('_token', Quietroom.csrf());
      try {
        const res = await fetch(base + 'api/reframe', {
          method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, body: fd
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not get a reframe.');
        out.innerHTML = '<div class="entry-reflection"><div class="pre-wrap">' +
          Quietroom.escape(json.text) + '</div></div>';
      } catch (err) {
        out.innerHTML = '<div class="notice notice-warn">' + Quietroom.escape(err.message) + '</div>';
      }
      btn.disabled = false; btn.innerHTML = Quietroom.labelled('sparkle', 'Get a balanced reframe');
    });
  }
})();
