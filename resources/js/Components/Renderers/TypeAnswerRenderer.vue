<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const currentIndex = ref(0);
const input        = ref('');
const answered     = ref(false);
const correct      = ref(false);
const score        = ref(0);
const finished     = ref(false);
const inputRef     = ref(null);
const shaking      = ref(false);
const particles    = ref([]);
let pid = 0;
function spawnParticles() {
    const chars = ['⭐','✨','🌟','💫','🎉'];
    for (let i = 0; i < 8; i++) {
        const p = { id: pid++, x: 20 + Math.random() * 60, y: 20 + Math.random() * 50,
            char: chars[Math.floor(Math.random() * chars.length)],
            dx: (Math.random() - 0.5) * 80, dy: -(30 + Math.random() * 50) };
        particles.value = [...particles.value, p];
        setTimeout(() => { particles.value = particles.value.filter(x => x.id !== p.id); }, 900);
    }
}

const total   = computed(() => props.gameData.items?.length ?? 0);
const current = computed(() => props.gameData.items?.[currentIndex.value]);
const pct     = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);

function normalize(s) { return s?.trim().toLowerCase().replace(/\s+/g, ' ') ?? ''; }

function focusInput() {
    nextTick(() => inputRef.value?.focus());
}

watch(currentIndex, () => focusInput());

function check() {
    if (!input.value.trim() || answered.value) return;
    answered.value = true;
    const userAns  = normalize(input.value);
    const corrAns  = normalize(current.value.answer);
    const alts     = (current.value.alternatives ?? []).map(normalize);
    correct.value  = userAns === corrAns || alts.includes(userAns);
    if (correct.value) {
        score.value++;
        playCorrect();
        spawnParticles();
    } else {
        playWrong();
        shaking.value = true;
        setTimeout(() => { shaking.value = false; }, 500);
    }
}

function next() {
    if (currentIndex.value < total.value - 1) {
        currentIndex.value++;
        input.value    = '';
        answered.value = false;
        correct.value  = false;
        focusInput();
    } else {
        finished.value = true;
        playComplete();
    }
}

function restart() {
    currentIndex.value = 0;
    input.value        = '';
    answered.value     = false;
    correct.value      = false;
    score.value        = 0;
    finished.value     = false;
    focusInput();
}
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-4">{{ pct >= 80 ? '🎉' : pct >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-2">{{ pct }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} to'g'ri</div>
                </div>
                <div class="p-8 text-center">
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else-if="current" class="w-full">

            <!-- Progress bar -->
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: (currentIndex / total * 100) + '%' }"></div>
                </div>
                <span class="text-sm font-bold text-slate-400 shrink-0">{{ currentIndex + 1 }}/{{ total }}</span>
                <span class="bg-indigo-100 text-indigo-700 text-xs font-black px-3 py-1 rounded-full shrink-0">{{ score }} ball</span>
            </div>

            <!-- Question card -->
            <div :key="currentIndex" :class="['q-appear', shaking ? 'shake' : '']">

                <!-- Question banner -->
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl p-5 mb-3 shadow-lg shadow-indigo-200/50 relative">
                    <TransitionGroup tag="div" name="particle">
                        <div v-for="p in particles" :key="p.id"
                            class="particle absolute pointer-events-none text-xl leading-none select-none z-10"
                            :style="{ left: p.x + '%', top: p.y + '%', '--dx': p.dx + 'px', '--dy': p.dy + 'px' }">
                            {{ p.char }}
                        </div>
                    </TransitionGroup>
                    <div class="flex items-center gap-2 mb-3">
                        <span class="bg-white/20 text-white text-xs font-black px-3 py-1 rounded-full">
                            ✏️ Savol {{ currentIndex + 1 }}
                        </span>
                    </div>
                    <p class="text-white font-bold text-base sm:text-lg leading-snug">{{ current.question }}</p>
                </div>

                <!-- Answer area -->
                <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 space-y-3">

                    <!-- Input -->
                    <div class="relative">
                        <input
                            ref="inputRef"
                            v-model="input"
                            type="text"
                            :disabled="answered"
                            @keydown.enter="check"
                            placeholder="Javobingizni yozing..."
                            :class="[
                                'w-full rounded-xl border-2 px-4 py-3.5 text-base font-semibold transition-all duration-200 focus:outline-none pr-12',
                                answered
                                    ? correct
                                        ? 'border-emerald-400 bg-emerald-50 text-emerald-800'
                                        : 'border-red-400 bg-red-50 text-red-800'
                                    : 'border-slate-200 bg-white text-slate-800 focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100/50'
                            ]"
                        />
                        <!-- Status icon inside input -->
                        <div v-if="answered" class="absolute right-3 top-1/2 -translate-y-1/2 text-xl">
                            {{ correct ? '✅' : '❌' }}
                        </div>
                        <div v-else-if="input.trim()" class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-300 text-sm font-bold">
                            ↵
                        </div>
                    </div>

                    <!-- Feedback -->
                    <Transition
                        enter-active-class="transition-all duration-300"
                        enter-from-class="opacity-0 -translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="answered && correct"
                            class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 rounded-xl px-4 py-3">
                            <span class="text-2xl shrink-0">🎉</span>
                            <span class="font-bold text-emerald-700">Barakalla! To'g'ri javob!</span>
                        </div>
                        <div v-else-if="answered && !correct"
                            class="flex items-start gap-3 bg-amber-50 border border-amber-200 rounded-xl px-4 py-3">
                            <span class="text-xl shrink-0">💡</span>
                            <div class="text-sm">
                                <div class="text-amber-700 font-semibold mb-0.5">Sizning javobingiz: <span class="line-through opacity-60">{{ input }}</span></div>
                                <div class="text-amber-900 font-bold">To'g'ri javob: {{ current.answer }}</div>
                            </div>
                        </div>
                    </Transition>

                    <!-- Buttons -->
                    <button v-if="!answered"
                        @click="check"
                        :disabled="!input.trim()"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-black py-3.5 rounded-xl transition text-base shadow-lg shadow-indigo-200/50">
                        Tekshirish ✓
                    </button>
                    <button v-else
                        @click="next"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3.5 rounded-xl transition text-base shadow-lg shadow-indigo-200/50 btn-pop">
                        {{ currentIndex < total - 1 ? 'Keyingisi →' : 'Natijani ko\'rish 🎉' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.q-appear { animation: qSlide 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity:0; transform:translateY(14px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

.btn-pop { animation: btnPop 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes btnPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}

.particle { animation: particleFly 0.85s ease-out forwards; }
.particle-enter-active { animation: particleFly 0.85s ease-out forwards; }
.particle-leave-active { opacity: 0; }
@keyframes particleFly {
    0%   { opacity: 1; transform: translate(0, 0) scale(1); }
    100% { opacity: 0; transform: translate(var(--dx), var(--dy)) scale(0.4); }
}

.shake { animation: shake 0.45s ease; }
@keyframes shake {
    0%,100% { transform:translateX(0); }
    15%  { transform:translateX(-7px); }
    30%  { transform:translateX(7px); }
    45%  { transform:translateX(-5px); }
    60%  { transform:translateX(5px); }
    75%  { transform:translateX(-3px); }
    90%  { transform:translateX(3px); }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}
</style>
