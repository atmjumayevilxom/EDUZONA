<script setup>
import { onMounted, ref, computed } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ gameId: Number });

const game     = ref(null);
const loading  = ref(true);
const error    = ref(null);
const showKey  = ref(false);

onMounted(async () => {
    try {
        const res = await axios.get(`/api/games/${props.gameId}`);
        game.value = res.data.data;
    } catch (e) {
        error.value = 'O\'yinni yuklashda xato yuz berdi.';
    } finally {
        loading.value = false;
    }
});

const templateCode = computed(() => game.value?.template?.code ?? '');
const json         = computed(() => game.value?.generated_json ?? {});
const items        = computed(() => json.value?.items ?? []);
const optionLabels = ['A', 'B', 'C', 'D', 'E'];

const today = new Date().toLocaleDateString('uz-Latn-UZ', {
    year: 'numeric', month: '2-digit', day: '2-digit',
});

// Reorder: deterministic shuffle for print (stable across renders)
function printShuffle(arr) {
    const a = [...arr];
    for (let i = a.length - 1; i > 0; i--) {
        const j = (i * 7 + 3) % (i + 1);
        [a[i], a[j]] = [a[j], a[i]];
    }
    return a;
}
</script>

<template>
    <Head :title="`Chop etish — ${game?.topic ?? 'O\'yin'}`" />

    <!-- Print controls (hidden when printing) -->
    <div class="no-print fixed top-0 left-0 right-0 z-50 bg-white border-b border-slate-200 shadow-sm px-6 py-3 flex items-center gap-3">
        <Link :href="game ? `/games/${game.id}` : '/dashboard'" class="text-slate-500 hover:text-slate-800 text-sm flex items-center gap-1">
            ← Orqaga
        </Link>
        <span class="text-slate-300">|</span>
        <span class="font-medium text-slate-700 text-sm">{{ game?.topic ?? 'Yuklanmoqda...' }}</span>
        <div class="flex-1"></div>
        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer">
            <input type="checkbox" v-model="showKey" class="rounded" />
            Javoblar bilan
        </label>
        <button
            @click="print()"
            class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold px-5 py-2 rounded-lg transition"
        >
            🖨️ Chop etish
        </button>
    </div>

    <div class="pt-16 min-h-screen bg-slate-100 no-print-bg">

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-32">
            <div class="text-center">
                <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-slate-400 text-sm">Yuklanmoqda...</p>
            </div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="max-w-xl mx-auto py-20 text-center text-red-600">{{ error }}</div>

        <!-- Printable sheet -->
        <div v-else-if="game" class="print-sheet mx-auto my-6">

            <!-- Header -->
            <div class="sheet-header">
                <div class="header-meta">
                    <div class="meta-field">
                        <span class="meta-label">Sana:</span>
                        <span class="meta-line">{{ today }}</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Ism:</span>
                        <span class="meta-line blank-line">____________________________</span>
                    </div>
                    <div class="meta-field">
                        <span class="meta-label">Sinf:</span>
                        <span class="meta-line blank-line">__________</span>
                    </div>
                </div>
                <div class="header-title">
                    <h1 class="sheet-title">{{ json.title ?? game.topic }}</h1>
                    <p v-if="json.description" class="sheet-subtitle">{{ json.description }}</p>
                    <div class="sheet-badges">
                        <span class="badge">{{ game.template?.name }}</span>
                        <span class="badge">{{ (game.language ?? 'uz').toUpperCase() }}</span>
                        <span class="badge">{{ items.length }} ta savol</span>
                    </div>
                </div>
            </div>

            <hr class="sheet-divider" />

            <!-- Quiz MCQ worksheet -->
            <div v-if="templateCode === 'quiz_mcq'" class="questions-list">
                <div v-for="(item, idx) in items" :key="item.id" class="question-block">
                    <div class="question-row">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <span class="question-text">{{ item.question }}</span>
                    </div>
                    <div class="options-grid">
                        <div
                            v-for="(opt, oi) in item.options"
                            :key="oi"
                            :class="['option-item', showKey && oi === item.answer_index ? 'option-correct' : '']"
                        >
                            <span class="option-label">{{ optionLabels[oi] }}</span>
                            <span class="option-text">{{ opt }}</span>
                            <span v-if="showKey && oi === item.answer_index" class="correct-check">✓</span>
                        </div>
                    </div>
                    <div v-if="showKey && item.explanation" class="explanation">
                        💡 {{ item.explanation }}
                    </div>
                </div>
            </div>

            <!-- Anagram worksheet -->
            <div v-else-if="templateCode === 'anagram'" class="anagram-list">
                <div v-for="(item, idx) in items" :key="item.id" class="anagram-block">
                    <div class="anagram-row">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <div class="anagram-content">
                            <div class="anagram-scrambled">
                                <span v-for="(ch, ci) in item.scrambled.split('')" :key="ci" class="letter-box">{{ ch }}</span>
                            </div>
                            <div class="anagram-hint" v-if="item.hint">💡 {{ item.hint }}</div>
                            <div class="anagram-answer-row">
                                <span class="answer-label">Javob:</span>
                                <span v-if="showKey" class="answer-revealed">{{ item.original }}</span>
                                <span v-else class="answer-blank">
                                    <span v-for="i in item.original.length" :key="i" class="answer-box">_</span>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- True/False worksheet -->
            <div v-else-if="templateCode === 'true_false'" class="tf-list">
                <div v-for="(item, idx) in items" :key="item.id ?? idx" class="tf-block">
                    <div class="tf-row">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <span class="question-text">{{ item.statement }}</span>
                        <div class="tf-choices">
                            <span :class="['tf-choice', showKey && item.answer === true  ? 'tf-correct' : '']">T</span>
                            <span :class="['tf-choice', showKey && item.answer === false ? 'tf-correct' : '']">F</span>
                        </div>
                    </div>
                    <div v-if="showKey && item.explanation" class="explanation">💡 {{ item.explanation }}</div>
                </div>
            </div>

            <!-- Matching Pairs worksheet -->
            <div v-else-if="templateCode === 'matching_pairs'" class="matching-layout">
                <div class="matching-columns">
                    <div class="matching-col">
                        <div class="col-header">A ustun</div>
                        <div v-for="(item, idx) in items" :key="'l'+idx" class="match-row">
                            <span class="match-num">{{ idx + 1 }}.</span>
                            <span class="match-text">{{ item.left }}</span>
                            <span class="match-blank">___</span>
                        </div>
                    </div>
                    <div class="matching-col">
                        <div class="col-header">B ustun</div>
                        <div v-for="(item, idx) in items" :key="'r'+idx" class="match-row">
                            <span class="match-num">{{ String.fromCharCode(97 + idx) }})</span>
                            <span class="match-text">{{ item.right }}</span>
                        </div>
                    </div>
                </div>
                <div v-if="showKey" class="answer-key-section">
                    <h3 class="key-title">Javoblar kaliti</h3>
                    <div class="key-grid">
                        <span v-for="(item, idx) in items" :key="idx" class="key-item">
                            <strong>{{ idx + 1 }}.</strong> {{ String.fromCharCode(97 + idx) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Flashcards worksheet -->
            <div v-else-if="templateCode === 'flashcards'" class="flashcard-grid">
                <div v-for="(item, idx) in items" :key="item.id ?? idx" class="flashcard-pair">
                    <div class="flashcard-num">{{ idx + 1 }}</div>
                    <div class="flashcard-front">{{ item.front }}</div>
                    <div class="flashcard-arrow">→</div>
                    <div class="flashcard-back">
                        <span v-if="showKey">{{ item.back }}</span>
                        <span v-else class="answer-blank-line">______________________________</span>
                    </div>
                </div>
            </div>

            <!-- Type Answer worksheet -->
            <div v-else-if="templateCode === 'type_answer'" class="type-list">
                <div v-for="(item, idx) in items" :key="item.id ?? idx" class="type-block">
                    <div class="question-row">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <span class="question-text">{{ item.question }}</span>
                    </div>
                    <div class="type-answer-row">
                        <span v-if="showKey" class="answer-revealed">{{ item.answer }}</span>
                        <span v-else class="type-line">_____________________________________________</span>
                    </div>
                </div>
            </div>

            <!-- Complete Sentence worksheet -->
            <div v-else-if="templateCode === 'complete_sentence'" class="fill-list">
                <div v-for="(item, idx) in items" :key="item.id ?? idx" class="fill-block">
                    <div class="question-row">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <span class="question-text fill-sentence">
                            {{ item.sentence?.replace('___', showKey ? ('【' + item.answer + '】') : '___________') }}
                        </span>
                    </div>
                </div>
                <div v-if="showKey" class="answer-key-section">
                    <h3 class="key-title">Javoblar kaliti</h3>
                    <div class="key-grid">
                        <span v-for="(item, idx) in items" :key="idx" class="key-item">
                            <strong>{{ idx + 1 }}.</strong> {{ item.answer }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Reorder worksheet -->
            <div v-else-if="templateCode === 'reorder'" class="reorder-list">
                <div v-for="(item, idx) in items" :key="item.id ?? idx" class="reorder-block">
                    <div class="question-row mb-2">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <div class="reorder-words">
                            <span v-for="(word, wi) in printShuffle((item.text ?? '').split(' '))" :key="wi" class="word-chip">{{ word }}</span>
                        </div>
                    </div>
                    <div class="reorder-answer-row">
                        <span v-if="showKey" class="answer-revealed">{{ item.text }}</span>
                        <span v-else class="type-line">_____________________________________________</span>
                    </div>
                </div>
            </div>

            <!-- Generic worksheet (other templates) -->
            <div v-else class="generic-list">
                <div v-for="(item, idx) in items" :key="item.id ?? idx" class="generic-block">
                    <div class="question-row">
                        <span class="question-num">{{ idx + 1 }}.</span>
                        <span class="question-text">{{ item.question ?? item.word ?? item.term ?? JSON.stringify(item) }}</span>
                    </div>
                    <div class="answer-line-row">
                        <span class="answer-label">Javob:</span>
                        <span v-if="showKey" class="answer-revealed">{{ item.answer ?? item.original ?? item.correct ?? '—' }}</span>
                        <span v-else class="answer-blank">_________________________________</span>
                    </div>
                </div>
            </div>

            <!-- Score box at bottom -->
            <div class="score-box">
                <div class="score-inner">
                    <span>Ball:</span>
                    <span class="score-slash">___</span>
                    <span>/ {{ items.length }}</span>
                </div>
                <div class="score-inner">
                    <span>Baho:</span>
                    <span class="score-slash">___</span>
                </div>
                <div class="score-inner">
                    <span>O'qituvchi:</span>
                    <span class="score-slash">____________________</span>
                </div>
            </div>

            <!-- Answer key (shown only if showKey) -->
            <div v-if="showKey && templateCode === 'quiz_mcq'" class="answer-key-section">
                <h3 class="key-title">Javoblar kaliti</h3>
                <div class="key-grid">
                    <span v-for="(item, idx) in items" :key="item.id" class="key-item">
                        <strong>{{ idx + 1 }}.</strong> {{ optionLabels[item.answer_index] }}
                    </span>
                </div>
            </div>

        </div>
    </div>
</template>

<style>
/* ─── Screen-only controls ─── */
.no-print-bg { background: #f3f4f6; }

/* ─── Print sheet container ─── */
.print-sheet {
    background: white;
    width: 210mm;
    min-height: 297mm;
    padding: 18mm 20mm;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
    font-family: 'Arial', sans-serif;
    font-size: 11pt;
    color: #1a1a1a;
}

/* ─── Header ─── */
.sheet-header {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-bottom: 12px;
}
.header-meta {
    display: flex;
    gap: 24px;
    font-size: 9.5pt;
    color: #555;
}
.meta-field { display: flex; align-items: baseline; gap: 4px; }
.meta-label { font-weight: 600; white-space: nowrap; }
.meta-line  { min-width: 80px; }
.blank-line { border-bottom: 1px solid #aaa; display: inline-block; }

.header-title { text-align: center; }
.sheet-title  { font-size: 16pt; font-weight: 700; margin: 0 0 4px; }
.sheet-subtitle { font-size: 10pt; color: #555; margin: 0 0 8px; }
.sheet-badges { display: flex; justify-content: center; gap: 8px; }
.badge {
    font-size: 8pt;
    background: #f0f4ff;
    color: #3730a3;
    border: 1px solid #c7d2fe;
    border-radius: 99px;
    padding: 2px 10px;
}

.sheet-divider { border: none; border-top: 2px solid #e5e7eb; margin: 10px 0 16px; }

/* ─── Quiz MCQ ─── */
.questions-list { display: flex; flex-direction: column; gap: 14px; }
.question-block { page-break-inside: avoid; }
.question-row   { display: flex; gap: 6px; margin-bottom: 8px; align-items: flex-start; }
.question-num   { font-weight: 700; min-width: 22px; flex-shrink: 0; }
.question-text  { font-weight: 500; line-height: 1.4; }

.options-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; padding-left: 28px; }
.option-item  { display: flex; align-items: center; gap: 6px; font-size: 10.5pt; padding: 3px 8px; border-radius: 4px; }
.option-label {
    width: 22px; height: 22px;
    border: 1.5px solid #d1d5db;
    border-radius: 50%;
    display: flex; align-items: center; justify-content: center;
    font-weight: 700; font-size: 9pt;
    flex-shrink: 0;
}
.option-text  { flex: 1; }
.option-correct { background: #f0fdf4; }
.option-correct .option-label { background: #16a34a; color: white; border-color: #16a34a; }
.correct-check { color: #16a34a; font-weight: 700; }
.explanation { font-size: 9pt; color: #6b7280; padding-left: 28px; margin-top: 4px; font-style: italic; }

/* ─── Anagram ─── */
.anagram-list { display: flex; flex-direction: column; gap: 18px; }
.anagram-block { page-break-inside: avoid; }
.anagram-row  { display: flex; gap: 8px; align-items: flex-start; }
.anagram-content { display: flex; flex-direction: column; gap: 8px; flex: 1; }
.anagram-scrambled { display: flex; gap: 5px; flex-wrap: wrap; }
.letter-box {
    width: 32px; height: 36px;
    border: 2px solid #6366f1;
    border-radius: 6px;
    display: flex; align-items: center; justify-content: center;
    font-size: 14pt; font-weight: 700; color: #4f46e5;
    background: #eef2ff;
}
.anagram-hint { font-size: 9pt; color: #9ca3af; font-style: italic; }
.anagram-answer-row { display: flex; align-items: center; gap: 8px; }
.answer-label { font-size: 9.5pt; font-weight: 600; color: #374151; white-space: nowrap; }
.answer-revealed { font-weight: 700; color: #16a34a; font-size: 12pt; letter-spacing: 0.05em; }
.answer-blank { display: flex; gap: 4px; }
.answer-box { width: 24px; border-bottom: 2px solid #374151; display: inline-block; text-align: center; font-size: 13pt; }

/* ─── Generic ─── */
.generic-list { display: flex; flex-direction: column; gap: 12px; }
.generic-block { page-break-inside: avoid; }
.answer-line-row { display: flex; gap: 8px; align-items: center; padding-left: 28px; margin-top: 4px; font-size: 10pt; }

/* ─── Score box ─── */
.score-box {
    margin-top: 24px;
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 10px 16px;
    display: flex;
    gap: 32px;
    flex-wrap: wrap;
    font-size: 10.5pt;
    page-break-inside: avoid;
}
.score-inner { display: flex; align-items: baseline; gap: 6px; }
.score-slash { border-bottom: 1.5px solid #374151; min-width: 40px; display: inline-block; }

/* ─── Answer key ─── */
.answer-key-section { margin-top: 24px; padding-top: 16px; border-top: 2px dashed #e5e7eb; }
.key-title { font-size: 11pt; font-weight: 700; margin: 0 0 10px; }
.key-grid  { display: grid; grid-template-columns: repeat(5, 1fr); gap: 6px 16px; }
.key-item  { font-size: 10pt; }

/* ─── True/False ─── */
.tf-list { display: flex; flex-direction: column; gap: 10px; }
.tf-block { page-break-inside: avoid; }
.tf-row   { display: flex; align-items: flex-start; gap: 6px; }
.tf-choices { display: flex; gap: 8px; margin-left: 10px; flex-shrink: 0; }
.tf-choice {
    width: 32px; height: 22px;
    border: 1.5px solid #9ca3af; border-radius: 4px;
    display: flex; align-items: center; justify-content: center;
    font-size: 9pt; font-weight: 700; color: #374151;
}
.tf-correct { background: #f0fdf4; border-color: #16a34a; color: #16a34a; }

/* ─── Matching Pairs ─── */
.matching-layout {}
.matching-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.matching-col {}
.col-header { font-weight: 700; font-size: 10pt; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1.5px solid #e5e7eb; }
.match-row { display: flex; align-items: baseline; gap: 6px; padding: 5px 0; border-bottom: 1px dotted #e5e7eb; }
.match-num { font-weight: 700; min-width: 20px; flex-shrink: 0; }
.match-text { flex: 1; }
.match-blank { min-width: 28px; font-weight: 700; color: #6366f1; }

/* ─── Flashcards ─── */
.flashcard-grid { display: flex; flex-direction: column; gap: 8px; }
.flashcard-pair {
    display: grid; grid-template-columns: 22px 1fr 18px 1fr;
    align-items: center; gap: 8px;
    padding: 8px 10px; border: 1px solid #e5e7eb; border-radius: 6px;
    page-break-inside: avoid;
}
.flashcard-num { font-weight: 700; color: #6b7280; font-size: 9pt; }
.flashcard-front { font-weight: 600; }
.flashcard-arrow { text-align: center; color: #9ca3af; }
.flashcard-back { color: #16a34a; font-weight: 600; }
.answer-blank-line { border-bottom: 1px solid #9ca3af; display: block; height: 16px; }

/* ─── Type Answer ─── */
.type-list { display: flex; flex-direction: column; gap: 14px; }
.type-block { page-break-inside: avoid; }
.type-answer-row { padding-left: 28px; margin-top: 6px; }
.type-line { display: block; border-bottom: 1px solid #6b7280; min-height: 20px; }

/* ─── Complete Sentence ─── */
.fill-list { display: flex; flex-direction: column; gap: 12px; }
.fill-block { page-break-inside: avoid; }
.fill-sentence { line-height: 1.8; }

/* ─── Reorder ─── */
.reorder-list { display: flex; flex-direction: column; gap: 16px; }
.reorder-block { page-break-inside: avoid; }
.reorder-words { display: flex; flex-wrap: wrap; gap: 6px; flex: 1; }
.word-chip {
    padding: 2px 10px; border: 1.5px solid #c7d2fe;
    border-radius: 99px; background: #eef2ff;
    color: #4f46e5; font-weight: 600; font-size: 10pt;
}
.reorder-answer-row { padding-left: 28px; margin-top: 4px; }
.mb-2 { margin-bottom: 6px; }

/* ─── Print media ─── */
@media print {
    .no-print { display: none !important; }
    .no-print-bg { background: white !important; padding-top: 0 !important; }
    .print-sheet {
        width: 100%;
        box-shadow: none;
        padding: 10mm 14mm;
        margin: 0;
        min-height: auto;
    }
    body { margin: 0; padding: 0; }
    @page { margin: 10mm; size: A4; }
}
</style>
