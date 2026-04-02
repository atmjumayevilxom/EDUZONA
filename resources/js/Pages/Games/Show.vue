<script setup>
import { onMounted, onUnmounted, ref, watch } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useGameStore } from '@/stores/game';
import { router } from '@inertiajs/vue3';
import { useTemplateMeta } from '@/composables/useTemplateMeta';
import QRCode from 'qrcode';

const deleting = ref(false);
const confirmDelete = ref(false);
const retrying = ref(false);
const classrooms = ref([]);
const selectedClassroomId = ref(null);
const presenterMode = ref(false);
const showPreview = ref(false);

const SHOW_GEN_STEPS = [
    { icon: '🔍', text: "Mavzu tahlil qilinmoqda..." },
    { icon: '🧠', text: "AI savollar yaratmoqda..." },
    { icon: '✏️',  text: "Javoblar tekshirilmoqda..." },
    { icon: '🎨', text: "O'yin formatlanmoqda..." },
    { icon: '✅', text: "Deyarli tayyor!" },
];
const showGenStep = ref(0);
let genStepTimer = null;

watch(() => gameStore.currentGame?.status, (status) => {
    if (status === 'generating') {
        showGenStep.value = 0;
        clearInterval(genStepTimer);
        genStepTimer = setInterval(() => {
            showGenStep.value = (showGenStep.value + 1) % SHOW_GEN_STEPS.length;
        }, 2800);
    } else {
        clearInterval(genStepTimer);
        genStepTimer = null;
    }
}, { immediate: true });

const props = defineProps({ gameId: Number });
const gameStore  = useGameStore();
const { tplMeta: getMeta } = useTemplateMeta();
const session     = ref(null);
const creating    = ref(false);
const copied      = ref(false);
const copiedUrl   = ref(false);
const copying     = ref(false);
const copiedGame  = ref(false);
const togglingPublic = ref(false);
const ending      = ref(false);
const qrDataUrl   = ref('');
const liveResults = ref([]);
let pollingTimer  = null;
let echoChannel   = null;

function stopPolling() {
    if (pollingTimer && pollingTimer !== true) { clearInterval(pollingTimer); }
    pollingTimer = null;
}

function stopEcho(code) {
    if (echoChannel && window.Echo) {
        window.Echo.leaveChannel(`session.${code}`);
        echoChannel = null;
    }
}

function startPollingIfGenerating() {
    if (gameStore.currentGame?.status !== 'generating') return;
    // First check after 2s, then every 1.5s
    setTimeout(async () => {
        if (!pollingTimer) return;
        try {
            const res = await axios.get(`/api/games/${props.gameId}`);
            const game = res.data.data;
            if (game.status !== 'generating') { gameStore.currentGame = game; stopPolling(); return; }
        } catch { /* ignore */ }
        pollingTimer = setInterval(async () => {
            try {
                const res = await axios.get(`/api/games/${props.gameId}`);
                const game = res.data.data;
                if (game.status !== 'generating') { gameStore.currentGame = game; stopPolling(); }
            } catch { /* keep polling */ }
        }, 1500);
    }, 2000);
    pollingTimer = true; // sentinel
}

async function fetchLiveResults() {
    if (!session.value?.session_code) return;
    try {
        const res = await axios.get(`/api/sessions/${session.value.session_code}/results`);
        liveResults.value = res.data.data?.results ?? [];
    } catch { /* ignore */ }
}

function startLiveEcho(code) {
    if (!window.Echo) {
        // Fallback to polling if Echo not available
        fetchLiveResults();
        const t = setInterval(async () => {
            if (session.value?.status === 'ended') { clearInterval(t); return; }
            await fetchLiveResults();
        }, 4000);
        return;
    }
    fetchLiveResults();
    echoChannel = window.Echo.channel(`session.${code}`)
        .listen('.result.submitted', ({ result }) => {
            const exists = liveResults.value.some(r => r.id === result.id);
            if (!exists) {
                liveResults.value = [...liveResults.value, result].sort((a, b) => b.score - a.score);
            }
        })
        .listen('.session.ended', () => {
            session.value = { ...session.value, status: 'ended' };
            fetchLiveResults();
            stopEcho(code);
        });
}

function onKeydown(e) {
    if (e.key === 'Escape') { presenterMode.value = false; showPreview.value = false; }
}

onMounted(async () => {
    await gameStore.fetchGame(props.gameId);
    startPollingIfGenerating();
    axios.get('/api/classrooms').then(r => { classrooms.value = r.data.data ?? []; }).catch(() => {});
    window.addEventListener('keydown', onKeydown);
});
onUnmounted(() => {
    stopPolling();
    clearInterval(genStepTimer);
    if (session.value?.session_code) stopEcho(session.value.session_code);
    window.removeEventListener('keydown', onKeydown);
});

async function startSession() {
    creating.value = true;
    try {
        session.value = await gameStore.createSession(props.gameId, selectedClassroomId.value ?? null);
        startLiveEcho(session.value.session_code);
        const url = `${window.location.origin}/session/${session.value.session_code}`;
        qrDataUrl.value = await QRCode.toDataURL(url, { width: 200, margin: 1, color: { dark: '#312e81', light: '#fff' } });
    } finally {
        creating.value = false;
    }
}

async function endSession() {
    if (!session.value?.session_code) return;
    ending.value = true;
    try {
        await axios.patch(`/api/sessions/${session.value.session_code}/end`);
        session.value = { ...session.value, status: 'ended' };
        stopEcho(session.value.session_code);
        await fetchLiveResults();
    } catch { /* ignore */ } finally {
        ending.value = false;
    }
}

async function copyCode() {
    if (session.value) {
        await navigator.clipboard.writeText(session.value.session_code);
        copied.value = true;
        setTimeout(() => { copied.value = false; }, 2000);
    }
}

async function copySessionUrl() {
    if (session.value) {
        const url = `${window.location.origin}/session/${session.value.session_code}`;
        await navigator.clipboard.writeText(url);
        copiedUrl.value = true;
        setTimeout(() => { copiedUrl.value = false; }, 2000);
    }
}

async function copyGame() {
    copying.value = true;
    try {
        const res = await gameStore.copyGame(props.gameId);
        copiedGame.value = true;
        setTimeout(() => { router.visit(`/games/${res.id}`); }, 800);
    } catch {
        copying.value = false;
    }
}

async function togglePublic() {
    togglingPublic.value = true;
    try {
        const res = await axios.patch(`/api/games/${props.gameId}/toggle-public`);
        gameStore.currentGame = { ...gameStore.currentGame, is_public: res.data.is_public };
    } catch { /* ignore */ } finally {
        togglingPublic.value = false;
    }
}

async function retryGame() {
    retrying.value = true;
    try {
        await gameStore.retryGame(props.gameId);
        startPollingIfGenerating();
    } catch { /* ignore */ } finally {
        retrying.value = false;
    }
}

async function doDelete() {
    deleting.value = true;
    try {
        await gameStore.deleteGame(props.gameId);
        router.visit('/dashboard');
    } catch {
        deleting.value = false;
        confirmDelete.value = false;
    }
}

</script>

<template>
    <Head title="O'yin" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-2 text-sm">
                <Link href="/dashboard" class="text-slate-400 hover:text-slate-600 transition">← Orqaga</Link>
                <span class="text-slate-200">/</span>
                <span class="text-slate-700 font-medium truncate max-w-xs">{{ gameStore.currentGame?.topic ?? 'O\'yin' }}</span>
            </div>
        </template>

        <div v-if="gameStore.loading" class="flex items-center justify-center py-24">
            <div class="text-center">
                <div class="w-12 h-12 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-slate-400 text-sm">Yuklanmoqda...</p>
            </div>
        </div>

        <!-- Xato holati -->
        <div v-else-if="gameStore.error" class="max-w-md mx-auto mt-16 text-center">
            <div class="bg-white rounded-2xl border border-red-100 shadow-sm p-10">
                <div class="text-5xl mb-4">😔</div>
                <h2 class="text-lg font-bold text-slate-800 mb-2">{{ gameStore.error }}</h2>
                <Link href="/dashboard"
                    class="inline-flex items-center gap-2 mt-4 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-medium text-sm hover:bg-indigo-700 transition">
                    ← Dashboardga qaytish
                </Link>
            </div>
        </div>
        <!-- AI Generating overlay -->
        <div v-else-if="gameStore.currentGame?.status === 'generating'"
            class="max-w-2xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="bg-gradient-to-br from-indigo-500 to-purple-600 px-8 py-10 text-center">
                    <div class="text-6xl mb-3 animate-bounce">🤖</div>
                    <h2 class="text-white text-xl font-black mb-1">AI O'yin Yaratmoqda</h2>
                    <p class="text-indigo-200 text-sm font-medium">{{ gameStore.currentGame.topic }}</p>
                </div>
                <div class="p-6 space-y-3">
                    <div v-for="(step, i) in SHOW_GEN_STEPS" :key="i"
                        :class="[
                            'flex items-center gap-4 px-5 py-3.5 rounded-2xl transition-all duration-500',
                            i === showGenStep
                                ? 'bg-indigo-50 border-2 border-indigo-300 shadow-sm'
                                : i < showGenStep
                                    ? 'bg-green-50 border border-green-200 opacity-70'
                                    : 'bg-slate-50 border border-slate-100 opacity-40',
                        ]">
                        <span class="text-2xl shrink-0">{{ i < showGenStep ? '✅' : step.icon }}</span>
                        <span :class="['font-semibold text-sm flex-1', i === showGenStep ? 'text-indigo-700' : i < showGenStep ? 'text-green-700' : 'text-slate-400']">
                            {{ step.text }}
                        </span>
                        <span v-if="i === showGenStep" class="flex gap-1 shrink-0">
                            <span v-for="d in 3" :key="d" class="w-1.5 h-1.5 rounded-full bg-indigo-400 animate-bounce"
                                :style="`animation-delay: ${(d-1) * 150}ms`"></span>
                        </span>
                    </div>
                </div>
                <p class="text-center text-slate-400 text-xs pb-5">Bu 20-40 soniya davom etishi mumkin...</p>
            </div>
        </div>

        <div v-else-if="gameStore.currentGame && gameStore.currentGame.status !== 'generating'" class="max-w-2xl mx-auto space-y-5">
            <!-- Hero card -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden card-appear" style="animation-delay: 0ms">
                <!-- Gradient header -->
                <div :class="['h-40 bg-gradient-to-br flex items-center justify-center relative overflow-hidden', getMeta(gameStore.currentGame.template?.code).color]">
                    <div class="absolute -top-8 -right-8 w-32 h-32 bg-white/10 rounded-full"></div>
                    <div class="absolute -bottom-10 -left-10 w-36 h-36 bg-white/10 rounded-full"></div>
                    <span class="text-7xl drop-shadow-lg relative z-10">{{ getMeta(gameStore.currentGame.template?.code).icon }}</span>
                    <!-- Status -->
                    <div class="absolute top-4 right-4">
                        <span :class="[
                            'text-sm px-3 py-1.5 rounded-full font-medium border backdrop-blur-sm',
                            gameStore.currentGame.status === 'ready'      ? 'bg-green-100/90 text-green-800 border-green-200'  :
                            gameStore.currentGame.status === 'error'      ? 'bg-red-100/90 text-red-800 border-red-200'        :
                            'bg-amber-100/90 text-amber-800 border-amber-200'
                        ]">
                            {{ gameStore.currentGame.status === 'ready'      ? '✅ Tayyor'         :
                               gameStore.currentGame.status === 'error'      ? '❌ Xato'           : '⏳ Yaratilmoqda' }}
                        </span>
                    </div>
                </div>

                <!-- Info body -->
                <div class="p-6">
                    <h2 class="text-2xl font-bold text-slate-900 mb-1">{{ gameStore.currentGame.topic }}</h2>
                    <p class="text-slate-500 text-sm mb-5">{{ gameStore.currentGame.template?.name }}</p>

                    <div class="grid grid-cols-3 gap-3">
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">{{ gameStore.currentGame.students_count }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Savol / So'z</div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-2xl font-bold text-indigo-600">{{ gameStore.currentGame.language?.toUpperCase() }}</div>
                            <div class="text-xs text-slate-500 mt-0.5">Til</div>
                        </div>
                        <div class="bg-slate-50 rounded-xl p-4 text-center">
                            <div class="text-xl font-bold">
                                {{ gameStore.currentGame.difficulty === 'easy' ? '🟢' : gameStore.currentGame.difficulty === 'hard' ? '🔴' : '🟡' }}
                            </div>
                            <div class="text-xs text-slate-500 mt-0.5">
                                {{ gameStore.currentGame.difficulty === 'easy' ? 'Oson' : gameStore.currentGame.difficulty === 'hard' ? 'Qiyin' : "O'rtacha" }}
                            </div>
                        </div>
                    </div>

                    <div v-if="gameStore.currentGame.generated_json?.title" class="mt-4 bg-indigo-50 border border-indigo-100 rounded-xl px-4 py-3 flex items-center justify-between gap-3">
                        <span class="text-sm text-indigo-800">📌 <strong>{{ gameStore.currentGame.generated_json.title }}</strong></span>
                        <button v-if="gameStore.currentGame.generated_json?.questions?.length || gameStore.currentGame.generated_json?.items?.length || gameStore.currentGame.generated_json?.pairs?.length"
                            @click="showPreview = true"
                            class="shrink-0 text-xs bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-3 py-1.5 rounded-lg transition">
                            Ko'rish
                        </button>
                    </div>
                </div>
            </div>

            <!-- Action buttons -->
            <div v-if="gameStore.currentGame.status === 'ready'" class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                <Link
                    :href="`/games/${gameStore.currentGame.id}/play`"
                    class="flex items-center justify-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-4 rounded-2xl transition shadow-md shadow-indigo-200"
                >
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                    O'yinni boshlash
                </Link>
                <a
                    :href="`/games/${gameStore.currentGame.id}/play?mode=focus`"
                    class="flex items-center justify-center gap-2 bg-slate-700 hover:bg-slate-800 text-white font-semibold py-4 rounded-2xl transition"
                    title="Proyektor uchun to'liq ekran rejimi"
                >
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Proyektor
                </a>
                <!-- Sessiya yaratish (sinf tanlash bilan) -->
                <div v-if="!session" class="col-span-2 sm:col-span-1 flex flex-col gap-1.5">
                    <!-- Sinf tanlash (sinflar mavjud bo'lsa) -->
                    <select
                        v-if="classrooms.length > 0"
                        v-model="selectedClassroomId"
                        class="w-full text-xs border border-slate-200 rounded-xl px-3 py-2 text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"
                    >
                        <option :value="null">— Sinfni tanlang (ixtiyoriy) —</option>
                        <option v-for="cls in classrooms" :key="cls.id" :value="cls.id">
                            {{ cls.name }}{{ cls.subject ? ` · ${cls.subject}` : '' }}
                        </option>
                    </select>
                    <button
                        @click="startSession"
                        :disabled="creating"
                        class="flex items-center justify-center gap-2 bg-white hover:bg-slate-50 disabled:bg-slate-50 text-slate-700 font-semibold py-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 transition"
                    >
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                        </svg>
                        {{ creating ? 'Yaratilmoqda...' : 'Sessiya yaratish' }}
                    </button>
                </div>

                <!-- Stats button -->
                <Link :href="`/games/${gameStore.currentGame.id}/stats`"
                    class="flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-semibold py-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 transition">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    Statistika
                </Link>

                <!-- Copy button -->
                <button
                    @click="copyGame"
                    :disabled="copying"
                    class="flex items-center justify-center gap-2 bg-white hover:bg-slate-50 disabled:opacity-60 text-slate-700 font-semibold py-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 transition"
                >
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    {{ copiedGame ? '✓ Nusxalandi!' : copying ? 'Nusxalanmoqda...' : 'Nusxalash' }}
                </button>

                <!-- Print button -->
                <Link :href="`/games/${gameStore.currentGame.id}/print`"
                    class="flex items-center justify-center gap-2 bg-white hover:bg-slate-50 text-slate-700 font-semibold py-4 rounded-2xl border-2 border-slate-200 hover:border-slate-300 transition">
                    <svg class="w-5 h-5 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Chop etish
                </Link>

                <!-- Toggle public -->
                <button @click="togglePublic" :disabled="togglingPublic"
                    :class="[
                        'flex items-center justify-center gap-2 font-semibold py-4 rounded-2xl border-2 transition disabled:opacity-60',
                        gameStore.currentGame.is_public
                            ? 'bg-green-50 border-green-300 text-green-700 hover:bg-green-100'
                            : 'bg-white hover:bg-slate-50 text-slate-700 border-slate-200 hover:border-slate-300'
                    ]">
                    <span class="text-lg">{{ gameStore.currentGame.is_public ? '🌐' : '🔒' }}</span>
                    {{ gameStore.currentGame.is_public ? 'Ommaviy' : 'Yopiq' }}
                </button>
            </div>

            <!-- Error state -->
            <div v-if="gameStore.currentGame.status === 'error'" class="bg-red-50 border border-red-200 rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <span class="text-3xl">⚠️</span>
                    <div>
                        <div class="font-semibold text-red-800">O'yin yaratishda xato yuz berdi</div>
                        <div class="text-sm text-red-600 mt-0.5">AI javob bermadi yoki schema xato qaytdi.</div>
                    </div>
                </div>
                <div class="flex gap-3 flex-wrap">
                    <button
                        @click="retryGame"
                        :disabled="retrying"
                        class="inline-flex items-center gap-2 text-sm bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white px-5 py-2.5 rounded-xl transition font-medium"
                    >
                        <svg v-if="retrying" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        {{ retrying ? 'Qayta urinilmoqda...' : 'Qayta urinish' }}
                    </button>
                    <Link href="/games/create" class="inline-flex items-center gap-2 text-sm bg-white hover:bg-slate-50 text-slate-700 border border-slate-200 px-5 py-2.5 rounded-xl transition font-medium">
                        + Yangi o'yin yaratish
                    </Link>
                </div>
            </div>

            <!-- Delete button -->
            <div class="flex justify-end">
                <button @click="confirmDelete = true"
                    class="flex items-center gap-1.5 text-sm text-red-400 hover:text-red-600 hover:bg-red-50 px-3 py-1.5 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    O'chirish
                </button>
            </div>

            <!-- Delete confirm modal -->
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="confirmDelete" class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 backdrop-blur-sm" @click.self="confirmDelete = false">
                    <div class="bg-white rounded-2xl shadow-2xl p-6 max-w-sm w-full mx-4">
                        <div class="text-center mb-5">
                            <div class="text-4xl mb-3">🗑️</div>
                            <h3 class="font-bold text-slate-900 text-lg">O'yinni o'chirishni tasdiqlang</h3>
                            <p class="text-sm text-slate-500 mt-1">
                                <strong>{{ gameStore.currentGame?.topic }}</strong> o'yini va uning barcha natijalari butunlay o'chiriladi.
                            </p>
                        </div>
                        <div class="flex gap-3">
                            <button @click="confirmDelete = false"
                                class="flex-1 py-2.5 rounded-xl border-2 border-slate-200 text-slate-700 font-semibold hover:bg-slate-50 transition text-sm">
                                Bekor qilish
                            </button>
                            <button @click="doDelete" :disabled="deleting"
                                class="flex-1 py-2.5 rounded-xl bg-red-600 hover:bg-red-700 disabled:opacity-60 text-white font-semibold transition text-sm">
                                {{ deleting ? 'O\'chirilmoqda...' : 'Ha, o\'chirish' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Transition>

            <!-- Session card -->
            <Transition enter-active-class="transition-all duration-400" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100">
                <div v-if="session" :class="['border-2 rounded-2xl p-6 space-y-4', session.status === 'ended' ? 'bg-slate-50 border-slate-200' : 'bg-gradient-to-br from-green-50 to-emerald-50 border-green-200']">

                    <!-- Header -->
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="text-2xl">{{ session.status === 'ended' ? '🏁' : '🔗' }}</span>
                            <div>
                                <h3 :class="['font-bold text-sm', session.status === 'ended' ? 'text-slate-600' : 'text-green-800']">
                                    {{ session.status === 'ended' ? 'Sessiya tugadi' : 'Sessiya faol' }}
                                </h3>
                                <p class="text-xs text-slate-400">{{ liveResults.length }} ta ishtirokchi</p>
                            </div>
                        </div>
                        <!-- Presenter + End buttons -->
                        <div class="flex items-center gap-2">
                            <button
                                @click="presenterMode = true"
                                class="text-xs bg-indigo-100 hover:bg-indigo-200 text-indigo-700 font-semibold px-3 py-1.5 rounded-lg transition"
                                title="To'liq ekran leaderboard (proyektor uchun)">
                                📽 Ekran
                            </button>
                            <button v-if="session.status !== 'ended'"
                                @click="endSession"
                                :disabled="ending"
                                class="text-xs bg-red-100 hover:bg-red-200 disabled:opacity-50 text-red-700 font-semibold px-3 py-1.5 rounded-lg transition">
                                {{ ending ? '...' : 'Tugatish' }}
                            </button>
                            <span v-else class="text-xs bg-slate-200 text-slate-600 font-semibold px-3 py-1.5 rounded-lg">
                                Tugagan
                            </span>
                        </div>
                    </div>

                    <!-- Session code -->
                    <div class="bg-white rounded-xl p-4 shadow-sm space-y-3">
                        <div class="flex items-center justify-between gap-4">
                            <div>
                                <div class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-1">Kod</div>
                                <span class="text-4xl font-mono font-black text-slate-900 tracking-[0.25em]">
                                    {{ session.session_code }}
                                </span>
                            </div>
                            <button @click="copyCode"
                                :class="['text-sm px-4 py-2.5 rounded-xl transition font-bold shrink-0', copied ? 'bg-green-500 text-white' : 'bg-green-600 hover:bg-green-700 text-white']">
                                {{ copied ? '✓ Nusxalandi!' : 'Kodni nusxalash' }}
                            </button>
                        </div>
                        <div class="flex items-center gap-2 pt-1 border-t border-slate-100">
                            <button @click="copySessionUrl"
                                :class="['flex-1 flex items-center justify-center gap-2 text-sm py-2 rounded-xl font-semibold transition', copiedUrl ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-50 hover:bg-slate-100 text-slate-600']">
                                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                                </svg>
                                {{ copiedUrl ? '✓ Havola nusxalandi!' : 'Havolani nusxalash' }}
                            </button>
                            <Link :href="`/session/${session.session_code}`" target="_blank"
                                class="flex items-center justify-center gap-1.5 text-sm py-2 px-4 rounded-xl bg-slate-50 hover:bg-slate-100 text-slate-600 font-semibold transition shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                </svg>
                                Ochish
                            </Link>
                        </div>

                        <!-- QR Code -->
                        <div v-if="qrDataUrl" class="pt-3 border-t border-slate-100 flex flex-col items-center gap-2">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">QR Kod — telefon bilan skanlansin</p>
                            <img :src="qrDataUrl" alt="QR kod" class="w-28 h-28 rounded-xl border border-slate-100 shadow-sm" />
                        </div>
                    </div>

                    <!-- Live leaderboard -->
                    <div v-if="liveResults.length" class="space-y-1">
                        <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">
                            {{ session.status === 'ended' ? 'Yakuniy natijalar' : 'Jonli natijalar' }}
                            <span v-if="session.status !== 'ended'" class="ml-1 inline-block w-1.5 h-1.5 bg-green-500 rounded-full animate-pulse"></span>
                        </div>
                        <div v-for="(r, idx) in liveResults.slice(0, 10)" :key="r.id"
                            :style="`animation-delay: ${idx * 40}ms`"
                            class="bg-white rounded-xl px-3 py-2 flex items-center gap-3 text-sm row-appear">
                            <span class="w-6 text-center font-bold text-slate-400 shrink-0">
                                {{ idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : idx + 1 + '.' }}
                            </span>
                            <span class="flex-1 font-medium text-slate-700 truncate">{{ r.participant_name }}</span>
                            <span class="font-bold text-indigo-600 shrink-0">{{ r.score }}</span>
                        </div>
                        <p v-if="liveResults.length > 10" class="text-xs text-center text-slate-400 pt-1">
                            + {{ liveResults.length - 10 }} ta ishtirokchi
                        </p>
                    </div>
                    <div v-else-if="session.status !== 'ended'" class="text-xs text-center text-slate-400 py-2">
                        Hali hech kim kirmagan...
                    </div>

                </div>
            </Transition>
        </div>

        <!-- ===== PRESENTER MODE (fullscreen leaderboard) ===== -->
        <Teleport to="body">
            <Transition enter-active-class="transition duration-200" enter-from-class="opacity-0 scale-95" enter-to-class="opacity-100 scale-100"
                leave-active-class="transition duration-150" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="presenterMode && session"
                    class="fixed inset-0 z-[200] bg-slate-950 flex flex-col overflow-hidden select-none">

                    <!-- Top bar -->
                    <div class="flex items-center justify-between px-8 pt-8 pb-4 shrink-0">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-lg">
                                🎓
                            </div>
                            <div>
                                <div class="text-white font-black text-lg tracking-tight">EDUZONA</div>
                                <div class="text-slate-400 text-xs">{{ gameStore.currentGame?.topic }}</div>
                            </div>
                        </div>

                        <!-- Session code prominent display -->
                        <div class="text-center">
                            <div class="text-slate-400 text-xs font-bold uppercase tracking-widest mb-1">Sessiya kodi</div>
                            <div class="text-5xl font-mono font-black text-white tracking-[0.3em]">{{ session.session_code }}</div>
                        </div>

                        <div class="flex items-center gap-3">
                            <div class="text-right">
                                <div class="text-slate-400 text-xs">Ishtirokchilar</div>
                                <div class="text-white font-black text-2xl">{{ liveResults.length }}</div>
                            </div>
                            <span v-if="session.status !== 'ended'" class="flex items-center gap-1.5 bg-green-900/60 border border-green-700 text-green-400 text-xs font-bold px-3 py-1.5 rounded-full">
                                <span class="w-1.5 h-1.5 bg-green-400 rounded-full animate-pulse"></span>
                                Jonli
                            </span>
                            <span v-else class="bg-slate-700 text-slate-400 text-xs font-bold px-3 py-1.5 rounded-full">Tugadi</span>
                            <button @click="presenterMode = false"
                                class="w-9 h-9 flex items-center justify-center bg-slate-800 hover:bg-slate-700 rounded-xl text-slate-400 hover:text-white transition text-lg">
                                ✕
                            </button>
                        </div>
                    </div>

                    <!-- Leaderboard -->
                    <div class="flex-1 overflow-y-auto px-8 pb-8">
                        <div v-if="liveResults.length === 0" class="flex items-center justify-center h-full">
                            <div class="text-center">
                                <div class="text-6xl mb-4">⏳</div>
                                <div class="text-slate-400 text-xl font-medium">Ishtirokchilar kutilmoqda...</div>
                                <div class="text-slate-600 text-sm mt-2">O'quvchilar sessiya kodini kiritishsa, bu yerda ko'rinadi</div>
                            </div>
                        </div>
                        <div v-else class="max-w-3xl mx-auto space-y-3">
                            <div v-for="(r, idx) in liveResults" :key="r.id"
                                :class="[
                                    'flex items-center gap-5 rounded-2xl px-6 py-4 transition-all',
                                    idx === 0 ? 'bg-gradient-to-r from-yellow-900/60 to-amber-900/40 border border-yellow-700/50 scale-[1.02]' :
                                    idx === 1 ? 'bg-gradient-to-r from-slate-700/60 to-slate-800/40 border border-slate-600/50' :
                                    idx === 2 ? 'bg-gradient-to-r from-orange-900/40 to-amber-900/20 border border-orange-800/40' :
                                    'bg-slate-900/60 border border-slate-800'
                                ]">
                                <div class="text-4xl font-black w-14 text-center shrink-0">
                                    {{ idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : `${idx + 1}` }}
                                </div>
                                <div class="w-12 h-12 rounded-xl bg-slate-700 flex items-center justify-center text-white font-black text-xl shrink-0">
                                    {{ r.participant_name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="flex-1 min-w-0">
                                    <div :class="['font-bold truncate', idx === 0 ? 'text-yellow-300 text-2xl' : idx < 3 ? 'text-white text-xl' : 'text-slate-200 text-lg']">
                                        {{ r.participant_name }}
                                    </div>
                                </div>
                                <div :class="['font-black shrink-0', idx === 0 ? 'text-yellow-300 text-4xl' : idx < 3 ? 'text-white text-3xl' : 'text-slate-300 text-2xl']">
                                    {{ r.score }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Bottom bar: QR + URL hint -->
                    <div class="shrink-0 border-t border-slate-800 px-8 py-4 flex items-center justify-between gap-6">
                        <div class="text-slate-500 text-sm">
                            O'yinga qo'shilish:
                            <span class="text-white font-mono font-bold ml-2">{{ `${window.location.host}/session/${session.session_code}` }}</span>
                        </div>
                        <img v-if="qrDataUrl" :src="qrDataUrl" alt="QR" class="w-20 h-20 rounded-xl border border-slate-700" />
                    </div>
                </div>
            </Transition>

            <!-- ===== GAME CONTENT PREVIEW MODAL ===== -->
            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
                leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
                <div v-if="showPreview && gameStore.currentGame?.generated_json"
                    class="fixed inset-0 z-[200] flex items-center justify-center bg-black/60 backdrop-blur-sm p-4"
                    @click.self="showPreview = false">
                    <div class="bg-white rounded-2xl shadow-2xl max-w-lg w-full max-h-[80vh] flex flex-col overflow-hidden">
                        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                            <div>
                                <h3 class="font-bold text-slate-900">O'yin mazmuni</h3>
                                <p class="text-xs text-slate-400 mt-0.5">{{ gameStore.currentGame.generated_json.title }}</p>
                            </div>
                            <button @click="showPreview = false" class="w-8 h-8 flex items-center justify-center rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition text-lg">✕</button>
                        </div>
                        <div class="overflow-y-auto p-6 space-y-3">
                            <template v-for="(item, i) in (gameStore.currentGame.generated_json.questions ?? gameStore.currentGame.generated_json.items ?? gameStore.currentGame.generated_json.pairs ?? [])" :key="i">
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <div class="text-sm font-semibold text-slate-800 mb-1">
                                        {{ i + 1 }}. {{ item.question ?? item.word ?? item.term ?? item.left ?? item.sentence ?? JSON.stringify(item).slice(0, 80) }}
                                    </div>
                                    <div v-if="item.correct_answer ?? item.answer ?? item.right" class="text-xs text-green-700 font-medium">
                                        ✓ {{ item.correct_answer ?? item.answer ?? item.right }}
                                    </div>
                                    <div v-if="item.options?.length" class="flex flex-wrap gap-1 mt-2">
                                        <span v-for="opt in item.options" :key="opt"
                                            :class="['text-xs px-2 py-0.5 rounded-full font-medium', opt === (item.correct_answer ?? item.answer) ? 'bg-green-100 text-green-700' : 'bg-slate-200 text-slate-600']">
                                            {{ opt }}
                                        </span>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

    </AuthenticatedLayout>
</template>

<style scoped>
.card-appear { animation: cardSlide 0.4s ease both; }
@keyframes cardSlide {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.row-appear { animation: rowFade 0.3s ease both; }
@keyframes rowFade {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
