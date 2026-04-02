<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const opened  = ref([]);   // indices that have been flipped open
const known   = ref([]);   // indices marked "I know this"
const finished = ref(false);

const items     = computed(() => props.gameData.items ?? []);
const total     = computed(() => items.value.length);
const allOpened = computed(() => opened.value.length >= total.value);

function isOpened(i)  { return opened.value.includes(i); }
function isKnown(i)   { return known.value.includes(i); }

function open(i) {
    if (isOpened(i)) return;
    opened.value = [...opened.value, i];
    audio.playCorrect();
}

function markKnown(i) {
    if (!isKnown(i)) known.value = [...known.value, i];
    checkFinished();
}

function markUnknown(i) {
    checkFinished();
}

function checkFinished() {
    // finished when every opened card has been rated
    const rated = items.value.every((_, i) => !isOpened(i) || isKnown(i) || ratedUnknown.value.includes(i));
    if (rated && allOpened.value) {
        setTimeout(() => { finished.value = true; audio.playComplete(); }, 400);
    }
}

const ratedUnknown = ref([]);  // track "don't know" separately so we know a card was rated

function markDontKnow(i) {
    if (!ratedUnknown.value.includes(i)) ratedUnknown.value = [...ratedUnknown.value, i];
    checkFinished();
}

function restart() {
    opened.value       = [];
    known.value        = [];
    ratedUnknown.value = [];
    finished.value     = false;
}

const knownCount = computed(() => known.value.length);
const percent    = computed(() => total.value ? Math.round((knownCount.value / total.value) * 100) : 0);

const BOX_THEMES = [
    { front: 'from-indigo-500 to-blue-600',   back: 'border-indigo-300',   dot: 'bg-indigo-500'   },
    { front: 'from-pink-500 to-rose-600',      back: 'border-pink-300',     dot: 'bg-pink-500'     },
    { front: 'from-emerald-500 to-green-600',  back: 'border-emerald-300',  dot: 'bg-emerald-500'  },
    { front: 'from-amber-500 to-orange-500',   back: 'border-amber-300',    dot: 'bg-amber-500'    },
    { front: 'from-purple-500 to-violet-600',  back: 'border-purple-300',   dot: 'bg-purple-500'   },
    { front: 'from-cyan-500 to-teal-600',      back: 'border-cyan-300',     dot: 'bg-cyan-500'     },
    { front: 'from-red-500 to-rose-700',       back: 'border-red-300',      dot: 'bg-red-500'      },
    { front: 'from-lime-500 to-green-600',     back: 'border-lime-300',     dot: 'bg-lime-500'     },
];
function theme(i) { return BOX_THEMES[i % BOX_THEMES.length]; }
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    percent >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : percent >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-4">{{ percent >= 80 ? '🎉' : percent >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-2">{{ percent }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ knownCount }} / {{ total }} ta bilindi</div>
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
        <template v-else>

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: total ? (opened.length / total * 100) + '%' : '0%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ opened.length }}/{{ total }}</span>
                <div class="bg-emerald-100 text-emerald-700 text-xs font-black px-3 py-1 rounded-full shrink-0">
                    ✅ {{ knownCount }} bilaman
                </div>
            </div>

            <!-- Cards grid -->
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 xl:grid-cols-5 gap-3">
                <!-- Outer: entrance animation wrapper -->
                <div
                    v-for="(item, i) in items"
                    :key="i"
                    class="box-appear"
                    :style="{ animationDelay: `${i * 45}ms`, minHeight: '160px' }"
                >
                    <!-- Middle: perspective container -->
                    <div style="perspective: 700px; position: relative; height: 100%; min-height: 160px;">
                    <!-- Flip wrapper -->
                    <div
                        :style="{
                            transform: `rotateY(${isOpened(i) ? 180 : 0}deg)`,
                            transition: 'transform 0.55s cubic-bezier(0.4,0,0.2,1)',
                            transformStyle: 'preserve-3d',
                            position: 'absolute',
                            inset: 0,
                        }"
                    >
                        <!-- ── FRONT (closed box) ── -->
                        <div
                            style="backface-visibility: hidden; position: absolute; inset: 0;"
                            @click="open(i)"
                            :class="[
                                'rounded-2xl bg-gradient-to-br cursor-pointer flex flex-col items-center justify-center gap-2',
                                'shadow-md hover:shadow-xl transition-all duration-200 hover:scale-105 active:scale-95',
                                theme(i).front,
                            ]"
                        >
                            <!-- Lid strip -->
                            <div class="absolute top-0 left-0 right-0 h-[28%] rounded-t-2xl bg-white/15 border-b border-white/20 flex items-center justify-center">
                                <div class="w-0.5 h-full bg-white/20"></div>
                            </div>
                            <span class="text-4xl relative z-10 mt-3">🎁</span>
                            <span class="text-white font-black text-xl relative z-10">{{ i + 1 }}</span>
                            <span class="text-white/70 text-[10px] font-bold uppercase tracking-widest relative z-10 pb-2">Ochish ▾</span>
                        </div>

                        <!-- ── BACK (revealed content) ── -->
                        <div
                            style="backface-visibility: hidden; transform: rotateY(180deg); position: absolute; inset: 0;"
                            :class="[
                                'rounded-2xl border-2 flex flex-col p-3 shadow-sm',
                                isKnown(i)
                                    ? 'border-emerald-400 bg-emerald-50'
                                    : ratedUnknown.includes(i)
                                        ? 'border-red-300 bg-red-50/60'
                                        : 'border-slate-200 bg-white',
                            ]"
                        >
                            <!-- Badge dot -->
                            <div class="flex items-center gap-1.5 mb-1.5">
                                <span :class="['w-2 h-2 rounded-full shrink-0', theme(i).dot]"></span>
                                <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">{{ i + 1 }}-karta</span>
                            </div>

                            <!-- Hint (topic/question) -->
                            <div class="text-xs font-bold text-slate-500 mb-1 leading-snug">{{ item.hint }}</div>

                            <!-- Divider -->
                            <div class="border-t border-slate-100 my-1.5"></div>

                            <!-- Hidden text (the revealed knowledge) -->
                            <div class="flex-1 text-sm font-semibold text-slate-800 leading-snug overflow-hidden">
                                {{ item.hidden_text }}
                            </div>

                            <!-- Rating buttons (only shown after open, not yet rated) -->
                            <div v-if="isOpened(i) && !isKnown(i) && !ratedUnknown.includes(i)"
                                class="flex gap-1.5 mt-2">
                                <button
                                    @click.stop="markDontKnow(i)"
                                    class="flex-1 text-xs bg-red-100 hover:bg-red-200 text-red-700 font-bold py-1.5 rounded-xl transition active:scale-95">
                                    ✗ Bilmayman
                                </button>
                                <button
                                    @click.stop="markKnown(i)"
                                    class="flex-1 text-xs bg-emerald-100 hover:bg-emerald-200 text-emerald-700 font-bold py-1.5 rounded-xl transition active:scale-95">
                                    ✓ Bilaman
                                </button>
                            </div>

                            <!-- Already rated badge -->
                            <div v-else-if="isKnown(i)"
                                class="mt-2 text-center text-emerald-600 text-xs font-black">
                                ✅ Bilaman
                            </div>
                            <div v-else-if="ratedUnknown.includes(i)"
                                class="mt-2 text-center text-red-500 text-xs font-black">
                                ❌ Bilmayman
                            </div>
                        </div>
                    </div>
                    </div><!-- /perspective container -->
                </div><!-- /box-appear -->
            </div><!-- /grid -->

        </template>
    </div>
</template>

<style scoped>
.box-appear { animation: boxPop 0.42s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes boxPop {
    from { opacity: 0; transform: scale(0.75) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
