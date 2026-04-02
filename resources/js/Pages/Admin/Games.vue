<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const games        = ref([]);
const loading      = ref(true);
const search       = ref('');
const statusFilter = ref('all');
const tplFilter    = ref('all');
const confirmDeleteId = ref(null);

async function fetchGames() {
    const res = await axios.get('/api/admin/games');
    games.value = res.data.data;
    loading.value = false;
}

async function deleteGame(id) {
    await axios.delete(`/api/admin/games/${id}`);
    games.value = games.value.filter(g => g.id !== id);
    confirmDeleteId.value = null;
}

onMounted(fetchGames);

// Unique template codes from loaded games
const templateOptions = computed(() => {
    const codes = [...new Set(games.value.map(g => g.template?.code).filter(Boolean))].sort();
    return codes;
});

const filtered = computed(() => {
    let list = games.value;
    if (statusFilter.value !== 'all') list = list.filter(g => g.status === statusFilter.value);
    if (tplFilter.value !== 'all')    list = list.filter(g => g.template?.code === tplFilter.value);
    if (search.value) {
        const s = search.value.toLowerCase();
        list = list.filter(g =>
            g.topic?.toLowerCase().includes(s) ||
            g.user?.email?.toLowerCase().includes(s) ||
            g.user?.name?.toLowerCase().includes(s)
        );
    }
    return list;
});

const statusConfig = {
    ready:      { label: 'Tayyor',       class: 'bg-green-100 text-green-700' },
    generating: { label: 'Yaratilmoqda', class: 'bg-amber-100 text-amber-700' },
    error:      { label: 'Xato',         class: 'bg-red-100 text-red-700' },
};

function getStatus(s) { return statusConfig[s] ?? { label: s, class: 'bg-slate-100 text-slate-600' }; }

function fmtDate(d) {
    return new Date(d).toLocaleDateString('uz-Latn-UZ', { day: '2-digit', month: '2-digit', year: '2-digit' });
}
</script>

<template>
    <Head title="O'yinlar" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">Barcha o'yinlar</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ filtered.length }} / {{ games.length }} ta o'yin</p>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Filters -->
            <div class="flex flex-wrap items-center gap-3">
                <!-- Search -->
                <div class="relative flex-1 min-w-[200px] max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Mavzu yoki foydalanuvchi..."
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"/>
                </div>

                <!-- Template filter -->
                <select v-model="tplFilter"
                    class="border border-slate-200 rounded-xl px-3 py-2.5 text-sm text-slate-600 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    <option value="all">Barcha shablonlar</option>
                    <option v-for="code in templateOptions" :key="code" :value="code">{{ code }}</option>
                </select>

                <!-- Status filter -->
                <div class="flex gap-1.5">
                    <button v-for="s in ['all','ready','generating','error']" :key="s"
                        @click="statusFilter = s"
                        :class="[
                            'px-3 py-2 text-xs font-medium rounded-xl transition',
                            statusFilter === s
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-white border border-slate-200 text-slate-600 hover:border-indigo-300 hover:text-indigo-600'
                        ]">
                        {{ s === 'all' ? 'Barchasi' : getStatus(s).label }}
                    </button>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div v-if="loading" class="p-8 flex items-center justify-center">
                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Mavzu</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Foydalanuvchi</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">Shablon</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Holat</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden sm:table-cell">Sana</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Amal</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="(game, gi) in filtered" :key="game.id"
                            :style="`animation-delay: ${gi * 30}ms`"
                            class="hover:bg-slate-50/60 transition-colors tr-appear">
                            <td class="px-5 py-4">
                                <div class="font-medium text-slate-800 max-w-[200px] truncate">{{ game.topic }}</div>
                                <div class="text-xs text-slate-400 mt-0.5 lg:hidden">{{ game.user?.name || game.user?.email }}</div>
                            </td>
                            <td class="px-5 py-4 hidden lg:table-cell">
                                <div class="text-slate-700 text-xs font-medium">{{ game.user?.name }}</div>
                                <div class="text-slate-400 text-xs">{{ game.user?.email }}</div>
                            </td>
                            <td class="px-5 py-4 hidden md:table-cell">
                                <span class="font-mono text-xs bg-indigo-50 text-indigo-600 px-2 py-1 rounded-lg border border-indigo-100">
                                    {{ game.template?.code }}
                                </span>
                            </td>
                            <td class="px-5 py-4">
                                <div class="flex flex-col gap-1.5">
                                    <span :class="['inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold', getStatus(game.status).class]">
                                        {{ getStatus(game.status).label }}
                                    </span>
                                    <span v-if="game.is_public"
                                        class="inline-flex items-center gap-1 px-2 py-0.5 rounded-lg text-[10px] font-bold bg-green-100 text-green-700">
                                        🌐 Ommaviy
                                    </span>
                                </div>
                            </td>
                            <td class="px-5 py-4 text-xs text-slate-400 hidden sm:table-cell">{{ fmtDate(game.created_at) }}</td>
                            <td class="px-5 py-4 text-right">
                                <template v-if="confirmDeleteId === game.id">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button @click="deleteGame(game.id)"
                                            class="text-xs bg-red-600 hover:bg-red-700 text-white px-2.5 py-1.5 rounded-lg transition font-medium">
                                            Ha
                                        </button>
                                        <button @click="confirmDeleteId = null"
                                            class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 px-2.5 py-1.5 rounded-lg transition font-medium">
                                            Yo'q
                                        </button>
                                    </div>
                                </template>
                                <button v-else @click="confirmDeleteId = game.id"
                                    class="text-xs text-red-500 hover:text-red-700 hover:bg-red-50 px-2.5 py-1.5 rounded-lg transition font-medium">
                                    O'chirish
                                </button>
                            </td>
                        </tr>
                        <tr v-if="!filtered.length">
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400 text-sm">O'yin topilmadi</td>
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
