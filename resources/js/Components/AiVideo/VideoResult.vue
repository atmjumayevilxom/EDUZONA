<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    result:  { type: Object, required: true },
    polling: { type: Boolean, default: false },
});

const emit = defineEmits(['retry']);

const currentStep = ref(0);
const showAll     = ref(false);

const solution   = computed(() => props.result?.solution ?? null);
const steps      = computed(() => solution.value?.steps ?? []);
const hasVideo   = computed(() => !!props.result?.video_url);
const isGenerating = computed(() => props.polling || props.result?.status === 'generating');
const activeStep = computed(() => steps.value[currentStep.value] ?? null);
const isLast     = computed(() => currentStep.value >= steps.value.length - 1);

const subjectIcons = {
    mathematics: '📐', geometry: '📏', algebra: '🔢', physics: '⚡',
    chemistry: '⚗️', biology: '🧬', history: '📜', geography: '🌍',
    language: '✍️', english: '🇬🇧', informatics: '💻', other: '📚',
};
const subjectIcon = computed(() => subjectIcons[props.result?.subject] ?? '📚');

function nextStep() { if (!isLast.value) currentStep.value++; }
function prevStep() { if (currentStep.value > 0) currentStep.value--; }
</script>

<template>
    <div class="space-y-4">

        <!-- ══ VIDEO PLAYER (tayyor bo'lganda) ══ -->
        <div v-if="hasVideo" class="bg-black rounded-2xl overflow-hidden shadow-xl">
            <video
                :src="result.video_url"
                controls
                autoplay
                class="w-full aspect-video"
                preload="auto">
            </video>
            <div class="bg-slate-900 px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="text-green-400 text-sm font-bold">✓ Video tayyor</span>
                    <span class="text-slate-500 text-xs">· {{ result.topic }}</span>
                </div>
                <div class="flex gap-2">
                    <a :href="result.video_url" download target="_blank"
                        class="text-xs text-slate-300 hover:text-white bg-slate-700 hover:bg-slate-600 px-3 py-1.5 rounded-lg transition">
                        ⬇ Yuklab olish
                    </a>
                    <button @click="emit('retry')"
                        class="text-xs text-indigo-300 hover:text-white bg-indigo-700 hover:bg-indigo-600 px-3 py-1.5 rounded-lg transition">
                        + Yangi masala
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ VIDEO YUKLANMOQDA ══ -->
        <div v-else-if="isGenerating"
            class="bg-gradient-to-r from-violet-50 to-indigo-50 border border-indigo-200 rounded-2xl px-5 py-4 flex items-center gap-4">
            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center shrink-0">
                <svg class="w-5 h-5 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                </svg>
            </div>
            <div class="flex-1">
                <p class="text-sm font-bold text-indigo-800">Video yaratilmoqda...</p>
                <p class="text-xs text-indigo-500 mt-0.5">Grok AI video tayyorlamoqda. 10–30 soniya kutish mumkin.</p>
            </div>
            <div class="flex gap-1">
                <span v-for="i in 3" :key="i"
                    class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce"
                    :style="{ animationDelay: `${(i-1)*150}ms` }"></span>
            </div>
        </div>

        <!-- ══ INTERAKTIV YECHIM ══ -->
        <div v-if="solution" class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">

            <!-- Header -->
            <div class="bg-gradient-to-r from-indigo-600 to-violet-600 px-5 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-xl shrink-0">
                            {{ subjectIcon }}
                        </div>
                        <div>
                            <div class="text-white font-bold text-sm leading-tight">{{ result.topic ?? 'Yechim' }}</div>
                            <div class="text-indigo-200 text-xs mt-0.5">AI yechim · {{ steps.length }} qadam</div>
                        </div>
                    </div>
                    <button @click="emit('retry')"
                        class="flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-white/20 hover:bg-white/30 rounded-lg transition">
                        + Yangi masala
                    </button>
                </div>
            </div>

            <!-- Masala matni -->
            <div v-if="result.problem_text" class="px-5 py-3 bg-slate-50 border-b border-slate-100">
                <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Masala</div>
                <div class="text-sm text-slate-700 leading-relaxed">{{ result.problem_text }}</div>
            </div>

            <!-- Barchasini ko'rish rejimi -->
            <div v-if="showAll" class="px-5 py-4 space-y-4">
                <div v-for="step in steps" :key="step.step" class="flex gap-3">
                    <div class="w-7 h-7 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                        {{ step.step }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-semibold text-slate-800 mb-1">{{ step.title }}</p>
                        <p class="text-sm text-slate-600 leading-relaxed">{{ step.explanation }}</p>
                        <div v-if="step.formula"
                            class="mt-2 bg-slate-800 text-green-400 text-xs font-mono px-3 py-2 rounded-lg overflow-x-auto">
                            {{ step.formula }}
                        </div>
                        <div v-if="step.note"
                            class="mt-2 flex items-start gap-1.5 text-xs text-amber-700 bg-amber-50 px-3 py-2 rounded-lg">
                            <span>💡</span><span>{{ step.note }}</span>
                        </div>
                    </div>
                </div>

                <div v-if="solution.final_answer"
                    class="mt-2 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                    <div class="text-xs font-bold text-green-700 uppercase tracking-wider mb-1">Yakuniy javob</div>
                    <div class="text-base font-bold text-green-800">{{ solution.final_answer }}</div>
                </div>

                <div v-if="solution.uzbek_summary"
                    class="bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 text-sm text-indigo-800">
                    {{ solution.uzbek_summary }}
                </div>

                <button @click="showAll = false; currentStep = 0"
                    class="w-full py-2.5 text-xs font-semibold text-slate-500 hover:text-slate-700 border border-slate-200 rounded-xl transition">
                    ↑ Qadam-baqadam rejimga qaytish
                </button>
            </div>

            <!-- Qadam-baqadam rejim -->
            <div v-else class="px-5 py-4">
                <!-- Progress -->
                <div class="flex items-center gap-2 mb-4">
                    <div class="flex-1 flex gap-1">
                        <div v-for="(s, idx) in steps" :key="idx"
                            :class="[
                                'h-1.5 flex-1 rounded-full transition-all duration-300',
                                idx < currentStep  ? 'bg-indigo-500'
                                : idx === currentStep ? 'bg-indigo-400'
                                : 'bg-slate-200'
                            ]"></div>
                    </div>
                    <span class="text-xs font-bold text-slate-400 shrink-0">
                        {{ currentStep + 1 }}/{{ steps.length }}
                    </span>
                </div>

                <!-- Faol qadam -->
                <div v-if="activeStep" class="min-h-[180px]">
                    <div class="flex gap-3">
                        <div class="w-9 h-9 bg-indigo-600 text-white rounded-full flex items-center justify-center text-sm font-bold shrink-0">
                            {{ activeStep.step }}
                        </div>
                        <div class="flex-1 min-w-0 pt-1">
                            <p class="text-base font-bold text-slate-800 mb-2">{{ activeStep.title }}</p>
                            <p class="text-sm text-slate-600 leading-relaxed">{{ activeStep.explanation }}</p>
                            <div v-if="activeStep.formula"
                                class="mt-3 bg-slate-800 text-green-400 text-sm font-mono px-4 py-3 rounded-xl overflow-x-auto shadow-inner">
                                {{ activeStep.formula }}
                            </div>
                            <div v-if="activeStep.note"
                                class="mt-3 flex items-start gap-2 text-sm text-amber-700 bg-amber-50 px-3 py-2.5 rounded-xl border border-amber-100">
                                <span>💡</span><span>{{ activeStep.note }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="isLast && solution.final_answer"
                        class="mt-4 bg-green-50 border border-green-200 rounded-xl px-4 py-3">
                        <div class="text-xs font-bold text-green-700 uppercase tracking-wider mb-1">✅ Yakuniy javob</div>
                        <div class="text-lg font-black text-green-800">{{ solution.final_answer }}</div>
                    </div>
                </div>

                <!-- Navigation -->
                <div class="flex items-center gap-2 mt-4">
                    <button @click="prevStep" :disabled="currentStep === 0"
                        :class="[
                            'px-4 py-2 text-sm font-semibold rounded-xl border transition',
                            currentStep === 0
                                ? 'text-slate-300 border-slate-100 cursor-not-allowed'
                                : 'text-slate-600 border-slate-200 hover:bg-slate-50'
                        ]">
                        ← Orqaga
                    </button>

                    <button v-if="!isLast" @click="nextStep"
                        class="flex-1 py-2 text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition">
                        Keyingi qadam →
                    </button>
                    <div v-else class="flex-1 flex gap-2">
                        <button @click="showAll = true"
                            class="flex-1 py-2 text-sm font-semibold border border-indigo-200 text-indigo-600 hover:bg-indigo-50 rounded-xl transition">
                            Barchasini ko'rish
                        </button>
                        <button @click="emit('retry')"
                            class="flex-1 py-2 text-sm font-bold bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition">
                            Yangi masala ↺
                        </button>
                    </div>
                </div>
            </div>

            <!-- Key concepts -->
            <div v-if="solution.key_concepts?.length" class="px-5 pb-4">
                <div class="border-t border-slate-100 pt-3">
                    <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Asosiy tushunchalar</div>
                    <div class="flex flex-wrap gap-1.5">
                        <span v-for="kc in solution.key_concepts" :key="kc"
                            class="px-2.5 py-1 bg-slate-100 text-slate-600 text-xs rounded-full font-medium">
                            {{ kc }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error fallback -->
        <div v-else-if="result?.error" class="bg-white rounded-2xl border border-red-100 shadow-sm p-8 text-center">
            <div class="text-3xl mb-3">😔</div>
            <p class="text-slate-500 text-sm mb-4">{{ result.error }}</p>
            <button @click="emit('retry')" class="px-5 py-2 bg-indigo-600 text-white rounded-xl text-sm font-semibold">
                Qayta urinish
            </button>
        </div>

    </div>
</template>
