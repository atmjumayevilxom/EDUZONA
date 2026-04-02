<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

/* ── State ── */
const deck     = ref([]);   // remaining cards
const known    = ref([]);   // marked as known
const again    = ref([]);   // marked as "again" → come back
const curIdx   = ref(0);
const flipped  = ref(false);
const finished = ref(false);
const flyDir   = ref(null); // 'right'|'left'
const hoverDir = ref(null);
const particles = ref([]);

const total = computed(() => props.gameData.items?.length ?? 0);
const current = computed(() => deck.value[curIdx.value] ?? null);
const next    = computed(() => deck.value[curIdx.value + 1] ?? null);
const pct     = computed(() => total.value ? Math.round(known.value.length / total.value * 100) : 0);

/* ── Init ── */
function init() {
    deck.value     = [...(props.gameData.items ?? [])];
    known.value    = [];
    again.value    = [];
    curIdx.value   = 0;
    flipped.value  = false;
    finished.value = false;
    flyDir.value   = null;
    hoverDir.value = null;
    particles.value = [];
}
onMounted(init);

/* ── Flip ── */
function flip() {
    if (flyDir.value) return;
    flipped.value = !flipped.value;
    audio.playCorrect();
}

/* ── Particles ── */
const COLORS = ['#34d399','#60a5fa','#f59e0b','#f472b6','#a78bfa'];
function spawnParticles() {
    particles.value = Array.from({ length: 14 }, (_, i) => ({
        id: Date.now() + i,
        color: COLORS[i % COLORS.length],
        x: 35 + Math.random() * 30,
        y: 35 + Math.random() * 30,
        dx: (Math.random() - 0.5) * 140,
        dy: -60 - Math.random() * 100,
        size: 5 + Math.random() * 7,
    }));
    setTimeout(() => { particles.value = []; }, 750);
}

/* ── Card action ── */
function decide(isKnown) {
    if (flyDir.value || !flipped.value) return;
    flyDir.value   = isKnown ? 'right' : 'left';
    hoverDir.value = null;

    if (isKnown) {
        known.value.push(current.value);
        audio.playCorrect();
        spawnParticles();
    } else {
        again.value.push(current.value);
        audio.playWrong();
    }

    setTimeout(() => {
        deck.value.splice(curIdx.value, 1);
        // If we ran out, add "again" cards back
        if (curIdx.value >= deck.value.length) {
            if (again.value.length > 0) {
                deck.value.push(...again.value);
                again.value = [];
                curIdx.value = 0;
            } else {
                finished.value = true;
                audio.playComplete();
                return;
            }
        }
        flipped.value  = false;
        flyDir.value   = null;
    }, 480);
}

/* ── Keyboard ── */
function onKey(e) {
    if (finished.value) return;
    if (e.code === 'Space' || e.key === ' ') { e.preventDefault(); flip(); }
    if (flipped.value && !flyDir.value) {
        if (e.key === 'ArrowRight' || e.key === 'k') decide(true);
        if (e.key === 'ArrowLeft'  || e.key === 'a') decide(false);
    }
}
onMounted(()  => window.addEventListener('keydown', onKey));
onUnmounted(() => window.removeEventListener('keydown', onKey));

/* ── Card CSS ── */
const cardClass = computed(() => {
    if (flyDir.value === 'right') return 'card-fly-right';
    if (flyDir.value === 'left')  return 'card-fly-left';
    if (hoverDir.value === 'right') return 'card-tilt-right';
    if (hoverDir.value === 'left')  return 'card-tilt-left';
    return '';
});
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-md mx-auto">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden result-appear">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-3">{{ pct >= 80 ? '🎉' : pct >= 50 ? '📖' : '💪' }}</div>
                    <div class="text-6xl font-black mb-1">{{ pct }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ known.length }} / {{ total }} karta o'zlashtirildi</div>
                </div>
                <div class="grid grid-cols-2 divide-x divide-slate-100">
                    <div class="px-4 py-4 text-center">
                        <div class="text-2xl font-black text-emerald-600">{{ known.length }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mt-0.5">Bilaman ✅</div>
                    </div>
                    <div class="px-4 py-4 text-center">
                        <div class="text-2xl font-black text-orange-500">{{ total - known.length }}</div>
                        <div class="text-[10px] text-slate-400 font-semibold uppercase tracking-wide mt-0.5">Yana kerak 🔄</div>
                    </div>
                </div>
                <div class="p-5">
                    <button @click="init"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else-if="current" class="w-full max-w-md mx-auto">

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-3 px-1">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-emerald-400 to-indigo-500 rounded-full transition-all duration-500"
                        :style="{ width: pct + '%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ known.length }}/{{ total }}</span>
                <div v-if="again.length > 0" class="bg-orange-100 text-orange-600 text-xs font-black px-2.5 py-1 rounded-full shrink-0">
                    🔄 {{ again.length }}
                </div>
            </div>

            <!-- Card stack -->
            <div class="card-stack relative" style="height:310px">

                <!-- Particles -->
                <div class="absolute inset-0 pointer-events-none overflow-hidden z-50">
                    <div v-for="p in particles" :key="p.id" class="particle"
                        :style="{
                            left: p.x + '%', top: p.y + '%',
                            background: p.color,
                            width: p.size + 'px', height: p.size + 'px',
                            '--dx': p.dx + 'px', '--dy': p.dy + 'px',
                        }">
                    </div>
                </div>

                <!-- Peek card behind -->
                <div v-if="next" class="peek-card absolute inset-x-6 inset-y-4 rounded-3xl"
                    style="background: linear-gradient(140deg,#312e81,#1e1b4b);">
                </div>

                <!-- Flip card wrapper -->
                <div :class="['flip-outer absolute inset-0 cursor-pointer', cardClass]"
                    :key="curIdx"
                    @mouseenter="hoverDir = null"
                    @mouseleave="hoverDir = null"
                    @click="!flipped ? flip() : null"
                    style="perspective: 1000px"
                >
                    <div class="flip-inner" :class="{ flipped: flipped }">
                        <!-- Front -->
                        <div class="flip-face flip-front rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-2xl"
                            style="background: linear-gradient(140deg,#4f46e5,#7c3aed)">
                            <div class="text-violet-300 text-[10px] font-black uppercase tracking-widest mb-4">Savol / So'z</div>
                            <p class="text-white font-bold text-lg sm:text-xl leading-snug break-words w-full">
                                {{ current.front ?? current.word ?? current.text }}
                            </p>
                            <div class="mt-6 text-white/40 text-xs font-medium flex items-center gap-1.5">
                                <span class="inline-block w-5 h-5 border border-white/25 rounded bg-white/10 text-[9px] font-black text-white/50 flex items-center justify-center">SPC</span>
                                bosing
                            </div>
                        </div>
                        <!-- Back -->
                        <div class="flip-face flip-back rounded-3xl p-6 flex flex-col items-center justify-center text-center shadow-2xl"
                            style="background: linear-gradient(140deg,#059669,#0d9488)">
                            <div class="text-emerald-200 text-[10px] font-black uppercase tracking-widest mb-4">Javob / Ta'rif</div>
                            <p class="text-white font-bold text-lg sm:text-xl leading-snug break-words w-full">
                                {{ current.back ?? current.definition ?? current.hint }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Buttons — show only after flip -->
            <Transition
                enter-active-class="transition-all duration-300 ease-out"
                enter-from-class="opacity-0 translate-y-4"
                enter-to-class="opacity-100 translate-y-0"
            >
                <div v-if="flipped && !flyDir" class="grid grid-cols-2 gap-3 mt-3">
                    <button
                        @click="decide(false)"
                        @mouseenter="hoverDir = 'left'"
                        @mouseleave="hoverDir = null"
                        class="action-btn action-again"
                    >
                        <span class="text-2xl">🔄</span>
                        <span class="font-black text-base leading-none">Yana kerak</span>
                        <span class="text-xs opacity-55">Qayta keladi</span>
                        <kbd class="key-hint">← A</kbd>
                    </button>
                    <button
                        @click="decide(true)"
                        @mouseenter="hoverDir = 'right'"
                        @mouseleave="hoverDir = null"
                        class="action-btn action-known"
                    >
                        <span class="text-2xl">✅</span>
                        <span class="font-black text-base leading-none">Bildim!</span>
                        <span class="text-xs opacity-55">Keyingisi</span>
                        <kbd class="key-hint">K →</kbd>
                    </button>
                </div>
                <div v-else-if="!flipped" class="flex justify-center mt-4">
                    <button @click="flip"
                        class="bg-indigo-600 hover:bg-indigo-700 active:scale-95 text-white font-bold px-8 py-3 rounded-2xl transition-all text-sm shadow-lg shadow-indigo-200 flex items-center gap-2">
                        <span>Javobni ko'rish</span>
                        <kbd class="bg-white/20 rounded px-1.5 py-0.5 text-[10px] font-black">SPC</kbd>
                    </button>
                </div>
            </Transition>

            <!-- Keyboard hint -->
            <p v-if="!flipped" class="text-center text-slate-400 text-xs mt-2">
                Kartani bosing yoki <kbd class="kbd">SPACE</kbd> tugmasini bosing
            </p>
        </div>
    </div>
</template>

<style scoped>
/* ── Stack ── */
.card-stack { position: relative; }
.peek-card {
    transform: scale(0.93) translateY(8px);
    opacity: 0.45;
    pointer-events: none;
    border: 1px solid rgba(129,140,248,0.15);
}

/* ── Flip container ── */
.flip-outer {
    transition: transform 0.18s ease, box-shadow 0.18s ease;
    will-change: transform;
}
.flip-inner {
    position: relative;
    width: 100%; height: 100%;
    transform-style: preserve-3d;
    transition: transform 0.55s cubic-bezier(0.4,0,0.2,1);
}
.flip-inner.flipped { transform: rotateY(180deg); }

.flip-face {
    position: absolute;
    inset: 0;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
}
.flip-back { transform: rotateY(180deg); }

/* ── Tilt / fly ── */
.card-tilt-right { transform: rotate(4deg) translateX(6px) !important; }
.card-tilt-left  { transform: rotate(-4deg) translateX(-6px) !important; }
.card-fly-right  { animation: flyRight 0.48s cubic-bezier(0.5,-0.3,1,0.5) forwards; }
.card-fly-left   { animation: flyLeft  0.48s cubic-bezier(0.5,-0.3,1,0.5) forwards; }
@keyframes flyRight { to { transform: translateX(130%) rotate(20deg); opacity: 0; } }
@keyframes flyLeft  { to { transform: translateX(-130%) rotate(-20deg); opacity: 0; } }

/* ── Appear ── */
.flip-outer { animation: cardAppear 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes cardAppear {
    from { opacity: 0; transform: scale(0.9) translateY(14px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}

/* ── Action buttons ── */
.action-btn {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    gap: 2px;
    padding: 15px 12px;
    border-radius: 18px;
    border: 2.5px solid;
    cursor: pointer;
    transition: all 0.15s;
    position: relative;
}
.action-btn:active { transform: scale(0.95); }

.action-again {
    background: rgba(249,115,22,0.1); border-color: rgba(253,186,116,0.4);
    color: #fdba74;
    box-shadow: 0 4px 16px rgba(249,115,22,0.12);
}
.action-again:hover {
    background: rgba(249,115,22,0.22); border-color: #fb923c;
    transform: translateY(-3px) rotate(-2deg);
    box-shadow: 0 10px 28px rgba(249,115,22,0.22);
}

.action-known {
    background: rgba(16,185,129,0.1); border-color: rgba(52,211,153,0.4);
    color: #6ee7b7;
    box-shadow: 0 4px 16px rgba(16,185,129,0.12);
}
.action-known:hover {
    background: rgba(16,185,129,0.22); border-color: #34d399;
    transform: translateY(-3px) rotate(2deg);
    box-shadow: 0 10px 28px rgba(16,185,129,0.22);
}

.key-hint {
    position: absolute; top: 5px; right: 8px;
    font-size: 9px; font-weight: 800; opacity: 0.3;
    font-family: monospace;
}

/* ── Particles ── */
.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.75s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx), var(--dy)) scale(0); opacity: 0; }
}

/* ── KBD ── */
kbd.kbd {
    background: rgba(0,0,0,0.07);
    border: 1px solid rgba(0,0,0,0.1);
    border-radius: 3px;
    padding: 1px 5px;
    font-size: 10px; font-family: monospace;
}

/* ── Result ── */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
