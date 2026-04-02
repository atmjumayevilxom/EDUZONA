<script setup>
import { onMounted, onUnmounted, computed, ref, watch, defineAsyncComponent } from 'vue';
import { Head } from '@inertiajs/vue3';
import GameLayout from '@/Components/GameLayout.vue';
import { useGameStore } from '@/stores/game';
import { useTemplateMeta } from '@/composables/useTemplateMeta';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameId: Number });

const gameStore     = useGameStore();
const { tplMeta }   = useTemplateMeta();
const audio         = useGameAudio();
const rendererKey   = ref(0);

// ── AI generation progress simulation ─────────────────────────────────────
const AI_STEPS = [
    { icon: '🔍', text: "Mavzu tahlil qilinmoqda..." },
    { icon: '🧠', text: "AI savollar yaratmoqda..." },
    { icon: '✏️',  text: "Javoblar tekshirilmoqda..." },
    { icon: '🎨', text: "O'yin formatlanmoqda..." },
    { icon: '✅', text: "Deyarli tayyor!" },
];
const genStep   = ref(0);
let genStepTimer = null;

const renderers = {
    quiz_mcq:          defineAsyncComponent(() => import('@/Components/Renderers/QuizRenderer.vue')),
    anagram:           defineAsyncComponent(() => import('@/Components/Renderers/AnagramRenderer.vue')),
    true_false:        defineAsyncComponent(() => import('@/Components/Renderers/TrueFalseRenderer.vue')),
    flashcards:        defineAsyncComponent(() => import('@/Components/Renderers/FlashcardsRenderer.vue')),
    matching_pairs:    defineAsyncComponent(() => import('@/Components/Renderers/MatchingPairsRenderer.vue')),
    type_answer:       defineAsyncComponent(() => import('@/Components/Renderers/TypeAnswerRenderer.vue')),
    random_wheel:      defineAsyncComponent(() => import('@/Components/Renderers/RandomWheelRenderer.vue')),
    open_box:          defineAsyncComponent(() => import('@/Components/Renderers/OpenBoxRenderer.vue')),
    complete_sentence: defineAsyncComponent(() => import('@/Components/Renderers/CompleteSentenceRenderer.vue')),
    hangman:           defineAsyncComponent(() => import('@/Components/Renderers/HangmanRenderer.vue')),
    reorder:           defineAsyncComponent(() => import('@/Components/Renderers/ReorderRenderer.vue')),
    group_sort:        defineAsyncComponent(() => import('@/Components/Renderers/GroupSortRenderer.vue')),
    whack_mole:        defineAsyncComponent(() => import('@/Components/Renderers/WhackMoleRenderer.vue')),
    word_search:       defineAsyncComponent(() => import('@/Components/Renderers/WordSearchRenderer.vue')),
    memory_cards:      defineAsyncComponent(() => import('@/Components/Renderers/MemoryCardsRenderer.vue')),
    game_show_quiz:    defineAsyncComponent(() => import('@/Components/Renderers/GameShowRenderer.vue')),
    flying_answers:    defineAsyncComponent(() => import('@/Components/Renderers/FlyingAnswersRenderer.vue')),
    pair_or_not:       defineAsyncComponent(() => import('@/Components/Renderers/PairOrNotRenderer.vue')),
    speed_sort:        defineAsyncComponent(() => import('@/Components/Renderers/SpeedSortRenderer.vue')),
    spell_word:        defineAsyncComponent(() => import('@/Components/Renderers/SpellWordRenderer.vue')),
    airplane:          defineAsyncComponent(() => import('@/Components/Renderers/AirplaneRenderer.vue')),
    watch_memorize:    defineAsyncComponent(() => import('@/Components/Renderers/WatchMemorizeRenderer.vue')),
    win_or_lose:       defineAsyncComponent(() => import('@/Components/Renderers/WinOrLoseRenderer.vue')),
    math_quiz:         defineAsyncComponent(() => import('@/Components/Renderers/MathQuizRenderer.vue')),
    millionaire:       defineAsyncComponent(() => import('@/Components/Renderers/MillionaireRenderer.vue')),
    spelling:          defineAsyncComponent(() => import('@/Components/Renderers/SpellingRenderer.vue')),
    diagram:           defineAsyncComponent(() => import('@/Components/Renderers/DiagramRenderer.vue')),
    zakovat:           defineAsyncComponent(() => import('@/Components/Renderers/ZakovatRenderer.vue')),
    race:              defineAsyncComponent(() => import('@/Components/Renderers/RaceRenderer.vue')),
    timeline:          defineAsyncComponent(() => import('@/Components/Renderers/TimelineRenderer.vue')),
    crossword:         defineAsyncComponent(() => import('@/Components/Renderers/CrosswordRenderer.vue')),
    lesson_plan:       defineAsyncComponent(() => import('@/Components/Renderers/LessonPlanRenderer.vue')),
    dtm_test:          defineAsyncComponent(() => import('@/Components/Renderers/DtmTestRenderer.vue')),
    rope_pull:         defineAsyncComponent(() => import('@/Components/Renderers/RopePullRenderer.vue')),
    sleeping_bear:     defineAsyncComponent(() => import('@/Components/Renderers/SleepingBearRenderer.vue')),
    pisa_reading:      defineAsyncComponent(() => import('@/Components/Renderers/PisaRenderer.vue')),
    olimpiada:         defineAsyncComponent(() => import('@/Components/Renderers/DtmTestRenderer.vue')),
};

const templateCode      = computed(() => gameStore.currentGame?.template?.code);
const meta              = computed(() => tplMeta(templateCode.value));
const rendererComponent = computed(() => renderers[templateCode.value] ?? null);
const hasRenderer       = computed(() => templateCode.value && templateCode.value in renderers);

function restartRenderer() { rendererKey.value++; }

onMounted(() => gameStore.fetchGame(props.gameId));
onUnmounted(() => { audio.stop(); clearInterval(genStepTimer); });

watch(
    () => gameStore.currentGame?.status,
    (status) => {
        if (status === 'generating') {
            genStep.value = 0;
            clearInterval(genStepTimer);
            genStepTimer = setInterval(() => {
                if (genStep.value < AI_STEPS.length - 1) genStep.value++;
                else clearInterval(genStepTimer);
            }, 2800);
        } else {
            clearInterval(genStepTimer);
        }
    }
);

// Start BGM when game becomes ready (immediate covers cached Pinia state)
watch(
    () => gameStore.currentGame?.status,
    (status) => {
        if (status === 'ready' && templateCode.value) {
            audio.play(templateCode.value);
        }
    },
    { immediate: true }
);
</script>

<template>
    <Head :title="gameStore.currentGame?.topic ?? 'O\'yin'" />

    <GameLayout
        :title="gameStore.currentGame?.topic ?? 'Yuklanmoqda...'"
        :subtitle="gameStore.currentGame?.template?.name ?? ''"
        :template-icon="meta.icon"
        :template-color="meta.color"
        :back-href="`/games/${gameId}`"
        :show-restart="hasRenderer && !gameStore.loading"
        @restart="restartRenderer"
    >
        <!-- ── Loading ── -->
        <div v-if="gameStore.loading" class="flex flex-col items-center justify-center py-40 gap-5">
            <div class="w-14 h-14 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            <p class="text-slate-400 text-sm font-medium">O'yin yuklanmoqda...</p>
        </div>

        <!-- ── Generating (polling) ── -->
        <div v-else-if="gameStore.currentGame?.status === 'generating'"
            class="flex flex-col items-center justify-center py-24 gap-8">

            <!-- Robot + spinner -->
            <div class="relative">
                <div class="w-24 h-24 border-4 border-indigo-100 border-t-indigo-500 rounded-full animate-spin"></div>
                <div class="absolute inset-0 flex items-center justify-center text-3xl">🤖</div>
            </div>

            <!-- Progress steps -->
            <div class="w-full max-w-xs space-y-3">
                <div v-for="(step, i) in AI_STEPS" :key="i"
                    :class="[
                        'flex items-center gap-3 px-4 py-3 rounded-2xl transition-all duration-500',
                        i < genStep  ? 'bg-green-50 text-green-700' :
                        i === genStep ? 'bg-indigo-50 text-indigo-700 shadow-sm' :
                        'bg-slate-50 text-slate-400'
                    ]">
                    <span class="text-xl shrink-0">
                        {{ i < genStep ? '✅' : i === genStep ? step.icon : '⏳' }}
                    </span>
                    <span class="text-sm font-semibold">{{ step.text }}</span>
                    <span v-if="i === genStep" class="ml-auto flex gap-0.5">
                        <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-1.5 h-1.5 bg-indigo-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </span>
                </div>
            </div>

            <p class="text-slate-400 text-xs">Bu bir necha soniya vaqt olishi mumkin</p>
        </div>

        <!-- ── Renderer ── -->
        <template v-else-if="gameStore.currentGame?.status === 'ready' && gameStore.currentGame?.generated_json">
            <div class="w-full max-w-4xl mx-auto">
            <component
                v-if="hasRenderer"
                :key="rendererKey"
                :is="rendererComponent"
                :game-data="gameStore.currentGame.generated_json"
            />
            <div v-else
                class="max-w-lg mx-auto bg-amber-50 border-2 border-amber-200 rounded-3xl p-14 text-center">
                <div class="text-6xl mb-5">🚧</div>
                <h3 class="font-bold text-amber-800 text-xl mb-2">Renderer tayyorlanmoqda</h3>
                <p class="text-amber-600 text-sm">
                    {{ gameStore.currentGame.template?.name }} uchun renderer tez orada qo'shiladi.
                </p>
            </div>
            </div>
        </template>

        <!-- ── Error ── -->
        <div v-else-if="gameStore.currentGame?.status === 'error'"
            class="max-w-lg mx-auto bg-red-50 border-2 border-red-200 rounded-3xl p-14 text-center">
            <div class="text-6xl mb-5">❌</div>
            <h3 class="font-bold text-red-800 text-xl mb-2">Yaratishda xato yuz berdi</h3>
            <p class="text-red-600 text-sm mb-6">AI o'yin yarata olmadi. Qayta urinib ko'ring.</p>
            <a :href="`/games/${gameId}`"
                class="inline-flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white font-semibold px-6 py-3 rounded-xl transition text-sm">
                O'yin sahifasiga qaytish
            </a>
        </div>

        <!-- ── Not ready ── -->
        <div v-else class="flex items-center justify-center py-40 text-slate-400 text-sm">
            O'yin hali tayyor emas.
        </div>
    </GameLayout>
</template>
