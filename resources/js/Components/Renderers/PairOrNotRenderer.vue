<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

/* ── State ── */
const qIdx      = ref(0);
const score     = ref(0);
const answered  = ref(false);
const chosen    = ref(null);   // true=pair, false=not
const finished  = ref(false);
const streak    = ref(0);
const maxStreak = ref(0);
const hoverDir  = ref(null);   // 'yes'|'no'|null — for tilt preview
const flyDir    = ref(null);   // 'yes'|'no'|null — card flying away
const particles = ref([]);

/* ── Data ── */
const items = computed(() => {
    // Use is_pair from data directly; if missing — generate fakes like before
    const arr = props.gameData.items ?? [];
    const hasPairField = arr.some(x => x.is_pair !== undefined);
    if (hasPairField) {
        return arr.map(it => ({
            a: it.front ?? it.word ?? it.text ?? '',
            b: it.back  ?? it.hint ?? it.definition ?? '',
            isPair: !!it.is_pair,
        })).sort(() => Math.random() - 0.5);
    }
    // fallback: build pairs + fakes
    const pairs = arr.map(it => ({
        a: it.front ?? it.word ?? it.text ?? '',
        b: it.back  ?? it.hint ?? it.definition ?? '',
        isPair: true,
    }));
    const shuffled = [...arr].sort(() => Math.random() - 0.5);
    const fakes = arr.map((it, i) => {
        const other = shuffled[i];
        const bOther = other.back ?? other.hint ?? other.text ?? '';
        const bMine  = it.back ?? it.hint ?? it.text ?? '';
        if (bOther !== bMine) return {
            a: it.front ?? it.word ?? it.text ?? '',
            b: bOther, isPair: false,
        };
        return null;
    }).filter(Boolean);
    return [...pairs, ...fakes.slice(0, pairs.length)].sort(() => Math.random() - 0.5);
});

const queue   = ref([]);
const total   = computed(() => queue.value.length);
const current = computed(() => queue.value[qIdx.value]);
const next    = computed(() => queue.value[qIdx.value + 1]);
const pct     = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);
const isCorrect = computed(() => answered.value && chosen.value === current.value?.isPair);

function init() {
    queue.value   = items.value;
    qIdx.value    = 0;
    score.value   = 0;
    streak.value  = 0;
    maxStreak.value = 0;
    answered.value  = false;
    chosen.value    = null;
    finished.value  = false;
    flyDir.value    = null;
    hoverDir.value  = null;
    particles.value = [];
}
onMounted(init);

/* ── Particles burst ── */
const COLORS = ['#34d399','#60a5fa','#f59e0b','#f472b6','#a78bfa','#fb923c'];
function spawnParticles() {
    const burst = Array.from({ length: 18 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 40 + Math.random() * 20,
        y: 40 + Math.random() * 20,
        dx: (Math.random() - 0.5) * 160,
        dy: -80 - Math.random() * 120,
        size: 6 + Math.random() * 8,
    }));
    particles.value = burst;
    setTimeout(() => { particles.value = []; }, 800);
}

/* ── Decide ── */
function decide(isPair) {
    if (answered.value || flyDir.value) return;
    answered.value = true;
    chosen.value   = isPair;
    flyDir.value   = isPair ? 'yes' : 'no';
    hoverDir.value = null;

    if (isPair === current.value.isPair) {
        streak.value++;
        if (streak.value > maxStreak.value) maxStreak.value = streak.value;
        const bonus = streak.value >= 3 ? 2 : 1;
        score.value += bonus;
        audio.playCorrect();
        spawnParticles();
    } else {
        streak.value = 0;
        audio.playWrong();
    }

    setTimeout(() => {
        if (qIdx.value + 1 >= queue.value.length) {
            finished.value = true;
            audio.playComplete();
        } else {
            qIdx.value++;
            answered.value = false;
            chosen.value   = null;
            flyDir.value   = null;
        }
    }, 520);
}

/* ── Keyboard ── */
function onKey(e) {
    if (finished.value || answered.value) return;
    if (e.key === 'ArrowRight' || e.key === 'y' || e.key === 'Y') decide(true);
    if (e.key === 'ArrowLeft'  || e.key === 'n' || e.key === 'N') decide(false);
}
onMounted(()  => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

/* ── Card CSS classes ── */
const cardClass = computed(() => {
    if (flyDir.value === 'yes') return 'card-fly-right';
    if (flyDir.value === 'no')  return 'card-fly-left';
    if (hoverDir.value === 'yes') return 'card-tilt-right';
    if (hoverDir.value === 'no')  return 'card-tilt-left';
    return '';
});
const cardOverlay = computed(() => {
    if (flyDir.value === 'yes' || hoverDir.value === 'yes') return 'overlay-yes';
    if (flyDir.value === 'no'  || hoverDir.value === 'no')  return 'overlay-no';
    return '';
});
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 50 ? 'bg-gradient-to-br from-violet-500 to-purple-700'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-3">{{ pct >= 80 ? '🏆' : pct >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-1">{{ pct }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} to'g'ri</div>
                    <div v-if="maxStreak >= 3" class="mt-2 inline-flex items-center gap-1 bg-white/15 rounded-full px-4 py-1.5 text-sm font-bold">
                        🔥 Max seriya: {{ maxStreak }}x
                    </div>
                </div>

                <!-- Stats row -->
                <div class="grid grid-cols-3 divide-x divide-slate-100">
                    <div class="px-4 py-4 text-center">
                        <div class="text-2xl font-black text-slate-800">{{ score }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mt-0.5">Ball</div>
                    </div>
                    <div class="px-4 py-4 text-center">
                        <div class="text-2xl font-black text-slate-800">{{ total - score }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mt-0.5">Xato</div>
                    </div>
                    <div class="px-4 py-4 text-center">
                        <div class="text-2xl font-black text-slate-800">{{ maxStreak }}x</div>
                        <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mt-0.5">Seriya</div>
                    </div>
                </div>

                <div class="p-5">
                    <button @click="init"
                        class="w-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-violet-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else-if="current" class="w-full max-w-md mx-auto">

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-3 px-1">
                <!-- Progress bar -->
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: (qIdx / total * 100) + '%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ qIdx + 1 }}/{{ total }}</span>
                <!-- Score badge -->
                <div class="bg-violet-100 text-violet-700 text-xs font-black px-3 py-1 rounded-full shrink-0">
                    {{ score }} ball
                </div>
                <!-- Streak fire -->
                <Transition name="streak-pop">
                    <div v-if="streak >= 2" class="streak-badge shrink-0">
                        🔥{{ streak }}x
                    </div>
                </Transition>
            </div>

            <!-- Card stack -->
            <div class="card-stack relative" style="height:300px">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-50">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{
                            left: p.x + '%', top: p.y + '%',
                            background: p.color,
                            width: p.size + 'px', height: p.size + 'px',
                            '--dx': p.dx + 'px', '--dy': p.dy + 'px',
                        }">
                    </div>
                </div>

                <!-- Next card (peek behind) -->
                <div v-if="next" class="card-peek absolute inset-x-4 inset-y-2 rounded-3xl"
                    style="background:linear-gradient(135deg,#1e1b4b,#312e81)">
                </div>

                <!-- Current card -->
                <div :class="['main-card absolute inset-0 rounded-3xl overflow-hidden cursor-pointer', cardClass]"
                    :key="qIdx"
                    @mouseenter="hoverDir = null"
                    @mouseleave="hoverDir = null"
                >
                    <!-- Color overlay on hover/fly -->
                    <div v-if="cardOverlay" :class="['card-overlay', cardOverlay]"></div>

                    <!-- Yes / No stamp labels -->
                    <Transition name="stamp">
                        <div v-if="flyDir === 'yes' || hoverDir === 'yes'"
                            class="stamp-label stamp-yes">✔ HA!</div>
                    </Transition>
                    <Transition name="stamp">
                        <div v-if="flyDir === 'no' || hoverDir === 'no'"
                            class="stamp-label stamp-no">✘ YO'Q!</div>
                    </Transition>

                    <!-- Card content -->
                    <div class="relative z-10 h-full flex flex-col p-5">
                        <!-- Header label -->
                        <div class="text-white/40 text-[10px] font-black uppercase tracking-widest mb-3">
                            Bu ikkisi juft (mos) mi?
                        </div>

                        <!-- A panel -->
                        <div :class="['card-panel flex-1 mb-3 transition-all duration-300',
                            answered ? (isCorrect ? 'card-panel-correct' : 'card-panel-wrong') : 'card-panel-idle'
                        ]">
                            <div class="panel-badge">A</div>
                            <p class="text-white font-bold text-sm sm:text-base text-center leading-snug break-words px-1">
                                {{ current.a }}
                            </p>
                        </div>

                        <!-- Connector -->
                        <div class="flex items-center justify-center gap-2 my-1.5">
                            <div class="h-px w-12 bg-white/15"></div>
                            <div :class="[
                                'connector-dot',
                                !answered ? 'connector-idle'
                                : isCorrect && current.isPair ? 'connector-match'
                                : isCorrect && !current.isPair ? 'connector-nomatch'
                                : current.isPair ? 'connector-match' : 'connector-nomatch'
                            ]">
                                <Transition name="icon-flip">
                                    <span :key="answered ? 'ans' : 'q'" class="text-base">
                                        {{ !answered ? '?' : current.isPair ? '🔗' : '✂️' }}
                                    </span>
                                </Transition>
                            </div>
                            <div class="h-px w-12 bg-white/15"></div>
                        </div>

                        <!-- B panel -->
                        <div :class="['card-panel flex-1 mt-1 transition-all duration-300',
                            answered ? (isCorrect ? 'card-panel-correct' : 'card-panel-wrong') : 'card-panel-idle'
                        ]">
                            <div class="panel-badge">B</div>
                            <p class="text-white font-bold text-sm sm:text-base text-center leading-snug break-words px-1">
                                {{ current.b }}
                            </p>
                        </div>

                        <!-- Feedback banner -->
                        <Transition
                            enter-active-class="transition-all duration-250 ease-out"
                            enter-from-class="opacity-0 scale-90 translate-y-1"
                            enter-to-class="opacity-100 scale-100 translate-y-0"
                        >
                            <div v-if="answered"
                                :class="[
                                    'mt-3 rounded-xl px-4 py-2.5 text-center font-bold text-sm',
                                    isCorrect
                                        ? 'bg-emerald-500/25 border border-emerald-400/40 text-emerald-300'
                                        : 'bg-red-500/25 border border-red-400/40 text-red-300'
                                ]"
                            >
                                <span v-if="isCorrect">
                                    ✅ To'g'ri!
                                    <span v-if="streak >= 3" class="ml-1">🔥 +2 ball (seriya)</span>
                                    <span v-else class="text-white/50"> · {{ current.isPair ? 'Juft!' : 'Juft emas!' }}</span>
                                </span>
                                <span v-else>
                                    ❌ Noto'g'ri! Bu ikkisi {{ current.isPair ? 'JUFT' : 'JUFT EMAS' }}
                                </span>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- Swipe Buttons -->
            <div class="grid grid-cols-2 gap-3 mt-3">
                <button
                    @click="decide(false)"
                    @mouseenter="!answered && (hoverDir = 'no')"
                    @mouseleave="hoverDir = null"
                    :disabled="!!answered || !!flyDir"
                    :class="[
                        'swipe-btn swipe-no',
                        answered && chosen === false ? (isCorrect ? 'chosen-correct' : 'chosen-wrong') : '',
                        answered && chosen !== false ? 'unchosen' : '',
                    ]"
                >
                    <span class="text-3xl">✂️</span>
                    <span class="font-black text-lg leading-none">YO'Q</span>
                    <span class="text-xs opacity-60">Juft emas</span>
                    <kbd class="key-hint">← N</kbd>
                </button>

                <button
                    @click="decide(true)"
                    @mouseenter="!answered && (hoverDir = 'yes')"
                    @mouseleave="hoverDir = null"
                    :disabled="!!answered || !!flyDir"
                    :class="[
                        'swipe-btn swipe-yes',
                        answered && chosen === true ? (isCorrect ? 'chosen-correct' : 'chosen-wrong') : '',
                        answered && chosen !== true ? 'unchosen' : '',
                    ]"
                >
                    <span class="text-3xl">🔗</span>
                    <span class="font-black text-lg leading-none">HA!</span>
                    <span class="text-xs opacity-60">Juft / Mos</span>
                    <kbd class="key-hint">Y →</kbd>
                </button>
            </div>

            <!-- Keyboard hint -->
            <p class="text-center text-slate-400 text-xs mt-2 font-medium">
                Klaviatura: <kbd class="kbd">←N</kbd> = Yo'q &nbsp;·&nbsp; <kbd class="kbd">Y→</kbd> = Ha
            </p>
        </div>
    </div>
</template>

<style scoped>
/* ── Card stack ── */
.card-stack { position: relative; }

.card-peek {
    transform: scale(0.94) translateY(6px);
    opacity: 0.5;
    pointer-events: none;
    border: 1px solid rgba(129,140,248,0.2);
}

.main-card {
    background: linear-gradient(140deg, #1e1b4b 0%, #312e81 50%, #1e1b4b 100%);
    border: 1.5px solid rgba(129,140,248,0.25);
    box-shadow: 0 16px 48px rgba(79,70,229,0.3), inset 0 1px 0 rgba(255,255,255,0.05);
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    will-change: transform;
}

/* Card tilt preview */
.card-tilt-right {
    transform: rotate(5deg) translateX(8px);
    box-shadow: 0 20px 60px rgba(16,185,129,0.3);
}
.card-tilt-left {
    transform: rotate(-5deg) translateX(-8px);
    box-shadow: 0 20px 60px rgba(239,68,68,0.3);
}

/* Card fly away */
.card-fly-right {
    animation: flyRight 0.5s cubic-bezier(0.5,-0.5,1,0.5) forwards;
}
.card-fly-left {
    animation: flyLeft 0.5s cubic-bezier(0.5,-0.5,1,0.5) forwards;
}
@keyframes flyRight {
    to { transform: translateX(120%) rotate(25deg); opacity: 0; }
}
@keyframes flyLeft {
    to { transform: translateX(-120%) rotate(-25deg); opacity: 0; }
}

/* Overlay */
.card-overlay {
    position: absolute; inset: 0; z-index: 5; pointer-events: none;
    border-radius: inherit;
    transition: opacity 0.15s;
}
.overlay-yes { background: rgba(16,185,129,0.18); }
.overlay-no  { background: rgba(239,68,68,0.18); }

/* Stamp labels */
.stamp-label {
    position: absolute; z-index: 20;
    font-size: 1.5rem; font-weight: 900;
    letter-spacing: 0.05em;
    border: 3px solid;
    border-radius: 8px;
    padding: 4px 14px;
    transform: rotate(-15deg);
    pointer-events: none;
}
.stamp-yes { top: 20px; right: 20px; color: #34d399; border-color: #34d399; background: rgba(16,185,129,0.1); transform: rotate(12deg); }
.stamp-no  { top: 20px; left: 20px;  color: #f87171; border-color: #f87171; background: rgba(239,68,68,0.1); transform: rotate(-12deg); }

.stamp-enter-active { transition: all 0.15s cubic-bezier(0.34,1.56,0.64,1); }
.stamp-enter-from   { opacity: 0; transform: scale(0.5) rotate(-20deg) !important; }
.stamp-leave-active { transition: all 0.1s ease; }
.stamp-leave-to     { opacity: 0; }

/* ── Card panels ── */
.card-panel {
    border-radius: 14px;
    padding: 14px 12px;
    display: flex; flex-direction: column; align-items: center; gap: 8px;
    justify-content: center;
    min-height: 70px;
}
.card-panel-idle    { background: rgba(255,255,255,0.07); border: 1.5px solid rgba(255,255,255,0.1); }
.card-panel-correct { background: rgba(16,185,129,0.2);  border: 1.5px solid rgba(52,211,153,0.5);  }
.card-panel-wrong   { background: rgba(239,68,68,0.12);  border: 1.5px solid rgba(248,113,113,0.3); }

.panel-badge {
    width: 24px; height: 24px;
    border-radius: 6px;
    background: rgba(255,255,255,0.12);
    color: rgba(255,255,255,0.55);
    font-size: 10px; font-weight: 900;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
}

/* ── Connector ── */
.connector-dot {
    width: 40px; height: 40px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 900;
    transition: all 0.25s ease;
}
.connector-idle    { background: rgba(255,255,255,0.08); color: rgba(255,255,255,0.4); font-size: 15px; }
.connector-match   { background: rgba(16,185,129,0.3);  border: 2px solid rgba(52,211,153,0.6); }
.connector-nomatch { background: rgba(239,68,68,0.25);  border: 2px solid rgba(248,113,113,0.5); }

.icon-flip-enter-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.icon-flip-enter-from   { opacity: 0; transform: rotateY(-90deg) scale(0.5); }
.icon-flip-leave-active { transition: all 0.15s ease; }
.icon-flip-leave-to     { opacity: 0; transform: rotateY(90deg); }

/* ── Swipe buttons ── */
.swipe-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 2px;
    padding: 16px 12px;
    border-radius: 20px;
    border: 2.5px solid;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}
.swipe-btn:active:not(:disabled) { transform: scale(0.95); }
.swipe-btn:disabled { cursor: default; }

.swipe-no {
    background: rgba(239,68,68,0.1); border-color: rgba(248,113,113,0.35);
    color: #fca5a5;
    box-shadow: 0 4px 20px rgba(239,68,68,0.12);
}
.swipe-no:hover:not(:disabled) {
    background: rgba(239,68,68,0.22); border-color: #f87171;
    transform: translateY(-3px) rotate(-2deg);
    box-shadow: 0 10px 30px rgba(239,68,68,0.25);
}

.swipe-yes {
    background: rgba(16,185,129,0.1); border-color: rgba(52,211,153,0.35);
    color: #6ee7b7;
    box-shadow: 0 4px 20px rgba(16,185,129,0.12);
}
.swipe-yes:hover:not(:disabled) {
    background: rgba(16,185,129,0.22); border-color: #34d399;
    transform: translateY(-3px) rotate(2deg);
    box-shadow: 0 10px 30px rgba(16,185,129,0.25);
}

.chosen-correct { background: rgba(16,185,129,0.3) !important; border-color: #34d399 !important; }
.chosen-wrong   { background: rgba(239,68,68,0.3)  !important; border-color: #f87171 !important; }
.unchosen { opacity: 0.3; }

.key-hint {
    position: absolute; top: 5px; right: 8px;
    font-size: 9px; font-weight: 800; opacity: 0.3;
    font-family: monospace;
}

/* ── Streak badge ── */
.streak-badge {
    background: linear-gradient(135deg,#f59e0b,#ef4444);
    color: white; font-size: 11px; font-weight: 900;
    padding: 3px 10px; border-radius: 999px;
    box-shadow: 0 2px 10px rgba(245,158,11,0.5);
    animation: strkPulse 0.6s ease infinite alternate;
}
@keyframes strkPulse {
    from { transform: scale(1); }
    to   { transform: scale(1.08); }
}
.streak-pop-enter-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.streak-pop-enter-from   { opacity: 0; transform: scale(0.3); }
.streak-pop-leave-active { transition: all 0.2s ease; }
.streak-pop-leave-to     { opacity: 0; transform: scale(0.5); }

/* ── Particles ── */
.particle {
    position: absolute;
    border-radius: 50%;
    animation: particleBurst 0.8s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx), var(--dy)) scale(0); opacity: 0; }
}

/* ── Keyboard KBD ── */
kbd.kbd {
    background: rgba(0,0,0,0.08);
    border: 1px solid rgba(0,0,0,0.12);
    border-radius: 3px;
    padding: 1px 5px;
    font-size: 10px; font-family: monospace;
}

/* ── Animations ── */
.main-card { animation: cardAppear 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes cardAppear {
    from { opacity: 0; transform: scale(0.92) translateY(16px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
