<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const items = computed(() => props.gameData?.items ?? []);
const current   = ref(0);
const selected  = ref(null);
const confirmed = ref(false);
const gameOver  = ref(false);
const won       = ref(false);
const usedLifelines = ref({ fifty: false, skip: false });
const particles = ref([]);

const PRIZES = [
    '100', '200', '300', '500', '1 000',
    '2 000', '4 000', '8 000', '16 000', '32 000',
    '64 000', '125 000', '250 000', '500 000', '1 000 000',
];
const SAFE_LEVELS = [4, 9, 14]; // 1K, 32K, 1M

function getPrize(i) { return PRIZES[Math.min(i, PRIZES.length - 1)]; }
function isSafe(i)   { return SAFE_LEVELS.includes(i); }

const currentItem    = computed(() => items.value[current.value]);
const visibleOptions = ref([]);

function initOptions() {
    visibleOptions.value = currentItem.value?.options?.map((o, i) => ({
        text: o, index: i, hidden: false,
    })) ?? [];
}
initOptions();

/* ── Particles ── */
const COLORS = ['#fbbf24','#f59e0b','#fcd34d','#34d399','#60a5fa'];
function spawnParticles() {
    particles.value = Array.from({ length: 16 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 20 + Math.random() * 60, y: 20 + Math.random() * 60,
        dx: (Math.random() - 0.5) * 150, dy: -60 - Math.random() * 110,
        size: 5 + Math.random() * 8,
    }));
    setTimeout(() => { particles.value = []; }, 800);
}

function select(i) {
    if (confirmed.value || gameOver.value) return;
    selected.value = i;
}

function confirm() {
    if (selected.value === null) return;
    confirmed.value = true;
    const correct = currentItem.value.answer_index;
    if (selected.value === correct) {
        audio.playCorrect();
        spawnParticles();
        setTimeout(() => {
            if (current.value < items.value.length - 1) {
                current.value++;
                selected.value  = null;
                confirmed.value = false;
                initOptions();
            } else {
                won.value     = true;
                gameOver.value = true;
                audio.playComplete();
            }
        }, 1400);
    } else {
        audio.playWrong();
        setTimeout(() => { gameOver.value = true; }, 1400);
    }
}

function useFiftyFifty() {
    if (usedLifelines.value.fifty || confirmed.value) return;
    usedLifelines.value.fifty = true;
    const correct = currentItem.value.answer_index;
    const wrong = visibleOptions.value.filter(o => o.index !== correct && !o.hidden);
    let n = 0;
    for (const o of wrong) { if (n++ >= 2) break; o.hidden = true; }
}

function useSkip() {
    if (usedLifelines.value.skip || confirmed.value) return;
    usedLifelines.value.skip = true;
    if (current.value < items.value.length - 1) {
        current.value++;
        selected.value  = null;
        confirmed.value = false;
        initOptions();
    }
}

function restart() {
    current.value   = 0;
    selected.value  = null;
    confirmed.value = false;
    gameOver.value  = false;
    won.value       = false;
    usedLifelines.value = { fifty: false, skip: false };
    particles.value = [];
    initOptions();
}

const LETTERS = ['A', 'B', 'C', 'D'];

function optState(opt) {
    if (opt.hidden) return 'hidden';
    if (!confirmed.value) return selected.value === opt.index ? 'selected' : 'idle';
    const correct = currentItem.value.answer_index;
    if (opt.index === correct) return 'correct';
    if (opt.index === selected.value) return 'wrong';
    return 'dim';
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ GAME OVER ══ -->
        <div v-if="gameOver" class="max-w-md mx-auto">
            <div class="rounded-3xl overflow-hidden shadow-2xl result-appear"
                :style="won
                    ? 'background:linear-gradient(140deg,#451a03,#92400e,#451a03)'
                    : 'background:linear-gradient(140deg,#0f172a,#1e1b4b)'">
                <div class="px-10 py-14 text-center">
                    <div class="text-7xl mb-4">{{ won ? '🏆' : '😢' }}</div>
                    <h2 class="text-2xl font-black text-white mb-2">
                        {{ won ? 'Tabriklaymiz!' : 'Afsuski...' }}
                    </h2>
                    <div class="text-4xl font-black text-amber-400 mb-1">
                        {{ won ? getPrize(items.length - 1) : getPrize(Math.max(0, current - 1)) }}
                    </div>
                    <div class="text-white/50 text-sm mb-8">
                        {{ won ? 'so\'m yutdingiz!' : `${current + 1}-savolda to'xtadingiz` }}
                    </div>
                    <button @click="restart"
                        class="w-full py-4 rounded-2xl font-black text-black text-lg active:scale-95 transition-all"
                        style="background:linear-gradient(135deg,#f59e0b,#d97706);box-shadow:0 8px 24px rgba(245,158,11,0.4)">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else class="w-full max-w-lg mx-auto space-y-3">

            <!-- Prize + progress -->
            <div class="flex items-center gap-3">
                <div class="flex gap-1 flex-1 overflow-hidden">
                    <div v-for="(_, i) in items" :key="i"
                        :class="[
                            'h-1.5 flex-1 rounded-full transition-all',
                            i < current ? 'bg-amber-400'
                            : i === current ? 'bg-amber-300'
                            : isSafe(i) ? 'bg-amber-500/30'
                            : 'bg-white/20'
                        ]"
                        style="background-color: inherit">
                    </div>
                </div>
                <div class="shrink-0 text-amber-400 font-black text-sm bg-amber-400/10 border border-amber-400/30 px-3 py-1 rounded-full">
                    💰 {{ getPrize(current) }}
                </div>
            </div>

            <!-- Question card -->
            <div :key="current"
                class="relative rounded-3xl overflow-hidden shadow-2xl q-appear"
                style="background:linear-gradient(140deg,#0a0a3a,#1a1a6a,#0a0a3a)">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-20">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{ left: p.x+'%', top: p.y+'%', background: p.color,
                                  width: p.size+'px', height: p.size+'px',
                                  '--dx': p.dx+'px', '--dy': p.dy+'px' }">
                    </div>
                </div>

                <div class="px-5 py-5">
                    <div class="text-amber-400/60 text-[10px] font-black uppercase tracking-widest mb-3">
                        Savol {{ current + 1 }}/{{ items.length }} · Millioner
                    </div>
                    <p class="text-white font-bold text-base sm:text-lg leading-snug text-center mb-2">
                        {{ currentItem?.question }}
                    </p>
                    <!-- Safe level hint -->
                    <div v-if="isSafe(current)" class="text-center">
                        <span class="text-amber-300 text-[10px] font-black bg-amber-400/10 border border-amber-400/20 px-2.5 py-0.5 rounded-full">
                            🔒 Xavfsiz daraja
                        </span>
                    </div>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-2 gap-2 px-4 pb-4">
                    <button
                        v-for="opt in visibleOptions" :key="opt.index"
                        :class="[
                            'flex items-center gap-2 px-3 py-3 rounded-2xl border text-sm font-bold transition-all opt-appear',
                            optState(opt) === 'hidden'   ? 'invisible pointer-events-none'
                            : optState(opt) === 'idle'     ? 'border-white/20 bg-white/5 text-white hover:border-amber-400/60 hover:bg-amber-400/10 cursor-pointer'
                            : optState(opt) === 'selected' ? 'border-amber-400 bg-amber-400/20 text-white'
                            : optState(opt) === 'correct'  ? 'border-emerald-400 bg-emerald-500/25 text-white'
                            : optState(opt) === 'wrong'    ? 'border-red-400 bg-red-500/25 text-white'
                            : 'border-white/10 bg-transparent text-white/30'
                        ]"
                        @click="select(opt.index)"
                    >
                        <span :class="[
                            'w-6 h-6 rounded-full border flex items-center justify-center text-[10px] font-black shrink-0',
                            optState(opt) === 'selected' ? 'border-amber-400 text-amber-400'
                            : optState(opt) === 'correct'  ? 'border-emerald-400 text-emerald-400'
                            : optState(opt) === 'wrong'    ? 'border-red-400 text-red-400'
                            : 'border-white/30 text-white/50'
                        ]">{{ LETTERS[opt.index] }}</span>
                        <span class="flex-1 text-left break-words leading-tight">{{ opt.text }}</span>
                    </button>
                </div>

                <!-- Lifelines + Confirm -->
                <div class="flex items-center gap-2 px-4 pb-4">
                    <button @click="useFiftyFifty"
                        :disabled="usedLifelines.fifty || confirmed"
                        :class="[
                            'px-3 py-2 rounded-xl text-xs font-black border transition-all',
                            usedLifelines.fifty
                                ? 'border-white/10 text-white/20 cursor-not-allowed'
                                : 'border-amber-400/60 text-amber-400 hover:bg-amber-400/10 active:scale-95'
                        ]">
                        50:50
                    </button>
                    <button @click="useSkip"
                        :disabled="usedLifelines.skip || confirmed"
                        :class="[
                            'px-3 py-2 rounded-xl text-xs font-black border transition-all',
                            usedLifelines.skip
                                ? 'border-white/10 text-white/20 cursor-not-allowed'
                                : 'border-blue-400/60 text-blue-400 hover:bg-blue-400/10 active:scale-95'
                        ]">
                        ⏭ O'tkazish
                    </button>
                    <div class="flex-1"></div>
                    <button @click="confirm"
                        :disabled="selected === null || confirmed"
                        :class="[
                            'px-6 py-2 rounded-xl font-black text-sm transition-all active:scale-95',
                            selected !== null && !confirmed
                                ? 'bg-amber-500 hover:bg-amber-400 text-black shadow-lg shadow-amber-500/30'
                                : 'bg-white/10 text-white/25 cursor-not-allowed'
                        ]">
                        Tasdiqlash ✓
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.q-appear { animation: qSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: scale(0.93) translateY(14px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.opt-appear { animation: optIn 0.3s ease both; }
@keyframes optIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.8s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx),var(--dy)) scale(0); opacity: 0; }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.88); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
