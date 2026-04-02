<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const stats   = ref(null);
const loading = ref(true);
const health  = ref(null);

onMounted(async () => {
    const [statsRes, healthRes] = await Promise.allSettled([
        axios.get('/api/admin/ai-usage'),
        axios.get('/api/system/health'),
    ]);
    if (statsRes.status === 'fulfilled')  stats.value  = statsRes.value.data.data;
    if (healthRes.status === 'fulfilled') health.value = healthRes.value.data;
    loading.value = false;
});

const chartMax = computed(() => {
    if (!stats.value?.daily_chart) return 1;
    const max = Math.max(...stats.value.daily_chart.map(d => d.count));
    return max > 0 ? max : 1;
});

const statCards = [
    { key: 'total_users',   label: "Jami o'qituvchilar", color: 'from-violet-500 to-purple-600',
      iconPath: 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z' },
    { key: 'total_games',   label: "Jami o'yinlar", color: 'from-indigo-500 to-blue-600',
      iconPath: 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z' },
    { key: 'today_games',   label: "Bugungi o'yinlar", color: 'from-green-500 to-emerald-600',
      iconPath: 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z' },
    { key: 'ready_games',   label: "Tayyor o'yinlar", color: 'from-blue-500 to-cyan-600',
      iconPath: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
    { key: 'active_users',  label: 'Faol foydalanuvchilar', color: 'from-teal-500 to-cyan-600',
      iconPath: 'M5.121 17.804A13.937 13.937 0 0112 16c2.5 0 4.847.655 6.879 1.804M15 10a3 3 0 11-6 0 3 3 0 016 0z' },
    { key: 'error_games',   label: 'Xatolar', color: 'from-red-500 to-rose-600',
      iconPath: 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z' },
];

const settingLabels = {
    model: 'AI Model', daily_request_limit: 'Kunlik limit',
    daily_token_budget: 'Token budjet', max_retries: 'Qayta urinish', max_tokens: 'Maks token',
};
</script>

<template>
    <Head title="Admin Dashboard" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">Dashboard</h1>
                <p class="text-xs text-slate-400 mt-0.5">Platforma umumiy ko'rinishi</p>
            </div>
        </template>

        <!-- Skeleton -->
        <div v-if="loading" class="space-y-6">
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-slate-200 p-5 animate-pulse h-28"></div>
            </div>
        </div>

        <div v-else-if="stats" class="space-y-6">
            <!-- Stat cards -->
            <div class="grid grid-cols-2 lg:grid-cols-3 gap-4">
                <div v-for="(card, ci) in statCards" :key="card.key"
                    :style="`animation-delay: ${ci * 60}ms`"
                    class="bg-white rounded-2xl border border-slate-200 p-5 hover:shadow-md hover:border-slate-300 transition-all duration-200 stat-appear">
                    <div class="flex items-start justify-between mb-3">
                        <div :class="['w-10 h-10 bg-gradient-to-br rounded-xl flex items-center justify-center shadow-sm', card.color]">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="card.iconPath"/>
                            </svg>
                        </div>
                    </div>
                    <div class="text-2xl font-bold text-slate-800 mb-0.5">{{ stats[card.key] ?? '—' }}</div>
                    <div class="text-xs text-slate-500 font-medium">{{ card.label }}</div>
                </div>
            </div>

            <!-- Queue health widget -->
            <div v-if="health" class="bg-white rounded-2xl border overflow-hidden"
                :class="health.queue_healthy ? 'border-green-200' : 'border-red-200'">
                <div class="px-5 py-4 flex items-center gap-3"
                    :class="health.queue_healthy ? 'bg-green-50' : 'bg-red-50'">
                    <div class="w-8 h-8 rounded-xl flex items-center justify-center"
                        :class="health.queue_healthy ? 'bg-green-500' : 'bg-red-500'">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"
                                d="M5 12h14M12 5l7 7-7 7"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h3 class="font-semibold text-sm"
                            :class="health.queue_healthy ? 'text-green-800' : 'text-red-800'">
                            Navbat holati —
                            <span class="font-bold">
                                {{ health.queue_healthy ? '✅ Ishlayapti' : '❌ Worker to\'xtagan yoki sekin' }}
                            </span>
                        </h3>
                        <p class="text-xs mt-0.5"
                            :class="health.queue_healthy ? 'text-green-600' : 'text-red-600'">
                            Kutayotgan: {{ health.pending_jobs }} ta ·
                            Oxirgi soatda xato: {{ health.failed_last_hour }} ta ·
                            Oxirgi faollik: {{ health.last_processed_at ?? 'Hech qachon' }}
                        </p>
                    </div>
                    <div v-if="!health.queue_healthy"
                        class="text-xs font-bold bg-red-100 text-red-700 px-3 py-1 rounded-full">
                        php artisan queue:work
                    </div>
                </div>
            </div>

            <!-- Daily chart -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-blue-600 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-sm">So'nggi 7 kun</h3>
                        <p class="text-xs text-slate-400">Har kuni yaratilgan o'yinlar</p>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="stats.daily_chart" class="flex items-end gap-2 h-32">
                        <div v-for="day in stats.daily_chart" :key="day.date"
                            class="flex-1 flex flex-col items-center gap-1.5">
                            <div class="text-[11px] font-bold text-indigo-600" v-show="day.count > 0">{{ day.count }}</div>
                            <div class="w-full rounded-t-lg transition-all duration-500 bg-gradient-to-t from-indigo-500 to-indigo-400 min-h-[4px]"
                                :style="{ height: chartMax > 0 ? `${Math.max(4, (day.count / chartMax) * 96)}px` : '4px' }">
                            </div>
                            <div class="text-[10px] text-slate-400 text-center leading-tight">{{ day.date }}</div>
                        </div>
                    </div>
                    <div v-else class="text-center text-slate-400 text-sm py-8">Ma'lumot yo'q</div>
                </div>
            </div>

            <!-- Top pedagoglar -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-amber-500 to-orange-500 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-sm">Top pedagoglar</h3>
                        <p class="text-xs text-slate-400">Eng ko'p o'yin yaratganlar</p>
                    </div>
                </div>
                <div class="p-5">
                    <div v-if="!stats.top_pedagoglar?.length" class="text-center text-slate-400 text-sm py-4">Ma'lumot yo'q</div>
                    <div v-else class="space-y-2">
                        <div v-for="(pedagog, idx) in stats.top_pedagoglar" :key="pedagog.id"
                            :style="`animation-delay: ${idx * 50}ms`"
                            class="flex items-center gap-3 py-2 px-3 rounded-xl hover:bg-slate-50 transition row-appear">
                            <!-- Rank -->
                            <div :class="[
                                'w-7 h-7 rounded-full flex items-center justify-center text-xs font-bold flex-shrink-0',
                                idx === 0 ? 'bg-amber-100 text-amber-700' :
                                idx === 1 ? 'bg-slate-100 text-slate-600' :
                                idx === 2 ? 'bg-orange-100 text-orange-700' :
                                'bg-slate-50 text-slate-500'
                            ]">{{ idx + 1 }}</div>
                            <!-- Avatar -->
                            <img v-if="pedagog.avatar" :src="pedagog.avatar" :alt="pedagog.name"
                                class="w-8 h-8 rounded-full object-cover flex-shrink-0 border border-slate-200" />
                            <div v-else
                                class="w-8 h-8 rounded-full bg-gradient-to-br from-indigo-500 to-blue-600 flex items-center justify-center text-white text-xs font-bold flex-shrink-0">
                                {{ (pedagog.name || '?')[0].toUpperCase() }}
                            </div>
                            <!-- Name -->
                            <div class="flex-1 min-w-0">
                                <div class="text-sm font-medium text-slate-800 truncate">{{ pedagog.name }}</div>
                                <div class="text-xs text-slate-400 truncate">{{ pedagog.email }}</div>
                            </div>
                            <!-- Count -->
                            <div class="flex items-center gap-1 flex-shrink-0">
                                <span class="text-sm font-bold text-indigo-600">{{ pedagog.games_count }}</span>
                                <span class="text-xs text-slate-400">ta o'yin</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AI Settings card -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center gap-3">
                    <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl flex items-center justify-center">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-slate-800 text-sm">AI Sozlamalar</h3>
                        <p class="text-xs text-slate-400">Joriy konfiguratsiya</p>
                    </div>
                </div>
                <div class="p-5 grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
                    <div v-for="(value, key) in stats.settings" :key="key"
                        class="bg-slate-50 rounded-xl p-3 border border-slate-100">
                        <div class="text-slate-400 text-[11px] font-medium uppercase tracking-wider mb-1">{{ settingLabels[key] ?? key }}</div>
                        <div class="font-mono font-semibold text-slate-800 text-sm truncate">{{ value }}</div>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.stat-appear { animation: statPop 0.4s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes statPop {
    from { opacity: 0; transform: scale(0.88) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.row-appear { animation: rowFade 0.3s ease both; }
@keyframes rowFade {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>