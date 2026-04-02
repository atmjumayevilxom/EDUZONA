<script setup>
import { ref, computed, reactive } from 'vue';

const props = defineProps({
    gameData: { type: Object, required: true },
});

const parts = computed(() => props.gameData?.parts ?? []);
const revealed = reactive({});
const selected = ref(null);
const score = ref(0);
const finished = ref(false);

// Create shuffled labels for matching
const shuffledLabels = computed(() => {
    const labels = parts.value.map(p => ({ id: p.id, label: p.label }));
    return [...labels].sort(() => Math.random() - 0.5);
});

// Track which part has been matched
const matched = reactive({});

function selectLabel(labelId) {
    if (selected.value === labelId) {
        selected.value = null;
        return;
    }
    selected.value = labelId;
}

function matchToPart(partId) {
    if (matched[partId] || !selected.value) return;
    const label = parts.value.find(p => p.id === selected.value);
    if (!label) return;

    matched[partId] = selected.value;
    const correct = selected.value === partId;
    revealed[partId] = { correct, labelId: selected.value };
    if (correct) score.value++;
    selected.value = null;

    if (Object.keys(matched).length === parts.value.length) {
        setTimeout(() => { finished.value = true; }, 600);
    }
}

function restart() {
    Object.keys(revealed).forEach(k => delete revealed[k]);
    Object.keys(matched).forEach(k => delete matched[k]);
    selected.value = null;
    score.value = 0;
    finished.value = false;
}

const percentage = computed(() => parts.value.length
    ? Math.round((score.value / parts.value.length) * 100)
    : 0
);

const usedLabelIds = computed(() => new Set(Object.values(matched)));
</script>

<template>
    <div class="min-h-[320px] sm:min-h-[400px] bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-5 flex flex-col gap-4 text-white select-none">

        <!-- Header -->
        <div class="flex items-center justify-between">
            <div class="font-bold text-sm">🗺️ {{ gameData.title }}</div>
            <div class="text-sm bg-white/20 px-3 py-1 rounded-full font-bold">
                {{ score }} / {{ parts.length }}
            </div>
        </div>

        <p v-if="gameData.description" class="text-xs text-white/70">{{ gameData.description }}</p>

        <!-- Finished screen -->
        <div v-if="finished" class="flex-1 flex flex-col items-center justify-center gap-4 text-center py-4">
            <div class="text-5xl">{{ percentage >= 80 ? '🎉' : percentage >= 50 ? '👍' : '📚' }}</div>
            <h2 class="text-xl font-bold">Bajarildi!</h2>
            <div class="text-3xl font-extrabold">{{ percentage }}%</div>
            <p class="text-white/70 text-sm">{{ score }} ta to'g'ri, {{ parts.length - score }} ta noto'g'ri</p>
            <button @click="restart"
                class="bg-white text-blue-700 font-bold px-8 py-3 rounded-2xl hover:bg-blue-50 transition text-sm mt-2">
                Qaytadan boshlash
            </button>
        </div>

        <template v-else>
            <!-- Instructions -->
            <div class="text-xs text-white/60 bg-white/10 rounded-xl px-4 py-2 text-center">
                Birinchi belgini tanlang, keyin qismga bosing
            </div>

            <!-- Labels palette -->
            <div class="flex flex-wrap gap-2">
                <button
                    v-for="(lbl, li) in shuffledLabels" :key="lbl.id"
                    :style="`animation-delay: ${li * 40}ms`"
                    @click="selectLabel(lbl.id)"
                    :disabled="usedLabelIds.has(lbl.id)"
                    :class="[
                        'px-3 py-1.5 rounded-xl text-xs font-bold border transition label-appear',
                        usedLabelIds.has(lbl.id)
                            ? 'border-white/10 text-white/20 bg-transparent cursor-not-allowed line-through'
                            : selected === lbl.id
                                ? 'border-amber-400 bg-amber-400/25 text-amber-200'
                                : 'border-white/30 bg-white/10 hover:border-white/50 hover:bg-white/20'
                    ]">
                    {{ lbl.label }}
                </button>
            </div>

            <!-- Parts list -->
            <div class="flex-1 flex flex-col gap-2 overflow-y-auto">
                <div
                    v-for="part in parts" :key="part.id"
                    @click="matchToPart(part.id)"
                    :class="[
                        'flex items-start gap-3 p-3 rounded-xl border transition',
                        matched[part.id]
                            ? revealed[part.id]?.correct
                                ? 'border-green-400 bg-green-500/20'
                                : 'border-red-400 bg-red-500/20'
                            : selected
                                ? 'border-amber-300/60 bg-amber-300/10 cursor-pointer hover:bg-amber-300/20'
                                : 'border-white/20 bg-white/5 cursor-default'
                    ]">
                    <!-- Number -->
                    <div class="w-7 h-7 rounded-full bg-white/20 flex items-center justify-center text-xs font-bold shrink-0">
                        {{ part.id }}
                    </div>
                    <!-- Description -->
                    <div class="flex-1 min-w-0">
                        <p class="text-sm text-white/70 leading-tight">{{ part.description }}</p>
                        <!-- Answer -->
                        <div v-if="matched[part.id]" class="mt-1">
                            <span v-if="revealed[part.id]?.correct" class="text-xs text-green-300 font-semibold">
                                ✓ {{ part.label }}
                            </span>
                            <span v-else class="text-xs text-red-300">
                                ✗ Sizning javobingiz — kerak: <strong>{{ part.label }}</strong>
                            </span>
                        </div>
                    </div>
                    <!-- Status icon -->
                    <div v-if="matched[part.id]" class="text-lg shrink-0">
                        {{ revealed[part.id]?.correct ? '✓' : '✗' }}
                    </div>
                    <div v-else-if="selected" class="text-xs text-amber-300/60 shrink-0">tap</div>
                </div>
            </div>
        </template>
    </div>
</template>

<style scoped>
.label-appear { animation: labelPop 0.3s ease both; }
@keyframes labelPop {
    from { opacity: 0; transform: scale(0.85); }
    to   { opacity: 1; transform: scale(1); }
}
</style>
