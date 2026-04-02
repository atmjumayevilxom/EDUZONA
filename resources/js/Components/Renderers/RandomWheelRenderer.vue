<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const spinning  = ref(false);
const rotation  = ref(0);
const result    = ref(null);
const history   = ref([]);
const removed   = ref([]);

const items = ref([...(props.gameData.items ?? [])]);
const n     = computed(() => items.value.length || 1);

function removeResult() {
    if (!result.value || spinning.value) return;
    const idx = items.value.findIndex(it => it.text === result.value);
    if (idx !== -1) {
        removed.value.push(items.value[idx]);
        items.value.splice(idx, 1);
    }
    result.value = null;
}

function restoreAll() {
    items.value.push(...removed.value);
    removed.value = [];
    result.value = null;
}

const CX = 240, CY = 240, R = 220;

const COLORS = [
    '#6366f1','#8b5cf6','#ec4899','#f43f5e','#f97316',
    '#eab308','#22c55e','#14b8a6','#3b82f6','#a855f7',
    '#06b6d4','#84cc16','#ef4444','#f59e0b','#10b981',
];

function toRad(deg) { return (deg * Math.PI) / 180; }
function getColor(i) { return COLORS[i % COLORS.length]; }

function slicePath(i) {
    const startAngle = (i * 360) / n.value - 90;
    const endAngle   = ((i + 1) * 360) / n.value - 90;
    const s = toRad(startAngle), e = toRad(endAngle);
    const x1 = CX + R * Math.cos(s), y1 = CY + R * Math.sin(s);
    const x2 = CX + R * Math.cos(e), y2 = CY + R * Math.sin(e);
    const large = (endAngle - startAngle) > 180 ? 1 : 0;
    return `M${CX},${CY} L${x1},${y1} A${R},${R},0,${large},1,${x2},${y2} Z`;
}

// Place text at 62% radius, rotated along slice bisector
function textPos(i) {
    const tr = R * 0.62;
    const angle = ((i + 0.5) * 360) / n.value - 90;
    return {
        x: CX + tr * Math.cos(toRad(angle)),
        y: CY + tr * Math.sin(toRad(angle)),
        rotate: ((i + 0.5) * 360) / n.value,
    };
}

// Font size scales with slice size
const fontSize = computed(() => {
    const count = n.value;
    if (count <= 4)  return 20;
    if (count <= 6)  return 17;
    if (count <= 8)  return 15;
    if (count <= 10) return 13;
    if (count <= 14) return 11;
    return 9;
});

// Arc-width at text radius determines max chars
// arc = 2π * R * 0.62 * (1/n) ; each char ~fontSize*0.6px wide
const maxChars = computed(() => {
    const arcWidth = 2 * Math.PI * R * 0.62 * (1 / n.value);
    const charWidth = fontSize.value * 0.62;
    return Math.max(4, Math.floor(arcWidth / charWidth));
});

// Split into max 2 lines, hard-truncate with "…" if still too long
function splitText(text) {
    const t = (text ?? '').trim();
    const max = maxChars.value;
    if (t.length <= max) return [t];
    // Try word-boundary split
    const mid = Math.ceil(t.length / 2);
    let spaceIdx = t.lastIndexOf(' ', mid);
    if (spaceIdx < 1) spaceIdx = t.indexOf(' ', mid);
    if (spaceIdx > 0) {
        const l1 = t.slice(0, spaceIdx);
        const l2 = t.slice(spaceIdx + 1);
        return [
            l1.length > max ? l1.slice(0, max - 1) + '…' : l1,
            l2.length > max ? l2.slice(0, max - 1) + '…' : l2,
        ];
    }
    return [t.slice(0, max - 1) + '…'];
}

const lineSpacing = computed(() => fontSize.value * 1.3);

// G'ildarak uchun BGM faqat aylanish vaqtida chaladi
onMounted(() => audio.stop());
onUnmounted(() => audio.stop());

function spin() {
    if (spinning.value || n.value === 0) return;
    spinning.value = true;
    result.value = null;

    // Aylanish boshlanganida musiqa ishga tushsin
    audio.play('random_wheel');

    const extra = 1800 + Math.random() * 1800;
    const finalRotation = rotation.value + extra;
    rotation.value = finalRotation;

    setTimeout(() => {
        spinning.value = false;
        const normalized = ((finalRotation % 360) + 360) % 360;
        const pointerAngle = (360 - normalized + 270) % 360;
        const index = Math.floor(pointerAngle / (360 / n.value)) % n.value;
        result.value = items.value[index]?.text ?? '';
        history.value.unshift(result.value);
        if (history.value.length > 5) history.value.pop();
        // Aylanish to'xtaganda musiqa o'chib, natija ovozi chalinadi
        audio.stop();
        audio.playCorrect();
    }, 4000);
}
</script>

<template>
    <div class="w-full">
        <div class="bg-white rounded-2xl shadow-sm border p-5">
            <div class="flex flex-col items-center">
                <!-- Pointer + wheel wrapper -->
                <div class="relative w-full max-w-xl mx-auto mb-6">
                    <!-- Pointer triangle -->
                    <div class="absolute top-0 left-1/2 -translate-x-1/2 -translate-y-1 z-10">
                        <div class="w-0 h-0"
                            style="border-left:16px solid transparent;border-right:16px solid transparent;border-top:32px solid #1e1b4b;filter:drop-shadow(0 4px 8px rgba(0,0,0,0.4))">
                        </div>
                    </div>

                    <!-- SVG wheel — responsive via viewBox -->
                    <svg
                        viewBox="0 0 480 480"
                        class="w-full h-auto drop-shadow-xl"
                        :style="`transform: rotate(${rotation}deg); transition: transform ${spinning ? '4s' : '0s'} cubic-bezier(0.17,0.67,0.12,0.99);`"
                    >
                        <defs>
                            <!-- Clip each text to its own slice -->
                            <clipPath v-for="(_, i) in items" :key="'cp'+i" :id="`cp-${i}`">
                                <path :d="slicePath(i)"/>
                            </clipPath>
                        </defs>

                        <!-- Outer rings -->
                        <circle :cx="CX" :cy="CY" :r="R + 12" fill="none" stroke="#e5e7eb" stroke-width="8"/>
                        <circle :cx="CX" :cy="CY" :r="R + 5"  fill="none" stroke="white"   stroke-width="4"/>

                        <!-- Slices -->
                        <g v-for="(item, i) in items" :key="i">
                            <path :d="slicePath(i)" :fill="getColor(i)" stroke="white" stroke-width="2"/>

                            <!-- Text clipped to slice — no overflow possible -->
                            <g :clip-path="`url(#cp-${i})`"
                               :transform="`rotate(${textPos(i).rotate}, ${textPos(i).x}, ${textPos(i).y})`">
                                <text
                                    :x="textPos(i).x"
                                    :y="splitText(item.text).length === 1 ? textPos(i).y : textPos(i).y - lineSpacing / 2"
                                    text-anchor="middle"
                                    dominant-baseline="middle"
                                    fill="white"
                                    :font-size="fontSize"
                                    font-weight="700"
                                    font-family="system-ui, sans-serif"
                                    style="filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6))"
                                >{{ splitText(item.text)[0] }}</text>
                                <text
                                    v-if="splitText(item.text).length > 1"
                                    :x="textPos(i).x"
                                    :y="textPos(i).y + lineSpacing / 2"
                                    text-anchor="middle"
                                    dominant-baseline="middle"
                                    fill="white"
                                    :font-size="fontSize"
                                    font-weight="700"
                                    font-family="system-ui, sans-serif"
                                    style="filter: drop-shadow(0 1px 3px rgba(0,0,0,0.6))"
                                >{{ splitText(item.text)[1] }}</text>
                            </g>
                        </g>

                        <!-- Center cap -->
                        <circle :cx="CX" :cy="CY" r="34" fill="white" stroke="#e5e7eb" stroke-width="4"/>
                        <circle :cx="CX" :cy="CY" r="18" fill="#6366f1"/>
                        <circle :cx="CX" :cy="CY" r="7"  fill="white"/>
                    </svg>
                </div>

                <!-- Buttons row -->
                <div class="flex items-center gap-3">
                    <!-- Spin -->
                    <button
                        @click="spin"
                        :disabled="spinning || items.length === 0"
                        class="bg-indigo-600 hover:bg-indigo-700 disabled:bg-indigo-300 text-white font-bold px-10 py-4 rounded-2xl transition-all shadow-xl shadow-indigo-500/40 text-lg"
                    >
                        {{ spinning ? '🌀 Aylanmoqda...' : '🎰 Aylantirish!' }}
                    </button>

                    <!-- Remove last result from wheel -->
                    <button
                        @click="removeResult"
                        :disabled="!result || spinning"
                        title="Tanlangan elementni g'ildirakdan olib tashlash"
                        class="flex flex-col items-center gap-0.5 bg-red-50 hover:bg-red-100 disabled:opacity-30 disabled:cursor-not-allowed border-2 border-red-200 hover:border-red-300 text-red-600 font-bold px-5 py-3 rounded-2xl transition-all text-sm"
                    >
                        <span class="text-xl leading-none">🗑️</span>
                        <span class="text-[10px] font-semibold leading-none whitespace-nowrap">Olib tashlash</span>
                    </button>
                </div>

                <!-- Removed items count + restore -->
                <div v-if="removed.length" class="mt-3 flex items-center gap-2 text-xs text-slate-500">
                    <span class="bg-red-100 text-red-600 font-bold px-2.5 py-1 rounded-full">
                        −{{ removed.length }} ta olib tashlandi
                    </span>
                    <button @click="restoreAll"
                        class="text-indigo-500 hover:text-indigo-700 font-semibold hover:underline transition">
                        ↩ Hammasini qaytarish
                    </button>
                </div>
            </div>

            <!-- Empty wheel message -->
            <div v-if="items.length === 0"
                class="mt-4 bg-amber-50 border border-amber-200 rounded-2xl p-5 text-center">
                <div class="text-3xl mb-2">🎡</div>
                <p class="text-sm font-semibold text-amber-700">G'ildarak bo'sh!</p>
                <button @click="restoreAll"
                    class="mt-3 text-sm bg-amber-500 hover:bg-amber-600 text-white font-bold px-5 py-2 rounded-xl transition">
                    ↩ Hammasini qaytarish
                </button>
            </div>

            <!-- Result -->
            <Transition
                enter-active-class="transition-all duration-500"
                enter-from-class="opacity-0 scale-90"
                enter-to-class="opacity-100 scale-100"
            >
                <div v-if="result && !spinning" class="mt-6 bg-indigo-50 border-2 border-indigo-300 rounded-2xl p-5 text-center">
                    <div class="text-xs text-indigo-400 uppercase tracking-widest mb-1">Tanlandi</div>
                    <div class="text-2xl font-extrabold text-indigo-700">🎯 {{ result }}</div>
                    <p class="text-xs text-slate-400 mt-2">Olib tashlash uchun 🗑️ tugmasini bosing</p>
                </div>
            </Transition>

            <!-- History -->
            <div v-if="history.length > 1" class="mt-4">
                <div class="text-xs text-gray-400 font-medium mb-2">Oldingi natijalar:</div>
                <div class="flex flex-wrap gap-2">
                    <span v-for="(h, i) in history.slice(1)" :key="i"
                        :style="`animation-delay: ${i * 40}ms`"
                        class="text-xs bg-gray-100 text-gray-600 px-3 py-1 rounded-full chip-appear">
                        {{ h }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.chip-appear { animation: chipPop 0.3s ease both; }
@keyframes chipPop {
    from { opacity: 0; transform: scale(0.7); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
