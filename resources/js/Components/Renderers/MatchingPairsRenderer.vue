<script setup>
import { ref, computed, onMounted, nextTick } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });

const leftItems     = ref([]);
const rightItems    = ref([]);
const selectedLeft  = ref(null);
const selectedRight = ref(null);
const matched       = ref([]);
const wrong         = ref([]);
const attempts      = ref(0);
const finished      = ref(false);
const lines         = ref([]); // { id, x1,y1,x2,y2, color, len }
const gridRef       = ref(null);

const LINE_COLORS = [
    '#6366f1','#8b5cf6','#ec4899','#f59e0b',
    '#10b981','#3b82f6','#f43f5e','#14b8a6',
];

const { playCorrect, playWrong, playComplete } = useGameAudio();
onMounted(() => setup());

function shuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

function setup() {
    const items = props.gameData.items ?? [];
    leftItems.value     = shuffle(items.map(i => ({ id: i.id, text: i.left })));
    rightItems.value    = shuffle(items.map(i => ({ id: i.id, text: i.right })));
    selectedLeft.value  = null;
    selectedRight.value = null;
    matched.value       = [];
    wrong.value         = [];
    attempts.value      = 0;
    finished.value      = false;
    lines.value         = [];
}

const total = computed(() => props.gameData.items?.length ?? 0);

function selectLeft(item) {
    if (matched.value.includes(item.id) || wrong.value.length) return;
    selectedLeft.value = item;
    checkMatch();
}

function selectRight(item) {
    if (matched.value.includes(item.id) || wrong.value.length) return;
    selectedRight.value = item;
    checkMatch();
}

function checkMatch() {
    if (!selectedLeft.value || !selectedRight.value) return;
    attempts.value++;
    if (selectedLeft.value.id === selectedRight.value.id) {
        const id = selectedLeft.value.id;
        matched.value.push(id);
        playCorrect();
        addLine(id);
        selectedLeft.value  = null;
        selectedRight.value = null;
        if (matched.value.length === total.value) {
            setTimeout(() => { finished.value = true; playComplete(); }, 700);
        }
    } else {
        playWrong();
        wrong.value = [selectedLeft.value.id, selectedRight.value.id];
        setTimeout(() => {
            wrong.value         = [];
            selectedLeft.value  = null;
            selectedRight.value = null;
        }, 900);
    }
}

function addLine(id) {
    nextTick(() => {
        if (!gridRef.value) return;
        const box   = gridRef.value.getBoundingClientRect();
        const leftEl  = gridRef.value.querySelector(`[data-left-id="${id}"]`);
        const rightEl = gridRef.value.querySelector(`[data-right-id="${id}"]`);
        if (!leftEl || !rightEl) return;
        const lr = leftEl.getBoundingClientRect();
        const rr = rightEl.getBoundingClientRect();
        const x1 = lr.right  - box.left;
        const y1 = lr.top    + lr.height / 2 - box.top;
        const x2 = rr.left   - box.left;
        const y2 = rr.top    + rr.height / 2 - box.top;
        const len = Math.sqrt((x2 - x1) ** 2 + (y2 - y1) ** 2);
        const color = LINE_COLORS[(lines.value.length) % LINE_COLORS.length];
        lines.value.push({ id, x1, y1, x2, y2, len, color });
    });
}

function accuracy() {
    if (!attempts.value) return 100;
    return Math.round((total.value / attempts.value) * 100);
}
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-2xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div class="bg-gradient-to-br from-violet-500 to-purple-700 px-10 py-14 text-center text-white">
                    <div class="text-7xl mb-4">🎉</div>
                    <div class="text-5xl font-black mb-3">Ajoyib!</div>
                    <div class="flex justify-center gap-6 mt-4">
                        <div class="bg-white/15 rounded-2xl px-6 py-3 text-center">
                            <div class="text-2xl font-black">{{ total }}</div>
                            <div class="text-xs text-white/70 font-semibold mt-0.5">Juftlik</div>
                        </div>
                        <div class="bg-white/15 rounded-2xl px-6 py-3 text-center">
                            <div class="text-2xl font-black">{{ attempts }}</div>
                            <div class="text-xs text-white/70 font-semibold mt-0.5">Urinish</div>
                        </div>
                        <div class="bg-white/15 rounded-2xl px-6 py-3 text-center">
                            <div class="text-2xl font-black">{{ accuracy() }}%</div>
                            <div class="text-xs text-white/70 font-semibold mt-0.5">Aniqlik</div>
                        </div>
                    </div>
                </div>
                <div class="p-8 text-center">
                    <button @click="setup"
                        class="w-full bg-violet-600 hover:bg-violet-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-violet-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <template v-else>

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-2">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: total ? (matched.length / total * 100) + '%' : '0%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ matched.length }}/{{ total }}</span>
                <div class="bg-slate-100 text-slate-500 text-xs font-black px-3 py-1 rounded-full shrink-0">
                    {{ attempts }} urinish
                </div>
            </div>


            <!-- Hint when left is selected -->
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div v-if="selectedLeft" class="mb-3 text-center text-xs font-semibold text-indigo-500 bg-indigo-50 rounded-xl py-2 px-4">
                    ✦ "{{ selectedLeft.text }}" tanlandi — endi o'ng tomonda juftini bosing
                </div>
            </Transition>

            <!-- Grid with SVG overlay -->
            <div ref="gridRef" class="relative">

                <!-- SVG connection lines -->
                <svg class="absolute inset-0 w-full h-full pointer-events-none" style="overflow:visible; z-index:10">
                    <g v-for="line in lines" :key="line.id">
                        <!-- Glow copy -->
                        <line
                            :x1="line.x1" :y1="line.y1" :x2="line.x2" :y2="line.y2"
                            :stroke="line.color" stroke-width="8" stroke-linecap="round"
                            opacity="0.18"
                        />
                        <!-- Main line -->
                        <line
                            :x1="line.x1" :y1="line.y1" :x2="line.x2" :y2="line.y2"
                            :stroke="line.color" stroke-width="2.5" stroke-linecap="round"
                            :stroke-dasharray="line.len"
                            :stroke-dashoffset="line.len"
                            class="draw-line"
                        />
                        <!-- Left dot -->
                        <circle :cx="line.x1" :cy="line.y1" r="5" :fill="line.color" class="dot-pop"/>
                        <!-- Right dot -->
                        <circle :cx="line.x2" :cy="line.y2" r="5" :fill="line.color" class="dot-pop" style="animation-delay:0.25s"/>
                    </g>
                </svg>

                <!-- Pairs grid -->
                <div class="grid grid-cols-2 gap-3 sm:gap-5">

                    <!-- Left — Atamalar -->
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2 pb-2">
                            <span class="w-2 h-2 rounded-full bg-indigo-500 shrink-0"></span>
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Atama</span>
                        </div>
                        <button
                            v-for="(item, li) in leftItems"
                            :key="item.id"
                            :data-left-id="item.id"
                            @click="selectLeft(item)"
                            :style="`animation-delay: ${li * 55}ms`"
                            :class="[
                                'pair-card option-appear w-full text-left',
                                matched.includes(item.id)        ? 'card-matched' :
                                wrong.includes(item.id)          ? 'card-wrong'   :
                                selectedLeft?.id === item.id     ? 'card-sel-left':
                                                                   'card-idle card-left-hover',
                            ]"
                        >
                            <span v-if="matched.includes(item.id)" class="match-badge"
                                :style="`background:${lines.find(l=>l.id===item.id)?.color ?? '#22c55e'}`">✓</span>
                            <span v-else class="card-dot bg-indigo-300"></span>
                            <span class="card-text">{{ item.text }}</span>
                        </button>
                    </div>

                    <!-- Right — Ta'riflar -->
                    <div class="space-y-2.5">
                        <div class="flex items-center gap-2 pb-2">
                            <span class="w-2 h-2 rounded-full bg-purple-500 shrink-0"></span>
                            <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Ta'rif</span>
                        </div>
                        <button
                            v-for="(item, ri) in rightItems"
                            :key="item.id"
                            :data-right-id="item.id"
                            @click="selectRight(item)"
                            :style="`animation-delay: ${ri * 55}ms`"
                            :class="[
                                'pair-card option-appear w-full text-left',
                                matched.includes(item.id)        ? 'card-matched' :
                                wrong.includes(item.id)          ? 'card-wrong'   :
                                selectedRight?.id === item.id    ? 'card-sel-right':
                                                                   'card-idle card-right-hover',
                            ]"
                        >
                            <span v-if="matched.includes(item.id)" class="match-badge"
                                :style="`background:${lines.find(l=>l.id===item.id)?.color ?? '#22c55e'}`">✓</span>
                            <span v-else class="card-dot bg-purple-300"></span>
                            <span class="card-text">{{ item.text }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* Entrance */
.option-appear { animation: cardIn 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes cardIn {
    from { opacity:0; transform:translateY(12px) scale(0.95); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}

/* ── Line drawing ── */
.draw-line {
    animation: drawLine 0.45s cubic-bezier(0.4,0,0.2,1) forwards;
}
@keyframes drawLine {
    to { stroke-dashoffset: 0; }
}
.dot-pop {
    animation: dotPop 0.3s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes dotPop {
    from { r: 0; opacity: 0; }
    to   { r: 5; opacity: 1; }
}

/* ── Base card ── */
.pair-card {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.85rem 1rem;
    border-radius: 1rem;
    border: 2px solid transparent;
    font-weight: 600;
    font-size: 0.875rem;
    line-height: 1.45;
    transition: transform 0.13s, box-shadow 0.13s, background 0.13s, border-color 0.13s, opacity 0.15s;
    background: #f8fafc;
    box-shadow: 0 2px 0 0 #e2e8f0;
    position: relative;
    z-index: 20;
}
.pair-card:active:not(:disabled) { transform: scale(0.97); }

.card-dot {
    width:0.45rem; height:0.45rem; border-radius:50%;
    flex-shrink:0; margin-top:0.35rem;
}
.card-text { flex:1; min-width:0; word-break:break-words; }

.match-badge {
    flex-shrink:0; width:1.3rem; height:1.3rem;
    color:white; border-radius:50%;
    display:flex; align-items:center; justify-content:center;
    font-size:0.7rem; font-weight:800; margin-top:0.1rem;
}

/* Idle */
.card-idle { background:#f8fafc; border-color:#e2e8f0; color:#334155; cursor:pointer; box-shadow:0 2px 0 0 #e2e8f0; }
.card-left-hover:hover  { background:#eef2ff; border-color:#818cf8; color:#3730a3; box-shadow:0 4px 0 0 #c7d2fe,0 0 0 3px #e0e7ff; transform:translateY(-1px); }
.card-right-hover:hover { background:#f5f3ff; border-color:#a78bfa; color:#5b21b6; box-shadow:0 4px 0 0 #ddd6fe,0 0 0 3px #ede9fe; transform:translateY(-1px); }

/* Selected */
.card-sel-left  { background:#eef2ff; border-color:#6366f1; color:#3730a3; box-shadow:0 4px 0 0 #a5b4fc,0 0 0 3px #e0e7ff; transform:translateY(-2px) scale(1.01); cursor:pointer; }
.card-sel-right { background:#f5f3ff; border-color:#7c3aed; color:#5b21b6; box-shadow:0 4px 0 0 #c4b5fd,0 0 0 3px #ede9fe; transform:translateY(-2px) scale(1.01); cursor:pointer; }

/* Matched */
.card-matched { background:#f0fdf4; border-color:#86efac; color:#15803d; opacity:0.6; box-shadow:0 2px 0 0 #bbf7d0; cursor:default; }

/* Wrong */
.card-wrong { background:#fff1f2; border-color:#fb7185; color:#be123c; box-shadow:0 2px 0 0 #fecdd3; animation:shake 0.45s ease; cursor:default; }
@keyframes shake {
    0%,100% { transform:translateX(0); }
    20%     { transform:translateX(-7px); }
    40%     { transform:translateX(7px); }
    60%     { transform:translateX(-5px); }
    80%     { transform:translateX(4px); }
}
</style>
