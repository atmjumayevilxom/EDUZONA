<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import GameTypeIllustration from '@/Components/GameTypeIllustration.vue';
import { useGameStore } from '@/stores/game';
import { useTemplateMeta } from '@/composables/useTemplateMeta';

const gameStore = useGameStore();
const { tplMeta } = useTemplateMeta();

const form = ref({
    template_id: '',
    topic: '',
    language: 'uz',
    students_count: 10,
    category_id: '',
    difficulty: 'medium',
});

const activeFilter  = ref('all');
const submitting    = ref(false);
const pollingGameId = ref(null);
const queueWarning  = ref(false); // queue worker ishlamayotgan bo'lsa ogohlantirish
let pollingTimer = null;
let pollingTimeout = null;

onMounted(async () => {
    await Promise.all([gameStore.fetchTemplates(), gameStore.fetchCategories()]);

    // Pre-select template from URL param: /games/create?template=quiz_mcq
    const urlTemplate = new URLSearchParams(window.location.search).get('template');
    if (urlTemplate) {
        const found = gameStore.templates.find(t => t.code === urlTemplate);
        if (found) form.value.template_id = found.id;
    }
});

const typeIcon = {
    quiz:   '❓',
    word:   '🔤',
    match:  '🔗',
    puzzle: '🧩',
    memory: '🧠',
    drag:   '🖱️',
};

const typeLabel = {
    quiz:   'Viktorina',
    word:   "So'z o'yini",
    match:  'Moslashtirish',
    puzzle: 'Boshqotirma',
    memory: 'Xotira',
    drag:   'Sudrab tashlash',
};


const filterTypes = computed(() => {
    const types = [...new Set(gameStore.templates.map(t => t.type))];
    return types;
});

const filteredTemplates = computed(() => {
    if (activeFilter.value === 'all') return gameStore.templates;
    return gameStore.templates.filter(t => t.type === activeFilter.value);
});

const step1Ready = computed(() =>
    form.value.topic.trim().length >= 3 &&
    form.value.language &&
    form.value.students_count >= 1
);

const canSubmit = computed(() => step1Ready.value && form.value.template_id && !submitting.value);

async function checkGame(gameId) {
    try {
        const res = await axios.get(`/api/games/${gameId}`);
        const game = res.data.data;
        if (game.status === 'ready') {
            stopPolling();
            router.visit(`/games/${gameId}`);
            return true;
        } else if (game.status === 'error') {
            stopPolling();
            pollingGameId.value = null;
            submitting.value = false;
            gameStore.error = "AI o'yin yaratishda xato yuz berdi. Qayta urinib ko'ring.";
            return true;
        }
    } catch {
        // network error — keep polling
    }
    return false;
}

function startPolling(gameId) {
    pollingGameId.value = gameId;
    // First check after 2s (OpenAI min latency), then every 1s
    setTimeout(async () => {
        if (!pollingTimer) return; // stopped already
        const done = await checkGame(gameId);
        if (!done) {
            pollingTimer = setInterval(() => checkGame(gameId), 1000);
        }
    }, 2000);
    pollingTimer = true; // sentinel so stopPolling knows we started

    // Safety timeout: if still generating after 90s, worker likely died
    pollingTimeout = setTimeout(() => {
        if (pollingGameId.value) {
            stopPolling();
            pollingGameId.value = null;
            submitting.value = false;
            gameStore.error = "O'yin yaratish vaqt tugadi (90 soniya). Iltimos, sahifani yangilab qayta urinib ko'ring. Muammo davom etsa, administrator bilan bog'laning.";
        }
    }, 90000);
}

function stopPolling() {
    if (pollingTimer && pollingTimer !== true) {
        clearInterval(pollingTimer);
    }
    pollingTimer = null;
    if (pollingTimeout) {
        clearTimeout(pollingTimeout);
        pollingTimeout = null;
    }
}

async function checkQueueHealth() {
    try {
        const res = await axios.get('/api/system/health');
        queueWarning.value = !res.data.queue_healthy;
    } catch {
        // network xatoda ogohlantirma ko'rsatmaymiz
    }
}

async function submit() {
    if (!canSubmit.value) return;

    // Avval queue holati tekshiriladi
    await checkQueueHealth();

    submitting.value = true;
    gameStore.error = null;
    try {
        const game = await gameStore.generateGame(form.value);
        if (game.status === 'ready') {
            router.visit(`/games/${game.id}`);
        } else {
            startPolling(game.id);
        }
    } catch {
        submitting.value = false;
    }
}

onUnmounted(stopPolling);

const languageOptions = [
    { value: 'uz', label: "O'zbek", flag: '🇺🇿' },
    { value: 'en', label: 'English', flag: '🇬🇧' },
    { value: 'ru', label: 'Русский', flag: '🇷🇺' },
];

const GEN_STEPS = [
    { icon: '🔍', text: "Mavzu tahlil qilinmoqda..." },
    { icon: '🧠', text: "AI savollar yaratmoqda..." },
    { icon: '✏️',  text: "Javoblar tekshirilmoqda..." },
    { icon: '🎨', text: "O'yin formatlanmoqda..." },
    { icon: '✅', text: "Deyarli tayyor!" },
];
const genStep = ref(0);
let genStepTimer = null;

watch(pollingGameId, (id) => {
    if (id) {
        genStep.value = 0;
        clearInterval(genStepTimer);
        genStepTimer = setInterval(() => {
            if (genStep.value < GEN_STEPS.length - 1) genStep.value++;
            else clearInterval(genStepTimer);
        }, 2800);
    } else {
        clearInterval(genStepTimer);
    }
});
</script>

<template>
    <Head title="Yangi o'yin yaratish" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-slate-800">Yangi o'yin yaratish</h2>
        </template>

        <div class="max-w-3xl mx-auto space-y-6">

            <!-- 1-QISM: Mavzu, Til, Miqdor -->
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                <h3 class="text-base font-semibold text-slate-700 mb-4 flex items-center gap-2">
                    <span class="w-6 h-6 bg-indigo-600 text-white rounded-full text-xs flex items-center justify-center font-bold">1</span>
                    O'yin ma'lumotlarini kiriting
                </h3>

                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Mavzu</label>
                        <input
                            v-model="form.topic"
                            type="text"
                            placeholder="Masalan: Ko'paytirish jadvali, O'zbekiston poytaxti..."
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"
                        />
                        <p v-if="form.topic.length > 0 && form.topic.length < 3" class="text-xs text-red-500 mt-1">
                            Kamida 3 ta harf kiriting
                        </p>
                    </div>

                    <!-- Kategoriya -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">
                            Kategoriya <span class="text-slate-400 font-normal text-xs">(ixtiyoriy)</span>
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button type="button" @click="form.category_id = ''"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-medium border-2 transition',
                                    !form.category_id
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-slate-200 text-slate-500 hover:border-slate-300'
                                ]">
                                Barcha
                            </button>
                            <button
                                v-for="cat in gameStore.categories"
                                :key="cat.id"
                                type="button"
                                @click="form.category_id = cat.id"
                                :class="[
                                    'px-3 py-1.5 rounded-xl text-xs font-medium border-2 transition',
                                    form.category_id === cat.id
                                        ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                        : 'border-slate-200 text-slate-500 hover:border-slate-300'
                                ]">
                                {{ cat.name }}
                            </button>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">Til</label>
                            <div class="flex gap-2">
                                <button
                                    v-for="lang in languageOptions"
                                    :key="lang.value"
                                    type="button"
                                    @click="form.language = lang.value"
                                    :class="[
                                        'flex-1 border-2 rounded-xl py-2 text-xs font-medium transition',
                                        form.language === lang.value
                                            ? 'border-indigo-500 bg-indigo-50 text-indigo-700'
                                            : 'border-slate-200 text-slate-600 hover:border-slate-300'
                                    ]"
                                >
                                    {{ lang.flag }}<br>{{ lang.label }}
                                </button>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-700 mb-2">
                                Savol / So'z soni <span class="text-slate-400 font-normal">(1–30)</span>
                            </label>
                            <div class="flex items-center gap-3">
                                <button type="button"
                                    @click="form.students_count = Math.max(1, form.students_count - 1)"
                                    class="w-10 h-10 rounded-xl border-2 border-slate-200 text-slate-600 text-xl font-bold hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center justify-center">
                                    −
                                </button>
                                <span class="text-2xl font-bold text-indigo-600 w-10 text-center select-none">{{ form.students_count }}</span>
                                <button type="button"
                                    @click="form.students_count = Math.min(30, form.students_count + 1)"
                                    class="w-10 h-10 rounded-xl border-2 border-slate-200 text-slate-600 text-xl font-bold hover:border-indigo-300 hover:bg-indigo-50 transition flex items-center justify-center">
                                    +
                                </button>
                            </div>
                        </div>
                    </div>
                    <!-- Qiyinlik darajasi -->
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">Qiyinlik darajasi</label>
                        <div class="flex gap-2">
                            <button type="button" @click="form.difficulty = 'easy'"
                                :class="['flex-1 border-2 rounded-xl py-2.5 text-xs font-semibold transition flex flex-col items-center gap-0.5',
                                    form.difficulty === 'easy'
                                        ? 'border-green-500 bg-green-50 text-green-700'
                                        : 'border-slate-200 text-slate-500 hover:border-green-300']">
                                <span class="text-base">🟢</span> Oson
                            </button>
                            <button type="button" @click="form.difficulty = 'medium'"
                                :class="['flex-1 border-2 rounded-xl py-2.5 text-xs font-semibold transition flex flex-col items-center gap-0.5',
                                    form.difficulty === 'medium'
                                        ? 'border-amber-500 bg-amber-50 text-amber-700'
                                        : 'border-slate-200 text-slate-500 hover:border-amber-300']">
                                <span class="text-base">🟡</span> O'rtacha
                            </button>
                            <button type="button" @click="form.difficulty = 'hard'"
                                :class="['flex-1 border-2 rounded-xl py-2.5 text-xs font-semibold transition flex flex-col items-center gap-0.5',
                                    form.difficulty === 'hard'
                                        ? 'border-red-500 bg-red-50 text-red-700'
                                        : 'border-slate-200 text-slate-500 hover:border-red-300']">
                                <span class="text-base">🔴</span> Qiyin
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 2-QISM: O'yin turi (1-qism to'ldirilgandan keyin paydo bo'ladi) -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="step1Ready" class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <h3 class="text-base font-semibold text-slate-700 mb-4 flex items-center gap-2">
                        <span class="w-6 h-6 bg-indigo-600 text-white rounded-full text-xs flex items-center justify-center font-bold">2</span>
                        O'yin turini tanlang
                    </h3>

                    <!-- Type filter tabs (3+ tur bo'lganda ko'rsatiladi) -->
                    <div v-if="filterTypes.length >= 3" class="flex gap-2 mb-4 flex-wrap">
                        <button
                            type="button"
                            @click="activeFilter = 'all'"
                            :class="[
                                'px-3 py-1.5 rounded-full text-xs font-medium border transition',
                                activeFilter === 'all'
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'border-slate-200 text-slate-600 hover:border-slate-300'
                            ]"
                        >🗂 Barchasi</button>
                        <button
                            v-for="type in filterTypes"
                            :key="type"
                            type="button"
                            @click="activeFilter = type"
                            :class="[
                                'px-3 py-1.5 rounded-full text-xs font-medium border transition',
                                activeFilter === type
                                    ? 'bg-indigo-600 text-white border-indigo-600'
                                    : 'border-slate-200 text-slate-600 hover:border-slate-300'
                            ]"
                        >{{ typeIcon[type] ?? '🎮' }} {{ typeLabel[type] ?? type }}</button>
                    </div>

                    <!-- Template kartalar -->
                    <div v-if="gameStore.loading" class="text-center py-8 text-slate-400 text-sm">
                        Yuklanmoqda...
                    </div>
                    <div v-else class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        <button
                            v-for="(tpl, ti) in filteredTemplates"
                            :key="tpl.id"
                            :style="`animation-delay: ${ti * 45}ms`"
                            type="button"
                            @click="form.template_id = tpl.id"
                            :class="[
                                'relative border-2 rounded-2xl overflow-hidden text-left transition-all duration-150 group tpl-card',
                                form.template_id === tpl.id
                                    ? 'border-indigo-500 shadow-lg shadow-indigo-100'
                                    : 'border-slate-200 hover:border-indigo-300 hover:shadow-md bg-white'
                            ]"
                        >
                            <!-- Gradient header -->
                            <div :class="['h-16 bg-gradient-to-br flex items-center justify-center p-2 relative overflow-hidden', tplMeta(tpl.code).color]">
                                <div class="absolute -top-2 -right-2 w-10 h-10 bg-white/20 rounded-full"></div>
                                <GameTypeIllustration :code="tpl.code" class="w-full h-full relative z-10" />
                                <!-- Check badge -->
                                <div v-if="form.template_id === tpl.id"
                                    class="absolute top-1.5 right-1.5 w-5 h-5 bg-white rounded-full flex items-center justify-center shadow">
                                    <svg class="w-3 h-3 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                </div>
                            </div>
                            <!-- Text body -->
                            <div class="p-3 bg-white">
                                <div class="font-semibold text-slate-800 text-sm leading-tight">{{ tpl.name }}</div>
                                <div class="text-xs text-slate-400 mt-0.5 leading-tight line-clamp-2">{{ tplMeta(tpl.code).desc || (typeLabel[tpl.type] ?? tpl.type) }}</div>
                            </div>
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Queue ogohlantirish -->
            <div v-if="queueWarning && !submitting"
                class="bg-amber-50 border border-amber-200 text-amber-800 text-sm rounded-xl px-4 py-3 flex items-start gap-2">
                <span class="shrink-0 text-base">⚠️</span>
                <div>
                    <span class="font-semibold">Tizim hozir sekin ishlayapti.</span>
                    O'yin yaratish odatdagidan ko'proq vaqt olishi mumkin. Bir necha daqiqadan so'ng qayta urinib ko'ring yoki administrator bilan bog'laning.
                </div>
            </div>

            <!-- Xato -->
            <div v-if="gameStore.error" class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-xl px-4 py-3">
                ⚠️ {{ gameStore.error }}
            </div>

            <!-- Submit tugmasi (tur tanlanganda paydo bo'ladi) -->
            <Transition
                enter-active-class="transition-all duration-200 ease-out"
                enter-from-class="opacity-0 translate-y-2"
                enter-to-class="opacity-100 translate-y-0"
            >
                <button
                    v-if="step1Ready && form.template_id"
                    type="button"
                    @click="submit"
                    :disabled="!canSubmit"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-semibold py-4 rounded-2xl transition-all text-base shadow-sm"
                >
                    <span v-if="submitting" class="flex items-center justify-center gap-2">
                        <svg class="animate-spin w-5 h-5" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/>
                        </svg>
                        <span v-if="pollingGameId">AI yaratmoqda... kutib turing</span>
                        <span v-else>Yuborilmoqda...</span>
                    </span>
                    <span v-else>🚀 O'yin yaratish</span>
                </button>
            </Transition>

        </div>

        <!-- ── Generating overlay ── -->
        <Transition
            enter-active-class="transition-all duration-300"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="pollingGameId" class="fixed inset-0 z-50 bg-white/90 backdrop-blur-sm flex flex-col items-center justify-center gap-8 p-6">
                <!-- Robot spinner -->
                <div class="relative">
                    <div class="w-24 h-24 border-4 border-indigo-100 border-t-indigo-500 rounded-full animate-spin"></div>
                    <div class="absolute inset-0 flex items-center justify-center text-3xl">🤖</div>
                </div>

                <div class="text-center">
                    <h2 class="text-2xl font-black text-slate-800 mb-1">AI o'yin yaratmoqda</h2>
                    <p class="text-slate-500 text-sm">{{ form.topic }}</p>
                </div>

                <!-- Steps -->
                <div class="w-full max-w-xs space-y-2.5">
                    <div v-for="(step, i) in GEN_STEPS" :key="i"
                        :class="[
                            'flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-500',
                            i < genStep  ? 'bg-green-50 text-green-700' :
                            i === genStep ? 'bg-indigo-50 text-indigo-700 shadow-sm' :
                            'bg-slate-50 text-slate-400'
                        ]">
                        <span class="text-xl shrink-0">{{ i < genStep ? '✅' : i === genStep ? step.icon : '⏳' }}</span>
                        <span class="text-sm font-semibold">{{ step.text }}</span>
                        <span v-if="i === genStep" class="ml-auto flex gap-0.5">
                            <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                            <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                            <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                        </span>
                    </div>
                </div>

                <p class="text-slate-400 text-xs">Bu 10–60 soniya davom etishi mumkin</p>
            </div>
        </Transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.tpl-card { animation: tplCardIn 0.35s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes tplCardIn {
    from { opacity: 0; transform: scale(0.88) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
