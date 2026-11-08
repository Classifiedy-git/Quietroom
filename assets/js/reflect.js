/* Quietroom: AI reflection on a journal entry */
(function () {
  'use strict';
  const base = window.CLASSY_API_BASE || '/';
  const btn = document.getElementById('reflect-btn');
  if (!btn) return;

  btn.addEventListener('click', async () => {
    const id = btn.getAttribute('data-entry');
    btn.disabled = true;
    btn.innerHTML = Quietroom.labelled('sparkle', 'Reflecting…');

    let holder = document.getElementById('ai-reflection');
    if (!holder) {
      holder = document.createElement('div');
      holder.id = 'ai-reflection';
      holder.className = 'entry-reflection';
      btn.parentElement.parentElement.insertBefore(holder, btn.parentElement);
    }
    holder.innerHTML = '<span class="muted small">Reading what you wrote…</span>';

    try {
      const body = Quietroom.body({ entry_id: id, _token: Quietroom.csrf() });
      const res = await fetch(base + 'api/journal/ai', {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body: body
      });
      const json = await Quietroom.json(res);
      if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not generate a reflection.');
      holder.innerHTML = '<div class="row-between mb-1"><strong class="small">Reflection</strong>'
        + '<span class="tiny faint">AI-generated · not clinical advice</span></div>'
        + '<div class="pre-wrap">' + Quietroom.escape(json.text) + '</div>';
    } catch (err) {
      holder.innerHTML = '<span class="text-danger">' + Quietroom.escape(err.message) + '</span>';
    }
    btn.disabled = false;
    btn.innerHTML = Quietroom.labelled('sparkle', 'Reflect on this entry');
  });
})();
