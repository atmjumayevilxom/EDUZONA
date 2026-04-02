<script setup>
import { ref, computed } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const boxes    = computed(() => props.gameData.items ?? props.gameData.boxes ?? []);
const revealed = ref([]);   // array of indices (reactive)
const picking  = ref(null); // index being opened
const score    = ref(0);
const winCount = ref(0);
const loseCount = ref(0);
const done     = ref(false);
const popup    = ref(null); // { result: 'win'|'lose', label }

function isWinner(box) { return box.is_winner ?? (box.result === 'win') ?? false; }
function isRevealed(idx) { return revealed.value.includes(idx); }

function pick(idx) {
    if (isRevealed(idx) || picking.value !== null || done.value) return;
    picking.value = idx;
    const box = boxes.value[idx];
    const win = isWinner(box);

    popup.value = { result: win ? 'win' : 'lose', label: box.label ?? box.hidden_text ?? box.text ?? '' };
    if (win) { audio.playCorrect(); } else { audio.playWrong(); }

    setTimeout(() => {
        revealed.value = [...revealed.value, idx];
        if (win) { score.value += 100; winCount.value++; }
        else     { loseCount.value++; }
        picking.value = null;
        popup.value   = null;
        if (revealed.value.length >= boxes.value.length) {
            done.value = true;
            audio.playComplete();
        }
    }, 1300);
}

function restart() {
    revealed.value = [];
    picking.value  = null;
    score.value    = 0;
    winCount.value = 0;
    loseCount.value = 0;
    done.value     = false;
    popup.value    = null;
}

const remaining = computed(() => boxes.value.length - revealed.value.length);
</script>

<template>
    <div class="w-full">

        <!-- HUD -->
        <div class="flex items-center gap-2 mb-4">
            <div class="flex-1 flex items-center gap-2">
                <div class="bg-emerald-500/15 border border-emerald-400/30 text-emerald-600 text-xs font-black px-3 py-1.5 rounded-full flex items-center gap-1">
                    🏆 {{ winCount }}
                </div>
                <div class="bg-red-500/15 border border-red-400/30 text-red-600 text-xs font-black px-3 py-1.5 rounded-full flex items-center gap-1">
                    💀 {{ loseCount }}
                </div>
            </div>
            <div class="bg-amber-100 text-amber-700 text-xs font-black px-3 py-1.5 rounded-full">
                {{ score }} ball
            </div>
            <div class="bg-slate-100 text-slate-500 text-xs font-bold px-3 py-1.5 rounded-full">
                {{ remaining }} qoldi
            </div>
        </div>

        <!-- Done screen -->
        <div v-if="done" class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden result-appear">
            <div :class="[
                'px-10 py-12 text-center text-white',
                winCount >= loseCount
                    ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : 'bg-gradient-to-br from-slate-600 to-slate-800'
            ]">
                <div class="text-7xl mb-4">{{ winCount >= loseCount ? '🏆' : '😔' }}</div>
                <div class="text-5xl font-black mb-2">{{ score }} ball</div>
                <div class="text-white/80 text-lg font-semibold">
                    {{ winCount }} ta yutuq · {{ loseCount }} ta mag'lubiyat
                </div>
            </div>
            <div class="p-6 text-center">
                <button @click="restart"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-indigo-200">
                    🔄 Qayta boshlash
                </button>
            </div>
        </div>

        <!-- Boxes grid -->
        <div v-else>

            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-3">
                <button
                    v-for="(box, idx) in boxes"
                    :key="idx"
                    @click="pick(idx)"
                    :disabled="isRevealed(idx) || picking !== null"
                    :style="`animation-delay: ${idx * 40}ms`"
                    :class="['box-btn option-appear', isRevealed(idx)
                        ? (isWinner(box) ? 'box-win' : 'box-lose')
                        : picking === idx ? 'box-opening'
                        : 'box-closed']"
                >
                    <!-- Closed -->
                    <template v-if="!isRevealed(idx) && picking !== idx">
                        <div class="box-lid"></div>
                        <div class="box-body-inner">
                            <span class="text-2xl">🎁</span>
                            <span class="box-num">{{ idx + 1 }}</span>
                        </div>
                    </template>

                    <!-- Opening animation -->
                    <template v-else-if="picking === idx">
                        <div class="text-3xl animate-bounce">❓</div>
                    </template>

                    <!-- Revealed -->
                    <template v-else>
                        <div class="text-2xl mb-1">{{ isWinner(box) ? '🏆' : '💀' }}</div>
                        <div class="text-[10px] font-bold leading-tight px-1 text-center line-clamp-2">
                            {{ box.label ?? box.hidden_text ?? box.text ?? '' }}
                        </div>
                        <div v-if="isWinner(box)" class="mt-1 text-[10px] font-black text-emerald-600">+100</div>
                    </template>
                </button>
            </div>
        </div>

        <!-- Result popup overlay -->
        <Transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 scale-75"
            enter-to-class="opacity-100 scale-100"
            leave-active-class="transition-all duration-200"
            leave-from-class="opacity-100 scale-100"
            leave-to-class="opacity-0 scale-110"
        >
            <div v-if="popup" class="fixed inset-0 z-50 flex items-center justify-center pointer-events-none">
                <div :class="[
                    'rounded-3xl px-16 py-10 text-center shadow-2xl text-white popup-card',
                    popup.result === 'win'
                        ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                        : 'bg-gradient-to-br from-red-500 to-rose-700'
                ]">
                    <div class="text-6xl mb-3">{{ popup.result === 'win' ? '🎉' : '💀' }}</div>
                    <div class="text-3xl font-black mb-1">
                        {{ popup.result === 'win' ? 'Yutdingiz!' : 'Yutqazdingiz!' }}
                    </div>
                    <div v-if="popup.label" class="text-white/80 text-base mt-1">{{ popup.label }}</div>
                    <div v-if="popup.result === 'win'" class="text-xl font-black text-yellow-300 mt-2">+100 ball</div>
                </div>
            </div>
        </Transition>
    </div>
</template>

<style scoped>
/* Stats */
.stat-card {
    border-radius: 1rem;
    border-width: 2px;
    padding: 0.75rem;
    text-align: center;
}

/* Box button base */
.box-btn {
    position: relative;
    aspect-ratio: 1;
    border-radius: 1rem;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    transition: transform 0.15s, box-shadow 0.15s;
    border: 2.5px solid transparent;
    font-weight: 700;
    cursor: pointer;
}
.box-num {
    font-size: 0.65rem;
    font-weight: 800;
    color: #94a3b8;
    margin-top: 0.15rem;
}

/* Closed box */
.box-closed {
    background: linear-gradient(135deg, #f8fafc, #f1f5f9);
    border-color: #e2e8f0;
    box-shadow: 0 4px 0 0 #e2e8f0;
}
.box-closed:hover:not(:disabled) {
    background: linear-gradient(135deg, #eef2ff, #e0e7ff);
    border-color: #818cf8;
    box-shadow: 0 6px 0 0 #c7d2fe, 0 0 0 3px #e0e7ff;
    transform: translateY(-3px) scale(1.03);
}
.box-closed:active:not(:disabled) { transform: scale(0.96); }

/* Lid decoration */
.box-lid {
    position: absolute;
    top: 0; left: 0; right: 0;
    height: 28%;
    background: linear-gradient(135deg, #e0e7ff, #c7d2fe);
    border-bottom: 2px solid #a5b4fc;
}
.box-lid::after {
    content: '';
    position: absolute;
    left: 50%; top: 0; bottom: 0;
    width: 3px;
    background: #a5b4fc;
    transform: translateX(-50%);
}
.box-body-inner {
    display: flex;
    flex-direction: column;
    align-items: center;
    margin-top: 0.5rem;
}

/* Opening */
.box-opening {
    background: linear-gradient(135deg, #fef9c3, #fde68a);
    border-color: #fbbf24;
    box-shadow: 0 0 0 4px #fef3c7, 0 6px 0 0 #fbbf24;
    transform: scale(1.05);
    cursor: default;
}

/* Win revealed */
.box-win {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border-color: #4ade80;
    box-shadow: 0 3px 0 0 #86efac;
    cursor: default;
    color: #15803d;
}

/* Lose revealed */
.box-lose {
    background: linear-gradient(135deg, #fff1f2, #ffe4e6);
    border-color: #fb7185;
    box-shadow: 0 3px 0 0 #fda4af;
    cursor: default;
    color: #be123c;
    opacity: 0.75;
}

/* Entrance */
.option-appear { animation: boxPop 0.4s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes boxPop {
    from { opacity:0; transform:scale(0.6) translateY(10px); }
    to   { opacity:1; transform:scale(1) translateY(0); }
}
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.9); }
    to   { opacity:1; transform:scale(1); }
}

/* Popup */
.popup-card { animation: popupBounce 0.35s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes popupBounce {
    from { opacity:0; transform:scale(0.6); }
    to   { opacity:1; transform:scale(1); }
}
</style>
