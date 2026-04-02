<script setup>
import { ref, onMounted } from 'vue';
import { Head } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const form = ref({ model: '', daily_request_limit: 10, daily_token_budget: 50000, max_retries: 2, max_tokens: 2000 });
const saved = ref(false);
const loading = ref(true);

onMounted(async () => {
    const res = await axios.get('/api/admin/ai-usage');
    const settings = res.data.data.settings;
    form.value = {
        model: settings.model ?? 'gpt-4o-mini',
        daily_request_limit: parseInt(settings.daily_request_limit ?? 10),
        daily_token_budget: parseInt(settings.daily_token_budget ?? 50000),
        max_retries: parseInt(settings.max_retries ?? 2),
        max_tokens: parseInt(settings.max_tokens ?? 2000),
    };
    loading.value = false;
});

async function save() {
    await axios.patch('/api/admin/ai-settings', form.value);
    saved.value = true;
    setTimeout(() => saved.value = false, 2500);
}

const fields = [
    {
        key: 'model',
        label: 'AI Model',
        desc: "OpenAI model nomi (masalan: gpt-4o-mini, gpt-4o)",
        type: 'text',
        icon: 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z',
    },
    {
        key: 'daily_request_limit',
        label: "Kunlik so'rov limiti",
        desc: "Bir foydalanuvchi kunda nechta o'yin yarata oladi",
        type: 'number', min: 1, max: 100,
        icon: 'M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z',
    },
    {
        key: 'max_tokens',
        label: 'Maksimal token (javob)',
        desc: "⚠️ ESKIRGAN — tizim endi har bir template uchun avtomatik hisoblaydi. Bu maydon e'tiborga olinmaydi.",
        type: 'number', min: 100, max: 8000,
        icon: 'M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z',
        deprecated: true,
    },
    {
        key: 'max_retries',
        label: 'Qayta urinish soni',
        desc: "Xato bo'lganda necha marta qayta urinish",
        type: 'number', min: 0, max: 5,
        icon: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15',
    },
];
</script>

<template>
    <Head title="AI Sozlamalar" />
    <AdminLayout>
        <template #header>
            <div>
                <h1 class="text-base font-semibold text-slate-800">AI Sozlamalar</h1>
                <p class="text-xs text-slate-400 mt-0.5">OpenAI API konfiguratsiyasi</p>
            </div>
        </template>

        <div class="max-w-2xl space-y-4">
            <!-- Loading -->
            <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 p-8 flex justify-center">
                <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <form v-else @submit.prevent="save" class="space-y-4">
                <!-- Info banner -->
                <div class="bg-indigo-50 border border-indigo-200 rounded-2xl px-5 py-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-indigo-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="text-sm text-indigo-700">
                        OpenAI API kaliti <code class="bg-indigo-100 px-1.5 py-0.5 rounded text-xs font-mono">.env</code> faylida saqlanadi.
                        Bu yerda faqat limitlar va model tanlanadi.
                    </div>
                </div>

                <!-- Fields -->
                <div class="bg-white rounded-2xl border border-slate-200 divide-y divide-slate-50">
                    <div v-for="(field, fi) in fields" :key="field.key"
                        :style="`animation-delay: ${fi * 60}ms`"
                        :class="['px-5 py-5 flex items-start gap-4 field-appear', field.deprecated ? 'opacity-50' : '']">
                        <div :class="['w-9 h-9 rounded-xl flex items-center justify-center shrink-0 mt-0.5', field.deprecated ? 'bg-amber-100' : 'bg-slate-100']">
                            <svg class="w-4.5 h-4.5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" :d="field.icon"/>
                            </svg>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-0.5">
                                <label class="block text-sm font-semibold text-slate-800">{{ field.label }}</label>
                                <span v-if="field.deprecated"
                                    class="text-[10px] font-bold bg-amber-100 text-amber-700 px-1.5 py-0.5 rounded-full leading-none">
                                    ESKIRGAN
                                </span>
                            </div>
                            <p :class="['text-xs mb-2', field.deprecated ? 'text-amber-600' : 'text-slate-400']">{{ field.desc }}</p>
                            <input
                                v-model="form[field.key]"
                                :type="field.type"
                                :min="field.min"
                                :max="field.max"
                                :disabled="field.deprecated"
                                :class="['w-full border rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:border-indigo-300 font-mono transition',
                                    field.deprecated ? 'bg-amber-50 border-amber-200 text-amber-500 cursor-not-allowed' : 'bg-slate-50 border-slate-200']"
                            />
                        </div>
                    </div>
                </div>

                <!-- Save -->
                <div class="flex items-center gap-3">
                    <button type="submit"
                        class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-sm shadow-indigo-200">
                        Saqlash
                    </button>
                    <Transition enter-active-class="transition-all duration-200" leave-active-class="transition-all duration-200"
                        enter-from-class="opacity-0 translate-y-1" leave-to-class="opacity-0 translate-y-1">
                        <div v-if="saved" class="flex items-center gap-2 text-green-600 text-sm font-medium">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Muvaffaqiyatli saqlandi!
                        </div>
                    </Transition>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>

<style scoped>
.field-appear { animation: fieldFadeIn 0.35s ease both; }
@keyframes fieldFadeIn {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
