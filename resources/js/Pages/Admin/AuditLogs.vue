<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const logs = ref([]);
const loading = ref(true);
const search = ref('');

onMounted(async () => {
    const res = await axios.get('/api/admin/audit-logs');
    logs.value = res.data.data;
    loading.value = false;
});

const filtered = computed(() => {
    if (!search.value) return logs.value;
    const s = search.value.toLowerCase();
    return logs.value.filter(l =>
        l.action?.toLowerCase().includes(s) ||
        l.actor?.email?.toLowerCase().includes(s) ||
        l.entity_type?.toLowerCase().includes(s)
    );
});

const actionColors = {
    create: 'bg-green-100 text-green-700',
    update: 'bg-blue-100 text-blue-700',
    delete: 'bg-red-100 text-red-700',
    login:  'bg-indigo-100 text-indigo-700',
    logout: 'bg-slate-100 text-slate-600',
};

function actionColor(action) {
    const key = Object.keys(actionColors).find(k => action?.toLowerCase().includes(k));
    return key ? actionColors[key] : 'bg-slate-100 text-slate-600';
}

function fmtDate(d) {
    return new Date(d).toLocaleString('uz-Latn-UZ', { day: '2-digit', month: '2-digit', year: '2-digit', hour: '2-digit', minute: '2-digit' });
}
</script>

<template>
    <Head title="Audit Log" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">Audit Log</h1>
                <p class="text-xs text-slate-400 mt-0.5">Tizim harakatlari tarixi</p>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Search -->
            <div class="relative max-w-sm">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input v-model="search" placeholder="Harakat, email, entity..."
                    class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"/>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div v-if="loading" class="p-8 flex items-center justify-center">
                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Harakat</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Foydalanuvchi</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Entity</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Sana</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="(log, li) in filtered" :key="log.id"
                            :style="`animation-delay: ${li * 25}ms`"
                            class="hover:bg-slate-50/60 transition-colors tr-appear">
                            <td class="px-5 py-4">
                                <span :class="['font-mono text-xs px-2.5 py-1 rounded-lg font-semibold', actionColor(log.action)]">
                                    {{ log.action }}
                                </span>
                                <div class="text-xs text-slate-400 mt-1 md:hidden">{{ log.actor?.email ?? 'System' }}</div>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <div class="flex items-center gap-2">
                                    <div class="w-6 h-6 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 text-xs font-bold shrink-0">
                                        {{ (log.actor?.name || log.actor?.email || 'S')[0].toUpperCase() }}
                                    </div>
                                    <span class="text-xs text-slate-600">{{ log.actor?.email ?? 'System' }}</span>
                                </div>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell">
                                <span class="text-xs text-slate-500 font-mono">{{ log.entity_type }}</span>
                                <span v-if="log.entity_id" class="text-xs text-slate-400 ml-1">#{{ log.entity_id }}</span>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400">{{ fmtDate(log.created_at) }}</td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="4" class="px-5 py-12 text-center text-slate-400 text-sm">Log topilmadi</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.tr-appear { animation: trFadeIn 0.3s ease both; }
@keyframes trFadeIn {
    from { opacity: 0; transform: translateY(6px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
