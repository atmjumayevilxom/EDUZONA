<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const currentIdx = ref(0);
const typed      = ref([]);
const score      = ref(0);
const finished   = ref(false);
const showResult = ref(false);
const correct    = ref(false);
const shakeSlots = ref(false);

const items   = computed(() => props.gameData.items ?? []);
const total   = computed(() => items.value.length);
const current = computed(() => items.value[currentIdx.value]);
const word    = computed(() => (current.value?.word ?? current.value?.text ?? '').toUpperCase());

const letterPool = ref([]);

/* ── Letter tile colors (by position) ── */
const TILE_COLORS = [
    { bg: '#4f46e5', shadow: 'rgba(79,70,229,0.4)' },
    { bg: '#7c3aed', shadow: 'rgba(124,58,237,0.4)' },
    { bg: '#be185d', shadow: 'rgba(190,24,93,0.4)' },
    { bg: '#0369a1', shadow: 'rgba(3,105,161,0.4)' },
    { bg: '#059669', shadow: 'rgba(5,150,105,0.4)' },
    { bg: '#d97706', shadow: 'rgba(217,119,6,0.4)' },
];

function setup() {
    if (!current.value) return;
    const letters = word.value.split('');
    const extras = 'ABCDEFGHIJKLMNOPRSTUVYZ'.split('')
        .filter(c => !letters.includes(c))
        .sort(() => Math.random() - 0.5)
        .slice(0, 3);
    const pool = [...letters, ...extras].sort(() => Math.random() - 0.5);
    letterPool.value = pool.map((l, i) => ({
        letter: l, id: i, used: false,
        color: TILE_COLORS[i % TILE_COLORS.length],
    }));
    typed.value    = [];
    showResult.value = false;
    correct.value    = false;
    shakeSlots.value = false;
}

onMounted(setup);

function clickLetter(tile) {
    if (tile.used || showResult.value) return;
    if (typed.value.length >= word.value.length) return;
    tile.used = true;
    typed.value = [...typed.value, { letter: tile.letter, tileId: tile.id, color: tile.color }];
    if (typed.value.length === word.value.length) checkWord();
}

function backspace() {
    if (typed.value.length === 0 || showResult.value) return;
    const last = typed.value[typed.value.length - 1];
    const tile = letterPool.value.find(t => t.id === last.tileId);
    if (tile) tile.used = false;
    typed.value = typed.value.slice(0, -1);
}

function checkWord() {
    const userWord = typed.value.map(t => t.letter).join('');
    correct.value = userWord === word.value;
    showResult.value = true;
    if (correct.value) {
        score.value += 100;
        audio.playCorrect();
    } else {
        audio.playWrong();
        shakeSlots.value = true;
        setTimeout(() => { shakeSlots.value = false; }, 500);
    }
    setTimeout(() => {
        if (currentIdx.value + 1 >= total.value) {
            finished.value = true;
            audio.playComplete();
        } else {
            currentIdx.value++;
            setup();
        }
    }, 1600);
}

function skip() {
    showResult.value = true;
    correct.value = false;
    setTimeout(() => {
        if (currentIdx.value + 1 >= total.value) {
            finished.value = true;
        } else {
            currentIdx.value++;
            setup();
        }
    }, 1000);
}

function restart() {
    currentIdx.value = 0;
    score.value = 0;
    finished.value = false;
    setup();
}

const pct = computed(() => total.value ? Math.round(score.value / total.value) : 0);

function slotState(i) {
    const t = typed.value[i];
    if (!t && !showResult.value) return 'empty';
    if (!showResult.value) return 'filled';
    return t?.letter === word.value[i] ? 'correct' : 'wrong';
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-violet-500 to-purple-700'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-3">{{ pct >= 80 ? '🎉' : pct >= 50 ? '🔤' : '💪' }}</div>
                    <div class="text-5xl font-black mb-1">{{ score }} ball</div>
                    <div class="text-white/80">{{ score / 100 }}/{{ total }} to'g'ri</div>
                </div>
                <div class="p-5">
                    <button @click="restart"
                        class="w-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-violet-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else-if="current" :key="currentIdx" class="w-full max-w-md mx-auto space-y-3 card-appear">

            <!-- HUD -->
            <div class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: (currentIdx / total * 100) + '%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ currentIdx + 1 }}/{{ total }}</span>
                <div class="bg-violet-100 text-violet-700 text-xs font-black px-3 py-1 rounded-full shrink-0">{{ score }} ball</div>
            </div>

            <!-- Hint card -->
            <div class="rounded-3xl overflow-hidden shadow-xl"
                style="background:linear-gradient(140deg,#1e1b4b,#312e81)">
                <div class="px-5 py-5 text-center">
                    <div class="text-violet-400 text-[9px] font-black uppercase tracking-widest mb-2">Izoh / Hint</div>
                    <p class="text-white font-bold text-base leading-snug break-words">
                        {{ current.hint ?? current.definition ?? '—' }}
                    </p>
                    <div class="text-violet-300/40 text-xs mt-2 font-medium">{{ word.length }} harf</div>
                </div>

                <!-- Answer slots -->
                <div :class="['flex flex-wrap justify-center gap-2 px-5 pb-4', shakeSlots ? 'shake' : '']">
                    <div v-for="(_, i) in word" :key="i"
                        :class="[
                            'slot-tile',
                            slotState(i) === 'empty' ? 'slot-empty'
                            : slotState(i) === 'filled' ? 'slot-filled'
                            : slotState(i) === 'correct' ? 'slot-correct'
                            : 'slot-wrong'
                        ]">
                        <span v-if="typed[i]">{{ typed[i].letter }}</span>
                    </div>
                </div>

                <!-- Result feedback -->
                <Transition
                    enter-active-class="transition-all duration-300"
                    enter-from-class="opacity-0 scale-90"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="showResult" class="px-5 pb-4">
                        <div :class="[
                            'text-center py-2.5 rounded-2xl font-bold text-sm',
                            correct
                                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-400/30'
                                : 'bg-red-500/20 text-red-300 border border-red-400/30'
                        ]">
                            {{ correct ? '✅ To\'g\'ri! Barakalla!' : `❌ To'g'ri so'z: ${word}` }}
                        </div>
                    </div>
                </Transition>
            </div>

            <!-- Letter pool -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
                <div class="flex flex-wrap gap-2 justify-center">
                    <button
                        v-for="(tile, ti) in letterPool"
                        :key="tile.id"
                        @click="clickLetter(tile)"
                        :disabled="tile.used || showResult"
                        :style="{
                            background: tile.used || showResult ? 'transparent' : tile.color.bg,
                            boxShadow: tile.used || showResult ? 'none' : `0 4px 12px ${tile.color.shadow}`,
                            animationDelay: (ti * 35) + 'ms',
                        }"
                        :class="[
                            'letter-tile pool-appear',
                            tile.used || showResult ? 'tile-used' : 'tile-active',
                        ]"
                    >
                        {{ tile.used || showResult ? '' : tile.letter }}
                    </button>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex gap-2">
                <button @click="backspace"
                    :disabled="typed.length === 0 || showResult"
                    class="flex-1 py-3 rounded-2xl border-2 border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-50 active:scale-95 disabled:opacity-35 transition-all">
                    ⌫ O'chirish
                </button>
                <button @click="skip"
                    :disabled="showResult"
                    class="flex-1 py-3 rounded-2xl border-2 border-slate-200 text-slate-500 font-bold text-sm hover:bg-slate-50 active:scale-95 disabled:opacity-35 transition-all">
                    O'tkazib →
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Answer slots ── */
.slot-tile {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; font-weight: 900;
    border: 2px solid;
    transition: all 0.2s;
}
.slot-empty   { background: rgba(255,255,255,0.08); border-color: rgba(255,255,255,0.2); color: transparent; }
.slot-filled  { background: rgba(167,139,250,0.2); border-color: #a78bfa; color: #ddd6fe; animation: slotPop 0.25s cubic-bezier(0.34,1.56,0.64,1); }
.slot-correct { background: rgba(52,211,153,0.2);  border-color: #34d399; color: #6ee7b7; }
.slot-wrong   { background: rgba(248,113,113,0.2); border-color: #f87171; color: #fca5a5; }

@keyframes slotPop {
    from { transform: scale(0.7); opacity: 0.5; }
    to   { transform: scale(1); opacity: 1; }
}

/* ── Letter tiles (pool) ── */
.letter-tile {
    width: 44px; height: 44px;
    border-radius: 12px;
    display: flex; align-items: center; justify-content: center;
    font-size: 16px; font-weight: 900;
    border: 2px solid transparent;
    transition: all 0.15s;
}
.tile-active {
    color: white;
    cursor: pointer;
}
.tile-active:hover {
    filter: brightness(1.15);
    transform: translateY(-3px) scale(1.08);
}
.tile-active:active { transform: scale(0.88); }
.tile-used {
    background: rgba(0,0,0,0.05) !important;
    border: 2px dashed rgba(0,0,0,0.1);
    cursor: not-allowed;
    width: 44px; height: 44px;
    border-radius: 12px;
}

.pool-appear { animation: tileAppear 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes tileAppear {
    from { opacity: 0; transform: scale(0.5) rotate(-10deg); }
    to   { opacity: 1; transform: scale(1) rotate(0deg); }
}

/* ── Shake ── */
.shake { animation: slotsShake 0.45s ease; }
@keyframes slotsShake {
    0%,100% { transform: translateX(0); }
    15% { transform: translateX(-8px); }
    30% { transform: translateX(8px); }
    45% { transform: translateX(-5px); }
    60% { transform: translateX(5px); }
    80% { transform: translateX(-3px); }
}

/* ── Other ── */
.card-appear { animation: cardSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes cardSlide {
    from { opacity: 0; transform: translateX(22px) scale(0.97); }
    to   { opacity: 1; transform: translateX(0) scale(1); }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
