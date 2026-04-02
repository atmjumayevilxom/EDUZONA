<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: Object });
const audio = useGameAudio();

const items = computed(() => {
    const raw = props.gameData?.items ?? props.gameData?.events ?? [];
    return raw.map((item, i) => ({
        id:   i,
        text: item.text ?? item.event ?? item.name ?? `Voqea ${i + 1}`,
        year: item.year ?? item.date ?? item.order ?? i,
        hint: item.hint ?? '',
    }));
});

const shuffled  = ref([]);
const dragIdx   = ref(null);
const dragOver  = ref(null);
const checked   = ref(false);
const finished  = ref(false);
const results   = ref([]);
const score     = ref(0);

const correctOrder = computed(() =>
    [...items.value].sort((a, b) => a.year - b.year)
);

onMounted(() => {
    shuffled.value = [...items.value].sort(() => Math.random() - 0.5);
});

function onDragStart(idx) { dragIdx.value = idx; }
function onDragOver(e, idx) { e.preventDefault(); dragOver.value = idx; }
function onDragLeave()      { dragOver.value = null; }
function onDrop(e, idx) {
    e.preventDefault();
    if (dragIdx.value === null || dragIdx.value === idx) { dragOver.value = null; return; }
    const arr = [...shuffled.value];
    const [moved] = arr.splice(dragIdx.value, 1);
    arr.splice(idx, 0, moved);
    shuffled.value = arr;
    dragIdx.value  = null;
    dragOver.value = null;
}

let touchStart = null;
function onTouchStart(e, idx) { touchStart = idx; }
function onTouchEnd(e, idx) {
    if (touchStart !== null && touchStart !== idx) {
        const arr = [...shuffled.value];
        const [moved] = arr.splice(touchStart, 1);
        arr.splice(idx, 0, moved);
        shuffled.value = arr;
    }
    touchStart = null;
}

function moveUp(idx) {
    if (idx === 0 || checked.value) return;
    const arr = [...shuffled.value];
    [arr[idx - 1], arr[idx]] = [arr[idx], arr[idx - 1]];
    shuffled.value = arr;
}
function moveDown(idx) {
    if (idx >= shuffled.value.length - 1 || checked.value) return;
    const arr = [...shuffled.value];
    [arr[idx], arr[idx + 1]] = [arr[idx + 1], arr[idx]];
    shuffled.value = arr;
}

function check() {
    checked.value = true;
    results.value = shuffled.value.map((item, i) => item.id === correctOrder.value[i].id);
    score.value   = results.value.filter(Boolean).length;
    const allCorrect = score.value === items.value.length;
    if (allCorrect) {
        audio.playCorrect();
        setTimeout(() => { audio.playComplete(); finished.value = true; }, 400);
    } else {
        audio.playWrong();
    }
}

function reset() {
    checked.value  = false;
    finished.value = false;
    results.value  = [];
    shuffled.value = [...items.value].sort(() => Math.random() - 0.5);
}

const DOT_GRADIENTS = [
    'from-violet-500 to-purple-600',
    'from-cyan-500   to-teal-600',
    'from-pink-500   to-rose-600',
    'from-amber-500  to-orange-500',
    'from-emerald-500 to-green-600',
    'from-blue-500   to-indigo-600',
    'from-red-500    to-rose-600',
    'from-lime-500   to-green-500',
];
function dotGradient(idx) { return DOT_GRADIENTS[idx % DOT_GRADIENTS.length]; }
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-xl mx-auto result-appear">
            <div class="rounded-3xl overflow-hidden shadow-2xl"
                style="background:linear-gradient(135deg,#0f172a,#1e1b4b,#0f172a)">
                <div class="px-10 py-14 text-center text-white">
                    <div class="text-7xl mb-4">🎉</div>
                    <div class="text-6xl font-black mb-2">100%</div>
                    <div class="text-white/70 text-lg font-semibold">Barcha voqealar to'g'ri tartibda!</div>
                </div>
                <div class="p-6 bg-black/20">
                    <button @click="reset"
                        class="w-full bg-gradient-to-r from-violet-600 to-indigo-600 hover:from-violet-500 hover:to-indigo-500
                               text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-violet-500/30">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <template v-else>

            <!-- Result banner (after check, not all correct) -->
            <Transition
                enter-active-class="transition-all duration-300"
                enter-from-class="opacity-0 -translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="checked"
                    :class="[
                        'mb-4 rounded-2xl px-5 py-3 flex items-center gap-3 border',
                        score === items.length
                            ? 'bg-emerald-50 border-emerald-300'
                            : 'bg-amber-50 border-amber-300'
                    ]">
                    <span class="text-xl">{{ score === items.length ? '✅' : '⚠️' }}</span>
                    <span :class="['font-bold text-sm', score === items.length ? 'text-emerald-700' : 'text-amber-700']">
                        {{ score }} / {{ items.length }} to'g'ri
                        <span v-if="score < items.length" class="text-slate-500 font-normal"> — qayta tartiblab ko'ring</span>
                    </span>
                </div>
            </Transition>

            <!-- Instruction -->
            <div v-if="!checked" class="text-center text-slate-400 text-xs mb-4 font-medium">
                ☝️ Kartalarni sudrab yoki ↑↓ tugmalar bilan tartibga soling — eng qadimiysi tepada
            </div>

            <!-- Timeline cards -->
            <div class="space-y-2">
                <div
                    v-for="(item, idx) in shuffled" :key="item.id"
                    draggable="true"
                    @dragstart="onDragStart(idx)"
                    @dragover="onDragOver($event, idx)"
                    @dragleave="onDragLeave"
                    @drop="onDrop($event, idx)"
                    @touchstart="onTouchStart($event, idx)"
                    @touchend="onTouchEnd($event, idx)"
                    :style="`animation-delay: ${idx * 55}ms`"
                    :class="[
                        'flex items-center gap-3 p-3 rounded-2xl border-2 transition-all duration-200 option-appear',
                        checked
                            ? results[idx]
                                ? 'bg-emerald-50 border-emerald-400'
                                : 'bg-red-50 border-red-300'
                            : dragOver === idx
                                ? 'bg-indigo-50 border-indigo-400 scale-[1.02]'
                                : 'bg-white border-slate-200 hover:border-slate-300 cursor-grab active:cursor-grabbing',
                    ]"
                >
                    <!-- Colored number dot -->
                    <div :class="[
                        'shrink-0 w-10 h-10 rounded-xl flex items-center justify-center font-black text-white text-sm shadow-md bg-gradient-to-br',
                        checked
                            ? results[idx] ? 'from-emerald-500 to-green-600' : 'from-red-400 to-rose-500'
                            : dotGradient(idx)
                    ]">{{ idx + 1 }}</div>

                    <!-- Text -->
                    <div class="flex-1 min-w-0">
                        <p class="font-semibold text-slate-800 text-sm leading-snug">{{ item.text }}</p>
                        <p v-if="checked && !results[idx]" class="text-xs text-emerald-600 mt-0.5 font-medium">
                            ✓ To'g'risi: {{ correctOrder[idx].text }}
                        </p>
                        <p v-if="checked && item.year" class="text-xs text-slate-400 mt-0.5">{{ item.year }}</p>
                    </div>

                    <!-- Move buttons (before check) -->
                    <div v-if="!checked" class="flex flex-col gap-0.5 shrink-0">
                        <button @click.stop="moveUp(idx)" :disabled="idx === 0"
                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-25 flex items-center justify-center text-xs text-slate-500 transition">
                            ▲
                        </button>
                        <button @click.stop="moveDown(idx)" :disabled="idx >= shuffled.length - 1"
                            class="w-7 h-7 rounded-lg bg-slate-100 hover:bg-slate-200 disabled:opacity-25 flex items-center justify-center text-xs text-slate-500 transition">
                            ▼
                        </button>
                    </div>

                    <!-- Result icon (after check) -->
                    <div v-if="checked" class="shrink-0 text-xl">
                        {{ results[idx] ? '✅' : '❌' }}
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div class="mt-6 flex justify-center">
                <button v-if="!checked" @click="check"
                    class="px-10 py-3.5 bg-gradient-to-r from-teal-600 to-emerald-600
                           hover:from-teal-500 hover:to-emerald-500
                           text-white font-black rounded-2xl transition shadow-lg shadow-teal-500/30 text-base">
                    Tekshirish ✓
                </button>
                <button v-else @click="reset"
                    class="px-8 py-3 bg-slate-700 hover:bg-slate-600 border border-slate-600
                           text-white font-bold rounded-2xl transition text-sm">
                    Qayta o'ynash ↺
                </button>
            </div>

        </template>
    </div>
</template>

<style scoped>
.option-appear { animation: optionSlideIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes optionSlideIn {
    from { opacity: 0; transform: translateY(10px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0)    scale(1); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
