<script setup>
import { ref, computed, onMounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({ gameData: { type: Object, required: true } });
const audio = useGameAudio();

const pool     = ref([]);
const assigned = ref({});
const selected = ref(null);   // { item, from }
const checked  = ref(false);
const finished = ref(false);

const groups = computed(() => props.gameData.groups ?? []);

const BUCKETS = [
    { gradient: 'from-indigo-500 to-blue-600',   light: '#eef2ff', border: '#818cf8', dot: '#6366f1' },
    { gradient: 'from-pink-500 to-rose-500',      light: '#fff1f2', border: '#fb7185', dot: '#f43f5e' },
    { gradient: 'from-emerald-500 to-teal-600',   light: '#f0fdf4', border: '#34d399', dot: '#10b981' },
    { gradient: 'from-amber-500 to-orange-500',   light: '#fffbeb', border: '#fbbf24', dot: '#f59e0b' },
    { gradient: 'from-purple-500 to-violet-600',  light: '#faf5ff', border: '#c084fc', dot: '#9333ea' },
    { gradient: 'from-cyan-500 to-sky-600',       light: '#ecfeff', border: '#22d3ee', dot: '#06b6d4' },
];

function shuffle(arr) { return [...arr].sort(() => Math.random() - 0.5); }

onMounted(() => setup());

function setup() {
    const allItems = [];
    const a = {};
    for (const g of groups.value) {
        a[g.name] = [];
        for (const item of (g.items ?? [])) {
            allItems.push({ text: item, correctGroup: g.name });
        }
    }
    pool.value     = shuffle(allItems);
    assigned.value = a;
    selected.value = null;
    checked.value  = false;
    finished.value = false;
}

function selectFromPool(item) {
    if (checked.value) return;
    selected.value = selected.value?.item === item ? null : { item, from: 'pool' };
}

function selectFromGroup(item, groupName) {
    if (checked.value) return;
    selected.value = selected.value?.item === item ? null : { item, from: groupName };
}

function assignToGroup(groupName) {
    if (!selected.value || checked.value) return;
    const { item, from } = selected.value;
    if (from === 'pool') pool.value = pool.value.filter(i => i !== item);
    else assigned.value[from] = assigned.value[from].filter(i => i !== item);

    if (groupName === 'pool') pool.value = [...pool.value, item];
    else assigned.value[groupName] = [...(assigned.value[groupName] ?? []), item];

    selected.value = null;
}

const allAssigned = computed(() => pool.value.length === 0);
const totalItems  = computed(() => groups.value.reduce((s, g) => s + (g.items?.length ?? 0), 0));
const correctCount = computed(() => {
    let c = 0;
    for (const g of groups.value)
        for (const item of (assigned.value[g.name] ?? []))
            if (item.correctGroup === g.name) c++;
    return c;
});
const pct = computed(() => totalItems.value ? Math.round((correctCount.value / totalItems.value) * 100) : 0);

function isCorrect(item, groupName) { return checked.value && item.correctGroup === groupName; }
function isWrong(item, groupName)   { return checked.value && item.correctGroup !== groupName; }

function check() {
    checked.value = true;
    setTimeout(() => { finished.value = true; audio.playComplete(); }, 1200);
}

function restart() { setup(); }

function bucketOf(gi) { return BUCKETS[gi % BUCKETS.length]; }
</script>

<template>
    <div class="w-full">

        <!-- ══ FINISHED ══ -->
        <div v-if="finished" class="max-w-2xl mx-auto result-appear">
            <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
                <div :class="[
                    'px-10 py-14 text-center text-white',
                    pct >= 80 ? 'bg-gradient-to-br from-emerald-400 to-green-600'
                    : pct >= 50 ? 'bg-gradient-to-br from-indigo-500 to-blue-600'
                    : 'bg-gradient-to-br from-orange-500 to-red-600'
                ]">
                    <div class="text-7xl mb-4">{{ pct >= 80 ? '🎉' : pct >= 50 ? '👍' : '💪' }}</div>
                    <div class="text-6xl font-black mb-2">{{ pct }}%</div>
                    <div class="text-white/80 text-lg font-semibold">{{ correctCount }} / {{ totalItems }} to'g'ri</div>
                </div>

                <!-- Correct answers breakdown -->
                <div class="p-6 space-y-3">
                    <div v-for="(g, gi) in groups" :key="g.name"
                        class="rounded-2xl overflow-hidden border border-slate-100">
                        <div :class="`bg-gradient-to-r ${bucketOf(gi).gradient} px-4 py-2 text-white text-sm font-bold`">
                            {{ g.name }}
                        </div>
                        <div class="flex flex-wrap gap-1.5 p-3 bg-slate-50">
                            <span v-for="item in g.items" :key="item"
                                class="px-3 py-1 rounded-lg bg-white border border-slate-200 text-slate-700 text-xs font-semibold">
                                {{ item }}
                            </span>
                        </div>
                    </div>
                </div>

                <div class="px-6 pb-6">
                    <button @click="restart"
                        class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-black py-4 rounded-2xl transition text-lg shadow-lg shadow-indigo-200">
                        Qayta o'ynash ↺
                    </button>
                </div>
            </div>
        </div>

        <!-- ══ ACTIVE GAME ══ -->
        <div v-else class="space-y-4">

            <!-- HUD -->
            <div class="flex items-center gap-3">
                <div class="flex-1 h-2 bg-slate-200 rounded-full overflow-hidden">
                    <div class="h-2 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-full transition-all duration-500"
                        :style="{ width: totalItems ? ((totalItems - pool.length) / totalItems * 100) + '%' : '0%' }"></div>
                </div>
                <span class="text-sm text-slate-400 font-bold shrink-0">{{ totalItems - pool.length }}/{{ totalItems }}</span>
            </div>

            <!-- Selected item floating hint -->
            <Transition enter-active-class="transition-all duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0">
                <div v-if="selected" class="flex items-center gap-3 bg-indigo-600 text-white rounded-2xl px-5 py-3 shadow-lg shadow-indigo-300/40">
                    <span class="text-lg">👆</span>
                    <span class="font-bold text-sm flex-1">"{{ selected.item.text }}" — qaysi idishga joylaysiz?</span>
                    <button @click="selected = null" class="text-indigo-200 hover:text-white text-lg font-bold leading-none">✕</button>
                </div>
            </Transition>

            <!-- ── Pool ── -->
            <div class="bg-white rounded-2xl border-2 border-dashed border-slate-200 p-4">
                <div class="flex items-center gap-2 mb-3">
                    <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                    <span class="text-xs font-black text-slate-500 uppercase tracking-widest">Tartiblanmagan elementlar</span>
                    <span class="ml-auto bg-slate-100 text-slate-600 text-xs font-bold px-2.5 py-0.5 rounded-full">{{ pool.length }} ta</span>
                </div>

                <div v-if="pool.length === 0" class="text-center py-4">
                    <span class="text-emerald-500 font-bold text-sm">✅ Barcha elementlar joylashtirildi!</span>
                </div>

                <div class="flex flex-wrap gap-2">
                    <TransitionGroup name="chip-move">
                        <button
                            v-for="item in pool"
                            :key="item.text"
                            @click="selectFromPool(item)"
                            :class="[
                                'pool-chip',
                                selected?.item === item ? 'pool-chip--active' : 'pool-chip--idle'
                            ]"
                        >
                            {{ item.text }}
                        </button>
                    </TransitionGroup>
                </div>
            </div>

            <!-- ── Group buckets ── -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div
                    v-for="(g, gi) in groups"
                    :key="g.name"
                    @click="selected && assignToGroup(g.name)"
                    :class="['bucket', selected ? 'bucket--droppable' : '']"
                    :style="selected ? `--bucket-border: ${bucketOf(gi).border}; --bucket-light: ${bucketOf(gi).light}` : ''"
                >
                    <!-- Bucket header -->
                    <div :class="`bucket-header bg-gradient-to-r ${bucketOf(gi).gradient}`">
                        <span class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-white/60"></span>
                            {{ g.name }}
                        </span>
                        <span class="bucket-count">{{ assigned[g.name]?.length ?? 0 }}</span>
                    </div>

                    <!-- Bucket body -->
                    <div class="bucket-body" :style="`background: ${bucketOf(gi).light}`"
                        @click.stop="selected && assignToGroup(g.name)">

                        <div v-if="!assigned[g.name]?.length"
                            class="bucket-empty" :style="`border-color: ${bucketOf(gi).border}; color: ${bucketOf(gi).dot}`">
                            <svg class="w-5 h-5 mb-1 opacity-40" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 9l-7 7-7-7"/>
                            </svg>
                            <span class="text-xs font-semibold opacity-50">Bu yerga tashlang</span>
                        </div>

                        <TransitionGroup name="chip-move" class="flex flex-wrap gap-2 content-start" tag="div">
                            <button
                                v-for="item in assigned[g.name]"
                                :key="item.text"
                                @click.stop="!checked && selectFromGroup(item, g.name)"
                                :class="[
                                    'bucket-chip',
                                    isCorrect(item, g.name) ? 'bucket-chip--correct' :
                                    isWrong(item, g.name)   ? 'bucket-chip--wrong'   :
                                    selected?.item === item  ? 'bucket-chip--selected' :
                                                               'bucket-chip--idle',
                                ]"
                                :style="`border-color: ${bucketOf(gi).border}`"
                            >
                                <span v-if="isCorrect(item, g.name)" class="mr-1">✓</span>
                                <span v-else-if="isWrong(item, g.name)" class="mr-1">✗</span>
                                {{ item.text }}
                            </button>
                        </TransitionGroup>
                    </div>

                    <!-- Drop indicator strip at bottom -->
                    <div v-if="selected"
                        :class="['bucket-drop-strip', `bg-gradient-to-r ${bucketOf(gi).gradient}`]">
                        + Bu idishga qo'yish
                    </div>
                </div>
            </div>

            <!-- Return to pool -->
            <Transition enter-active-class="transition-all duration-200" enter-from-class="opacity-0" enter-to-class="opacity-100">
                <div v-if="selected && selected.from !== 'pool'" class="text-center">
                    <button @click="assignToGroup('pool')"
                        class="text-sm text-slate-500 hover:text-indigo-600 font-semibold underline transition">
                        ↺ Havzaga qaytarish
                    </button>
                </div>
            </Transition>

            <!-- Check button -->
            <button
                @click="check"
                :disabled="!allAssigned || checked"
                class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-black py-4 rounded-2xl transition text-base shadow-lg shadow-indigo-200/50"
            >
                {{ checked ? 'Tekshirilmoqda...' : allAssigned ? 'Tekshirish ✓' : `${pool.length} ta element qoldi` }}
            </button>
        </div>
    </div>
</template>

<style scoped>
/* ── Chip transition ── */
.chip-move-move        { transition: all 0.25s ease; }
.chip-move-enter-active{ transition: all 0.3s cubic-bezier(0.34,1.56,0.64,1); }
.chip-move-leave-active{ transition: all 0.15s ease; position: absolute; }
.chip-move-enter-from  { opacity:0; transform:scale(0.7); }
.chip-move-leave-to    { opacity:0; transform:scale(0.7); }

/* ── Pool chips ── */
.pool-chip {
    padding: 0.45rem 0.9rem;
    border-radius: 0.75rem;
    font-size: 0.875rem;
    font-weight: 600;
    border: 2px solid;
    cursor: pointer;
    transition: transform 0.12s, box-shadow 0.12s, background 0.12s;
    white-space: nowrap;
}
.pool-chip--idle {
    background: #f8fafc;
    border-color: #e2e8f0;
    color: #334155;
    box-shadow: 0 2px 0 0 #e2e8f0;
}
.pool-chip--idle:hover {
    background: #eef2ff;
    border-color: #818cf8;
    color: #3730a3;
    transform: translateY(-2px);
    box-shadow: 0 4px 0 0 #c7d2fe;
}
.pool-chip--active {
    background: #6366f1;
    border-color: #4f46e5;
    color: white;
    box-shadow: 0 4px 0 0 #4f46e5, 0 0 0 3px #e0e7ff;
    transform: translateY(-2px) scale(1.04);
}

/* ── Bucket container ── */
.bucket {
    border-radius: 1rem;
    overflow: hidden;
    border: 2px solid #e2e8f0;
    background: white;
    transition: border-color 0.15s, box-shadow 0.15s, transform 0.15s;
    box-shadow: 0 4px 0 0 #e2e8f0;
}
.bucket--droppable {
    border-color: var(--bucket-border, #818cf8);
    box-shadow: 0 4px 0 0 var(--bucket-border, #818cf8), 0 0 0 3px color-mix(in srgb, var(--bucket-border, #818cf8) 20%, transparent);
    cursor: pointer;
    transform: translateY(-1px);
}
.bucket--droppable:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 0 0 var(--bucket-border, #818cf8), 0 0 0 4px color-mix(in srgb, var(--bucket-border, #818cf8) 25%, transparent);
}

.bucket-header {
    padding: 0.65rem 1rem;
    color: white;
    font-weight: 800;
    font-size: 0.875rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
}
.bucket-count {
    background: rgba(255,255,255,0.25);
    border-radius: 9999px;
    padding: 0.1rem 0.55rem;
    font-size: 0.75rem;
    font-weight: 900;
}

.bucket-body {
    min-height: 5.5rem;
    padding: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-content: flex-start;
}

.bucket-empty {
    width: 100%;
    height: 4rem;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    border: 2px dashed;
    border-radius: 0.75rem;
}

/* Drop strip at bottom */
.bucket-drop-strip {
    padding: 0.35rem;
    text-align: center;
    font-size: 0.7rem;
    font-weight: 700;
    color: white;
    opacity: 0.85;
    letter-spacing: 0.03em;
}

/* ── Bucket chips ── */
.bucket-chip {
    padding: 0.3rem 0.7rem;
    border-radius: 0.6rem;
    font-size: 0.8rem;
    font-weight: 700;
    border: 1.5px solid;
    cursor: pointer;
    transition: transform 0.1s, opacity 0.1s;
    background: white;
    color: #334155;
}
.bucket-chip--idle:hover    { transform: scale(1.04); }
.bucket-chip--selected      { background: #6366f1; color: white; border-color: #4f46e5; transform: scale(1.05); }
.bucket-chip--correct       { background: #f0fdf4; color: #15803d; border-color: #4ade80; cursor: default; }
.bucket-chip--wrong         { background: #fff1f2; color: #be123c; border-color: #fb7185; text-decoration: line-through; cursor: default; opacity: 0.8; }

/* ── Result ── */
.result-appear { animation: resultPop 0.45s cubic-bezier(0.34,1.56,0.64,1) both; }
@keyframes resultPop {
    from { opacity:0; transform:scale(0.92); }
    to   { opacity:1; transform:scale(1); }
}
</style>
