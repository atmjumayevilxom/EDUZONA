<script setup>
import { ref, computed, watch, nextTick } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const items       = computed(() => props.gameData?.items ?? []);
const current     = ref(0);
const userInput   = ref('');
const checked     = ref(false);
const correct     = ref(false);
const score       = ref(0);
const finished    = ref(false);
const showHint    = ref(false);
const shakeInput  = ref(false);
const inputRef    = ref(null);

const currentItem = computed(() => items.value[current.value]);
const total       = computed(() => items.value.length);
const percentage  = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);

watch(current, () => {
    userInput.value = '';
    checked.value   = false;
    correct.value   = false;
    showHint.value  = false;
    nextTick(() => inputRef.value?.focus());
});

function checkAnswer() {
    if (!userInput.value.trim()) return;
    checked.value = true;
    const expected = (currentItem.value.word ?? '').trim().toUpperCase();
    const given    = userInput.value.trim().toUpperCase();
    correct.value  = given === expected;
    if (correct.value) {
        score.value++;
        audio.playCorrect();
    } else {
        audio.playWrong();
        shakeInput.value = true;
        setTimeout(() => { shakeInput.value = false; }, 500);
    }
}

function next() {
    if (current.value < total.value - 1) {
        current.value++;
    } else {
        finished.value = true;
        audio.playComplete();
    }
}

function restart() {
    current.value   = 0;
    score.value     = 0;
    finished.value  = false;
    userInput.value = '';
    checked.value   = false;
    nextTick(() => inputRef.value?.focus());
}

// Show masked hint: first 2 letters visible, rest as _
const maskedWord = computed(() => {
    const w = currentItem.value?.word ?? '';
    return w.split('').map((c, i) => (i < 2 ? c.toUpperCase() : '_')).join(' ');
});

// Letter-by-letter comparison for feedback
const letterFeedback = computed(() => {
    if (!checked.value) return [];
    const word  = (currentItem.value?.word ?? '').toUpperCase();
    const input = userInput.value.trim().toUpperCase();
    return word.split('').map((c, i) => ({
        char: c,
        status: input[i] === c ? 'correct' : input[i] ? 'wrong' : 'missing',
    }));
});
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    percentage >= 80 ? 'bg-gradient-to-br from-fuchsia-500 to-pink-600'
                    : percentage >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-3">{{ percentage >= 80 ? '🏆' : percentage >= 50 ? '✏️' : '📚' }}</div>
                    <div class="text-6xl font-black mb-1">{{ percentage }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} to'g'ri</div>
                </div>
                <div class="p-5">
                    <button @click="restart"
                        class="w-full bg-fuchsia-600 hover:bg-fuchsia-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-fuchsia-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <template v-else-if="currentItem">

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-3">
                <div class="flex gap-1 flex-1 overflow-hidden">
                    <div v-for="(_, i) in items" :key="i"
                        :class="[
                            'h-1.5 flex-1 rounded-full transition-all duration-300',
                            i < current ? 'bg-fuchsia-400' : i === current ? 'bg-fuchsia-600' : 'bg-slate-200'
                        ]">
                    </div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ current + 1 }}/{{ total }}</span>
                <div class="bg-fuchsia-100 text-fuchsia-700 text-xs font-black px-3 py-1 rounded-full shrink-0">
                    {{ score }} ball
                </div>
            </div>

            <!-- Card -->
            <div :key="current" class="rounded-3xl overflow-hidden shadow-xl card-appear"
                style="background: linear-gradient(140deg,#701a75,#86198f,#701a75)">

                <div class="px-6 py-6">
                    <!-- Label -->
                    <div class="text-fuchsia-300 text-[10px] font-black uppercase tracking-widest mb-3">
                        ✏️ So'zni yozing — {{ current + 1 }}/{{ total }}
                    </div>

                    <!-- Hint text -->
                    <div class="bg-white/10 rounded-2xl px-4 py-3 mb-3 text-center">
                        <p class="text-white font-bold text-base leading-snug">{{ currentItem.hint }}</p>
                    </div>

                    <!-- Sentence if available -->
                    <div v-if="currentItem.sentence"
                        class="bg-white/8 rounded-xl px-4 py-2.5 mb-3 text-center">
                        <p class="text-white/65 text-sm italic">"{{ currentItem.sentence }}"</p>
                    </div>

                    <!-- Masked hint -->
                    <div class="text-center mb-3 h-6">
                        <button v-if="!showHint && !checked" @click="showHint = true"
                            class="text-xs text-fuchsia-300 hover:text-white underline transition">
                            Birinchi harflarni ko'rsatish
                        </button>
                        <Transition
                            enter-active-class="transition-all duration-300"
                            enter-from-class="opacity-0 scale-75"
                            enter-to-class="opacity-100 scale-100"
                        >
                            <div v-if="showHint" class="font-mono text-fuchsia-200 tracking-[0.25em] text-sm font-bold">
                                {{ maskedWord }}
                            </div>
                        </Transition>
                    </div>

                    <!-- Input -->
                    <div :class="['input-wrap', shakeInput ? 'shake' : '']">
                        <input
                            ref="inputRef"
                            v-model="userInput"
                            @keyup.enter="!checked ? checkAnswer() : next()"
                            :disabled="checked"
                            type="text"
                            placeholder="So'zni yozing..."
                            :class="[
                                'w-full px-4 py-3.5 rounded-2xl text-base font-bold text-center uppercase border-2 outline-none transition-all bg-white/12 placeholder-white/30 text-white',
                                checked
                                    ? correct
                                        ? 'border-emerald-400 bg-emerald-500/20'
                                        : 'border-red-400 bg-red-500/20'
                                    : 'border-white/25 focus:border-fuchsia-300 focus:bg-white/15'
                            ]"
                        />
                    </div>

                    <!-- Letter feedback -->
                    <Transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 translate-y-2"
                        enter-to-class="opacity-100 translate-y-0"
                    >
                        <div v-if="checked && !correct" class="flex justify-center gap-1 mt-2 flex-wrap">
                            <span v-for="(lf, li) in letterFeedback" :key="li"
                                :class="[
                                    'w-7 h-7 flex items-center justify-center rounded-lg text-xs font-black',
                                    lf.status === 'correct' ? 'bg-emerald-500/30 text-emerald-300 border border-emerald-400/50'
                                    : lf.status === 'wrong'   ? 'bg-red-500/30 text-red-300 border border-red-400/50'
                                    : 'bg-white/10 text-white/30 border border-white/15'
                                ]">
                                {{ lf.char }}
                            </span>
                        </div>
                    </Transition>

                    <!-- Feedback text -->
                    <Transition
                        enter-active-class="transition-all duration-250"
                        enter-from-class="opacity-0 scale-90"
                        enter-to-class="opacity-100 scale-100"
                    >
                        <div v-if="checked" class="mt-2 text-center text-sm font-bold">
                            <span v-if="correct" class="text-emerald-300 text-base">✅ To'g'ri! Ajoyib!</span>
                            <span v-else class="text-red-300">
                                ❌ Xato! To'g'risi:
                                <span class="font-mono text-white bg-white/15 px-2 py-0.5 rounded ml-1">
                                    {{ currentItem.word }}
                                </span>
                            </span>
                        </div>
                    </Transition>
                </div>

                <!-- Action button -->
                <div class="px-6 pb-6">
                    <button v-if="!checked" @click="checkAnswer"
                        :disabled="!userInput.trim()"
                        :class="[
                            'w-full py-3.5 rounded-2xl text-sm font-black transition-all',
                            userInput.trim()
                                ? 'bg-white text-fuchsia-700 hover:bg-fuchsia-50 active:scale-95 shadow-lg'
                                : 'bg-white/15 text-white/35 cursor-not-allowed'
                        ]">
                        Tekshirish ✓
                    </button>
                    <button v-else @click="next"
                        class="w-full py-3.5 rounded-2xl bg-white text-fuchsia-700 font-black hover:bg-fuchsia-50 active:scale-95 transition-all shadow-lg text-sm">
                        {{ current < total - 1 ? 'Keyingi →' : 'Natijani ko\'rish 🎯' }}
                    </button>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.card-appear { animation: cardSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes cardSlide {
    from { opacity: 0; transform: translateX(24px) scale(0.97); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}

.shake { animation: inputShake 0.45s ease; }
@keyframes inputShake {
    0%,100% { transform: translateX(0); }
    15% { transform: translateX(-7px); }
    30% { transform: translateX(7px); }
    45% { transform: translateX(-5px); }
    60% { transform: translateX(5px); }
    80% { transform: translateX(-3px); }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
