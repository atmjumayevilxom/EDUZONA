<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const answers    = ref({});
const submitted  = ref(false);
const showPassage = ref(true);

const items   = computed(() => props.gameData?.items ?? []);
const score   = computed(() => submitted.value
    ? items.value.filter(q => answers.value[q.id] === q.answer_index).length
    : 0
);
const percent = computed(() =>
    items.value.length ? Math.round((score.value / items.value.length) * 100) : 0
);

const skillLabels = {
    find_info:   { label: 'Ma\'lumot topish', color: 'bg-blue-100 text-blue-700' },
    interpret:   { label: 'Talqin qilish',    color: 'bg-purple-100 text-purple-700' },
    reflect:     { label: 'Baholash',          color: 'bg-amber-100 text-amber-700' },
};

function skillBadge(skill) {
    return skillLabels[skill] ?? { label: skill, color: 'bg-slate-100 text-slate-600' };
}

function choose(qid, idx) {
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
    answers.value  = {};
    submitted.value = false;
}

function optionClass(q, i) {
    if (!submitted.value) {
        return answers.value[q.id] === i
            ? 'bg-indigo-600 text-white border-indigo-500'
            : 'bg-white text-slate-700 border-slate-200 hover:border-indigo-300 hover:bg-indigo-50';
    }
    if (i === q.answer_index)       return 'bg-emerald-500 text-white border-emerald-500';
    if (answers.value[q.id] === i)  return 'bg-red-400 text-white border-red-400';
    return 'bg-white text-slate-400 border-slate-200';
}

const answeredCount = computed(() => Object.keys(answers.value).length);
</script>

<template>
    <div class="min-h-screen bg-slate-50 p-4 sm:p-6">
        <div class="max-w-4xl mx-auto">

            <!-- Header -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5 mb-5">
                <div class="flex items-start justify-between gap-4 flex-wrap">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 text-xs font-bold px-3 py-1 rounded-full mb-2">
                            🌍 PISA O'qish
                        </div>
                        <h1 class="text-lg sm:text-xl font-bold text-slate-800">{{ gameData.title }}</h1>
                        <p v-if="gameData.context" class="text-slate-500 text-sm mt-1">{{ gameData.context }}</p>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-500">
                        <span>{{ answeredCount }}/{{ items.length }} javob berildi</span>
                    </div>
                </div>
            </div>

            <!-- Score (after submit) -->
            <div v-if="submitted"
                 :class="[
                     'rounded-2xl p-5 mb-5 border text-center',
                     percent >= 80 ? 'bg-emerald-50 border-emerald-200'
                     : percent >= 50 ? 'bg-amber-50 border-amber-200'
                     : 'bg-red-50 border-red-200'
                 ]">
                <div class="text-3xl font-extrabold mb-1"
                     :class="percent >= 80 ? 'text-emerald-600' : percent >= 50 ? 'text-amber-600' : 'text-red-600'">
                    {{ score }} / {{ items.length }} — {{ percent }}%
                </div>
                <p class="text-slate-600 text-sm">
                    {{ percent >= 80 ? '🎉 Ajoyib PISA natijalari!' : percent >= 50 ? '👍 Yaxshi, lekin tahlil ko\'nikmalarini oshiring.' : '📖 Matnni diqqat bilan o\'qib, tahlil qiling.' }}
                </p>
                <button @click="restart" class="mt-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2 rounded-xl text-sm transition">
                    🔄 Qaytadan
                </button>
            </div>

            <div class="flex flex-col xl:flex-row gap-5">

                <!-- Passage -->
                <div class="xl:w-96 shrink-0">
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden sticky top-4">
                        <button @click="showPassage = !showPassage"
                                class="w-full flex items-center justify-between px-5 py-4 bg-indigo-50 hover:bg-indigo-100 transition">
                            <span class="font-bold text-indigo-700 text-sm">📄 Matn</span>
                            <span class="text-indigo-400 text-xs transition-transform"
                                  :class="{ 'rotate-180': !showPassage }">▼</span>
                        </button>
                        <div v-show="showPassage" class="p-5">
                            <p class="text-slate-700 text-sm leading-relaxed whitespace-pre-line">
                                {{ gameData.passage }}
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Questions -->
                <div class="flex-1 space-y-4">
                    <div v-for="(q, qi) in items" :key="q.id"
                         class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">

                        <!-- Question header -->
                        <div class="p-4 pb-3 flex items-start gap-3">
                            <span class="w-7 h-7 rounded-lg bg-indigo-100 text-indigo-700 text-xs font-bold flex items-center justify-center shrink-0 mt-0.5">
                                {{ qi + 1 }}
                            </span>
                            <div class="flex-1">
                                <div v-if="q.skill" class="mb-2">
                                    <span :class="['text-[10px] font-bold px-2 py-0.5 rounded-full', skillBadge(q.skill).color]">
                                        {{ skillBadge(q.skill).label }}
                                    </span>
                                </div>
                                <p class="text-slate-800 font-semibold text-sm sm:text-base leading-relaxed">
                                    {{ q.question }}
                                </p>
                            </div>
                        </div>

                        <!-- Options -->
                        <div class="px-4 pb-4 space-y-2">
                            <button
                                v-for="(opt, oi) in q.options" :key="oi"
                                @click="choose(q.id, oi)"
                                :disabled="submitted"
                                :class="[
                                    'w-full flex items-center gap-3 px-4 py-2.5 rounded-xl border text-sm text-left transition font-medium',
                                    optionClass(q, oi)
                                ]">
                                <span class="w-6 h-6 rounded-md border flex items-center justify-center text-xs font-bold shrink-0"
                                      :class="answers[q.id] === oi && !submitted ? 'bg-white/20 border-white/40' : 'border-current'">
                                    {{ ['A','B','C','D'][oi] }}
                                </span>
                                {{ opt }}
                            </button>
                        </div>

                        <!-- Explanation after submit -->
                        <div v-if="submitted && q.explanation"
                             class="px-4 py-3 bg-slate-50 border-t border-slate-100 text-sm text-slate-600 leading-relaxed">
                            💡 {{ q.explanation }}
                        </div>
                    </div>

                    <!-- Submit -->
                    <div v-if="!submitted" class="text-center pt-2">
                        <button
                            @click="submit"
                            :disabled="answeredCount < items.length"
                            :class="[
                                'font-bold px-10 py-3 rounded-2xl text-base transition',
                                answeredCount >= items.length
                                    ? 'bg-indigo-600 hover:bg-indigo-700 text-white cursor-pointer shadow-lg'
                                    : 'bg-slate-200 text-slate-400 cursor-not-allowed'
                            ]">
                            {{ answeredCount < items.length
                                ? `${items.length - answeredCount} ta savol qoldi`
                                : '✅ Natijani ko\'rish' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
