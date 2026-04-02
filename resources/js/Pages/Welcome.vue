<script setup>
import { ref, computed, watch, onMounted, onUnmounted, defineAsyncComponent } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const GameTypeIllustration = defineAsyncComponent(() => import('@/Components/GameTypeIllustration.vue'));

const page = usePage();

// Auto-dismiss flash messages after 5 seconds
const flashError   = ref(page.props.flash?.error ?? null);
const flashSuccess = ref(page.props.flash?.success ?? null);
let errorTimer, successTimer;
watch(() => page.props.flash?.error, v => {
    flashError.value = v ?? null;
    if (v) { clearTimeout(errorTimer); errorTimer = setTimeout(() => { flashError.value = null; }, 5000); }
}, { immediate: false });
watch(() => page.props.flash?.success, v => {
    flashSuccess.value = v ?? null;
    if (v) { clearTimeout(successTimer); successTimer = setTimeout(() => { flashSuccess.value = null; }, 5000); }
}, { immediate: false });
if (flashError.value) { errorTimer = setTimeout(() => { flashError.value = null; }, 5000); }
if (flashSuccess.value) { successTimer = setTimeout(() => { flashSuccess.value = null; }, 5000); }

// Dismissible BETA banner
const betaDismissed = ref(false);
function dismissBeta() {
    betaDismissed.value = true;
    try { localStorage.setItem('beta_dismissed', '1'); } catch {}
}

const billingCycle      = ref('monthly');
const standardMonthly   = 59000;
const standardYearly    = 47000;
const proMonthly        = 99000;
const proYearly         = 79000;
const standardPrice     = computed(() => billingCycle.value === 'monthly' ? standardMonthly : standardYearly);
const proPrice          = computed(() => billingCycle.value === 'monthly' ? proMonthly : proYearly);
const discountPct       = computed(() => Math.round(((proMonthly - proYearly) / proMonthly) * 100));
const proSaving         = discountPct;

const scrolled = ref(false);
const mobileOpen = ref(false);
const openFaq = ref(null);
const activeFilter = ref('all');

function handleScroll() { scrolled.value = window.scrollY > 30; }
onMounted(() => {
    window.addEventListener('scroll', handleScroll);
    try { if (localStorage.getItem('beta_dismissed') === '1') betaDismissed.value = true; } catch {}
});
onUnmounted(() => window.removeEventListener('scroll', handleScroll));

function scrollTo(id) {
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth' });
    mobileOpen.value = false;
}

function toggleFaq(i) { openFaq.value = openFaq.value === i ? null : i; }

const navLinks = [
    { label: 'Imkoniyatlar', id: 'features' },
    { label: "O'yin turlari", id: 'game-types' },
    { label: 'PDF Materiallar', id: 'pdf-materials' },
    { label: 'Qanday ishlaydi', id: 'how-it-works' },
    { label: 'Tariflar', id: 'pricing' },
    { label: 'Savollar', id: 'faq' },
];

const stats = [
    { value: '30+', label: "O'yin formati",           icon: '🎮' },
    { value: '8+',  label: 'PDF material turi',        icon: '📄' },
    { value: '8',   label: "Asosiy imkoniyat",         icon: '⚡' },
    { value: '100%', label: 'Asosiy funksiyalar bepul', icon: '✅' },
];

const features = [
    {
        icon: '🎮',
        title: "AI O'yin Yaratish",
        desc: "Mavzuni kiriting — 30 soniyada 30+ formatda interaktiv o'yin yaratiladi. Viktorina, anagramma, xotira o'yinlari va boshqalar.",
        color: 'from-indigo-500 to-blue-600',
        bg: 'bg-indigo-50',
        border: 'border-indigo-100',
        link: '/auth/google/redirect',
        linkLabel: 'O\'yin yaratish',
        badges: ['30+ format', 'AI yordamida', '30 soniya'],
    },
    {
        icon: '📄',
        title: 'PDF Materiallar',
        desc: "Chop etish uchun tayyor varaqalar yarating: test varaqlar, anagrammalar, so'z qidirish, moslashtirish, krossword va boshqalar.",
        color: 'from-emerald-500 to-teal-600',
        bg: 'bg-emerald-50',
        border: 'border-emerald-100',
        link: '/auth/google/redirect',
        linkLabel: 'Varaq yaratish',
        badges: ['Chop etish', 'AI tomonidan', '8+ tur'],
    },
    {
        icon: '🎓',
        title: 'Sinf Boshqaruvi',
        desc: "Sinflaringizni yarating, o'quvchilaringizni ro'yxatga qo'shing. O'quvchilar maxsus kod orqali anonimlik bilan qo'shiladi — akkaunt shart emas.",
        color: 'from-violet-500 to-purple-600',
        bg: 'bg-violet-50',
        border: 'border-violet-100',
        link: '/auth/google/redirect',
        linkLabel: 'Sinf yaratish',
        badges: ["Ro'yxat tizimi", 'Kod orqali', 'Akkaunt shart emas'],
    },
    {
        icon: '📊',
        title: 'Live Sessiya Kuzatuvi',
        desc: "O'yin sessiyasini oching va o'quvchilar natijalarini real vaqtda kuzating. Reyting jadvali, eng yaxshi natijalar, sessiya tarixi.",
        color: 'from-orange-500 to-amber-600',
        bg: 'bg-orange-50',
        border: 'border-orange-100',
        link: '/auth/google/redirect',
        linkLabel: 'Sessiya ochish',
        badges: ['Real vaqt', 'Leaderboard', 'Statistika'],
    },
    {
        icon: '🏆',
        title: "O'quvchi Kabineti",
        desc: "O'quvchilar o'z ismlarini kiritib barcha o'yin natijalarini ko'rishlari mumkin. Yutuqlar, o'rtacha ball, birinchilik sonlari.",
        color: 'from-rose-500 to-pink-600',
        bg: 'bg-rose-50',
        border: 'border-rose-100',
        link: '/student',
        linkLabel: "Natijalarni ko'rish",
        badges: ['Akkaunt shart emas', 'Yutuqlar', 'Tarix'],
    },
    {
        icon: '🤖',
        title: 'Aqlli Kontent Tizimi',
        desc: "Mazmunli, sifatli o'yin savollari va topshiriqlar avtomatik yaratiladi. O'zbek, rus, ingliz tillarida bir necha soniyada tayyor.",
        color: 'from-slate-600 to-gray-700',
        bg: 'bg-slate-50',
        border: 'border-slate-200',
        link: '/auth/google/redirect',
        linkLabel: 'Sinab ko\'rish',
        badges: ['Ko\'p tilli', 'Sifatli kontent', 'Tez yaratish'],
    },
    {
        icon: '📚',
        title: 'Ommaviy Kutubxona',
        desc: "Boshqa o'qituvchilar yaratgan o'yinlarni ko'ring. Bir bosish bilan o'z kolleksiyangizga nusxa oling va darsda darhol ishlating.",
        color: 'from-sky-500 to-cyan-600',
        bg: 'bg-sky-50',
        border: 'border-sky-100',
        link: '/library',
        linkLabel: "Kutubxonaga o'tish",
        badges: ['Bepul nusxa', 'Filtrlash', 'Barcha formatlar'],
        isNew: true,
    },
    {
        icon: '🏅',
        title: 'Sertifikat Yaratish',
        desc: "O'quvchilar uchun chop etishga tayyor sertifikat tayyorlang. Ism, mavzu, ball va baho avtomatik ko'rsatiladi.",
        color: 'from-amber-500 to-yellow-600',
        bg: 'bg-amber-50',
        border: 'border-amber-100',
        link: '/certificate',
        linkLabel: 'Sertifikat yaratish',
        badges: ['Chop etish', 'Baho tizimi', 'Bepul'],
        isNew: true,
    },
];

const filters = [
    { key: 'all', label: 'Barchasi' },
    { key: 'quiz', label: 'Viktorina' },
    { key: 'word', label: "So'z" },
    { key: 'memory', label: 'Xotira' },
    { key: 'drag', label: 'Saralash' },
    { key: 'soon', label: 'Tez kunda' },
];

const gameTypes = [
    { code: 'quiz_mcq',       name: 'Viktorina (Test)',      type: 'quiz',   color: 'from-indigo-500 to-blue-600' },
    { code: 'true_false',     name: "To'g'ri / Noto'g'ri",  type: 'quiz',   color: 'from-green-500 to-emerald-600' },
    { code: 'type_answer',    name: 'Javobni yozing',        type: 'quiz',   color: 'from-cyan-500 to-teal-600' },
    { code: 'random_wheel',   name: "Tasodifiy g'ildirak",   type: 'quiz',   color: 'from-amber-500 to-yellow-600' },
    { code: 'whack_mole',     name: "Ko'rsichaqa ur",        type: 'quiz',   color: 'from-lime-500 to-green-600' },
    { code: 'airplane',       name: "Samolyot o'yini",       type: 'quiz',   color: 'from-sky-500 to-blue-500' },
    { code: 'math_quiz',      name: 'Matematik test',        type: 'quiz',   color: 'from-violet-500 to-purple-600' },
    { code: 'game_show_quiz', name: "O'yin shou",            type: 'quiz',   color: 'from-orange-500 to-amber-600' },
    { code: 'millionaire',    name: 'Millioner',             type: 'quiz',   color: 'from-yellow-600 to-amber-700' },
    { code: 'anagram',        name: 'Anagramma',             type: 'word',   color: 'from-pink-500 to-rose-600' },
    { code: 'hangman',        name: "Harfni top",            type: 'word',   color: 'from-gray-600 to-slate-700' },
    { code: 'word_search',    name: "So'z qidirish",         type: 'word',   color: 'from-blue-500 to-indigo-600' },
    { code: 'spell_word',     name: "So'zni yoz",            type: 'word',   color: 'from-teal-500 to-cyan-600' },
    { code: 'spelling',       name: 'Imlo',                  type: 'word',   color: 'from-fuchsia-500 to-pink-600' },
    { code: 'flashcards',     name: 'Flesh-kartochkalar',    type: 'memory', color: 'from-yellow-500 to-orange-500' },
    { code: 'memory_cards',   name: "Xotira o'yini",         type: 'memory', color: 'from-purple-500 to-violet-600' },
    { code: 'open_box',       name: 'Qutini och',            type: 'memory', color: 'from-rose-400 to-pink-500' },
    { code: 'watch_memorize', name: "Ko'rib eslab qol",      type: 'memory', color: 'from-emerald-500 to-teal-600' },
    { code: 'matching_pairs', name: 'Juftlikni top',         type: 'drag',   color: 'from-purple-500 to-violet-600' },
    { code: 'reorder',        name: 'Tartibga sol',          type: 'drag',   color: 'from-teal-500 to-cyan-600' },
    { code: 'group_sort',     name: "Guruh bo'yicha saralash", type: 'drag', color: 'from-orange-500 to-red-500' },
    { code: 'complete_sentence', name: "Gapni to'ldiring",   type: 'drag',   color: 'from-violet-500 to-purple-600' },
    { code: 'pair_or_not',    name: "Juftmi yoki yo'q?",     type: 'drag',   color: 'from-sky-500 to-indigo-600' },
    { code: 'speed_sort',     name: 'Tez saralash',          type: 'drag',   color: 'from-red-500 to-orange-600' },
    { code: 'flying_answers', name: 'Uchuvchi javoblar',     type: 'drag',   color: 'from-indigo-500 to-violet-600' },
    { code: 'diagram',        name: 'Diagramma',             type: 'drag',   color: 'from-blue-600 to-indigo-700' },
    { code: 'zakovat',        name: 'Zakovat',               type: 'quiz',   color: 'from-purple-600 to-fuchsia-600' },
    { code: 'race',           name: 'Poyga',                 type: 'quiz',   color: 'from-red-500 to-orange-500' },
    { code: 'timeline',       name: "Vaqt chizig'i",         type: 'drag',   color: 'from-teal-600 to-cyan-600' },
    { code: 'rope_pull',      name: 'Arqon tortish',         type: 'soon',   color: 'from-green-600 to-emerald-700', soon: true },
    { code: 'sleeping_bear',  name: 'Uyqudagi Ayiq',         type: 'soon',   color: 'from-slate-600 to-gray-700',    soon: true },
    { code: 'crossword',      name: 'Krossvord',             type: 'soon',   color: 'from-indigo-700 to-purple-700', soon: true },
    { code: 'map_quiz',       name: 'Xarita testi',          type: 'soon',   color: 'from-green-700 to-teal-700',    soon: true },
];

const filteredGames = computed(() => {
    if (activeFilter.value === 'all') return gameTypes;
    return gameTypes.filter(g => g.type === activeFilter.value);
});

const pdfMaterials = [
    { icon: '📋', name: 'Test varaq',              desc: "Ko'p tanlovli test — printerdan chiqarib darsda ishlating", color: 'bg-blue-50 border-blue-100 text-blue-700', template: 'quiz_mcq' },
    { icon: '🔤', name: 'Anagramma varaq',         desc: "Harflari aralashtirилган so'zlar — o'quvchilar topadi",    color: 'bg-pink-50 border-pink-100 text-pink-700',   template: 'anagram' },
    { icon: '🔍', name: "So'z qidirish",           desc: "Harflar matritsasida so'z qidirish o'yini varaqasi",       color: 'bg-indigo-50 border-indigo-100 text-indigo-700', template: 'word_search' },
    { icon: '🔗', name: 'Moslashtirish',           desc: "Ikki ustunni chiziq bilan ulash topshirig'i",              color: 'bg-violet-50 border-violet-100 text-violet-700', template: 'matching_pairs' },
    { icon: '📝', name: "Bo'sh joy to'ldirish",    desc: "Gapda tushirilgan so'zlarni to'ldirish varaqasi",          color: 'bg-teal-50 border-teal-100 text-teal-700',   template: 'complete_sentence' },
    { icon: '↕️', name: "Tartiblashtirish",        desc: "Aralashtirilgan gaplar yoki bosqichlarni tartibga solish",  color: 'bg-orange-50 border-orange-100 text-orange-700', template: 'reorder' },
    { icon: '🔠', name: 'Krossword',               desc: 'Topishmoqli krossword varaqasi — tez kunda',              color: 'bg-amber-50 border-amber-100 text-amber-700', soon: true },
    { icon: '✅', name: "To'g'ri/Noto'g'ri varaq", desc: "Gaplarni to'g'ri yoki noto'g'ri deb belgilash",           color: 'bg-green-50 border-green-100 text-green-700', template: 'true_false' },
];

const steps = [
    { n: '1', title: 'Mavzu va format tanlang', desc: "O'yin turi yoki PDF material turini, fanni va mavzuni kiriting", icon: '🎯' },
    { n: '2', title: 'AI yaratadi',             desc: "30 soniyada aqlli tizim sifatli kontent yaratadi — savol, topshiriq yoki varaq", icon: '🤖' },
    { n: '3', title: "Darsda ishlating",        desc: "O'yinni sinf bilan o'ynang yoki varaqni chop etib tarqating", icon: '🎮' },
    { n: '4', title: "Natijalarni kuzating",    desc: "Live sessiyada o'quvchilar natijasini real vaqtda ko'ring", icon: '📊' },
];

const faqs = [
    {
        q: 'Bu platforma bepulmi?',
        a: "Ha, o'qituvchilar uchun bepul. Google akkount orqali tez kirish mumkin. Kuniga 10 ta o'yin yaratish mumkin.",
    },
    {
        q: "O'yin yaratish qancha vaqt oladi?",
        a: "AI yordamida o'yin 15-30 soniyada yaratiladi. PDF varaqalar ham xuddi shu tezlikda tayyor bo'ladi.",
    },
    {
        q: "Sinf boshqaruvi qanday ishlaydi?",
        a: "Sinf yaratgach, maxsus havola hosil bo'ladi. O'quvchilar havolaga kirib ismlarini yozadilar — boshqa hech narsa kerak emas. Akkaunt yoki parol shart emas.",
    },
    {
        q: "O'quvchilar o'z natijalarini qanday ko'radilar?",
        a: "O'quvchilar /student sahifasida o'z ismlarini kiritadilar va barcha o'yin natijalarini, reyting o'rinlarini va yutuqlarini ko'rishlari mumkin.",
    },
    {
        q: "Qanday o'yin turlari mavjud?",
        a: "Hozirda 30 ta faol o'yin turi bor: viktorina, so'z o'yinlari, xotira, saralash va boshqalar. Ko'plab yangi formatlar ishlab chiqilmoqda.",
    },
    {
        q: "PDF varaqalar qanday yaratiladi?",
        a: "Yaratish sahifasida PDF materiallar tabini tanlang. Turni va mavzuni kiriting — AI varaq yaratadi. Keyin brauzerdagi chop etish tugmasi orqali printerdan chiqaring.",
    },
    {
        q: "Live sessiya nima?",
        a: "O'qituvchi sessiya kodi yaratadi va o'quvchilarga beradi. O'quvchilar kodni kiritib o'yinni o'ynashadi. O'qituvchi real vaqtda kimning necha ball olayotganini ko'radi.",
    },
    {
        q: "Ommaviy kutubxona nima?",
        a: "Boshqa o'qituvchilar ommaviy qilgan o'yinlarini ko'rish va bir bosish bilan o'z kolleksiyangizga nusxa olish imkoniyati. Nusxa olganingizdan so'ng o'yin to'liq sizning nazoratingizda bo'ladi.",
    },
    {
        q: "Sertifikat qanday yaratiladi?",
        a: "Sertifikat sahifasida o'quvchi ismi, mavzu, ball va o'qituvchi ismini kiriting. Sertifikat avtomat ravishda tayyorlanadi — brauzerdagi chop etish orqali printerdan chiqaring yoki PDF sifatida saqlang.",
    },
];
</script>

<template>
    <Head title="EDUZONA — O'qituvchilar uchun AI ta'lim platformasi" />

    <!-- BETA banner -->
    <div v-if="!betaDismissed" class="fixed top-0 left-0 right-0 z-[50] bg-gradient-to-r from-amber-500 via-orange-500 to-rose-500 text-white">
        <div class="max-w-7xl mx-auto px-4 py-2 flex items-center justify-between gap-4 text-xs sm:text-sm">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <span class="shrink-0 bg-white/20 border border-white/30 text-white font-black text-[10px] px-2 py-0.5 rounded-md tracking-widest">BETA</span>
                <span class="font-medium truncate">Platforma beta rejimda — Xatolar bo'lsa, bizga xabar bering!</span>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <a href="https://t.me/ilhomjumayev96" target="_blank"
                    class="inline-flex items-center gap-1.5 bg-white/20 hover:bg-white/30 border border-white/30 text-white font-bold px-3 py-1 rounded-lg transition text-xs whitespace-nowrap">
                    💬 Fikr bildirish
                </a>
                <button @click="dismissBeta"
                    class="w-6 h-6 flex items-center justify-center bg-white/20 hover:bg-white/30 rounded-full transition text-white/80 hover:text-white"
                    title="Yopish">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Flash messages -->
    <transition name="fade">
        <div v-if="flashError"
            class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] bg-red-500 text-white px-6 py-3 rounded-2xl shadow-xl text-sm font-medium whitespace-nowrap">
            {{ flashError }}
        </div>
    </transition>
    <transition name="fade">
        <div v-if="flashSuccess"
            class="fixed top-4 left-1/2 -translate-x-1/2 z-[100] bg-green-500 text-white px-6 py-3 rounded-2xl shadow-xl text-sm font-medium whitespace-nowrap">
            {{ flashSuccess }}
        </div>
    </transition>

    <div class="min-h-screen bg-white text-slate-900">

        <!-- ===== NAVIGATION ===== -->
        <nav :class="[
            'fixed left-0 right-0 z-40 transition-all duration-300',
            betaDismissed ? 'top-0' : 'top-9',
            scrolled ? 'bg-white shadow border-b border-slate-100' : 'bg-white/80 backdrop-blur-lg'
        ]">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center h-16 gap-6">
                    <Link href="/" class="flex items-center gap-2.5 shrink-0 mr-2">
                        <div class="w-9 h-9 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white shadow-sm text-base">
                            🎓
                        </div>
                        <div class="leading-none">
                            <div class="font-bold text-slate-900 text-sm">EDUZONA</div>
                            <div class="text-[10px] text-slate-400">Ta'lim platformasi</div>
                        </div>
                    </Link>

                    <div class="hidden md:flex items-center gap-0.5 flex-1">
                        <button
                            v-for="link in navLinks" :key="link.id"
                            @click="scrollTo(link.id)"
                            class="px-3 py-2 text-sm text-slate-600 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition font-medium">
                            {{ link.label }}
                        </button>
                    </div>

                    <div class="flex items-center gap-2 ml-auto">
                        <Link href="/student"
                            class="hidden sm:inline-flex text-xs text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg transition font-medium border border-slate-200 hover:border-slate-300">
                            O'quvchi
                        </Link>
                        <Link href="/admin/login"
                            class="hidden sm:inline-flex text-xs text-slate-500 hover:text-slate-700 px-3 py-2 rounded-lg transition font-medium border border-slate-200 hover:border-slate-300">
                            Admin
                        </Link>
                        <a href="/auth/google/redirect"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-semibold rounded-xl transition shadow-sm shadow-indigo-200">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                            </svg>
                            <span class="hidden sm:inline">Kirish</span>
                            <span class="sm:hidden">Kirish</span>
                        </a>
                        <button @click="mobileOpen = !mobileOpen" class="md:hidden p-2 text-slate-500 hover:text-slate-700">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path v-if="!mobileOpen" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                                <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            <div v-if="mobileOpen" class="md:hidden bg-white border-t border-slate-100 px-4 py-3 space-y-1 shadow-lg">
                <button
                    v-for="link in navLinks" :key="link.id"
                    @click="scrollTo(link.id)"
                    class="block w-full text-left px-4 py-3 text-sm text-slate-700 hover:bg-slate-50 rounded-xl transition font-medium">
                    {{ link.label }}
                </button>
                <div class="pt-2 border-t border-slate-100">
                    <a href="/auth/google/redirect"
                        class="flex items-center gap-2 px-4 py-3 text-sm font-semibold text-indigo-600 hover:bg-indigo-50 rounded-xl transition">
                        Google bilan kirish
                    </a>
                    <Link href="/student"
                        class="flex items-center gap-2 px-4 py-3 text-sm text-slate-500 hover:bg-slate-50 rounded-xl transition">
                        O'quvchi kabineti
                    </Link>
                    <Link href="/admin/login"
                        class="flex items-center gap-2 px-4 py-3 text-sm text-slate-500 hover:bg-slate-50 rounded-xl transition">
                        Admin kirish
                    </Link>
                </div>
            </div>
        </nav>

        <!-- ===== HERO ===== -->
        <section :class="['pb-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-b from-indigo-50/70 via-white to-white', betaDismissed ? 'pt-28' : 'pt-36']">
            <div class="max-w-4xl mx-auto text-center">
                <!-- Social proof badge -->
                <div class="flex flex-wrap items-center justify-center gap-3 mb-6">
                    <div class="inline-flex items-center gap-2 bg-gradient-to-r from-emerald-500 to-teal-500 text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg shadow-emerald-200">
                        <span class="w-2 h-2 bg-white rounded-full animate-pulse"></span>
                        O'zbekiston o'qituvchilari uchun
                    </div>
                    <div class="inline-flex items-center gap-2 bg-gradient-to-r from-indigo-500 to-purple-600 text-white text-xs font-bold px-4 py-2 rounded-full shadow-lg shadow-indigo-200">
                        🤖 AI yordamida
                    </div>
                </div>

                <h1 class="text-4xl sm:text-5xl lg:text-6xl font-extrabold text-slate-900 leading-tight mb-6">
                    Ta'limni yangi
                    <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600"> darajaga</span>
                    <br class="hidden sm:block">
                    olib chiqing
                </h1>

                <p class="text-lg sm:text-xl text-slate-500 mb-10 max-w-2xl mx-auto leading-relaxed">
                    AI o'yinlar, PDF materiallar, sinf boshqaruvi, live natijalar —<br class="hidden sm:block">
                    <strong class="text-slate-700">o'qituvchilar uchun to'liq ta'lim platformasi.</strong>
                </p>

                <div class="flex flex-col sm:flex-row items-center justify-center gap-3">
                    <a href="/auth/google/redirect"
                        class="inline-flex items-center gap-2.5 bg-indigo-600 hover:bg-indigo-700 active:bg-indigo-800 text-white font-bold px-8 py-4 rounded-2xl text-base transition shadow-xl shadow-indigo-200/60">
                        <svg class="w-5 h-5" viewBox="0 0 24 24">
                            <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                            <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                            <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                            <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                        </svg>
                        Google bilan bepul boshlash
                    </a>
                    <button @click="scrollTo('features')"
                        class="inline-flex items-center gap-2 text-slate-600 hover:text-indigo-600 font-semibold px-6 py-4 rounded-2xl border border-slate-200 hover:border-indigo-300 transition text-base bg-white hover:bg-indigo-50/50">
                        Imkoniyatlarni ko'rish
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Stats row -->
            <div class="max-w-3xl mx-auto mt-16 grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div v-for="(s, si) in stats" :key="s.label"
                    :style="`animation-delay: ${si * 80}ms`"
                    class="bg-white rounded-2xl border border-slate-100 shadow-sm p-5 text-center hover:shadow-md transition hero-stat">
                    <div class="text-xl mb-1">{{ s.icon }}</div>
                    <div class="text-2xl font-extrabold text-indigo-600 mb-1">{{ s.value }}</div>
                    <div class="text-xs text-slate-500 font-medium">{{ s.label }}</div>
                </div>
            </div>
        </section>

        <!-- ===== YANGILIKLAR STRIP ===== -->
        <div class="bg-gradient-to-r from-emerald-600 to-teal-600 px-4 py-3">
            <div class="max-w-7xl mx-auto flex flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm text-white">
                <span class="font-black text-xs bg-white/20 border border-white/30 px-2 py-0.5 rounded-md tracking-widest shrink-0">YANGI</span>
                <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-xs font-medium">
                    <span class="flex items-center gap-1.5">📚 <Link href="/library" class="hover:underline font-semibold">Ommaviy Kutubxona</Link> — boshqa o'qituvchilar o'yinlarini ko'rish va nusxa olish</span>
                    <span class="text-white/40 hidden sm:inline">·</span>
                    <span class="flex items-center gap-1.5">🏅 <Link href="/certificate" class="hover:underline font-semibold">Sertifikat yaratish</Link> — o'quvchi uchun chop etishga tayyor sertifikat</span>
                </div>
            </div>
        </div>

        <!-- ===== FEATURES ===== -->
        <section id="features" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Platform imkoniyatlari</h2>
                    <p class="text-slate-500 text-sm max-w-lg mx-auto">
                        EDUZONA — faqat o'yin platforma emas. O'qituvchiga kerak bo'lgan barcha vositalar bir joyda.
                    </p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
                    <div v-for="(f, fi) in features" :key="f.title"
                        :style="`animation-delay: ${fi * 60}ms`"
                        :class="['rounded-2xl border p-5 hover:shadow-md transition-all duration-200 flex flex-col gap-4 feat-appear relative', f.bg, f.border]">
                        <!-- Yangi badge -->
                        <div v-if="f.isNew"
                            class="absolute top-3 right-3 bg-emerald-500 text-white text-[10px] font-black px-2 py-0.5 rounded-full tracking-wide">
                            YANGI
                        </div>
                        <div class="flex items-start gap-3">
                            <div :class="['w-11 h-11 rounded-xl bg-gradient-to-br flex items-center justify-center text-white text-lg shrink-0 shadow-sm', f.color]">
                                {{ f.icon }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <h3 class="font-bold text-slate-800 text-sm mb-1 leading-tight">{{ f.title }}</h3>
                                <p class="text-xs text-slate-500 leading-relaxed">{{ f.desc }}</p>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <span v-for="b in f.badges" :key="b"
                                class="text-[10px] font-semibold bg-white/70 border border-white px-2 py-0.5 rounded-full text-slate-600">
                                {{ b }}
                            </span>
                        </div>
                        <Link :href="f.link"
                            class="mt-auto inline-flex items-center gap-1.5 text-xs font-semibold text-indigo-600 hover:text-indigo-800 transition">
                            {{ f.linkLabel }}
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                            </svg>
                        </Link>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== GAME TYPES ===== -->
        <section id="game-types" class="py-20 px-4 sm:px-6 lg:px-8 bg-slate-50">
            <div class="max-w-7xl mx-auto">
                <div class="text-center mb-10">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">O'yin turlari</h2>
                    <p class="text-slate-500 text-sm max-w-lg mx-auto">
                        Har xil format — har xil o'rganish usuli. O'qituvchi mavzuni kiritadi, AI o'yinni yaratadi.
                    </p>
                </div>

                <!-- Filter tabs -->
                <div class="flex flex-wrap justify-center gap-2 mb-8">
                    <button v-for="f in filters" :key="f.key"
                        @click="activeFilter = f.key"
                        :class="[
                            'px-4 py-2 text-sm font-medium rounded-xl transition',
                            activeFilter === f.key
                                ? 'bg-indigo-600 text-white shadow-sm'
                                : 'bg-white text-slate-600 border border-slate-200 hover:border-indigo-300 hover:text-indigo-600'
                        ]">
                        {{ f.label }}
                        <span v-if="f.key === 'soon'" class="ml-1 text-[10px] bg-amber-400 text-amber-900 px-1.5 py-0.5 rounded-full font-bold">
                            {{ gameTypes.filter(g => g.soon).length }}
                        </span>
                    </button>
                </div>

                <!-- Games grid -->
                <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
                    <div v-for="game in filteredGames" :key="game.code"
                        class="bg-white rounded-2xl border border-slate-100 overflow-hidden hover:shadow-md hover:border-slate-200 transition-all duration-200 relative">

                        <div v-if="game.soon"
                            class="absolute top-2 right-2 z-10 bg-amber-400 text-amber-900 text-[10px] font-bold px-2 py-0.5 rounded-full leading-tight">
                            Tez kunda
                        </div>

                        <div :class="['h-28 bg-gradient-to-br flex items-center justify-center p-3 relative overflow-hidden', game.color]">
                            <div class="absolute -top-3 -right-3 w-16 h-16 bg-white/10 rounded-full"></div>
                            <div class="absolute -bottom-4 -left-4 w-20 h-20 bg-white/10 rounded-full"></div>
                            <GameTypeIllustration :code="game.code" class="w-full h-full relative z-10" />
                        </div>

                        <div class="p-3">
                            <div class="text-xs font-semibold text-slate-800 leading-tight min-h-[2.5rem] flex items-start">
                                {{ game.name }}
                            </div>
                            <div class="mt-2">
                                <a v-if="!game.soon" href="/auth/google/redirect"
                                    class="text-[11px] text-indigo-600 font-semibold hover:underline">
                                    Boshlash →
                                </a>
                                <span v-else class="text-[11px] text-amber-500 font-medium">Ishlab chiqilmoqda...</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-10">
                    <a href="/auth/google/redirect"
                        class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-8 py-3.5 rounded-2xl text-sm transition shadow-md shadow-indigo-200">
                        Hoziroq o'yin yaratish
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </a>
                </div>
            </div>
        </section>

        <!-- ===== PDF MATERIALS ===== -->
        <section id="pdf-materials" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="max-w-6xl mx-auto">
                <div class="text-center mb-12">
                    <div class="inline-flex items-center gap-2 bg-emerald-100 text-emerald-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4">
                        📄 Yangi imkoniyat
                    </div>
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">PDF Materiallar</h2>
                    <p class="text-slate-500 text-sm max-w-lg mx-auto">
                        Chop etish uchun tayyor varaqalar — AI mavzuingizga moslab yaratadi. Printerdan chiqarib darsda ishlating.
                    </p>
                </div>

                <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4">
                    <div v-for="mat in pdfMaterials" :key="mat.name" class="relative">
                        <a v-if="!mat.soon" href="/auth/google/redirect"
                            :class="['flex flex-col items-start gap-3 p-5 rounded-2xl border hover:shadow-md transition-all duration-200 h-full', mat.color]">
                            <div class="text-2xl">{{ mat.icon }}</div>
                            <div>
                                <div class="font-bold text-sm mb-1">{{ mat.name }}</div>
                                <div class="text-xs opacity-70 leading-relaxed">{{ mat.desc }}</div>
                            </div>
                            <div class="mt-auto text-xs font-semibold opacity-80 flex items-center gap-1">
                                Yaratish →
                            </div>
                        </a>
                        <div v-else :class="['flex flex-col items-start gap-3 p-5 rounded-2xl border h-full opacity-60', mat.color]">
                            <div class="text-2xl">{{ mat.icon }}</div>
                            <div>
                                <div class="font-bold text-sm mb-1">{{ mat.name }}</div>
                                <div class="text-xs opacity-70 leading-relaxed">{{ mat.desc }}</div>
                            </div>
                            <div class="mt-auto">
                                <span class="text-[10px] font-bold bg-amber-400 text-amber-900 px-2 py-0.5 rounded-full">Tez kunda</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 bg-gradient-to-r from-emerald-50 to-teal-50 border border-emerald-100 rounded-2xl p-6 flex flex-col sm:flex-row items-center gap-4">
                    <div class="text-3xl">🖨️</div>
                    <div class="flex-1 text-center sm:text-left">
                        <div class="font-bold text-slate-800 mb-1">Chop etish varaqalarini bepul yarating</div>
                        <div class="text-sm text-slate-500">AI mavzuingizga mos varaq tayyorlaydi. Brauzerdagi chop etish orqali printerdan chiqaring.</div>
                    </div>
                    <a href="/auth/google/redirect"
                        class="shrink-0 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-6 py-3 rounded-xl text-sm transition shadow-sm">
                        Boshqarish →
                    </a>
                </div>
            </div>
        </section>

        <!-- ===== AI VIDEO DARSLAR ===== -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-violet-900 to-indigo-900">
            <div class="max-w-5xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <!-- Chap: matn -->
                    <div>
                        <div class="inline-flex items-center gap-2 bg-white/10 text-violet-200 text-xs font-bold px-3 py-1.5 rounded-full mb-4">
                            🎬 YANGI IMKONIYAT
                        </div>
                        <h2 class="text-3xl sm:text-4xl font-extrabold text-white mb-4 leading-tight">
                            Masaladan <span class="text-violet-300">AI video dars</span>ga
                        </h2>
                        <p class="text-violet-200 text-lg mb-6 leading-relaxed">
                            Istalgan masala yoki savolni kiriting — AI yechib, <strong class="text-white">qora doska uslubida video dars</strong> tayyorlaydi. O'zbek tilida.
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li v-for="item in [
                                '📐 Matematika, fizika, kimyo, biologiya — barcha fanlar',
                                '🖊 Qora doska uslubi — chalk animatsiyasi',
                                '🇺🇿 O\'zbek tilida — formula va tushuntirish',
                                '⚡ 30 soniyada tayyor — AI avtomatik yechadi',
                            ]" :key="item" class="flex items-center gap-3 text-violet-100 text-sm">
                                <svg class="w-4 h-4 text-violet-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                </svg>
                                {{ item }}
                            </li>
                        </ul>
                        <Link
                            :href="$page.props.auth?.user ? '/ai-video' : '/register'"
                            class="inline-flex items-center gap-2 bg-white text-indigo-700 hover:bg-violet-50 font-bold px-6 py-3 rounded-xl shadow-lg transition text-sm">
                            🎬 Bepul sinab ko'rish
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </Link>
                    </div>

                    <!-- O'ng: demo ko'rinish -->
                    <div class="bg-black/40 rounded-2xl p-5 border border-white/10 backdrop-blur-sm">
                        <!-- Doska simulyatsiyasi -->
                        <div class="bg-slate-800 rounded-xl p-5 font-mono text-sm space-y-3 min-h-[260px] relative overflow-hidden">
                            <div class="absolute inset-0 opacity-10"
                                style="background-image: repeating-linear-gradient(0deg, transparent, transparent 24px, rgba(255,255,255,.1) 24px, rgba(255,255,255,.1) 25px)">
                            </div>
                            <p class="text-yellow-300 font-bold text-base text-center relative">Nyuton II qonuni</p>
                            <div class="border-t border-white/10 pt-3 space-y-2 relative">
                                <p class="text-slate-300 text-xs">Berilgan: m = 2 kg, F = 10 N</p>
                                <p class="text-green-300 text-center text-lg font-bold">F = m · a</p>
                                <p class="text-slate-300 text-xs text-center">↓</p>
                                <p class="text-green-300 text-center">a = F / m = 10 / 2</p>
                                <div class="mt-3 bg-green-900/50 border border-green-600/40 rounded-lg p-2 text-center">
                                    <p class="text-green-300 font-black text-xl">a = 5 m/s²</p>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 flex items-center justify-between text-xs">
                            <span class="text-violet-300 flex items-center gap-1">
                                <span class="w-2 h-2 bg-green-400 rounded-full animate-pulse inline-block"></span>
                                Video tayyor — 15 soniya
                            </span>
                            <span class="text-white/40">grok-imagine-video</span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CLASSROOM MANAGEMENT ===== -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-violet-50 to-indigo-50">
            <div class="max-w-5xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                    <div>
                        <div class="inline-flex items-center gap-2 bg-violet-100 text-violet-700 text-xs font-bold px-4 py-1.5 rounded-full mb-4">
                            🎓 Sinf boshqaruvi
                        </div>
                        <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-4">
                            O'quvchilaringizni bir joyda boshqaring
                        </h2>
                        <p class="text-slate-500 mb-6 leading-relaxed">
                            Sinflaringizni yarating va o'quvchilarni ro'yxatga qo'shing. O'quvchilar maxsus havola orqali ismlarini kiritib qo'shiladi — akkaunt yoki parol kerak emas.
                        </p>
                        <ul class="space-y-3 mb-8">
                            <li v-for="item in [
                                { icon: '✅', text: 'Sinf yarating (5A, 6B, va h.k.)' },
                                { icon: '🔗', text: 'Havola yoki kodni o\'quvchilarga yuboring' },
                                { icon: '👤', text: 'O\'quvchilar faqat ismlarini kiritadi' },
                                { icon: '📊', text: 'Sessiya natijalarini sinf bo\'yicha kuzating' },
                            ]" :key="item.text" class="flex items-center gap-3 text-sm text-slate-600">
                                <span class="text-lg">{{ item.icon }}</span>
                                {{ item.text }}
                            </li>
                        </ul>
                        <a href="/auth/google/redirect"
                            class="inline-flex items-center gap-2 bg-violet-600 hover:bg-violet-700 text-white font-semibold px-6 py-3 rounded-xl text-sm transition shadow-md shadow-violet-200">
                            Sinf yaratish →
                        </a>
                    </div>

                    <!-- Visual demo card -->
                    <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden">
                        <div class="bg-gradient-to-r from-violet-600 to-indigo-600 p-5 text-white">
                            <div class="text-sm font-bold mb-1">5A sinf — Matematika</div>
                            <div class="text-violet-200 text-xs">Qo'shilish kodi: <span class="font-mono font-bold text-white">ABC123</span></div>
                        </div>
                        <div class="divide-y divide-slate-100">
                            <div v-for="(student, i) in [
                                { name: 'Alisher Karimov', score: 95, medal: '🥇' },
                                { name: 'Malika Yusupova', score: 88, medal: '🥈' },
                                { name: 'Bobur Toshmatov', score: 82, medal: '🥉' },
                                { name: 'Zulfiya Hasanova', score: 76, medal: '' },
                                { name: 'Jasur Mirzayev',  score: 71, medal: '' },
                            ]" :key="i" class="px-5 py-3 flex items-center gap-3">
                                <div class="w-7 h-7 bg-violet-100 rounded-full flex items-center justify-center text-xs font-bold text-violet-700 shrink-0">
                                    {{ student.name.charAt(0) }}
                                </div>
                                <span class="flex-1 text-sm text-slate-700 font-medium">{{ student.name }}</span>
                                <span class="text-xs text-slate-400 mr-1">{{ student.score }} ball</span>
                                <span v-if="student.medal" class="text-base">{{ student.medal }}</span>
                            </div>
                        </div>
                        <div class="px-5 py-3 bg-slate-50 text-xs text-slate-400 text-center">
                            Live natijalar · Real vaqt yangilanadi
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== HOW IT WORKS ===== -->
        <section id="how-it-works" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="max-w-5xl mx-auto">
                <div class="text-center mb-14">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Qanday ishlaydi?</h2>
                    <p class="text-slate-500 text-sm">4 ta oddiy qadam — zamonaviy dars</p>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                    <div v-for="(step, i) in steps" :key="i" class="text-center relative">
                        <div v-if="i < steps.length - 1"
                            class="hidden lg:block absolute top-7 left-[calc(50%+2rem)] right-[calc(-50%+2rem)] h-0.5 bg-gradient-to-r from-indigo-200 to-indigo-100">
                        </div>
                        <div class="w-14 h-14 bg-indigo-100 rounded-2xl flex items-center justify-center text-2xl mx-auto mb-4 relative z-10">
                            {{ step.icon }}
                        </div>
                        <div class="text-xs font-bold text-indigo-500 mb-1 uppercase tracking-wider">{{ step.n }}-qadam</div>
                        <h3 class="font-bold text-slate-800 text-base mb-2">{{ step.title }}</h3>
                        <p class="text-sm text-slate-500 leading-relaxed">{{ step.desc }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== PRICING ===== -->
        <section id="pricing" class="py-24 px-4 sm:px-6 lg:px-8 bg-slate-50 relative overflow-hidden">
            <div class="absolute inset-0 bg-[radial-gradient(ellipse_at_top,_var(--tw-gradient-stops))] from-indigo-100/40 via-transparent to-transparent pointer-events-none"></div>

            <div class="max-w-7xl mx-auto relative">

                <!-- Header -->
                <div class="text-center mb-14">
                    <div class="inline-flex items-center gap-2 bg-indigo-100 text-indigo-700 text-xs font-bold px-4 py-1.5 rounded-full mb-5 uppercase tracking-wider">
                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                        Tariflar
                    </div>
                    <h2 class="text-3xl sm:text-4xl font-extrabold text-slate-900 mb-4 leading-tight">
                        Har bir o'qituvchiga mos <span class="text-transparent bg-clip-text bg-gradient-to-r from-indigo-600 to-purple-600">tarif</span>
                    </h2>
                    <p class="text-slate-500 text-base max-w-xl mx-auto mb-8">
                        Bepul boshlang — kerak bo'lganda yangilang. Hech qanday majburiyatsiz.
                    </p>

                    <!-- Trust row -->
                    <div class="flex flex-wrap items-center justify-center gap-5 mb-10 text-sm text-slate-500">
                        <div class="flex items-center gap-2">
                            <div class="flex -space-x-2">
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-indigo-400 to-indigo-600 border-2 border-white flex items-center justify-center text-white text-[10px] font-bold">A</div>
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-emerald-400 to-emerald-600 border-2 border-white flex items-center justify-center text-white text-[10px] font-bold">M</div>
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-violet-400 to-violet-600 border-2 border-white flex items-center justify-center text-white text-[10px] font-bold">Z</div>
                                <div class="w-7 h-7 rounded-full bg-gradient-to-br from-amber-400 to-orange-500 border-2 border-white flex items-center justify-center text-white text-[10px] font-bold">+</div>
                            </div>
                            <span><strong class="text-slate-700">1 000+</strong> o'qituvchi allaqachon foydalanmoqda</span>
                        </div>
                        <span class="hidden sm:inline text-slate-300">·</span>
                        <div class="flex items-center gap-1 text-amber-500">
                            <span>★★★★★</span>
                            <span class="text-slate-500 text-xs">O'qituvchilar bahosi</span>
                        </div>
                        <span class="hidden sm:inline text-slate-300">·</span>
                        <div class="flex items-center gap-1.5 text-xs text-slate-500">
                            <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                            Karta shart emas · Istalgan vaqt bekor qilish
                        </div>
                    </div>

                    <!-- Billing toggle -->
                    <div class="inline-flex items-center gap-1 bg-white border border-slate-200 rounded-xl p-1 shadow-sm">
                        <button @click="billingCycle = 'monthly'"
                            :class="['px-5 py-2 text-sm font-semibold rounded-lg transition-all duration-200', billingCycle === 'monthly' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                            Oylik
                        </button>
                        <button @click="billingCycle = 'yearly'"
                            :class="['px-5 py-2 text-sm font-semibold rounded-lg transition-all duration-200 flex items-center gap-2', billingCycle === 'yearly' ? 'bg-indigo-600 text-white shadow-sm' : 'text-slate-500 hover:text-slate-700']">
                            Yillik
                            <span :class="['text-[10px] font-black px-2 py-0.5 rounded-full transition-colors', billingCycle === 'yearly' ? 'bg-white/20 text-white' : 'bg-emerald-100 text-emerald-700']">
                                −{{ discountPct }}%
                            </span>
                        </button>
                    </div>
                </div>

                <!-- 4 Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 items-start">

                    <!-- ─── START (FREE) ─── -->
                    <div class="bg-white rounded-3xl border border-slate-200 p-6 flex flex-col gap-4 hover:shadow-lg hover:border-slate-300 hover:-translate-y-0.5 transition-all duration-300">
                        <!-- Plan label -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Start</span>
                            </div>
                            <span class="text-[10px] font-bold bg-emerald-50 text-emerald-600 px-2 py-1 rounded-full">Bepul</span>
                        </div>

                        <!-- Price -->
                        <div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl font-black text-slate-900 tracking-tight">0</span>
                                <span class="text-slate-400 text-sm">so'm / oy</span>
                            </div>
                            <p class="text-slate-500 text-xs mt-1">1 daqiqada ro'yxatdan o'ting</p>
                        </div>

                        <!-- LIMIT highlight -->
                        <div class="bg-amber-50 border border-amber-200 rounded-2xl p-3">
                            <div class="flex items-center gap-2 mb-1">
                                <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
                                <span class="text-xs font-black text-amber-700">Oylik limit: 10 ta o'yin</span>
                            </div>
                            <p class="text-[11px] text-amber-600 leading-relaxed">
                                Limit tugadi → davom ettirish uchun Pro ga o'ting
                            </p>
                        </div>

                        <div class="h-px bg-slate-100"></div>

                        <!-- Features -->
                        <div class="space-y-2 flex-1">
                            <div v-for="item in [
                                { t: '30+ interaktiv o\'yin formati', icon: '🎮' },
                                { t: '8+ PDF material (test, worksheet)', icon: '📄' },
                                { t: 'Sinf boshqaruvi va ro\'yxat', icon: '🎓' },
                                { t: 'Live sessiya — real vaqt natijalar', icon: '📊' },
                                { t: 'O\'quvchi kabineti va yutuqlar', icon: '🏆' },
                                { t: 'Sertifikat yaratish va chop etish', icon: '🏅' },
                                { t: 'Ommaviy kutubxona — nusxa olish', icon: '📚' },
                                { t: 'Oddiy savol yechimi va izohlar', icon: '💬' },
                            ]" :key="item.t" class="flex items-center gap-2.5 text-sm text-slate-600">
                                <svg class="w-3.5 h-3.5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ item.t }}
                            </div>
                        </div>

                        <a href="/auth/google/redirect"
                            class="block w-full text-center bg-slate-900 hover:bg-slate-700 text-white font-bold py-3 rounded-2xl transition-all duration-200 text-sm group">
                            Bepul boshlash <span class="group-hover:translate-x-0.5 inline-block transition-transform">→</span>
                        </a>
                    </div>

                    <!-- ─── STANDARD ─── -->
                    <div class="bg-white rounded-3xl border border-blue-200 p-6 flex flex-col gap-4 hover:shadow-lg hover:border-blue-300 hover:-translate-y-0.5 transition-all duration-300">
                        <!-- Plan label -->
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-blue-500"></span>
                                <span class="text-xs font-black text-blue-400 uppercase tracking-widest">Standard</span>
                            </div>
                            <Transition name="fade">
                                <span v-if="billingCycle === 'yearly'" class="text-[10px] font-bold bg-emerald-100 text-emerald-700 px-2 py-1 rounded-full">−{{ discountPct }}%</span>
                            </Transition>
                        </div>

                        <!-- Price -->
                        <div>
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl font-black text-slate-900 tracking-tight">{{ standardPrice.toLocaleString('uz-UZ') }}</span>
                                <span class="text-slate-400 text-sm">so'm / oy</span>
                            </div>
                            <p class="text-slate-500 text-xs mt-1">Kundalik darslar uchun</p>
                        </div>

                        <!-- Game limit highlight -->
                        <div class="bg-blue-50 border border-blue-100 rounded-2xl p-3">
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                <span class="text-xs font-black text-blue-700">Oyda 100–150 ta o'yin</span>
                            </div>
                            <p class="text-[11px] text-blue-500 mt-1">Har kunlik dars uchun yetarli</p>
                        </div>

                        <div class="h-px bg-blue-100"></div>

                        <!-- Features -->
                        <div class="space-y-2 flex-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Start tarifidagi hamma narsa +</p>
                            <div v-for="item in [
                                { t: 'Misollarni bosqichma-bosqich yechish', icon: '➗', soon: false },
                                { t: 'Har bir savolga kengroq tushuntirish', icon: '💡', soon: false },
                                { t: 'Test va topshiriqlar yaratish', icon: '📋', soon: false },
                                { t: 'Fayl yuklash → oddiy tekshiruv', icon: '📄', soon: true },
                                { t: 'O\'quvchi natijalarini ko\'rish', icon: '📊', soon: false },
                                { t: 'Qisqa xulosa va tavsiyalar', icon: '📌', soon: true },
                            ]" :key="item.t" class="flex items-center gap-2.5 text-sm text-slate-600">
                                <svg class="w-3.5 h-3.5 text-blue-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span class="flex-1">{{ item.t }}</span>
                                <span v-if="item.soon" class="text-[9px] bg-amber-100 text-amber-600 font-bold px-1.5 py-0.5 rounded-full shrink-0">tez kunda</span>
                            </div>
                        </div>

                        <button disabled class="block w-full text-center bg-blue-50 text-blue-300 font-bold py-3 rounded-2xl text-sm cursor-not-allowed border border-blue-100">
                            Tez kunda ochiladi
                        </button>
                    </div>

                    <!-- ─── PRO ─── (MOST POPULAR) -->
                    <div class="relative bg-gradient-to-b from-indigo-600 via-indigo-700 to-purple-800 rounded-3xl p-6 flex flex-col gap-4 shadow-2xl shadow-indigo-400/30 ring-1 ring-inset ring-white/10 xl:-mt-6">
                        <!-- Decorations -->
                        <div class="absolute -inset-px rounded-3xl bg-gradient-to-b from-white/10 to-transparent pointer-events-none"></div>
                        <div class="absolute -bottom-12 left-1/2 -translate-x-1/2 w-3/4 h-24 bg-indigo-400/20 blur-3xl pointer-events-none rounded-full"></div>
                        <div class="absolute top-0 right-0 w-36 h-36 bg-white/5 rounded-full -translate-y-1/2 translate-x-1/2 pointer-events-none"></div>

                        <!-- Popular badge -->
                        <div class="absolute -top-4 left-1/2 -translate-x-1/2 flex items-center gap-1.5 bg-gradient-to-r from-amber-400 to-orange-400 text-amber-900 text-xs font-black px-4 py-1.5 rounded-full whitespace-nowrap shadow-lg shadow-amber-200/50">
                            🔥 Eng mashhur tarif
                        </div>

                        <!-- Plan label -->
                        <div class="relative flex items-center justify-between pt-1">
                            <div class="flex items-center gap-2">
                                <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                                <span class="text-xs font-black text-indigo-300 uppercase tracking-widest">Pro</span>
                            </div>
                            <Transition name="fade">
                                <span v-if="billingCycle === 'yearly'" class="text-[10px] font-bold bg-emerald-400/20 border border-emerald-400/30 text-emerald-300 px-2 py-1 rounded-full">−{{ discountPct }}%</span>
                            </Transition>
                        </div>

                        <!-- Price -->
                        <div class="relative">
                            <div class="flex items-baseline gap-1.5">
                                <span class="text-4xl font-black text-white tracking-tight">{{ proPrice.toLocaleString('uz-UZ') }}</span>
                                <span class="text-indigo-300 text-sm">so'm / oy</span>
                            </div>
                            <p class="text-indigo-300 text-xs mt-1">To'liq dars tizimi — limitisiz</p>
                        </div>

                        <!-- Unlimited highlight -->
                        <div class="relative bg-white/10 border border-white/20 rounded-2xl p-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base">♾️</span>
                                <span class="text-xs font-black text-white">Cheksiz o'yin yaratish</span>
                            </div>
                            <p class="text-[11px] text-indigo-300 mt-1">Kunlik yoki oylik limit yo'q</p>
                        </div>

                        <div class="relative h-px bg-white/10"></div>

                        <!-- Features -->
                        <div class="relative space-y-2 flex-1">
                            <p class="text-[10px] font-black text-indigo-300 uppercase tracking-widest mb-1">Standard tarifidagi hamma narsa +</p>

                            <!-- Video feature — hero -->
                            <div class="bg-white/10 border border-white/15 rounded-xl p-2.5 mb-2">
                                <div class="flex items-start gap-2">
                                    <span class="text-sm mt-0.5">🎥</span>
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <span class="text-sm font-bold text-white">Yechimni video ko'rish</span>
                                            <span class="text-[9px] bg-rose-500/30 border border-rose-400/40 text-rose-300 font-black px-1.5 py-0.5 rounded-full">ENG KUCHLI</span>
                                        </div>
                                        <p class="text-[11px] text-indigo-300 mt-0.5">savol → 1 klikda video tushuntirish</p>
                                    </div>
                                </div>
                            </div>

                            <div v-for="item in [
                                { t: 'Har qanday fan bo\'yicha to\'liq yechim', icon: '➗', soon: false },
                                { t: 'Masalani video darsga aylantirish', icon: '📹', soon: false },
                                { t: 'To\'liq PDF material generatsiyasi', icon: '📄', soon: false },
                                { t: 'Fayllarni yuklash va avtomatik tekshirish', icon: '📋', soon: true },
                                { t: 'Avtomatik baholash va natija', icon: '✅', soon: true },
                                { t: 'O\'quvchi tahlili — kim nimani tushunmadi', icon: '🔍', soon: true },
                                { t: 'Shaxsiy o\'rganish tavsiyalari', icon: '🎯', soon: true },
                                { t: 'Batafsil o\'qituvchi hisobotlari', icon: '📈', soon: true },
                                { t: 'Ustuvor qo\'llab-quvvatlash', icon: '🛡️', soon: false },
                            ]" :key="item.t" class="flex items-center gap-2.5 text-sm text-indigo-100">
                                <svg class="w-3.5 h-3.5 text-indigo-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                <span class="flex-1">{{ item.t }}</span>
                                <span v-if="item.soon" class="text-[9px] bg-amber-400/20 border border-amber-400/30 text-amber-300 font-bold px-1.5 py-0.5 rounded-full shrink-0 whitespace-nowrap">tez kunda</span>
                            </div>
                        </div>

                        <button disabled class="relative block w-full text-center bg-white text-indigo-700 font-black py-3.5 rounded-2xl text-sm cursor-not-allowed shadow-lg shadow-indigo-900/40">
                            Pro ga o'tish — tez kunda
                        </button>
                    </div>

                    <!-- ─── INSTITUTION ─── -->
                    <div class="bg-slate-900 rounded-3xl p-6 flex flex-col gap-4 hover:shadow-lg hover:bg-slate-800 hover:-translate-y-0.5 transition-all duration-300 ring-1 ring-slate-700/50">
                        <!-- Plan label -->
                        <div class="flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-violet-400"></span>
                            <span class="text-xs font-black text-slate-400 uppercase tracking-widest">Institution</span>
                        </div>

                        <!-- Price -->
                        <div>
                            <div class="text-3xl font-black text-white tracking-tight">Shartnoma</div>
                            <p class="text-slate-400 text-xs mt-1">Maktab, litsey, texnikum va universitetlar</p>
                        </div>

                        <!-- Multi-user highlight -->
                        <div class="bg-violet-950/60 border border-violet-700/40 rounded-2xl p-3">
                            <div class="flex items-center gap-2">
                                <span class="text-base">👥</span>
                                <span class="text-xs font-black text-violet-300">Ko'p o'qituvchi — yagona tizim</span>
                            </div>
                            <p class="text-[11px] text-slate-500 mt-1">Barcha sinf va fanlar bitta panelda</p>
                        </div>

                        <div class="h-px bg-slate-700/50"></div>

                        <!-- Features -->
                        <div class="space-y-2 flex-1">
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pro tarifidagi hamma narsa +</p>
                            <div v-for="item in [
                                { t: 'Admin panel (direktor / dekan uchun)', icon: '🏫' },
                                { t: 'Tashkilot bo\'yicha umumiy tahlil', icon: '📊' },
                                { t: 'Qaysi sinf past — AI xulosa', icon: '🧠' },
                                { t: 'O\'qituvchilar ishini tekshirish', icon: '📝' },
                                { t: 'HEMIS va tizimlar bilan integratsiya', icon: '🔗' },
                                { t: 'Ajratilgan server va xavfsizlik', icon: '🖥️' },
                                { t: 'Maxsus brending va API', icon: '🏷️' },
                                { t: '99.9% uptime SLA kafolati', icon: '🛡️' },
                            ]" :key="item.t" class="flex items-center gap-2.5 text-sm text-slate-300">
                                <svg class="w-3.5 h-3.5 text-slate-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                {{ item.t }}
                            </div>
                        </div>

                        <a href="https://t.me/ilhomjumayev96" target="_blank"
                            class="block w-full text-center bg-white hover:bg-slate-100 text-slate-900 font-bold py-3 rounded-2xl transition-all duration-200 text-sm group">
                            Bog'lanish <span class="group-hover:translate-x-0.5 inline-block transition-transform">→</span>
                        </a>
                    </div>
                </div>

                <!-- Bottom note -->
                <p class="text-center text-xs text-slate-400 mt-10 flex flex-wrap items-center justify-center gap-x-4 gap-y-1">
                    <span class="flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-green-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd"/></svg>
                        256-bit xavfsiz shifrlash
                    </span>
                    <span class="text-slate-600">·</span>
                    <span>Karta ma'lumoti shart emas</span>
                    <span class="text-slate-600">·</span>
                    <span>Istalgan vaqt bekor qilish mumkin</span>
                </p>
            </div>
        </section>

        <!-- ===== FAQ ===== -->
        <section id="faq" class="py-20 px-4 sm:px-6 lg:px-8 bg-white">
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-12">
                    <h2 class="text-2xl sm:text-3xl font-bold text-slate-900 mb-3">Ko'p so'raladigan savollar</h2>
                </div>

                <div class="space-y-3">
                    <div v-for="(faq, i) in faqs" :key="i"
                        :style="`animation-delay: ${i * 60}ms`"
                        class="bg-white rounded-2xl border border-slate-100 overflow-hidden hover:border-indigo-100 transition faq-appear">
                        <button @click="toggleFaq(i)"
                            class="w-full flex items-center justify-between px-5 py-4 text-left gap-4">
                            <span class="font-semibold text-slate-800 text-sm">{{ faq.q }}</span>
                            <svg :class="['w-4 h-4 text-slate-400 transition-transform duration-200 shrink-0', openFaq === i ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <Transition name="faq-slide">
                            <div v-if="openFaq === i" class="px-5 pb-4">
                                <p class="text-sm text-slate-600 leading-relaxed">{{ faq.a }}</p>
                            </div>
                        </Transition>
                    </div>
                </div>
            </div>
        </section>

        <!-- ===== CTA BANNER ===== -->
        <section class="py-20 px-4 sm:px-6 lg:px-8 bg-gradient-to-br from-indigo-600 via-indigo-700 to-purple-700">
            <div class="max-w-3xl mx-auto text-center">
                <h2 class="text-2xl sm:text-3xl font-bold text-white mb-4">
                    Bugun boshlab ko'ring!
                </h2>
                <p class="text-indigo-200 mb-3 text-base max-w-xl mx-auto">
                    Google akkountingiz bilan 1 daqiqada ro'yxatdan o'ting va birinchi o'yiningizni yarating.
                </p>
                <p class="text-indigo-300 mb-8 text-sm">
                    O'yinlar · PDF materiallar · Sinf boshqaruvi · Live natijalar — barchasi bepul
                </p>
                <a href="/auth/google/redirect"
                    class="inline-flex items-center gap-3 bg-white text-indigo-700 font-bold px-8 py-4 rounded-2xl text-base hover:bg-indigo-50 transition shadow-xl">
                    <svg class="w-5 h-5" viewBox="0 0 24 24">
                        <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                        <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                        <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                        <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                    </svg>
                    Bepul boshlash
                </a>
            </div>
        </section>

        <!-- ===== FOOTER ===== -->
        <footer class="py-10 px-4 sm:px-6 lg:px-8 bg-slate-900">
            <div class="max-w-7xl mx-auto">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-6">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-sm shadow-sm">
                            🎓
                        </div>
                        <div>
                            <div class="font-bold text-white text-sm leading-none">EDUZONA</div>
                            <div class="text-slate-500 text-xs mt-0.5">O'qituvchilar uchun AI ta'lim platformasi</div>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center justify-center gap-4 text-xs text-slate-500">
                        <button @click="scrollTo('features')" class="hover:text-slate-300 transition">Imkoniyatlar</button>
                        <button @click="scrollTo('game-types')" class="hover:text-slate-300 transition">O'yin turlari</button>
                        <button @click="scrollTo('pdf-materials')" class="hover:text-slate-300 transition">PDF Materiallar</button>
                        <button @click="scrollTo('how-it-works')" class="hover:text-slate-300 transition">Qanday ishlaydi</button>
                        <button @click="scrollTo('pricing')" class="hover:text-slate-300 transition">Tariflar</button>
                        <button @click="scrollTo('faq')" class="hover:text-slate-300 transition">Savollar</button>
                        <Link href="/student" class="hover:text-slate-300 transition">O'quvchi kabineti</Link>
                        <Link href="/admin/login" class="hover:text-slate-300 transition">Admin</Link>
                    </div>
                </div>

                <div class="border-t border-slate-800 mt-8 pt-6 flex flex-col sm:flex-row items-center justify-between gap-3 text-xs text-slate-600">
                    <div class="flex flex-col sm:flex-row items-center gap-2 text-center sm:text-left">
                        <span>© 2026 EDUZONA. Barcha huquqlar himoyalangan.</span>
                        <span class="hidden sm:inline text-slate-700">·</span>
                        <span>Platforma ishlab chiquvchisi: <span class="text-slate-400">Jumayev Ilhom</span></span>
                        <span class="hidden sm:inline text-slate-700">·</span>
                        <a href="https://t.me/ilhomjumayev96" target="_blank" class="text-indigo-400 hover:text-indigo-300 transition">@ilhomjumayev96</a>
                    </div>
                    <span class="flex items-center gap-1.5">
                        <span class="w-1.5 h-1.5 bg-green-500 rounded-full"></span>
                        Barcha tizimlar ishlayapti
                    </span>
                </div>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s, transform 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; transform: translateY(-8px) translateX(-50%); }
.hero-stat { animation: heroStatPop 0.5s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes heroStatPop {
    from { opacity: 0; transform: scale(0.8) translateY(10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
.feat-appear { animation: featSlideIn 0.4s ease both; }
@keyframes featSlideIn {
    from { opacity: 0; transform: translateY(16px); }
    to   { opacity: 1; transform: translateY(0); }
}
.faq-appear { animation: faqFadeIn 0.3s ease both; }
@keyframes faqFadeIn {
    from { opacity: 0; transform: translateX(-8px); }
    to   { opacity: 1; transform: translateX(0); }
}
.faq-slide-enter-active { transition: max-height 0.25s ease, opacity 0.2s ease; overflow: hidden; max-height: 300px; }
.faq-slide-leave-active { transition: max-height 0.2s ease, opacity 0.15s ease; overflow: hidden; max-height: 300px; }
.faq-slide-enter-from, .faq-slide-leave-to { max-height: 0; opacity: 0; }
</style>
