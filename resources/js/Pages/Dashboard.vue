<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useGameStore } from '@/stores/game';
import GameTypeIllustration from '@/Components/GameTypeIllustration.vue';
import { useTemplateMeta } from '@/composables/useTemplateMeta';
import axios from 'axios';

const gameStore = useGameStore();
const { tplMeta: getMeta, templateMeta } = useTemplateMeta();
let refreshTimer = null;
let searchTimer = null;
const deletingId = ref(null);
const profileStats = ref(null);
const confirmDeleteId = ref(null);
const retryingId = ref(null);
const search = ref('');
const filterTemplate = ref('');
const filterStatus = ref('');
const filterDifficulty = ref('');

// AI Videos
const recentVideos = ref([]);
const selectedVideo = ref(null);

async function loadRecentVideos() {
    try {
        const { data } = await axios.get('/api/ai-video/history');
        recentVideos.value = (data.data ?? []).slice(0, 6);
    } catch { /* ignore */ }
}

async function openVideo(item) {
    if (!item.status === 'completed') return;
    try {
        const { data } = await axios.get(`/api/ai-video/${item.id}`);
        selectedVideo.value = data;
    } catch { /* ignore */ }
}

const subjectIcons = {
    mathematics: '📐', geometry: '📏', algebra: '🔢', physics: '⚡',
    chemistry: '⚗️', biology: '🧬', history: '📜', geography: '🌍',
    language: '✍️', english: '🇬🇧', informatics: '💻', other: '📚',
};

function currentFilters() {
    return { search: search.value, template: filterTemplate.value, status: filterStatus.value, difficulty: filterDifficulty.value };
}

function stopRefresh() {
    if (refreshTimer) { clearInterval(refreshTimer); refreshTimer = null; }
}

function scheduleRefreshIfNeeded() {
    stopRefresh();
    const hasGenerating = gameStore.games.some(g => g.status === 'generating');
    if (!hasGenerating) return;
    refreshTimer = setInterval(async () => {
        await gameStore.fetchGames(gameStore.gamesMeta?.current_page ?? 1, currentFilters());
        if (!gameStore.games.some(g => g.status === 'generating')) stopRefresh();
    }, 5000);
}

async function applyFilters(page = 1) {
    await gameStore.fetchGames(page, currentFilters());
    scheduleRefreshIfNeeded();
}

// Debounced search watcher
watch(search, () => {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => applyFilters(1), 350);
});

watch([filterTemplate, filterStatus, filterDifficulty], () => applyFilters(1));

onMounted(async () => {
    await gameStore.fetchGames();
    scheduleRefreshIfNeeded();
    axios.get('/api/profile/stats').then(r => { profileStats.value = r.data.data; }).catch(() => {});
    loadRecentVideos();
});

onUnmounted(() => { stopRefresh(); clearTimeout(searchTimer); });


const statusConfig = {
    ready:      { text: 'Tayyor',        class: 'bg-green-100 text-green-700 border-green-200' },
    generating: { text: 'Yaratilmoqda', class: 'bg-amber-100 text-amber-700 border-amber-200' },
    error:      { text: 'Xato',          class: 'bg-red-100 text-red-700 border-red-200' },
};

function getStatus(status) {
    return statusConfig[status] ?? { text: status, class: 'bg-slate-100 text-slate-700 border-slate-200' };
}

async function doDelete(id) {
    deletingId.value = id;
    try {
        await gameStore.deleteGame(id);
    } finally {
        deletingId.value = null;
        confirmDeleteId.value = null;
    }
}

async function doRetry(id) {
    retryingId.value = id;
    try {
        await gameStore.retryGame(id);
        scheduleRefreshIfNeeded();
    } catch { /* ignore */ } finally {
        retryingId.value = null;
    }
}
</script>

<template>
    <Head title="Dashboard" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-xl font-semibold text-slate-800">Mening o'yinlarim</h2>
                        <div v-if="profileStats" class="flex items-center gap-3 mt-1">
                            <span class="text-xs text-slate-400">
                                <span class="font-semibold text-indigo-600">{{ profileStats.readyGames }}</span> tayyor ·
                                <span class="font-semibold text-purple-600">{{ profileStats.totalSessions }}</span> sessiya ·
                                <span class="font-semibold text-amber-600">{{ profileStats.totalStudents }}</span> o'quvchi
                            </span>
                        </div>
                    </div>
                    <Link href="/games/create"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Yangi o'yin
                    </Link>
                </div>
                <!-- Search & Filter bar -->
                <div class="flex flex-col sm:flex-row gap-2">
                    <!-- Search input -->
                    <div class="relative flex-1">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                        </svg>
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Mavzu bo'yicha qidirish..."
                            class="w-full pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-white transition"
                        />
                        <button v-if="search" @click="search = ''"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                    <!-- Template filter -->
                    <select v-model="filterTemplate"
                        class="sm:w-48 py-2 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-white text-slate-600 transition">
                        <option value="">Barcha turlar</option>
                        <option v-for="(meta, code) in templateMeta" :key="code" :value="code">
                            {{ meta.icon }} {{ meta.label }}
                        </option>
                    </select>
                    <!-- Status filter -->
                    <select v-model="filterStatus"
                        class="sm:w-36 py-2 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-white text-slate-600 transition">
                        <option value="">Barcha holat</option>
                        <option value="ready">Tayyor</option>
                        <option value="generating">Yaratilmoqda</option>
                        <option value="error">Xato</option>
                    </select>
                    <!-- Difficulty filter -->
                    <select v-model="filterDifficulty"
                        class="sm:w-36 py-2 px-3 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-400 bg-white text-slate-600 transition">
                        <option value="">Barcha daraja</option>
                        <option value="easy">🟢 Oson</option>
                        <option value="medium">🟡 O'rtacha</option>
                        <option value="hard">🔴 Qiyin</option>
                    </select>
                    <!-- Clear filters -->
                    <button v-if="search || filterTemplate || filterStatus || filterDifficulty"
                        @click="search = ''; filterTemplate = ''; filterStatus = ''; filterDifficulty = ''"
                        class="px-3 py-2 text-sm text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition">
                        Tozalash
                    </button>
                </div>
            </div>
        </template>

        <!-- ══ AI VIDEO BO'LIMI ══ -->
        <div v-if="recentVideos.length" class="mb-8">
            <div class="flex items-center justify-between mb-3">
                <div class="flex items-center gap-2">
                    <span class="text-lg">🎬</span>
                    <h3 class="font-bold text-slate-800 text-base">AI Video Darslar</h3>
                    <span class="text-xs bg-violet-100 text-violet-600 font-bold px-2 py-0.5 rounded-full">
                        {{ recentVideos.length }}
                    </span>
                </div>
                <Link href="/ai-video/history"
                    class="text-xs text-indigo-600 hover:text-indigo-800 font-medium transition">
                    Barchasini ko'rish →
                </Link>
            </div>

            <div class="grid grid-cols-2 sm:grid-cols-3 xl:grid-cols-6 gap-3">
                <!-- Yangi video kartasi -->
                <Link href="/ai-video"
                    class="flex flex-col items-center justify-center gap-2 bg-gradient-to-br from-violet-50 to-indigo-50 border-2 border-dashed border-indigo-200 hover:border-indigo-400 rounded-2xl p-4 text-center transition group min-h-[120px]">
                    <div class="w-10 h-10 bg-indigo-100 group-hover:bg-indigo-200 rounded-xl flex items-center justify-center text-xl transition">➕</div>
                    <span class="text-xs font-semibold text-indigo-600">Yangi video</span>
                </Link>

                <!-- Video kartalar -->
                <div
                    v-for="video in recentVideos" :key="video.id"
                    @click="openVideo(video)"
                    class="relative bg-white border border-slate-200 hover:border-indigo-300 hover:shadow-md rounded-2xl overflow-hidden cursor-pointer transition group min-h-[120px] flex flex-col">

                    <!-- Rang bandi top -->
                    <div :class="[
                        'h-2 w-full',
                        video.video_url ? 'bg-gradient-to-r from-violet-500 to-indigo-500'
                        : video.status === 'generating' ? 'bg-gradient-to-r from-amber-400 to-orange-400'
                        : 'bg-gradient-to-r from-slate-200 to-slate-300'
                    ]"></div>

                    <div class="p-3 flex-1 flex flex-col gap-1.5">
                        <!-- Subject icon + video badge -->
                        <div class="flex items-center justify-between">
                            <span class="text-xl">{{ subjectIcons[video.subject] ?? '📚' }}</span>
                            <span v-if="video.video_url"
                                class="text-[10px] font-bold bg-green-100 text-green-700 px-1.5 py-0.5 rounded-full">
                                ▶ Video
                            </span>
                            <span v-else-if="video.status === 'generating'"
                                class="text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full animate-pulse">
                                ⏳
                            </span>
                            <span v-else
                                class="text-[10px] font-bold bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full">
                                📄 Yechim
                            </span>
                        </div>

                        <!-- Mavzu -->
                        <p class="text-xs font-semibold text-slate-800 line-clamp-2 leading-snug flex-1">
                            {{ video.topic }}
                        </p>

                        <!-- Fan + vaqt -->
                        <div class="flex items-center justify-between">
                            <span class="text-[10px] text-slate-400">{{ video.subject_label }}</span>
                        </div>
                    </div>

                    <!-- Hover overlay -->
                    <div class="absolute inset-0 bg-indigo-600/0 group-hover:bg-indigo-600/5 transition rounded-2xl flex items-center justify-center">
                        <span class="opacity-0 group-hover:opacity-100 text-xs font-bold text-indigo-700 bg-white rounded-lg px-2 py-1 shadow-sm transition">
                            {{ video.video_url ? '▶ Ko\'rish' : '📖 Yechim' }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agar video yo'q bo'lsa — qisqa banner -->
        <div v-else class="mb-8 bg-gradient-to-r from-violet-50 to-indigo-50 border border-indigo-100 rounded-2xl px-5 py-4 flex items-center gap-4">
            <div class="text-3xl shrink-0">🎬</div>
            <div class="flex-1 min-w-0">
                <p class="font-bold text-slate-800 text-sm">AI Video Darslar</p>
                <p class="text-xs text-slate-500 mt-0.5">Masalani kiriting — AI yechib video dars tayyorlaydi</p>
            </div>
            <Link href="/ai-video"
                class="shrink-0 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-xl transition">
                Yaratish
            </Link>
        </div>

        <!-- ══ O'YINLAR ══ -->

        <!-- Loading -->
        <div v-if="gameStore.loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-pulse">
                <div class="h-28 bg-slate-100"></div>
                <div class="p-4 space-y-2">
                    <div class="h-4 bg-slate-100 rounded w-3/4"></div>
                    <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="gameStore.games.length === 0" class="flex flex-col items-center justify-center py-24">
            <div class="w-24 h-24 bg-indigo-100 rounded-3xl flex items-center justify-center text-5xl mb-6 shadow-inner">
                {{ search || filterTemplate || filterStatus || filterDifficulty ? '🔍' : '🎮' }}
            </div>
            <h3 class="text-xl font-semibold text-slate-700 mb-2">
                {{ search || filterTemplate || filterStatus || filterDifficulty ? 'Hech narsa topilmadi' : "Hali o'yinlar yo'q" }}
            </h3>
            <p class="text-slate-400 mb-8">
                {{ search || filterTemplate || filterStatus ? 'Qidiruv yoki filterni o\'zgartiring' : 'AI yordamida birinchi o\'yiningizni yarating!' }}
            </p>
            <button v-if="search || filterTemplate || filterStatus || filterDifficulty"
                @click="search = ''; filterTemplate = ''; filterStatus = ''; filterDifficulty = ''"
                class="bg-slate-100 hover:bg-slate-200 text-slate-700 px-6 py-2.5 rounded-2xl font-medium transition mb-4">
                Filterni tozalash
            </button>
            <Link v-else href="/games/create"
                class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-2xl font-medium transition shadow-md shadow-indigo-200">
                ✨ O'yin yaratish
            </Link>
        </div>

        <!-- Games grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-5">
            <div
                v-for="(game, idx) in gameStore.games"
                :key="game.id"
                :style="`animation-delay: ${idx * 50}ms`"
                class="relative group card-appear"
            >
                <Link
                    :href="`/games/${game.id}`"
                    class="block bg-white rounded-2xl border border-slate-200 overflow-hidden hover:border-transparent hover:shadow-xl hover:shadow-indigo-100/60 transition-all duration-200"
                >
                    <!-- Card header with gradient -->
                    <div :class="['h-28 bg-gradient-to-br flex items-center justify-center relative overflow-hidden p-3', getMeta(game.template?.code).color]">
                        <!-- Decorative circles -->
                        <div class="absolute -top-4 -right-4 w-20 h-20 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-6 -left-6 w-24 h-24 bg-white/10 rounded-full"></div>
                        <!-- Illustration -->
                        <GameTypeIllustration :code="game.template?.code" class="w-full h-full relative z-10" />
                        <!-- Status badge -->
                        <div class="absolute top-3 right-3 flex items-center gap-1.5">
                            <span v-if="game.is_public"
                                class="text-[10px] px-2 py-0.5 rounded-full font-bold bg-green-500/90 text-white border border-green-400/50 backdrop-blur-sm">
                                🌐 Ommaviy
                            </span>
                            <span :class="['text-xs px-2.5 py-1 rounded-full font-medium border', getStatus(game.status).class]">
                                {{ getStatus(game.status).text }}
                            </span>
                        </div>
                    </div>

                    <!-- Card body -->
                    <div class="p-4">
                        <h3 class="font-semibold text-slate-900 truncate text-base mb-1 group-hover:text-indigo-700 transition">
                            {{ game.topic }}
                        </h3>
                        <p class="text-sm text-slate-500 mb-3">{{ getMeta(game.template?.code).label }}</p>
                        <div class="flex items-center gap-3 text-xs text-slate-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ game.students_count }} ta
                            </span>
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                                </svg>
                                {{ game.language?.toUpperCase() }}
                            </span>
                            <span v-if="game.difficulty"
                                :class="['text-[10px] font-bold px-1.5 py-0.5 rounded-full',
                                    game.difficulty === 'easy' ? 'bg-green-100 text-green-700' :
                                    game.difficulty === 'hard' ? 'bg-red-100 text-red-700' :
                                    'bg-amber-100 text-amber-700']">
                                {{ game.difficulty === 'easy' ? '🟢' : game.difficulty === 'hard' ? '🔴' : '🟡' }}
                            </span>
                            <span v-if="game.status === 'ready'" class="ml-auto text-indigo-500 font-medium group-hover:underline">
                                O'ynash →
                            </span>
                            <span v-else-if="game.status === 'generating'" class="ml-auto text-amber-500">
                                ⏳ Kuting...
                            </span>
                            <span v-else-if="game.status === 'error'"
                                class="ml-auto text-red-400 text-xs cursor-help"
                                :title="game.error_message ?? 'AI yaratishda xato yuz berdi'">
                                ⚠️ Xato
                            </span>
                        </div>
                    </div>
                </Link>

                <!-- Delete button overlay -->
                <button
                    v-if="confirmDeleteId !== game.id"
                    @click.prevent="confirmDeleteId = game.id"
                    class="absolute top-2 left-2 z-10 w-7 h-7 flex items-center justify-center rounded-lg bg-black/30 hover:bg-red-500 text-white opacity-0 group-hover:opacity-100 transition-all"
                    title="O'chirish"
                >
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                </button>

                <!-- Retry button for error games -->
                <button
                    v-if="game.status === 'error' && confirmDeleteId !== game.id"
                    @click.prevent="doRetry(game.id)"
                    :disabled="retryingId === game.id"
                    class="absolute bottom-3 right-3 z-10 flex items-center gap-1 text-xs bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white px-2.5 py-1.5 rounded-lg transition font-medium"
                    title="Qayta urinish"
                >
                    <svg v-if="retryingId === game.id" class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <svg v-else class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                    </svg>
                    {{ retryingId === game.id ? '...' : 'Qayta urinish' }}
                </button>

                <!-- Inline confirm overlay -->
                <div v-if="confirmDeleteId === game.id"
                    class="absolute inset-0 z-20 bg-white/95 rounded-2xl border-2 border-red-200 flex flex-col items-center justify-center gap-3 p-4">
                    <p class="text-sm font-semibold text-slate-800 text-center">O'chirilsinmi?</p>
                    <p class="text-xs text-slate-500 text-center truncate w-full">{{ game.topic }}</p>
                    <p v-if="game.error_message" class="text-[10px] text-red-400 text-center w-full line-clamp-2 px-1">
                        {{ game.error_message }}
                    </p>
                    <div class="flex gap-2 w-full">
                        <button @click="confirmDeleteId = null"
                            class="flex-1 py-2 rounded-xl border border-slate-200 text-slate-600 text-xs font-semibold hover:bg-slate-50 transition">
                            Yo'q
                        </button>
                        <button @click="doDelete(game.id)" :disabled="deletingId === game.id"
                            class="flex-1 py-2 rounded-xl bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white text-xs font-semibold transition">
                            {{ deletingId === game.id ? '...' : 'Ha, o\'chir' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <div v-if="gameStore.gamesMeta && gameStore.gamesMeta.last_page > 1"
            class="flex items-center justify-between mt-8 px-1">
            <p class="text-sm text-slate-400">
                <span v-if="search || filterTemplate || filterStatus">
                    Topildi: <span class="font-medium text-slate-600">{{ gameStore.gamesMeta.total }}</span> ta
                </span>
                <span v-else>
                    Jami <span class="font-medium text-slate-600">{{ gameStore.gamesMeta.total }}</span> ta o'yin
                </span>
            </p>
            <div class="flex items-center gap-1">
                <button
                    @click="applyFilters(gameStore.gamesMeta.current_page - 1)"
                    :disabled="gameStore.gamesMeta.current_page <= 1"
                    class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:border-indigo-300 hover:text-indigo-600 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </button>

                <template v-for="page in gameStore.gamesMeta.last_page" :key="page">
                    <button
                        v-if="page === 1 || page === gameStore.gamesMeta.last_page ||
                              Math.abs(page - gameStore.gamesMeta.current_page) <= 1"
                        @click="applyFilters(page)"
                        :class="[
                            'w-9 h-9 flex items-center justify-center rounded-xl text-sm font-medium transition',
                            page === gameStore.gamesMeta.current_page
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600'
                        ]"
                    >{{ page }}</button>
                    <span
                        v-else-if="page === 2 && gameStore.gamesMeta.current_page > 3 ||
                                   page === gameStore.gamesMeta.last_page - 1 && gameStore.gamesMeta.current_page < gameStore.gamesMeta.last_page - 2"
                        class="w-9 h-9 flex items-center justify-center text-slate-300 text-sm"
                    >…</span>
                </template>

                <button
                    @click="applyFilters(gameStore.gamesMeta.current_page + 1)"
                    :disabled="gameStore.gamesMeta.current_page >= gameStore.gamesMeta.last_page"
                    class="w-9 h-9 flex items-center justify-center rounded-xl border border-slate-200 text-slate-500 hover:border-indigo-300 hover:text-indigo-600 disabled:opacity-30 disabled:cursor-not-allowed transition"
                >
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </button>
            </div>
        </div>
    </AuthenticatedLayout>

    <!-- ══ VIDEO MODAL ══ -->
    <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="selectedVideo"
            class="fixed inset-0 z-50 bg-black/60 backdrop-blur-sm flex items-end sm:items-center justify-center p-4"
            @click.self="selectedVideo = null">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto">

                <!-- Modal header -->
                <div class="sticky top-0 bg-white flex items-center justify-between px-5 py-4 border-b border-slate-100 z-10">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">{{ subjectIcons[selectedVideo.subject] ?? '📚' }}</span>
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm leading-tight">{{ selectedVideo.topic }}</h3>
                            <p class="text-xs text-slate-400">{{ selectedVideo.subject_label }}</p>
                        </div>
                    </div>
                    <button @click="selectedVideo = null"
                        class="w-8 h-8 flex items-center justify-center rounded-lg hover:bg-slate-100 transition text-slate-400 text-lg">
                        ✕
                    </button>
                </div>

                <div class="p-5 space-y-4">
                    <!-- Video player -->
                    <div v-if="selectedVideo.video_url" class="bg-black rounded-xl overflow-hidden aspect-video">
                        <video :src="selectedVideo.video_url" controls autoplay class="w-full h-full"></video>
                    </div>

                    <!-- Masala matni -->
                    <div class="bg-slate-50 rounded-xl p-4">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Masala</div>
                        <p class="text-sm text-slate-700 leading-relaxed">{{ selectedVideo.problem_text }}</p>
                    </div>

                    <!-- Yechim qadamlari -->
                    <div v-if="selectedVideo.solution_json?.steps?.length" class="space-y-3">
                        <div class="text-xs font-bold text-slate-400 uppercase tracking-wider">Qadam-baqadam yechim</div>
                        <div v-for="step in selectedVideo.solution_json.steps" :key="step.step" class="flex gap-3">
                            <div class="w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                                {{ step.step }}
                            </div>
                            <div class="flex-1">
                                <p class="text-sm font-semibold text-slate-800">{{ step.title }}</p>
                                <p class="text-xs text-slate-600 mt-0.5 leading-relaxed">{{ step.explanation }}</p>
                                <div v-if="step.formula"
                                    class="mt-1.5 bg-slate-800 text-green-400 text-xs font-mono px-3 py-2 rounded-lg">
                                    {{ step.formula }}
                                </div>
                            </div>
                        </div>
                        <div v-if="selectedVideo.solution_json.final_answer"
                            class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm font-bold text-green-800">
                            ✅ {{ selectedVideo.solution_json.final_answer }}
                        </div>
                    </div>

                    <!-- Havolalar -->
                    <div class="flex gap-2 pt-1">
                        <a v-if="selectedVideo.video_url" :href="selectedVideo.video_url" download target="_blank"
                            class="flex-1 py-2.5 text-xs font-bold text-center border border-slate-200 text-slate-600 hover:bg-slate-50 rounded-xl transition">
                            ⬇ Videoni yuklab olish
                        </a>
                        <Link href="/ai-video"
                            class="flex-1 py-2.5 text-xs font-bold text-center bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl transition">
                            + Yangi masala
                        </Link>
                    </div>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.card-appear { animation: cardSlideIn 0.4s ease both; }
@keyframes cardSlideIn {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
