<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: Object });
const audio = useGameAudio();

const allItems = computed(() => {
    const raw = props.gameData?.items ?? props.gameData?.questions ?? [];
    return raw.map((q, i) => ({
        id: i,
        category: q.category ?? q.topic ?? `Kategoriya ${Math.floor(i / 3) + 1}`,
        question: q.question ?? q.text ?? `Savol ${i + 1}`,
        options:  q.options ?? q.choices ?? [],
        answer:   q.answer ?? q.correct ?? '',
        points:   q.points ?? (100 * (Math.floor(i % 3) + 1)),
    }));
});

const categories = computed(() => {
    const map = new Map();
    for (const item of allItems.value) {
        if (!map.has(item.category)) map.set(item.category, []);
        map.get(item.category).push(item);
    }
    return Array.from(map.entries()).map(([name, questions]) => ({ name, questions }));
});

const CAT_GRADIENTS = [
    ['#6d28d9','#7c3aed'], ['#be185d','#db2777'],
    ['#0369a1','#0284c7'], ['#065f46','#059669'],
    ['#92400e','#b45309'], ['#1e3a5f','#1d4ed8'],
];
const CAT_ICONS = ['🏛️','🔬','🌍','🎨','⚽','🎵','📚','💡','🏆','🌿'];

// State
const phase      = ref('board');
const activeItem = ref(null);
const selected   = ref(null);
const feedback   = ref(null);
const answered   = ref(new Set());
const score      = ref(0);
const particles  = ref([]);

const allAnswered = computed(() => answered.value.size >= allItems.value.length);

/* ── Particles ── */
const COLORS = ['#a78bfa','#f472b6','#34d399','#fbbf24','#60a5fa'];
function spawnParticles() {
    particles.value = Array.from({ length: 14 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 20 + Math.random() * 60, y: 20 + Math.random() * 50,
        dx: (Math.random() - 0.5) * 140, dy: -50 - Math.random() * 100,
        size: 5 + Math.random() * 7,
    }));
    setTimeout(() => { particles.value = []; }, 750);
}

function openQuestion(item) {
    if (answered.value.has(item.id) || phase.value !== 'board') return;
    activeItem.value = item;
    selected.value   = null;
    feedback.value   = null;
    phase.value      = 'question';
}

function choose(opt) {
    if (selected.value !== null) return;
    selected.value = opt;
    const isCorrect = opt === activeItem.value.answer;
    feedback.value  = isCorrect ? 'correct' : 'wrong';
    if (isCorrect) {
        audio.playCorrect();
        score.value += activeItem.value.points;
        spawnParticles();
    } else {
        audio.playWrong();
    }
}

function closeQuestion() {
    answered.value = new Set([...answered.value, activeItem.value.id]);
    phase.value    = 'board';
    activeItem.value = null;
    if (allAnswered.value) setTimeout(() => audio.playComplete(), 300);
}

function reset() {
    score.value      = 0;
    answered.value   = new Set();
    activeItem.value = null;
    selected.value   = null;
    feedback.value   = null;
    phase.value      = 'board';
    particles.value  = [];
}

function pointsBg(pts) {
    if (pts >= 300) return 'linear-gradient(135deg,#dc2626,#b91c1c)';
    if (pts >= 200) return 'linear-gradient(135deg,#d97706,#b45309)';
    return 'linear-gradient(135deg,#2563eb,#1d4ed8)';
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ BOARD ══ -->
        <div v-if="phase === 'board'" class="space-y-3">

            <!-- HUD -->
            <div class="flex items-center justify-between px-1">
                <div class="text-slate-500 text-sm font-semibold">
                    {{ answered.size }} / {{ allItems.length }} javoblandi
                </div>
                <div class="bg-purple-600 text-white font-black text-sm px-4 py-1.5 rounded-full shadow">
                    {{ score }} ball
                </div>
            </div>

            <!-- All done -->
            <div v-if="allAnswered"
                class="rounded-3xl overflow-hidden shadow-xl all-done-appear"
                style="background:linear-gradient(140deg,#2e1065,#4c1d95)">
                <div class="px-8 py-10 text-center">
                    <div class="text-6xl mb-3">🏆</div>
                    <div class="text-4xl font-black text-white mb-1">{{ score }}</div>
                    <div class="text-purple-300 mb-6">ball · barcha savollar javoblandi!</div>
                    <button @click="reset"
                        class="px-8 py-3 bg-purple-400 hover:bg-purple-300 text-purple-900 font-black rounded-2xl transition-all active:scale-95">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>

            <!-- Categories -->
            <div v-for="(cat, ci) in categories" :key="cat.name"
                class="rounded-3xl overflow-hidden shadow-lg board-appear"
                :style="{ animationDelay: (ci * 80) + 'ms' }">

                <!-- Cat header -->
                <div class="flex items-center gap-3 px-5 py-3"
                    :style="{ background: `linear-gradient(135deg,${CAT_GRADIENTS[ci % CAT_GRADIENTS.length][0]},${CAT_GRADIENTS[ci % CAT_GRADIENTS.length][1]})` }">
                    <span class="text-xl">{{ CAT_ICONS[ci % CAT_ICONS.length] }}</span>
                    <h3 class="font-black text-white text-sm truncate flex-1">{{ cat.name }}</h3>
                    <span class="text-white/50 text-xs font-bold shrink-0">
                        {{ cat.questions.filter(q => answered.has(q.id)).length }}/{{ cat.questions.length }}
                    </span>
                </div>

                <!-- Points buttons -->
                <div class="bg-slate-900/95 p-3 grid gap-2"
                    :style="{ gridTemplateColumns: `repeat(${Math.min(cat.questions.length, 5)}, 1fr)` }">
                    <button
                        v-for="q in cat.questions" :key="q.id"
                        @click="openQuestion(q)"
                        :disabled="answered.has(q.id)"
                        :class="[
                            'py-3 px-1 rounded-2xl font-black text-white text-sm transition-all',
                            answered.has(q.id)
                                ? 'opacity-20 cursor-not-allowed bg-white/10'
                                : 'cursor-pointer hover:scale-105 hover:shadow-lg active:scale-95'
                        ]"
                        :style="answered.has(q.id) ? {} : { background: pointsBg(q.points), boxShadow: '0 4px 14px rgba(0,0,0,0.3)' }"
                    >
                        {{ q.points }}
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ QUESTION ══ -->
        <div v-else-if="phase === 'question' && activeItem"
            class="w-full max-w-lg mx-auto">

            <div class="rounded-3xl overflow-hidden shadow-2xl q-appear"
                style="background:linear-gradient(140deg,#0f172a,#1e1b4b)">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-30 rounded-3xl" style="position:relative">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{ left: p.x+'%', top: p.y+'%', background: p.color,
                                  width: p.size+'px', height: p.size+'px',
                                  '--dx': p.dx+'px', '--dy': p.dy+'px' }">
                    </div>
                </div>

                <!-- Header -->
                <div class="flex items-center justify-between px-5 pt-5 pb-3">
                    <span class="bg-purple-500/20 border border-purple-400/30 text-purple-300 text-xs font-black px-3 py-1 rounded-full">
                        {{ activeItem.category }}
                    </span>
                    <span class="bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-black px-3 py-1 rounded-full">
                        {{ activeItem.points }} ball
                    </span>
                </div>

                <!-- Question -->
                <div class="px-5 pb-4">
                    <div class="bg-white/8 rounded-2xl px-5 py-4 text-center">
                        <p class="text-white font-bold text-base sm:text-lg leading-snug break-words">
                            {{ activeItem.question }}
                        </p>
                    </div>
                </div>

                <!-- Feedback -->
                <Transition
                    enter-active-class="transition-all duration-250 ease-out"
                    enter-from-class="opacity-0 scale-90"
                    enter-to-class="opacity-100 scale-100"
                >
                    <div v-if="feedback" class="px-5 pb-3">
                        <div :class="[
                            'text-center py-2.5 rounded-2xl font-bold text-sm',
                            feedback === 'correct'
                                ? 'bg-emerald-500/20 border border-emerald-400/40 text-emerald-300'
                                : 'bg-red-500/20 border border-red-400/40 text-red-300'
                        ]">
                            {{ feedback === 'correct'
                                ? `✅ To'g'ri! +${activeItem.points} ball!`
                                : `❌ To'g'ri javob: "${activeItem.answer}"` }}
                        </div>
                    </div>
                </Transition>

                <!-- Options -->
                <div v-if="!feedback" class="px-5 pb-4 grid grid-cols-1 sm:grid-cols-2 gap-2">
                    <button
                        v-for="(opt, oi) in activeItem.options" :key="opt"
                        @click="choose(opt)"
                        :style="{ animationDelay: (oi * 70) + 'ms' }"
                        class="w-full text-left px-4 py-3.5 rounded-2xl border-2 border-white/15 bg-white/5
                               hover:border-purple-400/60 hover:bg-purple-400/10 font-semibold text-sm text-white
                               transition-all active:scale-97 cursor-pointer opt-appear"
                    >
                        <span class="text-white/40 font-black mr-2">{{ ['A','B','C','D'][oi] }}.</span>
                        {{ opt }}
                    </button>
                </div>

                <!-- Footer button -->
                <div class="px-5 pb-5">
                    <button v-if="feedback" @click="closeQuestion"
                        class="w-full py-3.5 bg-purple-600 hover:bg-purple-500 active:scale-95 text-white font-black rounded-2xl transition-all">
                        Davom etish →
                    </button>
                    <button v-else @click="closeQuestion"
                        class="w-full py-2 text-white/30 hover:text-white/60 text-sm font-medium transition-all">
                        O'tkazib yuborish
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.board-appear { animation: boardIn 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes boardIn {
    from { opacity: 0; transform: translateY(12px) scale(0.97); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

.q-appear { animation: qSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: scale(0.93) translateY(14px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.opt-appear { animation: optIn 0.3s ease both; }
@keyframes optIn {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}

.all-done-appear { animation: adPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes adPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}

.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.75s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx),var(--dy)) scale(0); opacity: 0; }
}
</style>
