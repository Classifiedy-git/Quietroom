/* ==========================================================================
   Quietroom: guided exercise player (breathing, grounding, body scan)
   ========================================================================== */
(function () {
  'use strict';

  function Player(root) {
    this.root = root;
    this.phaseEl = root.querySelector('[data-phase]');
    this.hintEl = root.querySelector('[data-hint]');
    this.countEl = root.querySelector('[data-count]');
    this.circleEl = root.querySelector('[data-circle]');
    this.barEl = root.querySelector('[data-progress]');
    this.startBtn = root.querySelector('[data-start]');
    this.resetBtn = root.querySelector('[data-reset]');
    this.doneEl = root.querySelector('[data-done]');
    this.stepList = root.querySelector('[data-steps]');
    this.stepIndex = 0;

    try {
      this.phases = JSON.parse(root.getAttribute('data-phases') || '[]');
    } catch (e) { this.phases = []; }

    this.cycle = parseInt(root.getAttribute('data-cycle') || '1', 10);
    this.phaseIndex = 0;
    this.remaining = 0;
    this.total = 0;
    this.elapsed = 0;
    this.timer = null;
    this.running = false;
    this.startedAt = 0;
    this.pausedDuration = 0;
    this.done = false;
    this._firstRender = true;

    if (this.startBtn) this.startBtn.addEventListener('click', () => this.toggle());
    if (this.resetBtn) this.resetBtn.addEventListener('click', () => this.reset());
    this.render();
  }

  Player.prototype.circleClass = function (label) {
    const l = (label || '').toLowerCase();
    if (l.indexOf('in') !== -1) return 'is-in';
    if (l.indexOf('out') !== -1 || l.indexOf('exhale') !== -1 || l.indexOf('sigh') !== -1) return 'is-out';
    return 'is-hold';
  };

  // Smooth continuous scale for the breathing circle, modelled on the
  // Apple Breathe app: a single eased sine wave across the whole cycle.
  // 0 = fully contracted, 1 = fully expanded, 0.5 = midpoint.
  Player.prototype.breatheAt = function (t) {
    // t in [0,1) across one full inhale-hold-exhale-hold cycle.
    // Eased with a smoothstep so the turnaround is soft, not abrupt.
    const eased = t < 0.5
      ? 0.5 * easeInQuad(2 * t)
      : 0.5 + 0.5 * easeOutQuad(2 * (t - 0.5));
    const scale = 0.78 + (1.18 - 0.78) * eased;
    if (this.circleEl) this.circleEl.style.transform = 'scale(' + scale.toFixed(4) + ')';
  };

  function easeInQuad(t) { return t * t; }
  function easeOutQuad(t) { return 1 - (1 - t) * (1 - t); }

  Player.prototype.render = function () {
    const phase = this.phases[this.phaseIndex];
    if (!phase) return;
    // The phase label and countdown swap instantly — they sit on top of the
    // breathing circle and should never dim. Only the hint text fades, so it
    // reads like a slow, rhythmic whisper underneath the motion.
    if (this.phaseEl) this.phaseEl.textContent = phase.label;
    if (this.hintEl) {
      if (this._firstRender) {
        this.hintEl.textContent = phase.text || '';
      } else {
        this.hintEl.classList.add('is-fading');
        setTimeout(() => {
          this.hintEl.textContent = phase.text || '';
          this.hintEl.classList.remove('is-fading');
        }, 1400);
      }
    }
    this._firstRender = false;
    if (this.circleEl) {
      this.circleEl.classList.remove('is-in', 'is-hold', 'is-out');
      this.circleEl.classList.add(this.circleClass(phase.label));
    }
    this.updateStep();
  };

  Player.prototype.updateStep = function () {
    if (!this.stepList) return;
    const items = this.stepList.querySelectorAll('li');
    items.forEach((li, i) => li.classList.toggle('is-active', i === this.stepIndex));
  };

  Player.prototype.nextPhase = function () {
    this.phaseIndex++;
    if (this.phaseIndex >= this.phases.length) {
      this.phaseIndex = 0;
      this.cycle++;
      this.advanceStep();
      if (this.cycle > this.cycle_total) {
        this.finish();
        return;
      }
    }
    const phase = this.phases[this.phaseIndex];
    this.remaining = phase.seconds;
    this.total = phase.seconds;
    this.render();
  };

  Player.prototype.advanceStep = function () {
    if (this.stepList) {
      const items = this.stepList.querySelectorAll('li');
      if (this.stepIndex < items.length) this.stepIndex++;
    }
  };

  Player.prototype.tick = function () {
    this.remaining--;
    this.elapsed++;
    if (this.countEl) this.countEl.textContent = Math.max(0, this.remaining);
    const phaseTotal = this.phases[this.phaseIndex].seconds;
    if (this.barEl) {
      const pct = phaseTotal > 0 ? ((phaseTotal - this.remaining) / phaseTotal) * 100 : 0;
      this.barEl.style.width = Math.min(100, Math.max(0, pct)) + '%';
    }
    if (this.remaining <= 0) this.nextPhase();
  };

  // Drive the circle on a 60fps frame loop so the scale eases smoothly
  // instead of snapping between discrete classes every second.
  // Position is derived from wall-clock time since start, so the circle
  // glides continuously even though the timer only ticks once per second.
  Player.prototype.animate = function () {
    if (!this.running) return;
    const phase = this.phases[this.phaseIndex];
    if (!phase) return;
    const cycleSecs = this.phases.reduce((s, p) => s + p.seconds, 0);
    if (cycleSecs <= 0) { requestAnimationFrame(() => this.animate()); return; }
    const phaseStart = this.phases.slice(0, this.phaseIndex)
      .reduce((s, p) => s + p.seconds, 0);
    // Interpolate between the last completed second and the next, so the
    // circle keeps moving between timer ticks instead of freezing.
    const nowSec = (Date.now() - this.startedAt) / 1000;
    const tInCycle = (nowSec - phaseStart) / cycleSecs;
    const t = ((tInCycle % 1) + 1) % 1;
    this.breatheAt(t);
    requestAnimationFrame(() => this.animate());
  };

  Player.prototype.toggle = function () {
    if (this.running) { this.pause(); return; }
    this.start();
  };

  Player.prototype.start = function () {
    if (this.done) this.reset();
    const phase = this.phases[this.phaseIndex];
    if (!phase) return;
    if (this.remaining <= 0) {
      this.remaining = phase.seconds;
      this.phaseTotal = phase.seconds;
    }
    this.running = true;
    // startedAt is shifted back by any previously paused duration so the
    // wall-clock position stays correct across pause/resume.
    this.startedAt = Date.now() - this.pausedDuration;
    if (this.startBtn) this.startBtn.innerHTML = Quietroom.labelled('pause', 'Pause');
    this.root.classList.add('is-running');
    this.timer = setInterval(() => this.tick(), 1000);
    requestAnimationFrame(() => this.animate());
  };

  Player.prototype.pause = function () {
    if (!this.running) return;
    this.running = false;
    this.pausedDuration += Date.now() - this.startedAt;
    clearInterval(this.timer);
    this.timer = null;
    if (this.startBtn) this.startBtn.innerHTML = Quietroom.labelled('play', 'Resume');
    this.root.classList.remove('is-running');
  };

  Player.prototype.reset = function () {
    this.pause();
    this.phaseIndex = 0;
    this.cycle = 1;
    this.remaining = this.phases.length ? this.phases[0].seconds : 0;
    this.phaseTotal = this.remaining;
    this.elapsed = 0;
    this.stepIndex = 0;
    this.done = false;
    this._firstRender = true;
    this.root.classList.remove('is-complete');
    if (this.doneEl) this.doneEl.classList.add('hidden');
    if (this.startBtn) this.startBtn.innerHTML = Quietroom.labelled('play', 'Start');
    if (this.countEl) this.countEl.textContent = this.remaining;
    if (this.barEl) this.barEl.style.width = '0%';
    if (this.circleEl) this.circleEl.style.transform = 'scale(0.78)';
    this.render();
  };

  Player.prototype.finish = function () {
    this.pause();
    this.done = true;
    this.root.classList.add('is-complete');
    if (this.doneEl) this.doneEl.classList.remove('hidden');
    if (this.startBtn) this.startBtn.innerHTML = Quietroom.labelled('check', 'Done');
    const seconds = this.elapsed;
    this.root.dispatchEvent(new CustomEvent('exercise:complete', { bubbles: true, detail: { seconds: seconds } }));
  };

  function initPlayers() {
    const roots = Array.from(document.querySelectorAll('[data-player]'));
    roots.forEach((root) => {
      if (root._player) return;
      const p = new Player(root);
      p.cycle_total = p.cycle;
      root._player = p;
    });
  }

  document.addEventListener('DOMContentLoaded', initPlayers);
  // Fallback: if the page is already loaded (cached navigation, SPA-style
  // transitions), DOMContentLoaded never fires and the player never starts.
  initPlayers();
})();
