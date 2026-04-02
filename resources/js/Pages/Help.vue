<script setup>
import { ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const openFaq = ref(null);
function toggle(i) { openFaq.value = openFaq.value === i ? null : i; }

const faqs = [
    { q: "O'yin qanday yaratiladi?",
      a: "Yaratish → O'yin turini tanlang → Mavzu, sinf va tilni kiriting → AI 30 soniyada tayyor o'yin yaratadi." },
    { q: "O'yinni o'quvchilar bilan qanday ulashaman?",
      a: "O'yin sahifasidan 'Sessiya yaratish' tugmasini bosing → 6 belgili kod hosil bo'ladi → O'quvchilar shu kodni /session/KOD manziliga kiritib o'ynaydi. Login talab qilinmaydi." },
    { q: "Real-time natijalar qanday ishlaydi?",
      a: "O'quvchi natija yuborar ekan, o'qituvchi sahifasida leaderboard avtomatik yangilanadi (WebSocket orqali, sahifani yangilash shart emas). Reverb serveri ishlab turishi kerak: php artisan reverb:start" },
    { q: "Kutubxona (Library) nima?",
      a: "Boshqa o'qituvchilar 'Ommaviy' qilgan o'yinlar. O'yin sahifasidagi 🌐 tugma orqali o'yinni ommaviy qilsangiz, barcha foydalanuvchilar Kutubxonada ko'radi va nusxa olishi mumkin." },
    { q: "Sertifikat qanday yaratiladi?",
      a: "Materiallar → Sertifikatlar → O'quvchi ismi, mavzu, ball kiriting → 'Sertifikat yaratish' → Brauzerdan PDF sifatida chop eting. Server-side dastur talab qilinmaydi." },
    { q: "Kuniga nechta o'yin yaratsam bo'ladi?",
      a: "Standart: kuniga 10 ta. Admin panel → AI Settings orqali bu limitni o'zgartirish mumkin." },
    { q: "Google bilan kirish ishlamasa?",
      a: "Brauzer cookie va JavaScriptni yoqing. APP_URL .env da http://localhost:8000 bo'lishi kerak. Muammo davom etsa, admin bilan bog'laning." },
    { q: "O'yinda xato bo'lsa nima qilaman?",
      a: "O'yin sahifasidagi 'Qayta yaratish' tugmasini bosing. AI boshqa variant yaratadi. Agar muammo takrorlansa, admin bilan bog'laning." },
    { q: "PDF material qanday chop etiladi?",
      a: "Materiallar → turni tanlang → Mavzu kiriting → AI varaq yaratadi → O'yin sahifasida 'Chop etish' tugmasi bilan PDF ga aylantirib yuklab olasiz." },
    { q: "Sinf boshqaruvi qanday ishlaydi?",
      a: "Sinflar sahifasida yangi sinf yarating → 8 belgili qo'shilish kodi hosil bo'ladi → O'quvchilar /join/KOD sahifasiga kirib ismlarini yozib sinflarga qo'shiladi." },
    { q: "Qiyinlik darajasi nima uchun kerak?",
      a: "O'yin yaratishda Oson/O'rtacha/Qiyin darajasini tanlasangiz, AI savollarni shu darajaga mos yaratadi. Kichik sinflar uchun Oson, olimpiada tayyorgarligi uchun Qiyin darajasini tanlang." },
    { q: "Proyektor rejimi nima?",
      a: "Sessiya faol bo'lganda '📽 Ekran' tugmasini bosing — to'liq ekran leaderboard ochiladi. Sinfda proyektor yoki katta ekranga ulangan holda o'quvchilar reyting jadvalini ko'rishlari mumkin. Esc tugmasi bilan yopiladi." },
];

const contacts = [
    { icon: '📧', label: 'Email',    value: 'admin@eduzona.uz' },
    { icon: '💬', label: 'Telegram', value: '@ilhomjumayev96' },
];
</script>

<template>
    <Head title="Yordam" />
    <AuthenticatedLayout>
        <template #header><h2 class="text-base font-semibold text-slate-800">Yordam</h2></template>

        <div class="max-w-2xl mx-auto space-y-6">

            <!-- Quick links -->
            <div class="grid grid-cols-3 sm:grid-cols-6 gap-2">
                <Link href="/create"
                    class="bg-white border border-slate-100 rounded-2xl p-3 text-center hover:shadow-md hover:border-indigo-200 transition group">
                    <div class="text-xl mb-1">🚀</div>
                    <div class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-700">Yaratish</div>
                </Link>
                <Link href="/dashboard"
                    class="bg-white border border-slate-100 rounded-2xl p-3 text-center hover:shadow-md hover:border-indigo-200 transition group">
                    <div class="text-xl mb-1">🎮</div>
                    <div class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-700">O'yinlarim</div>
                </Link>
                <Link href="/library"
                    class="bg-white border border-slate-100 rounded-2xl p-3 text-center hover:shadow-md hover:border-indigo-200 transition group">
                    <div class="text-xl mb-1">📚</div>
                    <div class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-700">Kutubxona</div>
                </Link>
                <Link href="/materials"
                    class="bg-white border border-slate-100 rounded-2xl p-3 text-center hover:shadow-md hover:border-indigo-200 transition group">
                    <div class="text-xl mb-1">📄</div>
                    <div class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-700">Materiallar</div>
                </Link>
                <Link href="/certificate"
                    class="bg-white border border-slate-100 rounded-2xl p-3 text-center hover:shadow-md hover:border-indigo-200 transition group">
                    <div class="text-xl mb-1">🏅</div>
                    <div class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-700">Sertifikat</div>
                </Link>
                <Link href="/classrooms"
                    class="bg-white border border-slate-100 rounded-2xl p-3 text-center hover:shadow-md hover:border-indigo-200 transition group">
                    <div class="text-xl mb-1">🏫</div>
                    <div class="text-[10px] font-bold text-slate-600 group-hover:text-indigo-700">Sinflar</div>
                </Link>
            </div>

            <!-- FAQ -->
            <div>
                <h3 class="font-bold text-slate-800 mb-3">Ko'p so'raladigan savollar</h3>
                <div class="space-y-2">
                    <div v-for="(faq, i) in faqs" :key="i"
                        :style="`animation-delay: ${i * 55}ms`"
                        class="bg-white border border-slate-100 rounded-2xl overflow-hidden hover:border-indigo-100 transition faq-appear">
                        <button @click="toggle(i)"
                            class="w-full flex items-center justify-between px-5 py-4 text-left gap-4">
                            <span class="font-semibold text-slate-800 text-sm">{{ faq.q }}</span>
                            <svg :class="['w-4 h-4 text-slate-400 transition-transform shrink-0', openFaq === i ? 'rotate-180' : '']"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div v-if="openFaq === i" class="px-5 pb-4">
                            <p class="text-sm text-slate-600 leading-relaxed">{{ faq.a }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Contact -->
            <div class="bg-indigo-50 border border-indigo-100 rounded-2xl p-5">
                <h3 class="font-bold text-indigo-800 text-sm mb-3">Bog'lanish</h3>
                <div class="space-y-2">
                    <div v-for="c in contacts" :key="c.label" class="flex items-center gap-3 text-sm">
                        <span class="text-lg">{{ c.icon }}</span>
                        <span class="text-slate-500 font-medium w-16">{{ c.label }}:</span>
                        <span class="text-indigo-700 font-semibold">{{ c.value }}</span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.faq-appear { animation: faqSlide 0.35s ease both; }
@keyframes faqSlide {
    from { opacity: 0; transform: translateY(8px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
