/**
 * useGameAudio — Web Audio API singleton for game BGM + SFX.
 * Each of the 30+ game templates has a completely unique melody theme.
 * Handles AudioContext autoplay policy via pending-start pattern.
 */
import { ref } from 'vue';

// ── Module-level singletons ─────────────────────────────────────────────────
let _ctx          = null;
let _gain         = null;
let _timer        = null;
let _theme        = null;
let _running      = false;
let _noteIdx      = 0;
let _nextTime     = 0;
let _lastCode     = null;   // remember last code for unmute restart
let _pendingStart = null;   // closure waiting for user gesture

const muted = ref(false);

// ── Frequency helper ────────────────────────────────────────────────────────
const C4 = 261.63;
const st = n => C4 * Math.pow(2, n / 12);

// ── 27 unique per-game themes ───────────────────────────────────────────────
// s  = scale (semitone offsets from C4)
// w  = waveform (sine | triangle | square | sawtooth)
// g  = master gain (0.02–0.05)
// oct= frequency multiplier (1=C4 range, 2=C5 range)
// mel= melody as indices into s[]  (max index < s.length)
// bpm= tempo
const THEMES = {
  // ═══ QUIZ ═══
  quiz_mcq: {
    bpm:112, w:'square',   g:.035, oct:2,
    s:[0,2,4,7,9],
    mel:[0,2,4,2,4,2,3,0,4,2,3,4,2,0,3,4],
  },
  true_false: {
    bpm:102, w:'square',   g:.032, oct:1,
    s:[0,3,5,7,10],
    mel:[0,3,0,3,4,0,3,4,0,3,0,4,0,3,4,0],
  },
  millionaire: {
    bpm:62,  w:'sawtooth', g:.025, oct:1,
    s:[0,2,3,5,7,8,10],
    mel:[0,2,4,3,2,0,3,2,4,3,5,4,3,2,0,2],
  },
  game_show_quiz: {
    bpm:128, w:'square',   g:.038, oct:2,
    s:[0,2,4,5,7,9,11],
    mel:[0,2,4,5,4,2,4,5,6,4,5,4,2,4,5,4],
  },
  math_quiz: {
    bpm:94,  w:'triangle', g:.036, oct:1,
    s:[0,2,3,5,7,9,10],
    mel:[0,3,2,5,3,5,2,3,0,5,3,2,5,3,0,2],
  },

  // ═══ WORD ═══
  anagram: {
    bpm:108, w:'triangle', g:.040, oct:2,
    s:[0,2,4,5,7,9],
    mel:[0,4,2,5,4,2,4,5,0,2,4,5,2,4,0,4],
  },
  word_search: {
    bpm:86,  w:'sine',     g:.033, oct:1,
    s:[0,2,4,7,9],
    mel:[0,2,3,2,4,3,2,3,0,4,2,3,4,2,3,0],
  },
  hangman: {
    bpm:56,  w:'sawtooth', g:.023, oct:1,
    s:[0,2,3,5,7,8,10],
    mel:[0,2,0,3,0,2,3,0,2,3,2,0,3,2,0,3],
  },
  spell_word: {
    bpm:98,  w:'triangle', g:.037, oct:1,
    s:[0,2,4,6,7,9,11],
    mel:[0,2,4,2,5,4,2,4,2,5,2,4,0,5,4,2],
  },
  spelling: {
    bpm:88,  w:'square',   g:.034, oct:1,
    s:[0,2,4,7,9],
    mel:[0,4,2,4,0,2,4,2,4,0,4,2,0,4,2,4],
  },
  complete_sentence: {
    bpm:78,  w:'sine',     g:.035, oct:1,
    s:[0,2,4,5,7,9,11],
    mel:[0,2,4,5,4,6,5,4,2,4,5,4,2,0,2,4],
  },
  type_answer: {
    bpm:114, w:'square',   g:.031, oct:1,
    s:[0,2,4,7,9],
    mel:[0,2,0,4,2,4,0,2,4,2,0,4,2,0,4,2],
  },

  // ═══ ACTION ═══
  whack_mole: {
    bpm:152, w:'square',   g:.027, oct:2,
    s:[0,3,5,7,10],
    mel:[4,3,4,0,4,3,0,4,3,4,0,3,4,0,3,4],
  },
  flying_answers: {
    bpm:72,  w:'sine',     g:.030, oct:2,
    s:[0,2,4,6,8,10],
    mel:[0,4,2,5,4,2,4,5,0,2,4,5,2,4,0,5],
  },
  speed_sort: {
    bpm:142, w:'sawtooth', g:.025, oct:1,
    s:[0,2,3,5,7,8,10],
    mel:[0,3,0,5,3,5,0,3,5,0,3,5,0,3,0,5],
  },
  airplane: {
    bpm:82,  w:'triangle', g:.038, oct:1,
    s:[0,2,4,5,7,9,10],
    mel:[0,2,4,5,6,5,4,5,2,4,5,6,4,5,2,0],
  },

  // ═══ MEMORY ═══
  memory_cards: {
    bpm:66,  w:'sine',     g:.027, oct:1,
    s:[0,2,3,5,7,9,10],
    mel:[0,2,3,5,3,2,3,0,2,3,5,3,2,0,3,2],
  },
  flashcards: {
    bpm:74,  w:'sine',     g:.031, oct:1,
    s:[0,2,4,5,7,9,11],
    mel:[0,2,4,2,0,4,2,4,5,4,2,0,4,2,4,0],
  },
  watch_memorize: {
    bpm:60,  w:'sine',     g:.025, oct:1,
    s:[0,2,4,7,9],
    mel:[0,2,4,2,4,2,0,4,2,0,4,2,4,0,2,4],
  },
  matching_pairs: {
    bpm:90,  w:'triangle', g:.033, oct:1,
    s:[0,2,4,5,7,9,11],
    mel:[0,4,2,4,5,4,2,0,4,2,5,4,2,4,0,2],
  },
  group_sort: {
    bpm:96,  w:'triangle', g:.035, oct:1,
    s:[0,2,4,7,9],
    mel:[0,2,4,2,4,0,2,4,0,4,2,0,4,2,4,0],
  },
  pair_or_not: {
    bpm:106, w:'square',   g:.031, oct:1,
    s:[0,3,5,7,10],
    mel:[0,4,0,3,4,3,0,4,3,0,4,3,4,0,3,4],
  },
  reorder: {
    bpm:86,  w:'triangle', g:.033, oct:1,
    s:[0,2,4,5,7,9,11],
    mel:[0,1,2,3,4,5,4,3,2,1,0,2,4,5,4,2],
  },
  diagram: {
    bpm:62,  w:'sine',     g:.023, oct:1,
    s:[0,2,4,6,8,10],
    mel:[0,3,1,4,2,5,2,4,1,3,0,3,2,4,1,3],
  },

  // ═══ FUN ═══
  random_wheel: {
    bpm:134, w:'square',   g:.036, oct:2,
    s:[0,2,4,5,7,9,11],
    mel:[0,4,5,4,5,4,2,4,5,4,5,2,4,5,4,2],
  },
  open_box: {
    bpm:68,  w:'sine',     g:.027, oct:1,
    s:[0,3,5,6,7,10],
    mel:[0,2,3,5,3,2,0,3,2,5,3,2,3,0,2,3],
  },
  win_or_lose: {
    bpm:78,  w:'sawtooth', g:.029, oct:1,
    s:[0,2,3,5,7,8,10],
    mel:[0,3,2,4,2,4,3,0,4,3,2,4,3,2,3,0],
  },

  // ═══ YANGI O'YINLAR ═══
  zakovat: {
    bpm:100, w:'square',   g:.039, oct:2,
    s:[0,2,4,5,7,9,11],
    mel:[0,4,5,4,0,4,5,4,2,4,5,4,0,4,5,4],
  },
  race: {
    bpm:148, w:'sawtooth', g:.027, oct:1,
    s:[0,2,3,5,7,10],
    mel:[0,2,0,3,2,3,0,2,3,2,0,3,2,0,3,2],
  },
  timeline: {
    bpm:80,  w:'triangle', g:.033, oct:1,
    s:[0,2,3,5,7,9,10],
    mel:[0,2,3,5,3,5,3,2,0,3,5,3,2,3,0,2],
  },
};

// ── AudioContext factory ────────────────────────────────────────────────────
function getCtx() {
  if (!_ctx) {
    _ctx  = new (window.AudioContext || window.webkitAudioContext)();
    _gain = _ctx.createGain();
    _gain.gain.value = muted.value ? 0 : 1;
    _gain.connect(_ctx.destination);
  }
  return _ctx;
}

// ── Play a single note ──────────────────────────────────────────────────────
function playNote(freq, startTime, duration, wave, gain) {
  const c = getCtx();
  const o = c.createOscillator();
  const g = c.createGain();
  o.type = wave;
  o.frequency.setValueAtTime(freq, startTime);
  g.gain.setValueAtTime(gain, startTime);
  g.gain.exponentialRampToValueAtTime(0.001, startTime + duration);
  o.connect(g);
  g.connect(_gain);
  o.start(startTime);
  o.stop(startTime + duration + 0.06);
}

// ── BGM scheduler (25 ms lookahead) ────────────────────────────────────────
function schedule() {
  if (!_running || !_theme) return;
  const c    = getCtx();
  const LOOK = 0.15;
  const spb  = 60 / _theme.bpm;
  const dur  = spb * 0.72;
  const mult = _theme.oct ?? 1;

  while (_nextTime < c.currentTime + LOOK) {
    const scIdx = _theme.mel[_noteIdx % _theme.mel.length];
    const semi  = _theme.s[scIdx % _theme.s.length];
    playNote(st(semi) * mult, _nextTime, dur, _theme.w, _theme.g);
    _nextTime += spb;
    _noteIdx++;
  }
  _timer = setTimeout(schedule, 25);
}

// ── First-interaction handler: starts pending music ─────────────────────────
function _onFirstInteraction() {
  document.removeEventListener('click',   _onFirstInteraction);
  document.removeEventListener('keydown', _onFirstInteraction);
  document.removeEventListener('touchstart', _onFirstInteraction);
  const fn = _pendingStart;
  _pendingStart = null;
  if (fn) {
    const c = getCtx();
    c.resume().then(fn).catch(() => {});
  }
}

// ── Public API ──────────────────────────────────────────────────────────────
export function useGameAudio() {

  function play(code) {
    if (muted.value) return;
    stop();

    _theme   = THEMES[code] ?? THEMES.quiz_mcq;
    _lastCode = code;
    _running  = true;
    _noteIdx  = 0;

    const c = getCtx();

    const startNow = () => {
      _nextTime = c.currentTime + 0.3;
      schedule();
    };

    if (c.state !== 'running') {
      // Queue start; fire on first user gesture (click / keydown / touch)
      _pendingStart = startNow;
      document.addEventListener('click',      _onFirstInteraction, { once: false });
      document.addEventListener('keydown',    _onFirstInteraction, { once: false });
      document.addEventListener('touchstart', _onFirstInteraction, { once: false });
      // Also attempt resume immediately — works if user already interacted before
      c.resume().then(() => {
        if (_pendingStart === startNow) {
          document.removeEventListener('click',      _onFirstInteraction);
          document.removeEventListener('keydown',    _onFirstInteraction);
          document.removeEventListener('touchstart', _onFirstInteraction);
          _pendingStart = null;
          startNow();
        }
      }).catch(() => {});
    } else {
      startNow();
    }
  }

  function stop() {
    _running = false;
    clearTimeout(_timer);
    _timer = null;
    _theme = null;
    _pendingStart = null;
    document.removeEventListener('click',      _onFirstInteraction);
    document.removeEventListener('keydown',    _onFirstInteraction);
    document.removeEventListener('touchstart', _onFirstInteraction);
  }

  function toggleMute() {
    muted.value = !muted.value;
    if (_gain) {
      _gain.gain.setTargetAtTime(muted.value ? 0 : 1, getCtx().currentTime, 0.15);
    }
    if (muted.value) {
      stop();
    } else if (_lastCode) {
      // toggleMute is called from a button click → IS a user gesture → play immediately
      const c = getCtx();
      _theme   = THEMES[_lastCode] ?? THEMES.quiz_mcq;
      _running = true;
      _noteIdx = 0;
      c.resume().then(() => {
        _nextTime = c.currentTime + 0.3;
        schedule();
      }).catch(() => {});
    }
  }

  // ✅ Correct answer
  function playCorrect() {
    if (muted.value) return;
    const c = getCtx();
    if (c.state !== 'running') return;
    const t = c.currentTime;
    [[st(0),0],[st(4),0.1],[st(7),0.2],[st(12),0.3]].forEach(([f,d]) =>
      playNote(f, t+d, 0.13, 'triangle', 0.13)
    );
  }

  // ❌ Wrong answer
  function playWrong() {
    if (muted.value) return;
    const c = getCtx();
    if (c.state !== 'running') return;
    const t = c.currentTime;
    [[st(-2),0],[st(-5),0.14]].forEach(([f,d]) =>
      playNote(f, t+d, 0.18, 'sawtooth', 0.09)
    );
  }

  // 🎉 Game complete
  function playComplete() {
    if (muted.value) return;
    const c = getCtx();
    if (c.state !== 'running') return;
    const t = c.currentTime;
    [[st(0),0,0.10],[st(4),0.12,0.10],[st(7),0.24,0.10],
     [st(12),0.36,0.28],[st(7),0.40,0.10],[st(12),0.58,0.50]]
      .forEach(([f,d,dur]) => playNote(f, t+d, dur, 'square', 0.16));
    document.dispatchEvent(new CustomEvent('game-complete'));
  }

  return { muted, play, stop, toggleMute, playCorrect, playWrong, playComplete };
}
