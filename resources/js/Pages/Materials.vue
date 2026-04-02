<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const materials = [
    {
        name: 'Ko\'p tanlovli test',
        icon: '📋',
        desc: 'Test savollari bilan ishchi varaq — 4 ta javob variant',
        color: 'bg-blue-50 text-blue-700',
        templateCode: 'quiz_mcq',
        soon: false,
    },
    {
        name: 'Anagramma',
        icon: '🔤',
        desc: "Harflarni aralashtirib so'z topish mashqlari",
        color: 'bg-pink-50 text-pink-700',
        templateCode: 'anagram',
        soon: false,
    },
    {
        name: "So'z qidirish",
        icon: '🔍',
        desc: "Harflar jadvalidan so'zlarni topish",
        color: 'bg-indigo-50 text-indigo-700',
        templateCode: 'word_search',
        soon: false,
    },
    {
        name: 'Juftlashtirish',
        icon: '🔗',
        desc: 'Chap va o\'ng ustunlarni chiziq bilan moslashtirish',
        color: 'bg-violet-50 text-violet-700',
        templateCode: 'matching_pairs',
        soon: false,
    },
    {
        name: 'To\'g\'ri / Noto\'g\'ri',
        icon: '✅',
        desc: 'Ifodalarni to\'g\'ri yoki noto\'g\'ri deb belgilash',
        color: 'bg-green-50 text-green-700',
        templateCode: 'true_false',
        soon: false,
    },
    {
        name: 'Imlo mashqi',
        icon: '✏️',
        desc: "So'zlarni to'g'ri imloda yozish namunalari",
        color: 'bg-fuchsia-50 text-fuchsia-700',
        templateCode: 'spelling',
        soon: false,
    },
    {
        name: 'Javob yozish',
        icon: '⌨️',
        desc: 'Savollarga qisqa javob yozish uchun bo\'sh joy',
        color: 'bg-cyan-50 text-cyan-700',
        templateCode: 'type_answer',
        soon: false,
    },
    {
        name: 'Gap tartiblashtirish',
        icon: '↕️',
        desc: "Aralashgan so'zlardan to'g'ri gap tuzish",
        color: 'bg-teal-50 text-teal-700',
        templateCode: 'reorder',
        soon: false,
    },
    {
        name: 'Diagramma belgilash',
        icon: '🗺️',
        desc: 'Diagramma qismlarini nomlash va belgilash',
        color: 'bg-blue-50 text-blue-700',
        templateCode: 'diagram',
        soon: false,
    },
    {
        name: 'Matematik mashqlar',
        icon: '🔢',
        desc: 'Matematik masalalar va misollar to\'plami',
        color: 'bg-emerald-50 text-emerald-700',
        templateCode: 'math_quiz',
        soon: false,
    },
    {
        name: 'Flesh-kartochkalar',
        icon: '🃏',
        desc: 'Ikki tomonlama kartalar — savol va javob',
        color: 'bg-yellow-50 text-yellow-700',
        templateCode: 'flashcards',
        soon: false,
    },
    {
        name: 'Sertifikatlar',
        icon: '🏅',
        desc: 'Diplom, sertifikat va faxriy yorliqlar — o\'quvchi ismi bilan chiroyli PDF',
        color: 'bg-amber-50 text-amber-700',
        templateCode: null,
        soon: false,
        link: '/certificate',
    },
];
</script>

<template>
    <Head title="Materiallar" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-slate-800">PDF Materiallar</h2>
        </template>

        <div class="max-w-4xl mx-auto space-y-5">
            <!-- Hero -->
            <div class="bg-gradient-to-br from-blue-600 to-indigo-700 rounded-2xl p-6 text-white flex items-center gap-5">
                <div class="text-5xl shrink-0">📄</div>
                <div>
                    <h2 class="font-extrabold text-lg mb-1">AI bilan PDF materiallar yaratish</h2>
                    <p class="text-blue-100 text-sm leading-relaxed">
                        Mavzuni kiriting — AI avtomatik ravishda chiroyli formatdagi, printerga tayyor PDF materiallar yaratadi.
                        Barcha materiallar DTS talablariga mos va javob kalitlari bilan birga.
                    </p>
                </div>
            </div>

            <!-- Count badge -->
            <div class="flex items-center gap-3">
                <span class="text-sm text-slate-500">
                    <span class="font-semibold text-indigo-600">{{ materials.filter(m => !m.soon).length }}</span> ta material turi tayyor
                </span>
                <span class="text-slate-300">·</span>
                <span v-if="materials.filter(m => m.soon).length" class="text-xs bg-amber-100 text-amber-700 font-semibold px-2.5 py-1 rounded-full">
                    + {{ materials.filter(m => m.soon).length }} ta tez kunda
                </span>
            </div>

            <!-- Materials grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <div v-for="(mat, mi) in materials" :key="mat.name"
                    :style="`animation-delay: ${mi * 45}ms`"
                    :class="[
                        'bg-white border rounded-2xl p-5 transition relative group mat-appear',
                        mat.soon
                            ? 'border-dashed border-slate-200 opacity-60'
                            : 'border-slate-100 hover:shadow-md hover:border-indigo-200'
                    ]">
                    <!-- Soon badge -->
                    <div v-if="mat.soon"
                        class="absolute top-3 right-3 text-[9px] font-bold bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full">
                        Tez kunda
                    </div>

                    <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-2xl mb-3', mat.color]">
                        {{ mat.icon }}
                    </div>
                    <div class="font-bold text-slate-800 text-sm mb-1">{{ mat.name }}</div>
                    <div class="text-xs text-slate-500 leading-snug mb-4">{{ mat.desc }}</div>

                    <!-- Action -->
                    <div v-if="!mat.soon">
                        <Link v-if="mat.link"
                            :href="mat.link"
                            class="w-full flex items-center justify-center gap-1.5 text-xs bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold py-2 rounded-xl transition">
                            🏅 Sertifikat yaratish
                        </Link>
                        <Link v-else
                            :href="mat.templateCode ? `/games/create?template=${mat.templateCode}` : '/games/create'"
                            class="w-full flex items-center justify-center gap-1.5 text-xs bg-indigo-50 hover:bg-indigo-100 text-indigo-700 font-semibold py-2 rounded-xl transition">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            AI bilan yaratish
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Note -->
            <div class="bg-slate-50 border border-slate-100 rounded-2xl p-5 text-xs text-slate-500 leading-relaxed flex items-start gap-3">
                <span class="text-lg shrink-0">💡</span>
                <p>
                    Barcha materiallar avtomatik tekshiriladi va sifat nazoratidan o'tkaziladi.
                    Yaratilgan materiallarni <strong>Chop etish</strong> tugmasi orqali PDF ko'rinishida yuklab olishingiz mumkin.
                    Javob kalitlari ham avtomatik shakllantiriladi.
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.mat-appear { animation: matSlide 0.38s ease both; }
@keyframes matSlide {
    from { opacity: 0; transform: translateY(10px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
