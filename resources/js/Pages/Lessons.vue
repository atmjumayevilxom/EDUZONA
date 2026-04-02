<script setup>
import { ref } from 'vue';
import { Head } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const activeCategory = ref('all');
const openLesson     = ref(null);

const categories = [
    { id: 'all',      label: 'Barchasi',       icon: '🗂' },
    { id: 'methods',  label: 'Metodikalar',    icon: '📋' },
    { id: 'tips',     label: 'Maslahatlar',    icon: '💡' },
    { id: 'games',    label: "O'yin texnikasi", icon: '🎮' },
    { id: 'ai',       label: "AI qo'llanma",   icon: '🤖' },
];

const lessons = [
    {
        id: 1, category: 'methods', icon: '🎯',
        title: "Faol o'qitish metodikasi",
        desc: "O'quvchilarni darsga jalb qilishning 5 ta samarali usuli.",
        duration: '8 daqiqa', level: "Boshlang'ich",
        color: 'from-indigo-500 to-blue-600',
        content: `
## Faol o'qitish nima?

Faol o'qitish — o'quvchi passiv tinglovchi emas, balki jarayonning faol ishtirokchisi bo'lgan pedagogik yondashuv.

### 5 ta asosiy usul:

**1. Think-Pair-Share (O'yla-Juftlash-Ulash)**
O'quvchilar avval mustaqil o'ylaydi, keyin juftlikda muhokama qiladi, so'ng sinfga taqdim etadi.

**2. Guruh ishlari**
3-5 kishilik guruhlarda muammoli vaziyatni hal qilish. Har bir o'quvchi mas'uliyat oladi.

**3. Rolli o'yinlar**
Mavzu yuzasidan stsenariy yozib, o'quvchilar turli rollarni o'ynaydi.

**4. Vikorinalik raqobat**
EDUZONA yordamida sinf uchun real-time viktorina o'tkazing — motivatsiya keskin oshadi.

**5. Refleksiya daftari**
Har dars oxirida: "Bugun nima o'rgandim?" savoli — o'rganishni mustahkamlaydi.

### Amaliy maslahat
EDUZONA da yaratilgan o'yinlarni dars oxirida 5-10 daqiqa uchun ishlating — bu bilimlarni qiziqarli mustahkamlaydi.
        `,
    },
    {
        id: 2, category: 'games', icon: '🏆',
        title: "Gamifikatsiya: o'yinni ta'limga qo'shish",
        desc: "Ball tizimi va raqobat orqali motivatsiya oshirish.",
        duration: '12 daqiqa', level: "O'rta",
        color: 'from-amber-500 to-orange-600',
        content: `
## Gamifikatsiya nima?

Gamifikatsiya — o'yin elementlarini (ball, reyting, yutuq) ta'lim jarayoniga kiritish.

### Nima uchun ishlaydi?

- **Darhol fikr-mulohaza**: O'quvchi javob berganda darhol to'g'ri/noto'g'ri biladi
- **Raqobat**: Reyting jadvali o'quvchilarni harakatlantiradi
- **Maqsad**: Yutuqlar va balllar maqsad yaratadi

### EDUZONA bilan qanday amalga oshiriladi?

1. **O'yin yarating** — mavzudan viktorina yoki boshqa format
2. **Sessiya oching** — 6 belgili kod beradi
3. **Sinfga kiring** — o'quvchilar telefon/kompyuterdan kiradi
4. **Live kuzating** — reyting real vaqtda yangilanadi

### Muhim maslahat

Har haftada kamida 1 marta o'yin o'tkazing. Tadqiqotlar ko'rsatishicha, gamifikatsiya o'quvchi faolligini 40% gacha oshiradi.
        `,
    },
    {
        id: 3, category: 'ai', icon: '🤖',
        title: 'AI bilan dars tayyorlash',
        desc: "To'g'ri prompt yozish va EDUZONA da o'yin yaratish.",
        duration: '15 daqiqa', level: "Boshlang'ich",
        color: 'from-purple-500 to-violet-600',
        content: `
## EDUZONA da AI o'yin yaratish

EDUZONA aqlli tizimi yordamida avtomatik o'yin savollari yaratadi.

### Yaxshi prompt misollari:

❌ **Yomon**: "Matematika"
✅ **Yaxshi**: "5-sinf uchun kasrlar bo'yicha 10 ta viktorina savoli"

❌ **Yomon**: "Tarix"
✅ **Yaxshi**: "O'zbekiston mustaqilligi tarixi, 9-sinf, 15 savol, o'zbek tilida"

### Optimal sozlamalar:

| Sozlama | Tavsiya |
|---------|---------|
| Savol soni | 10-15 (dars uchun optimal) |
| Til | O'quvchilar tiliga mos |
| Format | Mavzuga mos (test, so'z o'yini...) |

### Natijani tekshirish

AI yaratgan savollarni har doim ko'rib chiqing. Agar biror savol noto'g'ri bo'lsa, o'yinni qayta yaratasiz yoki nusxalab tahrirlaysiz.

### Pro maslahat

Bir dars uchun 2 xil format yarating: viktorina (bilimni tekshirish) + anagramma yoki so'z qidirish (qiziqarli mashq).
        `,
    },
    {
        id: 4, category: 'tips', icon: '⚡',
        title: "Sessiya boshqarish: 10 ta maslahat",
        desc: "Live sessiyani samarali o'tkazish usullari.",
        duration: '6 daqiqa', level: "Boshlang'ich",
        color: 'from-green-500 to-emerald-600',
        content: `
## Live sessiyani muvaffaqiyatli o'tkazish

### 10 ta amaliy maslahat:

**1. Oldindan tayyorlaning**
O'yinni dars oldidan testdan o'tkazing — texnik muammolarni bartaraf eting.

**2. Kodni ekranda ko'rsating**
6 belgili kodni proyektorda yoki doskada yozing. Barcha o'quvchi ko'rsin.

**3. Vaqt belgilang**
"5 daqiqa o'yin o'tkazamiz" deb aytmang — siz oxiri tugagach keyingi bosqichga o'tasiz.

**4. Birinchi o'rinni e'lon qiling**
O'yin tugagach, 1-3 o'rinni baland ovozda e'lon qiling — g'olibni maqtang.

**5. Telefon muammosi**
Agar o'quvchilar telefon ishlatmasa, bir juft o'quvchiga bitta qurilma bering.

**6. Internetni tekshiring**
Dars oldidan sinfdagi wifi ishlashini tekshiring.

**7. Qayta o'ynash imkoni**
Yaxshi natija bo'lmasa: "Yana bir marta?" — o'quvchilar xohlaydi!

**8. Statistikadan foydalaning**
Qaysi savolda ko'p xato bo'ldi? Keyingi darsda shu mavzuni qayta tushuntiring.

**9. Uyga vazifa**
"/student" sahifasini o'quvchilarga bering — ular uyda natijalarini ko'ra oladi.

**10. Muntazamlik**
Har hafta bir o'yin o'tkazing — o'quvchilar kutib qoladi!
        `,
    },
    {
        id: 5, category: 'methods', icon: '🔄',
        title: "Takrorlash darsida o'yinlardan foydalanish",
        desc: "Chorak oxirida bilimlarni qiziqarli mustahkamlash.",
        duration: '10 daqiqa', level: "O'rta",
        color: 'from-teal-500 to-cyan-600',
        content: `
## Takrorlash darslari uchun eng samarali formatlar

### Qaysi o'yin qaysi maqsad uchun:

| Format | Maqsad | Qachon |
|--------|--------|--------|
| Viktorina (MCQ) | Nazariy bilimni tekshirish | Har dars oxiri |
| Millioner | Chuqur bilim, qiziqarli | Chorak oxiri |
| So'z qidirish | Atamalarni mustahkamlash | Mavzu oxiri |
| Juftlashtirish | Tushunchalar bog'liqligini bilish | O'rta tekshiruv |
| Anagramma | Terminologiya | Yangi mavzu oldidan |

### Chorak yakunida to'liq takrorlash sessiyasi:

**1-5 daqiqa**: Anagramma — mavzu terminologiyasi
**5-15 daqiqa**: Viktorina — asosiy savollar
**15-20 daqiqa**: Natijalarni muhokama

### Guruh raqobati

Sinfni 2-3 guruhga bo'ling. Har guruh bitta qurilmadan sessiyaga kiradi. Guruhlar bir-biri bilan raqobatlashadi!
        `,
    },
    {
        id: 6, category: 'games', icon: '👥',
        title: "Jamoaviy o'yin strategiyalari",
        desc: "Guruhlar bo'yicha raqobat va hamkorlik.",
        duration: '9 daqiqa', level: "Ilg'or",
        color: 'from-rose-500 to-pink-600',
        content: `
## Jamoaviy o'yin: qanday tashkil qilish?

### Guruhga bo'lish usullari:

**Random bo'linish**: Barcha o'quvchi tartib raqam oladi — 1-2-3 yoki A-B-C guruhiga kiradi.

**Ko'nikma bo'yicha**: Turli darajadagi o'quvchilarni aralashtiring — kuchlilari zaiflariga yordam beradi.

**Doimiy jamoalar**: Sinfda doimiy 4-5 kishilik jamoalar — har hafta reyting yig'iladi.

### Jamoaviy o'yin qoidalari:

1. Har jamoadan bitta kapitan — u qurilmani boshqaradi
2. Barcha jamoa a'zolari javob beradi — kapitan ovoz ko'pchiligini tanglaydi
3. Jamoalar bir-birining ekranini ko'ra olmaydi

### Motivatsiya tizimi:

- 🥇 1-o'rin: +5 ball
- 🥈 2-o'rin: +3 ball
- 🥉 3-o'rin: +1 ball

Bu balllarni chorak bahosiga qo'shish mumkin!
        `,
    },
    {
        id: 7, category: 'ai', icon: '📊',
        title: "Statistikani o'qish va tahlil qilish",
        desc: "EDUZONA statistikasidan keyingi darsni rejalashtirish.",
        duration: '7 daqiqa', level: "O'rta",
        color: 'from-sky-500 to-blue-600',
        content: `
## O'yin statistikasini qanday o'qish kerak?

### Statistika sahifasiga kirish:
O'yin sahifasi → "Statistika" tugmasi → /games/{id}/stats

### Asosiy ko'rsatkichlar:

**Jami ishtirokchilar** — necha o'quvchi o'ynadi
**O'rtacha ball** — umumiy darajani ko'rsatadi
**Eng yaxshi natija** — sinfda eng yaxshisi kim
**Sessiyalar tarixi** — qaysi kuni qanday natija bo'ldi

### Pedagogik tahlil:

Statistikadan quyidagi savolga javob toping:
- "Qaysi savolda 50% dan kam o'quvchi to'g'ri javob berdi?"
- Bu mavzu qayta tushuntirishni talab qiladi!

### CSV eksport

"CSV yuklab olish" tugmasi orqali barcha natijalarni Excel da ko'rish mumkin. Bu:
- Ota-onalarga hisobot
- O'qituvchi kundaligi uchun ma'lumot
- O'quvchi progressini kuzatish

### Pro maslahat

Har chorakda 3-4 marta bir xil mavzuda o'yin o'tkazing — natijalarni solishtirish o'quvchi o'sishini ko'rsatadi.
        `,
    },
    {
        id: 8, category: 'tips', icon: '📱',
        title: "Mobil qurilmadan foydalanish",
        desc: "O'quvchilar telefon orqali sessiyaga kirganda.",
        duration: '5 daqiqa', level: "Boshlang'ich",
        color: 'from-fuchsia-500 to-purple-600',
        content: `
## Mobil qurilmada EDUZONA sessiyasi

### O'quvchi telefondan qanday kiradi?

1. Brauzer ochadi (Chrome, Safari)
2. Manzilni yozadi: **eduzona.uz/session/XXXXXX** (kodni sizdan oladi)
3. Ismini kiritadi
4. O'yinni boshlaydi

### Ko'p uchraydigan muammolar:

**Muammo**: Sahifa ochilmayapti
**Yechim**: Internetni tekshiring. Wi-Fi ga ulaning.

**Muammo**: Kod ishlamayapti
**Yechim**: Katta-kichik harf farqi yo'q. Boshqatdan kiriting.

**Muammo**: O'yin sekin ishlayapti
**Yechim**: Brauzer varaqlarini yoping. Sahifani yangilang.

**Muammo**: Telefon yo'q
**Yechim**: 2 o'quvchiga 1 telefon bering. Yoki proyektor rejimida o'z kompyuteringizdan o'ynating.

### Sinf uchun qulaylik

Agar sinfda barcha o'quvchida telefon bo'lmasa — guruhga bo'ling. Har guruhdan 1 ta qurilma yetarli!
        `,
    },
];

const levelColor = {
    "Boshlang'ich": 'bg-green-100 text-green-700',
    "O'rta":        'bg-amber-100 text-amber-700',
    "Ilg'or":       'bg-red-100 text-red-700',
};

const filtered = () =>
    activeCategory.value === 'all'
        ? lessons
        : lessons.filter(l => l.category === activeCategory.value);

// Simple markdown-like renderer
function renderContent(text) {
    return text
        .replace(/^## (.+)$/gm, '<h2 class="text-lg font-bold text-slate-800 mt-5 mb-2">$1</h2>')
        .replace(/^### (.+)$/gm, '<h3 class="text-base font-semibold text-slate-700 mt-4 mb-1.5">$1</h3>')
        .replace(/\*\*(.+?)\*\*/g, '<strong class="text-slate-800">$1</strong>')
        .replace(/^- (.+)$/gm, '<li class="text-sm text-slate-600 leading-relaxed ml-4 list-disc">$1</li>')
        .replace(/^\| (.+) \|$/gm, (m, p1) => {
            const cells = p1.split(' | ');
            return '<tr>' + cells.map(c => `<td class="border border-slate-200 px-2 py-1 text-xs text-slate-600">${c}</td>`).join('') + '</tr>';
        })
        .replace(/^(\d+)\. (.+)$/gm, '<div class="flex gap-2 items-start text-sm text-slate-600 mb-1"><span class="font-bold text-indigo-600 shrink-0">$1.</span><span>$2</span></div>')
        .replace(/❌ \*\*(.+?)\*\*: (.+)/g, '<div class="flex gap-2 items-start text-sm mb-1"><span class="text-red-500">❌</span><span><strong>$1</strong>: $2</span></div>')
        .replace(/✅ \*\*(.+?)\*\*: (.+)/g, '<div class="flex gap-2 items-start text-sm mb-1"><span class="text-green-500">✅</span><span><strong>$1</strong>: $2</span></div>')
        .replace(/\n\n/g, '<br/>')
        .replace(/^(?!<[hldtb]).+$/gm, m => m ? `<p class="text-sm text-slate-600 leading-relaxed">${m}</p>` : '');
}
</script>

<template>
    <Head title="Darslar" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold text-slate-800">Darslar va metodikalar</h2>
        </template>

        <div class="max-w-4xl mx-auto space-y-6">

            <!-- Hero banner -->
            <div class="bg-gradient-to-br from-indigo-600 to-purple-700 rounded-2xl p-6 text-white flex items-center gap-6">
                <div class="text-5xl shrink-0">📚</div>
                <div>
                    <h3 class="text-xl font-bold mb-1">O'qituvchilar uchun qo'llanma</h3>
                    <p class="text-indigo-200 text-sm leading-relaxed">
                        EDUZONA dan samarali foydalanish bo'yicha darslar, metodikalar va amaliy maslahatlar.
                    </p>
                </div>
            </div>

            <!-- Category tabs -->
            <div class="flex gap-2 flex-wrap">
                <button v-for="cat in categories" :key="cat.id"
                    @click="activeCategory = cat.id"
                    :class="[
                        'flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-medium border-2 transition',
                        activeCategory === cat.id
                            ? 'bg-indigo-600 text-white border-indigo-600'
                            : 'border-slate-200 text-slate-600 hover:border-indigo-300 bg-white'
                    ]">
                    {{ cat.icon }} {{ cat.label }}
                </button>
            </div>

            <!-- Lessons grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div v-for="(lesson, li) in filtered()" :key="lesson.id"
                    :style="`animation-delay: ${li * 55}ms`"
                    class="bg-white rounded-2xl border border-slate-200 overflow-hidden hover:shadow-md hover:border-indigo-200 transition-all group lesson-appear cursor-pointer"
                    @click="openLesson = lesson">
                    <!-- Header -->
                    <div :class="['h-24 bg-gradient-to-br flex items-center justify-center text-4xl relative overflow-hidden', lesson.color]">
                        <div class="absolute -top-4 -right-4 w-16 h-16 bg-white/10 rounded-full"></div>
                        <div class="absolute -bottom-6 -left-6 w-20 h-20 bg-white/10 rounded-full"></div>
                        <span class="relative z-10">{{ lesson.icon }}</span>
                    </div>
                    <!-- Body -->
                    <div class="p-4">
                        <div class="flex items-center gap-2 mb-2">
                            <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', levelColor[lesson.level]]">
                                {{ lesson.level }}
                            </span>
                            <span class="text-xs text-slate-400 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                {{ lesson.duration }}
                            </span>
                        </div>
                        <h4 class="font-semibold text-slate-900 mb-1.5 group-hover:text-indigo-700 transition">{{ lesson.title }}</h4>
                        <p class="text-xs text-slate-500 leading-relaxed line-clamp-3">{{ lesson.desc }}</p>
                    </div>
                    <div class="px-4 pb-4">
                        <div class="w-full py-2 rounded-xl bg-indigo-50 group-hover:bg-indigo-100 text-indigo-700 text-sm font-semibold transition text-center">
                            O'qish →
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Lesson Modal -->
        <Transition
            enter-active-class="transition duration-200"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div v-if="openLesson"
                class="fixed inset-0 z-50 flex items-start justify-center bg-black/50 backdrop-blur-sm p-4 overflow-y-auto"
                @click.self="openLesson = null">
                <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8 overflow-hidden modal-in">
                    <!-- Modal header -->
                    <div :class="['h-28 bg-gradient-to-br flex items-center justify-center text-5xl relative overflow-hidden', openLesson.color]">
                        <div class="absolute -top-6 -right-6 w-24 h-24 bg-white/10 rounded-full"></div>
                        <span class="relative z-10">{{ openLesson.icon }}</span>
                        <button @click="openLesson = null"
                            class="absolute top-4 right-4 w-8 h-8 bg-white/20 hover:bg-white/30 rounded-full flex items-center justify-center text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    <!-- Modal content -->
                    <div class="p-6">
                        <div class="flex items-center gap-2 mb-3">
                            <span :class="['text-xs font-medium px-2 py-0.5 rounded-full', levelColor[openLesson.level]]">
                                {{ openLesson.level }}
                            </span>
                            <span class="text-xs text-slate-400">⏱ {{ openLesson.duration }}</span>
                        </div>
                        <h2 class="text-xl font-bold text-slate-800 mb-4">{{ openLesson.title }}</h2>
                        <div class="prose prose-sm max-w-none space-y-1 lesson-content"
                            v-html="renderContent(openLesson.content)">
                        </div>
                    </div>

                    <div class="px-6 pb-6">
                        <button @click="openLesson = null"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-bold py-3 rounded-2xl transition">
                            Yopish ✓
                        </button>
                    </div>
                </div>
            </div>
        </Transition>

    </AuthenticatedLayout>
</template>

<style scoped>
.lesson-appear { animation: lessonSlide 0.38s ease both; }
@keyframes lessonSlide {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.modal-in { animation: modalPop 0.25s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes modalPop {
    from { opacity: 0; transform: scale(0.95) translateY(10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
