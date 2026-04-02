<script setup>
import { ref, onMounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import HistoryCard from '@/Components/AiVideo/HistoryCard.vue';
import axios from 'axios';

const items   = ref([]);
const loading = ref(true);
const selected = ref(null);

async function load() {
    loading.value = true;
    try {
        const { data } = await axios.get('/api/ai-video/history');
        items.value = data.data;
    } finally {
        loading.value = false;
    }
}

async function openItem(item) {
    if (item.status === 'completed' || item.status === 'failed') {
        const { data } = await axios.get(`/api/ai-video/${item.id}`);
        selected.value = data;
    } else {
        // Hali tugallanmagan — asosiy sahifaga yo'naltirish
        router.visit('/ai-video');
    }
}

function closeDetail() {
    selected.value = null;
}

onMounted(load);
</script>

<template>
    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <Link href="/ai-video"
                    class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </Link>
                <h1 class="text-base font-bold text-slate-800">Video tarix</h1>
            </div>
        </template>

        <div class="max-w-3xl mx-auto">

            <!-- Loading -->
            <div v-if="loading" class="space-y-3">
                <div v-for="i in 5" :key="i"
                    class="bg-white rounded-xl border border-slate-100 p-4 animate-pulse">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-slate-100 rounded-xl"></div>
                        <div class="flex-1 space-y-2">
                            <div class="h-3 bg-slate-100 rounded w-1/2"></div>
                            <div class="h-2.5 bg-slate-100 rounded w-1/4"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bo'sh holat -->
            <div v-else-if="!items.length"
                class="bg-white rounded-2xl border border-slate-100 shadow-sm p-12 text-center">
                <div class="text-5xl mb-4">🎬</div>
                <h3 class="font-bold text-slate-700 text-lg mb-2">Hali video yo'q</h3>
                <p class="text-sm text-slate-400 mb-6">Birinchi video darsni yarating!</p>
                <Link href="/ai-video"
                    class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-medium text-sm hover:bg-indigo-700 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Video yaratish
                </Link>
            </div>

            <!-- Ro'yxat -->
            <div v-else class="space-y-2">
                <HistoryCard
                    v-for="item in items" :key="item.id"
                    :item="item"
                    @open="openItem"
                />
            </div>

        </div>

        <!-- Detail modal -->
        <Transition
            enter-active-class="transition-all duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0">
            <div v-if="selected"
                class="fixed inset-0 z-50 bg-black/40 backdrop-blur-sm flex items-end sm:items-center justify-center p-4"
                @click.self="closeDetail">

                <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[85vh] overflow-y-auto">
                    <!-- Modal header -->
                    <div class="sticky top-0 bg-white flex items-center justify-between px-5 py-4 border-b border-slate-100 z-10">
                        <div>
                            <h3 class="font-bold text-slate-800 text-sm">{{ selected.topic }}</h3>
                            <p class="text-xs text-slate-400">{{ selected.subject_label }}</p>
                        </div>
                        <button @click="closeDetail"
                            class="w-7 h-7 flex items-center justify-center rounded-lg hover:bg-slate-100 transition text-slate-400">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal kontent -->
                    <div class="p-5 space-y-4">
                        <!-- Video -->
                        <div v-if="selected.video_url" class="bg-black rounded-xl overflow-hidden aspect-video">
                            <video :src="selected.video_url" controls class="w-full h-full"></video>
                        </div>

                        <!-- Masala -->
                        <div class="bg-slate-50 rounded-xl p-4">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Masala</div>
                            <p class="text-sm text-slate-700 leading-relaxed">{{ selected.problem_text }}</p>
                        </div>

                        <!-- Yechim qadamlari -->
                        <div v-if="selected.solution_json?.steps?.length" class="space-y-3">
                            <div class="text-xs font-bold text-slate-500 uppercase tracking-wider">Yechim</div>
                            <div
                                v-for="step in selected.solution_json.steps" :key="step.step"
                                class="flex gap-3">
                                <div class="w-6 h-6 bg-indigo-100 text-indigo-700 rounded-full flex items-center justify-center text-xs font-bold shrink-0 mt-0.5">
                                    {{ step.step }}
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-800">{{ step.title }}</p>
                                    <p class="text-xs text-slate-600 mt-0.5">{{ step.explanation }}</p>
                                    <div v-if="step.formula"
                                        class="mt-1.5 bg-slate-800 text-green-400 text-xs font-mono px-2 py-1.5 rounded">
                                        {{ step.formula }}
                                    </div>
                                </div>
                            </div>
                            <!-- Yakuniy javob -->
                            <div v-if="selected.solution_json.final_answer"
                                class="bg-green-50 border border-green-200 rounded-xl px-4 py-3 text-sm font-semibold text-green-800">
                                ✅ {{ selected.solution_json.final_answer }}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </Transition>
    </AuthenticatedLayout>
</template>
