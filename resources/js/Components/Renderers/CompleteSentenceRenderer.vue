<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const currentIndex    = ref(0);
const selected        = ref(null);
const answered        = ref(false);
const correct         = ref(false);
const score           = ref(0);
const finished        = ref(false);
const shuffledOptions = ref([]);

const total   = computed(() => props.gameData.items?.length ?? 0);
const current = computed(() => props.gameData.items?.[currentIndex.value]);
const pct     = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);

function shuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

function loadOptions() {
    const opts = current.value?.options;
    shuffledOptions.value = Array.isArray(opts) && opts.length
        ? shuffle(opts)
        : [current.value?.answer ?? ''];
}

onMounted(() => loadOptions());
watch(currentIndex, () => loadOptions());

// Split sentence on ___
const parts = computed(() => {
    const s = current.value?.sentence ?? '';
    const split = s.split('___');
    return { before: split[0] ?? '', after: split[1] ?? '' };
});

function normalize(s) { return s?.trim().toLowerCase().replace(/\s+/g, ' ') ?? ''; }

function choose(opt) {
    if (answered.value) return;
    selected.value = opt;
    answered.value = true;
    const ans  = normalize(current.value?.answer ?? '');
    const alts = (current.value?.alternatives ?? []).map(normalize);
    correct.value = normalize(opt) === ans || alts.includes(normalize(opt));
    if (correct.value) { score.value++; audio.playCorrect(); spawnParticles(); }
    else               { audio.playWrong(); }
}

function next() {
    if (currentIndex.value < total.value - 1) {
        currentIndex.value++;
        selected.value = null;
        answered.value = false;
        correct.value  = false;
    } else {
        finished.value = true;
        audio.playComplete();
    }
}

function restart() {
    currentIndex.value = 0;
    selected.value     = null;
    answered.value     = false;
    correct.value      = false;
    score.value        = 0;
    finished.value     = false;
}

function optClass(opt) {
    if (!answered.value) return 'opt-idle';
    const isCorrectOpt = normalize(opt) === normalize(current.value?.answer ?? '');
    if (isCorrectOpt)           return 'opt-correct';
    if (opt === selected.value) return 'opt-wrong';
    return 'opt-dim';
}

const LETTERS = ['A', 'B', 'C', 'D', 'E'];

const particles = ref([]);
let pid = 0;
function spawnParticles() {
    const chars = ['⭐','✨','🌟','💫','🎉'];
    for (let i = 0; i < 8; i++) {
        const p = { id: pid++, x: 15 + Math.random() * 70, y: 20 + Math.random() * 50,
            char: chars[Math.floor(Math.random() * chars.length)],
            dx: (Math.random() - 0.5) * 80, dy: -(30 + Math.random() * 50) };
        particles.value = [...particles.value, p];
        setTimeout(() => { particles.value = particles.value.filter(x => x.id !== p.id); }, 900);
    }
}
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-violet-500 to-purple-700'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-4">{{ pct >= 80 ? '🎉' : pct >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-2">{{ pct }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} to'g'ri</div>
                </div>
                <div class="p-8 text-center">
                    <button @click="restart"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-violet-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else-if="current" class="w-full">

            <!-- Progress -->
            <div class="flex items-center gap-4 mb-5">
                <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2.5 bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: (currentIndex / total * 100) + '%' }"></div>
                </div>
                <div class="shrink-0 flex items-center gap-2">
                    <span class="text-slate-500 font-semibold text-sm">{{ currentIndex + 1 }}/{{ total }}</span>
                    <span class="bg-violet-100 text-violet-700 px-3 py-1 rounded-full text-xs font-black">{{ score }} ball</span>
                </div>
            </div>

            <!-- Question card -->
            <div :key="currentIndex" class="q-appear space-y-4">

                <!-- Sentence with blank -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-6 relative">
                    <TransitionGroup tag="div" name="particle">
                        <div v-for="p in particles" :key="p.id"
                            class="particle absolute pointer-events-none text-xl leading-none select-none z-10"
                            :style="{ left: p.x + '%', top: p.y + '%', '--dx': p.dx + 'px', '--dy': p.dy + 'px' }">
                            {{ p.char }}
                        </div>
                    </TransitionGroup>
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-violet-400"></span>
                        Bo'sh joyni to'ldiring
                    </div>

                    <p class="text-lg sm:text-xl font-semibold text-slate-800 leading-relaxed flex flex-wrap items-center gap-x-1.5 gap-y-2">
                        <span>{{ parts.before }}</span>

                        <!-- The blank slot -->
                        <span :class="[
                            'inline-flex items-center px-4 py-1.5 rounded-xl border-2 font-black text-base transition-all duration-300 min-w-[5rem] justify-center',
                            !answered
                                ? 'border-dashed border-violet-300 bg-violet-50 text-violet-300'
                                : correct
                                    ? 'border-emerald-400 bg-emerald-50 text-emerald-700 scale-105'
                                    : 'border-red-400 bg-red-50 text-red-600'
                        ]">
                            <span v-if="!answered" class="text-violet-300">___</span>
                            <span v-else class="flex items-center gap-1">
                                <span v-if="correct">✓</span>
                                <span v-else>✗</span>
                                {{ selected }}
                            </span>
                        </span>

                        <span>{{ parts.after }}</span>
                    </p>

                    <!-- Correct answer hint (after wrong) -->
                    <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="answered && !correct"
                            class="mt-4 flex items-center gap-2 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3 text-sm">
                            <span class="text-lg shrink-0">💡</span>
                            <span class="text-amber-800 font-medium">
                                To'g'ri javob: <strong class="text-amber-900">{{ current.answer }}</strong>
                            </span>
                        </div>
                    </Transition>
                </div>

                <!-- Options -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                        Variantlar
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2.5">
                        <button
                            v-for="(opt, oi) in shuffledOptions"
                            :key="opt"
                            @click="choose(opt)"
                            :disabled="answered"
                            :style="`animation-delay: ${oi * 70}ms`"
                            :class="['opt-btn option-appear', optClass(opt)]"
                        >
                            <span class="opt-letter">{{ LETTERS[oi] }}</span>
                            <span class="flex-1 text-left leading-snug break-words">{{ opt }}</span>
                            <span v-if="answered && normalize(opt) === normalize(current.answer)" class="text-emerald-500 text-lg shrink-0">✓</span>
                            <span v-else-if="answered && opt === selected && normalize(opt) !== normalize(current.answer)" class="text-red-400 text-lg shrink-0">✗</span>
                        </button>
                    </div>
                </div>

                <!-- Next button -->
                <button v-if="answered" @click="next"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-base shadow-lg shadow-indigo-200/50">
                    {{ currentIndex < total - 1 ? 'Keyingisi →' : 'Natijani ko\'rish 🎉' }}
                </button>

            </div>
        </div>
    </div>
</template>

<style scoped>
.particle { animation: particleFly 0.85s ease-out forwards; }
.particle-enter-active { animation: particleFly 0.85s ease-out forwards; }
.particle-leave-active { opacity: 0; }
@keyframes particleFly {
    0%   { opacity: 1; transform: translate(0, 0) scale(1); }
    100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(0.4); }
}

.q-appear { animation: qSlide 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity:0; transform:translateY(14px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}
.option-appear { animation: optIn 0.3s ease both; }
@keyframes optIn {
    from { opacity:0; transform:translateX(10px); }
    to   { opacity:1; transform:translateX(0); }
}

/* Option buttons */
.opt-btn {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.8rem 1rem;
    border-radius: 0.875rem;
    border: 2px solid;
    font-weight: 600;
    font-size: 0.9rem;
    transition: transform 0.12s, box-shadow 0.12s, background 0.12s, border-color 0.12s;
    cursor: pointer;
}
.opt-btn:active:not(:disabled) { transform: scale(0.97); }

.opt-letter {
    width: 1.8rem; height: 1.8rem;
    display: flex; align-items: center; justify-content: center;
    border-radius: 0.5rem;
    font-weight: 900; font-size: 0.75rem;
    flex-shrink: 0;
    transition: background 0.12s, color 0.12s;
}

.opt-idle { background:#f8fafc; border-color:#e2e8f0; color:#334155; box-shadow:0 2px 0 0 #e2e8f0; }
.opt-idle .opt-letter { background:#e2e8f0; color:#64748b; }
.opt-idle:hover:not(:disabled) { background:#f5f3ff; border-color:#a78bfa; color:#5b21b6; box-shadow:0 4px 0 0 #ddd6fe; transform:translateY(-1px); }
.opt-idle:hover .opt-letter { background:#7c3aed; color:white; }

.opt-correct { background:#f0fdf4; border-color:#4ade80; color:#15803d; box-shadow:0 3px 0 0 #86efac; }
.opt-correct .opt-letter { background:#22c55e; color:white; }

.opt-wrong { background:#fff1f2; border-color:#fb7185; color:#be123c; box-shadow:0 3px 0 0 #fda4af; }
.opt-wrong .opt-letter { background:#ef4444; color:white; }

.opt-dim { background:#f8fafc; border-color:#e2e8f0; color:#94a3b8; opacity:0.4; cursor:default; }
.opt-dim .opt-letter { background:#f1f5f9; color:#94a3b8; }
</style>
