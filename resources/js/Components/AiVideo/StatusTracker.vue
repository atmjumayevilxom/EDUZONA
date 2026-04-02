<script setup>
import { ref, onMounted, onUnmounted, computed } from 'vue';
import axios from 'axios';

const props  = defineProps({ requestId: { type: Number, required: true } });
const emit   = defineEmits(['completed', 'failed']);

const data    = ref(null);
const loading = ref(true);
let pollTimer = null;

const steps = [
    { key: 'pending',          label: "So'rov qabul qilindi",       icon: '📋' },
    { key: 'solving',          label: 'GPT masalani yechmoqda',     icon: '🧠' },
    { key: 'building_prompt',  label: 'Video prompti qurilmoqda',   icon: '✍️'  },
    { key: 'generating',       label: 'Video yaratilmoqda',         icon: '🎬' },
    { key: 'completed',        label: 'Video tayyor!',              icon: '✅' },
];

const currentStepIndex = computed(() => {
    if (!data.value) return 0;
    const idx = steps.findIndex(s => s.key === data.value.status);
    return idx === -1 ? 0 : idx;
});

const isFailed    = computed(() => data.value?.status === 'failed');
const isCompleted = computed(() => data.value?.status === 'completed');
const isTerminal  = computed(() => isFailed.value || isCompleted.value);

async function fetchStatus() {
    try {
        const { data: res } = await axios.get(`/api/ai-video/${props.requestId}/status`);
        data.value  = res;
        loading.value = false;

        if (res.status === 'completed') {
            emit('completed', res);
            stopPolling();
        } else if (res.status === 'failed') {
            emit('failed', res);
            stopPolling();
        }
    } catch {
        loading.value = false;
    }
}

function startPolling() {
    fetchStatus();
    pollTimer = setInterval(fetchStatus, 3000);
}

function stopPolling() {
    if (pollTimer) {
        clearInterval(pollTimer);
        pollTimer = null;
    }
}

onMounted(startPolling);
onUnmounted(stopPolling);
</script>

<template>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
        <!-- Loading shimmer -->
        <div v-if="loading" class="space-y-3 animate-pulse">
            <div class="h-4 bg-slate-100 rounded w-1/2"></div>
            <div class="h-3 bg-slate-100 rounded w-1/3"></div>
        </div>

        <!-- Xato holati -->
        <div v-else-if="isFailed" class="text-center py-4">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 text-3xl">
                ❌
            </div>
            <h3 class="font-bold text-slate-800 mb-1">Xato yuz berdi</h3>
            <p class="text-sm text-red-600 bg-red-50 rounded-xl px-4 py-2 max-w-sm mx-auto">
                {{ data?.error || "Noma'lum xato. Iltimos qayta urining." }}
            </p>
        </div>

        <!-- Jarayon holati -->
        <div v-else>
            <div class="flex items-center justify-between mb-6">
                <h3 class="font-bold text-slate-800">Video tayyorlanmoqda</h3>
                <span class="text-xs text-slate-400 bg-slate-50 px-3 py-1 rounded-full">
                    Avtomatik yangilanadi...
                </span>
            </div>

            <!-- Qadamlar -->
            <div class="space-y-3">
                <div
                    v-for="(step, idx) in steps" :key="step.key"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-xl transition-all',
                        idx < currentStepIndex
                            ? 'bg-green-50 border border-green-100'
                            : idx === currentStepIndex && !isCompleted
                                ? 'bg-indigo-50 border border-indigo-200'
                                : isCompleted && idx === currentStepIndex
                                    ? 'bg-green-50 border border-green-200'
                                    : 'bg-slate-50 border border-slate-100 opacity-40'
                    ]">
                    <!-- Icon / Spinner -->
                    <div class="shrink-0 w-8 h-8 flex items-center justify-center text-lg">
                        <svg
                            v-if="idx === currentStepIndex && !isTerminal"
                            class="w-5 h-5 text-indigo-600 animate-spin"
                            fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10"
                                stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor"
                                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/>
                        </svg>
                        <span v-else>{{ step.icon }}</span>
                    </div>

                    <!-- Label -->
                    <div class="flex-1">
                        <span :class="[
                            'text-sm font-medium',
                            idx < currentStepIndex || (isCompleted && idx === currentStepIndex)
                                ? 'text-green-700'
                                : idx === currentStepIndex && !isTerminal
                                    ? 'text-indigo-700'
                                    : 'text-slate-400'
                        ]">
                            {{ step.label }}
                        </span>
                    </div>

                    <!-- Check -->
                    <svg
                        v-if="idx < currentStepIndex || (isCompleted && idx === currentStepIndex)"
                        class="w-4 h-4 text-green-500 shrink-0"
                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
            </div>

            <!-- Progress bar -->
            <div class="mt-5 bg-slate-100 rounded-full h-2 overflow-hidden">
                <div
                    class="h-full bg-gradient-to-r from-violet-500 to-indigo-500 rounded-full transition-all duration-700 ease-out"
                    :style="{ width: `${(currentStepIndex / (steps.length - 1)) * 100}%` }">
                </div>
            </div>
            <p class="text-xs text-slate-400 text-right mt-1">
                {{ Math.round((currentStepIndex / (steps.length - 1)) * 100) }}%
            </p>
        </div>
    </div>
</template>
