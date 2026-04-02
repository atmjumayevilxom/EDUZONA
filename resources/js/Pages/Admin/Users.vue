<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const users   = ref([]);
const loading = ref(true);
const search  = ref('');
const confirmBlockId = ref(null);
const resettingId = ref(null);

async function fetchUsers() {
    loading.value = true;
    const res = await axios.get('/api/admin/users');
    users.value = res.data.data;
    loading.value = false;
}

async function block(id) {
    await axios.patch(`/api/admin/users/${id}/block`);
    const u = users.value.find(u => u.id === id);
    if (u) u.status = 'blocked';
    confirmBlockId.value = null;
}

async function activate(id) {
    await axios.patch(`/api/admin/users/${id}/activate`);
    const u = users.value.find(u => u.id === id);
    if (u) u.status = 'active';
}

async function resetLimit(id) {
    resettingId.value = id;
    try {
        await axios.patch(`/api/admin/users/${id}/reset-limit`);
        const u = users.value.find(u => u.id === id);
        if (u) u.daily_limit_reset_at = new Date().toISOString();
    } finally {
        resettingId.value = null;
    }
}

onMounted(fetchUsers);

const filtered = computed(() => {
    if (!search.value) return users.value;
    const s = search.value.toLowerCase();
    return users.value.filter(u =>
        u.name?.toLowerCase().includes(s) ||
        u.email?.toLowerCase().includes(s)
    );
});

function initials(name) {
    return (name || 'U').split(' ').map(w => w[0]).join('').toUpperCase().slice(0, 2);
}

function fmtDate(d) {
    if (!d) return '—';
    return new Date(d).toLocaleDateString('uz-Latn-UZ', { day: '2-digit', month: '2-digit', year: '2-digit' });
}

const avatarColors = [
    'from-indigo-500 to-blue-600',
    'from-pink-500 to-rose-600',
    'from-green-500 to-emerald-600',
    'from-amber-500 to-orange-500',
    'from-purple-500 to-violet-600',
];
</script>

<template>
    <Head title="Foydalanuvchilar" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">Foydalanuvchilar</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ users.length }} ta foydalanuvchi</p>
            </div>
        </template>

        <div class="space-y-4">
            <!-- Search -->
            <div class="flex items-center gap-3">
                <div class="relative flex-1 max-w-sm">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input v-model="search" type="text" placeholder="Ism yoki email bo'yicha qidirish..."
                        class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300 bg-white"/>
                </div>
                <span class="text-xs text-slate-400">
                    {{ filtered.length }} / {{ users.length }}
                </span>
            </div>

            <!-- Table -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div v-if="loading" class="p-8 flex items-center justify-center">
                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <table v-else class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-100 bg-slate-50/50">
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Foydalanuvchi</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden lg:table-cell">Email</th>
                            <th class="px-5 py-3.5 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider hidden md:table-cell">O'yinlar</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider hidden xl:table-cell">So'nggi kirish</th>
                            <th class="px-5 py-3.5 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Holat</th>
                            <th class="px-5 py-3.5 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Amallar</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        <tr v-for="(user, idx) in filtered" :key="user.id"
                            :style="`animation-delay: ${idx * 30}ms`"
                            class="hover:bg-slate-50/60 transition-colors tr-appear">

                            <!-- Avatar + Name -->
                            <td class="px-5 py-4">
                                <div class="flex items-center gap-3">
                                    <img v-if="user.avatar" :src="user.avatar"
                                        class="w-9 h-9 rounded-xl object-cover border border-slate-200 shrink-0" />
                                    <div v-else :class="['w-9 h-9 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-xs font-bold shrink-0', avatarColors[idx % avatarColors.length]]">
                                        {{ initials(user.name) }}
                                    </div>
                                    <div>
                                        <div class="font-medium text-slate-800">{{ user.name }}</div>
                                        <div class="text-xs text-slate-400 lg:hidden">{{ user.email }}</div>
                                        <div class="text-xs mt-0.5">
                                            <span :class="['font-semibold', user.role === 'admin' ? 'text-purple-600' : 'text-slate-400']">
                                                {{ user.role === 'admin' ? 'Admin' : 'Pedagog' }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </td>

                            <!-- Email -->
                            <td class="px-5 py-4 text-slate-500 text-sm hidden lg:table-cell">{{ user.email }}</td>

                            <!-- Games count -->
                            <td class="px-5 py-4 text-center hidden md:table-cell">
                                <span class="inline-flex items-center gap-1 text-sm font-bold text-indigo-600 bg-indigo-50 px-2.5 py-1 rounded-lg">
                                    {{ user.games_count ?? 0 }}
                                    <svg class="w-3 h-3 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                                    </svg>
                                </span>
                            </td>

                            <!-- Last login -->
                            <td class="px-5 py-4 text-xs text-slate-400 hidden xl:table-cell">
                                {{ fmtDate(user.last_login_at) }}
                            </td>

                            <!-- Status -->
                            <td class="px-5 py-4">
                                <span :class="[
                                    'inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold',
                                    user.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
                                ]">
                                    <span :class="['w-1.5 h-1.5 rounded-full', user.status === 'active' ? 'bg-green-500' : 'bg-red-500']"></span>
                                    {{ user.status === 'active' ? 'Faol' : 'Bloklangan' }}
                                </span>
                            </td>

                            <!-- Actions -->
                            <td class="px-5 py-4 text-right">
                                <template v-if="user.status === 'active' && user.role !== 'admin'">
                                    <div v-if="confirmBlockId === user.id" class="flex items-center justify-end gap-1.5">
                                        <button @click="block(user.id)"
                                            class="text-xs bg-red-600 hover:bg-red-700 text-white px-2.5 py-1.5 rounded-lg transition font-medium">
                                            Ha
                                        </button>
                                        <button @click="confirmBlockId = null"
                                            class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 px-2.5 py-1.5 rounded-lg transition font-medium">
                                            Yo'q
                                        </button>
                                    </div>
                                    <button v-else @click="confirmBlockId = user.id"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-red-50 hover:bg-red-100 text-red-600 text-xs font-medium rounded-lg transition">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                        </svg>
                                        Bloklash
                                    </button>
                                </template>
                                <button v-else-if="user.status === 'blocked'"
                                    @click="activate(user.id)"
                                    class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-green-50 hover:bg-green-100 text-green-600 text-xs font-medium rounded-lg transition">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    Faollashtirish
                                </button>
                                <!-- Reset daily limit (all non-admin users) -->
                                <button v-if="user.role !== 'admin'"
                                    @click="resetLimit(user.id)"
                                    :disabled="resettingId === user.id"
                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 bg-amber-50 hover:bg-amber-100 disabled:opacity-50 text-amber-700 text-xs font-medium rounded-lg transition ml-1"
                                    title="Kunlik limitni tiklash">
                                    {{ resettingId === user.id ? '...' : '↺' }}
                                </button>
                                <span v-else class="text-xs text-slate-300">—</span>
                            </td>
                        </tr>

                        <tr v-if="!filtered.length">
                            <td colspan="6" class="px-5 py-12 text-center text-slate-400 text-sm">
                                Foydalanuvchi topilmadi
                            </td>
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
