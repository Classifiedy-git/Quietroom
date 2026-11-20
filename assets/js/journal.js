/* Quietroom: journal page helpers */
(function () {
  'use strict';

  const PROMPTS = window.CLASSY_PROMPTS || [];
  let idx = window.CLASSY_PROMPT_INDEX || 0;

  const newBtn = document.getElementById('new-prompt');
  const textEl = document.getElementById('prompt-text');
  const inputEl = document.getElementById('prompt-input');
  if (newBtn && textEl && inputEl && PROMPTS.length) {
    newBtn.addEventListener('click', () => {
      idx = (idx + 1) % PROMPTS.length;
      textEl.textContent = PROMPTS[idx];
      inputEl.value = PROMPTS[idx];
    });
  }

  const aiBtn = document.getElementById('ai-prompt-btn');
  if (aiBtn && textEl && inputEl) {
    aiBtn.addEventListener('click', async () => {
      aiBtn.disabled = true;
      aiBtn.innerHTML = Quietroom.labelled('sparkle', 'Thinking…');
      try {
        const moodEl = document.getElementById('mood');
        const mood = moodEl ? moodEl.value : '';
        const body = Quietroom.body({ _token: Quietroom.csrf(), mood: mood || '' });
        const res = await fetch((window.CLASSY_API_BASE || '/') + 'api/journal/prompt', {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: body
        });
        const json = await Quietroom.json(res);
        if (!res.ok || json.ok === false) throw new Error(json.error || 'Could not generate a prompt.');
        textEl.textContent = json.prompt;
        inputEl.value = json.prompt;
      } catch (err) {
        // keep existing prompt on failure
      }
      aiBtn.disabled = false;
      aiBtn.innerHTML = Quietroom.labelled('sparkle', 'AI prompt');
    });
  }
})();
