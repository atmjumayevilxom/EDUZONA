<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const answers    = ref({});   // id -> chosen index
const submitted  = ref(false);
const showExplain = ref({});  // id -> bool

const items = computed(() => props.gameData?.items ?? []);

const score = computed(() => {
    if (!submitted.value) return 0;
    return items.value.filter(q => answers.value[q.id] === q.answer_index).length;
});

const percent = computed(() =>
    items.value.length ? Math.round((score.value / items.value.length) * 100) : 0
);

function select(qid, idx) {
    if (submitted.value) return;
    answers.value[qid] = idx;
}

function submit() {
    if (Object.keys(answers.value).length < items.value.length) return;
    submitted.value = true;
    if (percent.value >= 80) playComplete();
    else if (percent.value >= 50) playCorrect();
    else playWrong();
}

function restart() {
    answers.value   = {};
    submitted.value = false;
    showExplain.value = {};
}

function optionLabel(i) {
    return ['A', 'B', 'C', 'D'][i] ?? String(i + 1);
}

function optionClass(q, i) {
    if (!submitted.value) {
        return answers.value[q.id] === i
            ? 'bg-indigo-600 text-white border-indigo-600'
            : 'bg-white text-slate-700 border-slate-300 hover:border-indigo-400 hover:bg-indigo-50';
    }
    if (i === q.answer_index)          return 'bg-emerald-500 text-white border-emerald-500';
    if (answers.value[q.id] === i)     return 'bg-red-400 text-white border-red-400';
    return 'bg-white text-slate-400 border-slate-200';
}

const answeredCount = computed(() => Object.keys(answers.value).length);
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-900 via-indigo-950 to-slate-900 p-4 sm:p-6">

        <!-- Header -->
        <div class="max-w-3xl mx-auto">
            <div class="text-center mb-6">
                <div class="inline-flex items-center gap-2 bg-indigo-900/50 border border-indigo-700 rounded-full px-4 py-1.5 text-indigo-300 text-xs font-bold mb-3">
                    <span>🎓</span> DTM Test
                    <span v-if="gameData.subject" class="text-indigo-400">· {{ gameData.subject }}</span>
                </div>
                <h1 class="text-xl sm:text-2xl font-bold text-white">{{ gameData.title }}</h1>
                <p v-if="gameData.instructions" class="text-slate-400 text-sm mt-2">{{ gameData.instructions }}</p>
            </div>

            <!-- Progress bar (before submit) -->
            <div v-if="!submitted" class="bg-slate-800/60 rounded-2xl p-4 mb-5 border border-slate-700 flex items-center gap-4">
                <div class="flex-1 bg-slate-700 rounded-full h-2 overflow-hidden">
                    <div class="h-full bg-indigo-500 rounded-full transition-all duration-300"
                         :style="`width: ${items.length ? (answeredCount / items.length) * 100 : 0}%`"></div>
                </div>
                <span class="text-slate-300 text-sm font-medium shrink-0">
                    {{ answeredCount }} / {{ items.length }}
                </span>
            </div>

            <!-- Score card (after submit) -->
            <div v-if="submitted"
                 :class="[
                     'rounded-2xl p-5 mb-6 text-center border',
                     percent >= 80 ? 'bg-emerald-900/40 border-emerald-700'
                     : percent >= 50 ? 'bg-amber-900/40 border-amber-700'
                     : 'bg-red-900/40 border-red-700'
                 ]">
                <div class="text-4xl font-extrabold mb-1"
                     :class="percent >= 80 ? 'text-emerald-300' : percent >= 50 ? 'text-amber-300' : 'text-red-300'">
                    {{ score }} / {{ items.length }}
                </div>
                <div class="text-2xl font-bold text-white">{{ percent }}%</div>
                <p class="text-slate-300 text-sm mt-1">
                    {{ percent >= 80 ? '🎉 Ajoyib natija!' : percent >= 50 ? '👍 Yaxshi, lekin yaxshilash mumkin.' : '📚 Ko\'proq mashq qiling.' }}
                </p>
                <button @click="restart"
                        class="mt-4 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2 rounded-xl text-sm transition">
                    🔄 Qaytadan
                </button>
            </div>

            <!-- Questions -->
            <div class="space-y-4">
                <div v-for="(q, qi) in items" :key="q.id"
                     class="bg-slate-800/60 rounded-2xl border border-slate-700 overflow-hidden">

                    <!-- Question -->
                    <div class="p-4 pb-3">
                        <div class="flex items-start gap-3">
                            <span class="shrink-0 w-7 h-7 rounded-lg bg-indigo-700/60 text-indigo-300 text-xs font-bold flex items-center justify-center mt-0.5">
                                {{ qi + 1 }}
                            </span>
                            <p class="text-white text-sm sm:text-base leading-relaxed font-medium">
                                {{ q.question }}
                            </p>
                        </div>
                    </div>

                    <!-- Options -->
                    <div class="px-4 pb-4 grid sm:grid-cols-2 gap-2">
                        <button
                            v-for="(opt, oi) in q.options" :key="oi"
                            @click="select(q.id, oi)"
                            :disabled="submitted"
                            :class="[
                                'flex items-center gap-2.5 px-3 py-2.5 rounded-xl border text-sm text-left transition font-medium',
                                optionClass(q, oi)
                            ]">
                            <span class="w-6 h-6 rounded-lg border flex items-center justify-center text-xs font-bold shrink-0"
                                  :class="answers[q.id] === oi && !submitted ? 'bg-white/20 border-white/40 text-white' : 'border-current'">
                                {{ optionLabel(oi) }}
                            </span>
                            <span>{{ opt }}</span>
                        </button>
                    </div>

                    <!-- Explanation (after submit) -->
                    <div v-if="submitted && q.explanation">
                        <button @click="showExplain[q.id] = !showExplain[q.id]"
                                class="w-full px-4 py-2 bg-slate-700/50 hover:bg-slate-700 text-slate-400 hover:text-slate-200 text-xs font-medium transition text-left flex items-center gap-1.5">
                            <span :class="{ 'rotate-90': showExplain[q.id] }" class="transition-transform inline-block">▶</span>
                            Izoh ko'rish
                        </button>
                        <div v-if="showExplain[q.id]"
                             class="px-4 py-3 bg-slate-900/40 text-slate-300 text-sm leading-relaxed border-t border-slate-700">
                            {{ q.explanation }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit button -->
            <div v-if="!submitted" class="mt-6 text-center">
                <button
                    @click="submit"
                    :disabled="answeredCount < items.length"
                    :class="[
                        'font-bold px-10 py-3.5 rounded-2xl text-base transition shadow-lg',
                        answeredCount >= items.length
                            ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-indigo-900/50 cursor-pointer'
                            : 'bg-slate-700 text-slate-500 cursor-not-allowed'
                    ]">
                    {{ answeredCount < items.length
                        ? `${items.length - answeredCount} ta savol qoldi`
                        : '✅ Natijani ko\'rish' }}
                </button>
            </div>
        </div>
    </div>
</template>
