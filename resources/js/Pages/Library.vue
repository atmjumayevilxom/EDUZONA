<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useTemplateMeta } from '@/composables/useTemplateMeta';
import axios from 'axios';

const { tplMeta } = useTemplateMeta();
const activeSubject = ref('all');
const search        = ref('');
const games         = ref([]);
const loading       = ref(true);
const lastPage      = ref(1);
const currentPage   = ref(1);
const copying       = ref(null);
let searchTimer     = null;

const subjects = [
    { id: 'all',          label: 'Barchasi',          icon: '🗂' },
    { id: 'quiz_mcq',     label: 'Viktorina',          icon: '❓' },
    { id: 'true_false',   label: "To'g'ri/Noto'g'ri",  icon: '✅' },
    { id: 'flashcards',   label: 'Flesh-kartochkalar',     icon: '🃏' },
    { id: 'anagram',      label: 'Anagramma',          icon: '🔤' },
    { id: 'word_search',  label: "So'z qidirish",      icon: '🔍' },
    { id: 'matching_pairs', label: 'Juftlashtirish',   icon: '🔗' },
];

async function fetchGames(pg = 1) {
    loading.value = true;
    try {
        const params = { page: pg };
        if (activeSubject.value !== 'all') params.template = activeSubject.value;
        if (search.value.trim()) params.search = search.value.trim();
        const res = await axios.get('/api/public/games', { params });
        const data = res.data;
        games.value     = pg === 1 ? (data.data ?? []) : [...games.value, ...(data.data ?? [])];
        lastPage.value  = data.last_page ?? data.meta?.last_page ?? 1;
        currentPage.value = pg;
    } catch {
        games.value = [];
    } finally {
        loading.value = false;
    }
}

function onFilterChange() { fetchGames(1); }

function onSearch() {
    clearTimeout(searchTimer);
    searchTimer = setTimeout(() => fetchGames(1), 350);
}

async function copyGame(id) {
    copying.value = id;
    try {
        await axios.post(`/api/games/${id}/copy`);
        router.visit('/dashboard');
    } catch { /* ignore */ } finally {
        copying.value = null;
    }
}

onMounted(() => fetchGames(1));

const hasMore = computed(() => currentPage.value < lastPage.value);
</script>

<template>
    <Head title="Kutubxona" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between gap-4 flex-wrap">
                <h2 class="text-xl font-semibold text-slate-800">Ommaviy Kutubxona</h2>
                <div class="relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                    </svg>
                    <input v-model="search" @input="onSearch" type="text" placeholder="O'yin qidirish..."
                        class="pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white w-52 transition"/>
                </div>
            </div>
        </template>

        <div class="max-w-5xl mx-auto space-y-6">

            <!-- Hero -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white flex items-center gap-5">
                <div class="text-5xl shrink-0">📚</div>
                <div>
                    <h3 class="text-xl font-bold mb-1">Ommaviy O'yinlar Kutubxonasi</h3>
                    <p class="text-indigo-200 text-sm leading-relaxed">
                        O'qituvchilar tomonidan yaratilgan va ommaga ochiq o'yinlar. O'ynash va nusxalash bepul.
                    </p>
                </div>
            </div>

            <!-- Subject filter -->
            <div class="flex gap-2 flex-wrap">
                <button v-for="sub in subjects" :key="sub.id"
                    @click="activeSubject = sub.id; onFilterChange()"
                    :class="[
                        'flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium border-2 transition',
                        activeSubject === sub.id
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'border-slate-200 text-slate-600 hover:border-indigo-300 bg-white'
                    ]">
                    {{ sub.icon }} {{ sub.label }}
                </button>
            </div>

            <!-- Loading skeletons -->
            <div v-if="loading && games.length === 0" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-slate-200 overflow-hidden animate-pulse">
                    <div class="h-20 bg-slate-100"></div>
                    <div class="p-4 space-y-2">
                        <div class="h-3 bg-slate-100 rounded w-3/4"></div>
                        <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                        <div class="h-7 bg-slate-100 rounded mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Empty state -->
            <div v-else-if="!loading && games.length === 0"
                class="flex flex-col items-center justify-center py-20 text-center">
                <div class="text-6xl mb-4">📭</div>
                <h3 class="text-lg font-semibold text-slate-700 mb-2">Hozircha ommaviy o'yinlar yo'q</h3>
                <p class="text-slate-400 text-sm max-w-sm leading-relaxed">
                    O'yiningizni ommaviy qilish uchun o'yin sahifasida
                    <strong class="text-slate-600">"Ommaviy qilish"</strong> tugmasini bosing.
                </p>
                <Link href="/games/create"
                    class="mt-6 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-6 py-3 rounded-2xl transition text-sm">
                    ✨ O'yin yaratish
                </Link>
            </div>

            <!-- Games grid -->
            <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="(game, gi) in games" :key="game.id"
                    :style="`animation-delay: ${(gi % 6) * 45}ms`"
                    class="bg-white rounded-2xl border border-slate-200 overflow-hidden group hover:shadow-md hover:border-indigo-200 transition-all game-appear">
                    <!-- Header -->
                    <div :class="['h-20 bg-gradient-to-br flex items-center justify-center relative overflow-hidden', tplMeta(game.template?.code).color]">
                        <div class="absolute -top-3 -right-3 w-12 h-12 bg-white/10 rounded-full"></div>
                        <span class="relative z-10 text-3xl">{{ tplMeta(game.template?.code).icon }}</span>
                        <span class="absolute top-2 right-2 text-xs bg-white/20 text-white font-mono px-2 py-0.5 rounded-full">
                            {{ game.language?.toUpperCase() }}
                        </span>
                    </div>
                    <!-- Body -->
                    <div class="p-4">
                        <p class="text-xs text-indigo-600 font-semibold mb-1">{{ tplMeta(game.template?.code).label }}</p>
                        <h4 class="font-semibold text-slate-900 text-sm leading-tight mb-1 line-clamp-2">{{ game.topic }}</h4>
                        <p class="text-xs text-slate-400 mb-3 flex items-center gap-1">
                            <span>👤</span> {{ game.user?.name ?? 'O\'qituvchi' }}
                        </p>
                        <div class="flex gap-2">
                            <Link :href="`/games/${game.id}/play`"
                                class="flex-1 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold text-center transition">
                                ▶ O'ynash
                            </Link>
                            <button @click="copyGame(game.id)" :disabled="copying === game.id"
                                class="px-3 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 disabled:opacity-50 text-slate-600 text-xs font-semibold transition"
                                title="Nusxalash">
                                {{ copying === game.id ? '...' : '📋' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Load more -->
            <div v-if="hasMore" class="text-center pt-2">
                <button @click="fetchGames(currentPage + 1)" :disabled="loading"
                    class="px-6 py-2.5 bg-white border border-slate-200 hover:border-indigo-300 text-slate-600 font-semibold text-sm rounded-2xl transition disabled:opacity-50">
                    {{ loading ? 'Yuklanmoqda...' : "Ko'proq ko'rish ↓" }}
                </button>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.game-appear { animation: gameSlide 0.35s ease both; }
@keyframes gameSlide {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
