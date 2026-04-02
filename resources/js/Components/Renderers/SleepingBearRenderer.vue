<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const MAX_LIVES  = 3;
const lives      = ref(MAX_LIVES);
const currentIdx = ref(0);
const selected   = ref(null);
const showResult = ref(false);
const gameOver   = ref(false);
const won        = ref(false);
const showHint   = ref(false);
const score      = ref(0);
const bearState  = ref('sleeping'); // sleeping | stirring | angry | awake
let resultTimer  = null;

const items   = computed(() => props.gameData?.items ?? []);
const current = computed(() => items.value[currentIdx.value] ?? null);
const isLast  = computed(() => currentIdx.value >= items.value.length - 1);

// Bear emoji based on state
const bearEmoji = computed(() => ({
    sleeping: '🐻',
    stirring: '😤',
    angry:    '🤬',
    awake:    '😡',
}[bearState.value]));

// Bear background color based on state
const bearBg = computed(() => ({
    sleeping: 'from-slate-700 to-slate-800',
    stirring: 'from-amber-700 to-amber-800',
    angry:    'from-orange-700 to-orange-800',
    awake:    'from-red-700 to-red-800',
}[bearState.value]));

function updateBearState() {
    const remaining = lives.value;
    if (remaining === MAX_LIVES)      bearState.value = 'sleeping';
    else if (remaining === 2)         bearState.value = 'stirring';
    else if (remaining === 1)         bearState.value = 'angry';
    else                              bearState.value = 'awake';
}

function choose(idx) {
    if (selected.value !== null || showResult.value) return;
    selected.value = idx;
    showResult.value = true;
    showHint.value = false;

    const correct = idx === current.value.answer_index;

    if (correct) {
        score.value++;
        playCorrect();
    } else {
        lives.value = Math.max(0, lives.value - 1);
        updateBearState();
        playWrong();
        showHint.value = true;

        if (lives.value === 0) {
            resultTimer = setTimeout(() => { gameOver.value = true; won.value = false; }, 1000);
            return;
        }
    }

    resultTimer = setTimeout(() => {
        if (isLast.value) {
            gameOver.value = true;
            won.value = true;
            playComplete();
        } else {
            currentIdx.value++;
            selected.value = null;
            showResult.value = false;
            showHint.value = false;
        }
    }, correct ? 1000 : 1800);
}

function restart() {
    clearTimeout(resultTimer);
    lives.value      = MAX_LIVES;
    currentIdx.value = 0;
    selected.value   = null;
    showResult.value = false;
    showHint.value   = false;
    gameOver.value   = false;
    won.value        = false;
    score.value      = 0;
    bearState.value  = 'sleeping';
}

function optionClass(idx) {
    if (!showResult.value) {
        return 'bg-white/10 text-white border-white/20 hover:bg-white/20 hover:border-white/40';
    }
    if (idx === current.value.answer_index) return 'bg-emerald-500 text-white border-emerald-500';
    if (selected.value === idx)             return 'bg-red-500 text-white border-red-500';
    return 'bg-white/5 text-white/30 border-white/10';
}

const progressPercent = computed(() =>
    items.value.length ? Math.round((currentIdx.value / items.value.length) * 100) : 0
);

onUnmounted(() => clearTimeout(resultTimer));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-indigo-950 via-slate-900 to-slate-950 flex flex-col items-center justify-start p-4 sm:p-6">

        <!-- Header -->
        <div class="w-full max-w-xl mb-4">
            <h1 class="text-white font-extrabold text-xl text-center mb-1">{{ gameData.title }}</h1>
            <p v-if="gameData.description" class="text-slate-400 text-sm text-center">{{ gameData.description }}</p>

            <!-- Progress -->
            <div class="mt-3 flex items-center gap-3">
                <div class="flex-1 bg-slate-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full transition-all duration-500"
                         :style="`width: ${progressPercent}%`"></div>
                </div>
                <span class="text-slate-400 text-xs shrink-0">{{ currentIdx + 1 }}/{{ items.length }}</span>
            </div>
        </div>

        <!-- Bear + Lives -->
        <div class="w-full max-w-xl mb-5">
            <div :class="['rounded-3xl p-6 flex items-center gap-5 transition-all duration-500 bg-gradient-to-br', bearBg]">
                <!-- Bear -->
                <div class="relative shrink-0">
                    <div class="text-7xl transition-all duration-300 select-none"
                         :class="bearState !== 'sleeping' ? 'animate-bounce' : ''">
                        {{ bearEmoji }}
                    </div>
                    <!-- Zzz when sleeping -->
                    <div v-if="bearState === 'sleeping'"
                         class="absolute -top-2 -right-2 text-blue-300 text-sm font-bold animate-pulse">
                        zzz
                    </div>
                </div>

                <div class="flex-1">
                    <div class="text-white/60 text-xs font-bold uppercase tracking-widest mb-2">
                        {{ bearState === 'sleeping' ? 'Ayiq uxlamoqda...' : bearState === 'stirring' ? 'Ayiq qo\'zg\'alyapti!' : bearState === 'angry' ? 'Ayiq g\'azablandi!' : '😡 Ayiq uyg\'ondi!' }}
                    </div>
                    <!-- Lives -->
                    <div class="flex items-center gap-2">
                        <span class="text-white/70 text-xs">Hayot:</span>
                        <div class="flex gap-1.5">
                            <span v-for="i in MAX_LIVES" :key="i"
                                  class="text-lg transition-all duration-300"
                                  :class="i <= lives ? 'opacity-100' : 'opacity-20 grayscale'">
                                ❤️
                            </span>
                        </div>
                    </div>
                    <!-- Score -->
                    <div class="mt-1 text-white/60 text-xs">
                        To'g'ri: <span class="text-emerald-300 font-bold">{{ score }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Game over screen -->
        <div v-if="gameOver" class="w-full max-w-xl">
            <div class="bg-white/10 border border-white/20 rounded-3xl p-8 text-center backdrop-blur-sm">
                <div class="text-7xl mb-4">{{ won ? '🎉' : '😡' }}</div>
                <h2 class="text-2xl font-extrabold text-white mb-2">
                    {{ won ? 'Barakallo!' : 'Ayiq uyg\'ondi!' }}
                </h2>
                <p class="text-slate-300 mb-1">
                    {{ won ? 'Ayiqni uyg\'otmadingiz!' : 'Ayiqni uyg\'otib qo\'ydingiz!' }}
                </p>
                <p class="text-slate-400 text-sm mb-6">
                    To'g'ri javoblar: <span class="text-emerald-300 font-bold">{{ score }} / {{ items.length }}</span>
                </p>
                <button @click="restart"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-xl transition">
                    🔄 Qaytadan
                </button>
            </div>
        </div>

        <!-- Question -->
        <div v-else class="w-full max-w-xl">
            <!-- Question card -->
            <div class="bg-white/10 border border-white/20 rounded-2xl p-5 mb-4 backdrop-blur-sm">
                <p class="text-white font-bold text-base sm:text-lg leading-relaxed text-center">
                    {{ current?.question }}
                </p>
            </div>

            <!-- Options -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <button
                    v-for="(opt, oi) in current?.options" :key="oi"
                    @click="choose(oi)"
                    :disabled="showResult"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-xl border text-sm font-semibold text-left transition-all duration-200',
                        optionClass(oi)
                    ]">
                    <span class="w-7 h-7 rounded-lg bg-white/20 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ ['A','B','C','D'][oi] }}
                    </span>
                    {{ opt }}
                </button>
            </div>

            <!-- Result feedback -->
            <div v-if="showResult" class="mt-4">
                <div v-if="selected === current?.answer_index"
                     class="bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-semibold px-4 py-3 rounded-xl text-sm text-center">
                    ✅ To'g'ri! Ayiq hali uxlamoqda 💤
                </div>
                <div v-else class="space-y-2">
                    <div class="bg-red-500/20 border border-red-500/40 text-red-300 font-semibold px-4 py-3 rounded-xl text-sm text-center">
                        ❌ Xato! Ayiq bezovtalandi!
                    </div>
                    <div v-if="showHint && current?.hint"
                         class="bg-amber-500/20 border border-amber-500/40 text-amber-200 px-4 py-3 rounded-xl text-xs">
                        💡 <strong>Maslahat:</strong> {{ current.hint }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
