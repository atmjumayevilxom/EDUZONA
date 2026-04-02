<script setup>
import { ref, computed, onUnmounted, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const GAME_TIME = 45;

const queue    = ref([]);
const qIdx     = ref(0);
const score    = ref(0);
const timeLeft = ref(GAME_TIME);
const started  = ref(false);
const finished = ref(false);
const feedback = ref(null);  // {text, correct}
const streak   = ref(0);
const particles = ref([]);
let timer = null;

const groups  = computed(() => props.gameData.groups ?? []);
const current = computed(() => queue.value[qIdx.value]);
const remaining = computed(() => queue.value.length - qIdx.value);
const timerPct  = computed(() => (timeLeft.value / GAME_TIME) * 100);
const urgent    = computed(() => timeLeft.value <= 10);

const GROUP_COLORS = [
    { bg: '#6d28d9', glow: 'rgba(109,40,217,0.4)', text: '#ddd6fe' },
    { bg: '#be185d', glow: 'rgba(190,24,93,0.4)',  text: '#fbcfe8' },
    { bg: '#065f46', glow: 'rgba(6,95,70,0.4)',    text: '#6ee7b7' },
    { bg: '#92400e', glow: 'rgba(146,64,14,0.4)',  text: '#fde68a' },
    { bg: '#1e3a5f', glow: 'rgba(30,58,95,0.4)',   text: '#bae6fd' },
    { bg: '#3b1f6e', glow: 'rgba(59,31,110,0.4)',  text: '#e9d5ff' },
];

function buildQueue() {
    const arr = [];
    for (const g of groups.value) {
        for (const item of (g.items ?? [])) {
            arr.push({ text: item, correctGroup: g.name });
        }
    }
    return arr.sort(() => Math.random() - 0.5);
}

onMounted(() => { queue.value = buildQueue(); });

function start() {
    started.value = true;
    timer = setInterval(() => {
        if (--timeLeft.value <= 0) endGame();
    }, 1000);
}

function endGame() {
    clearInterval(timer);
    finished.value = true;
    audio.playComplete();
}

/* ── Particles ── */
const COLORS = ['#a78bfa','#f472b6','#34d399','#fbbf24','#60a5fa'];
function spawnParticles() {
    particles.value = Array.from({ length: 10 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 30 + Math.random() * 40,
        y: 30 + Math.random() * 40,
        dx: (Math.random() - 0.5) * 100,
        dy: -50 - Math.random() * 80,
        size: 4 + Math.random() * 6,
    }));
    setTimeout(() => { particles.value = []; }, 650);
}

function sort(groupName) {
    if (feedback.value || finished.value) return;
    const isCorrect = current.value.correctGroup === groupName;
    if (isCorrect) {
        streak.value++;
        const bonus = 10 + Math.min(streak.value - 1, 5) * 2;
        score.value += bonus;
        feedback.value = { text: `✅ +${bonus}${streak.value > 2 ? ' 🔥' : ''}`, correct: true };
        audio.playCorrect();
        spawnParticles();
    } else {
        streak.value = 0;
        feedback.value = { text: `❌ ${current.value.correctGroup}`, correct: false };
        audio.playWrong();
    }
    setTimeout(() => {
        feedback.value = null;
        if (qIdx.value + 1 >= queue.value.length) {
            endGame();
        } else {
            qIdx.value++;
        }
    }, 650);
}

function restart() {
    clearInterval(timer);
    queue.value    = buildQueue();
    qIdx.value     = 0;
    score.value    = 0;
    timeLeft.value = GAME_TIME;
    started.value  = false;
    finished.value = false;
    feedback.value = null;
    streak.value   = 0;
    particles.value = [];
}

onUnmounted(() => clearInterval(timer));
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ START ══ -->
        <div v-if="!started" class="max-w-md mx-auto">
            <div class="rounded-3xl overflow-hidden shadow-2xl" style="background:linear-gradient(140deg,#0f172a,#1e1b4b,#0f172a)">
                <div class="px-8 py-10 text-center">
                    <div class="text-6xl mb-4">⚡</div>
                    <h2 class="text-3xl font-black text-white mb-2">Tez saralash!</h2>
                    <p class="text-white/50 text-sm mb-6">Har elementni to'g'ri guruhga joylashtiring</p>

                    <!-- Group chips -->
                    <div class="flex flex-wrap justify-center gap-2 mb-6">
                        <div v-for="(g, i) in groups" :key="g.name"
                            class="px-4 py-1.5 rounded-full text-sm font-bold"
                            :style="{ background: GROUP_COLORS[i % GROUP_COLORS.length].bg, color: GROUP_COLORS[i % GROUP_COLORS.length].text }">
                            {{ g.name }}
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-2 mb-6">
                        <div class="bg-white/8 rounded-2xl p-3 text-center border border-white/10">
                            <div class="text-2xl">⏱️</div>
                            <div class="text-white font-black">{{ GAME_TIME }}s</div>
                        </div>
                        <div class="bg-white/8 rounded-2xl p-3 text-center border border-white/10">
                            <div class="text-2xl">🎯</div>
                            <div class="text-white font-black">{{ queue.length }} element</div>
                        </div>
                    </div>

                    <button @click="start"
                        class="w-full py-4 rounded-2xl font-black text-white text-xl active:scale-95 transition-all"
                        style="background:linear-gradient(135deg,#f59e0b,#ef4444);box-shadow:0 8px 24px rgba(245,158,11,0.4)">
                        ⚡ Boshlash!
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ FINISHED ══ -->
        <div v-else-if="finished" class="max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div class="bg-gradient-to-br from-amber-400 to-orange-600 px-10 py-12 text-center text-white">
                    <div class="text-6xl mb-3">🏁</div>
                    <div class="text-6xl font-black mb-1">{{ score }}</div>
                    <div class="text-white/80 text-lg">ball · {{ qIdx }}/{{ queue.length }} element</div>
                </div>
                <div class="p-5">
                    <button @click="restart"
                        class="w-full bg-amber-500 hover:bg-amber-600 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-amber-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else class="w-full max-w-md mx-auto space-y-3">

            <!-- HUD -->
            <div class="flex items-center gap-3">
                <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full transition-all duration-1000', urgent ? 'animate-pulse' : '']"
                        :style="{
                            width: timerPct + '%',
                            background: timeLeft > 20 ? '#22c55e' : timeLeft > 10 ? '#f59e0b' : '#ef4444',
                        }">
                    </div>
                </div>
                <span :class="['text-base font-black w-10 text-right shrink-0', urgent ? 'text-red-500 animate-pulse' : 'text-slate-600']">
                    {{ timeLeft }}s
                </span>
                <div class="bg-amber-100 text-amber-700 text-xs font-black px-3 py-1 rounded-full shrink-0">
                    {{ score }} ball
                </div>
                <Transition name="streak-pop">
                    <div v-if="streak >= 2"
                        class="bg-gradient-to-r from-amber-500 to-red-500 text-white text-xs font-black px-2.5 py-1 rounded-full shrink-0"
                        style="box-shadow:0 2px 10px rgba(245,158,11,0.5)">
                        🔥{{ streak }}x
                    </div>
                </Transition>
            </div>

            <!-- Item card -->
            <div class="relative rounded-3xl overflow-hidden shadow-xl"
                style="background: linear-gradient(140deg,#1e1b4b,#312e81,#1e1b4b); min-height:140px">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-20">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{ left: p.x+'%', top: p.y+'%', background: p.color,
                                  width: p.size+'px', height: p.size+'px',
                                  '--dx': p.dx+'px', '--dy': p.dy+'px' }">
                    </div>
                </div>

                <div class="px-5 py-4">
                    <div class="text-violet-400 text-[9px] font-black uppercase tracking-widest mb-3">
                        Qaysi guruhga tegishli? · {{ remaining }} qoldi
                    </div>
                    <div class="flex items-center justify-center min-h-[70px]">
                        <Transition
                            enter-active-class="transition-all duration-250 ease-out"
                            enter-from-class="opacity-0 scale-80 translateY(-8px)"
                            enter-to-class="opacity-100 scale-100"
                            mode="out-in"
                        >
                            <div v-if="feedback" :key="'fb'"
                                :class="['text-2xl font-black text-center', feedback.correct ? 'text-emerald-400' : 'text-red-400']">
                                {{ feedback.text }}
                            </div>
                            <div v-else-if="current" :key="current.text + qIdx"
                                class="text-white font-bold text-xl sm:text-2xl text-center break-words px-2 item-appear">
                                {{ current.text }}
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>

            <!-- Group buttons -->
            <div :class="['grid gap-2.5', groups.length <= 2 ? 'grid-cols-2' : groups.length <= 4 ? 'grid-cols-2' : 'grid-cols-3']">
                <button
                    v-for="(g, i) in groups"
                    :key="g.name"
                    @click="sort(g.name)"
                    :disabled="!!feedback"
                    :style="{
                        background: GROUP_COLORS[i % GROUP_COLORS.length].bg,
                        color: GROUP_COLORS[i % GROUP_COLORS.length].text,
                        boxShadow: `0 4px 16px ${GROUP_COLORS[i % GROUP_COLORS.length].glow}`,
                        animationDelay: (i * 80) + 'ms',
                    }"
                    class="py-4 px-3 rounded-2xl font-bold text-sm transition-all active:scale-95 disabled:opacity-60 break-words text-center group-btn"
                >
                    {{ g.name }}
                </button>
            </div>
        </div>
    </div>
</template>

<style scoped>
.group-btn {
    animation: groupAppear 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes groupAppear {
    from { opacity: 0; transform: scale(0.8) translateY(8px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.group-btn:not(:disabled):hover {
    filter: brightness(1.15);
    transform: translateY(-2px);
}

.item-appear { animation: itemSlide 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes itemSlide {
    from { opacity: 0; transform: scale(0.85); }
    to   { opacity: 1; transform: scale(1); }
}

.streak-pop-enter-active { transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.streak-pop-enter-from   { opacity: 0; transform: scale(0.3); }
.streak-pop-leave-active { transition: all 0.2s ease; }
.streak-pop-leave-to     { opacity: 0; transform: scale(0.5); }

.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.65s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx),var(--dy)) scale(0); opacity: 0; }
}

.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
