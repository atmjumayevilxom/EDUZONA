<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const GRID_SIZE     = 9;
const GAME_DURATION = 60;

// ── State ──────────────────────────────────────────────────────────
const moles         = ref(Array(GRID_SIZE).fill(null)); // { text, correct, uid } | null
const score         = ref(0);
const combo         = ref(0);
const misses        = ref(0);
const timeLeft      = ref(GAME_DURATION);
const started       = ref(false);
const finished      = ref(false);
const currentQIdx   = ref(0);
const hitFlash      = ref([]); // { idx, type: 'good'|'bad' }
const foundAnswers  = ref([]); // correct answer texts hit in current Q
const qTransition   = ref(false);

const items   = computed(() => props.gameData.items ?? []);
const total   = computed(() => items.value.length);
const currentQ = computed(() => items.value[currentQIdx.value] ?? null);
const correctLeft = computed(() => {
    if (!currentQ.value) return 0;
    return currentQ.value.correct_answers.filter(a => !foundAnswers.value.includes(a)).length;
});

let gameTimer  = null;
let moleTimer  = null;
const cellTimers = {};

function shuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

// ── Pool of answers to spawn ────────────────────────────────────────
const spawnPool = ref([]);

function buildPool() {
    if (!currentQ.value) return;
    const remaining = currentQ.value.correct_answers.filter(a => !foundAnswers.value.includes(a));
    const wrongs    = currentQ.value.wrong_answers ?? [];
    spawnPool.value = shuffle([
        ...remaining.map(t => ({ text: t, correct: true })),
        ...wrongs.map(t => ({ text: t, correct: false })),
    ]);
}

function loadQuestion() {
    foundAnswers.value = [];
    // clear moles
    Object.values(cellTimers).forEach(clearTimeout);
    Object.keys(cellTimers).forEach(k => delete cellTimers[k]);
    moles.value = Array(GRID_SIZE).fill(null);
    buildPool();
}

watch(currentQIdx, () => loadQuestion());
onMounted(() => buildPool());

// ── Game flow ──────────────────────────────────────────────────────
function startGame() {
    score.value       = 0;
    combo.value       = 0;
    misses.value      = 0;
    timeLeft.value    = GAME_DURATION;
    currentQIdx.value = 0;
    finished.value    = false;
    started.value     = true;
    hitFlash.value    = [];
    loadQuestion();

    gameTimer = setInterval(() => {
        if (--timeLeft.value <= 0) endGame();
    }, 1000);

    moleTimer = setInterval(spawnMole, 950);
}

function spawnMole() {
    if (finished.value || qTransition.value) return;
    if (!spawnPool.value.length) buildPool();
    if (!spawnPool.value.length) return;

    const emptyCells = moles.value
        .map((m, i) => m === null ? i : -1)
        .filter(i => i >= 0);
    if (emptyCells.length < 2) return; // keep at least one empty

    const cell = emptyCells[Math.floor(Math.random() * emptyCells.length)];
    // pick from pool (random, allow repeats by cycling)
    const mole = { ...spawnPool.value[Math.floor(Math.random() * spawnPool.value.length)], uid: Math.random() };

    const newMoles = [...moles.value];
    newMoles[cell] = mole;
    moles.value = newMoles;

    const uid = mole.uid;
    cellTimers[cell] = setTimeout(() => {
        if (moles.value[cell]?.uid === uid) {
            const m = [...moles.value];
            m[cell] = null;
            moles.value = m;
        }
        delete cellTimers[cell];
    }, 2100);
}

function whack(idx) {
    if (!started.value || finished.value || qTransition.value || !moles.value[idx]) return;
    const mole = moles.value[idx];

    // Remove mole
    clearTimeout(cellTimers[idx]);
    delete cellTimers[idx];
    const newMoles = [...moles.value];
    newMoles[idx] = null;
    moles.value = newMoles;

    if (mole.correct) {
        const pts = 10 + combo.value * 2;
        score.value += pts;
        combo.value++;
        audio.playCorrect();
        addFlash(idx, 'good');

        if (!foundAnswers.value.includes(mole.text)) {
            foundAnswers.value = [...foundAnswers.value, mole.text];
        }

        // Rebuild pool so hit correct answers stop spawning
        buildPool();

        // All correct answers found → next question
        const allFound = currentQ.value.correct_answers.every(a => foundAnswers.value.includes(a));
        if (allFound) {
            qTransition.value = true;
            if (currentQIdx.value < total.value - 1) {
                setTimeout(() => {
                    currentQIdx.value++;
                    qTransition.value = false;
                }, 700);
            } else {
                setTimeout(() => endGame(), 700);
            }
        }
    } else {
        score.value = Math.max(0, score.value - 5);
        combo.value = 0;
        misses.value++;
        audio.playWrong();
        addFlash(idx, 'bad');
    }
}

function addFlash(idx, type) {
    hitFlash.value = [...hitFlash.value, { idx, type }];
    setTimeout(() => {
        hitFlash.value = hitFlash.value.filter(f => f.idx !== idx);
    }, 380);
}

function endGame() {
    clearInterval(gameTimer);
    clearInterval(moleTimer);
    Object.values(cellTimers).forEach(clearTimeout);
    Object.keys(cellTimers).forEach(k => delete cellTimers[k]);
    moles.value = Array(GRID_SIZE).fill(null);
    finished.value = true;
    started.value  = false;
    audio.playComplete();
}

function restart() {
    finished.value = false;
    started.value  = false;
}

onUnmounted(() => {
    clearInterval(gameTimer);
    clearInterval(moleTimer);
    Object.values(cellTimers).forEach(clearTimeout);
});

const timerPct   = computed(() => timeLeft.value / GAME_DURATION);
const timerColor = computed(() => {
    if (timeLeft.value > 20) return '#22c55e';
    if (timeLeft.value > 8)  return '#f59e0b';
    return '#ef4444';
});

const flashFor = (idx) => hitFlash.value.find(f => f.idx === idx);

// Mole card colors (neutral until hit)
const MOLE_COLORS = [
    'from-amber-400 to-orange-500',
    'from-violet-400 to-purple-500',
    'from-cyan-400 to-teal-500',
    'from-pink-400 to-rose-500',
    'from-lime-400 to-green-500',
    'from-blue-400 to-indigo-500',
    'from-red-400 to-pink-500',
    'from-yellow-400 to-amber-500',
    'from-teal-400 to-cyan-500',
];
let colorIdx = 0;
const moleColorMap = {};
function moleColor(uid) {
    if (!moleColorMap[uid]) {
        moleColorMap[uid] = MOLE_COLORS[colorIdx % MOLE_COLORS.length];
        colorIdx++;
    }
    return moleColorMap[uid];
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-12 text-center text-white',
                    score >= total * 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : score >= total * 40 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-slate-600 to-slate-800'
                ]">
                    <div class="text-7xl mb-4">{{ score >= total * 80 ? '🏆' : score >= total * 40 ? '🎯' : '💪' }}</div>
                    <div class="text-6xl font-black mb-2">{{ score }}</div>
                    <div class="text-white/80 text-lg font-semibold">ball to'plandi</div>
                    <div class="flex justify-center gap-6 mt-4 text-sm">
                        <div class="text-center">
                            <div class="text-2xl font-black text-yellow-300">{{ total }}</div>
                            <div class="text-white/60">savol</div>
                        </div>
                        <div class="text-center">
                            <div class="text-2xl font-black text-red-300">{{ misses }}</div>
                            <div class="text-white/60">xato</div>
                        </div>
                    </div>
                </div>
                <div class="p-6 text-center">
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ START SCREEN ══ -->
        <div v-else-if="!started" class="max-w-xl mx-auto">
            <div class="rounded-3xl overflow-hidden shadow-2xl"
                style="background:linear-gradient(135deg,#052e16,#064e3b,#0f172a)">
                <div class="px-10 py-12 text-center text-white">
                    <div class="text-7xl mb-4">🦔</div>
                    <h2 class="text-3xl font-black mb-2">Ko'rsichaqa ur!</h2>
                    <p class="text-white/70 text-base">
                        Savolga to'g'ri javob bo'lgan so'zlarni bosing.<br>
                        <span class="text-red-300 font-semibold">Noto'g'ri javoblardan saqlaning!</span>
                    </p>
                </div>
                <div class="p-6 space-y-3 bg-black/20">
                    <div class="grid grid-cols-3 gap-3 text-center">
                        <div class="bg-white/10 border border-white/20 rounded-xl p-3">
                            <div class="text-2xl font-black text-white">{{ total }}</div>
                            <div class="text-xs text-white/50 font-bold mt-0.5">savol</div>
                        </div>
                        <div class="bg-white/10 border border-white/20 rounded-xl p-3">
                            <div class="text-2xl font-black text-emerald-300">+10</div>
                            <div class="text-xs text-white/50 font-bold mt-0.5">to'g'ri</div>
                        </div>
                        <div class="bg-white/10 border border-white/20 rounded-xl p-3">
                            <div class="text-2xl font-black text-red-300">-5</div>
                            <div class="text-xs text-white/50 font-bold mt-0.5">xato</div>
                        </div>
                    </div>
                    <button @click="startGame"
                        class="w-full bg-gradient-to-r from-green-600 to-emerald-500 hover:from-green-500 hover:to-emerald-400
                               text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-green-500/30">
                        🚀 Boshlash
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else class="space-y-3">

            <!-- Stats bar -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-3 flex items-center gap-3">
                <!-- Score -->
                <div class="flex-1 flex items-center gap-2">
                    <div class="bg-indigo-50 border border-indigo-200 rounded-xl px-3 py-1.5 text-center min-w-[60px]">
                        <div class="text-lg font-black text-indigo-600 leading-none">{{ score }}</div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">ball</div>
                    </div>
                    <div v-if="combo >= 2" class="bg-yellow-50 border border-yellow-200 rounded-xl px-3 py-1.5 text-center">
                        <div class="text-lg font-black text-yellow-500 leading-none">×{{ combo }}</div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">combo</div>
                    </div>
                    <div class="bg-red-50 border border-red-200 rounded-xl px-3 py-1.5 text-center">
                        <div class="text-lg font-black text-red-500 leading-none">{{ misses }}</div>
                        <div class="text-[9px] font-bold text-slate-400 uppercase mt-0.5">xato</div>
                    </div>
                </div>

                <!-- Question progress -->
                <div class="text-xs font-bold text-slate-400">
                    {{ currentQIdx + 1 }}/{{ total }}
                </div>

                <!-- Timer ring -->
                <div class="relative w-12 h-12 shrink-0">
                    <svg class="w-12 h-12 -rotate-90" viewBox="0 0 48 48">
                        <circle cx="24" cy="24" r="20" stroke="#e2e8f0" stroke-width="4" fill="none"/>
                        <circle cx="24" cy="24" r="20"
                            :stroke="timerColor"
                            stroke-width="4" fill="none"
                            stroke-linecap="round"
                            :stroke-dasharray="`${2 * Math.PI * 20}`"
                            :stroke-dashoffset="`${2 * Math.PI * 20 * (1 - timerPct)}`"
                            style="transition: stroke-dashoffset 0.9s linear, stroke 0.5s"/>
                    </svg>
                    <span :class="[
                        'absolute inset-0 flex items-center justify-center font-black text-sm',
                        timeLeft <= 8 ? 'animate-pulse' : ''
                    ]" :style="{ color: timerColor }">
                        {{ timeLeft }}
                    </span>
                </div>
            </div>

            <!-- Current question card -->
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="currentQ" :key="currentQIdx"
                    class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
                    <div class="flex items-start gap-3">
                        <div class="shrink-0 w-7 h-7 rounded-lg bg-green-700 flex items-center justify-center text-white font-black text-xs">
                            {{ currentQIdx + 1 }}
                        </div>
                        <div class="flex-1">
                            <p class="font-bold text-slate-800 text-sm sm:text-base leading-snug">{{ currentQ.question }}</p>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span v-for="ans in currentQ.correct_answers" :key="ans"
                                    :class="[
                                        'text-[11px] font-bold px-2 py-0.5 rounded-full border transition-all duration-300',
                                        foundAnswers.includes(ans)
                                            ? 'bg-emerald-100 border-emerald-300 text-emerald-700 line-through opacity-60'
                                            : 'bg-slate-100 border-slate-200 text-slate-500'
                                    ]">
                                    {{ foundAnswers.includes(ans) ? '✓ ' : '' }}{{ ans }}
                                </span>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-lg font-black text-slate-800">{{ correctLeft }}</div>
                            <div class="text-[10px] text-slate-400 font-bold">qoldi</div>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Grass + Holes grid -->
            <div class="game-field rounded-2xl overflow-hidden p-4">
                <div class="grid grid-cols-3 gap-3">
                    <button
                        v-for="i in GRID_SIZE"
                        :key="i"
                        @click="whack(i - 1)"
                        :disabled="!moles[i - 1]"
                        class="hole-cell relative rounded-2xl overflow-hidden"
                        style="aspect-ratio: 1;"
                    >
                        <!-- Hole background -->
                        <div class="absolute inset-0 hole-bg"></div>
                        <!-- Hole ellipse shadow -->
                        <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-3/4 h-5 bg-black/40 rounded-full blur-sm"></div>

                        <!-- Mole popping up -->
                        <Transition
                            enter-active-class="transition-all duration-180 ease-out"
                            enter-from-class="translate-y-full opacity-0 scale-90"
                            enter-to-class="translate-y-0 opacity-100 scale-100"
                            leave-active-class="transition-all duration-150"
                            leave-from-class="translate-y-0 opacity-100 scale-100"
                            leave-to-class="translate-y-full opacity-0"
                        >
                            <div v-if="moles[i - 1]"
                                class="absolute inset-x-1 bottom-1 top-2 flex flex-col items-center justify-center gap-1.5">
                                <!-- Mole head -->
                                <div :class="[
                                    'mole-head w-10 h-10 sm:w-12 sm:h-12 rounded-full bg-gradient-to-br shadow-lg flex items-center justify-center text-xl shrink-0',
                                    moleColor(moles[i-1].uid)
                                ]">
                                    🦔
                                </div>
                                <!-- Answer text bubble -->
                                <div class="bg-white shadow-md rounded-xl px-2 py-1 text-center max-w-full">
                                    <span class="text-[11px] sm:text-xs font-black text-slate-800 leading-tight break-words line-clamp-2">
                                        {{ moles[i - 1].text }}
                                    </span>
                                </div>
                            </div>
                        </Transition>

                        <!-- Hit flash overlay -->
                        <Transition
                            enter-active-class="transition-opacity duration-80"
                            leave-active-class="transition-opacity duration-300"
                            enter-from-class="opacity-0"
                            leave-to-class="opacity-0"
                        >
                            <div v-if="flashFor(i - 1)"
                                :class="[
                                    'absolute inset-0 flex items-center justify-center text-3xl rounded-2xl',
                                    flashFor(i - 1)?.type === 'good'
                                        ? 'bg-emerald-400/70'
                                        : 'bg-red-400/70'
                                ]">
                                {{ flashFor(i - 1)?.type === 'good' ? '💥' : '❌' }}
                            </div>
                        </Transition>
                    </button>
                </div>
            </div>

        </div>
    </div>
</template>

<style scoped>
.game-field {
    background: linear-gradient(180deg, #15803d 0%, #166534 40%, #92400e 40%, #78350f 100%);
}

.hole-bg {
    background: radial-gradient(ellipse at 50% 60%, #1c0a00 0%, #2d1a0a 50%, #3d2210 100%);
}

.hole-cell {
    cursor: default;
    transition: transform 0.1s;
}
.hole-cell:not(:disabled) {
    cursor: pointer;
}
.hole-cell:not(:disabled):active {
    transform: scale(0.94);
}

.mole-head {
    border: 3px solid rgba(255,255,255,0.3);
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}

.duration-180 { transition-duration: 180ms; }
.duration-80  { transition-duration: 80ms; }
</style>
