<script setup>
import { ref, computed } from 'vue';

const props = defineProps({
    serverErrors: { type: Object, default: null },
});
const emit = defineEmits(['submit']);

const subjects = {
    mathematics: '📐 Matematika',
    geometry:    '📏 Geometriya',
    algebra:     '🔢 Algebra',
    physics:     '⚡ Fizika',
    chemistry:   '⚗️ Kimyo',
    biology:     '🧬 Biologiya',
    history:     '📜 Tarix',
    geography:   '🌍 Geografiya',
    language:    '✍️ O\'zbek tili',
    english:     '🇬🇧 Ingliz tili',
    informatics: '💻 Informatika',
    other:       '📚 Boshqa',
};

const form = ref({
    subject:            'mathematics',
    problem_text:       '',
    video_style:        'blackboard',
    explanation_length: 'medium',
    voice_style:        'calm',
    language:           'uz',
});

const showAdvanced = ref(false);
const errors = computed(() => props.serverErrors ?? {});
const isValid = computed(() => form.value.problem_text.trim().length >= 10);

function submit() {
    if (!isValid.value) return;
    emit('submit', { ...form.value });
}
</script>

<template>
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm overflow-hidden">
        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 px-6 py-5">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-white/20 rounded-xl flex items-center justify-center text-xl">🎬</div>
                <div>
                    <h2 class="text-white font-bold text-lg leading-tight">AI Video Dars</h2>
                    <p class="text-violet-200 text-sm">Masalani kiriting — AI video dars tayyorlaydi</p>
                </div>
            </div>
        </div>

        <div class="p-6 space-y-5">
            <!-- Xato -->
            <div v-if="errors.general"
                class="flex items-start gap-3 bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm">
                <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                {{ errors.general }}
            </div>

            <!-- Fan -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">Fan tanlang</label>
                <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                    <button v-for="(label, key) in subjects" :key="key" type="button"
                        @click="form.subject = key"
                        :class="[
                            'px-2 py-2 rounded-xl text-xs font-medium border transition-all text-left',
                            form.subject === key
                                ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                                : 'bg-slate-50 text-slate-600 border-slate-200 hover:border-indigo-300 hover:text-indigo-600'
                        ]">
                        {{ label }}
                    </button>
                </div>
            </div>

            <!-- Masala -->
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Masala yoki savol <span class="text-red-400">*</span>
                </label>
                <textarea v-model="form.problem_text" rows="5"
                    placeholder="Masalani shu yerga yozing...&#10;&#10;Misol: x² + 5x + 6 = 0 tenglamani yeching&#10;yoki: Massa 2 kg, kuch 10 N. Tezlanish toping."
                    :class="[
                        'w-full px-4 py-3 rounded-xl border text-sm transition-all outline-none resize-none',
                        errors.problem_text
                            ? 'border-red-300 bg-red-50'
                            : 'border-slate-200 focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100'
                    ]" />
                <div class="flex justify-between items-center mt-1">
                    <p v-if="errors.problem_text" class="text-xs text-red-500">{{ errors.problem_text[0] }}</p>
                    <p v-else class="text-xs text-slate-400">{{ form.problem_text.length }} / 3000</p>
                </div>
            </div>

            <!-- Qo'shimcha sozlamalar -->
            <button type="button" @click="showAdvanced = !showAdvanced"
                class="flex items-center gap-2 text-sm text-slate-500 hover:text-indigo-600 transition">
                <svg :class="['w-4 h-4 transition-transform', showAdvanced ? 'rotate-90' : '']"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                {{ showAdvanced ? 'Sozlamalarni yopish' : "Qo'shimcha sozlamalar" }}
            </button>

            <div v-if="showAdvanced" class="bg-slate-50 rounded-xl p-4 space-y-4 border border-slate-100">
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Video uslubi</label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="(label, key) in { blackboard:'🖊 Doska', animated:'✨ Animatsiyali', minimal:'⬜ Minimal' }"
                            :key="key" type="button" @click="form.video_style = key"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-all',
                                form.video_style === key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300']">
                            {{ label }}
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Tushuntirish</label>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="(label, key) in { short:'⚡ Qisqa', medium:'📖 O\'rtacha', long:'📚 Batafsil' }"
                            :key="key" type="button" @click="form.explanation_length = key"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-all',
                                form.explanation_length === key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300']">
                            {{ label }}
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Til</label>
                    <div class="flex gap-2">
                        <button v-for="(label, key) in { uz:'🇺🇿 O\'zbek', en:'🇬🇧 English', ru:'🇷🇺 Русский' }"
                            :key="key" type="button" @click="form.language = key"
                            :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition-all',
                                form.language === key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300']">
                            {{ label }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Submit -->
            <button type="button" @click="submit" :disabled="!isValid"
                :class="[
                    'w-full py-4 rounded-xl font-bold text-sm transition-all flex items-center justify-center gap-2',
                    isValid
                        ? 'bg-gradient-to-r from-violet-600 to-indigo-600 text-white hover:from-violet-700 hover:to-indigo-700 shadow-lg active:scale-[0.99]'
                        : 'bg-slate-100 text-slate-400 cursor-not-allowed'
                ]">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                🎬 Video Dars Yaratish
            </button>
        </div>
    </div>
</template>
