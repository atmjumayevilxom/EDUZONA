<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const LIVES      = 3;
const TIME_LIMIT = 15;

const currentIdx = ref(0);
const lives      = ref(LIVES);
const score      = ref(0);
const timeLeft   = ref(TIME_LIMIT);
const answered   = ref(false);
const chosen     = ref(null);
const finished   = ref(false);
const started    = ref(false);
const lostLife   = ref(false);  // flash animation for life loss

let timer = null;

function shuffleItem(item) {
    const opts    = [...(item.options ?? [])];
    const correct = opts[item.answer_index ?? 0];
    const shuffled = opts.sort(() => Math.random() - 0.5);
    return { ...item, options: shuffled, answer_index: shuffled.indexOf(correct) };
}

const items   = ref((props.gameData.items ?? []).map(shuffleItem));
const current = computed(() => items.value[currentIdx.value]);
const total   = computed(() => items.value.length);

function startTimer() {
    clearInterval(timer);
    timer = setInterval(() => {
        timeLeft.value--;
        if (timeLeft.value <= 0) {
            clearInterval(timer);
            lives.value--;
            answered.value = true;
            chosen.value = null;
            triggerLostLife();
            if (lives.value <= 0) {
                setTimeout(() => { finished.value = true; audio.playComplete(); }, 1200);
            } else {
                setTimeout(next, 1600);
            }
        }
    }, 1000);
}

function triggerLostLife() {
    lostLife.value = true;
    setTimeout(() => { lostLife.value = false; }, 600);
}

function start() {
    started.value = true;
    startTimer();
}

function answer(idx) {
    if (answered.value) return;
    clearInterval(timer);
    answered.value = true;
    chosen.value = idx;

    const isCorrect = idx === current.value.answer_index;
    if (isCorrect) {
        audio.playCorrect();
        score.value += 100 + timeLeft.value * 10;
    } else {
        audio.playWrong();
        lives.value--;
        triggerLostLife();
    }

    const delay = isCorrect ? 1200 : 1600;
    setTimeout(() => {
        if (lives.value <= 0) { finished.value = true; audio.playComplete(); return; }
        if (currentIdx.value + 1 >= total.value) { finished.value = true; audio.playComplete(); }
        else { next(); }
    }, delay);
}

function next() {
    currentIdx.value++;
    answered.value = false;
    chosen.value   = null;
    timeLeft.value = TIME_LIMIT;
    if (currentIdx.value < total.value) startTimer();
}

function restart() {
    clearInterval(timer);
    currentIdx.value = 0;
    lives.value      = LIVES;
    score.value      = 0;
    timeLeft.value   = TIME_LIMIT;
    answered.value   = false;
    chosen.value     = null;
    finished.value   = false;
    started.value    = false;
    lostLife.value   = false;
}

onUnmounted(() => clearInterval(timer));

const timerPct   = computed(() => (timeLeft.value / TIME_LIMIT) * 100);
const urgent     = computed(() => timeLeft.value <= 5);
const timerColor = computed(() => {
    if (timeLeft.value > 8) return '#22c55e';
    if (timeLeft.value > 4) return '#f59e0b';
    return '#ef4444';
});

const LABELS = ['A', 'B', 'C', 'D'];
const COLORS = [
    { idle: 'opt-a', correct: 'opt-correct', wrong: 'opt-wrong', dim: 'opt-dim' },
    { idle: 'opt-b', correct: 'opt-correct', wrong: 'opt-wrong', dim: 'opt-dim' },
    { idle: 'opt-c', correct: 'opt-correct', wrong: 'opt-wrong', dim: 'opt-dim' },
    { idle: 'opt-d', correct: 'opt-correct', wrong: 'opt-wrong', dim: 'opt-dim' },
];

function optClass(idx) {
    if (!answered.value) return COLORS[idx].idle;
    if (idx === current.value.answer_index) return COLORS[idx].correct;
    if (idx === chosen.value) return COLORS[idx].wrong;
    return COLORS[idx].dim;
}

const maxScore = computed(() => total.value * (100 + TIME_LIMIT * 10));
const pct      = computed(() => maxScore.value ? Math.round((score.value / maxScore.value) * 100) : 0);
</script>

<template>
    <div class="w-full">

        <!-- ══ START SCREEN ══ -->
        <div v-if="!started" class="max-w-xl mx-auto">
            <div class="show-start rounded-3xl overflow-hidden shadow-2xl">
                <div class="px-8 pt-12 pb-8 text-center">
                    <div class="spotlight mx-auto mb-6 w-24 h-24 flex items-center justify-center rounded-full text-5xl">
                        🎬
                    </div>
                    <h2 class="text-4xl font-black text-white mb-2 tracking-tight">O'YIN SHOU</h2>
                    <p class="text-white/50 text-sm mb-8">Tez javob — ko'p ball!</p>

                    <div class="grid grid-cols-3 gap-3 mb-8">
                        <div class="stat-chip">
                            <span class="text-2xl">❤️</span>
                            <span class="text-white font-black text-lg">{{ LIVES }}</span>
                            <span class="text-white/40 text-xs">jon</span>
                        </div>
                        <div class="stat-chip">
                            <span class="text-2xl">⏱️</span>
                            <span class="text-white font-black text-lg">{{ TIME_LIMIT }}s</span>
                            <span class="text-white/40 text-xs">savol</span>
                        </div>
                        <div class="stat-chip">
                            <span class="text-2xl">⚡</span>
                            <span class="text-white font-black text-lg">×10</span>
                            <span class="text-white/40 text-xs">tezlik</span>
                        </div>
                    </div>

                    <div class="text-white/30 text-xs mb-6">{{ total }} ta savol · maksimum {{ maxScore }} ball</div>

                    <button @click="start"
                        class="start-btn w-full py-4 rounded-2xl font-black text-xl text-white transition">
                        🚀 Boshlash!
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ FINISHED ══ -->
        <div v-else-if="finished" class="max-w-xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-12 text-center text-white',
                    lives > 0 ? 'bg-gradient-to-br from-yellow-400 to-orange-500'
                              : 'bg-gradient-to-br from-slate-600 to-slate-800'
                ]">
                    <div class="text-7xl mb-4">{{ lives > 0 ? '🏆' : '💔' }}</div>
                    <div class="text-5xl font-black mb-1">{{ score }}</div>
                    <div class="text-white/70 text-base mb-3">ball (max {{ maxScore }})</div>
                    <div class="flex justify-center gap-1 mb-2">
                        <span v-for="i in LIVES" :key="i" class="text-2xl">{{ i <= lives ? '❤️' : '🖤' }}</span>
                    </div>
                    <div class="text-white/60 text-sm">{{ lives > 0 ? 'Barcha savollar tugadi!' : 'Jonlar tugadi!' }}</div>
                </div>
                <div class="p-6">
                    <button @click="restart"
                        class="w-full bg-slate-800 hover:bg-slate-900 text-white font-black py-4 rounded-2xl transition text-lg">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else-if="current" class="game-arena rounded-2xl overflow-hidden">

            <!-- Top HUD -->
            <div :class="['hud-bar px-5 pt-4 pb-3', lostLife ? 'hud-shake' : '']">
                <div class="flex items-center justify-between mb-3">
                    <!-- Lives -->
                    <div class="flex items-center gap-1.5">
                        <TransitionGroup name="heart">
                            <span v-for="i in LIVES" :key="i"
                                :class="['text-xl transition-all duration-300', i <= lives ? 'life-active' : 'life-lost']">
                                {{ i <= lives ? '❤️' : '🖤' }}
                            </span>
                        </TransitionGroup>
                    </div>

                    <!-- Question counter -->
                    <div class="text-white/50 text-xs font-black tracking-widest uppercase">
                        {{ currentIdx + 1 }} / {{ total }}
                    </div>

                    <!-- Score -->
                    <div class="text-right">
                        <div class="text-yellow-300 font-black text-xl leading-none">{{ score }}</div>
                        <div class="text-white/30 text-[10px] font-bold">BALL</div>
                    </div>
                </div>

                <!-- Timer bar -->
                <div class="h-2 bg-white/10 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full', urgent ? 'timer-urgent' : '']"
                        :style="{
                            width: timerPct + '%',
                            backgroundColor: timerColor,
                            transition: 'width 0.9s linear, background-color 0.5s'
                        }">
                    </div>
                </div>
                <!-- Timer number -->
                <div class="flex justify-end mt-1">
                    <span :class="['text-xs font-black', urgent ? 'text-red-400 animate-pulse' : 'text-white/40']">
                        {{ timeLeft }}s
                    </span>
                </div>
            </div>

            <!-- Question -->
            <div :key="currentIdx" class="px-5 pb-4 q-appear">
                <div class="question-card rounded-2xl p-5 mb-4">
                    <div class="text-yellow-400/60 text-[10px] font-black uppercase tracking-widest mb-2">Savol {{ currentIdx + 1 }}</div>
                    <p class="text-white font-bold text-base sm:text-lg leading-snug">{{ current.question }}</p>
                </div>

                <!-- Options 2x2 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                    <button
                        v-for="(opt, i) in current.options"
                        :key="i"
                        @click="answer(i)"
                        :disabled="answered"
                        :style="`animation-delay: ${i * 70}ms`"
                        :class="['opt-btn option-appear', optClass(i)]"
                    >
                        <span class="opt-badge">{{ LABELS[i] }}</span>
                        <span class="flex-1 text-left text-sm leading-snug break-words">{{ opt }}</span>
                        <span v-if="answered && i === current.answer_index" class="text-emerald-300 text-base shrink-0">✓</span>
                        <span v-else-if="answered && i === chosen && i !== current.answer_index" class="text-red-300 text-base shrink-0">✗</span>
                    </button>
                </div>

                <!-- Feedback -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-90"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="answered"
                        :class="[
                            'mt-3 rounded-xl px-5 py-3 text-center font-black text-base',
                            chosen === current.answer_index ? 'feedback-correct'
                            : chosen === null              ? 'feedback-timeout'
                            : 'feedback-wrong'
                        ]">
                        <span v-if="chosen === current.answer_index">🎉 To'g'ri! +{{ 100 + timeLeft * 10 }} ball</span>
                        <span v-else-if="chosen === null">⏰ Vaqt tugadi! {{ lives > 0 ? lives + ' jon qoldi' : '' }}</span>
                        <span v-else>❌ Noto'g'ri! {{ lives > 0 ? lives + ' jon qoldi' : 'O\'yin tugadi!' }}</span>
                    </div>
                </Transition>
            </div>

        </div>
    </div>
</template>

<style scoped>
/* ── Arena ── */
.game-arena {
    background: linear-gradient(180deg, #0f0720 0%, #1a0a3c 50%, #0d0d1f 100%);
    border: 1px solid rgba(139,92,246,0.2);
    box-shadow: 0 0 40px rgba(139,92,246,0.15), inset 0 1px 0 rgba(255,255,255,0.05);
}
.hud-bar {
    background: rgba(255,255,255,0.04);
    border-bottom: 1px solid rgba(255,255,255,0.07);
}

/* ── Start screen ── */
.show-start {
    background: linear-gradient(135deg, #0f0720 0%, #1a0a3c 60%, #0d1117 100%);
    border: 1px solid rgba(139,92,246,0.3);
}
.spotlight {
    background: radial-gradient(circle, rgba(250,204,21,0.3) 0%, rgba(250,204,21,0.05) 60%, transparent 100%);
    border: 2px solid rgba(250,204,21,0.3);
    animation: pulse-glow 2s ease-in-out infinite;
}
@keyframes pulse-glow {
    0%,100% { box-shadow: 0 0 20px rgba(250,204,21,0.2); }
    50%     { box-shadow: 0 0 40px rgba(250,204,21,0.5); }
}
.stat-chip {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 16px;
    padding: 12px 8px;
}
.start-btn {
    background: linear-gradient(135deg, #7c3aed, #4f46e5);
    box-shadow: 0 8px 24px rgba(124,58,237,0.5);
    transition: transform 0.1s, box-shadow 0.2s;
}
.start-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 12px 32px rgba(124,58,237,0.6);
}

/* ── Question card ── */
.question-card {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 24px rgba(0,0,0,0.3);
}

/* ── Options ── */
.opt-btn {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 14px 14px;
    border-radius: 14px;
    border: 2px solid;
    font-weight: 600;
    font-size: 0.9rem;
    transition: transform 0.12s, box-shadow 0.12s, background 0.15s, border-color 0.15s;
    cursor: pointer;
}
.opt-btn:active:not(:disabled) { transform: scale(0.97); }
.opt-btn:disabled { cursor: default; }

.opt-badge {
    width: 2rem; height: 2rem;
    display: flex; align-items: center; justify-content: center;
    border-radius: 8px;
    font-weight: 900; font-size: 0.8rem;
    flex-shrink: 0;
    transition: background 0.15s, color 0.15s;
}

/* Idle variants */
.opt-a { background: rgba(79,70,229,0.15); border-color: rgba(79,70,229,0.4); color: #c4b5fd; }
.opt-a .opt-badge { background: rgba(79,70,229,0.3); color: #a5b4fc; }
.opt-a:hover:not(:disabled) { background: rgba(79,70,229,0.3); border-color: #818cf8; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(79,70,229,0.3); }
.opt-a:hover .opt-badge { background: #4f46e5; color: white; }

.opt-b { background: rgba(234,88,12,0.15); border-color: rgba(234,88,12,0.4); color: #fdba74; }
.opt-b .opt-badge { background: rgba(234,88,12,0.3); color: #fb923c; }
.opt-b:hover:not(:disabled) { background: rgba(234,88,12,0.3); border-color: #f97316; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(234,88,12,0.3); }
.opt-b:hover .opt-badge { background: #ea580c; color: white; }

.opt-c { background: rgba(5,150,105,0.15); border-color: rgba(5,150,105,0.4); color: #6ee7b7; }
.opt-c .opt-badge { background: rgba(5,150,105,0.3); color: #34d399; }
.opt-c:hover:not(:disabled) { background: rgba(5,150,105,0.3); border-color: #10b981; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(5,150,105,0.3); }
.opt-c:hover .opt-badge { background: #059669; color: white; }

.opt-d { background: rgba(220,38,38,0.15); border-color: rgba(220,38,38,0.4); color: #fca5a5; }
.opt-d .opt-badge { background: rgba(220,38,38,0.3); color: #f87171; }
.opt-d:hover:not(:disabled) { background: rgba(220,38,38,0.3); border-color: #ef4444; transform: translateY(-2px); box-shadow: 0 6px 20px rgba(220,38,38,0.3); }
.opt-d:hover .opt-badge { background: #dc2626; color: white; }

/* After answer */
.opt-correct { background: rgba(16,185,129,0.25) !important; border-color: #34d399 !important; color: #6ee7b7 !important; box-shadow: 0 0 20px rgba(16,185,129,0.3); }
.opt-correct .opt-badge { background: #10b981 !important; color: white !important; }

.opt-wrong { background: rgba(239,68,68,0.2) !important; border-color: #f87171 !important; color: #fca5a5 !important; }
.opt-wrong .opt-badge { background: #ef4444 !important; color: white !important; }

.opt-dim { background: rgba(255,255,255,0.03) !important; border-color: rgba(255,255,255,0.08) !important; color: rgba(255,255,255,0.2) !important; opacity: 0.6; }
.opt-dim .opt-badge { background: rgba(255,255,255,0.06) !important; color: rgba(255,255,255,0.2) !important; }

/* ── Feedback banners ── */
.feedback-correct { background: rgba(16,185,129,0.2); border: 1px solid rgba(16,185,129,0.4); color: #6ee7b7; }
.feedback-wrong   { background: rgba(239,68,68,0.2);  border: 1px solid rgba(239,68,68,0.4);  color: #fca5a5; }
.feedback-timeout { background: rgba(245,158,11,0.2); border: 1px solid rgba(245,158,11,0.4); color: #fcd34d; }

/* ── Timer urgent pulse ── */
.timer-urgent { animation: timerPulse 0.5s ease-in-out infinite; }
@keyframes timerPulse {
    0%,100% { opacity: 1; }
    50%     { opacity: 0.5; }
}

/* ── HUD shake on life lost ── */
.hud-shake { animation: hudShake 0.5s ease; }
@keyframes hudShake {
    0%,100% { transform: translateX(0); }
    20% { transform: translateX(-6px); }
    40% { transform: translateX(6px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
}

/* ── Life icons ── */
.life-active { animation: heartBeat 0.4s ease; }
.life-lost   { filter: grayscale(1); opacity: 0.4; animation: heartLost 0.5s ease; }
@keyframes heartBeat {
    0%,100% { transform: scale(1); }
    50% { transform: scale(1.3); }
}
@keyframes heartLost {
    0%   { transform: scale(1.3); opacity: 1; }
    100% { transform: scale(0.8); opacity: 0.4; }
}

/* ── Card + option animations ── */
.q-appear { animation: qSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity:0; transform:translateY(16px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.option-appear { animation: optPop 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes optPop {
    from { opacity:0; transform:scale(0.9) translateY(6px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}

/* ── Result ── */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.88); }
    to   { opacity:1; transform:scale(1); }
}
</style>
