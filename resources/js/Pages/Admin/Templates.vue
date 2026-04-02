<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import GameTypeIllustration from '@/Components/GameTypeIllustration.vue';
import axios from 'axios';

const templates = ref([]);
const loading = ref(true);

// Budget editing
const budgetEditing = ref(null);
const budgetForm = ref({ token_budget_base: 800, token_budget_per_item: 60 });
const savingBudget = ref(false);

function openBudgetEdit(tpl) {
    budgetEditing.value = tpl.id;
    budgetForm.value = { token_budget_base: tpl.token_budget_base || 800, token_budget_per_item: tpl.token_budget_per_item || 60 };
}

async function saveBudget(id) {
    savingBudget.value = true;
    try {
        await axios.patch(`/api/admin/templates/${id}/budget`, budgetForm.value);
        const idx = templates.value.findIndex(t => t.id === id);
        if (idx !== -1) templates.value[idx] = { ...templates.value[idx], ...budgetForm.value };
        budgetEditing.value = null;
    } catch { /* ignore */ } finally {
        savingBudget.value = false;
    }
}

// Create template modal
const showCreate = ref(false);
const creating = ref(false);
const createErrors = ref({});
const form = ref({
    name: '',
    code: '',
    type: 'quiz',
    renderer_component: '',
    output_schema: '',
    prompt_text: '',
    token_budget_base: 800,
    token_budget_per_item: 60,
});

const typeOptions = [
    { value: 'quiz',   label: 'Viktorina (quiz)' },
    { value: 'word',   label: "So'z o'yini (word)" },
    { value: 'match',  label: 'Moslashtirish (match)' },
    { value: 'puzzle', label: 'Boshqotirma (puzzle)' },
    { value: 'memory', label: 'Xotira (memory)' },
    { value: 'drag',   label: 'Sudrab tashlash (drag)' },
];

const outputSchemaHint = `{"required":["title","items"],"item_fields":["id","question","options","answer_index","explanation"]}`;

async function fetchTemplates() {
    const res = await axios.get('/api/admin/templates');
    templates.value = res.data.data;
    loading.value = false;
}

async function toggle(id) {
    await axios.patch(`/api/admin/templates/${id}/toggle`);
    await fetchTemplates();
}

function openCreate() {
    form.value = { name: '', code: '', type: 'quiz', renderer_component: '', output_schema: '', prompt_text: '', token_budget_base: 800, token_budget_per_item: 60 };
    createErrors.value = {};
    showCreate.value = true;
}

function autoCode() {
    form.value.code = form.value.name
        .toLowerCase()
        .replace(/[^a-z0-9\s]/g, '')
        .trim()
        .replace(/\s+/g, '_')
        .slice(0, 50);
}

async function submitCreate() {
    creating.value = true;
    createErrors.value = {};
    try {
        await axios.post('/api/admin/templates', form.value);
        showCreate.value = false;
        await fetchTemplates();
    } catch (e) {
        if (e.response?.data?.errors) {
            createErrors.value = e.response.data.errors;
        }
    } finally {
        creating.value = false;
    }
}

onMounted(fetchTemplates);

const gradients = {
    quiz_mcq:          'from-indigo-500 to-blue-600',
    anagram:           'from-pink-500 to-rose-600',
    true_false:        'from-green-500 to-emerald-600',
    flashcards:        'from-yellow-500 to-orange-500',
    matching_pairs:    'from-purple-500 to-violet-600',
    type_answer:       'from-cyan-500 to-teal-600',
    random_wheel:      'from-amber-500 to-yellow-600',
    open_box:          'from-rose-500 to-pink-600',
    complete_sentence: 'from-violet-500 to-purple-600',
    hangman:           'from-gray-600 to-slate-700',
    reorder:           'from-teal-500 to-cyan-600',
    group_sort:        'from-orange-500 to-red-500',
    whack_mole:        'from-lime-500 to-green-600',
    word_search:       'from-blue-500 to-indigo-600',
};

function getGrad(code) { return gradients[code] ?? 'from-indigo-500 to-purple-600'; }
</script>

<template>
    <Head title="Shablonlar" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-base font-semibold text-slate-800">O'yin shablonlari</h1>
                    <p class="text-xs text-slate-400 mt-0.5">{{ templates.length }} ta shablon</p>
                </div>
                <button @click="openCreate"
                    class="flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2 rounded-xl transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Yangi shablon
                </button>
            </div>
        </template>

        <div v-if="loading" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="i in 6" :key="i" class="bg-white rounded-2xl border border-slate-200 animate-pulse h-36"></div>
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div v-for="(tpl, ti) in templates" :key="tpl.id"
                :style="`animation-delay: ${ti * 40}ms`"
                class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-md transition-all duration-200 tpl-appear"
                :class="tpl.status !== 'enabled' ? 'opacity-60' : ''">
                <!-- Card header with illustration -->
                <div :class="['h-20 bg-gradient-to-br flex items-center justify-center p-3 relative overflow-hidden', getGrad(tpl.code)]">
                    <div class="absolute -top-3 -right-3 w-12 h-12 bg-white/10 rounded-full"></div>
                    <GameTypeIllustration :code="tpl.code" class="w-full h-full relative z-10" />
                </div>

                <!-- Body -->
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2 mb-2">
                        <div>
                            <h3 class="font-semibold text-slate-800 text-sm">{{ tpl.name }}</h3>
                            <span class="font-mono text-[11px] text-slate-400">{{ tpl.code }}</span>
                        </div>
                        <span :class="[
                            'shrink-0 inline-flex items-center gap-1 text-xs px-2 py-1 rounded-lg font-medium',
                            tpl.status === 'enabled' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500'
                        ]">
                            <span :class="['w-1.5 h-1.5 rounded-full', tpl.status === 'enabled' ? 'bg-green-500' : 'bg-slate-400']"></span>
                            {{ tpl.status === 'enabled' ? 'Faol' : 'Nofaol' }}
                        </span>
                    </div>
                    <div class="flex items-center gap-2 mb-3">
                        <p class="text-xs text-slate-400">{{ tpl.games_count ?? 0 }} ta o'yin</p>
                        <span v-if="tpl.token_budget_base" class="text-[10px] text-slate-400 bg-slate-100 px-1.5 py-0.5 rounded font-mono">
                            {{ tpl.token_budget_base }}+{{ tpl.token_budget_per_item }}×n
                        </span>
                    </div>

                    <button @click="toggle(tpl.id)"
                        :class="[
                            'w-full text-xs font-semibold py-2 rounded-xl transition',
                            tpl.status === 'enabled'
                                ? 'bg-red-50 hover:bg-red-100 text-red-600 border border-red-200'
                                : 'bg-green-50 hover:bg-green-100 text-green-600 border border-green-200'
                        ]">
                        {{ tpl.status === 'enabled' ? "O'chirish" : 'Yoqish' }}
                    </button>

                    <!-- Budget inline edit -->
                    <div v-if="budgetEditing === tpl.id" class="mt-2 space-y-2 border-t border-slate-100 pt-2">
                        <div class="grid grid-cols-2 gap-2">
                            <div>
                                <label class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide">Base</label>
                                <input v-model.number="budgetForm.token_budget_base" type="number" min="200" max="8000" step="50"
                                    class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-300" />
                            </div>
                            <div>
                                <label class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide">Per item</label>
                                <input v-model.number="budgetForm.token_budget_per_item" type="number" min="10" max="500" step="10"
                                    class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-indigo-300" />
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button @click="saveBudget(tpl.id)" :disabled="savingBudget"
                                class="flex-1 text-xs bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold py-1.5 rounded-lg transition">
                                {{ savingBudget ? '...' : 'Saqlash' }}
                            </button>
                            <button @click="budgetEditing = null"
                                class="text-xs text-slate-400 hover:text-slate-600 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition">
                                ✕
                            </button>
                        </div>
                    </div>
                    <button v-else @click="openBudgetEdit(tpl)"
                        class="w-full mt-2 text-xs text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 py-1.5 rounded-xl transition border border-dashed border-slate-200 hover:border-indigo-200">
                        ⚙ Token budget
                    </button>
                </div>
            </div>
        </div>
    </AdminLayout>

    <!-- Create Template Modal -->
    <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
        leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
        <div v-if="showCreate"
            class="fixed inset-0 z-50 flex items-start justify-center pt-8 px-4 pb-8 bg-black/40 backdrop-blur-sm overflow-y-auto"
            @click.self="showCreate = false">
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
                <!-- Header -->
                <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                    <h3 class="font-bold text-slate-900">Yangi shablon qo'shish</h3>
                    <button @click="showCreate = false" class="text-slate-400 hover:text-slate-600 p-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>

                <div class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Shablon nomi <span class="text-red-500">*</span></label>
                        <input v-model="form.name" @input="autoCode" type="text" placeholder="Masalan: Krossvord"
                            class="w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300"
                            :class="createErrors.name ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="createErrors.name" class="text-xs text-red-500 mt-1">{{ createErrors.name[0] }}</p>
                    </div>

                    <!-- Code -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Kod (slug) <span class="text-red-500">*</span>
                            <span class="text-slate-400 font-normal text-xs ml-1">— faqat kichik harf, raqam, pastki chiziq</span>
                        </label>
                        <input v-model="form.code" type="text" placeholder="krossvord"
                            class="w-full border rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300"
                            :class="createErrors.code ? 'border-red-400' : 'border-slate-200'" />
                        <p v-if="createErrors.code" class="text-xs text-red-500 mt-1">{{ createErrors.code[0] }}</p>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Type -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">Tur <span class="text-red-500">*</span></label>
                            <select v-model="form.type"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                                <option v-for="opt in typeOptions" :key="opt.value" :value="opt.value">{{ opt.label }}</option>
                            </select>
                        </div>

                        <!-- Renderer Component -->
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Renderer komponenti <span class="text-red-500">*</span>
                            </label>
                            <input v-model="form.renderer_component" type="text" placeholder="KrossvordRenderer"
                                class="w-full border rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300"
                                :class="createErrors.renderer_component ? 'border-red-400' : 'border-slate-200'" />
                            <p v-if="createErrors.renderer_component" class="text-xs text-red-500 mt-1">{{ createErrors.renderer_component[0] }}</p>
                        </div>
                    </div>

                    <!-- Output Schema -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Output schema (JSON) <span class="text-red-500">*</span>
                        </label>
                        <p class="text-xs text-slate-400 mb-1.5">Misol: <code class="bg-slate-100 px-1 py-0.5 rounded text-xs">{{ outputSchemaHint }}</code></p>
                        <textarea v-model="form.output_schema" rows="3" placeholder='{"required":["title","items"],"item_fields":["id","word","hint"]}'
                            class="w-full border rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"
                            :class="createErrors.output_schema ? 'border-red-400' : 'border-slate-200'"></textarea>
                        <p v-if="createErrors.output_schema" class="text-xs text-red-500 mt-1">{{ createErrors.output_schema[0] }}</p>
                    </div>

                    <!-- Prompt Text -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Boshlang'ich prompt <span class="text-red-500">*</span>
                        </label>
                        <div class="text-xs text-amber-700 bg-amber-50 border border-amber-100 rounded-xl px-3 py-2 mb-2">
                            <strong>Placeholder'lar:</strong>
                            <code v-pre class="mx-1">{{topic}}</code>,
                            <code v-pre class="mx-1">{{language}}</code>,
                            <code v-pre class="mx-1">{{students_count}}</code>
                            — bularni albatta qo'shing.
                        </div>
                        <textarea v-model="form.prompt_text" rows="6"
                            placeholder="Generate a crossword about {{topic}} in {{language}} with {{students_count}} words..."
                            class="w-full border rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none"
                            :class="createErrors.prompt_text ? 'border-red-400' : 'border-slate-200'"></textarea>
                        <div class="flex justify-between mt-1">
                            <p v-if="createErrors.prompt_text" class="text-xs text-red-500">{{ createErrors.prompt_text[0] }}</p>
                            <span class="text-xs text-slate-400 ml-auto">{{ form.prompt_text.length }} ta belgi</span>
                        </div>
                    </div>

                    <!-- Token Budget -->
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Token budget (base)
                                <span class="text-slate-400 font-normal text-xs ml-1">— minimal token miqdori</span>
                            </label>
                            <input v-model.number="form.token_budget_base" type="number" min="200" max="4000" step="50"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-1">
                                Token budget (har item)
                                <span class="text-slate-400 font-normal text-xs ml-1">— item boshiga qo'shimcha</span>
                            </label>
                            <input v-model.number="form.token_budget_per_item" type="number" min="10" max="300" step="10"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300" />
                        </div>
                    </div>
                    <p class="text-xs text-slate-400 -mt-2">
                        Jami max tokens = base + per_item × items_count. Misol: 800 + 60×10 = 1400 token.
                    </p>

                    <!-- Info note -->
                    <div class="bg-blue-50 border border-blue-100 rounded-xl px-4 py-3 text-xs text-blue-700">
                        <strong>Eslatma:</strong> Yangi shablon <strong>o'chirilgan</strong> holatda yaratiladi.
                        Renderer komponentini (<code class="font-mono">resources/js/Components/Renderers/</code>) qo'shib,
                        <code class="font-mono">Games/Play.vue</code> va <code class="font-mono">Session/Show.vue</code> ga
                        ulang, so'ng admin paneldan faollashtiring.
                    </div>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-100">
                    <button @click="showCreate = false"
                        class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">
                        Bekor qilish
                    </button>
                    <button @click="submitCreate" :disabled="creating || !form.name || !form.code || !form.prompt_text"
                        class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold transition">
                        {{ creating ? 'Yaratilmoqda...' : 'Yaratish' }}
                    </button>
                </div>
            </div>
        </div>
    </Transition>
</template>

<style scoped>
.tpl-appear { animation: tplSlideIn 0.4s ease both; }
@keyframes tplSlideIn {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
