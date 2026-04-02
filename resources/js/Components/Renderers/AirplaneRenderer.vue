<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const items = computed(() => props.gameData.items ?? []);
const current = ref(0);
const score = ref(0);
const lives = ref(3);
const done = ref(false);
const feedback = ref(null);
const answered = ref(false);
const timeLeft = ref(12);
let timer = null;

const item = computed(() => items.value[current.value] ?? null);
const progress = computed(() => ((current.value) / (items.value.length || 1)) * 100);

// Shuffle option indices each question so correct isn't always index 0
const shuffledOrder = ref([]);
function shuffleOptions() {
    if (!item.value) return;
    const n = item.value.options?.length ?? 0;
    const arr = Array.from({ length: n }, (_, i) => i);
    for (let i = arr.length - 1; i > 0; i--) {
        const j = Math.floor(Math.random() * (i + 1));
        [arr[i], arr[j]] = [arr[j], arr[i]];
    }
    shuffledOrder.value = arr;
}

function startTimer() {
    clearInterval(timer);
    timeLeft.value = 12;
    timer = setInterval(() => {
        timeLeft.value--;
        if (timeLeft.value <= 0) {
            clearInterval(timer);
            if (!answered.value) {
                answered.value = true;
                lives.value--;
                feedback.value = 'timeout';
                advance();
            }
        }
    }, 1000);
}

function advance() {
    setTimeout(() => {
        feedback.value = null;
        answered.value = false;
        if (current.value < items.value.length - 1 && lives.value > 0) {
            current.value++;
            shuffleOptions();
            startTimer();
        } else {
            done.value = true; audio.playComplete();
        }
    }, 1300);
}

function answer(optionIdx) {
    if (answered.value || !item.value) return;
    answered.value = true;
    clearInterval(timer);
    if (optionIdx === item.value.answer_index) {
        score.value += 80 + timeLeft.value * 10;
        feedback.value = 'correct';
        audio.playCorrect();
    } else {
        lives.value--;
        feedback.value = 'wrong';
        audio.playWrong();
    }
    advance();
}

function restart() {
    current.value = 0;
    score.value = 0;
    lives.value = 3;
    done.value = false;
    feedback.value = null;
    answered.value = false;
    shuffleOptions();
    startTimer();
}

// Init
shuffleOptions();
startTimer();
onUnmounted(() => clearInterval(timer));

const CLOUD_POS = [
    'top-8 left-6',
    'top-8 right-6',
    'bottom-10 left-6',
    'bottom-10 right-6',
];
const CLOUD_COLORS = [
    'from-sky-400 to-blue-500',
    'from-purple-400 to-violet-500',
    'from-emerald-400 to-teal-500',
    'from-amber-400 to-orange-500',
];

const timerPct = computed(() => (timeLeft.value / 12) * 100);
const timerColor = computed(() => {
    if (timeLeft.value > 7) return '#22c55e';
    if (timeLeft.value > 3) return '#f59e0b';
    return '#ef4444';
});
</script>

<template>
    <div class="w-full select-none">
        <!-- Result screen -->
        <div v-if="done" class="bg-white rounded-2xl shadow-sm border p-8 text-center">
            <div class="text-6xl mb-4">✈️</div>
            <div class="text-2xl font-extrabold text-indigo-700 mb-1">{{ score }} ball</div>
            <div class="text-gray-500 mb-6">{{ current + 1 }} ta savoldan o'tdingiz</div>
            <button @click="restart"
                class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-2xl transition">
                🔄 Qayta boshlash
            </button>
        </div>

        <div v-else-if="item">
            <!-- Stats row -->
            <div class="flex items-center justify-between mb-3 px-1">
                <div class="flex gap-1">
                    <span v-for="l in 3" :key="l" :class="l <= lives ? 'text-red-500' : 'text-gray-200'" class="text-xl">❤️</span>
                </div>
                <div class="text-indigo-600 font-bold text-sm">{{ score }} ball</div>
                <div class="text-sm text-gray-500">{{ current + 1 }}/{{ items.length }}</div>
            </div>

            <!-- Progress bar -->
            <div class="w-full bg-gray-100 rounded-full h-1.5 mb-4">
                <div class="h-1.5 rounded-full bg-indigo-500 transition-all duration-500" :style="`width:${progress}%`"></div>
            </div>

            <!-- Game arena -->
            <div class="bg-gradient-to-b from-sky-300 to-blue-500 rounded-2xl overflow-hidden shadow-lg"
                style="min-height: 380px; position: relative;">

                <!-- Clouds decorative -->
                <div class="absolute top-4 left-16 w-16 h-8 bg-white/30 rounded-full"></div>
                <div class="absolute top-10 right-20 w-10 h-6 bg-white/20 rounded-full"></div>
                <div class="absolute top-6 right-36 w-8 h-5 bg-white/25 rounded-full"></div>

                <!-- Timer arc top-center -->
                <div :class="['absolute top-3 left-1/2 -translate-x-1/2 flex flex-col items-center z-10', timeLeft <= 3 ? 'animate-pulse' : '']">
                    <svg width="44" height="44" viewBox="0 0 44 44">
                        <circle cx="22" cy="22" r="18" fill="none" stroke="rgba(255,255,255,0.3)" stroke-width="4"/>
                        <circle cx="22" cy="22" r="18" fill="none" :stroke="timerColor" stroke-width="4"
                            stroke-linecap="round"
                            :stroke-dasharray="`${2 * Math.PI * 18}`"
                            :stroke-dashoffset="`${2 * Math.PI * 18 * (1 - timerPct/100)}`"
                            transform="rotate(-90 22 22)" style="transition:stroke-dashoffset 1s linear"/>
                        <text x="22" y="27" text-anchor="middle" font-size="13" font-weight="bold" fill="white">{{ timeLeft }}</text>
                    </svg>
                </div>

                <!-- Question banner -->
                <div class="absolute top-14 left-4 right-4 bg-white/90 backdrop-blur rounded-2xl px-5 py-3 text-center z-10 shadow">
                    <p class="text-gray-800 font-semibold text-base leading-snug">{{ item.question }}</p>
                </div>

                <!-- Answer clouds (4 corners) -->
                <div v-for="(optIdx, pos) in shuffledOrder" :key="optIdx"
                    :class="['absolute w-2/5 z-10', CLOUD_POS[pos]]"
                    :style="`animation-delay: ${pos * 120}ms`"
                    class="cloud-appear">
                    <button
                        @click="answer(optIdx)"
                        :disabled="answered"
                        :class="[
                            'w-full text-white font-bold text-sm py-3 px-2 rounded-2xl shadow-lg bg-gradient-to-br transition-all duration-150 text-center leading-tight',
                            CLOUD_COLORS[pos],
                            answered
                                ? (optIdx === item.answer_index ? 'ring-4 ring-white scale-105' : 'opacity-50')
                                : 'hover:scale-105 active:scale-95 cursor-pointer',
                        ]"
                    >
                        {{ item.options[optIdx] }}
                    </button>
                </div>

                <!-- Airplane at bottom center -->
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 text-5xl z-10"
                    :class="feedback === 'correct' ? 'animate-bounce' : ''">
                    ✈️
                </div>

                <!-- Feedback overlay -->
                <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-75" enter-to-class="opacity-100 scale-100">
                    <div v-if="feedback" class="absolute inset-0 flex items-center justify-center z-20">
                        <div :class="[
                            'rounded-3xl px-10 py-6 text-center shadow-2xl text-white font-extrabold text-2xl',
                            feedback === 'correct' ? 'bg-green-500/90' : 'bg-red-500/90'
                        ]">
                            {{ feedback === 'correct' ? '🎯 To\'g\'ri!' : (feedback === 'timeout' ? '⏱ Vaqt tugadi!' : '❌ Noto\'g\'ri!') }}
                        </div>
                    </div>
                </Transition>
            </div>
        </div>
    </div>
</template>

<style scoped>
.cloud-appear { animation: cloudPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes cloudPop {
    from { opacity: 0; transform: scale(0.5); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
