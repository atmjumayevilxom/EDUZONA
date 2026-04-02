<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const { playCorrect, playWrong, playComplete } = useGameAudio();

const tiles    = ref([]);
const flipped  = ref(new Set());
const matched  = ref(new Set());
const selected = ref([]);
const locking  = ref(false);
const finished = ref(false);
const moves    = ref(0);
const stars    = ref(0);
const wrongPairs = ref(new Set()); // briefly highlight wrong pair
const particles  = ref([]);

const items = computed(() => props.gameData.items ?? []);

function setup() {
    const arr = [];
    items.value.forEach((item, i) => {
        arr.push({ uid: i * 2,     pairId: i, side: 'front', text: item.front ?? item.word ?? item.text ?? '' });
        arr.push({ uid: i * 2 + 1, pairId: i, side: 'back',  text: item.back  ?? item.hint ?? item.definition ?? '' });
    });
    arr.sort(() => Math.random() - 0.5);
    tiles.value    = arr;
    flipped.value  = new Set();
    matched.value  = new Set();
    selected.value = [];
    locking.value  = false;
    finished.value = false;
    moves.value    = 0;
    stars.value    = 0;
    wrongPairs.value = new Set();
    particles.value  = [];
}
onMounted(setup);

function isOpen(idx)    { return flipped.value.has(idx) || matched.value.has(tiles.value[idx]?.pairId); }
function isMatched(idx) { return matched.value.has(tiles.value[idx]?.pairId); }
function isWrong(idx)   { return wrongPairs.value.has(idx); }

/* ── Particles on match ── */
const COLORS = ['#34d399','#60a5fa','#fbbf24','#f472b6','#a78bfa','#fb923c'];
function spawnMatchParticles(idx) {
    particles.value = Array.from({ length: 12 }, (_, i) => ({
        id: Date.now() + i + idx * 100,
        color: COLORS[i % COLORS.length],
        x: 10 + Math.random() * 80,
        y: 10 + Math.random() * 80,
        dx: (Math.random() - 0.5) * 120,
        dy: -50 - Math.random() * 90,
        size: 5 + Math.random() * 6,
    }));
    setTimeout(() => { particles.value = []; }, 700);
}

function flip(idx) {
    if (locking.value || isOpen(idx)) return;
    if (selected.value.length >= 2) return;

    const f = new Set(flipped.value); f.add(idx);
    flipped.value = f;

    const sel = [...selected.value, idx];
    selected.value = sel;

    if (sel.length === 2) {
        moves.value++;
        locking.value = true;
        const [a, b] = sel;

        if (tiles.value[a].pairId === tiles.value[b].pairId) {
            playCorrect();
            setTimeout(() => {
                spawnMatchParticles(a);
                const m = new Set(matched.value); m.add(tiles.value[a].pairId);
                matched.value = m;
                const ff = new Set(flipped.value); ff.delete(a); ff.delete(b);
                flipped.value = ff;
                selected.value = [];
                locking.value = false;
                if (matched.value.size === items.value.length) {
                    // Calculate stars
                    const perfect = items.value.length;
                    stars.value = moves.value <= perfect ? 3
                                : moves.value <= perfect * 1.6 ? 2 : 1;
                    setTimeout(() => { finished.value = true; playComplete(); }, 500);
                }
            }, 500);
        } else {
            playWrong();
            // Flash wrong pair red
            const wp = new Set([a, b]);
            wrongPairs.value = wp;
            setTimeout(() => {
                const ff = new Set(flipped.value); ff.delete(a); ff.delete(b);
                flipped.value = ff;
                wrongPairs.value = new Set();
                selected.value = [];
                locking.value = false;
            }, 900);
        }
    }
}

const cols = computed(() => {
    const n = tiles.value.length;
    if (n <= 8)  return 4;
    if (n <= 12) return 4;
    return 6;
});
</script>

<template>
    <div class="w-full select-none">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-md mx-auto result-appear">
            <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                <div class="bg-gradient-to-br from-violet-500 to-purple-700 px-10 py-12 text-center text-white">
                    <div class="text-7xl mb-3">🎉</div>
                    <!-- Stars -->
                    <div class="flex justify-center gap-2 mb-3">
                        <span v-for="i in 3" :key="i"
                            :class="['text-3xl transition-all', i <= stars ? 'star-glow' : 'opacity-30 grayscale']">⭐</span>
                    </div>
                    <div class="text-5xl font-black mb-1">Ajoyib!</div>
                    <div class="text-white/75 mt-2">{{ moves }} ta harakat · {{ items.length }} juft</div>
                </div>
                <div class="p-5">
                    <button @click="setup"
                        class="w-full bg-violet-600 hover:bg-violet-700 active:scale-95 text-white font-black py-4 rounded-2xl transition-all text-lg shadow-lg shadow-violet-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE ══ -->
        <div v-else class="w-full">

            <!-- HUD -->
            <div class="flex items-center gap-3 mb-3">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-violet-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: items.length ? (matched.size / items.length * 100) + '%' : '0%' }"></div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <div class="bg-violet-100 text-violet-700 text-xs font-black px-3 py-1 rounded-full">
                        {{ matched.size }}/{{ items.length }} juft
                    </div>
                    <div class="bg-slate-100 text-slate-600 text-xs font-black px-3 py-1 rounded-full">
                        {{ moves }} harakat
                    </div>
                </div>
            </div>

            <!-- Particles overlay -->
            <div class="relative">
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

                <!-- Grid -->
                <div :class="['grid gap-2 sm:gap-3']"
                    :style="{ gridTemplateColumns: `repeat(${cols}, 1fr)` }">
                    <div
                        v-for="(tile, idx) in tiles"
                        :key="tile.uid"
                        class="tile-wrap"
                        :style="{ animationDelay: (idx * 35) + 'ms' }"
                        @click="flip(idx)"
                    >
                        <div :class="[
                            'tile-inner',
                            isOpen(idx) ? 'tile-flipped' : '',
                            isMatched(idx) ? 'tile-matched' : '',
                        ]">
                            <!-- Back face (hidden) -->
                            <div :class="['tile-face tile-back', isWrong(idx) ? 'tile-wrong-shake' : '']">
                                <span class="text-white/80 text-2xl font-black">?</span>
                            </div>
                            <!-- Front face (shown when flipped) -->
                            <div :class="[
                                'tile-face tile-front',
                                isMatched(idx) ? 'tile-front-matched' : 'tile-front-open',
                            ]">
                                <span class="tile-text">{{ tile.text }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Instruction -->
            <p class="text-center text-slate-400 text-xs mt-4 font-medium">
                Bir juft kartani toping — ketma-ket 2 ta oching 🃏
            </p>
        </div>
    </div>
</template>

<style scoped>
/* ── Tile wrapper ── */
.tile-wrap {
    aspect-ratio: 1;
    cursor: pointer;
    perspective: 600px;
    animation: tileAppear 0.4s cubic-bezier(0.34,1.56,0.64,1) both;
}
@keyframes tileAppear {
    from { opacity: 0; transform: scale(0.7) rotate(-5deg); }
    to   { opacity: 1; transform: scale(1) rotate(0deg); }
}

/* ── Flip inner ── */
.tile-inner {
    position: relative;
    width: 100%; height: 100%;
    transform-style: preserve-3d;
    transition: transform 0.45s cubic-bezier(0.4,0,0.2,1);
    border-radius: 14px;
}
.tile-flipped { transform: rotateY(180deg); }

/* ── Faces ── */
.tile-face {
    position: absolute;
    inset: 0;
    border-radius: 14px;
    backface-visibility: hidden;
    -webkit-backface-visibility: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 6px;
    overflow: hidden;
}

/* Back face (default visible) */
.tile-back {
    background: linear-gradient(140deg, #4f46e5, #7c3aed);
    border: 1.5px solid rgba(129,140,248,0.3);
    box-shadow: 0 4px 16px rgba(79,70,229,0.25);
    cursor: pointer;
    transition: filter 0.15s, transform 0.15s;
}
.tile-wrap:hover .tile-back {
    filter: brightness(1.12);
    box-shadow: 0 8px 24px rgba(79,70,229,0.4);
}
.tile-wrap:active .tile-back { transform: scale(0.93); }

/* Front face (flipped) */
.tile-front {
    transform: rotateY(180deg);
}
.tile-front-open {
    background: #f0f4ff;
    border: 2px solid #818cf8;
    box-shadow: 0 6px 20px rgba(129,140,248,0.3);
}
.tile-front-matched {
    background: linear-gradient(140deg, #ecfdf5, #d1fae5);
    border: 2px solid #34d399;
    box-shadow: 0 6px 24px rgba(52,211,153,0.35), 0 0 0 3px rgba(52,211,153,0.15);
    animation: matchGlow 0.5s cubic-bezier(0.34,1.56,0.64,1);
}
@keyframes matchGlow {
    0%   { box-shadow: 0 0 0 0 rgba(52,211,153,0.7); transform: rotateY(180deg) scale(1); }
    50%  { box-shadow: 0 0 0 12px rgba(52,211,153,0); transform: rotateY(180deg) scale(1.06); }
    100% { transform: rotateY(180deg) scale(1); }
}

.tile-text {
    font-size: clamp(8px, 2vw, 12px);
    font-weight: 700;
    text-align: center;
    word-break: break-word;
    line-height: 1.3;
    color: #1e1b4b;
}
.tile-front-matched .tile-text { color: #065f46; }

/* Wrong shake */
.tile-wrong-shake { animation: wrongShake 0.4s ease; background: linear-gradient(140deg,#7f1d1d,#b91c1c) !important; }
@keyframes wrongShake {
    0%,100% { transform: translateX(0); }
    20% { transform: translateX(-5px); }
    40% { transform: translateX(5px); }
    60% { transform: translateX(-4px); }
    80% { transform: translateX(4px); }
}

/* ── Particles ── */
.particle {
    position: absolute; border-radius: 50%;
    animation: particleBurst 0.7s ease-out forwards;
    pointer-events: none;
}
@keyframes particleBurst {
    0%   { transform: translate(0,0) scale(1); opacity: 1; }
    100% { transform: translate(var(--dx), var(--dy)) scale(0); opacity: 0; }
}

/* ── Stars ── */
.star-glow { animation: starPop 0.5s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes starPop {
    from { transform: scale(0) rotate(-45deg); opacity: 0; }
    to   { transform: scale(1) rotate(0deg); opacity: 1; }
}
.star-glow:nth-child(1) { animation-delay: 0.1s; }
.star-glow:nth-child(2) { animation-delay: 0.25s; }
.star-glow:nth-child(3) { animation-delay: 0.4s; }

/* ── Result ── */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity: 0; transform: scale(0.9); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
