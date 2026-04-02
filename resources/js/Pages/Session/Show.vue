<script setup>
import { ref, computed, onMounted, onUnmounted, watch, defineAsyncComponent } from 'vue';
import { Head } from '@inertiajs/vue3';
import GameLayout from '@/Components/GameLayout.vue';
import { useTemplateMeta } from '@/composables/useTemplateMeta';
import { useGameAudio } from '@/composables/useGameAudio';
import axios from 'axios';

const renderers = {
    quiz_mcq:          defineAsyncComponent(() => import('@/Components/Renderers/QuizRenderer.vue')),
    anagram:           defineAsyncComponent(() => import('@/Components/Renderers/AnagramRenderer.vue')),
    true_false:        defineAsyncComponent(() => import('@/Components/Renderers/TrueFalseRenderer.vue')),
    flashcards:        defineAsyncComponent(() => import('@/Components/Renderers/FlashcardsRenderer.vue')),
    matching_pairs:    defineAsyncComponent(() => import('@/Components/Renderers/MatchingPairsRenderer.vue')),
    type_answer:       defineAsyncComponent(() => import('@/Components/Renderers/TypeAnswerRenderer.vue')),
    random_wheel:      defineAsyncComponent(() => import('@/Components/Renderers/RandomWheelRenderer.vue')),
    open_box:          defineAsyncComponent(() => import('@/Components/Renderers/OpenBoxRenderer.vue')),
    complete_sentence: defineAsyncComponent(() => import('@/Components/Renderers/CompleteSentenceRenderer.vue')),
    hangman:           defineAsyncComponent(() => import('@/Components/Renderers/HangmanRenderer.vue')),
    reorder:           defineAsyncComponent(() => import('@/Components/Renderers/ReorderRenderer.vue')),
    group_sort:        defineAsyncComponent(() => import('@/Components/Renderers/GroupSortRenderer.vue')),
    whack_mole:        defineAsyncComponent(() => import('@/Components/Renderers/WhackMoleRenderer.vue')),
    word_search:       defineAsyncComponent(() => import('@/Components/Renderers/WordSearchRenderer.vue')),
    memory_cards:      defineAsyncComponent(() => import('@/Components/Renderers/MemoryCardsRenderer.vue')),
    game_show_quiz:    defineAsyncComponent(() => import('@/Components/Renderers/GameShowRenderer.vue')),
    flying_answers:    defineAsyncComponent(() => import('@/Components/Renderers/FlyingAnswersRenderer.vue')),
    pair_or_not:       defineAsyncComponent(() => import('@/Components/Renderers/PairOrNotRenderer.vue')),
    speed_sort:        defineAsyncComponent(() => import('@/Components/Renderers/SpeedSortRenderer.vue')),
    spell_word:        defineAsyncComponent(() => import('@/Components/Renderers/SpellWordRenderer.vue')),
    airplane:          defineAsyncComponent(() => import('@/Components/Renderers/AirplaneRenderer.vue')),
    watch_memorize:    defineAsyncComponent(() => import('@/Components/Renderers/WatchMemorizeRenderer.vue')),
    win_or_lose:       defineAsyncComponent(() => import('@/Components/Renderers/WinOrLoseRenderer.vue')),
    math_quiz:         defineAsyncComponent(() => import('@/Components/Renderers/MathQuizRenderer.vue')),
    millionaire:       defineAsyncComponent(() => import('@/Components/Renderers/MillionaireRenderer.vue')),
    spelling:          defineAsyncComponent(() => import('@/Components/Renderers/SpellingRenderer.vue')),
    diagram:           defineAsyncComponent(() => import('@/Components/Renderers/DiagramRenderer.vue')),
    zakovat:           defineAsyncComponent(() => import('@/Components/Renderers/ZakovatRenderer.vue')),
    race:              defineAsyncComponent(() => import('@/Components/Renderers/RaceRenderer.vue')),
    timeline:          defineAsyncComponent(() => import('@/Components/Renderers/TimelineRenderer.vue')),
};

const props      = defineProps({ sessionCode: String });
const { tplMeta } = useTemplateMeta();
const audio       = useGameAudio();

// O'quvchi identifikatsiyasi — akkaunt kerak emas
// LocalStorage da UUID token saqlanadi, keyingi sessiyalarda ham taniladi
function getOrCreateStudentToken() {
    const KEY = 'eduzona_student_token';
    let token = localStorage.getItem(KEY);
    if (!token) {
        // crypto.randomUUID zamonaviy brauzerlarda mavjud
        token = (typeof crypto !== 'undefined' && crypto.randomUUID)
            ? crypto.randomUUID()
            : Math.random().toString(36).slice(2) + Date.now().toString(36);
        localStorage.setItem(KEY, token);
    }
    return token;
}

const pageState   = ref('loading');
const session     = ref(null);
const loadError   = ref(null);
const playerName  = ref('');
const nameError   = ref('');
const score       = ref(0);
const scoreError  = ref('');
const saving      = ref(false);
const saved       = ref(false);
const leaderboard = ref([]);
const lbLoading   = ref(false);

const templateCode      = computed(() => session.value?.game?.template?.code);
const meta              = computed(() => tplMeta(templateCode.value));
const rendererComponent = computed(() => renderers[templateCode.value] ?? null);
const gameData          = computed(() => session.value?.game?.generated_json ?? null);
const maxScore          = computed(() => session.value?.game?.students_count ?? 10);

const myRank = computed(() => {
    if (!saved.value || !leaderboard.value.length) return null;
    const idx = leaderboard.value.findIndex(
        r => r.participant_name === playerName.value && r.score === score.value
    );
    return idx >= 0 ? idx + 1 : null;
});

async function loadSession() {
    try {
        const res = await axios.get(`/api/sessions/${props.sessionCode}`);
        session.value = res.data.data;
        if (session.value.status === 'ended') {
            await loadLeaderboard();
            pageState.value = 'leaderboard';
        } else {
            pageState.value = 'enter_name';
        }
    } catch {
        loadError.value = 'Sessiya topilmadi yoki muddati tugagan.';
        pageState.value = 'error';
    }
}

function startGame() {
    nameError.value = '';
    if (!playerName.value.trim() || playerName.value.trim().length < 2) {
        nameError.value = "Iltimos, to'liq ismingizni kiriting (kamida 2 harf).";
        return;
    }
    score.value = 0;
    pageState.value = 'playing';
}

function openSaveScore() { pageState.value = 'save_score'; }

async function submitScore() {
    scoreError.value = '';
    if (score.value < 0 || score.value > maxScore.value) {
        scoreError.value = `Ball 0 dan ${maxScore.value} gacha bo'lishi kerak.`;
        return;
    }
    saving.value = true;
    try {
        await axios.post(`/api/sessions/${props.sessionCode}/results`, {
            participant_name: playerName.value.trim(),
            score:            score.value,
            total:            maxScore.value,
            answers_json:     [],
            student_token:    getOrCreateStudentToken(), // qurilma asosida tanib olish uchun
        });
        saved.value = true;
        await loadLeaderboard();
        pageState.value = 'leaderboard';
    } catch {
        scoreError.value = "Xato yuz berdi. Qaytadan urinib ko'ring.";
    } finally {
        saving.value = false;
    }
}

async function loadLeaderboard() {
    lbLoading.value = true;
    try {
        const res = await axios.get(`/api/sessions/${props.sessionCode}/results`);
        leaderboard.value = res.data.data.results ?? [];
    } finally {
        lbLoading.value = false;
    }
}

function playAgain() {
    saved.value     = false;
    score.value     = 0;
    pageState.value = 'playing';
}

let echoChannel = null;

function subscribeEcho() {
    if (!window.Echo) return;
    echoChannel = window.Echo.channel(`session.${props.sessionCode}`)
        .listen('.result.submitted', ({ result }) => {
            // Add new result if not already present, keep sorted by score desc
            const exists = leaderboard.value.some(r => r.id === result.id);
            if (!exists) {
                leaderboard.value = [...leaderboard.value, result]
                    .sort((a, b) => b.score - a.score);
            }
        })
        .listen('.session.ended', () => {
            // Session ended — reload full leaderboard then show it
            loadLeaderboard().then(() => {
                pageState.value = 'leaderboard';
            });
        });
}

onMounted(() => {
    loadSession();
    subscribeEcho();
});

onUnmounted(() => {
    audio.stop();
    if (echoChannel) window.Echo.leaveChannel(`session.${props.sessionCode}`);
});

// Start BGM when student starts playing
watch(
    () => [pageState.value, templateCode.value],
    ([state, code]) => {
        if (state === 'playing' && code) audio.play(code);
        if (state === 'save_score' || state === 'leaderboard') audio.stop();
    }
);
</script>

<template>
    <Head :title="`Sessiya: ${sessionCode}`" />

    <!-- ══════════════════════════════════════════════
         PLAYING — uses GameLayout for full-screen feel
    ══════════════════════════════════════════════════ -->
    <GameLayout
        v-if="pageState === 'playing'"
        :title="session?.game?.topic ?? 'O\'yin'"
        :subtitle="playerName"
        :template-icon="meta.icon"
        :template-color="meta.color"
    >
        <template #actions>
            <button @click="openSaveScore"
                class="flex items-center gap-1.5 bg-green-600 hover:bg-green-700 text-white text-xs sm:text-sm font-semibold px-3 sm:px-4 py-2 rounded-xl transition shadow-sm shadow-green-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="hidden sm:inline">Natijani saqlash</span>
                <span class="sm:hidden">Saqlash</span>
            </button>
        </template>

        <div class="w-full max-w-4xl mx-auto">
            <component v-if="rendererComponent && gameData"
                :is="rendererComponent"
                :game-data="gameData"
            />
        </div>
        <div v-if="!rendererComponent || !gameData" class="max-w-lg mx-auto bg-amber-50 border-2 border-amber-200 rounded-3xl p-12 text-center mt-8">
            <div class="text-5xl mb-4">🚧</div>
            <p class="text-amber-700 text-sm font-medium">Bu o'yin turi uchun renderer mavjud emas.</p>
        </div>
    </GameLayout>

    <!-- ══════════════════════════════════════════════
         All other states (no sidebar/layout needed)
    ══════════════════════════════════════════════════ -->
    <div v-else class="min-h-screen bg-gradient-to-br from-slate-50 via-indigo-50/40 to-purple-50/30">

        <!-- LOADING -->
        <div v-if="pageState === 'loading'" class="flex items-center justify-center min-h-screen">
            <div class="text-center">
                <div class="w-14 h-14 border-4 border-indigo-200 border-t-indigo-500 rounded-full animate-spin mx-auto mb-5"></div>
                <p class="text-slate-400 text-sm font-medium">Yuklanmoqda...</p>
            </div>
        </div>

        <!-- ERROR -->
        <div v-else-if="pageState === 'error'" class="flex items-center justify-center min-h-screen p-4">
            <div class="max-w-sm w-full bg-white rounded-3xl shadow-2xl p-10 text-center">
                <div class="text-6xl mb-5">😕</div>
                <h2 class="text-xl font-bold text-slate-800 mb-2">Sessiya topilmadi</h2>
                <p class="text-slate-500 text-sm mb-7">{{ loadError }}</p>
                <a href="/"
                    class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-7 py-3 rounded-2xl text-sm font-semibold transition shadow-lg shadow-indigo-200">
                    Bosh sahifaga
                </a>
            </div>
        </div>

        <!-- ENTER NAME -->
        <div v-else-if="pageState === 'enter_name'"
            class="flex items-center justify-center min-h-screen p-4">
            <div class="w-full max-w-sm card-appear">
                <!-- Game card -->
                <div class="bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden mb-4">
                    <div :class="['h-36 bg-gradient-to-br relative flex items-center justify-center overflow-hidden', meta.color]">
                        <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-10 -left-10 w-36 h-36 bg-white/10 rounded-full"></div>
                        <span class="text-7xl relative z-10 drop-shadow-sm">{{ meta.icon }}</span>
                    </div>
                    <div class="p-7 text-center">
                        <div class="inline-flex items-center gap-1.5 text-xs font-bold bg-indigo-100 text-indigo-700 px-3 py-1.5 rounded-full mb-3">
                            {{ session?.game?.template?.name ?? "O'yin" }}
                        </div>
                        <h1 class="text-xl font-extrabold text-slate-900 mb-1 leading-tight">
                            {{ session?.game?.topic }}
                        </h1>
                        <p class="text-sm text-slate-500">
                            Kod: <span class="font-mono font-bold text-indigo-600 tracking-widest">{{ sessionCode }}</span>
                        </p>
                    </div>
                </div>

                <!-- Name input -->
                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 p-7">
                    <h2 class="font-bold text-slate-800 text-base mb-5 text-center">Ismingizni kiriting</h2>
                    <input
                        v-model="playerName"
                        @keyup.enter="startGame"
                        type="text"
                        placeholder="Ism Familiya"
                        maxlength="50"
                        autofocus
                        class="w-full px-4 py-3.5 rounded-2xl border-2 border-slate-200 text-base focus:outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition text-center font-semibold"
                    />
                    <p v-if="nameError" class="text-red-500 text-xs mt-2 text-center">{{ nameError }}</p>
                    <button @click="startGame"
                        class="mt-4 w-full bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold py-4 rounded-2xl transition text-base shadow-xl shadow-indigo-200/60">
                        O'yinni boshlash →
                    </button>
                </div>
                <p class="text-center text-xs text-slate-400 mt-5 font-medium">EDUZONA</p>
            </div>
        </div>

        <!-- SAVE SCORE -->
        <div v-else-if="pageState === 'save_score'"
            class="flex items-center justify-center min-h-screen p-4">
            <div class="max-w-sm w-full bg-white rounded-3xl shadow-2xl border border-slate-100 overflow-hidden card-appear">
                <div class="h-24 bg-gradient-to-r from-green-500 to-emerald-600 flex items-center justify-center">
                    <span class="text-5xl">🏆</span>
                </div>
                <div class="p-7">
                    <h2 class="font-extrabold text-slate-800 text-xl text-center mb-1">Natijangizni saqlang</h2>
                    <p class="text-xs text-slate-500 text-center mb-6">O'yindagi ballingizni kiriting</p>

                    <div class="bg-indigo-50 rounded-2xl px-4 py-3 mb-5 text-center">
                        <span class="text-xs text-indigo-500 font-semibold">O'yinchi:</span>
                        <div class="font-bold text-indigo-800 text-base mt-0.5">{{ playerName }}</div>
                    </div>

                    <label class="block text-xs font-bold text-slate-600 mb-2 text-center">
                        Necha ball? (0–{{ maxScore }})
                    </label>
                    <input
                        v-model.number="score"
                        type="number"
                        :min="0"
                        :max="maxScore"
                        class="w-full px-4 py-3.5 rounded-2xl border-2 border-slate-200 text-3xl font-extrabold text-center focus:outline-none focus:border-indigo-400 focus:ring-4 focus:ring-indigo-100 transition"
                    />
                    <p v-if="scoreError" class="text-red-500 text-xs mt-1.5 text-center">{{ scoreError }}</p>

                    <!-- Progress bar -->
                    <div class="mt-4 mb-6">
                        <div class="flex justify-between text-xs text-slate-400 mb-1.5">
                            <span>0</span><span>{{ maxScore }}</span>
                        </div>
                        <div class="h-3 bg-slate-100 rounded-full overflow-hidden">
                            <div class="h-3 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-300"
                                :style="{ width: maxScore ? Math.min(100, (score / maxScore) * 100) + '%' : '0%' }">
                            </div>
                        </div>
                        <div class="text-center text-base font-extrabold text-indigo-600 mt-2">
                            {{ maxScore ? Math.round((score / maxScore) * 100) : 0 }}%
                        </div>
                    </div>

                    <div class="flex gap-3">
                        <button @click="pageState = 'playing'"
                            class="flex-1 bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold py-3.5 rounded-2xl transition text-sm">
                            ← Orqaga
                        </button>
                        <button @click="submitScore" :disabled="saving"
                            class="flex-1 bg-green-600 hover:bg-green-700 disabled:bg-green-300 text-white font-bold py-3.5 rounded-2xl transition text-sm shadow-lg shadow-green-200">
                            {{ saving ? 'Saqlanmoqda...' : 'Saqlash ✓' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- LEADERBOARD -->
        <div v-else-if="pageState === 'leaderboard'" class="min-h-screen p-4 sm:p-6">
            <div class="max-w-lg mx-auto pt-4">

                <!-- My result banner -->
                <div v-if="saved"
                    class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-3xl p-7 mb-6 text-white text-center shadow-2xl shadow-indigo-300/40">
                    <div class="text-6xl mb-3">
                        {{ myRank === 1 ? '🥇' : myRank === 2 ? '🥈' : myRank === 3 ? '🥉' : '🏅' }}
                    </div>
                    <h2 class="text-xl font-extrabold mb-1">{{ playerName }}</h2>
                    <div class="text-5xl font-black mb-1">{{ score }}</div>
                    <div class="text-indigo-200 text-sm">
                        {{ maxScore }} dan {{ score }} ball
                        <span v-if="myRank" class="ml-2 font-bold text-white">· {{ myRank }}-o'rin</span>
                    </div>
                    <div class="mt-5 bg-white/20 rounded-2xl p-4">
                        <div class="h-2.5 bg-white/30 rounded-full overflow-hidden">
                            <div class="h-2.5 bg-white rounded-full transition-all duration-500"
                                :style="{ width: maxScore ? Math.round((score / maxScore) * 100) + '%' : '0%' }">
                            </div>
                        </div>
                        <div class="text-white/80 text-xs mt-2 font-medium">
                            {{ maxScore ? Math.round((score / maxScore) * 100) : 0 }}% to'g'ri
                        </div>
                    </div>
                </div>

                <!-- Leaderboard header -->
                <div class="flex items-center justify-between mb-4">
                    <h3 class="font-extrabold text-slate-800 text-lg">🏆 Natijalar jadvali</h3>
                    <button @click="loadLeaderboard"
                        class="text-xs text-indigo-500 hover:text-indigo-700 font-semibold transition">
                        Yangilash ↻
                    </button>
                </div>

                <div v-if="lbLoading" class="text-center py-10 text-slate-400 text-sm">Yuklanmoqda...</div>

                <div v-else-if="leaderboard.length === 0"
                    class="bg-white rounded-3xl border border-slate-100 p-10 text-center shadow-sm">
                    <div class="text-5xl mb-4">📋</div>
                    <p class="text-slate-500 text-sm">Hali natija yo'q</p>
                </div>

                <div v-else class="space-y-2.5">
                    <div
                        v-for="(result, idx) in leaderboard"
                        :key="result.id"
                        :style="`animation-delay: ${idx * 60}ms`"
                        :class="[
                            'bg-white rounded-2xl border px-5 py-4 flex items-center gap-4 transition shadow-sm lb-row',
                            result.participant_name === playerName && result.score === score && saved
                                ? 'border-indigo-300 ring-2 ring-indigo-200 shadow-indigo-100'
                                : 'border-slate-100 hover:border-slate-200',
                        ]"
                    >
                        <div class="shrink-0 text-2xl w-9 text-center font-bold">
                            {{ idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : `${idx + 1}.` }}
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="font-bold text-slate-800 truncate text-sm">
                                {{ result.participant_name }}
                                <span v-if="result.participant_name === playerName && result.score === score && saved"
                                    class="ml-1.5 text-xs text-indigo-500 font-extrabold">(Siz)</span>
                            </div>
                            <div class="mt-1.5 h-1.5 bg-slate-100 rounded-full overflow-hidden w-full">
                                <div class="h-1.5 rounded-full bg-gradient-to-r from-indigo-500 to-purple-500 transition-all duration-700"
                                    :style="{ width: maxScore ? Math.min(100, Math.round((result.score / leaderboard[0].score) * 100)) + '%' : '0%' }">
                                </div>
                            </div>
                        </div>
                        <div class="shrink-0 text-right">
                            <div class="text-xl font-extrabold text-indigo-600">{{ result.score }}</div>
                            <div class="text-xs text-slate-400">/ {{ maxScore }}</div>
                        </div>
                    </div>
                </div>

                <div class="mt-7 flex gap-3">
                    <button @click="playAgain"
                        class="flex-1 bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-4 rounded-2xl transition shadow-lg shadow-indigo-200/60">
                        Qayta o'ynash
                    </button>
                    <button @click="loadLeaderboard"
                        class="flex-1 bg-white hover:bg-slate-50 text-slate-700 font-semibold py-4 rounded-2xl border-2 border-slate-200 transition">
                        Yangilash ↻
                    </button>
                </div>

                <a :href="`/student?name=${encodeURIComponent(playerName)}`"
                    class="mt-4 block text-center text-xs text-indigo-500 hover:text-indigo-700 font-semibold py-2 hover:underline transition">
                    🎓 Barcha natijalarimni ko'rish →
                </a>
                <p class="text-center text-xs text-slate-400 mt-4">EDUZONA</p>
            </div>
        </div>
    </div>
</template>

<style scoped>
.card-appear { animation: cardPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes cardPop {
    from { opacity: 0; transform: scale(0.92) translateY(10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.lb-row { animation: lbSlideIn 0.4s ease both; }
@keyframes lbSlideIn {
    from { opacity: 0; transform: translateX(-12px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
