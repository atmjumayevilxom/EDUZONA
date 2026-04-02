<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

// Rope position: -5 (team B wins) to +5 (team A wins), 0 = center
const ROPE_MAX = 5;
const ropePos     = ref(0);
const currentIdx  = ref(0);
const selected    = ref(null);
const showResult  = ref(false);
const gameOver    = ref(false);
const winner      = ref(null);   // 'a' | 'b' | null
const score       = ref({ a: 0, b: 0 });
let resultTimer   = null;

const items   = computed(() => props.gameData?.items ?? []);
const teamA   = computed(() => props.gameData?.team_a ?? 'Yashillar');
const teamB   = computed(() => props.gameData?.team_b ?? 'Qizillar');
const current = computed(() => items.value[currentIdx.value] ?? null);
const isLast  = computed(() => currentIdx.value >= items.value.length - 1);

// Rope visual: convert ropePos (-5..+5) to percentage (0..100%)
const ropePercent = computed(() => ((ropePos.value + ROPE_MAX) / (ROPE_MAX * 2)) * 100);
const flagX       = computed(() => `${ropePercent.value}%`);

function choose(idx) {
    if (selected.value !== null || showResult.value) return;
    selected.value = idx;
    showResult.value = true;

    const correct = idx === current.value.answer_index;
    if (correct) {
        ropePos.value = Math.min(ROPE_MAX, ropePos.value + 1);
        score.value.a++;
        playCorrect();
    } else {
        ropePos.value = Math.max(-ROPE_MAX, ropePos.value - 1);
        score.value.b++;
        playWrong();
    }

    // Check win condition
    if (ropePos.value >= ROPE_MAX) {
        resultTimer = setTimeout(() => { winner.value = 'a'; gameOver.value = true; playComplete(); }, 800);
        return;
    }
    if (ropePos.value <= -ROPE_MAX) {
        resultTimer = setTimeout(() => { winner.value = 'b'; gameOver.value = true; playWrong(); }, 800);
        return;
    }

    resultTimer = setTimeout(() => {
        if (isLast.value) {
            // Game ends by question count
            winner.value = ropePos.value > 0 ? 'a' : ropePos.value < 0 ? 'b' : 'draw';
            gameOver.value = true;
            if (winner.value === 'a') playComplete(); else playWrong();
        } else {
            currentIdx.value++;
            selected.value = null;
            showResult.value = false;
        }
    }, 1200);
}

function restart() {
    clearTimeout(resultTimer);
    ropePos.value    = 0;
    currentIdx.value = 0;
    selected.value   = null;
    showResult.value = false;
    gameOver.value   = false;
    winner.value     = null;
    score.value      = { a: 0, b: 0 };
}

function optionClass(idx) {
    if (!showResult.value) {
        return selected.value === idx
            ? 'bg-indigo-600 text-white border-indigo-600'
            : 'bg-white/10 text-white border-white/20 hover:bg-white/20 hover:border-white/40';
    }
    if (idx === current.value.answer_index) return 'bg-emerald-500 text-white border-emerald-500';
    if (selected.value === idx)             return 'bg-red-500 text-white border-red-500';
    return 'bg-white/5 text-white/40 border-white/10';
}

onUnmounted(() => clearTimeout(resultTimer));
</script>

<template>
    <div class="min-h-screen bg-gradient-to-b from-slate-900 via-indigo-950 to-slate-900 flex flex-col overflow-hidden">

        <!-- Teams header -->
        <div class="flex items-stretch">
            <div class="flex-1 bg-emerald-700/80 px-6 py-4 flex items-center gap-3">
                <span class="text-3xl">🟢</span>
                <div>
                    <div class="text-white font-extrabold text-lg">{{ teamA }}</div>
                    <div class="text-emerald-200 text-sm font-semibold">{{ score.a }} to'g'ri</div>
                </div>
            </div>
            <div class="bg-slate-800/60 px-4 py-4 flex flex-col items-center justify-center text-center shrink-0">
                <div class="text-white font-bold text-xs uppercase tracking-widest opacity-60">Savol</div>
                <div class="text-white font-extrabold text-xl">{{ currentIdx + 1 }}/{{ items.length }}</div>
            </div>
            <div class="flex-1 bg-red-700/80 px-6 py-4 flex items-center justify-end gap-3 text-right">
                <div>
                    <div class="text-white font-extrabold text-lg">{{ teamB }}</div>
                    <div class="text-red-200 text-sm font-semibold">{{ score.b }} xato</div>
                </div>
                <span class="text-3xl">🔴</span>
            </div>
        </div>

        <!-- Rope visual -->
        <div class="relative py-6 px-8 bg-gradient-to-b from-amber-950/40 to-transparent">
            <!-- Ground line -->
            <div class="relative h-10 flex items-center">
                <!-- Rope -->
                <div class="absolute inset-x-8 top-1/2 -translate-y-1/2 h-3 rounded-full overflow-hidden"
                     style="background: repeating-linear-gradient(90deg, #a16207 0px, #854d0e 8px, #a16207 16px)">
                </div>
                <!-- Danger zones -->
                <div class="absolute left-8 top-1/2 -translate-y-1/2 w-10 h-3 bg-emerald-500/40 rounded-l-full"></div>
                <div class="absolute right-8 top-1/2 -translate-y-1/2 w-10 h-3 bg-red-500/40 rounded-r-full"></div>
                <!-- Center marker -->
                <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-white/30 -translate-x-1/2"></div>

                <!-- Flag / puller -->
                <div class="absolute top-1/2 -translate-y-1/2 -translate-x-1/2 transition-all duration-500 ease-out z-10"
                     :style="`left: ${flagX}`">
                    <div class="w-10 h-10 rounded-full border-4 flex items-center justify-center text-xl shadow-lg transition-colors duration-300"
                         :class="ropePos > 0 ? 'bg-emerald-500 border-emerald-300' : ropePos < 0 ? 'bg-red-500 border-red-300' : 'bg-amber-500 border-amber-300'">
                        🏳️
                    </div>
                </div>
            </div>

            <!-- Position indicators -->
            <div class="flex justify-between mt-2 px-0">
                <div v-for="i in (ROPE_MAX * 2 + 1)" :key="i"
                     class="w-1.5 h-1.5 rounded-full"
                     :class="(i - 1 - ROPE_MAX) === ropePos ? 'bg-white' : 'bg-white/20'">
                </div>
            </div>
        </div>

        <!-- Question -->
        <div class="flex-1 flex flex-col items-center justify-center px-4 py-4 max-w-2xl mx-auto w-full">

            <div v-if="!gameOver">
                <!-- Question card -->
                <div class="bg-white/10 border border-white/20 rounded-2xl p-5 mb-5 text-center backdrop-blur-sm w-full">
                    <p class="text-white font-bold text-base sm:text-lg leading-relaxed">
                        {{ current?.question }}
                    </p>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full">
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
                <div v-if="showResult" class="mt-4 text-center">
                    <span v-if="selected === current?.answer_index"
                          class="inline-flex items-center gap-2 bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 font-bold px-4 py-2 rounded-xl text-sm">
                        ✅ To'g'ri! Arqon sizga qarab keldi!
                    </span>
                    <span v-else
                          class="inline-flex items-center gap-2 bg-red-500/20 border border-red-500/40 text-red-300 font-bold px-4 py-2 rounded-xl text-sm">
                        ❌ Xato! Arqon raqibga qarab ketdi!
                    </span>
                </div>
            </div>

            <!-- Game over -->
            <div v-else class="text-center w-full">
                <div class="bg-white/10 border border-white/20 rounded-3xl p-8 backdrop-blur-sm">
                    <div class="text-7xl mb-4">
                        {{ winner === 'a' ? '🏆' : winner === 'b' ? '😔' : '🤝' }}
                    </div>
                    <h2 class="text-2xl font-extrabold text-white mb-2">
                        {{ winner === 'a' ? teamA + ' g\'alaba qozondi!' : winner === 'b' ? teamB + ' g\'alaba qozondi!' : 'Durrang!' }}
                    </h2>
                    <p class="text-slate-300 text-sm mb-2">
                        To'g'ri: {{ score.a }} · Xato: {{ score.b }}
                    </p>
                    <p class="text-slate-400 text-sm mb-6">
                        {{ winner === 'a' ? 'Zo\'r edi! Barcha savollarni to\'g\'ri javobladingiz.' : winner === 'b' ? 'Ko\'proq mashq qiling!' : 'Teng kuch!' }}
                    </p>
                    <button @click="restart"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-xl transition">
                        🔄 Qaytadan
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
