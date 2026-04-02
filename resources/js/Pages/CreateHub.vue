<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const activeTab = ref('games');

const tabs = [
    { key: 'games',    label: "O'yin rejimlari",    icon: '🎮', count: '33+' },
    { key: 'pdf',      label: 'PDF materiallar',     icon: '📄', count: '7+' },
    { key: 'nazorat',  label: 'Nazorat ishlari',     icon: '📝', count: '4' },
    { key: 'docs',     label: "Ta'lim hujjatlari",   icon: '📋', count: '3+' },
    { key: 'exams',    label: "Imtihon tayyorgarlik", icon: '🏆', count: '3' },
];

// 10 asosiy o'yin rejimlari + qolganlar
const featuredGames = [
    { code: 'quiz_mcq',       name: 'Test',              icon: '❓', color: 'bg-indigo-100 text-indigo-700',  desc: "Ko'p tanlovli savollar" },
    { code: 'open_box',       name: 'Sirli quti',        icon: '🎁', color: 'bg-rose-100 text-rose-700',      desc: 'Qutini ochib javobni top' },
    { code: 'random_wheel',   name: 'Omad charxi',       icon: '🎡', color: 'bg-amber-100 text-amber-700',    desc: "Tasodifiy g'ildirakni aylantir" },
    { code: 'anagram',        name: 'Anagramma',         icon: '🔤', color: 'bg-pink-100 text-pink-700',      desc: 'Tartib buzilgan so\'zlar' },
    { code: 'word_search',    name: "So'z qidirish",     icon: '🔍', color: 'bg-blue-100 text-blue-700',      desc: 'Harflar jadvalidan so\'z top' },
    { code: 'matching_pairs', name: 'Moslashtirish',     icon: '🔗', color: 'bg-purple-100 text-purple-700',  desc: 'Juftlarni moslashtirish' },
    { code: 'complete_sentence', name: "Bo'sh joy",      icon: '📝', color: 'bg-violet-100 text-violet-700',  desc: "Bo'sh joylarni to'ldirish" },
    { code: 'reorder',        name: 'Gap tuzish',        icon: '↕️', color: 'bg-teal-100 text-teal-700',      desc: "So'zlarni tartibga sol" },
    { code: 'flashcards',     name: 'Kartochka',         icon: '🃏', color: 'bg-yellow-100 text-yellow-700',  desc: "Ikki tomonlama kartochkalar" },
    { code: 'true_false',     name: "To'g'ri / Xato",   icon: '✅', color: 'bg-green-100 text-green-700',    desc: "To'g'ri yoki noto'g'ri?" },
];

const moreGames = [
    { code: 'hangman',         name: "Harfni top",        icon: '🪢', color: 'bg-slate-100 text-slate-700' },
    { code: 'type_answer',     name: 'Javob yozish',      icon: '⌨️', color: 'bg-cyan-100 text-cyan-700' },
    { code: 'group_sort',      name: 'Guruhlash',         icon: '🗂', color: 'bg-orange-100 text-orange-700' },
    { code: 'whack_mole',      name: "Ko'rsichaqa ur",    icon: '🔨', color: 'bg-lime-100 text-lime-700' },
    { code: 'memory_cards',    name: "Xotira o'yini",     icon: '🧠', color: 'bg-sky-100 text-sky-700' },
    { code: 'game_show_quiz',  name: "O'yin shou",        icon: '🎬', color: 'bg-fuchsia-100 text-fuchsia-700' },
    { code: 'flying_answers',  name: 'Uchuvchi javoblar', icon: '💨', color: 'bg-indigo-100 text-indigo-700' },
    { code: 'pair_or_not',     name: "Juftmi yo'q?",      icon: '🔀', color: 'bg-amber-100 text-amber-700' },
    { code: 'speed_sort',      name: 'Tez saralash',      icon: '⚡', color: 'bg-red-100 text-red-700' },
    { code: 'spell_word',      name: "So'zni yoz",        icon: '✏️', color: 'bg-emerald-100 text-emerald-700' },
    { code: 'airplane',        name: "Samolyot o'yini",   icon: '✈️', color: 'bg-sky-100 text-sky-700' },
    { code: 'watch_memorize',  name: "Ko'rib eslab qol",  icon: '👁', color: 'bg-teal-100 text-teal-700' },
    { code: 'win_or_lose',     name: "Yut yoki Yo'qot",    icon: '🏆', color: 'bg-yellow-100 text-yellow-700' },
    { code: 'math_quiz',       name: 'Matematik test',    icon: '🔢', color: 'bg-violet-100 text-violet-700' },
    { code: 'millionaire',     name: 'Millioner',         icon: '💰', color: 'bg-amber-100 text-amber-700' },
    { code: 'spelling',        name: 'Imlo',              icon: '📖', color: 'bg-fuchsia-100 text-fuchsia-700' },
    { code: 'diagram',         name: 'Diagramma',         icon: '🗺️', color: 'bg-blue-100 text-blue-700' },
    { code: 'zakovat',         name: 'Zakovat',           icon: '🧩', color: 'bg-purple-100 text-purple-700' },
    { code: 'race',            name: 'Poyga',             icon: '🏎️', color: 'bg-red-100 text-red-700' },
    { code: 'timeline',        name: "Vaqt chizig'i",     icon: '📅', color: 'bg-teal-100 text-teal-700' },
    { code: 'crossword',       name: 'Krossvord',         icon: '🔠', color: 'bg-violet-100 text-violet-700' },
    { code: 'rope_pull',       name: 'Arqon tortish',     icon: '💪', color: 'bg-emerald-100 text-emerald-700' },
    { code: 'sleeping_bear',   name: 'Uyqudagi Ayiq',     icon: '🐻', color: 'bg-amber-100 text-amber-700' },
    { code: 'dtm_test',        name: 'DTM test',          icon: '🎓', color: 'bg-indigo-100 text-indigo-700' },
    { code: 'olimpiada',       name: 'Olimpiada',         icon: '🥇', color: 'bg-yellow-100 text-yellow-700' },
    { code: 'pisa_reading',    name: "PISA o'qish",       icon: '🌍', color: 'bg-teal-100 text-teal-700' },
    { code: 'lesson_plan',     name: 'Dars rejasi',       icon: '📋', color: 'bg-sky-100 text-sky-700' },
];

const soonGames = [
    { name: 'Xarita testi',  icon: '🗺️' },
];

const pdfMaterials = [
    { name: 'Test varaq',        icon: '📋', desc: 'Test savollari bilan ishchi varaq',          template: 'quiz_mcq' },
    { name: 'Anagramma',         icon: '🔤', desc: 'Harflarni aralashtirib so\'z topish mashqi', template: 'anagram' },
    { name: "So'z qidirish",     icon: '🔍', desc: 'Harflar jadvalidan so\'zlarni topish',       template: 'word_search' },
    { name: 'Moslashtirish',     icon: '🔗', desc: 'Juftliklarni moslashtirish mashqi',           template: 'matching_pairs' },
    { name: "Bo'sh joy to'ldirish", icon: '📝', desc: 'Gaplardagi bo\'sh joylarni to\'ldirish',  template: 'complete_sentence' },
    { name: 'Gapni tartiblashtirish', icon: '↕️', desc: "Aralashgan so'zlarni to'g'ri tartibga", template: 'reorder' },
    { name: 'Krossvord',         icon: '🔠', desc: 'Mavzu bo\'yicha qiziqarli krosswordlar',     template: 'crossword' },
    { name: 'Matematik ishchi varaq', icon: '🔢', desc: 'Ustunda hisoblash uchun misollar',      soon: true },
    { name: 'Matematik krossword',    icon: '➕', desc: 'Matematik misollardan krossword',       soon: true },
    { name: 'Kod kaliti',        icon: '🔑', desc: 'Raqamlar orqali yashirin so\'zni topish',    soon: true },
];

const nazoratItems = [
    { name: 'BSB / ChSB',           icon: '📊', desc: 'Bosqichli va choraklik summativ baholash', soon: false, link: '/games/create?template=quiz_mcq' },
    { name: 'Mavzu yuzasidan',       icon: '📖', desc: 'Mavzu bo\'yicha nazorat savollari',        soon: false, link: '/games/create?template=quiz_mcq' },
    { name: 'Ishchi varaqlar',       icon: '📄', desc: 'Tarqatma materiallar va mashqlar',         soon: false, link: '/materials' },
    { name: 'Sertifikatlar',         icon: '🏅', desc: 'Diplom, sertifikat, faxriy yorliq',        soon: false, link: '/certificate' },
];

const docItems = [
    { name: 'Dars rejasi',          icon: '📅', desc: 'Kunlik dars rejasi tayyorlash',  soon: false, link: '/games/create?template=lesson_plan' },
    { name: "Kalendar-taqvim reja", icon: '📆', desc: 'Yillik va choraklik rejalar',    soon: true },
    { name: 'Hisobot',              icon: '📈', desc: "O'quvchilar natijalari hisoboti", soon: true },
];

const examItems = [
    { name: 'PISA o\'qish',   icon: '🌍', desc: 'Xalqaro PISA formatidagi o\'qish va tahlil', soon: false, link: '/games/create?template=pisa_reading' },
    { name: 'Olimpiada',      icon: '🥇', desc: 'Fan olimpiadalariga tayyorgarlik savollari',  soon: false, link: '/games/create?template=olimpiada' },
    { name: 'DTM test',       icon: '🎓', desc: 'DTM formatidagi test savollari',              soon: false, link: '/games/create?template=dtm_test' },
];
</script>

<template>
    <Head title="Yaratish — EDUZONA" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-semibold text-slate-800">Yaratish</h2>
        </template>

        <div class="max-w-6xl mx-auto space-y-6">

            <!-- Hero card -->
            <div class="bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700 rounded-2xl p-6 text-white">
                <div class="flex flex-col lg:flex-row gap-6 items-start">
                    <div class="flex-1">
                        <div class="inline-flex items-center gap-2 bg-white/15 text-white text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                            <span class="w-1.5 h-1.5 bg-white rounded-full animate-pulse"></span>
                            AI yordamida
                        </div>
                        <h1 class="text-xl sm:text-2xl font-extrabold mb-3 leading-tight">
                            AI yordamida professional darajadagi<br class="hidden sm:block">
                            o'quv materiallarini bir necha soniyada yarating
                        </h1>
                        <ul class="space-y-1.5 text-sm text-indigo-100 mb-6">
                            <li class="flex items-center gap-2"><span class="text-green-300">✓</span> Davlat darsliklari asosida</li>
                            <li class="flex items-center gap-2"><span class="text-green-300">✓</span> AI yordamida avtomatik yaratish</li>
                            <li class="flex items-center gap-2"><span class="text-green-300">✓</span> Tayyor PDF fayl sifatida yuklab olish</li>
                            <li class="flex items-center gap-2"><span class="text-green-300">✓</span> Javob kalitlari bilan birga</li>
                        </ul>
                        <Link href="/games/create"
                            class="inline-flex items-center gap-2 bg-white text-indigo-700 font-bold px-6 py-3 rounded-xl text-sm hover:bg-indigo-50 transition shadow-lg">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Yangi o'yin yaratish
                        </Link>
                    </div>

                    <!-- Stats -->
                    <div class="grid grid-cols-2 gap-3 lg:w-64 shrink-0">
                        <div class="bg-white/15 rounded-2xl p-4 text-center">
                            <div class="text-2xl font-extrabold">33+</div>
                            <div class="text-xs text-indigo-200 mt-0.5">O'yin turi</div>
                        </div>
                        <div class="bg-white/15 rounded-2xl p-4 text-center">
                            <div class="text-2xl font-extrabold">7</div>
                            <div class="text-xs text-indigo-200 mt-0.5">PDF material</div>
                        </div>
                        <div class="bg-white/15 rounded-2xl p-4 text-center">
                            <div class="text-2xl font-extrabold">15+</div>
                            <div class="text-xs text-indigo-200 mt-0.5">Fanlar</div>
                        </div>
                        <div class="bg-white/15 rounded-2xl p-4 text-center">
                            <div class="text-2xl font-extrabold">~30s</div>
                            <div class="text-xs text-indigo-200 mt-0.5">Yaratish vaqti</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Promo banner -->
            <div class="bg-amber-50 border border-amber-200 rounded-2xl px-5 py-4 flex items-center gap-4">
                <span class="text-3xl shrink-0">💡</span>
                <div>
                    <p class="text-sm font-bold text-amber-800">1 ta o'yin yarating — 7 xil material, 33+ xil o'yin paydo bo'ladi</p>
                    <p class="text-xs text-amber-600 mt-0.5">Bitta mavzudan butun dars uchun barcha resurslar avtomatik tayyorlanadi</p>
                </div>
                <Link href="/games/create"
                    class="ml-auto shrink-0 bg-amber-500 hover:bg-amber-600 text-white font-bold px-4 py-2 rounded-xl text-xs transition whitespace-nowrap">
                    Boshlash →
                </Link>
            </div>

            <!-- Tabs -->
            <div class="flex flex-wrap gap-2 border-b border-slate-200 pb-0 -mb-px">
                <button v-for="tab in tabs" :key="tab.key"
                    @click="!tab.soon && (activeTab = tab.key)"
                    :class="[
                        'flex items-center gap-2 px-4 py-2.5 text-sm font-medium border-b-2 transition whitespace-nowrap',
                        tab.soon
                            ? 'text-slate-400 border-transparent cursor-default'
                            : activeTab === tab.key
                                ? 'border-indigo-600 text-indigo-600'
                                : 'border-transparent text-slate-600 hover:text-slate-900 hover:border-slate-300'
                    ]">
                    <span>{{ tab.icon }}</span>
                    {{ tab.label }}
                    <span v-if="tab.count"
                        class="text-[10px] font-bold bg-indigo-100 text-indigo-600 px-1.5 py-0.5 rounded-full leading-none">
                        {{ tab.count }}
                    </span>
                    <span v-if="tab.soon"
                        class="text-[9px] font-bold bg-amber-100 text-amber-500 px-1.5 py-0.5 rounded-full">
                        Tez kunda
                    </span>
                </button>
            </div>

            <!-- ===== TAB: O'yin rejimlari ===== -->
            <div v-if="activeTab === 'games'" class="space-y-6">

                <!-- Asosiy 10 ta -->
                <div>
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="font-bold text-slate-800">10 ta asosiy o'yin rejimi</h3>
                        <Link href="/games/create" class="text-xs text-indigo-600 hover:underline font-medium">
                            Barchasi →
                        </Link>
                    </div>
                    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                        <Link
                            v-for="(game, gi) in featuredGames" :key="game.code"
                            href="/games/create"
                            :style="`animation-delay: ${gi * 50}ms`"
                            class="group bg-white border border-slate-100 rounded-2xl p-4 hover:shadow-md hover:border-slate-200 transition-all text-center game-appear">
                            <div :class="['w-12 h-12 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-3', game.color]">
                                {{ game.icon }}
                            </div>
                            <div class="text-xs font-bold text-slate-800 mb-1">{{ game.name }}</div>
                            <div class="text-[10px] text-slate-500 leading-tight">{{ game.desc }}</div>
                            <div class="mt-3 text-[10px] text-indigo-600 font-semibold opacity-0 group-hover:opacity-100 transition">
                                Yaratish →
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Qo'shimcha o'yinlar -->
                <div>
                    <h3 class="font-bold text-slate-800 mb-4">Barcha {{ moreGames.length }} ta qo'shimcha o'yin</h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-6 gap-2">
                        <Link
                            v-for="(game, gi) in moreGames" :key="game.code"
                            href="/games/create"
                            :style="`animation-delay: ${gi * 30}ms`"
                            class="bg-white border border-slate-100 rounded-xl px-3 py-2.5 flex items-center gap-2 hover:shadow-sm hover:border-indigo-200 hover:bg-indigo-50/30 transition group game-appear">
                            <span :class="['w-7 h-7 rounded-lg flex items-center justify-center text-sm shrink-0', game.color]">
                                {{ game.icon }}
                            </span>
                            <span class="text-xs font-medium text-slate-700 group-hover:text-indigo-700 truncate">{{ game.name }}</span>
                        </Link>
                    </div>
                </div>

                <!-- Tez kunda -->
                <div>
                    <h3 class="font-bold text-slate-800 mb-4 flex items-center gap-2">
                        Tez kunda
                        <span class="text-[10px] font-bold bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full">+{{ soonGames.length }}</span>
                    </h3>
                    <div class="grid grid-cols-2 sm:grid-cols-4 md:grid-cols-7 gap-2">
                        <div v-for="game in soonGames" :key="game.name"
                            class="bg-slate-50 border border-dashed border-slate-200 rounded-xl px-3 py-2.5 flex items-center gap-2 opacity-60">
                            <span class="text-base">{{ game.icon }}</span>
                            <span class="text-xs text-slate-500 font-medium truncate">{{ game.name }}</span>
                        </div>
                    </div>
                </div>

                <!-- CTA -->
                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-6 text-center">
                    <p class="text-sm font-semibold text-indigo-800 mb-3">
                        Mavzuni kiriting — AI 30 soniyada tayyor o'yin yaratadi
                    </p>
                    <Link href="/games/create"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-8 py-3 rounded-xl text-sm transition shadow-md shadow-indigo-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        O'yin yaratish
                    </Link>
                </div>
            </div>

            <!-- ===== TAB: PDF materiallar ===== -->
            <div v-if="activeTab === 'pdf'" class="space-y-4">
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-4 text-sm text-blue-700">
                    💡 <strong>Qanday ishlaydi?</strong> Mavzuni kiriting → AI varaq yaratadi → Printerdan chiqaring. Javob kalitlari ham birga beriladi.
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-3">
                    <template v-for="mat in pdfMaterials" :key="mat.name">
                        <!-- Active card — links to game create with pre-selected template -->
                        <Link
                            v-if="!mat.soon"
                            :href="`/games/create?template=${mat.template}`"
                            class="group bg-white border border-slate-100 rounded-2xl p-4 hover:shadow-md hover:border-indigo-200 transition relative block">
                            <div class="text-3xl mb-3">{{ mat.icon }}</div>
                            <div class="text-sm font-bold text-slate-800 mb-1">{{ mat.name }}</div>
                            <div class="text-xs text-slate-500 leading-snug">{{ mat.desc }}</div>
                            <div class="mt-3 text-xs text-indigo-600 font-semibold opacity-0 group-hover:opacity-100 transition">
                                Yaratish →
                            </div>
                        </Link>
                        <!-- Soon card -->
                        <div
                            v-else
                            class="bg-white border border-dashed border-slate-200 rounded-2xl p-4 opacity-60 relative">
                            <div class="absolute top-2.5 right-2.5 text-[9px] font-bold bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full">
                                Tez kunda
                            </div>
                            <div class="text-3xl mb-3">{{ mat.icon }}</div>
                            <div class="text-sm font-bold text-slate-800 mb-1">{{ mat.name }}</div>
                            <div class="text-xs text-slate-500 leading-snug">{{ mat.desc }}</div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- ===== TAB: Nazorat ishlari ===== -->
            <div v-if="activeTab === 'nazorat'" class="space-y-4">
                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-4 text-sm text-indigo-700">
                    💡 <strong>Nazorat ishlari</strong> — AI yordamida DTS talablariga mos baholash materiallari yarating. Javob kalitlari bilan birga chop etish mumkin.
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <template v-for="(item, ii) in nazoratItems" :key="item.name">
                        <Link v-if="!item.soon"
                            :href="item.link"
                            :style="`animation-delay: ${ii * 70}ms`"
                            class="bg-white border border-slate-100 rounded-2xl p-5 flex items-start gap-4 hover:shadow-md hover:border-indigo-200 transition group game-appear">
                            <div class="text-3xl shrink-0">{{ item.icon }}</div>
                            <div class="flex-1 min-w-0">
                                <div class="font-bold text-slate-800 text-sm mb-1">{{ item.name }}</div>
                                <div class="text-xs text-slate-500">{{ item.desc }}</div>
                                <div class="mt-2 text-xs text-indigo-600 font-semibold opacity-0 group-hover:opacity-100 transition">Yaratish →</div>
                            </div>
                        </Link>
                        <div v-else
                            :style="`animation-delay: ${ii * 70}ms`"
                            class="bg-white border border-dashed border-slate-200 rounded-2xl p-5 flex items-start gap-4 opacity-60 relative game-appear">
                            <div class="absolute top-3 right-3 text-[9px] font-bold bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full">Tez kunda</div>
                            <div class="text-3xl shrink-0">{{ item.icon }}</div>
                            <div>
                                <div class="font-bold text-slate-800 text-sm mb-1">{{ item.name }}</div>
                                <div class="text-xs text-slate-500">{{ item.desc }}</div>
                            </div>
                        </div>
                    </template>
                </div>

                <!-- Info box -->
                <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
                    <h4 class="font-bold text-blue-800 text-sm mb-3">Nazorat ishlari haqida</h4>
                    <p class="text-xs text-blue-700 leading-relaxed">
                        Barcha materiallar DTS talablariga mos ravishda yaratiladi. AI tomonidan tayyorlangan savollar avtomatik tekshiriladi va sifat nazoratidan o'tkaziladi. Yaratilgan materiallarni o'zingiz tahrirlashingiz va printerdan chiqarishingiz mumkin.
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mt-4">
                        <div class="text-center">
                            <div class="text-lg font-extrabold text-blue-700">30+</div>
                            <div class="text-[10px] text-blue-500">Savol turlari</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-extrabold text-blue-700">1-11</div>
                            <div class="text-[10px] text-blue-500">Sinflar</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-extrabold text-blue-700">15+</div>
                            <div class="text-[10px] text-blue-500">Fanlar</div>
                        </div>
                        <div class="text-center">
                            <div class="text-lg font-extrabold text-blue-700">~30s</div>
                            <div class="text-[10px] text-blue-500">Yaratish vaqti</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ===== TAB: Ta'lim hujjatlari ===== -->
            <div v-if="activeTab === 'docs'" class="space-y-4">
                <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white flex items-center gap-5">
                    <div class="text-5xl shrink-0">📋</div>
                    <div>
                        <h2 class="font-extrabold text-lg mb-1">AI bilan ta'lim hujjatlari</h2>
                        <p class="text-indigo-100 text-sm leading-relaxed">
                            Dars rejalari, kalendar-taqvim rejalar va boshqa rasmiy hujjatlarni AI yordamida bir necha daqiqada tayyorlang.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <template v-for="(item, ii) in docItems" :key="item.name">
                        <Link v-if="!item.soon"
                            :href="item.link"
                            :style="`animation-delay: ${ii * 70}ms`"
                            class="group bg-white border border-slate-100 rounded-2xl p-6 text-center hover:shadow-md hover:border-indigo-200 transition relative block game-appear">
                            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3">{{ item.icon }}</div>
                            <div class="font-bold text-slate-700 text-sm mb-1">{{ item.name }}</div>
                            <div class="text-xs text-slate-400 leading-snug">{{ item.desc }}</div>
                            <div class="mt-3 text-xs text-indigo-600 font-semibold opacity-0 group-hover:opacity-100 transition">Yaratish →</div>
                        </Link>
                        <div v-else
                            :style="`animation-delay: ${ii * 70}ms`"
                            class="bg-white border border-dashed border-slate-200 rounded-2xl p-6 text-center relative opacity-60 game-appear">
                            <div class="absolute top-3 right-3 text-[9px] font-bold bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full">Tez kunda</div>
                            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3">{{ item.icon }}</div>
                            <div class="font-bold text-slate-700 text-sm mb-1">{{ item.name }}</div>
                            <div class="text-xs text-slate-400 leading-snug">{{ item.desc }}</div>
                        </div>
                    </template>
                </div>

                <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5 flex items-center gap-4">
                    <span class="text-2xl shrink-0">💡</span>
                    <p class="text-xs text-indigo-700 leading-relaxed">
                        Boshqa hujjat turlari tayyorlanmoqda.
                        <Link href="/lessons" class="font-semibold hover:underline">Darslar</Link> bo'limidagi
                        tayyor materiallardan ham foydalanishingiz mumkin.
                    </p>
                </div>
            </div>

            <!-- ===== TAB: Imtihon tayyorgarlik ===== -->
            <div v-if="activeTab === 'exams'" class="space-y-4">
                <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-6 text-white flex items-center gap-5">
                    <div class="text-5xl shrink-0">🏆</div>
                    <div>
                        <h2 class="font-extrabold text-lg mb-1">Imtihonlarga tayyorgarlik</h2>
                        <p class="text-amber-100 text-sm leading-relaxed">
                            PISA, Olimpiada va DTM formatidagi maxsus test materiallar va topshiriqlar yarating.
                        </p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <template v-for="(item, ii) in examItems" :key="item.name">
                        <Link v-if="!item.soon"
                            :href="item.link"
                            :style="`animation-delay: ${ii * 70}ms`"
                            class="group bg-white border border-slate-100 rounded-2xl p-6 text-center hover:shadow-md hover:border-amber-200 transition relative block game-appear">
                            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3">{{ item.icon }}</div>
                            <div class="font-bold text-slate-700 text-sm mb-1">{{ item.name }}</div>
                            <div class="text-xs text-slate-400 leading-snug">{{ item.desc }}</div>
                            <div class="mt-3 text-xs text-amber-600 font-semibold opacity-0 group-hover:opacity-100 transition">Yaratish →</div>
                        </Link>
                        <div v-else
                            :style="`animation-delay: ${ii * 70}ms`"
                            class="bg-white border border-dashed border-slate-200 rounded-2xl p-6 text-center relative opacity-60 game-appear">
                            <div class="absolute top-3 right-3 text-[9px] font-bold bg-amber-100 text-amber-600 px-2 py-0.5 rounded-full">Tez kunda</div>
                            <div class="w-14 h-14 bg-amber-50 rounded-2xl flex items-center justify-center text-3xl mx-auto mb-3">{{ item.icon }}</div>
                            <div class="font-bold text-slate-700 text-sm mb-1">{{ item.name }}</div>
                            <div class="text-xs text-slate-400 leading-snug">{{ item.desc }}</div>
                        </div>
                    </template>
                </div>

                <div class="bg-amber-50 border border-amber-100 rounded-2xl p-5 flex items-center gap-4">
                    <span class="text-3xl shrink-0">💡</span>
                    <div class="flex-1">
                        <p class="text-sm font-bold text-amber-800 mb-0.5">Mavzuni kiriting — AI 30 soniyada tayyor material yaratadi</p>
                        <p class="text-xs text-amber-700">PISA, Olimpiada va DTM formatidagi materiallar uchun yuqoridagi kartalardan birini tanlang</p>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.game-appear { animation: gameSlideIn 0.35s ease both; }
@keyframes gameSlideIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
