<script setup>
import { ref, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import ProblemForm from '@/Components/AiVideo/ProblemForm.vue';
import VideoResult from '@/Components/AiVideo/VideoResult.vue';

// view: 'form' | 'processing' | 'result'
const view      = ref('form');
const result    = ref(null);
const formError = ref(null);

// Video polling
let pollTimer   = null;
const pollCount = ref(0);
const MAX_POLLS = 60; // max 5 min (60 × 5s)

function stopPolling() {
    if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
}

onUnmounted(stopPolling);

async function pollVideoStatus(id) {
    stopPolling();
    pollTimer = setInterval(async () => {
        pollCount.value++;
        if (pollCount.value > MAX_POLLS) {
            stopPolling();
            return;
        }
        try {
            const { data } = await axios.get(`/api/ai-video/${id}/status`);
            if (data.video_url) {
                result.value = { ...result.value, ...data };
                stopPolling();
            } else if (data.status === 'completed' || data.status === 'failed') {
                result.value = { ...result.value, ...data };
                stopPolling();
            }
        } catch {
            // polling xatosi — davom etamiz
        }
    }, 5000);
}

async function onSubmit(formData) {
    view.value      = 'processing';
    result.value    = null;
    formError.value = null;
    stopPolling();
    pollCount.value = 0;

    try {
        const { data } = await axios.post('/api/ai-video/generate', formData);
        result.value = data;
        view.value   = 'result';

        // Agar video hali generating holatida bo'lsa — polling boshlash
        if (data.status === 'generating' && data.id) {
            pollVideoStatus(data.id);
        }
    } catch (err) {
        if (err.response?.status === 422) {
            formError.value = err.response.data.errors ?? {};
            view.value = 'form';
        } else if (err.response?.status === 429) {
            formError.value = { general: err.response.data.message };
            view.value = 'form';
        } else {
            result.value = { error: err.response?.data?.message ?? 'Xato yuz berdi. Qayta urining.' };
            view.value = 'result';
        }
    }
}

function retry() {
    stopPolling();
    view.value      = 'form';
    result.value    = null;
    formError.value = null;
    pollCount.value = 0;
}
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div class="w-8 h-8 bg-gradient-to-br from-violet-500 to-indigo-600 rounded-xl flex items-center justify-center text-white text-sm shadow-sm">
                    🎬
                </div>
                <div>
                    <h1 class="text-base font-bold text-slate-800 leading-tight">AI Video Yechim</h1>
                    <p class="text-xs text-slate-400">Masaladan AI video darsga</p>
                </div>
                <div class="ml-auto">
                    <Link href="/ai-video/history"
                        class="flex items-center gap-1.5 text-xs font-medium text-slate-500 hover:text-indigo-600 bg-slate-50 hover:bg-indigo-50 border border-slate-200 hover:border-indigo-200 px-3 py-1.5 rounded-lg transition">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Tarix
                    </Link>
                </div>
            </div>
        </template>

        <div class="max-w-3xl mx-auto">

            <!-- Banner -->
            <div v-if="view === 'form'"
                class="mb-6 bg-gradient-to-r from-violet-600 to-indigo-600 rounded-2xl p-5 text-white flex items-center gap-4">
                <div class="text-4xl shrink-0">🎓</div>
                <div>
                    <h2 class="font-bold text-lg leading-tight">Har qanday masaladan AI video dars</h2>
                    <p class="text-violet-200 text-sm mt-1">
                        Masalangizni kiriting — AI yechadi va video dars tayyorlaydi
                    </p>
                </div>
            </div>

            <!-- FORMA -->
            <ProblemForm
                v-if="view === 'form'"
                :server-errors="formError"
                @submit="onSubmit"
            />

            <!-- YECHMOQDA (AI solving spinner) -->
            <div v-else-if="view === 'processing'"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center space-y-5">
                <div class="w-20 h-20 mx-auto bg-gradient-to-br from-violet-100 to-indigo-100 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-slate-800 font-bold text-lg">AI masalani yechmoqda...</p>
                    <p class="text-slate-400 text-sm mt-1">Yechim tayyor bo'lgach video yaratiladi (10–30 s)</p>
                </div>
                <div class="flex justify-center gap-1.5">
                    <span v-for="i in 3" :key="i"
                        class="w-2 h-2 bg-indigo-400 rounded-full animate-bounce"
                        :style="{ animationDelay: `${(i-1) * 150}ms` }"></span>
                </div>
                <button @click="retry"
                    class="text-xs text-slate-400 hover:text-red-500 transition underline underline-offset-2">
                    Bekor qilish
                </button>
            </div>

            <!-- NATIJA -->
            <VideoResult
                v-else-if="view === 'result'"
                :result="result"
                :polling="pollTimer !== null"
                @retry="retry"
            />

        </div>
    </AuthenticatedLayout>
</template>
