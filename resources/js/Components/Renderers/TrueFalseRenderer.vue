<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });

const currentIndex = ref(0);
const answered     = ref(false);
const selected     = ref(null);
const score        = ref(0);
const finished     = ref(false);

const { playCorrect, playWrong, playComplete } = useGameAudio();
const total   = computed(() => props.gameData.items?.length ?? 0);
const current = computed(() => props.gameData.items?.[currentIndex.value]);
const pct     = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);

const particles = ref([]);
let pid = 0;
function spawnParticles() {
    const chars = ['⭐','✨','🌟','💫','🎉','✅'];
    for (let i = 0; i < 8; i++) {
        const p = { id: pid++, x: 20 + Math.random() * 60, y: 30 + Math.random() * 40,
            char: chars[Math.floor(Math.random() * chars.length)],
            dx: (Math.random() - 0.5) * 80, dy: -(30 + Math.random() * 50) };
        particles.value = [...particles.value, p];
        setTimeout(() => { particles.value = particles.value.filter(x => x.id !== p.id); }, 900);
    }
}

function choose(val) {
    if (answered.value) return;
    selected.value = val;
    answered.value = true;
    if (val === current.value.answer) { score.value++; playCorrect(); spawnParticles(); }
    else { playWrong(); }
}

function next() {
    if (currentIndex.value < total.value - 1) {
        currentIndex.value++;
        answered.value = false;
        selected.value = null;
    } else {
        finished.value = true;
        playComplete();
    }
}

function restart() {
    currentIndex.value = 0;
    answered.value = false;
    selected.value = null;
    score.value    = 0;
    finished.value = false;
}

// Keyboard: T = to'g'ri, F = noto'g'ri, Enter/Space = keyingi
function onKey(e) {
    if (e.key === 't' || e.key === 'T') choose(true);
    if (e.key === 'f' || e.key === 'F') choose(false);
    if ((e.key === 'Enter' || e.key === ' ') && answered.value) next();
}
onMounted(() => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

// Button state helpers
function isWinner(val) { return answered.value && val === current.value?.answer; }
function isLoser(val)  { return answered.value && val === selected.value && val !== current.value?.answer; }
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-8xl mb-5">{{ pct >= 80 ? '🎉' : pct >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-7xl font-black mb-2">{{ pct }}%</div>
                    <div class="text-white/80 text-xl font-semibold">{{ score }} / {{ total }} to'g'ri</div>
                </div>
                <div class="p-10 text-center">
                    <h3 class="text-3xl font-extrabold text-slate-800 mb-8">Tugadi!</h3>
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-5 rounded-2xl transition text-xl shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else-if="current" class="max-w-3xl mx-auto">

            <!-- Progress + score -->
            <div class="flex items-center gap-4 mb-6">
                <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2.5 bg-gradient-to-r from-emerald-400 to-green-500 rounded-full transition-all duration-500"
                        :style="`width:${(currentIndex / total) * 100}%`"></div>
                </div>
                <div class="shrink-0 flex items-center gap-3">
                    <span class="text-slate-500 font-semibold text-sm">{{ currentIndex + 1 }} / {{ total }}</span>
                    <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-xs font-black">
                        {{ score }} ball
                    </span>
                </div>
            </div>

            <!-- Statement card -->
            <div :key="currentIndex" class="q-appear space-y-4">

                <!-- Statement -->
                <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden">
                    <div class="bg-gradient-to-br from-slate-700 to-slate-900 px-8 py-8 relative">
                        <!-- Particles -->
                        <TransitionGroup tag="div" name="particle">
                            <div v-for="p in particles" :key="p.id"
                                class="particle absolute pointer-events-none text-xl leading-none select-none z-10"
                                :style="{ left: p.x + '%', top: p.y + '%', '--dx': p.dx + 'px', '--dy': p.dy + 'px' }">
                                {{ p.char }}
                            </div>
                        </TransitionGroup>
                        <div class="flex items-center gap-2 mb-4">
                            <span class="text-slate-400 text-xs font-bold uppercase tracking-widest">Ibora</span>
                            <span class="ml-auto text-slate-500 text-xs font-semibold">T = To'g'ri · F = Noto'g'ri</span>
                        </div>
                        <p class="text-xl sm:text-2xl font-bold text-white leading-relaxed text-center">
                            {{ current.statement }}
                        </p>
                    </div>

                    <!-- True / False buttons -->
                    <div class="p-5 grid grid-cols-2 gap-4">
                        <!-- TRUE -->
                        <button
                            @click="choose(true)"
                            :disabled="answered"
                            :class="[
                                'tf-btn',
                                !answered          ? 'tf-idle tf-true-idle'   : '',
                                isWinner(true)     ? 'tf-winner tf-true-win'  : '',
                                isLoser(true)      ? 'tf-loser'               : '',
                                answered && !isWinner(true) && !isLoser(true) ? 'tf-neutral' : '',
                            ]"
                        >
                            <span class="tf-icon">✅</span>
                            <span class="tf-label">To'g'ri</span>
                            <span class="tf-key">T</span>
                        </button>

                        <!-- FALSE -->
                        <button
                            @click="choose(false)"
                            :disabled="answered"
                            :class="[
                                'tf-btn',
                                !answered          ? 'tf-idle tf-false-idle'  : '',
                                isWinner(false)    ? 'tf-winner tf-false-win' : '',
                                isLoser(false)     ? 'tf-loser'               : '',
                                answered && !isWinner(false) && !isLoser(false) ? 'tf-neutral' : '',
                            ]"
                        >
                            <span class="tf-icon">❌</span>
                            <span class="tf-label">Noto'g'ri</span>
                            <span class="tf-key">F</span>
                        </button>
                    </div>

                    <!-- Explanation -->
                    <Transition
                        enter-active-class="transition-all duration-400 ease-out"
                        enter-from-class="opacity-0 -translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="answered && current.explanation"
                            class="mx-5 mb-5 flex items-start gap-3 bg-sky-50 border border-sky-200 rounded-2xl p-4 text-sm text-sky-800">
                            <span class="text-xl shrink-0 mt-0.5">💡</span>
                            <span class="leading-relaxed font-medium">{{ current.explanation }}</span>
                        </div>
                    </Transition>

                    <!-- Next button -->
                    <div v-if="answered" class="px-5 pb-5">
                        <button @click="next"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-indigo-200/50">
                            {{ currentIndex < total - 1 ? 'Keyingisi →' : 'Natijani ko\'rish 🎉' }}
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>

<style scoped>
/* Card slide-in */
.q-appear { animation: qSlide 0.38s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: translateY(16px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* Finished pop */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}

/* ── Base button ── */
.tf-btn {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 1.75rem 1rem;
    border-radius: 1.25rem;
    border: 2.5px solid transparent;
    transition: transform 0.15s, box-shadow 0.15s, background 0.15s, border-color 0.15s, opacity 0.15s;
    cursor: pointer;
}
.tf-btn:active:not(:disabled) { transform: scale(0.96); }

.tf-icon { font-size: 2.5rem; line-height: 1; }
.tf-label { font-size: 1.15rem; font-weight: 800; }

/* Keyboard hint badge */
.tf-key {
    position: absolute;
    top: 0.5rem;
    right: 0.6rem;
    font-size: 0.65rem;
    font-weight: 700;
    background: rgba(0,0,0,0.08);
    color: inherit;
    padding: 0.15rem 0.4rem;
    border-radius: 0.4rem;
    opacity: 0.6;
}

/* Idle states */
.tf-true-idle {
    background: #f0fdf4;
    border-color: #86efac;
    color: #166534;
    box-shadow: 0 4px 0 0 #bbf7d0;
}
.tf-true-idle:hover:not(:disabled) {
    background: #dcfce7;
    border-color: #4ade80;
    box-shadow: 0 6px 0 0 #86efac, 0 0 20px rgba(74,222,128,0.25);
    transform: translateY(-2px);
}
.tf-false-idle {
    background: #fff1f2;
    border-color: #fda4af;
    color: #9f1239;
    box-shadow: 0 4px 0 0 #fecdd3;
}
.tf-false-idle:hover:not(:disabled) {
    background: #ffe4e6;
    border-color: #fb7185;
    box-shadow: 0 6px 0 0 #fda4af, 0 0 20px rgba(251,113,133,0.25);
    transform: translateY(-2px);
}

/* Winner — glow + scale */
.tf-winner { transform: scale(1.04); cursor: default; }
.tf-true-win  {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-color: #15803d;
    color: #fff;
    box-shadow: 0 6px 0 0 #166534, 0 0 28px rgba(34,197,94,0.45);
}
.tf-false-win {
    background: linear-gradient(135deg, #22c55e, #16a34a);
    border-color: #15803d;
    color: #fff;
    box-shadow: 0 6px 0 0 #166534, 0 0 28px rgba(34,197,94,0.45);
}

/* Loser — shake + dim */
.tf-loser {
    background: linear-gradient(135deg, #ef4444, #dc2626);
    border-color: #b91c1c;
    color: #fff;
    opacity: 0.9;
    animation: shake 0.4s ease;
    cursor: default;
}
@keyframes shake {
    0%,100% { transform: translateX(0); }
    20%      { transform: translateX(-6px); }
    40%      { transform: translateX(6px); }
    60%      { transform: translateX(-4px); }
    80%      { transform: translateX(4px); }
}

/* ── Particles ── */
.particle { animation: particleFly 0.85s ease-out forwards; }
.particle-enter-active { animation: particleFly 0.85s ease-out forwards; }
.particle-leave-active { opacity: 0; }
@keyframes particleFly {
    0%   { opacity: 1; transform: translate(0, 0) scale(1); }
    100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(0.4); }
}

/* Neutral (not selected, not correct) */
.tf-neutral {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #94a3b8;
    opacity: 0.45;
    cursor: default;
}
</style>
