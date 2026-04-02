<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const templates = ref([]);
const loading = ref(true);
const selected = ref(null);   // { prompt, template }
const editText = ref('');
const saving = ref(false);
const activating = ref(null);
const saved = ref(false);
const expanded = ref({});     // { template_id: bool }

// New version modal
const newVersion = ref(null); // { template }
const newVersionText = ref('');
const addingVersion = ref(false);

// Preview (sinov) state — shared for both edit & new version modals
const previewOpen = ref(false);
const previewTopic = ref('Qon aylanish tizimi');
const previewLang = ref('uz');
const previewCount = ref(10);

// Computed: fill placeholders in the currently active textarea text
function fillPreview(text) {
    return text
        .replace(/\{\{topic\}\}/g, previewTopic.value || '...')
        .replace(/\{\{language\}\}/g, previewLang.value)
        .replace(/\{\{students_count\}\}/g, String(previewCount.value))
        .replace(/\{\{grade\}\}/g, '7');
}

const activePreviewText = computed(() => {
    const src = newVersion.value ? newVersionText.value : editText.value;
    return fillPreview(src);
});

onMounted(async () => {
    await load();
});

async function load() {
    loading.value = true;
    const res = await axios.get('/api/admin/prompts');
    templates.value = res.data.data;
    loading.value = false;
}

function toggleExpand(tplId) {
    expanded.value[tplId] = !expanded.value[tplId];
}

async function openEditor(prompt, tpl) {
    const res = await axios.get(`/api/admin/prompts/${prompt.id}`);
    selected.value = { prompt: res.data.data, template: tpl };
    editText.value = res.data.data.prompt_text;
    saved.value = false;
    previewOpen.value = false;
}

function closeEditor() {
    selected.value = null;
    editText.value = '';
    previewOpen.value = false;
}

async function savePrompt() {
    if (!selected.value) return;
    saving.value = true;
    try {
        await axios.patch(`/api/admin/prompts/${selected.value.prompt.id}`, {
            prompt_text: editText.value,
        });
        saved.value = true;
        setTimeout(() => saved.value = false, 3000);
        const tpl = templates.value.find(t => t.id === selected.value.template.id);
        if (tpl) {
            const pv = tpl.prompt_versions.find(p => p.id === selected.value.prompt.id);
            if (pv) pv.prompt_text = editText.value;
        }
    } finally {
        saving.value = false;
    }
}

async function activatePrompt(promptId, tplId) {
    activating.value = promptId;
    try {
        await axios.patch(`/api/admin/prompts/${promptId}/activate`);
        const tpl = templates.value.find(t => t.id === tplId);
        if (tpl) {
            tpl.prompt_versions.forEach(p => {
                p.status = p.id === promptId ? 'active' : 'inactive';
            });
        }
    } finally {
        activating.value = null;
    }
}

function openNewVersion(tpl) {
    const active = tpl.prompt_versions?.find(p => p.status === 'active');
    newVersionText.value = active ? active.prompt_text ?? '' : '';
    newVersion.value = { template: tpl };
    previewOpen.value = false;
}

async function submitNewVersion() {
    if (!newVersion.value) return;
    addingVersion.value = true;
    try {
        const res = await axios.post('/api/admin/prompts', {
            template_id: newVersion.value.template.id,
            prompt_text: newVersionText.value,
        });
        const tpl = templates.value.find(t => t.id === newVersion.value.template.id);
        if (tpl) {
            if (!tpl.prompt_versions) tpl.prompt_versions = [];
            tpl.prompt_versions.unshift(res.data.data);
        }
        newVersion.value = null;
        newVersionText.value = '';
        previewOpen.value = false;
    } finally {
        addingVersion.value = false;
    }
}
</script>

<template>
    <Head title="Prompt Muharriri" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">Prompt Muharriri</h1>
                <p class="text-xs text-slate-400 mt-0.5">Har bir template uchun alohida prompt versiyalari</p>
            </div>
        </template>

        <div class="max-w-4xl">
            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-12">
                <div class="w-6 h-6 border-2 border-indigo-500 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <div v-else class="space-y-2">
                <div v-for="(tpl, ti) in templates" :key="tpl.id"
                    :style="`animation-delay: ${ti * 50}ms`"
                    class="bg-white rounded-2xl border border-slate-200 overflow-hidden tpl-appear">

                    <!-- Template header (click to expand) -->
                    <button @click="toggleExpand(tpl.id)"
                        class="w-full flex items-center gap-3 px-5 py-4 hover:bg-slate-50 transition text-left">
                        <div class="flex-1 flex items-center gap-3">
                            <span class="text-xs font-mono bg-slate-100 text-slate-600 px-2 py-1 rounded-lg">{{ tpl.code }}</span>
                            <span class="font-medium text-slate-800 text-sm">{{ tpl.name }}</span>
                            <span :class="['text-xs px-2 py-0.5 rounded-full font-medium', tpl.status === 'enabled' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-500']">
                                {{ tpl.status === 'enabled' ? 'Faol' : "O'chirilgan" }}
                            </span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400">{{ tpl.prompt_versions?.length ?? 0 }} versiya</span>
                            <svg :class="['w-4 h-4 text-slate-400 transition-transform', expanded[tpl.id] ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </div>
                    </button>

                    <!-- Versions list -->
                    <div v-if="expanded[tpl.id]" class="border-t border-slate-100">
                        <div v-if="!tpl.prompt_versions?.length" class="px-5 py-4 text-sm text-slate-400">
                            Bu template uchun prompt versiyasi yo'q.
                        </div>
                        <div v-for="(pv, pi) in tpl.prompt_versions" :key="pv.id"
                            :style="`animation-delay: ${pi * 40}ms`"
                            class="flex items-center gap-3 px-5 py-3 border-b border-slate-50 last:border-0 hover:bg-slate-50/50 pv-appear">
                            <!-- Status dot -->
                            <span :class="['w-2 h-2 rounded-full shrink-0', pv.status === 'active' ? 'bg-green-500' : 'bg-slate-300']"></span>

                            <!-- Version badge -->
                            <span class="text-xs font-mono font-bold text-slate-600 w-12">{{ pv.version }}</span>

                            <!-- Created at -->
                            <span class="text-xs text-slate-400 flex-1">{{ pv.created_at?.slice(0,10) }}</span>

                            <!-- Active badge -->
                            <span v-if="pv.status === 'active'"
                                class="text-xs bg-green-100 text-green-700 font-semibold px-2 py-0.5 rounded-full">
                                Faol
                            </span>

                            <!-- Actions -->
                            <div class="flex items-center gap-2">
                                <button
                                    v-if="pv.status !== 'active'"
                                    @click="activatePrompt(pv.id, tpl.id)"
                                    :disabled="activating === pv.id"
                                    class="text-xs bg-indigo-50 hover:bg-indigo-100 disabled:opacity-50 text-indigo-700 font-semibold px-3 py-1.5 rounded-lg transition">
                                    {{ activating === pv.id ? '...' : 'Faollashtirish' }}
                                </button>
                                <button @click="openEditor(pv, tpl)"
                                    class="text-xs bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-3 py-1.5 rounded-lg transition">
                                    Tahrirlash
                                </button>
                            </div>
                        </div>

                        <!-- Add new version row -->
                        <div class="px-5 py-3 bg-slate-50/50 border-t border-slate-100">
                            <button @click="openNewVersion(tpl)"
                                class="flex items-center gap-1.5 text-xs text-indigo-600 hover:text-indigo-800 font-semibold transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Yangi versiya qo'shish
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Edit modal -->
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="selected" class="fixed inset-0 z-50 flex items-start justify-center pt-6 px-4 pb-6 bg-black/40 backdrop-blur-sm overflow-y-auto"
                @click.self="closeEditor">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col">
                    <!-- Modal header -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-900">Prompt tahrirlash</h3>
                            <p class="text-xs text-slate-400 mt-0.5">
                                {{ selected.template.name }} — {{ selected.prompt.version }}
                                <span :class="['ml-2 font-semibold', selected.prompt.status === 'active' ? 'text-green-600' : 'text-slate-400']">
                                    {{ selected.prompt.status === 'active' ? '● Faol' : '○ Nofaol' }}
                                </span>
                            </p>
                        </div>
                        <div class="flex items-center gap-2">
                            <!-- Preview toggle -->
                            <button @click="previewOpen = !previewOpen"
                                :class="['flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition',
                                    previewOpen ? 'bg-violet-100 text-violet-700 border-violet-200' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200']">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Sinov
                            </button>
                            <button @click="closeEditor" class="text-slate-400 hover:text-slate-600 p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Info bar -->
                    <div class="px-6 py-3 bg-amber-50 border-b border-amber-100 text-xs text-amber-700">
                        <strong>Diqqat:</strong> <code v-pre>{{topic}}</code>, <code v-pre>{{language}}</code>, <code v-pre>{{students_count}}</code> placeholder'larini o'zgartirmang.
                    </div>

                    <!-- Preview panel -->
                    <div v-if="previewOpen" class="border-b border-slate-100 bg-violet-50/50 px-6 py-4 space-y-3">
                        <p class="text-xs font-semibold text-violet-700">Sinov parametrlari:</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Mavzu</label>
                                <input v-model="previewTopic" type="text"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-violet-300" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Til</label>
                                <select v-model="previewLang" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-violet-300">
                                    <option value="uz">O'zbek</option>
                                    <option value="en">English</option>
                                    <option value="ru">Русский</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Miqdor</label>
                                <input v-model.number="previewCount" type="number" min="1" max="30"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-violet-300" />
                            </div>
                        </div>
                        <pre class="bg-white border border-violet-200 rounded-xl px-4 py-3 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap max-h-48 overflow-y-auto font-mono">{{ activePreviewText }}</pre>
                    </div>

                    <!-- Textarea -->
                    <div class="p-4">
                        <textarea
                            v-model="editText"
                            class="w-full min-h-[350px] border border-slate-200 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none bg-slate-50"
                            spellcheck="false"
                        ></textarea>
                    </div>

                    <!-- Footer -->
                    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                        <span class="text-xs text-slate-400">{{ editText.length }} ta belgi</span>
                        <div class="flex items-center gap-3">
                            <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" leave-to-class="opacity-0">
                                <span v-if="saved" class="text-sm text-green-600 font-medium">✓ Saqlandi</span>
                            </Transition>
                            <button @click="closeEditor" class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">
                                Yopish
                            </button>
                            <button @click="savePrompt" :disabled="saving || editText.length < 50"
                                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold transition">
                                {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>

        <!-- New Version modal -->
        <Transition enter-active-class="transition duration-150" enter-from-class="opacity-0" enter-to-class="opacity-100"
            leave-active-class="transition duration-100" leave-from-class="opacity-100" leave-to-class="opacity-0">
            <div v-if="newVersion" class="fixed inset-0 z-50 flex items-start justify-center pt-6 px-4 pb-6 bg-black/40 backdrop-blur-sm overflow-y-auto"
                @click.self="newVersion = null">
                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl flex flex-col">
                    <div class="flex items-center justify-between px-6 py-4 border-b border-slate-100">
                        <div>
                            <h3 class="font-bold text-slate-900">Yangi prompt versiyasi</h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ newVersion.template.name }}</p>
                        </div>
                        <div class="flex items-center gap-2">
                            <button @click="previewOpen = !previewOpen"
                                :class="['flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-lg border transition',
                                    previewOpen ? 'bg-violet-100 text-violet-700 border-violet-200' : 'bg-slate-100 text-slate-600 border-slate-200 hover:bg-slate-200']">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Sinov
                            </button>
                            <button @click="newVersion = null" class="text-slate-400 hover:text-slate-600 p-1">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="px-6 py-3 bg-blue-50 border-b border-blue-100 text-xs text-blue-700">
                        Yangi versiya <strong>nofaol</strong> holatda yaratiladi. Tekshirib bo'lgach "Faollashtirish" tugmasidan foydalaning.
                        Placeholder'lar: <code v-pre>{{topic}}</code>, <code v-pre>{{language}}</code>, <code v-pre>{{students_count}}</code>
                    </div>

                    <!-- Preview panel -->
                    <div v-if="previewOpen" class="border-b border-slate-100 bg-violet-50/50 px-6 py-4 space-y-3">
                        <p class="text-xs font-semibold text-violet-700">Sinov parametrlari:</p>
                        <div class="grid grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Mavzu</label>
                                <input v-model="previewTopic" type="text"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-violet-300" />
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Til</label>
                                <select v-model="previewLang" class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-violet-300">
                                    <option value="uz">O'zbek</option>
                                    <option value="en">English</option>
                                    <option value="ru">Русский</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs text-slate-500 mb-1">Miqdor</label>
                                <input v-model.number="previewCount" type="number" min="1" max="30"
                                    class="w-full border border-slate-200 rounded-lg px-3 py-1.5 text-xs focus:outline-none focus:ring-1 focus:ring-violet-300" />
                            </div>
                        </div>
                        <pre class="bg-white border border-violet-200 rounded-xl px-4 py-3 text-xs text-slate-700 leading-relaxed whitespace-pre-wrap max-h-48 overflow-y-auto font-mono">{{ activePreviewText }}</pre>
                    </div>

                    <div class="p-4">
                        <textarea
                            v-model="newVersionText"
                            class="w-full min-h-[350px] border border-slate-200 rounded-xl px-4 py-3 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none bg-slate-50"
                            spellcheck="false"
                        ></textarea>
                    </div>

                    <div class="flex items-center justify-between px-6 py-4 border-t border-slate-100">
                        <span class="text-xs text-slate-400">{{ newVersionText.length }} ta belgi</span>
                        <div class="flex items-center gap-3">
                            <button @click="newVersion = null" class="px-4 py-2 rounded-xl border border-slate-200 text-sm text-slate-600 hover:bg-slate-50 transition">
                                Bekor qilish
                            </button>
                            <button @click="submitNewVersion" :disabled="addingVersion || newVersionText.length < 50"
                                class="px-5 py-2 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-semibold transition">
                                {{ addingVersion ? 'Saqlanmoqda...' : "Versiya qo'shish" }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AdminLayout>
</template>

<style scoped>
.tpl-appear { animation: tplFadeIn 0.35s ease both; }
@keyframes tplFadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
.pv-appear { animation: pvFadeIn 0.3s ease both; }
@keyframes pvFadeIn {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
