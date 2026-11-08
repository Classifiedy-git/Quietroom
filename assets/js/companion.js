/* ==========================================================================
   Quietroom: AI companion chat (streaming)
   ========================================================================== */
(function () {
  'use strict';

  const log = document.querySelector('[data-chat-log]');
  if (!log) return;

  const form = document.querySelector('[data-chat-form]');
  const input = document.querySelector('[data-chat-input]');
  const sendBtn = document.querySelector('[data-chat-send]');
  const stopBtn = document.querySelector('[data-chat-stop]');
  const banner = document.querySelector('[data-crisis-banner]');
  const suggest = document.querySelector('[data-suggestions]');
  let busy = false;
  let controller = null;

  function esc(s) { return Quietroom.escape(s); }

  function scroll() { log.scrollTop = log.scrollHeight; }

  function bubble(role, html, extraClass) {
    const wrap = document.createElement('div');
    wrap.className = 'msg msg-' + role + (extraClass ? ' ' + extraClass : '');
    wrap.innerHTML = html;
    log.appendChild(wrap);
    scroll();
    return wrap;
  }

  function typingBubble() {
    const b = bubble('ai', '<span class="typing"><i></i><i></i><i></i></span>');
    return b;
  }

  function showCrisis(level) {
    if (!banner) return;
    const copy = {
      critical: 'It sounds like you may be in immediate danger. Please contact emergency services or a crisis line right now, and stay with someone you trust.',
      high: 'What you are describing sounds really heavy. Please talk to a human today. The helplines below are free and confidential.',
      elevated: 'You seem to be going through a lot. A few things below can help in the next few minutes.'
    };
    banner.querySelector('[data-crisis-text]').textContent = copy[level] || copy.elevated;
    banner.classList.remove('hidden');
    banner.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
  }

  async function send(text) {
    if (busy) return;
    busy = true;
    text = (text || '').trim();
    if (!text) return;

    bubble('user', esc(text));
    if (input) { input.value = ''; input.style.height = 'auto'; }
    if (suggest) suggest.classList.add('hidden');
    const thinking = typingBubble();
    if (sendBtn) sendBtn.disabled = true;
    if (stopBtn) stopBtn.classList.remove('hidden');

    const body = Quietroom.body({ message: text, _token: Quietroom.csrf() });

    let assistant = null;
    let buffer = '';
    let finalText = '';
    let errored = false;

    try {
      const res = await fetch(form.getAttribute('action'), {
        method: 'POST',
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json',
          'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8'
        },
        body: body,
        signal: controller ? controller.signal : undefined
      });

      const ct = res.headers.get('content-type') || '';

      if (!res.ok) {
        const txt = await res.text();
        let msg = 'Could not reach the companion.';
        try { msg = JSON.parse(txt).error || msg; } catch (e) {}
        throw new Error(msg);
      }

      // Hosts that cannot stream (or anti-bot proxies) return the whole reply as one JSON object.
      if (!ct.includes('text/event-stream')) {
        const txt = await res.text();
        let parsed = null;
        try { parsed = JSON.parse(txt); } catch (e) { parsed = null; }
        if (parsed && typeof parsed === 'object') {
          thinking.remove();
          if (!parsed.ok) {
            bubble('ai', '<strong>' + esc(parsed.error || 'Something went wrong.') + '</strong><br><span class="small muted">Please try again in a moment.</span>', 'is-error');
          } else {
            assistant = bubble('ai', esc(parsed.text || ''));
            if (parsed.crisis && parsed.crisis !== 'none') showCrisis(parsed.crisis);
            if (parsed.outCrisis && parsed.outCrisis !== 'none') showCrisis(parsed.outCrisis);
          }
          busy = false;
          if (sendBtn) sendBtn.disabled = false;
          if (stopBtn) stopBtn.classList.add('hidden');
          if (input) input.focus();
          return;
        }
      }

      // streaming path (SSE)
      const reader = res.body.getReader();
        const decoder = new TextDecoder();
        let done_ = false;
        let finished = false;

        while (!done_) {
          const { value, done } = await reader.read();
          if (done) break;
          const chunk = decoder.decode(value, { stream: true });
          buffer += chunk;
          const parts = buffer.split(/\r?\n\r?\n/);
          buffer = parts.pop();
          for (const part of parts) {
            const line = part.trim();
            if (!line || line.indexOf('data:') !== 0) continue;
            const payload = line.slice(5).trim();
            if (!payload) continue;
            let evt;
            try { evt = JSON.parse(payload); } catch (e) { continue; }

            if (evt.type === 'crisis') {
              showCrisis(evt.level);
            } else if (evt.type === 'delta' && typeof evt.text !== 'undefined') {
              if (!assistant) {
                thinking.remove();
                assistant = bubble('ai', '');
              }
              finalText += evt.text;
              assistant.textContent = finalText;
              scroll();
            } else if (evt.type === 'error') {
              errored = true;
              done_ = true;
              if (thinking.parentNode) thinking.remove();
              const reason = evt.message || 'Something went wrong.';
              if (assistant && finalText) {
                assistant.classList.add('is-error');
                const note = document.createElement('span');
                note.className = 'small muted';
                note.textContent = ' ' + reason;
                assistant.appendChild(note);
              } else {
                bubble('ai', '<strong>' + esc(reason) + '</strong><br><span class="small muted">You can try again, or use the toolkit in the meantime.</span>', 'is-error');
              }
            } else if (evt.type === 'done') {
              done_ = true;
              finished = true;
              if (evt.level && evt.level !== 'none') showCrisis(evt.level);
            }
          }
        }
        if (thinking.parentNode) thinking.remove();
        if (errored) {
          try { reader.cancel(); } catch (e) {}
        } else if (assistant && !finalText) {
          assistant.innerHTML = '<span class="muted small">No reply came back. Please try again.</span>';
        } else if (assistant && !finished) {
          const note = document.createElement('span');
          note.className = 'muted small';
          note.textContent = ' The reply was cut short. Try asking again.';
          assistant.appendChild(note);
        }
    } catch (err) {
      if (thinking.parentNode) thinking.remove();
      if (err && err.name === 'AbortError') {
        bubble('ai', '<span class="muted small">Stopped.</span>');
      } else if (assistant && finalText) {
        // A reply that broke off mid-way: keep the text, explain the break.
        assistant.classList.add('is-error');
        const note = document.createElement('span');
        note.className = 'small muted';
        note.textContent = ' ' + (err.message || 'The connection was interrupted.');
        assistant.appendChild(note);
      } else {
        bubble('ai', '<strong>' + esc(err.message || 'Something went wrong.') + '</strong><br><span class="small muted">Please try again in a moment.</span>', 'is-error');
      }
    } finally {
      busy = false;
      if (sendBtn) sendBtn.disabled = false;
      if (stopBtn) stopBtn.classList.add('hidden');
      if (input) input.focus();
    }
  }

  if (form) {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      controller = new AbortController();
      send(input ? input.value : '');
    });
  }
  if (stopBtn) {
    stopBtn.addEventListener('click', () => { if (controller) controller.abort(); });
  }
  if (input) {
    input.addEventListener('input', () => {
      input.style.height = 'auto';
      input.style.height = Math.min(input.scrollHeight, 160) + 'px';
    });
    input.addEventListener('keydown', (e) => {
      if (e.key === 'Enter' && !e.shiftKey) {
        e.preventDefault();
        const v = (input.value || '').trim();
        if (v) send(v);
      }
    });
  }
  if (suggest) {
    suggest.addEventListener('click', (e) => {
      const b = e.target.closest('[data-suggest]');
      if (!b) return;
      send(b.getAttribute('data-suggest'));
    });
  }

  scroll();
})();
