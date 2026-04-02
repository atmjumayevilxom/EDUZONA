<script setup>
const props = defineProps({
    item: { type: Object, required: true },
});

const emit = defineEmits(['open']);

const statusColors = {
    pending:         'bg-slate-100 text-slate-500',
    solving:         'bg-blue-100 text-blue-600',
    building_prompt: 'bg-purple-100 text-purple-600',
    generating:      'bg-amber-100 text-amber-600',
    completed:       'bg-green-100 text-green-700',
    failed:          'bg-red-100 text-red-600',
};

const subjectIcons = {
    mathematics: '📐',
    geometry:    '📏',
    algebra:     '🔢',
    physics:     '⚛️',
    chemistry:   '🧪',
    biology:     '🌱',
    history:     '📜',
    geography:   '🌍',
    language:    '📝',
    english:     '🔤',
    informatics: '💻',
    other:       '📚',
};

function formatDate(iso) {
    if (!iso) return '';
    return new Date(iso).toLocaleDateString('uz-UZ', {
        day: '2-digit', month: 'short', year: 'numeric',
    });
}
</script>

<template>
    <button
        @click="emit('open', item)"
        class="w-full text-left bg-white rounded-xl border border-slate-100 hover:border-indigo-200 hover:shadow-sm transition-all p-4 group">
        <div class="flex items-start gap-3">
            <!-- Subject icon -->
            <div class="w-10 h-10 bg-slate-50 rounded-xl flex items-center justify-center text-xl shrink-0 group-hover:bg-indigo-50 transition">
                {{ subjectIcons[item.subject] ?? '📚' }}
            </div>

            <!-- Info -->
            <div class="flex-1 min-w-0">
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-slate-800 truncate">{{ item.topic }}</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ item.subject_label }}</p>
                    </div>
                    <!-- Status badge -->
                    <span :class="['shrink-0 text-xs font-medium px-2 py-0.5 rounded-full', statusColors[item.status] ?? 'bg-slate-100 text-slate-500']">
                        {{ item.status_label }}
                    </span>
                </div>

                <!-- Footer -->
                <div class="flex items-center justify-between mt-2">
                    <span class="text-xs text-slate-400">{{ formatDate(item.created_at) }}</span>
                    <!-- Video mavjud bo'lsa -->
                    <span v-if="item.video_url" class="text-xs text-indigo-600 font-medium flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M8 5v14l11-7z"/>
                        </svg>
                        Video bor
                    </span>
                </div>
            </div>
        </div>
    </button>
</template>
