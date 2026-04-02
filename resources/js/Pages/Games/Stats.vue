<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import axios from 'axios';

const props = defineProps({ gameId: Number });
const stats    = ref(null);
const loading  = ref(true);
const error    = ref(null);
const exporting = ref(false);

async function exportCsv() {
    exporting.value = true;
    try {
        const res = await axios.get(`/api/games/${props.gameId}/stats/export`, { responseType: 'blob' });
        const url = URL.createObjectURL(new Blob([res.data]));
        const a = document.createElement('a');
        a.href = url;
        a.download = `stats_${props.gameId}_${new Date().toISOString().slice(0,10)}.csv`;
        a.click();
        URL.revokeObjectURL(url);
    } finally {
        exporting.value = false;
    }
}

async function loadStats() {
    loading.value = true;
    try {
        const res = await axios.get(`/api/games/${props.gameId}/stats`);
        stats.value = res.data.data;
    } catch {
        error.value = "Statistika yuklanmadi.";
    } finally {
        loading.value = false;
    }
}

function pct(score, max) {
    if (!max) return 0;
    return Math.round((score / max) * 100);
}

function rankEmoji(idx) {
    return idx === 0 ? '🥇' : idx === 1 ? '🥈' : idx === 2 ? '🥉' : `${idx + 1}.`;
}

onMounted(loadStats);
</script>

<template>
    <Head title="O'yin statistikasi" />
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between w-full">
                <div class="flex items-center gap-2 text-sm">
                    <Link :href="`/games/${gameId}`" class="text-slate-400 hover:text-slate-600 transition">← Orqaga</Link>
                    <span class="text-slate-200">/</span>
                    <span class="text-slate-700 font-medium truncate max-w-xs">{{ stats?.topic ?? "O'yin" }} — Statistika</span>
                </div>
                <button v-if="stats && stats.total_players > 0"
                    @click="exportCsv"
                    :disabled="exporting"
                    class="flex items-center gap-1.5 text-xs bg-emerald-50 hover:bg-emerald-100 disabled:opacity-50 text-emerald-700 font-semibold px-3 py-1.5 rounded-xl transition">
                    <svg v-if="exporting" class="animate-spin w-3.5 h-3.5" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                    </svg>
                    <svg v-else class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                    CSV yuklab olish
                </button>
            </div>
        </template>

        <!-- Loading -->
        <div v-if="loading" class="flex items-center justify-center py-24">
            <div class="w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
        </div>

        <!-- Error -->
        <div v-else-if="error" class="max-w-lg mx-auto bg-red-50 border border-red-200 rounded-2xl p-6 text-center">
            <p class="text-red-600 text-sm">{{ error }}</p>
        </div>

        <!-- Stats -->
        <div v-else-if="stats" class="max-w-3xl mx-auto space-y-5">

            <!-- Summary cards -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm stat-appear" style="animation-delay: 0ms">
                    <div class="text-2xl font-extrabold text-indigo-600 mb-0.5">{{ stats.total_sessions }}</div>
                    <div class="text-xs text-slate-500 font-medium">Sessiyalar</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm stat-appear" style="animation-delay: 70ms">
                    <div class="text-2xl font-extrabold text-green-600 mb-0.5">{{ stats.total_players }}</div>
                    <div class="text-xs text-slate-500 font-medium">O'yinchilar</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm stat-appear" style="animation-delay: 140ms">
                    <div class="text-2xl font-extrabold text-amber-600 mb-0.5">{{ stats.average_score }}</div>
                    <div class="text-xs text-slate-500 font-medium">O'rtacha ball</div>
                </div>
                <div class="bg-white rounded-2xl border border-slate-100 p-5 text-center shadow-sm stat-appear" style="animation-delay: 210ms">
                    <div class="text-2xl font-extrabold text-purple-600 mb-0.5">{{ stats.best_score }}</div>
                    <div class="text-xs text-slate-500 font-medium">Eng yuqori ball</div>
                </div>
            </div>

            <!-- No data -->
            <div v-if="stats.total_sessions === 0" class="bg-white rounded-2xl border border-slate-100 p-12 text-center shadow-sm">
                <div class="text-5xl mb-4">📊</div>
                <h3 class="font-semibold text-slate-700 mb-2">Hali sessiya yo'q</h3>
                <p class="text-slate-400 text-sm mb-5">O'yin o'ynash uchun sessiya yarating va o'quvchilarga kod yuboring.</p>
                <Link :href="`/games/${gameId}`" class="inline-block bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl text-sm font-semibold transition">
                    O'yin sahifasiga →
                </Link>
            </div>

            <!-- Sessions list -->
            <div v-else class="space-y-4">
                <div v-for="(session, sIdx) in stats.sessions" :key="session.id"
                    :style="`animation-delay: ${sIdx * 60}ms`"
                    class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden ses-appear">

                    <!-- Session header -->
                    <div class="px-5 py-4 border-b border-slate-50 flex items-center justify-between">
                        <div class="flex items-center gap-3">
                            <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 font-bold text-sm">
                                {{ sIdx + 1 }}
                            </div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm flex items-center gap-2">
                                    Kod: <span class="font-mono tracking-widest text-indigo-700">{{ session.session_code }}</span>
                                    <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                                        session.status === 'active' ? 'bg-green-100 text-green-700' :
                                        session.status === 'ended' ? 'bg-slate-100 text-slate-600' :
                                        'bg-amber-100 text-amber-700']">
                                        {{ session.status === 'active' ? 'Faol' : session.status === 'ended' ? 'Tugagan' : 'Kutilmoqda' }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-400 mt-0.5">
                                    {{ session.started_at ? new Date(session.started_at).toLocaleDateString('uz-Latn-UZ', { day: '2-digit', month: '2-digit', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : 'Boshlanmagan' }}
                                </div>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-lg font-bold text-slate-800">{{ session.results_count }}</div>
                            <div class="text-xs text-slate-400">o'yinchi</div>
                        </div>
                    </div>

                    <!-- Participants -->
                    <div v-if="session.top_results.length === 0" class="px-5 py-4 text-center text-slate-400 text-sm">
                        Hali natija yo'q
                    </div>
                    <div v-else class="divide-y divide-slate-50">
                        <div v-for="(result, rIdx) in session.top_results" :key="result.id"
                            :style="`animation-delay: ${rIdx * 40}ms`"
                            class="px-5 py-3 flex items-center gap-3 hover:bg-slate-50 transition res-appear">
                            <div class="shrink-0 text-base w-6 text-center">{{ rankEmoji(rIdx) }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-slate-800 text-sm truncate">{{ result.participant_name }}</div>
                            </div>
                            <!-- Score bar -->
                            <div class="w-32 hidden sm:block">
                                <div class="h-1.5 bg-slate-100 rounded-full overflow-hidden">
                                    <div class="h-1.5 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full"
                                        :style="{ width: pct(result.score, session.top_results[0]?.score || 1) + '%' }">
                                    </div>
                                </div>
                            </div>
                            <div class="shrink-0 text-right min-w-[60px]">
                                <span class="font-extrabold text-indigo-600 text-base">{{ result.score }}</span>
                                <span class="text-slate-400 text-xs ml-0.5">ball</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.stat-appear { animation: statPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes statPop {
    from { opacity: 0; transform: scale(0.85); }
    to   { opacity: 1; transform: scale(1); }
}
.ses-appear { animation: sesSlideIn 0.4s ease both; }
@keyframes sesSlideIn {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
.res-appear { animation: resFade 0.3s ease both; }
@keyframes resFade {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>