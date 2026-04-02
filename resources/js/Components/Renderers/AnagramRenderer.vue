<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({
    gameData: { type: Object, required: true },
});

const currentIndex = ref(0);
const userInput    = ref('');
const answered     = ref(false);
const isCorrect    = ref(false);
const score        = ref(0);
const finished     = ref(false);
const showHint     = ref(false);

const currentItem = computed(() => props.gameData.items?.[currentIndex.value]);
const total       = computed(() => props.gameData.items?.length ?? 0);
const progress    = computed(() => total.value ? Math.round((currentIndex.value / total.value) * 100) : 0);
const pct         = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);

const scrambledLetters = computed(() =>
    currentItem.value?.scrambled?.split('') ?? []
);

// Letter cells for answer display
const answerLength = computed(() => currentItem.value?.original?.length ?? 0);
const inputLetters = computed(() => {
    const chars = userInput.value.toUpperCase().split('');
    return Array.from({ length: answerLength.value }, (_, i) => chars[i] ?? '');
});

// Per-cell color after answer
function cellClass(i) {
    if (!answered.value) return '';
    const correct = currentItem.value.original.toUpperCase()[i];
    const given   = userInput.value.toUpperCase()[i] ?? '';
    if (given === correct) return 'correct';
    if (given) return 'wrong';
    return 'empty-after';
}

const { playCorrect, playWrong, playComplete } = useGameAudio();

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

function checkAnswer() {
    if (!userInput.value.trim()) return;
    answered.value  = true;
    isCorrect.value = userInput.value.trim().toUpperCase() === currentItem.value.original.toUpperCase();
    if (isCorrect.value) { score.value++; playCorrect(); spawnParticles(); }
    else { playWrong(); }
}

function next() {
    if (currentIndex.value < total.value - 1) {
        currentIndex.value++;
        userInput.value = '';
        answered.value  = false;
        isCorrect.value = false;
        showHint.value  = false;
    } else {
        finished.value = true;
        playComplete();
    }
}

function restart() {
    currentIndex.value = 0;
    userInput.value    = '';
    answered.value     = false;
    isCorrect.value    = false;
    score.value        = 0;
    finished.value     = false;
    showHint.value     = false;
}
</script>

<template>
    <!-- ══════════ FINISHED ══════════ -->
    <div v-if="finished" class="w-full">
        <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
            <div :class="[
                'px-10 py-12 text-center text-white',
                pct >= 80 ? 'bg-gradient-to-br from-pink-500 to-rose-600'
                : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                : 'bg-gradient-to-br from-orange-500 to-red-600'
            ]">
                <div class="text-7xl mb-4">{{ pct >= 80 ? '🎉' : pct >= 50 ? '👍' : '💪' }}</div>
                <div class="text-6xl font-black mb-2">{{ pct }}%</div>
                <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} to'g'ri</div>
            </div>
            <div class="p-8 text-center">
                <h3 class="text-2xl font-extrabold text-slate-800 mb-6">O'yin tugadi!</h3>
                <button @click="restart"
                    class="w-full bg-pink-600 hover:bg-pink-700 text-white font-bold py-4 rounded-2xl transition text-base shadow-lg shadow-pink-200">
                    Qayta o'ynash ↺
                </button>
            </div>
        </div>
    </div>

    <!-- ══════════ ACTIVE GAME ══════════ -->
    <div v-else-if="currentItem" class="w-full">

        <!-- Progress -->
        <div class="flex items-center gap-4 mb-6">
            <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden">
                <div class="h-2.5 bg-gradient-to-r from-pink-500 to-rose-500 rounded-full transition-all duration-500"
                    :style="{ width: progress + '%' }"></div>
            </div>
            <div class="shrink-0 flex items-center gap-3 text-sm font-semibold">
                <span class="text-slate-500">{{ currentIndex + 1 }} / {{ total }}</span>
                <span class="bg-pink-100 text-pink-700 px-3 py-1 rounded-full text-xs font-bold">
                    Ball: {{ score }}
                </span>
            </div>
        </div>

        <!-- Main card -->
        <div :key="currentIndex" class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden q-appear">

            <!-- ── Scrambled tiles header ── -->
            <div class="bg-gradient-to-br from-pink-500 via-rose-500 to-orange-400 px-6 py-8 text-center relative">
                <TransitionGroup tag="div" name="particle">
                    <div v-for="p in particles" :key="p.id"
                        class="particle absolute pointer-events-none text-xl leading-none select-none z-10"
                        :style="{ left: p.x + '%', top: p.y + '%', '--dx': p.dx + 'px', '--dy': p.dy + 'px' }">
                        {{ p.char }}
                    </div>
                </TransitionGroup>
                <div class="text-xs font-bold text-pink-100 uppercase tracking-widest mb-5">
                    Harflarni tartibga soling
                </div>
                <div class="flex justify-center flex-wrap gap-2 sm:gap-3">
                    <div
                        v-for="(letter, i) in scrambledLetters"
                        :key="i"
                        :style="`animation-delay: ${i * 55}ms`"
                        class="tile-appear relative w-12 h-14 sm:w-14 sm:h-16
                               flex items-center justify-center select-none
                               bg-white rounded-2xl
                               text-rose-600 font-black text-2xl sm:text-3xl
                               shadow-[0_6px_0_0_rgba(0,0,0,0.18),0_2px_8px_rgba(0,0,0,0.15)]
                               border-b-4 border-rose-200/60"
                    >
                        {{ letter.toUpperCase() }}
                    </div>
                </div>
            </div>

            <!-- ── Hint ── -->
            <div v-if="currentItem.hint && showHint"
                class="mx-6 mt-5 flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-2xl p-4 text-sm text-amber-800">
                <span class="text-xl shrink-0">💡</span>
                <span class="leading-relaxed font-medium">{{ currentItem.hint }}</span>
            </div>

            <!-- ── Input section ── -->
            <div class="p-6 sm:p-8 space-y-5">

                <!-- Answer letter cells -->
                <div class="flex justify-center flex-wrap gap-2">
                    <div
                        v-for="(ch, i) in inputLetters"
                        :key="i"
                        :class="['answer-cell', cellClass(i)]"
                    >
                        {{ ch }}
                    </div>
                </div>

                <!-- Hidden text input (drives the cells) -->
                <input
                    v-if="!answered"
                    v-model="userInput"
                    @keyup.enter="checkAnswer"
                    type="text"
                    :maxlength="answerLength"
                    placeholder="Yozing..."
                    class="w-full border-2 border-slate-200 rounded-2xl px-5 py-3.5 text-base font-bold
                           uppercase tracking-widest text-center focus:outline-none
                           focus:border-pink-400 focus:ring-4 focus:ring-pink-100 transition"
                    autofocus
                />

                <!-- Result banner -->
                <div v-if="answered"
                    :class="[
                        'flex items-center gap-3 rounded-2xl px-5 py-4 font-bold text-base',
                        isCorrect
                            ? 'bg-green-50 border-2 border-green-400 text-green-700'
                            : 'bg-red-50 border-2 border-red-400 text-red-700',
                    ]">
                    <span class="text-2xl shrink-0">{{ isCorrect ? '✅' : '❌' }}</span>
                    <span>
                        {{ isCorrect ? "To'g'ri! Ajoyib!" : "Noto'g'ri. To'g'ri javob: " }}
                        <strong v-if="!isCorrect" class="uppercase tracking-widest">{{ currentItem.original }}</strong>
                    </span>
                </div>

                <!-- Actions row -->
                <div class="flex items-center justify-between gap-3">
                    <button
                        v-if="!answered && currentItem.hint"
                        @click="showHint = !showHint"
                        class="text-sm text-amber-600 hover:text-amber-800 font-semibold flex items-center gap-1.5 transition"
                    >
                        <span>💡</span>
                        {{ showHint ? 'Yashirish' : 'Maslahat' }}
                    </button>
                    <div v-else class="flex-1"></div>

                    <button
                        v-if="!answered"
                        @click="checkAnswer"
                        :disabled="!userInput.trim()"
                        class="bg-pink-600 hover:bg-pink-700 disabled:bg-pink-200
                               text-white font-bold px-8 py-3.5 rounded-2xl transition
                               shadow-lg shadow-pink-200 text-sm"
                    >
                        Tekshirish ✓
                    </button>

                    <button
                        v-if="answered"
                        @click="next"
                        class="flex-1 sm:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-bold
                               px-8 py-4 rounded-2xl transition text-base shadow-lg shadow-indigo-200/50"
                    >
                        {{ currentIndex < total - 1 ? 'Keyingi so\'z →' : 'Natija 🎉' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Particles ── */
.particle { animation: particleFly 0.85s ease-out forwards; }
.particle-enter-active { animation: particleFly 0.85s ease-out forwards; }
.particle-leave-active { opacity: 0; }
@keyframes particleFly {
    0%   { opacity: 1; transform: translate(0, 0) scale(1); }
    100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(0.4); }
}

/* Question card slide-in */
.q-appear { animation: qSlide 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: translateX(18px) scale(0.97); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}

/* Scrambled letter tiles pop-in */
.tile-appear { animation: tileIn 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes tileIn {
    from { opacity: 0; transform: scale(0.55) translateY(14px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* Finished screen */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}

/* ── Answer letter cells ── */
.answer-cell {
    width: 2.75rem;
    height: 3.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.75rem;
    font-size: 1.35rem;
    font-weight: 800;
    letter-spacing: 0.05em;
    text-transform: uppercase;
    background: #f8fafc;
    border: 2.5px solid #e2e8f0;
    color: #334155;
    transition: background 0.2s, border-color 0.2s, color 0.2s;
    box-shadow: 0 2px 0 0 #e2e8f0;
}
.answer-cell.correct {
    background: #f0fdf4;
    border-color: #4ade80;
    color: #15803d;
    box-shadow: 0 2px 0 0 #bbf7d0;
}
.answer-cell.wrong {
    background: #fff1f2;
    border-color: #fb7185;
    color: #be123c;
    box-shadow: 0 2px 0 0 #fecdd3;
}
.answer-cell.empty-after {
    background: #fef9c3;
    border-color: #fde047;
    color: #713f12;
}
</style>
