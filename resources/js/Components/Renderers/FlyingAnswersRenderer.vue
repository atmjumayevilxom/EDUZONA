<script setup>
import { ref, computed, watch, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const TIME_LIMIT = 14;

const currentIdx = ref(0);
const score      = ref(0);
const lives      = ref(3);
const finished   = ref(false);
const started    = ref(false);
const timeLeft   = ref(TIME_LIMIT);
const feedback   = ref(null); // 'correct'|'wrong'|'timeout'
const feedMsg    = ref('');
const roundKey   = ref(0);   // re-mount balloons each question

let countTimer = null;

function shuffleItem(item) {
    const opts    = [...(item.options ?? [])];
    const correct = opts[item.answer_index ?? 0];
    const shuffled = opts.sort(() => Math.random() - 0.5);
    return { ...item, options: shuffled, answer_index: shuffled.indexOf(correct) };
}

const items   = ref((props.gameData.items ?? []).map(shuffleItem));
const current = computed(() => items.value[currentIdx.value]);
const total   = computed(() => items.value.length);

// 4 balloon columns, evenly spaced
const COLS = [12, 33, 56, 78]; // left % positions

const BALLOON_STYLES = [
    { string: '#f87171', body: '#ef4444', shine: '#fca5a5', emoji: '🔴' },
    { string: '#60a5fa', body: '#3b82f6', shine: '#93c5fd', emoji: '🔵' },
    { string: '#4ade80', body: '#22c55e', shine: '#86efac', emoji: '🟢' },
    { string: '#fbbf24', body: '#f59e0b', shine: '#fde68a', emoji: '🟡' },
];

const balloons = computed(() => {
    if (!current.value || feedback.value) return [];
    return (current.value.options ?? []).map((text, i) => ({
        id:      `${roundKey.value}-${i}`,
        text,
        correct: i === current.value.answer_index,
        left:    COLS[i % COLS.length],
        style:   BALLOON_STYLES[i % BALLOON_STYLES.length],
        delay:   i * 0.35,
    }));
});

function startCountdown() {
    clearInterval(countTimer);
    timeLeft.value = TIME_LIMIT;
    countTimer = setInterval(() => {
        if (--timeLeft.value <= 0) {
            clearInterval(countTimer);
            lives.value--;
            showFeedback('timeout', '⏰ Vaqt tugadi!');
            if (lives.value <= 0) {
                setTimeout(() => { finished.value = true; audio.playComplete(); }, 1300);
            } else {
                setTimeout(advance, 1400);
            }
        }
    }, 1000);
}

function pop(balloon) {
    if (feedback.value) return;
    clearInterval(countTimer);

    if (balloon.correct) {
        const bonus = 100 + timeLeft.value * 5;
        score.value += bonus;
        audio.playCorrect();
        showFeedback('correct', `🎉 To'g'ri! +${bonus} ball`);
        setTimeout(advance, 1200);
    } else {
        lives.value--;
        audio.playWrong();
        showFeedback('wrong', '💥 Xato! Bu shar emas');
        if (lives.value <= 0) {
            setTimeout(() => { finished.value = true; audio.playComplete(); }, 1300);
        } else {
            setTimeout(advance, 1300);
        }
    }
}

function showFeedback(type, msg) {
    feedback.value = type;
    feedMsg.value  = msg;
}

function advance() {
    if (currentIdx.value + 1 >= total.value) {
        finished.value = true;
        audio.playComplete();
        return;
    }
    currentIdx.value++;
    feedback.value = null;
    roundKey.value++;
    startCountdown();
}

function start() {
    started.value = true;
    startCountdown();
}

function restart() {
    clearInterval(countTimer);
    currentIdx.value = 0; score.value = 0; lives.value = 3;
    finished.value = false; started.value = false;
    feedback.value = null; timeLeft.value = TIME_LIMIT; roundKey.value = 0;
    items.value = (props.gameData.items ?? []).map(shuffleItem);
}

onUnmounted(() => clearInterval(countTimer));

const timerPct = computed(() => (timeLeft.value / TIME_LIMIT) * 100);
const urgent   = computed(() => timeLeft.value <= 4);
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ START ══ -->
        <div v-if="!started" class="max-w-lg mx-auto">
            <div class="rounded-3xl overflow-hidden shadow-2xl" style="background:linear-gradient(160deg,#0f172a,#1e1b4b,#0f172a)">
                <div class="px-8 py-10 text-center">
                    <!-- Demo balloons -->
                    <div class="flex justify-center gap-5 mb-6">
                        <div v-for="(s,i) in BALLOON_STYLES.slice(0,4)" :key="i"
                            class="demo-balloon"
                            :style="{ '--bc': s.body, '--bs': s.shine, animationDelay: `${i*0.3}s` }">
                            <div class="balloon-body-demo"></div>
                            <div class="text-[10px] font-black text-white mt-1">A B C D</div>
                        </div>
                    </div>

                    <h2 class="text-3xl font-black text-white mb-2">Sharni yoring! 🎯</h2>
                    <p class="text-white/50 text-sm mb-3">Savolga <strong class="text-white/80">to'g'ri javob</strong> yozilgan sharni bosing</p>

                    <div class="bg-white/5 rounded-2xl p-4 mb-6 text-left space-y-2">
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <span class="text-xl">✅</span> To'g'ri shar → <span class="text-emerald-400 font-bold">+ball</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <span class="text-xl">💥</span> Noto'g'ri shar → <span class="text-red-400 font-bold">❤️ yo'qoladi</span>
                        </div>
                        <div class="flex items-center gap-3 text-sm text-white/70">
                            <span class="text-xl">⏰</span> Shar uchib ketsa → <span class="text-amber-400 font-bold">❤️ yo'qoladi</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-3 gap-2 mb-6">
                        <div class="bg-white/8 rounded-xl p-3 text-center border border-white/10">
                            <div class="text-2xl">❤️</div>
                            <div class="text-white font-black">3 jon</div>
                        </div>
                        <div class="bg-white/8 rounded-xl p-3 text-center border border-white/10">
                            <div class="text-2xl">⏱️</div>
                            <div class="text-white font-black">{{ TIME_LIMIT }}s</div>
                        </div>
                        <div class="bg-white/8 rounded-xl p-3 text-center border border-white/10">
                            <div class="text-2xl">🎯</div>
                            <div class="text-white font-black">{{ total }} savol</div>
                        </div>
                    </div>

                    <button @click="start"
                        class="w-full py-4 rounded-2xl font-black text-white text-xl"
                        style="background:linear-gradient(135deg,#7c3aed,#4f46e5);box-shadow:0 8px 24px rgba(124,58,237,0.5)">
                        🚀 Boshlash!
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ FINISHED ══ -->
        <div v-else-if="finished" class="max-w-lg mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="['px-10 py-12 text-center text-white', lives > 0 ? 'bg-gradient-to-br from-violet-500 to-purple-700' : 'bg-gradient-to-br from-slate-600 to-slate-800']">
                    <div class="text-7xl mb-4">{{ lives > 0 ? '🏆' : '💔' }}</div>
                    <div class="text-6xl font-black mb-2">{{ score }}</div>
                    <div class="text-white/70">ball to'plandi · {{ currentIdx }}/{{ total }} savol</div>
                    <div class="flex justify-center gap-1 mt-3">
                        <span v-for="i in 3" :key="i" class="text-xl">{{ i <= lives ? '❤️' : '🖤' }}</span>
                    </div>
                </div>
                <div class="p-6">
                    <button @click="restart" class="w-full bg-violet-600 hover:bg-violet-700 text-white font-black py-4 rounded-2xl transition text-lg">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else class="space-y-2">

            <!-- HUD -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm px-4 py-3 flex items-center gap-3">
                <div class="flex gap-1">
                    <span v-for="i in 3" :key="i" :class="['text-lg transition-all duration-300', i > lives ? 'grayscale opacity-30' : '']">
                        {{ i <= lives ? '❤️' : '🖤' }}
                    </span>
                </div>
                <div class="flex-1 h-2.5 bg-slate-100 rounded-full overflow-hidden">
                    <div :class="['h-full rounded-full', urgent ? 'animate-pulse' : '']"
                        :style="{ width: timerPct + '%', background: timeLeft > 7 ? '#22c55e' : timeLeft > 3 ? '#f59e0b' : '#ef4444', transition: 'width 0.9s linear, background 0.4s' }">
                    </div>
                </div>
                <span :class="['text-sm font-black w-8 text-right shrink-0', urgent ? 'text-red-500 animate-pulse' : 'text-slate-400']">{{ timeLeft }}s</span>
                <div class="bg-violet-100 text-violet-700 text-xs font-black px-3 py-1 rounded-full shrink-0">{{ score }} ball</div>
            </div>

            <!-- Question -->
            <div :key="currentIdx" class="rounded-2xl overflow-hidden q-appear shadow-sm">
                <div class="bg-gradient-to-r from-violet-600 to-purple-700 px-5 py-4">
                    <div class="text-violet-300 text-[10px] font-black uppercase tracking-widest mb-1.5">Savol {{ currentIdx + 1 }} / {{ total }} · To'g'ri sharni bosing 👇</div>
                    <p class="text-white font-bold text-sm sm:text-base leading-snug">{{ current?.question }}</p>
                </div>
            </div>

            <!-- Balloon arena -->
            <div class="balloon-arena relative rounded-2xl overflow-hidden" style="height:360px">

                <!-- Sky bg -->
                <div class="absolute inset-0 arena-sky"></div>

                <!-- Clouds -->
                <div class="deco-cloud" style="top:8%;animation-duration:20s">☁️</div>
                <div class="deco-cloud" style="top:22%;animation-duration:26s;animation-delay:-8s;opacity:.4">⛅</div>
                <div class="deco-cloud" style="top:5%;animation-duration:32s;animation-delay:-14s">☁️</div>

                <!-- Ground -->
                <div class="absolute bottom-0 left-0 right-0 h-14" style="background:linear-gradient(180deg,#15803d,#166534)">
                    <div class="flex justify-around items-end h-full px-4 pb-1">
                        <span class="text-2xl">🌻</span>
                        <span class="text-2xl">🌷</span>
                        <span class="text-2xl">🌸</span>
                        <span class="text-xl">🍀</span>
                        <span class="text-2xl">🌺</span>
                    </div>
                </div>

                <!-- Balloons -->
                <TransitionGroup tag="div" name="balloon-group">
                    <div
                        v-for="balloon in balloons"
                        :key="balloon.id"
                        class="absolute bottom-14"
                        :style="{ left: balloon.left + '%', animationDelay: balloon.delay + 's' }"
                    >
                        <button
                            @click="pop(balloon)"
                            class="balloon-wrap"
                            :style="{ '--bc': balloon.style.body, '--bs': balloon.style.shine, '--st': balloon.style.string }"
                        >
                            <!-- Balloon body SVG -->
                            <div class="balloon-body">
                                <div class="balloon-shine"></div>
                            </div>
                            <!-- Knot -->
                            <div class="balloon-knot"></div>
                            <!-- String -->
                            <div class="balloon-string"></div>
                            <!-- Text label -->
                            <div class="balloon-label">{{ balloon.text }}</div>
                        </button>
                    </div>
                </TransitionGroup>

                <!-- Feedback overlay -->
                <Transition
                    enter-active-class="transition-all duration-200"
                    enter-from-class="opacity-0 scale-75"
                    enter-to-class="opacity-100 scale-100"
                    leave-active-class="transition-all duration-150"
                    leave-to-class="opacity-0"
                >
                    <div v-if="feedback"
                        class="absolute inset-0 z-40 flex items-center justify-center"
                        style="background:rgba(0,0,0,0.35);backdrop-filter:blur(4px)">
                        <div :class="[
                            'feedback-card rounded-3xl px-10 py-7 text-center text-white shadow-2xl',
                            feedback === 'correct' ? 'bg-emerald-500' : feedback === 'wrong' ? 'bg-red-500' : 'bg-amber-500'
                        ]">
                            <div class="text-5xl mb-2">{{ feedback === 'correct' ? '🎈' : feedback === 'wrong' ? '💥' : '⏰' }}</div>
                            <div class="text-2xl font-black">{{ feedMsg }}</div>
                            <div v-if="lives > 0" class="text-white/70 text-sm mt-1">{{ lives }} ❤️ qoldi</div>
                        </div>
                    </div>
                </Transition>

            </div><!-- /arena -->
        </div>
    </div>
</template>

<style scoped>
/* ── Arena sky ── */
.arena-sky {
    background: linear-gradient(180deg, #38bdf8 0%, #7dd3fc 55%, #bae6fd 100%);
}

/* ── Deco clouds ── */
.deco-cloud {
    position: absolute; font-size: 1.8rem; opacity: .5;
    animation: cloudDrift linear infinite;
}
@keyframes cloudDrift {
    from { left:110%; }
    to   { left:-15%; }
}

/* ── Balloon ── */
.balloon-wrap {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: center;
    cursor: pointer;
    animation: riseUp 12s linear forwards;
    transform-origin: bottom center;
    filter: drop-shadow(0 4px 12px rgba(0,0,0,0.25));
    transition: filter .15s;
}
.balloon-wrap:hover  { filter: drop-shadow(0 4px 20px rgba(0,0,0,0.4)) brightness(1.08); }
.balloon-wrap:active { transform: scale(0.88); }

@keyframes riseUp {
    0%   { transform: translateY(0)    rotate(0deg); opacity:1; }
    30%  { transform: translateY(-60px) rotate(3deg); }
    60%  { transform: translateY(-140px) rotate(-3deg); }
    90%  { transform: translateY(-240px) rotate(2deg); opacity:1; }
    100% { transform: translateY(-320px); opacity:0; }
}

.balloon-body {
    width: 72px; height: 86px;
    border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
    background: var(--bc);
    position: relative;
    overflow: hidden;
}
.balloon-shine {
    position: absolute;
    top: 10%; left: 18%;
    width: 28%; height: 35%;
    background: var(--bs);
    border-radius: 50%;
    opacity: .6;
    transform: rotate(-30deg);
}
.balloon-knot {
    width: 10px; height: 10px;
    background: var(--bc);
    clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
    margin-top: -2px;
}
.balloon-string {
    width: 2px; height: 28px;
    background: var(--st);
    opacity: .7;
    border-radius: 1px;
}
.balloon-label {
    position: absolute;
    top: 50%; left: 50%;
    transform: translate(-50%, -50%);
    width: 64px;
    text-align: center;
    font-size: 10px;
    font-weight: 900;
    color: #fff;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
    word-break: break-word;
    line-height: 1.25;
    padding: 2px;
}

/* ── Balloon TransitionGroup ── */
.balloon-group-enter-active { transition: all .4s cubic-bezier(0.34,1.56,0.64,1); }
.balloon-group-enter-from   { opacity:0; transform:scale(.4) translateY(20px); }
.balloon-group-leave-active { transition: all .2s ease; }
.balloon-group-leave-to     { opacity:0; transform:scale(.5); }

/* ── Demo balloons (start screen) ── */
.demo-balloon {
    display: flex; flex-direction: column; align-items: center;
    animation: demoBob 2s ease-in-out infinite;
}
.balloon-body-demo {
    width: 40px; height: 48px;
    border-radius: 50% 50% 50% 50% / 60% 60% 40% 40%;
    background: var(--bc);
    position: relative;
}
.balloon-body-demo::after {
    content:''; position:absolute; top:12%; left:18%; width:28%; height:32%;
    background:var(--bs); border-radius:50%; opacity:.6;
}
@keyframes demoBob {
    0%,100% { transform:translateY(0); }
    50%     { transform:translateY(-10px); }
}

/* ── Feedback ── */
.feedback-card { animation: feedPop .35s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes feedPop {
    from { transform:scale(.6); opacity:0; }
    to   { transform:scale(1);  opacity:1; }
}

/* ── Other animations ── */
.q-appear { animation: qSlide .38s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes qSlide {
    from { opacity:0; transform:translateY(14px) scale(.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}
.result-appear { animation: resultPop .45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(.9); }
    to   { opacity:1; transform:scale(1); }
}
</style>
