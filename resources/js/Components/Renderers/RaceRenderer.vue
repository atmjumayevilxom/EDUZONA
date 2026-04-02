<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: Object });
const audio = useGameAudio();

// ── Questions ─────────────────────────────────────────────
const questions = computed(() => {
    const raw = props.gameData?.items ?? props.gameData?.questions ?? [];
    return raw.map((q, i) => ({
        id:       i,
        question: q.question ?? q.text ?? `Savol ${i + 1}`,
        options:  q.options  ?? q.choices ?? [],
        answer:   q.answer   ?? q.correct ?? '',
    }));
});

const TOTAL      = computed(() => questions.value.length);
const STEP       = computed(() => TOTAL.value > 0 ? Math.floor(100 / TOTAL.value) : 10);
const currentIdx = ref(0);
const currentQ   = computed(() => questions.value[currentIdx.value] ?? null);

// ── Player state ──────────────────────────────────────────
const p1Pos   = ref(0);
const p2Pos   = ref(0);
const p1Score = ref(0);
const p2Score = ref(0);
const p1Lock  = ref(false); // locked out of current question
const p2Lock  = ref(false);
const p1Flash = ref(null);  // 'correct' | 'wrong' | null
const p2Flash = ref(null);

const roundOver = ref(false);
const started   = ref(false);
const finished  = ref(false);
const winner    = ref(null); // 1 | 2 | 'draw'

const LETTERS  = ['A', 'B', 'C', 'D'];
const P1_KEYS  = ['1', '2', '3', '4'];
const P2_KEYS  = ['7', '8', '9', '0'];

// ── Core logic ────────────────────────────────────────────
function handleAnswer(player, optIdx) {
    if (!started.value || finished.value || roundOver.value) return;
    if (player === 1 && p1Lock.value) return;
    if (player === 2 && p2Lock.value) return;

    const q = currentQ.value;
    if (!q || optIdx >= q.options.length) return;

    const isCorrect = q.options[optIdx] === q.answer;

    if (isCorrect) {
        if (player === 1) { p1Score.value++; p1Pos.value = Math.min(100, p1Pos.value + STEP.value); p1Flash.value = 'correct'; }
        else              { p2Score.value++; p2Pos.value = Math.min(100, p2Pos.value + STEP.value); p2Flash.value = 'correct'; }
        audio.playCorrect();
        roundOver.value = true;
        setTimeout(() => {
            p1Flash.value = null;
            p2Flash.value = null;
            const reachedEnd = p1Pos.value >= 100 || p2Pos.value >= 100;
            const lastQ      = currentIdx.value >= TOTAL.value - 1;
            if (reachedEnd || lastQ) { endGame(); }
            else                     { nextRound(); }
        }, 900);

    } else {
        if (player === 1) { p1Lock.value = true; p1Flash.value = 'wrong'; }
        else              { p2Lock.value = true; p2Flash.value = 'wrong'; }
        audio.playWrong();
        setTimeout(() => {
            if (player === 1) p1Flash.value = null;
            else              p2Flash.value = null;
        }, 500);

        // Both locked → skip question
        if (p1Lock.value && p2Lock.value) {
            roundOver.value = true;
            setTimeout(() => {
                p1Flash.value = null; p2Flash.value = null;
                if (currentIdx.value >= TOTAL.value - 1) { endGame(); }
                else                                     { nextRound(); }
            }, 750);
        }
    }
}

function nextRound() {
    currentIdx.value++;
    p1Lock.value    = false;
    p2Lock.value    = false;
    roundOver.value = false;
}

function endGame() {
    finished.value = true;
    if      (p1Score.value > p2Score.value) winner.value = 1;
    else if (p2Score.value > p1Score.value) winner.value = 2;
    else                                    winner.value = 'draw';
    audio.playComplete();
}

function restart() {
    p1Pos.value = p2Pos.value = p1Score.value = p2Score.value = 0;
    p1Lock.value = p2Lock.value = false;
    p1Flash.value = p2Flash.value = null;
    roundOver.value = finished.value = false;
    winner.value = null;
    currentIdx.value = 0;
    started.value = false;
}

// ── Keyboard ──────────────────────────────────────────────
function onKeyDown(e) {
    if (!started.value) return;
    const k = e.key;
    const i1 = P1_KEYS.indexOf(k);
    if (i1 !== -1) { handleAnswer(1, i1); return; }
    const i2 = P2_KEYS.indexOf(k);
    if (i2 !== -1) { handleAnswer(2, i2); return; }
}
onMounted(()  => window.addEventListener('keydown', onKeyDown));
onUnmounted(() => window.removeEventListener('keydown', onKeyDown));

// ── Helpers ───────────────────────────────────────────────
function optClass(player, optIdx) {
    const q     = currentQ.value;
    const lock  = player === 1 ? p1Lock.value  : p2Lock.value;
    const flash = player === 1 ? p1Flash.value : p2Flash.value;

    if (lock || roundOver.value) {
        const isAns = q?.options[optIdx] === q?.answer;
        if (isAns)  return player === 1 ? 'opt-correct-p1' : 'opt-correct-p2';
        return 'opt-dim';
    }
    return player === 1 ? 'opt-idle-p1' : 'opt-idle-p2';
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-8 py-12 text-center text-white',
                    winner === 1   ? 'bg-gradient-to-br from-red-400 to-rose-600'
                    : winner === 2 ? 'bg-gradient-to-br from-blue-400 to-indigo-600'
                    : 'bg-gradient-to-br from-slate-500 to-slate-700'
                ]">
                    <div class="text-7xl mb-3">{{ winner === 1 ? '🏎️' : winner === 2 ? '🚙' : '🤝' }}</div>
                    <div class="text-4xl font-black mb-1">
                        {{ winner === 1 ? '1-o\'yinchi g\'alaba qozondi!' : winner === 2 ? '2-o\'yinchi g\'alaba qozondi!' : 'Durrang!' }}
                    </div>
                    <div class="text-white/80 text-base mt-3 flex justify-center gap-8">
                        <div class="text-center">
                            <div class="text-3xl font-black">{{ p1Score }}</div>
                            <div class="text-sm text-white/60">1-o'yinchi</div>
                        </div>
                        <div class="text-white/40 text-3xl">:</div>
                        <div class="text-center">
                            <div class="text-3xl font-black">{{ p2Score }}</div>
                            <div class="text-sm text-white/60">2-o'yinchi</div>
                        </div>
                    </div>
                </div>
                <div class="p-6">
                    <button @click="restart"
                        class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-4 rounded-2xl transition text-lg">
                        🔄 Qayta poyga
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ START SCREEN ══ -->
        <div v-else-if="!started" class="max-w-xl mx-auto">
            <div class="rounded-3xl overflow-hidden shadow-2xl"
                style="background:linear-gradient(135deg,#0f172a,#1e293b,#0f172a)">
                <div class="px-8 py-10 text-center text-white">
                    <div class="text-6xl mb-3">🏎️🚙</div>
                    <h2 class="text-3xl font-black mb-2">Poyga!</h2>
                    <p class="text-white/50 text-sm">Ikkita o'yinchi — bir kompyuter</p>
                </div>
                <div class="px-6 pb-6 space-y-4 bg-black/20">
                    <div class="grid grid-cols-2 gap-3">
                        <div class="bg-red-500/15 border-2 border-red-500/30 rounded-2xl p-4 text-center">
                            <div class="text-3xl mb-2">🏎️</div>
                            <div class="font-black text-red-300 text-lg">1-o'yinchi</div>
                            <div class="text-red-400/70 text-xs font-bold mt-2">Tugmalar</div>
                            <div class="flex justify-center gap-1.5 mt-2">
                                <span v-for="(k,i) in P1_KEYS" :key="k"
                                    class="bg-red-500/20 text-red-300 font-black text-sm w-8 h-8 rounded-lg flex items-center justify-center border border-red-500/30">
                                    {{ k }}
                                </span>
                            </div>
                            <div class="text-red-400/50 text-xs mt-1.5">= A B C D</div>
                        </div>
                        <div class="bg-blue-500/15 border-2 border-blue-500/30 rounded-2xl p-4 text-center">
                            <div class="text-3xl mb-2">🚙</div>
                            <div class="font-black text-blue-300 text-lg">2-o'yinchi</div>
                            <div class="text-blue-400/70 text-xs font-bold mt-2">Tugmalar</div>
                            <div class="flex justify-center gap-1.5 mt-2">
                                <span v-for="(k,i) in P2_KEYS" :key="k"
                                    class="bg-blue-500/20 text-blue-300 font-black text-sm w-8 h-8 rounded-lg flex items-center justify-center border border-blue-500/30">
                                    {{ k }}
                                </span>
                            </div>
                            <div class="text-blue-400/50 text-xs mt-1.5">= A B C D</div>
                        </div>
                    </div>
                    <button @click="started = true"
                        class="w-full bg-gradient-to-r from-slate-600 to-slate-700 hover:from-slate-500 hover:to-slate-600
                               text-white font-black py-4 rounded-2xl transition text-lg shadow-lg border border-white/10">
                        🚦 Poyga boshlash!
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else class="space-y-3">

            <!-- Race track -->
            <div class="race-field rounded-2xl overflow-hidden p-4 space-y-3">
                <!-- Player 1 lane -->
                <div class="lane">
                    <div class="lane-label text-red-300">
                        <span class="text-base">🏎️</span>
                        <span class="text-xs font-black">P1</span>
                        <span class="text-xs font-bold opacity-70">{{ p1Score }} ball</span>
                    </div>
                    <div class="lane-track">
                        <div class="lane-dashes"></div>
                        <!-- Finish -->
                        <div class="finish-stripe"></div>
                        <div class="absolute right-0.5 top-1 text-xs">🏁</div>
                        <!-- Car -->
                        <div class="car-emoji text-2xl"
                            :class="p1Flash === 'correct' ? 'car-correct' : p1Flash === 'wrong' ? 'car-wrong' : ''"
                            :style="{ left: `calc(${p1Pos}% - ${p1Pos > 5 ? 20 : 0}px)`, transition: 'left 0.7s cubic-bezier(0.4,0,0.2,1)' }">
                            🏎️
                        </div>
                    </div>
                    <div class="lane-pct text-red-300">{{ p1Pos }}%</div>
                </div>

                <!-- Player 2 lane -->
                <div class="lane">
                    <div class="lane-label text-blue-300">
                        <span class="text-base">🚙</span>
                        <span class="text-xs font-black">P2</span>
                        <span class="text-xs font-bold opacity-70">{{ p2Score }} ball</span>
                    </div>
                    <div class="lane-track">
                        <div class="lane-dashes"></div>
                        <div class="finish-stripe"></div>
                        <div class="absolute right-0.5 top-1 text-xs">🏁</div>
                        <div class="car-emoji text-2xl"
                            :class="p2Flash === 'correct' ? 'car-correct' : p2Flash === 'wrong' ? 'car-wrong' : ''"
                            :style="{ left: `calc(${p2Pos}% - ${p2Pos > 5 ? 20 : 0}px)`, transition: 'left 0.7s cubic-bezier(0.4,0,0.2,1)' }">
                            🚙
                        </div>
                    </div>
                    <div class="lane-pct text-blue-300">{{ p2Pos }}%</div>
                </div>

                <!-- Progress label -->
                <div class="flex justify-between text-slate-500 text-xs font-bold px-10">
                    <span>Start 🚦</span>
                    <span class="text-slate-400">{{ currentIdx + 1 }}/{{ TOTAL }} savol</span>
                    <span>Finish 🏁</span>
                </div>
            </div>

            <!-- Question card -->
            <div v-if="currentQ" :key="currentIdx" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden q-appear">
                <div class="bg-gradient-to-r from-slate-700 to-slate-800 px-5 py-4">
                    <div class="flex items-center gap-2 mb-2">
                        <span class="bg-white/20 text-white text-xs font-black px-2 py-0.5 rounded-full">Savol {{ currentIdx + 1 }}</span>
                        <span v-if="roundOver" class="bg-emerald-500/30 text-emerald-300 text-xs font-black px-2 py-0.5 rounded-full">✓ Javob berildi</span>
                    </div>
                    <p class="text-white font-bold text-sm sm:text-base leading-snug">{{ currentQ.question }}</p>
                </div>

                <!-- Options grid — 2x2 -->
                <div class="grid grid-cols-2 gap-2 p-3">
                    <div v-for="(opt, oi) in currentQ.options" :key="opt"
                        class="option-card"
                        :class="opt === currentQ.answer && (roundOver || (p1Lock && p2Lock)) ? 'opt-reveal' : ''">
                        <div class="opt-letter-badge">{{ LETTERS[oi] }}</div>
                        <div class="flex-1 text-xs sm:text-sm font-semibold text-slate-700 leading-snug">{{ opt }}</div>

                        <!-- P1 button -->
                        <button
                            @click="handleAnswer(1, oi)"
                            :disabled="p1Lock || roundOver"
                            :class="['player-btn p1-btn', p1Flash === 'wrong' && p1Lock ? 'p1-locked' : '', optClass(1, oi) === 'opt-correct-p1' ? 'p1-correct' : '']"
                        >{{ P1_KEYS[oi] }}</button>

                        <!-- P2 button -->
                        <button
                            @click="handleAnswer(2, oi)"
                            :disabled="p2Lock || roundOver"
                            :class="['player-btn p2-btn', p2Flash === 'wrong' && p2Lock ? 'p2-locked' : '', optClass(2, oi) === 'opt-correct-p2' ? 'p2-correct' : '']"
                        >{{ P2_KEYS[oi] }}</button>
                    </div>
                </div>

                <!-- Player status bar -->
                <div class="grid grid-cols-2 gap-0 border-t border-slate-100">
                    <div :class="[
                        'flex items-center gap-2 px-4 py-3 border-r border-slate-100 transition-all duration-300',
                        p1Flash === 'correct' ? 'bg-emerald-50'
                        : p1Flash === 'wrong'  ? 'bg-red-50'
                        : p1Lock               ? 'bg-slate-50 opacity-60'
                        : 'bg-white'
                    ]">
                        <span class="text-xl">🏎️</span>
                        <div class="flex-1">
                            <div class="text-xs font-black text-red-600">1-O'YINCHI</div>
                            <div class="text-[10px] text-slate-400 font-bold">
                                {{ p1Flash === 'correct' ? '✅ To\'g\'ri!' : p1Flash === 'wrong' ? '❌ Xato' : p1Lock ? '🔒 Javob berildi' : '⏳ Kutmoqda...' }}
                            </div>
                        </div>
                        <div class="text-sm font-black text-red-500">{{ p1Score }} 🏆</div>
                    </div>
                    <div :class="[
                        'flex items-center gap-2 px-4 py-3 transition-all duration-300',
                        p2Flash === 'correct' ? 'bg-emerald-50'
                        : p2Flash === 'wrong'  ? 'bg-red-50'
                        : p2Lock               ? 'bg-slate-50 opacity-60'
                        : 'bg-white'
                    ]">
                        <span class="text-xl">🚙</span>
                        <div class="flex-1">
                            <div class="text-xs font-black text-blue-600">2-O'YINCHI</div>
                            <div class="text-[10px] text-slate-400 font-bold">
                                {{ p2Flash === 'correct' ? '✅ To\'g\'ri!' : p2Flash === 'wrong' ? '❌ Xato' : p2Lock ? '🔒 Javob berildi' : '⏳ Kutmoqda...' }}
                            </div>
                        </div>
                        <div class="text-sm font-black text-blue-500">{{ p2Score }} 🏆</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* ── Race field ── */
.race-field {
    background: linear-gradient(180deg, #0f172a 0%, #1e293b 100%);
}

/* ── Lane ── */
.lane {
    display: flex;
    align-items: center;
    gap: 8px;
}
.lane-label {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 1px;
    width: 36px;
    flex-shrink: 0;
}
.lane-track {
    flex: 1;
    height: 48px;
    position: relative;
    background: linear-gradient(90deg, #374151, #4b5563);
    border-radius: 12px;
    overflow: hidden;
    border: 1px solid rgba(255,255,255,0.1);
}
.lane-dashes {
    position: absolute;
    top: 50%;
    left: 0; right: 0;
    height: 2px;
    margin-top: -1px;
    background: repeating-linear-gradient(90deg, rgba(255,255,255,0.15) 0, rgba(255,255,255,0.15) 12px, transparent 12px, transparent 24px);
}
.finish-stripe {
    position: absolute;
    right: 0; top: 0; bottom: 0;
    width: 20px;
    background: repeating-linear-gradient(45deg, #fff 0, #fff 3px, #000 3px, #000 6px);
    border-radius: 0 12px 12px 0;
    opacity: 0.9;
}
.lane-pct {
    font-size: 10px;
    font-weight: 800;
    width: 30px;
    text-align: right;
    flex-shrink: 0;
}
.car-emoji {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 2;
    line-height: 1;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
}
.car-correct { animation: carBoost 0.5s ease; filter: drop-shadow(0 0 8px #22c55e); }
.car-wrong   { animation: carBump  0.4s ease; filter: drop-shadow(0 0 8px #ef4444); }

@keyframes carBoost {
    0%   { transform: translateY(-50%) scale(1); }
    40%  { transform: translateY(-70%) scale(1.3); }
    100% { transform: translateY(-50%) scale(1); }
}
@keyframes carBump {
    0%,100% { transform: translateY(-50%) translateX(0); }
    30%     { transform: translateY(-50%) translateX(-6px); }
    60%     { transform: translateY(-50%) translateX(4px); }
}

/* ── Question card ── */
.q-appear { animation: qSlide 0.35s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity:0; transform:translateY(14px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

/* ── Option cards ── */
.option-card {
    display: flex;
    align-items: center;
    gap: 6px;
    padding: 8px 10px;
    border-radius: 12px;
    border: 2px solid #e2e8f0;
    background: #f8fafc;
    transition: border-color 0.2s, background 0.2s;
    min-height: 48px;
}
.opt-reveal {
    border-color: #4ade80 !important;
    background: #f0fdf4 !important;
}
.opt-letter-badge {
    width: 22px; height: 22px;
    border-radius: 6px;
    background: #e2e8f0;
    color: #64748b;
    font-size: 11px;
    font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ── Player buttons ── */
.player-btn {
    width: 28px; height: 28px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 900;
    border: 2px solid;
    cursor: pointer;
    transition: transform 0.1s, opacity 0.15s;
    flex-shrink: 0;
}
.player-btn:active:not(:disabled) { transform: scale(0.88); }
.player-btn:disabled { opacity: 0.35; cursor: default; }

.p1-btn         { background: #fef2f2; color: #dc2626; border-color: #fca5a5; }
.p1-btn:hover:not(:disabled) { background: #dc2626; color: white; }
.p1-correct     { background: #22c55e !important; color: white !important; border-color: #16a34a !important; }
.p1-locked      { opacity: 0.35; }

.p2-btn         { background: #eff6ff; color: #2563eb; border-color: #93c5fd; }
.p2-btn:hover:not(:disabled) { background: #2563eb; color: white; }
.p2-correct     { background: #22c55e !important; color: white !important; border-color: #16a34a !important; }
.p2-locked      { opacity: 0.35; }

/* ── Result ── */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.88); }
    to   { opacity:1; transform:scale(1); }
}
</style>
