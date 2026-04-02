<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const currentIdx   = ref(0);
const wordBank     = ref([]);   // shuffled chips not yet placed
const answerSlots  = ref([]);   // chips placed by user
const correctOrder = ref([]);   // correct chip-id sequence
const checked      = ref(false);
const isCorrect    = ref(false);
const score        = ref(0);
const finished     = ref(false);

const items   = computed(() => props.gameData.items ?? []);
const total   = computed(() => items.value.length);
const current = computed(() => items.value[currentIdx.value]);

const CHIP_COLORS = [
    'chip-indigo','chip-purple','chip-pink','chip-rose',
    'chip-orange','chip-amber','chip-teal','chip-blue',
];

function shuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

/**
 * Match each token of correct_sentence to the closest unused word in rawWords[].
 * Uses prefix-length scoring so "madaniyati" matches "madaniyatini" correctly.
 * Returns array of rawWords indices in sentence order.
 */
function computeCorrectOrder(rawWords, correctSentence) {
    const tokens = correctSentence
        .toLowerCase().replace(/[.,!?;:]/g, '').split(/\s+/).filter(Boolean);
    const used  = new Array(rawWords.length).fill(false);
    const order = [];

    for (const token of tokens) {
        let bestIdx = -1, bestScore = -1;
        for (let i = 0; i < rawWords.length; i++) {
            if (used[i]) continue;
            const w = rawWords[i].toLowerCase();
            if (w === token) { bestIdx = i; bestScore = Infinity; break; }
            // shared prefix length
            let shared = 0;
            const minLen = Math.min(w.length, token.length);
            for (let k = 0; k < minLen; k++) {
                if (w[k] === token[k]) shared++; else break;
            }
            if (shared > bestScore) { bestScore = shared; bestIdx = i; }
        }
        if (bestIdx !== -1) { used[bestIdx] = true; order.push(bestIdx); }
    }
    // leftover words appended (shouldn't normally happen)
    for (let i = 0; i < rawWords.length; i++) { if (!used[i]) order.push(i); }
    return order;
}

function loadSentence() {
    if (!current.value) return;
    const rawWords = current.value.words ?? [];
    const chips = rawWords.map((w, i) => ({
        id: i,
        text: w,
        color: CHIP_COLORS[i % CHIP_COLORS.length],
    }));
    correctOrder.value = computeCorrectOrder(rawWords, current.value.correct_sentence ?? '');
    wordBank.value     = shuffle([...chips]);
    answerSlots.value  = [];
    checked.value      = false;
    isCorrect.value    = false;
}

onMounted(() => loadSentence());

function pickWord(chip) {
    if (checked.value) return;
    wordBank.value    = wordBank.value.filter(c => c.id !== chip.id);
    answerSlots.value = [...answerSlots.value, chip];
}

function returnWord(chip) {
    if (checked.value) return;
    answerSlots.value = answerSlots.value.filter(c => c.id !== chip.id);
    wordBank.value    = [...wordBank.value, chip];
}

function check() {
    if (answerSlots.value.length !== (current.value?.words?.length ?? 0)) return;
    checked.value = true;
    const userIds    = answerSlots.value.map(c => c.id);
    isCorrect.value  = JSON.stringify(userIds) === JSON.stringify(correctOrder.value);
    if (isCorrect.value) { score.value++; audio.playCorrect(); }
    else { audio.playWrong(); }
}

function next() {
    if (currentIdx.value < total.value - 1) {
        currentIdx.value++;
        loadSentence();
    } else {
        finished.value = true;
        audio.playComplete();
    }
}

function restart() {
    currentIdx.value = 0;
    score.value      = 0;
    finished.value   = false;
    loadSentence();
}

const pct = computed(() =>
    total.value ? Math.round((score.value / total.value) * 100) : 0
);

// Correct answer words for display after wrong
const correctWords = computed(() => {
    if (!current.value) return [];
    const sentence = current.value.correct_sentence ?? '';
    return sentence.replace(/\.$/, '').trim().split(/\s+/);
});
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct === 100 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 60  ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-4">{{ pct === 100 ? '🎉' : pct >= 60 ? '👍' : '💪' }}</div>
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

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-2">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: total ? (currentIdx / total * 100) + '%' : '0%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ currentIdx + 1 }}/{{ total }}</span>
                <div class="bg-emerald-100 text-emerald-700 text-xs font-black px-3 py-1 rounded-full shrink-0">
                    {{ score }} ball
                </div>
            </div>


            <!-- Main card -->
            <div :key="currentIdx" class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden q-appear">

                <!-- ── Answer area ── -->
                <div class="p-5 border-b border-slate-100">
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-indigo-400"></span>
                        Sizning javobingiz
                    </div>
                    <div
                        :class="[
                            'min-h-16 rounded-xl border-2 border-dashed p-3 flex flex-wrap gap-2 transition-all',
                            checked
                                ? isCorrect
                                    ? 'border-emerald-400 bg-emerald-50/60'
                                    : 'border-red-400 bg-red-50/60'
                                : answerSlots.length
                                    ? 'border-indigo-300 bg-indigo-50/40'
                                    : 'border-slate-200 bg-slate-50/50'
                        ]"
                    >
                        <span v-if="!answerSlots.length && !checked"
                            class="text-slate-400 text-sm self-center w-full text-center">
                            Quyidagi so'zlarni bosing...
                        </span>
                        <button
                            v-for="chip in answerSlots"
                            :key="chip.id"
                            @click="returnWord(chip)"
                            :class="['chip', chip.color, checked ? 'cursor-default' : 'hover:scale-95 active:scale-90']"
                            :disabled="checked"
                        >{{ chip.text }}</button>
                    </div>

                    <!-- Result feedback -->
                    <Transition enter-active-class="transition-all duration-300 ease-out" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="checked" :class="[
                            'mt-3 flex items-start gap-2 rounded-xl px-4 py-3 text-sm font-semibold',
                            isCorrect ? 'bg-emerald-50 border border-emerald-200 text-emerald-700'
                                      : 'bg-red-50 border border-red-200 text-red-700'
                        ]">
                            <span class="text-base shrink-0">{{ isCorrect ? '✅' : '❌' }}</span>
                            <div>
                                <div>{{ isCorrect ? "To'g'ri! Ajoyib!" : "Noto'g'ri." }}</div>
                                <div v-if="!isCorrect" class="mt-1 text-slate-600 font-normal">
                                    To'g'ri: <span class="font-bold text-emerald-700">{{ current.correct_sentence }}</span>
                                </div>
                            </div>
                        </div>
                    </Transition>
                </div>

                <!-- ── Word bank ── -->
                <div class="p-5">
                    <div class="text-xs font-black text-slate-400 uppercase tracking-widest mb-3 flex items-center gap-2">
                        <span class="w-1.5 h-1.5 rounded-full bg-purple-400"></span>
                        So'zlar
                        <span class="ml-auto text-[10px] normal-case font-semibold text-slate-300">bosib qo'shing</span>
                    </div>
                    <div class="flex flex-wrap gap-2 min-h-12">
                        <TransitionGroup name="chip-list">
                            <button
                                v-for="chip in wordBank"
                                :key="chip.id"
                                @click="pickWord(chip)"
                                :class="['chip', chip.color, 'hover:scale-105 active:scale-95']"
                            >{{ chip.text }}</button>
                        </TransitionGroup>
                        <span v-if="!wordBank.length && !checked" class="text-slate-300 text-sm self-center">
                            Hammasi joylashtirildi
                        </span>
                    </div>
                </div>

                <!-- ── Actions ── -->
                <div class="px-5 pb-5 flex gap-3">
                    <!-- Clear -->
                    <button v-if="!checked && answerSlots.length"
                        @click="loadSentence"
                        class="px-5 py-3 rounded-xl border-2 border-slate-200 text-slate-500 hover:border-slate-300 font-semibold text-sm transition">
                        ↺ Tozalash
                    </button>

                    <!-- Check -->
                    <button v-if="!checked"
                        @click="check"
                        :disabled="answerSlots.length !== (current?.words?.length ?? 0)"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-200 disabled:cursor-not-allowed text-white font-black py-3 rounded-xl transition shadow-lg shadow-indigo-200/50 text-base">
                        {{ answerSlots.length === (current?.words?.length ?? 0) ? 'Tekshirish ✓' : `${answerSlots.length}/${current?.words?.length ?? 0} so'z` }}
                    </button>

                    <!-- Next -->
                    <button v-if="checked"
                        @click="next"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-black py-3 rounded-xl transition shadow-lg shadow-indigo-200/50 text-base">
                        {{ currentIdx < total - 1 ? 'Keyingi gap →' : 'Natijani ko\'rish 🎉' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* Card entrance */
.q-appear { animation: qSlide 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity:0; transform:translateY(14px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}

/* Chip list transition */
.chip-list-move        { transition: all 0.25s ease; }
.chip-list-enter-active{ transition: all 0.25s cubic-bezier(0.34,1.56,0.64,1); }
.chip-list-leave-active{ transition: all 0.15s ease; }
.chip-list-enter-from  { opacity:0; transform:scale(0.7); }
.chip-list-leave-to    { opacity:0; transform:scale(0.7); }

/* ── Base chip ── */
.chip {
    display: inline-flex;
    align-items: center;
    padding: 0.4rem 0.85rem;
    border-radius: 0.65rem;
    font-size: 0.9rem;
    font-weight: 700;
    border: 2px solid transparent;
    cursor: pointer;
    transition: transform 0.12s, box-shadow 0.12s;
    white-space: nowrap;
    box-shadow: 0 2px 0 0 rgba(0,0,0,0.1);
}

/* Color variants */
.chip-indigo { background:#e0e7ff; color:#3730a3; border-color:#a5b4fc; box-shadow:0 2px 0 0 #a5b4fc; }
.chip-purple { background:#f3e8ff; color:#6b21a8; border-color:#d8b4fe; box-shadow:0 2px 0 0 #d8b4fe; }
.chip-pink   { background:#fce7f3; color:#9d174d; border-color:#f9a8d4; box-shadow:0 2px 0 0 #f9a8d4; }
.chip-rose   { background:#fff1f2; color:#9f1239; border-color:#fda4af; box-shadow:0 2px 0 0 #fda4af; }
.chip-orange { background:#fff7ed; color:#9a3412; border-color:#fdba74; box-shadow:0 2px 0 0 #fdba74; }
.chip-amber  { background:#fffbeb; color:#92400e; border-color:#fcd34d; box-shadow:0 2px 0 0 #fcd34d; }
.chip-teal   { background:#f0fdfa; color:#134e4a; border-color:#5eead4; box-shadow:0 2px 0 0 #5eead4; }
.chip-blue   { background:#eff6ff; color:#1e3a8a; border-color:#93c5fd; box-shadow:0 2px 0 0 #93c5fd; }
</style>
