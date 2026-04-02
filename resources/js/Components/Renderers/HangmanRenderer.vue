<script setup>
import { ref, computed, watch } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const wordIndex   = ref(0);
const guessed     = ref([]);
const score       = ref(0);
const finished    = ref(false);
const shaking     = ref(false);
const celebrating = ref(false);
const particles   = ref([]); // { id, x, y, color, char }
let   pid = 0;

const items   = computed(() => props.gameData.items ?? []);
const total   = computed(() => items.value.length);
const current = computed(() => items.value[wordIndex.value]);
const word    = computed(() => (current.value?.word ?? '').toUpperCase());

const ALPHABET  = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ'.split('');
const MAX_WRONG = 6;

// Klaviaturada bo'lmagan belgilar (apostrof, tire va h.k.) avtomatik ochiq hisoblanadi
function isAutoRevealed(letter) {
    return letter === ' ' || !ALPHABET.includes(letter);
}

const wrongGuesses = computed(() => guessed.value.filter(l => !word.value.includes(l)));
const wrongCount   = computed(() => wrongGuesses.value.length);
const wordComplete = computed(() => word.value.length > 0 && word.value.split('').every(l => isAutoRevealed(l) || guessed.value.includes(l)));
const isLost       = computed(() => wrongCount.value >= MAX_WRONG);

// Character stages
const STAGES = [
    { emoji: '😄', mood: 'happy',   bg: 'from-sky-400 to-blue-500' },
    { emoji: '😅', mood: 'nervous', bg: 'from-sky-400 to-blue-500' },
    { emoji: '😨', mood: 'scared',  bg: 'from-amber-400 to-orange-500' },
    { emoji: '😰', mood: 'scared',  bg: 'from-amber-500 to-orange-600' },
    { emoji: '😱', mood: 'panic',   bg: 'from-red-400 to-rose-600' },
    { emoji: '😵', mood: 'panic',   bg: 'from-red-500 to-red-700' },
    { emoji: '💀', mood: 'dead',    bg: 'from-slate-600 to-slate-800' },
];
const stage    = computed(() => STAGES[Math.min(wrongCount.value, 6)]);
const charBg   = computed(() => stage.value.bg);
const charMood = computed(() => {
    if (celebrating.value) return 'celebrate';
    if (isLost.value)      return 'dead';
    return stage.value.mood;
});

function spawnParticles(correct) {
    const chars = correct
        ? ['⭐','✨','🌟','💫','👏']
        : ['💔','❌','😤','💢'];
    for (let i = 0; i < 6; i++) {
        const p = {
            id:    pid++,
            x:     30 + Math.random() * 40,
            y:     20 + Math.random() * 40,
            char:  chars[Math.floor(Math.random() * chars.length)],
            angle: (Math.random() - 0.5) * 160,
            dist:  40 + Math.random() * 50,
        };
        particles.value = [...particles.value, p];
        setTimeout(() => {
            particles.value = particles.value.filter(x => x.id !== p.id);
        }, 900);
    }
}

function guess(letter) {
    if (guessed.value.includes(letter) || wordComplete.value || isLost.value || isAutoRevealed(letter)) return;
    const wasCorrect = word.value.includes(letter);
    guessed.value = [...guessed.value, letter];

    if (wasCorrect) {
        playCorrect();
        spawnParticles(true);
        if (wordComplete.value) {
            score.value++;
            celebrating.value = true;
            setTimeout(() => { celebrating.value = false; advanceOrFinish(); }, 1400);
        }
    } else {
        playWrong();
        spawnParticles(false);
        shaking.value = true;
        setTimeout(() => { shaking.value = false; }, 500);
        if (isLost.value) {
            setTimeout(() => advanceOrFinish(), 1800);
        }
    }
}

function advanceOrFinish() {
    if (wordIndex.value < total.value - 1) {
        wordIndex.value++;
        guessed.value = [];
    } else {
        finished.value = true;
        playComplete();
    }
}

function restart() {
    wordIndex.value = 0;
    guessed.value   = [];
    score.value     = 0;
    finished.value  = false;
}

const percent = computed(() => total.value ? Math.round((score.value / total.value) * 100) : 0);
const ROWS    = ['QWERTYUIOP', 'ASDFGHJKL', 'ZXCVBNM'];
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-xl mx-auto">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    percent >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : percent >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-4">{{ percent >= 80 ? '🎉' : percent >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-2">{{ percent }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ score }} / {{ total }} so'z topildi</div>
                </div>
                <div class="p-8 text-center">
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else-if="current" class="w-full">

            <!-- Progress -->
            <div class="flex items-center gap-3 mb-4">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: (wordIndex / total * 100) + '%' }"></div>
                </div>
                <span class="text-sm font-bold text-slate-400 shrink-0">{{ wordIndex + 1 }}/{{ total }}</span>
                <span class="bg-indigo-100 text-indigo-700 text-xs font-black px-3 py-1 rounded-full shrink-0">{{ score }} ball</span>
            </div>

            <!-- Main card -->
            <div :key="wordIndex" :class="['bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden word-appear', shaking ? 'shake' : '']">

                <!-- ── Character zone ── -->
                <div :class="['relative overflow-hidden transition-all duration-700 bg-gradient-to-br', charBg]"
                    style="min-height: 200px;">

                    <!-- Animated background dots -->
                    <div class="stars-bg absolute inset-0 opacity-20"></div>

                    <!-- Rope (only shown when wrongCount > 0) -->
                    <Transition enter-active-class="transition-all duration-400" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                        <div v-if="wrongCount > 0"
                            class="absolute top-0 left-1/2 -translate-x-1/2 w-1 bg-yellow-200/60 rounded-b-full"
                            :style="{ height: isLost ? '70px' : '50px', transition: 'height 0.4s' }">
                        </div>
                    </Transition>

                    <!-- Character emoji -->
                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-2">
                        <div :class="['character-wrap', `mood-${charMood}`]"
                            :style="wrongCount > 0 ? 'margin-top: 30px' : ''">
                            <div class="text-7xl sm:text-8xl leading-none filter drop-shadow-lg">
                                {{ celebrating ? '🥳' : stage.emoji }}
                            </div>
                        </div>

                        <!-- Win/Lose message -->
                        <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 scale-75" enter-to-class="opacity-100 scale-100">
                            <div v-if="celebrating"
                                class="bg-white/20 backdrop-blur-sm rounded-2xl px-5 py-2 text-white font-black text-lg">
                                🎉 Barakalla!
                            </div>
                            <div v-else-if="isLost"
                                class="bg-black/20 backdrop-blur-sm rounded-2xl px-4 py-2 text-white font-bold text-sm">
                                So'z: <strong>{{ word }}</strong>
                            </div>
                        </Transition>
                    </div>

                    <!-- Floating particles -->
                    <TransitionGroup tag="div" name="particle">
                        <div v-for="p in particles" :key="p.id"
                            class="particle absolute pointer-events-none text-xl leading-none"
                            :style="{
                                left: p.x + '%',
                                top:  p.y + '%',
                                '--dx': (Math.sin(p.angle * Math.PI/180) * p.dist) + 'px',
                                '--dy': (-p.dist * 0.8) + 'px',
                            }">
                            {{ p.char }}
                        </div>
                    </TransitionGroup>

                    <!-- Lives (hearts) top-right -->
                    <div class="absolute top-3 right-3 flex gap-1">
                        <span v-for="i in MAX_WRONG" :key="i"
                            :class="['text-lg transition-all duration-300', i <= wrongCount ? 'grayscale opacity-40' : '']">
                            {{ i <= wrongCount ? '🖤' : '❤️' }}
                        </span>
                    </div>

                    <!-- Hint bottom-left -->
                    <div class="absolute bottom-3 left-3 right-16">
                        <div class="text-white/70 text-xs font-medium">💡 {{ current.hint }}</div>
                    </div>
                </div>

                <!-- ── Word tiles ── -->
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50">
                    <div class="flex flex-wrap gap-2 justify-center">
                        <template v-for="(letter, li) in word.split('')" :key="li">
                            <div v-if="letter === ' '" class="w-3"></div>
                            <!-- Apostrof yoki maxsus belgi — doim ochiq ko'rinadi, kichik -->
                            <div v-else-if="isAutoRevealed(letter)"
                                class="w-5 h-11 flex items-end justify-center pb-1 font-black text-base text-slate-500">
                                {{ letter }}
                            </div>
                            <div v-else :class="[
                                'w-9 h-11 rounded-xl border-2 flex items-center justify-center font-black text-lg transition-all duration-300',
                                guessed.includes(letter)
                                    ? wordComplete
                                        ? 'bg-emerald-100 border-emerald-400 text-emerald-700 letter-pop'
                                        : 'bg-indigo-100 border-indigo-400 text-indigo-700 letter-pop'
                                    : isLost
                                        ? 'bg-red-50 border-red-300 text-red-400'
                                        : 'bg-white border-slate-200 text-transparent'
                            ]">
                                {{ (guessed.includes(letter) || isLost) ? letter : '?' }}
                            </div>
                        </template>
                    </div>
                    <div class="text-center mt-2 text-xs text-slate-400 font-semibold">
                        {{ word.length }} ta harf · {{ MAX_WRONG - wrongCount }} urinish qoldi
                    </div>
                </div>

                <!-- ── QWERTY Keyboard ── -->
                <div class="p-4 sm:p-5">
                    <div class="space-y-2">
                        <div v-for="(row, ri) in ROWS" :key="ri" class="flex justify-center gap-1.5">
                            <button
                                v-for="(letter, li) in row.split('')"
                                :key="letter"
                                @click="guess(letter)"
                                :disabled="guessed.includes(letter) || wordComplete || isLost"
                                :style="`animation-delay: ${(ri * 10 + li) * 22}ms`"
                                :class="[
                                    'w-8 h-8 sm:w-9 sm:h-9 md:w-10 md:h-10 rounded-xl text-xs sm:text-sm font-black transition-all duration-150 key-appear',
                                    guessed.includes(letter)
                                        ? word.includes(letter)
                                            ? 'bg-emerald-500 text-white border-2 border-emerald-600 scale-95'
                                            : 'bg-slate-200 text-slate-400 border-2 border-slate-200 opacity-40'
                                        : 'bg-white text-slate-700 border-2 border-slate-200 shadow-sm hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 cursor-pointer active:scale-90',
                                    (wordComplete || isLost) && !guessed.includes(letter) ? 'opacity-20 pointer-events-none' : ''
                                ]"
                            >{{ letter }}</button>
                        </div>
                    </div>
                </div>

            </div><!-- /main card -->
        </div>
    </div>
</template>

<style scoped>
/* ── Card entrance ── */
.word-appear { animation: wordSlide 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes wordSlide {
    from { opacity:0; transform:translateY(18px) scale(0.97); }
    to   { opacity:1; transform:translateY(0) scale(1); }
}

/* ── Key entrance ── */
.key-appear { animation: keyPop 0.3s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes keyPop {
    from { opacity:0; transform:scale(0.5); }
    to   { opacity:1; transform:scale(1); }
}

/* ── Letter tile pop ── */
.letter-pop { animation: lPop 0.35s cubic-bezier(0.34,1.56,0.64,1); }
@keyframes lPop {
    0%  { transform:scale(1); }
    50% { transform:scale(1.35); }
    100%{ transform:scale(1); }
}

/* ── Card shake on wrong ── */
.shake { animation: shake 0.45s ease; }
@keyframes shake {
    0%,100% { transform:translateX(0); }
    15%  { transform:translateX(-7px) rotate(-1deg); }
    30%  { transform:translateX(7px)  rotate(1deg); }
    45%  { transform:translateX(-5px); }
    60%  { transform:translateX(5px); }
    75%  { transform:translateX(-3px); }
    90%  { transform:translateX(3px); }
}

/* ── Character moods ── */
.character-wrap { display:inline-block; }

.mood-happy    { animation: float  2.2s ease-in-out infinite; }
.mood-nervous  { animation: wobble 1.4s ease-in-out infinite; }
.mood-scared   { animation: tremble 0.5s ease-in-out infinite; }
.mood-panic    { animation: tremble 0.28s ease-in-out infinite; }
.mood-dead     { animation: tilt 0.6s ease forwards; filter: grayscale(1); }
.mood-celebrate{ animation: celebrate 0.5s cubic-bezier(0.34,1.56,0.64,1) infinite alternate; }

@keyframes float {
    0%,100% { transform: translateY(0)   rotate(0deg); }
    50%     { transform: translateY(-8px) rotate(3deg); }
}
@keyframes wobble {
    0%,100% { transform: rotate(-5deg); }
    50%     { transform: rotate(5deg); }
}
@keyframes tremble {
    0%,100% { transform: translateX(-3px) rotate(-2deg); }
    50%     { transform: translateX(3px)  rotate(2deg); }
}
@keyframes tilt {
    to { transform: rotate(90deg) scale(0.8); opacity:0.6; }
}
@keyframes celebrate {
    from { transform: scale(1)   rotate(-8deg); }
    to   { transform: scale(1.2) rotate(8deg); }
}

/* ── Particles ── */
.particle {
    animation: particleFly 0.85s ease-out forwards;
}
.particle-enter-active { animation: particleFly 0.85s ease-out forwards; }
.particle-leave-active { opacity: 0; }
@keyframes particleFly {
    0%   { opacity:1; transform: translate(0, 0) scale(1); }
    100% { opacity:0; transform: translate(var(--dx), var(--dy)) scale(0.4); }
}

/* ── Animated bg dots ── */
.stars-bg {
    background-image:
        radial-gradient(circle, rgba(255,255,255,0.8) 1px, transparent 1px),
        radial-gradient(circle, rgba(255,255,255,0.5) 1px, transparent 1px);
    background-size: 30px 30px, 50px 50px;
    background-position: 0 0, 15px 15px;
    animation: starsDrift 8s linear infinite;
}
@keyframes starsDrift {
    from { background-position: 0 0, 15px 15px; }
    to   { background-position: 30px 30px, 45px 45px; }
}

/* ── Result screen ── */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}

.duration-400 { transition-duration: 400ms; }
</style>
