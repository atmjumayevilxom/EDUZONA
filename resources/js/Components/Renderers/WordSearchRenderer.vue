<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });

const { playCorrect, playComplete } = useGameAudio();

const grid       = ref([]);
const placements = ref([]);
const foundWords = ref([]);          // array, not Set — fully reactive
const wordColors = ref({});          // word → color index
const startCell  = ref(null);
const hovered    = ref([]);          // array of "r,c" strings
const finished   = ref(false);

const WORD_COLORS = [
    { bg: '#fde68a', text: '#92400e', border: '#fbbf24' },  // amber
    { bg: '#bbf7d0', text: '#065f46', border: '#34d399' },  // emerald
    { bg: '#bfdbfe', text: '#1e3a8a', border: '#60a5fa' },  // blue
    { bg: '#e9d5ff', text: '#581c87', border: '#c084fc' },  // purple
    { bg: '#fecaca', text: '#7f1d1d', border: '#f87171' },  // red
    { bg: '#fed7aa', text: '#7c2d12', border: '#fb923c' },  // orange
    { bg: '#a7f3d0', text: '#064e3b', border: '#10b981' },  // teal
    { bg: '#fce7f3', text: '#831843', border: '#f472b6' },  // pink
];

const items = computed(() => props.gameData.items ?? []);
const words = computed(() => items.value.map(i => (i.word ?? i.text ?? '').toUpperCase().replace(/\s+/g, '')));
const GRID_SIZE = computed(() => Math.max(10, Math.min(16, words.value.length + 4)));

const ALPHA = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';

function buildGrid() {
    const size = GRID_SIZE.value;
    const g    = Array.from({ length: size }, () => Array(size).fill(''));
    const placed = [];
    const dirs = [[0,1],[1,0],[1,1],[-1,1],[0,-1],[-1,0],[-1,-1],[1,-1]];

    for (const word of words.value) {
        if (!word) continue;
        let ok = false;
        for (let attempt = 0; attempt < 200 && !ok; attempt++) {
            const [dr, dc] = dirs[Math.floor(Math.random() * dirs.length)];
            const maxR = dr === 0 ? size : dr > 0 ? size - word.length : size;
            const maxC = dc === 0 ? size : dc > 0 ? size - word.length : size;
            const minR = dr < 0 ? word.length - 1 : 0;
            const minC = dc < 0 ? word.length - 1 : 0;
            if (maxR <= minR || maxC <= minC) continue;
            const r = minR + Math.floor(Math.random() * (maxR - minR));
            const c = minC + Math.floor(Math.random() * (maxC - minC));
            let valid = true;
            for (let k = 0; k < word.length; k++) {
                const nr = r + dr * k, nc = c + dc * k;
                if (nr < 0 || nr >= size || nc < 0 || nc >= size) { valid = false; break; }
                if (g[nr][nc] && g[nr][nc] !== word[k]) { valid = false; break; }
            }
            if (valid) {
                for (let k = 0; k < word.length; k++) g[r + dr * k][c + dc * k] = word[k];
                placed.push({ word, r, c, dr, dc });
                ok = true;
            }
        }
    }
    for (let r = 0; r < size; r++)
        for (let c = 0; c < size; c++)
            if (!g[r][c]) g[r][c] = ALPHA[Math.floor(Math.random() * 26)];

    grid.value      = g;
    placements.value = placed;
}

onMounted(() => buildGrid());

function cellKey(r, c) { return `${r},${c}`; }

function getCellsInLine(a, b) {
    const dr = b.r - a.r, dc = b.c - a.c;
    const len = Math.max(Math.abs(dr), Math.abs(dc));
    if (len === 0) return [a];
    if (dr !== 0 && dc !== 0 && Math.abs(dr) !== Math.abs(dc)) return [];
    const ur = dr === 0 ? 0 : dr / Math.abs(dr);
    const uc = dc === 0 ? 0 : dc / Math.abs(dc);
    const cells = [];
    for (let i = 0; i <= len; i++) cells.push({ r: a.r + ur * i, c: a.c + uc * i });
    return cells;
}

function onCellDown(r, c) {
    if (finished.value) return;
    startCell.value = { r, c };
    hovered.value   = [cellKey(r, c)];
}

function onCellMove(r, c) {
    if (!startCell.value) return;
    const cells = getCellsInLine(startCell.value, { r, c });
    hovered.value = cells.length > 1
        ? cells.map(cell => cellKey(cell.r, cell.c))
        : [cellKey(startCell.value.r, startCell.value.c)];
}

function onCellUp(r, c) {
    if (!startCell.value) return;
    const cells = getCellsInLine(startCell.value, { r, c });
    if (cells.length > 1) {
        const word    = cells.map(cell => grid.value[cell.r][cell.c]).join('');
        const wordRev = word.split('').reverse().join('');
        const found   = placements.value.find(p => p.word === word || p.word === wordRev);
        if (found && !foundWords.value.includes(found.word)) {
            const colorIdx = foundWords.value.length % WORD_COLORS.length;
            wordColors.value = { ...wordColors.value, [found.word]: colorIdx };
            foundWords.value = [...foundWords.value, found.word];
            playCorrect();
            if (foundWords.value.length === placements.value.length) {
                setTimeout(() => { finished.value = true; playComplete(); }, 600);
            }
        }
    }
    startCell.value = null;
    hovered.value   = [];
}

// Touch handlers
function onTouchStart(e, r, c) {
    e.preventDefault();
    onCellDown(r, c);
}
function onTouchMove(e) {
    e.preventDefault();
    const touch = e.touches[0];
    const el    = document.elementFromPoint(touch.clientX, touch.clientY);
    if (el && el.dataset.r !== undefined) onCellMove(+el.dataset.r, +el.dataset.c);
}
function onTouchEnd(e) {
    e.preventDefault();
    const touch = e.changedTouches[0];
    const el    = document.elementFromPoint(touch.clientX, touch.clientY);
    if (el && el.dataset.r !== undefined) onCellUp(+el.dataset.r, +el.dataset.c);
    else { startCell.value = null; hovered.value = []; }
}

// Found cells map: key → colorIdx
const foundCellsMap = computed(() => {
    const map = {};
    for (const fw of foundWords.value) {
        const p = placements.value.find(pl => pl.word === fw);
        if (!p) continue;
        const ci = wordColors.value[fw] ?? 0;
        for (let k = 0; k < fw.length; k++)
            map[cellKey(p.r + p.dr * k, p.c + p.dc * k)] = ci;
    }
    return map;
});

function getCellStyle(r, c) {
    const key = cellKey(r, c);
    const ci  = foundCellsMap.value[key];
    if (ci !== undefined) {
        const col = WORD_COLORS[ci];
        return { background: col.bg, color: col.text, borderColor: col.border, fontWeight: '800' };
    }
    const isStart   = startCell.value && cellKey(startCell.value.r, startCell.value.c) === key;
    const isHovered = hovered.value.includes(key);
    if (isStart)   return { background: '#6366f1', color: '#fff', borderColor: '#4f46e5', fontWeight: '800' };
    if (isHovered) return { background: '#e0e7ff', color: '#3730a3', borderColor: '#818cf8', fontWeight: '700' };
    return {};
}

function restart() {
    foundWords.value = [];
    wordColors.value = {};
    startCell.value  = null;
    hovered.value    = [];
    finished.value   = false;
    buildGrid();
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="flex flex-col items-center justify-center py-16">
            <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 p-12 text-center max-w-md w-full result-appear">
                <div class="text-8xl mb-5">🔍</div>
                <h3 class="text-3xl font-black text-slate-800 mb-2">Barcha so'zlar topildi!</h3>
                <p class="text-slate-500 mb-8">
                    <strong class="text-emerald-600">{{ placements.length }}</strong> ta so'z muvaffaqiyatli topildi!
                </p>
                <button @click="restart"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-xl shadow-indigo-200">
                    Yangi jadval ↺
                </button>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <template v-else>

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-2">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-emerald-500 rounded-full transition-all duration-500"
                        :style="{ width: placements.length ? (foundWords.length / placements.length * 100) + '%' : '0%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ foundWords.length }}/{{ placements.length }}</span>
            </div>


            <div class="flex flex-col xl:flex-row gap-5 items-start">

                <!-- ── Grid ── -->
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-3 sm:p-4 overflow-auto shrink-0"
                    @mouseleave="() => { if(startCell) { startCell.value = null; hovered.value = []; } }"
                    @touchmove.prevent="onTouchMove"
                    @touchend.prevent="onTouchEnd">
                    <div
                        class="grid gap-0.5"
                        :style="`grid-template-columns: repeat(${GRID_SIZE}, minmax(0,1fr)); width: max-content;`"
                    >
                        <template v-for="(row, r) in grid" :key="r">
                            <div
                                v-for="(letter, c) in row"
                                :key="`${r}-${c}`"
                                :data-r="r"
                                :data-c="c"
                                :style="getCellStyle(r, c)"
                                class="grid-cell"
                                @mousedown="onCellDown(r, c)"
                                @mouseover="onCellMove(r, c)"
                                @mouseup="onCellUp(r, c)"
                                @touchstart.prevent="onTouchStart($event, r, c)"
                            >{{ letter }}</div>
                        </template>
                    </div>
                </div>

                <!-- ── Word list ── -->
                <div class="flex-1 bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sm:p-5">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-black text-slate-600 uppercase tracking-widest">So'zlar ro'yxati</h3>
                        <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full border border-emerald-200">
                            {{ foundWords.length }} / {{ placements.length }}
                        </span>
                    </div>
                    <div class="space-y-2">
                        <div
                            v-for="(item, i) in items"
                            :key="i"
                            :style="`animation-delay: ${i * 45}ms`"
                            :class="['word-row word-appear', foundWords.includes(words[i]) ? 'found' : 'pending']"
                        >
                            <!-- Color dot / checkmark -->
                            <div class="shrink-0 w-6 h-6 rounded-full flex items-center justify-center text-xs font-black transition-all"
                                :style="foundWords.includes(words[i])
                                    ? { background: WORD_COLORS[wordColors[words[i]] ?? 0].bg,
                                        color: WORD_COLORS[wordColors[words[i]] ?? 0].text,
                                        border: `2px solid ${WORD_COLORS[wordColors[words[i]] ?? 0].border}` }
                                    : { background: '#f1f5f9', color: '#94a3b8', border: '2px solid #e2e8f0' }
                                ">
                                {{ foundWords.includes(words[i]) ? '✓' : i + 1 }}
                            </div>
                            <div class="min-w-0 flex-1">
                                <div :class="['font-bold text-sm leading-tight transition-all',
                                    foundWords.includes(words[i]) ? 'line-through opacity-50' : 'text-slate-800']">
                                    {{ item.word ?? item.text }}
                                </div>
                                <div v-if="item.hint" class="text-xs text-slate-400 mt-0.5 leading-snug">{{ item.hint }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
/* Grid cell */
.grid-cell {
    width: 2rem;
    height: 2rem;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.8rem;
    font-weight: 600;
    font-family: ui-monospace, monospace;
    border-radius: 0.4rem;
    border: 1.5px solid #e2e8f0;
    background: #f8fafc;
    color: #475569;
    cursor: pointer;
    transition: background 0.08s, color 0.08s, border-color 0.08s, transform 0.08s;
    user-select: none;
    -webkit-user-select: none;
}
@media (min-width: 640px) {
    .grid-cell { width: 2.35rem; height: 2.35rem; font-size: 0.9rem; }
}
@media (min-width: 1024px) {
    .grid-cell { width: 2.6rem; height: 2.6rem; font-size: 1rem; }
}
.grid-cell:hover { background: #f1f5f9; }

/* Word list rows */
.word-row {
    display: flex;
    align-items: flex-start;
    gap: 0.6rem;
    padding: 0.6rem 0.75rem;
    border-radius: 0.75rem;
    border: 1.5px solid transparent;
    transition: background 0.2s, border-color 0.2s;
}
.word-row.pending {
    background: #f8fafc;
    border-color: #e2e8f0;
}
.word-row.found {
    background: #f0fdf4;
    border-color: #bbf7d0;
}

/* Entrance */
.word-appear { animation: wordIn 0.35s ease both; }
@keyframes wordIn {
    from { opacity: 0; transform: translateX(-10px); }
    to   { opacity: 1; transform: translateX(0); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
