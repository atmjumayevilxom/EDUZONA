<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const GRID_SIZE = 22;

const grid        = ref([]);         // 2D array of { letter, number, r, c }
const placedWords = ref([]);         // { word, clue, direction, row, col, number }
const inputs      = ref({});         // "r,c" -> typed letter
const activeCell  = ref(null);       // { r, c }
const activeWordNum = ref(null);
const activeDir   = ref('across');
const checked     = ref(false);
const finished    = ref(false);
const score       = ref(0);
const gridRef     = ref(null);

// Normalize words: uppercase, Latin only
const items = computed(() =>
    (props.gameData?.items ?? [])
        .map(i => ({ ...i, word: (i.word ?? '').toUpperCase().replace(/[^A-Z]/g, '') }))
        .filter(i => i.word.length >= 3 && i.word.length <= 15)
);

// ===== GRID BUILD =====
function canPlace(g, word, row, col, dir) {
    const len  = word.length;
    const endR = dir === 'down'   ? row + len - 1 : row;
    const endC = dir === 'across' ? col + len - 1 : col;
    if (row < 1 || col < 1 || endR >= GRID_SIZE - 1 || endC >= GRID_SIZE - 1) return false;

    for (let i = 0; i < len; i++) {
        const r = dir === 'down'   ? row + i : row;
        const c = dir === 'across' ? col + i : col;
        const cell = g[r][c];

        if (cell.letter) {
            if (cell.letter !== word[i]) return false;
        } else {
            // No parallel adjacent words
            if (dir === 'across') {
                if (g[r - 1]?.[c]?.letter || g[r + 1]?.[c]?.letter) return false;
            } else {
                if (g[r]?.[c - 1]?.letter || g[r]?.[c + 1]?.letter) return false;
            }
        }
    }

    // Check word start/end boundaries
    if (dir === 'across') {
        if (g[row]?.[col - 1]?.letter)  return false;
        if (g[row]?.[endC + 1]?.letter) return false;
    } else {
        if (g[row - 1]?.[col]?.letter)  return false;
        if (g[endR + 1]?.[col]?.letter) return false;
    }
    return true;
}

function countIntersections(g, word, row, col, dir) {
    let n = 0;
    for (let i = 0; i < word.length; i++) {
        const r = dir === 'down'   ? row + i : row;
        const c = dir === 'across' ? col + i : col;
        if (g[r][c].letter === word[i]) n++;
    }
    return n;
}

function placeWord(g, pw) {
    for (let i = 0; i < pw.word.length; i++) {
        const r = pw.direction === 'down'   ? pw.row + i : pw.row;
        const c = pw.direction === 'across' ? pw.col + i : pw.col;
        g[r][c].letter = pw.word[i];
    }
}

function buildCrossword() {
    const sorted = [...items.value].sort((a, b) => b.word.length - a.word.length);
    if (!sorted.length) return;

    const g = Array.from({ length: GRID_SIZE }, () =>
        Array.from({ length: GRID_SIZE }, () => ({ letter: '' }))
    );
    const placed = [];

    // First word horizontally in center
    const first = sorted[0];
    const r0 = Math.floor(GRID_SIZE / 2);
    const c0 = Math.floor((GRID_SIZE - first.word.length) / 2);
    placed.push({ word: first.word, clue: first.clue, direction: 'across', row: r0, col: c0 });
    placeWord(g, placed[0]);

    for (let wi = 1; wi < sorted.length; wi++) {
        const w = sorted[wi];
        let best = null, bestScore = -1;

        for (const pw of placed) {
            const dir = pw.direction === 'across' ? 'down' : 'across';
            for (let pi = 0; pi < pw.word.length; pi++) {
                for (let si = 0; si < w.word.length; si++) {
                    if (pw.word[pi] !== w.word[si]) continue;
                    const row = dir === 'down'   ? pw.row - si : pw.row + pi;
                    const col = dir === 'across' ? pw.col - si : pw.col + pi;
                    if (!canPlace(g, w.word, row, col, dir)) continue;
                    const sc = countIntersections(g, w.word, row, col, dir);
                    if (sc > bestScore) {
                        bestScore = sc;
                        best = { word: w.word, clue: w.clue, direction: dir, row, col };
                    }
                }
            }
        }

        if (best) {
            placed.push(best);
            placeWord(g, best);
        }
    }

    // Bounding box
    let minR = GRID_SIZE, maxR = 0, minC = GRID_SIZE, maxC = 0;
    for (let r = 0; r < GRID_SIZE; r++)
        for (let c = 0; c < GRID_SIZE; c++)
            if (g[r][c].letter) {
                minR = Math.min(minR, r); maxR = Math.max(maxR, r);
                minC = Math.min(minC, c); maxC = Math.max(maxC, c);
            }

    // Offset coords to trimmed grid
    for (const pw of placed) { pw.row -= minR; pw.col -= minC; }

    const trimmed = [];
    for (let r = minR; r <= maxR; r++) {
        const row = [];
        for (let c = minC; c <= maxC; c++)
            row.push({ letter: g[r][c].letter, number: 0, r: r - minR, c: c - minC });
        trimmed.push(row);
    }

    // Number cells (sorted by row then col)
    let num = 1;
    const cellNumMap = {};
    const sortedPlaced = [...placed].sort((a, b) => a.row !== b.row ? a.row - b.row : a.col - b.col);
    for (const pw of sortedPlaced) {
        const key = `${pw.row},${pw.col}`;
        if (!cellNumMap[key]) cellNumMap[key] = num++;
        pw.number = cellNumMap[key];
    }
    for (const [key, n] of Object.entries(cellNumMap)) {
        const [r, c] = key.split(',').map(Number);
        if (trimmed[r]?.[c]) trimmed[r][c].number = n;
    }

    grid.value        = trimmed;
    placedWords.value = sortedPlaced;
}

// ===== HELPERS =====
const ck = (r, c) => `${r},${c}`;

function isInWord(pw, r, c) {
    if (pw.direction === 'across') return pw.row === r && c >= pw.col && c < pw.col + pw.word.length;
    return pw.col === c && r >= pw.row && r < pw.row + pw.word.length;
}

function getActiveWord() {
    return placedWords.value.find(w => w.number === activeWordNum.value && w.direction === activeDir.value);
}

const acrossWords = computed(() =>
    placedWords.value.filter(w => w.direction === 'across').sort((a, b) => a.number - b.number)
);
const downWords = computed(() =>
    placedWords.value.filter(w => w.direction === 'down').sort((a, b) => a.number - b.number)
);

// ===== CELL STATE =====
function isCellActive(cell) { return activeCell.value?.r === cell.r && activeCell.value?.c === cell.c; }

function isCellHighlighted(cell) {
    const w = getActiveWord();
    return w ? isInWord(w, cell.r, cell.c) : false;
}

function getCellStatus(cell) {
    if (!checked.value || !cell.letter) return '';
    const inp = inputs.value[ck(cell.r, cell.c)] ?? '';
    if (!inp) return 'empty';
    return inp === cell.letter ? 'correct' : 'wrong';
}

// ===== INTERACTION =====
function onCellClick(cell) {
    if (!cell.letter) return;
    const wordsHere = placedWords.value.filter(pw => isInWord(pw, cell.r, cell.c));
    if (!wordsHere.length) return;

    if (activeCell.value?.r === cell.r && activeCell.value?.c === cell.c && wordsHere.length > 1) {
        const newDir = activeDir.value === 'across' ? 'down' : 'across';
        const w = wordsHere.find(w => w.direction === newDir);
        if (w) { activeDir.value = newDir; activeWordNum.value = w.number; }
    } else {
        activeCell.value = { r: cell.r, c: cell.c };
        const same = wordsHere.find(w => w.direction === activeDir.value);
        const chosen = same ?? wordsHere[0];
        activeDir.value   = chosen.direction;
        activeWordNum.value = chosen.number;
    }
}

function selectWord(w) {
    activeDir.value     = w.direction;
    activeWordNum.value = w.number;
    activeCell.value    = { r: w.row, c: w.col };
    gridRef.value?.focus();
}

function moveCaret(delta) {
    if (!activeCell.value) return;
    const dr = activeDir.value === 'down'   ? delta : 0;
    const dc = activeDir.value === 'across' ? delta : 0;
    const nr = activeCell.value.r + dr;
    const nc = activeCell.value.c + dc;
    const cell = grid.value[nr]?.[nc];
    if (cell?.letter) {
        activeCell.value = { r: nr, c: nc };
        const wordsHere = placedWords.value.filter(pw => isInWord(pw, nr, nc));
        const same = wordsHere.find(w => w.direction === activeDir.value);
        if (same) activeWordNum.value = same.number;
    }
}

function onKeydown(e) {
    if (!activeCell.value) return;
    const { r, c } = activeCell.value;

    if (e.key === 'Backspace') {
        e.preventDefault();
        if (inputs.value[ck(r, c)]) {
            inputs.value[ck(r, c)] = '';
        } else {
            moveCaret(-1);
        }
        checked.value = false;
        return;
    }
    if (e.key === 'Enter')       { e.preventDefault(); checkAnswers(); return; }
    if (e.key === 'ArrowRight')  { e.preventDefault(); activeDir.value = 'across'; moveCaret(1);  return; }
    if (e.key === 'ArrowLeft')   { e.preventDefault(); activeDir.value = 'across'; moveCaret(-1); return; }
    if (e.key === 'ArrowDown')   { e.preventDefault(); activeDir.value = 'down';   moveCaret(1);  return; }
    if (e.key === 'ArrowUp')     { e.preventDefault(); activeDir.value = 'down';   moveCaret(-1); return; }

    if (e.key.length === 1 && /[a-zA-Z]/.test(e.key)) {
        e.preventDefault();
        inputs.value[ck(r, c)] = e.key.toUpperCase();
        checked.value = false;
        moveCaret(1);
    }
}

// ===== CHECK =====
function checkAnswers() {
    checked.value = true;
    let correct = 0;
    for (const pw of placedWords.value) {
        let ok = true;
        for (let i = 0; i < pw.word.length; i++) {
            const r = pw.direction === 'down'   ? pw.row + i : pw.row;
            const c = pw.direction === 'across' ? pw.col + i : pw.col;
            if ((inputs.value[ck(r, c)] ?? '') !== pw.word[i]) { ok = false; break; }
        }
        if (ok) correct++;
    }
    score.value = placedWords.value.length > 0 ? Math.round((correct / placedWords.value.length) * 100) : 0;
    if (correct === placedWords.value.length) { playComplete(); finished.value = true; }
    else if (correct > 0) playCorrect();
    else playWrong();
}

function revealWord() {
    const w = getActiveWord();
    if (!w) return;
    for (let i = 0; i < w.word.length; i++) {
        const r = w.direction === 'down'   ? w.row + i : w.row;
        const c = w.direction === 'across' ? w.col + i : w.col;
        inputs.value[ck(r, c)] = w.word[i];
    }
    checked.value = false;
}

function revealAll() {
    for (const row of grid.value)
        for (const cell of row)
            if (cell.letter) inputs.value[ck(cell.r, cell.c)] = cell.letter;
    checked.value = false;
}

function restart() {
    inputs.value    = {};
    checked.value   = false;
    finished.value  = false;
    score.value     = 0;
    activeCell.value     = null;
    activeWordNum.value  = null;
}

onMounted(() => buildCrossword());
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-indigo-950 via-slate-900 to-slate-950 p-3 sm:p-6 select-none"
         @click.self="activeCell = null">

        <!-- Header -->
        <div class="text-center mb-5">
            <h1 class="text-xl sm:text-2xl font-bold text-white">{{ gameData.title }}</h1>
            <p v-if="gameData.description" class="text-indigo-300 text-sm mt-1">{{ gameData.description }}</p>
        </div>

        <!-- Finished overlay -->
        <Transition name="fade">
            <div v-if="finished"
                 class="fixed inset-0 z-50 bg-black/70 flex items-center justify-center p-4">
                <div class="bg-white rounded-3xl p-8 max-w-sm w-full text-center shadow-2xl">
                    <div class="text-6xl mb-4">🎉</div>
                    <h2 class="text-2xl font-bold text-slate-800 mb-2">Barakallo!</h2>
                    <p class="text-slate-600 mb-6">Krossvordni to'liq to'ldirdingiz!</p>
                    <button @click="restart"
                            class="bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-xl transition">
                        Qaytadan
                    </button>
                </div>
            </div>
        </Transition>

        <div class="max-w-6xl mx-auto flex flex-col xl:flex-row gap-5">

            <!-- Left: grid + controls -->
            <div class="flex-1 min-w-0">

                <!-- Grid -->
                <div
                    ref="gridRef"
                    tabindex="0"
                    @keydown="onKeydown"
                    class="outline-none overflow-x-auto cursor-default rounded-2xl"
                >
                    <div class="inline-block bg-slate-800/60 rounded-2xl p-2 sm:p-3 border border-slate-700">
                        <div v-for="(row, ri) in grid" :key="ri" class="flex">
                            <div
                                v-for="cell in row"
                                :key="cell.c"
                                @click.stop="onCellClick(cell); gridRef?.focus()"
                                :class="[
                                    'relative flex items-center justify-center font-bold text-sm transition-all',
                                    'w-8 h-8 sm:w-9 sm:h-9 border',
                                    cell.letter ? [
                                        'cursor-pointer',
                                        isCellActive(cell)
                                            ? 'bg-yellow-400 text-slate-900 border-yellow-500 z-10 scale-105 shadow-lg'
                                            : isCellHighlighted(cell)
                                                ? 'bg-indigo-300 text-indigo-900 border-indigo-400'
                                                : getCellStatus(cell) === 'correct'
                                                    ? 'bg-emerald-300 text-emerald-900 border-emerald-400'
                                                    : getCellStatus(cell) === 'wrong'
                                                        ? 'bg-red-300 text-red-900 border-red-400'
                                                        : getCellStatus(cell) === 'empty'
                                                            ? 'bg-amber-50 text-slate-500 border-amber-300'
                                                            : 'bg-white text-slate-800 border-slate-300 hover:bg-indigo-50'
                                    ] : 'bg-slate-900 border-slate-900 pointer-events-none'
                                ]"
                            >
                                <span v-if="cell.number"
                                      class="absolute top-0 left-0.5 text-[8px] font-bold leading-none"
                                      :class="isCellActive(cell) ? 'text-slate-700' : 'text-slate-400'">
                                    {{ cell.number }}
                                </span>
                                <span v-if="cell.letter" class="leading-none">
                                    {{ inputs[ck(cell.r, cell.c)] ?? '' }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Controls -->
                <div class="flex flex-wrap gap-2 mt-4">
                    <button @click="checkAnswers"
                            class="flex-1 sm:flex-none bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-2.5 rounded-xl text-sm transition shadow">
                        ✅ Tekshirish
                    </button>
                    <button @click="revealWord"
                            class="bg-amber-500 hover:bg-amber-600 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition"
                            title="Tanlangan so'zni ko'rsat">
                        💡 So'zni ko'rsat
                    </button>
                    <button @click="revealAll"
                            class="bg-slate-600 hover:bg-slate-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition">
                        🔍 Barchasini ko'rsat
                    </button>
                    <button @click="restart"
                            class="bg-slate-600 hover:bg-slate-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition">
                        🔄 Qayta
                    </button>
                </div>

                <!-- Score bar -->
                <div v-if="checked && !finished"
                     class="mt-3 bg-slate-800/60 border border-slate-700 rounded-xl p-3 flex items-center gap-3">
                    <div class="flex-1 bg-slate-700 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-500"
                             :class="score >= 80 ? 'bg-emerald-400' : score >= 50 ? 'bg-amber-400' : 'bg-red-400'"
                             :style="`width: ${score}%`"></div>
                    </div>
                    <span class="text-white font-bold text-sm">{{ score }}%</span>
                    <span class="text-slate-400 text-sm">to'g'ri</span>
                </div>
            </div>

            <!-- Right: clues -->
            <div class="xl:w-72 flex flex-col sm:flex-row xl:flex-col gap-4">

                <!-- Across -->
                <div class="flex-1 bg-slate-800/60 rounded-2xl p-4 border border-slate-700">
                    <h3 class="text-indigo-300 font-bold text-xs mb-3 uppercase tracking-widest flex items-center gap-1.5">
                        <span>→</span> Gorizontal
                    </h3>
                    <ul class="space-y-1.5 max-h-64 xl:max-h-96 overflow-y-auto pr-0.5">
                        <li v-for="w in acrossWords" :key="w.number"
                            @click="selectWord(w)"
                            :class="[
                                'flex gap-2 text-sm cursor-pointer rounded-lg px-2 py-1.5 transition leading-snug',
                                activeWordNum === w.number && activeDir === 'across'
                                    ? 'bg-indigo-600/50 text-white'
                                    : 'text-slate-300 hover:bg-slate-700/60'
                            ]">
                            <span class="font-bold text-indigo-400 shrink-0 w-5 text-xs pt-0.5">{{ w.number }}.</span>
                            <span>{{ w.clue }}</span>
                        </li>
                    </ul>
                </div>

                <!-- Down -->
                <div class="flex-1 bg-slate-800/60 rounded-2xl p-4 border border-slate-700">
                    <h3 class="text-purple-300 font-bold text-xs mb-3 uppercase tracking-widest flex items-center gap-1.5">
                        <span>↓</span> Vertikal
                    </h3>
                    <ul class="space-y-1.5 max-h-64 xl:max-h-96 overflow-y-auto pr-0.5">
                        <li v-for="w in downWords" :key="w.number"
                            @click="selectWord(w)"
                            :class="[
                                'flex gap-2 text-sm cursor-pointer rounded-lg px-2 py-1.5 transition leading-snug',
                                activeWordNum === w.number && activeDir === 'down'
                                    ? 'bg-purple-600/50 text-white'
                                    : 'text-slate-300 hover:bg-slate-700/60'
                            ]">
                            <span class="font-bold text-purple-400 shrink-0 w-5 text-xs pt-0.5">{{ w.number }}.</span>
                            <span>{{ w.clue }}</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Keyboard hint -->
        <p class="text-center text-slate-600 text-xs mt-6">
            Hujayraga bosing → harf yozing · Yo'nalish: ← → ↑ ↓ · Enter: tekshirish
        </p>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
