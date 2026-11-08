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
})();
