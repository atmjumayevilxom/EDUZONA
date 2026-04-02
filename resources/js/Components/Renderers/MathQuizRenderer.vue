<script setup>
import { ref, computed, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const TIME_LIMIT = 20;

const items     = computed(() => props.gameData.items ?? []);
const current   = ref(0);
const score     = ref(0);
const lives     = ref(3);
const done      = ref(false);
const feedback  = ref(null); // 'correct'|'wrong'|'timeout'
const answered  = ref(false);
const userInput = ref('');
const timeLeft  = ref(TIME_LIMIT);
const streak    = ref(0);
const particles = ref([]);
let timer = null;

const item     = computed(() => items.value[current.value] ?? null);
const timerPct = computed(() => (timeLeft.value / TIME_LIMIT) * 100);
const urgent   = computed(() => timeLeft.value <= 5);

/* ── Particles ── */
const COLORS = ['#34d399','#60a5fa','#fbbf24','#f472b6','#a78bfa'];
function spawnParticles() {
    particles.value = Array.from({ length: 12 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 20 + Math.random() * 60,
        y: 20 + Math.random() * 60,
        dx: (Math.random() - 0.5) * 120,
        dy: -50 - Math.random() * 90,
        size: 5 + Math.random() * 6,
    }));
    setTimeout(() => { particles.value = []; }, 700);
}

function startTimer() {
    clearInterval(timer);
    timeLeft.value = TIME_LIMIT;
    timer = setInterval(() => {
        if (--timeLeft.value <= 0) {
            clearInterval(timer);
            if (!answered.value) timeout();
        }
    }, 1000);
}

function timeout() {
    answered.value = true;
    lives.value--;
    streak.value = 0;
    feedback.value = 'timeout';
    audio.playWrong();
    advance();
}

function advance() {
    setTimeout(() => {
        feedback.value = null;
        answered.value = false;
        userInput.value = '';
        if (lives.value <= 0 || current.value >= items.value.length - 1) {
            done.value = true;
            audio.playComplete();
        } else {
            current.value++;
            startTimer();
        }
    }, 1400);
}

function submit() {
    if (answered.value || !item.value) return;
    const val = userInput.value.trim();
    if (!val) return;
    answered.value = true;
    clearInterval(timer);

    const correctAns = String(item.value.answer).trim().toLowerCase();
    const userAns = val.toLowerCase();

    let isCorrect = false;
    if (item.value.options) {
        const chosen = item.value.options.find(o => String(o).toLowerCase() === userAns || String(o) === val);
        isCorrect = chosen && String(chosen).toLowerCase() === correctAns;
    } else {
        isCorrect = userAns === correctAns;
    }

    if (isCorrect) {
        streak.value++;
        const bonus = 50 + Math.floor(timeLeft.value * 3) + (streak.value > 1 ? (streak.value - 1) * 10 : 0);
        score.value += bonus;
        feedback.value = 'correct';
        audio.playCorrect();
        spawnParticles();
    } else {
        lives.value--;
        streak.value = 0;
        feedback.value = 'wrong';
        audio.playWrong();
    }
    advance();
}

function pickOption(opt) {
    userInput.value = String(opt);
    submit();
}

function restart() {
    clearInterval(timer);
    current.value   = 0;
    score.value     = 0;
    lives.value     = 3;
    done.value      = false;
    feedback.value  = null;
    answered.value  = false;
    userInput.value = '';
    streak.value    = 0;
    particles.value = [];
    startTimer();
}

startTimer();
onUnmounted(() => clearInterval(timer));

function optState(opt) {
    if (!answered.value) return 'idle';
    if (String(opt) === String(item.value?.answer)) return 'correct';
    if (String(userInput.value) === String(opt)) return 'wrong';
    return 'dim';
}
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ DONE ══ -->
        <div v-if="done" class="max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div class="bg-gradient-to-br from-violet-500 to-indigo-700 px-10 py-12 text-center text-white">
                    <div class="text-6xl mb-3">🧮</div>
                    <div class="text-5xl font-black mb-1">{{ score }}</div>
                    <div class="text-white/80">ball · {{ items.length }} masala</div>
                    <div v-if="streak > 1" class="mt-2 text-sm font-bold text-amber-300">🔥 Max seriya: {{ streak }}</div>
                </div>
                <div class="p-5">
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else-if="item" class="w-full max-w-md mx-auto space-y-3">

            <!-- HUD -->
            <div class="flex items-center gap-3">
                <!-- Lives -->
                <div class="flex gap-1 shrink-0">
                    <span v-for="l in 3" :key="l"
                        :class="['text-lg transition-all duration-300', l > lives ? 'grayscale opacity-25' : '']">❤️</span>
                </div>
                <!-- Timer bar -->
                <div class="flex-1 h-2.5 bg-slate-200 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full transition-all duration-1000', urgent ? 'animate-pulse' : '']"
                        :style="{
                            width: timerPct + '%',
                            background: timeLeft > 12 ? '#22c55e' : timeLeft > 6 ? '#f59e0b' : '#ef4444',
                            transition: 'width 0.9s linear, background 0.4s'
                        }">
                    </div>
                </div>
                <span :class="['text-base font-black w-8 text-right shrink-0', urgent ? 'text-red-500 animate-pulse' : 'text-slate-500']">
                    {{ timeLeft }}s
                </span>
                <div class="bg-violet-100 text-violet-700 text-xs font-black px-3 py-1 rounded-full shrink-0">{{ score }}</div>
            </div>

            <!-- Expression card -->
            <div :key="current" class="relative rounded-3xl overflow-hidden shadow-xl q-appear"
                style="background: linear-gradient(140deg,#1e1b4b,#4c1d95,#1e1b4b)">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-20">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{ left: p.x+'%', top: p.y+'%', background: p.color,
                                  width: p.size+'px', height: p.size+'px',
                                  '--dx': p.dx+'px', '--dy': p.dy+'px' }">
                    </div>
                </div>

                <div class="px-5 py-6 text-center">
                    <div class="text-violet-400 text-[9px] font-black uppercase tracking-widest mb-3">
                        {{ current + 1 }}/{{ items.length }} · Matematik masala
                    </div>
                    <div class="text-3xl sm:text-4xl font-black text-white font-mono tracking-wider mb-1">
                        {{ item.expression ?? item.question }}
                    </div>
                    <div v-if="item.hint" class="text-sm text-violet-300/70 mt-1">{{ item.hint }}</div>

                    <!-- Streak badge -->
                    <Transition name="streak-pop">
                        <div v-if="streak >= 2"
                            class="inline-flex items-center gap-1 mt-2 bg-amber-500/20 border border-amber-400/30 text-amber-300 text-xs font-black px-3 py-1 rounded-full">
                            🔥 {{ streak }}x seriya!
                        </div>
                    </Transition>
                </div>
            </div>

            <!-- MCQ options -->
            <div v-if="item.options" class="grid grid-cols-2 gap-2.5">
                <button v-for="(opt, oi) in item.options" :key="opt"
                    @click="pickOption(opt)"
                    :disabled="answered"
                    :style="{ animationDelay: (oi * 70) + 'ms' }"
                    :class="[
                        'py-4 rounded-2xl font-black text-xl font-mono border-2 transition-all opt-appear active:scale-95',
                        optState(opt) === 'idle'    ? 'bg-white border-slate-200 text-slate-800 hover:border-violet-400 hover:bg-violet-50 cursor-pointer hover:scale-105'
                        : optState(opt) === 'correct' ? 'bg-emerald-50 border-emerald-500 text-emerald-700'
                        : optState(opt) === 'wrong'   ? 'bg-red-50 border-red-400 text-red-600'
                        : 'bg-slate-50 border-slate-100 text-slate-300 cursor-default'
                    ]"
                >{{ opt }}</button>
            </div>

            <!-- Free input -->
            <div v-else class="flex gap-2">
                <input
                    v-model="userInput"
                    type="text"
                    inputmode="numeric"
                    placeholder="Javobni kiriting..."
                    @keyup.enter="submit"
                    :disabled="answered"
                    class="flex-1 border-2 border-slate-200 rounded-2xl px-5 py-3.5 text-xl font-mono font-black text-center focus:outline-none focus:border-violet-400 bg-white transition-all"
                />
                <button @click="submit"
                    :disabled="answered || !userInput.trim()"
                    class="bg-violet-600 hover:bg-violet-700 active:scale-95 disabled:bg-violet-300 text-white font-black px-6 rounded-2xl transition-all">
                    ✓
                </button>
            </div>

            <!-- Feedback -->
            <Transition
                enter-active-class="transition-all duration-250 ease-out"
                enter-from-class="opacity-0 scale-90"
                enter-to-class="opacity-100 scale-100"
            >
                <div v-if="feedback" :class="[
                    'rounded-2xl px-5 py-3 text-center font-bold text-sm',
                    feedback === 'correct' ? 'bg-emerald-50 text-emerald-700 border-2 border-emerald-300'
                    : feedback === 'timeout' ? 'bg-amber-50 text-amber-700 border-2 border-amber-300'
                    : 'bg-red-50 text-red-600 border-2 border-red-200'
                ]">
                    <span v-if="feedback === 'correct'">✅ To'g'ri! Ball qo'shildi 🎉</span>
                    <span v-else-if="feedback === 'timeout'">⏰ Vaqt tugadi! Javob: <strong class="font-mono">{{ item?.answer }}</strong></span>
                    <span v-else>❌ Noto'g'ri! Javob: <strong class="font-mono">{{ item?.answer }}</strong></span>
                </div>
            </Transition>
        </div>
    </div>
</template>

<style scoped>
.q-appear { animation: qSlide 0.38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity: 0; transform: scale(0.93) translateY(12px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

.opt-appear { animation: optIn 0.3s ease both; }
@keyframes optIn {
    from { opacity: 0; transform: scale(0.8); }
    to   { opacity: 1; transform: scale(1); }
}

.streak-pop-enter-active { transition: all 0.35s cubic-bezier(0.34,1.56,0.64,1); }
.streak-pop-enter-from   { opacity: 0; transform: scale(0.4); }
.streak-pop-leave-active { transition: all 0.2s ease; }
.streak-pop-leave-to     { opacity: 0; }

.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.7s ease-out forwards;
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
