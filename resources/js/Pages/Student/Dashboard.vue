<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const nameInput  = ref('');
const loading    = ref(false);
const searched   = ref(false);
const data       = ref(null);
const errorMsg   = ref('');

onMounted(() => {
    const name = new URLSearchParams(window.location.search).get('name');
    if (name) {
        nameInput.value = name;
        search();
    }
});

async function search() {
    const name = nameInput.value.trim();
    if (name.length < 2) return;
    loading.value  = true;
    errorMsg.value = '';
    data.value     = null;
    try {
        const res = await axios.get('/api/student/results', { params: { name } });
        data.value = res.data.data;
        searched.value = true;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'Xato yuz berdi.';
    } finally {
        loading.value = false;
    }
}

function formatDate(dateStr) {
    if (!dateStr) return '—';
    return new Date(dateStr).toLocaleDateString('uz-Latn-UZ', {
        day: '2-digit', month: '2-digit', year: 'numeric',
    });
}

function rankBadge(rank) {
    if (rank === 1) return { emoji: '🥇', color: 'text-amber-600 bg-amber-50' };
    if (rank === 2) return { emoji: '🥈', color: 'text-slate-500 bg-slate-100' };
    if (rank === 3) return { emoji: '🥉', color: 'text-orange-600 bg-orange-50' };
    return { emoji: `#${rank}`, color: 'text-slate-500 bg-slate-50' };
}
</script>

<template>
    <Head title="O'quvchi kabineti" />

    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-indigo-50">

        <!-- Header -->
        <div class="bg-white border-b border-slate-200 px-4 py-3 flex items-center gap-3">
            <Link href="/" class="text-slate-400 hover:text-slate-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </Link>
            <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-sm font-bold">
                🎓
            </div>
            <div>
                <h1 class="text-sm font-bold text-slate-800">O'quvchi kabineti</h1>
                <p class="text-xs text-slate-400">Natijalar tarixi va yutuqlar</p>
            </div>
        </div>

        <div class="max-w-2xl mx-auto px-4 py-8 space-y-6">

            <!-- Search card -->
            <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm">
                <h2 class="text-base font-bold text-slate-800 mb-1">Ismingizni kiriting</h2>
                <p class="text-xs text-slate-400 mb-4">O'yinlarda ishlatgan ismingizni yozing — barcha natijalaringiz ko'rinadi</p>
                <form @submit.prevent="search" class="flex gap-2">
                    <input
                        v-model="nameInput"
                        type="text"
                        placeholder="Ism Familiya"
                        minlength="2"
                        class="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
                    />
                    <button
                        type="submit"
                        :disabled="nameInput.trim().length < 2 || loading"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                        {{ loading ? '...' : 'Qidirish' }}
                    </button>
                </form>
                <p v-if="errorMsg" class="mt-3 text-sm text-red-600">{{ errorMsg }}</p>
            </div>

            <!-- Loading skeleton -->
            <div v-if="loading" class="space-y-3">
                <div class="bg-white rounded-2xl border border-slate-200 p-5 animate-pulse h-24"></div>
                <div class="bg-white rounded-2xl border border-slate-200 p-5 animate-pulse h-32"></div>
            </div>

            <!-- No results -->
            <div v-else-if="searched && data && data.stats.totalSessions === 0"
                class="bg-white rounded-2xl border border-slate-200 p-8 text-center">
                <div class="text-4xl mb-3">🔍</div>
                <p class="text-slate-600 font-medium">Natija topilmadi</p>
                <p class="text-xs text-slate-400 mt-1">«{{ nameInput }}» nomli o'quvchi hech qanday o'yinda qatnashmagan</p>
            </div>

            <!-- Results -->
            <template v-else-if="data">

                <!-- Stats row -->
                <div class="grid grid-cols-3 gap-3">
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center stat-appear" style="animation-delay: 0ms">
                        <div class="text-2xl font-extrabold text-indigo-600">{{ data.stats.totalSessions }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">O'yin</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center stat-appear" style="animation-delay: 80ms">
                        <div class="text-2xl font-extrabold text-emerald-600">{{ data.stats.avgScore }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">O'rtacha ball</div>
                    </div>
                    <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center stat-appear" style="animation-delay: 160ms">
                        <div class="text-2xl font-extrabold text-amber-500">{{ data.stats.firstPlaces }}</div>
                        <div class="text-xs text-slate-500 mt-0.5">1-o'rin</div>
                    </div>
                </div>

                <!-- Achievements -->
                <div v-if="data.achievements.length" class="bg-white rounded-2xl border border-slate-200 p-5">
                    <h3 class="text-sm font-bold text-slate-800 mb-3">Yutuqlar</h3>
                    <div class="flex flex-wrap gap-2">
                        <div v-for="(ach, ai) in data.achievements" :key="ach.key"
                            :style="`animation-delay: ${ai * 60}ms`"
                            class="flex items-center gap-2 bg-indigo-50 border border-indigo-100 rounded-xl px-3 py-2 ach-appear">
                            <span class="text-lg">{{ ach.icon }}</span>
                            <div>
                                <div class="text-xs font-bold text-indigo-800">{{ ach.label }}</div>
                                <div class="text-[10px] text-indigo-500">{{ ach.desc }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Results list -->
                <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-800">O'yinlar tarixi</h3>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div v-for="(result, ri) in data.results" :key="result.id"
                            :style="`animation-delay: ${ri * 50}ms`"
                            class="px-5 py-3.5 flex items-center gap-3 hover:bg-slate-50 transition res-appear">
                            <!-- Rank badge -->
                            <div :class="['w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold flex-shrink-0', rankBadge(result.rank).color]">
                                {{ rankBadge(result.rank).emoji }}
                            </div>
                            <!-- Info -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-slate-800 truncate">{{ result.game_topic ?? '—' }}</div>
                                <div class="text-xs text-slate-400 truncate">
                                    {{ result.template }} · {{ result.teacher }} · {{ formatDate(result.session_date) }}
                                </div>
                            </div>
                            <!-- Score -->
                            <div class="text-right flex-shrink-0">
                                <div class="text-base font-bold text-indigo-600">{{ result.score }}</div>
                                <div class="text-[10px] text-slate-400">ball</div>
                            </div>
                        </div>
                    </div>
                    <div v-if="!data.results.length" class="px-5 py-8 text-center text-slate-400 text-sm">
                        Natijalar yo'q
                    </div>
                </div>

            </template>

            <!-- Initial state hint -->
            <div v-else-if="!searched" class="text-center py-6 text-slate-400 text-sm">
                <div class="text-4xl mb-3">🏅</div>
                <p>O'yinlarda ishtirok etib, ball to'plang</p>
                <p class="text-xs mt-1 text-slate-300">Natijalaringizni ko'rish uchun ismingizni kiriting</p>
            </div>

        </div>
    </div>
</template>

<style scoped>
.stat-appear { animation: statPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes statPop {
    from { opacity: 0; transform: scale(0.85); }
    to   { opacity: 1; transform: scale(1); }
}
.ach-appear { animation: achSlide 0.3s ease both; }
@keyframes achSlide {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.res-appear { animation: resFade 0.3s ease both; }
@keyframes resFade {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
