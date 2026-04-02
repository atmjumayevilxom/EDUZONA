<script setup>
import { ref, computed, onUnmounted, watch } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const items = computed(() => props.gameData.items ?? []);

// Phase: 'study' | 'quiz' | 'done'
const phase      = ref('study');
const studyIdx   = ref(0);
const studyDone  = ref(false);
const timeLeft   = ref(3);    // countdown per card
const manualMode = ref(false); // user clicked next manually
let studyTimer = null;
let countTimer = null;

// Quiz
const quizItems = ref([]);
const quizIdx   = ref(0);
const score     = ref(0);
const chosen    = ref(null);
const finished  = ref(false);

/* ── Study logic ── */
function startStudy() {
    studyIdx.value  = 0;
    studyDone.value = false;
    manualMode.value = false;
    clearTimers();
    nextCard();
}

function nextCard() {
    clearInterval(countTimer);
    timeLeft.value = 3;
    countTimer = setInterval(() => {
        if (--timeLeft.value <= 0) {
            clearInterval(countTimer);
            if (studyIdx.value < items.value.length - 1) {
                studyIdx.value++;
                nextCard();
            } else {
                studyDone.value = true;
            }
        }
    }, 1000);
}

function skipStudy() {
    clearTimers();
    studyDone.value = true;
}

function goToQuiz() {
    clearTimers();
    buildQuiz();
    quizIdx.value = 0;
    score.value   = 0;
    chosen.value  = null;
    finished.value = false;
    phase.value   = 'quiz';
}

function clearTimers() {
    clearInterval(studyTimer);
    clearInterval(countTimer);
}

/* ── Quiz logic ── */
function buildQuiz() {
    const all = items.value;
    quizItems.value = all.map((item, idx) => {
        const label   = item.front ?? item.text ?? item.word ?? '';
        const correct = item.back  ?? item.hint ?? item.answer ?? '';
        const others = all
            .filter((_, i) => i !== idx)
            .map(i => i.back ?? i.hint ?? i.answer ?? '')
            .filter(Boolean)
            .sort(() => Math.random() - 0.5)
            .slice(0, 3);
        const opts = [correct, ...others].sort(() => Math.random() - 0.5);
        return { label, correct, options: opts };
    });
}

function pick(opt) {
    if (chosen.value !== null) return;
    chosen.value = opt;
    const isCorrect = opt === quizItems.value[quizIdx.value].correct;
    if (isCorrect) { score.value += 100; audio.playCorrect(); }
    else           { audio.playWrong(); }
    setTimeout(() => {
        chosen.value = null;
        if (quizIdx.value < quizItems.value.length - 1) {
            quizIdx.value++;
        } else {
            finished.value = true;
            audio.playComplete();
        }
    }, 1100);
}

function restart() {
    phase.value = 'study';
    startStudy();
}

startStudy();
onUnmounted(clearTimers);

const current  = computed(() => items.value[studyIdx.value]);
const qItem    = computed(() => quizItems.value[quizIdx.value] ?? null);
const qPct     = computed(() => quizItems.value.length ? (quizIdx.value / quizItems.value.length * 100) : 0);
const finalPct = computed(() => quizItems.value.length ? Math.round(score.value / quizItems.value.length) : 0);
const timerPct = computed(() => (timeLeft.value / 3) * 100);
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ STUDY PHASE ══ -->
        <div v-if="phase === 'study'" class="w-full max-w-md mx-auto space-y-3">

            <!-- HUD -->
            <div class="flex items-center gap-3">
                <div class="flex gap-1 flex-1">
                    <div v-for="(_, i) in items" :key="i"
                        :class="[
                            'h-1.5 flex-1 rounded-full transition-all duration-500',
                            i < studyIdx ? 'bg-cyan-400'
                            : i === studyIdx ? 'bg-cyan-600'
                            : 'bg-slate-200'
                        ]">
                    </div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ studyIdx + 1 }}/{{ items.length }}</span>
            </div>

            <!-- Study card -->
            <div v-if="current" :key="studyIdx"
                class="study-card rounded-3xl overflow-hidden shadow-2xl"
                style="background: linear-gradient(140deg,#0c4a6e,#0369a1,#0c4a6e)">

                <div class="px-6 pt-6 pb-5">
                    <!-- Phase label -->
                    <div class="text-cyan-300 text-[10px] font-black uppercase tracking-widest mb-4 flex items-center gap-2">
                        <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-cyan-400/20 text-cyan-300 text-[11px]">👁</span>
                        Eslab qoling — {{ studyIdx + 1 }}/{{ items.length }}
                    </div>

                    <!-- Front -->
                    <div class="bg-white/12 rounded-2xl px-5 py-4 text-center mb-3">
                        <div class="text-cyan-300 text-[9px] font-black uppercase tracking-widest mb-1.5">Tushuncha</div>
                        <p class="text-white font-bold text-lg leading-snug break-words">
                            {{ current.front ?? current.text ?? current.word }}
                        </p>
                    </div>

                    <!-- Arrow -->
                    <div class="text-center text-cyan-400 text-lg mb-3">↓</div>

                    <!-- Back -->
                    <div class="bg-white/12 rounded-2xl px-5 py-4 text-center">
                        <div class="text-cyan-300 text-[9px] font-black uppercase tracking-widest mb-1.5">Ta'rif / Javob</div>
                        <p class="text-cyan-100 font-semibold text-base leading-snug break-words">
                            {{ current.back ?? current.hint ?? current.answer }}
                        </p>
                    </div>
                </div>

                <!-- Timer bar -->
                <div v-if="!studyDone" class="px-6 pb-4">
                    <div class="h-1.5 bg-white/15 rounded-full overflow-hidden">
                        <div class="h-full bg-cyan-400 rounded-full transition-all duration-1000"
                            :style="{ width: timerPct + '%' }">
                        </div>
                    </div>
                    <div class="text-center text-cyan-300/50 text-[10px] mt-1.5">
                        {{ timeLeft }}s → keyingisi
                    </div>
                </div>
            </div>

            <!-- Buttons -->
            <div class="flex gap-3">
                <button v-if="!studyDone" @click="skipStudy"
                    class="flex-1 py-3 rounded-2xl border-2 border-white/20 text-white/60 text-sm font-bold hover:border-white/40 hover:text-white/80 transition-all">
                    ⏭ O'tkazib yuborish
                </button>
                <button
                    @click="goToQuiz"
                    :disabled="!studyDone"
                    :class="[
                        'font-black py-3.5 rounded-2xl transition-all text-sm shadow-lg',
                        studyDone
                            ? 'flex-1 bg-cyan-500 hover:bg-cyan-400 active:scale-95 text-white shadow-cyan-500/30'
                            : 'flex-1 bg-white/15 text-white/30 cursor-not-allowed'
                    ]"
                >
                    {{ studyDone ? '🎯 Testni boshlash!' : '⏳ Kuting...' }}
                </button>
            </div>
        </div>

        <!-- ══ QUIZ PHASE ══ -->
        <div v-else-if="phase === 'quiz'" class="w-full max-w-md mx-auto">

            <!-- Finished -->
            <div v-if="finished" class="result-appear">
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                    <div :class="[
                        'px-10 py-12 text-center text-white',
                        finalPct >= 80 ? 'bg-gradient-to-br from-cyan-500 to-blue-600'
                        : finalPct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-violet-600'
                        : 'bg-gradient-to-br from-orange-500 to-red-600'
                    ]">
                        <div class="text-6xl mb-3">{{ finalPct >= 80 ? '🧠' : finalPct >= 50 ? '👍' : '💪' }}</div>
                        <div class="text-5xl font-black mb-1">{{ finalPct }}%</div>
                        <div class="text-white/80">{{ score / 100 }}/{{ quizItems.length }} to'g'ri</div>
                    </div>
                    <div class="p-5">
                        <button @click="restart"
                            class="w-full bg-cyan-600 hover:bg-cyan-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-cyan-200">
                            Qayta o'ynash ↺
                        </button>
                    </div>
                </div>
            </div>

            <!-- Active quiz -->
            <div v-else-if="qItem" class="space-y-3">
                <!-- HUD -->
                <div class="flex items-center gap-3">
                    <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-2 bg-gradient-to-r from-cyan-500 to-blue-500 rounded-full transition-all duration-500"
                            :style="{ width: qPct + '%' }"></div>
                    </div>
                    <span class="text-sm text-slate-400 font-bold shrink-0">{{ quizIdx + 1 }}/{{ quizItems.length }}</span>
                    <div class="bg-cyan-100 text-cyan-700 text-xs font-black px-3 py-1 rounded-full shrink-0">
                        {{ score }} ball
                    </div>
                </div>

                <!-- Question -->
                <div :key="quizIdx" class="rounded-3xl overflow-hidden shadow-xl q-appear"
                    style="background: linear-gradient(135deg,#0c4a6e,#0369a1)">
                    <div class="px-5 py-5 text-center">
                        <div class="text-cyan-300 text-[10px] font-black uppercase tracking-widest mb-3">Bu nima edi?</div>
                        <p class="text-white font-bold text-lg leading-snug break-words">{{ qItem.label }}</p>
                    </div>
                </div>

                <!-- Options -->
                <div class="grid grid-cols-1 gap-2">
                    <button v-for="(opt, oi) in qItem.options" :key="opt + oi"
                        @click="pick(opt)"
                        :disabled="chosen !== null"
                        :style="{ animationDelay: oi * 65 + 'ms' }"
                        :class="[
                            'w-full text-left px-5 py-4 rounded-2xl border-2 font-semibold text-sm transition-all opt-appear',
                            chosen === null
                                ? 'border-slate-200 bg-white hover:border-cyan-400 hover:bg-cyan-50 hover:translate-x-1'
                                : chosen === opt
                                    ? (opt === qItem.correct
                                        ? 'border-emerald-500 bg-emerald-50 text-emerald-700'
                                        : 'border-red-400 bg-red-50 text-red-600')
                                    : (opt === qItem.correct
                                        ? 'border-emerald-400 bg-emerald-50 text-emerald-700'
                                        : 'border-slate-100 bg-slate-50 text-slate-400')
                        ]"
                    >
                        <span class="inline-flex items-center gap-3">
                            <span :class="[
                                'w-6 h-6 rounded-lg flex items-center justify-center text-xs font-black shrink-0',
                                chosen === null ? 'bg-slate-100 text-slate-500'
                                : chosen === opt
                                    ? (opt === qItem.correct ? 'bg-emerald-200 text-emerald-700' : 'bg-red-200 text-red-700')
                                    : (opt === qItem.correct ? 'bg-emerald-200 text-emerald-700' : 'bg-slate-100 text-slate-400')
                            ]">{{ ['A','B','C','D'][oi] }}</span>
                            {{ opt }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.study-card { animation: cardAppear 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes cardAppear {
    from { opacity: 0; transform: translateY(18px) scale(0.95); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.q-appear { animation: qSlide 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: translateX(20px) scale(0.97); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}

.opt-appear { animation: optIn 0.32s ease both; }
@keyframes optIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
