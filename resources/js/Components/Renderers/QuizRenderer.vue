<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const currentIndex   = ref(0);
const selectedAnswer = ref(null);
const answered       = ref(false);
const score          = ref(0);
const streak         = ref(0);
const finished       = ref(false);
const particles      = ref([]);
const history        = ref([]); // {correct: bool}

const currentItem = computed(() => props.gameData.items?.[currentIndex.value]);
const total       = computed(() => props.gameData.items?.length ?? 0);
const pct         = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);
const LABELS      = ['A', 'B', 'C', 'D', 'E', 'F'];

/* ── Particles ── */
const COLORS = ['#34d399','#60a5fa','#fbbf24','#f472b6','#a78bfa'];
function spawnParticles() {
    particles.value = Array.from({ length: 14 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 30 + Math.random() * 40,
        y: 20 + Math.random() * 40,
        dx: (Math.random() - 0.5) * 130,
        dy: -60 - Math.random() * 100,
        size: 5 + Math.random() * 7,
    }));
    setTimeout(() => { particles.value = []; }, 750);
}

function selectAnswer(index) {
    if (answered.value) return;
    selectedAnswer.value = index;
    answered.value = true;

    const isCorrect = index === currentItem.value.answer_index;
    history.value.push({ correct: isCorrect });

    if (isCorrect) {
        streak.value++;
        score.value++;
        playCorrect();
        spawnParticles();
    } else {
        streak.value = 0;
        playWrong();
    }
}

function next() {
    if (currentIndex.value < total.value - 1) {
        currentIndex.value++;
        selectedAnswer.value = null;
        answered.value = false;
    } else {
        finished.value = true;
        playComplete();
    }
}

function restart() {
    currentIndex.value   = 0;
    selectedAnswer.value = null;
    answered.value       = false;
    score.value          = 0;
    streak.value         = 0;
    finished.value       = false;
    history.value        = [];
    particles.value      = [];
}

function optionState(index) {
    if (!answered.value) return 'idle';
    if (index === currentItem.value.answer_index) return 'correct';
    if (index === selectedAnswer.value) return 'wrong';
    return 'dim';
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-lg mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-12 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-purple-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-3">{{ pct >= 80 ? '🏆' : pct >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-1">{{ pct }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} to'g'ri</div>
                </div>

                <!-- Progress dots -->
                <div class="flex flex-wrap gap-1.5 justify-center px-6 py-4 border-b border-slate-100">
                    <div v-for="(h, i) in history" :key="i"
                        :class="['w-6 h-6 rounded-full flex items-center justify-center text-[10px] font-black text-white',
                            h.correct ? 'bg-emerald-500' : 'bg-red-400']">
                        {{ h.correct ? '✓' : '✗' }}
                    </div>
                </div>

                <div class="p-5">
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else-if="currentItem" class="w-full max-w-lg mx-auto">

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-3">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: (currentIndex / total * 100) + '%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ currentIndex + 1 }}/{{ total }}</span>
                <div class="bg-indigo-100 text-indigo-700 text-xs font-black px-3 py-1 rounded-full shrink-0">{{ score }} ball</div>
                <Transition name="streak-pop">
                    <div v-if="streak >= 2"
                        class="bg-gradient-to-r from-amber-500 to-red-500 text-white text-xs font-black px-2.5 py-1 rounded-full shrink-0"
                        style="box-shadow:0 2px 8px rgba(245,158,11,0.5)">
                        🔥{{ streak }}
                    </div>
                </Transition>
            </div>

            <!-- Question card -->
            <div :key="currentIndex" class="rounded-3xl overflow-hidden shadow-xl mb-3 q-appear relative">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-30">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{ left: p.x+'%', top: p.y+'%', background: p.color,
                                  width: p.size+'px', height: p.size+'px',
                                  '--dx': p.dx+'px', '--dy': p.dy+'px' }">
                    </div>
                </div>

                <!-- Question header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-700 px-5 py-5">
                    <div class="text-indigo-300 text-[10px] font-black uppercase tracking-widest mb-1.5">
                        Savol {{ currentIndex + 1 }} / {{ total }}
                    </div>
                    <p class="text-white font-bold text-base sm:text-lg leading-snug">
                        {{ currentItem.question }}
                    </p>
                </div>

                <!-- Options -->
                <div class="bg-white p-4 space-y-2">
                    <button
                        v-for="(option, i) in currentItem.options"
                        :key="i"
                        @click="selectAnswer(i)"
                        :disabled="answered"
                        :style="{ animationDelay: (i * 60) + 'ms' }"
                        :class="[
                            'option-btn opt-appear w-full flex items-center gap-3 rounded-2xl px-4 py-3.5 text-left transition-all text-sm font-medium border-2',
                            optionState(i) === 'idle'    ? 'border-slate-200 bg-slate-50 text-slate-700 hover:border-indigo-400 hover:bg-indigo-50 cursor-pointer'
                            : optionState(i) === 'correct' ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                            : optionState(i) === 'wrong'   ? 'border-red-400 bg-red-50 text-red-700'
                            : 'border-slate-100 bg-slate-50/50 text-slate-400'
                        ]"
                    >
                        <span :class="[
                            'shrink-0 w-8 h-8 flex items-center justify-center rounded-xl text-xs font-black',
                            optionState(i) === 'idle'    ? 'bg-slate-200 text-slate-500'
                            : optionState(i) === 'correct' ? 'bg-emerald-500 text-white'
                            : optionState(i) === 'wrong'   ? 'bg-red-400 text-white'
                            : 'bg-slate-100 text-slate-400'
                        ]">{{ LABELS[i] }}</span>
                        <span class="flex-1 leading-snug break-words">{{ option }}</span>
                        <span v-if="optionState(i) === 'correct'" class="text-emerald-500 text-lg shrink-0">✓</span>
                        <span v-else-if="optionState(i) === 'wrong'" class="text-red-400 text-lg shrink-0">✗</span>
                    </button>

                    <!-- Explanation -->
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="answered && currentItem.explanation"
                            class="flex items-start gap-2.5 bg-blue-50 border border-blue-200 rounded-2xl px-4 py-3 text-sm text-blue-800 mt-1">
                            <span class="text-lg shrink-0">💡</span>
                            <span class="leading-relaxed">{{ currentItem.explanation }}</span>
                        </div>
                    </Transition>

                    <!-- Next button -->
                    <Transition
                        enter-active-class="transition-all duration-250"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <button v-if="answered" @click="next"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-black py-3.5 rounded-2xl transition-all text-sm shadow-lg shadow-indigo-200/50 mt-1">
                            {{ currentIndex < total - 1 ? 'Keyingi →' : 'Natijani ko\'rish 🎉' }}
                        </button>
                    </Transition>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.q-appear { animation: qSlide 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: translateX(20px) scale(0.97); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}

.opt-appear { animation: optIn 0.3s ease both; }
@keyframes optIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.option-btn:not(:disabled):hover { transform: translateX(3px); }

.streak-pop-enter-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.streak-pop-enter-from   { opacity: 0; transform: scale(0.3); }
.streak-pop-leave-active { transition: all 0.2s ease; }
.streak-pop-leave-to     { opacity: 0; }

.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.75s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx),var(--dy)) scale(0); opacity: 0; }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
