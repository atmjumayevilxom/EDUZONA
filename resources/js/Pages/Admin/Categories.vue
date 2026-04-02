<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const categories = ref([]);
const loading = ref(true);
const saving = ref(false);
const error = ref('');
const newName = ref('');
const editingId = ref(null);
const editingName = ref('');
const editingStatus = ref('active');
const confirmDeleteId = ref(null);

async function fetch() {
    loading.value = true;
    const res = await axios.get('/api/admin/categories');
    categories.value = res.data.data;
    loading.value = false;
}

async function add() {
    const name = newName.value.trim();
    if (!name) return;
    error.value = '';
    saving.value = true;
    try {
        await axios.post('/api/admin/categories', { name });
        newName.value = '';
        await fetch();
    } catch (e) {
        error.value = e.response?.data?.errors?.name?.[0] ?? e.response?.data?.message ?? 'Xato';
    } finally {
        saving.value = false;
    }
}

function startEdit(cat) {
    editingId.value = cat.id;
    editingName.value = cat.name;
    editingStatus.value = cat.status;
}

async function saveEdit() {
    if (!editingName.value.trim()) return;
    saving.value = true;
    try {
        await axios.patch(`/api/admin/categories/${editingId.value}`, {
            name: editingName.value.trim(),
            status: editingStatus.value,
        });
        editingId.value = null;
        await fetch();
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Xato';
    } finally {
        saving.value = false;
    }
}

async function toggleStatus(cat) {
    await axios.patch(`/api/admin/categories/${cat.id}`, {
        status: cat.status === 'active' ? 'inactive' : 'active',
    });
    await fetch();
}

async function remove(id) {
    error.value = '';
    try {
        await axios.delete(`/api/admin/categories/${id}`);
        confirmDeleteId.value = null;
        await fetch();
    } catch (e) {
        error.value = e.response?.data?.message ?? 'Xato';
        confirmDeleteId.value = null;
    }
}

onMounted(fetch);
</script>

<template>
    <Head title="Kategoriyalar" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">Kategoriyalar</h1>
                <p class="text-xs text-slate-400 mt-0.5">{{ categories.length }} ta kategoriya</p>
            </div>
        </template>

        <div class="max-w-2xl space-y-4">
            <!-- Add form -->
            <div class="bg-white rounded-2xl border border-slate-200 p-5">
                <h3 class="text-sm font-semibold text-slate-800 mb-3">Yangi kategoriya</h3>
                <div class="flex gap-2">
                    <input
                        v-model="newName"
                        @keyup.enter="add"
                        placeholder="Kategoriya nomi..."
                        class="flex-1 border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"
                    />
                    <button @click="add" :disabled="saving || !newName.trim()"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                        + Qo'shish
                    </button>
                </div>
                <p v-if="error" class="text-xs text-red-500 mt-2">{{ error }}</p>
            </div>

            <!-- List -->
            <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                <div v-if="loading" class="p-8 flex justify-center">
                    <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
                </div>

                <div v-else class="divide-y divide-slate-50">
                    <div v-for="(cat, ci) in categories" :key="cat.id"
                        :style="`animation-delay: ${ci * 50}ms`"
                        class="flex items-center gap-3 px-5 py-4 cat-appear">
                        <!-- Edit mode -->
                        <template v-if="editingId === cat.id">
                            <input v-model="editingName"
                                class="flex-1 border border-indigo-300 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                @keyup.enter="saveEdit" @keyup.escape="editingId = null"
                            />
                            <select v-model="editingStatus"
                                class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none">
                                <option value="active">Faol</option>
                                <option value="inactive">Nofaol</option>
                            </select>
                            <button @click="saveEdit" :disabled="saving"
                                class="bg-green-500 hover:bg-green-600 text-white text-xs font-bold px-4 py-2 rounded-xl transition">
                                ✓ Saqlash
                            </button>
                            <button @click="editingId = null"
                                class="bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold px-4 py-2 rounded-xl transition">
                                Bekor
                            </button>
                        </template>

                        <!-- View mode -->
                        <template v-else>
                            <div class="w-8 h-8 bg-indigo-100 rounded-xl flex items-center justify-center text-indigo-600 text-xs font-bold shrink-0">
                                {{ cat.sort_order }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="font-semibold text-slate-800 text-sm">{{ cat.name }}</div>
                                <div class="text-xs text-slate-400">{{ cat.games_count ?? 0 }} ta o'yin</div>
                            </div>
                            <span :class="[
                                'text-xs px-2.5 py-1 rounded-lg font-medium',
                                cat.status === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'
                            ]">
                                {{ cat.status === 'active' ? 'Faol' : 'Nofaol' }}
                            </span>
                            <button @click="startEdit(cat)"
                                class="text-xs bg-blue-50 hover:bg-blue-100 text-blue-600 border border-blue-200 px-3 py-1.5 rounded-xl transition font-semibold">
                                Tahrir
                            </button>
                            <template v-if="confirmDeleteId === cat.id">
                                <button @click="remove(cat.id)"
                                    class="text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1.5 rounded-xl transition font-semibold">
                                    Ha
                                </button>
                                <button @click="confirmDeleteId = null"
                                    class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-600 px-3 py-1.5 rounded-xl transition font-semibold">
                                    Yo'q
                                </button>
                            </template>
                            <button v-else @click="confirmDeleteId = cat.id"
                                class="text-xs bg-red-50 hover:bg-red-100 text-red-500 border border-red-200 px-3 py-1.5 rounded-xl transition font-semibold">
                                O'chirish
                            </button>
                        </template>
                    </div>

                    <div v-if="!categories.length" class="p-8 text-center text-slate-400 text-sm">
                        Hali kategoriyalar yo'q
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>

<style scoped>
.cat-appear { animation: catFadeIn 0.3s ease both; }
@keyframes catFadeIn {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
