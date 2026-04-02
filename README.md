<div align="center">

# 🎮 EDUZONA — AI Ta'lim Platformasi [![Telegram](https://img.icons8.com/color/48/telegram-app.png)](https://t.me/ilhomjumayev) Jumayev Ilhom

**O'qituvchilar uchun AI yordamida interaktiv o'yinlar va video darslar yaratish platformasi**

![Laravel](https://img.shields.io/badge/Laravel-12.x-FF2D20?style=for-the-badge&logo=laravel&logoColor=white)
![Vue.js](https://img.shields.io/badge/Vue.js-3.x-4FC08D?style=for-the-badge&logo=vuedotjs&logoColor=white)
![PHP](https://img.shields.io/badge/PHP-8.2+-777BB4?style=for-the-badge&logo=php&logoColor=white)
![TailwindCSS](https://img.shields.io/badge/TailwindCSS-3.x-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-2.x-9553E9?style=for-the-badge)

**Boshlangan sana: 30 mart 2026**

</div>

---

## 📋 Loyiha haqida

**EDUZONA** — o'zbek o'qituvchilari va pedagoglari uchun maxsus ishlab chiqilgan AI-quvvatlangan ta'lim platformasi. Platforma orqali:

- 🧠 **AI yordamida** interaktiv ta'lim o'yinlari yaratish (викторина, flashcard, anagram, word search va boshqalar)
- 🎬 **AI Video** — masala bering, ChatGPT yechsin, Grok video dars yaratsın
- 🏫 **Sinfxona boshqaruvi** — o'quvchilarni guruhlab, sessiyalarni boshqarish
- 📊 **Statistika** — o'yin natijalari va o'quvchilar ko'rsatkichlarini kuzatish
- 🎓 **Sertifikat** — o'quvchilar uchun avtomatik sertifikat generatsiyasi

---

## 🏗️ Texnologiyalar

### Backend (Server tomonida)

| Texnologiya | Versiya | Maqsad |
|------------|---------|--------|
| **PHP** | 8.2+ | Asosiy dasturlash tili |
| **Laravel** | 12.x | Backend freymvork |
| **Laravel Sanctum** | 4.x | API autentifikatsiya |
| **Laravel Socialite** | 5.x | Google OAuth login |
| **Laravel Reverb** | 1.x | Real-time WebSocket server |
| **Inertia.js (PHP)** | 2.x | SPA bridge (API yo'q, to'g'ridan-to'g'ri) |
| **DomPDF** | * | PDF sertifikat generatsiyasi |
| **OpenAI PHP** | 0.18+ | ChatGPT API integratsiyasi |
| **Ziggy** | 2.x | PHP routelarni JS ga o'tkazish |

### Frontend (Mijoz tomonida)

| Texnologiya | Versiya | Maqsad |
|------------|---------|--------|
| **Vue.js** | 3.4+ | Frontend freymvork |
| **Inertia.js (Vue)** | 2.x | Server-side rendering bilan SPA |
| **TailwindCSS** | 3.x | CSS freymvork |
| **Pinia** | 3.x | Holat boshqarish (State management) |
| **Axios** | 1.x | HTTP so'rovlar |
| **Laravel Echo** | 2.x | WebSocket mijozi |
| **Pusher JS** | 8.x | Real-time event handling |
| **QRCode.js** | 1.x | QR kod generatsiyasi |
| **Vite** | 7.x | Asset bundler |
| **Vite PWA** | 1.x | Progressive Web App qo'llab-quvvatlash |

### AI / Tashqi API integratsiyalar

| Servis | Maqsad |
|--------|--------|
| **OpenAI (ChatGPT)** | Masalalar yechimi, o'yin kontenti generatsiyasi, prompt yaratish |
| **xAI Grok (`grok-imagine-video`)** | AI video dars generatsiyasi (8–30 soniya) |
| **Google OAuth** | Tizimga kirish (Google akkount orqali) |

### Ma'lumotlar bazasi

| Texnologiya | Maqsad |
|-------------|--------|
| **MySQL / MariaDB** | Asosiy ma'lumotlar bazasi (XAMPP) |
| **Laravel Migrations** | DB versiya boshqaruvi |
| **Eloquent ORM** | Ma'lumotlar bilan ishlash |

---

## ✨ Asosiy imkoniyatlar

### 🎮 AI O'yin Generatori

O'qituvchi mavzu va sinfni kiritadi — ChatGPT avtomatik interaktiv o'yin yaratadi. Qo'llab-quvvatlanadigan **40+ o'yin turlari**:

- Viktorina (Quiz) · Rost/Yolg'on · Flashcard · Anagram
- Hangman (Gallows) · Word Search · Memory Cards · Matching Pairs
- Speed Sort · Group Sort · Crossword · Spelling
- Race · Whack-a-Mole · Flying Answers · Millionaire
- Reorder · Complete Sentence · Game Show · Math Quiz
- Timeline · Open Box · Watching & Memorize · DTM Test
- Lesson Plan · Diagram · Pair or Not · Rope Pull
- Sleeping Bear · PISA · Airplane · Zakovat · Spin Wheel

### 🎬 AI Video Dars (Yangi!)

```
Foydalanuvchi → Masala matni → ChatGPT (qadam-baqadam yechim)
→ VideoPromptBuilder → Grok API → Video generatsiya → Foydalanuvchiga
```

- O'qituvchi masala matnini kiritadi
- ChatGPT masalani qadam-baqadam yechib, yechimni tuzadi
- Avtomatik video prompti yaratiladi (qora doska uslubi)
- Grok `grok-imagine-video` modeli 15 soniyalik ta'limiy video yaratadi
- Video tayyor bo'lgach foydalanuvchi ko'rishi va yuklab olishi mumkin
- Har bir foydalanuvchi profilida video tarixi saqlanadi

### 🏫 Sinfxona Boshqaruvi

- Sinflar yaratish va o'quvchilarni qo'shish
- QR kod orqali o'quvchilar sinfga qo'shilishi
- Sessiyalarni boshqarish (jonli o'yin rejimi)
- O'quvchilar natijalarini ko'rish

### 📊 Statistika va Hisobot

- O'yin natijalarini real-time kuzatish
- O'quvchi ko'rsatkichlari tarixi
- Excel/PDF export
- Daraja diagrammalari

### 🎓 Sertifikat Tizimi

- Avtomatik sertifikat generatsiyasi (PDF)
- O'quvchi nomi va natijasi ko'rsatiladi
- Bosib chiqarish imkoniyati

### 👨‍💼 Admin Panel

- Foydalanuvchilarni boshqarish (bloklahs, aktivlashtirish)
- AI sozlamalar (model, token limiti, kunlik so'rov chegarasi)
- **Video sozlamalar** — video uzunligi, uslub, prompt prefix/suffix
- Shablonlar va kategoriyalar boshqaruvi
- Prompt muharriri (AI promptlarni bevosita tahrirlash)
- Audit log — barcha muhim amallar tarixi
- O'yinlar monitoringi

---

## 🗂️ Loyiha tuzilmasi

```
eduzona/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/                   # API kontrollerlar
│   │   │   │   ├── Admin/             # Admin API
│   │   │   │   ├── AiVideoController  # AI Video
│   │   │   │   ├── GameController     # O'yinlar
│   │   │   │   ├── SessionController  # Sessiyalar
│   │   │   │   └── ClassroomController# Sinfxonalar
│   │   │   └── Auth/                  # Autentifikatsiya
│   │   └── Middleware/
│   ├── Models/                        # Eloquent modellar
│   ├── Services/
│   │   ├── AiSolverService            # ChatGPT masala yechish
│   │   ├── VideoGenerationService     # Grok video generatsiya
│   │   └── VideoPromptBuilderService  # Video prompt qurish
│   └── Jobs/                          # Queue joblar
├── database/migrations/               # DB migratsiyalar
├── resources/
│   └── js/
│       ├── Pages/                     # Vue sahifalari
│       │   ├── Admin/                 # Admin panel
│       │   ├── AiVideo/               # AI Video sahifalari
│       │   ├── Games/                 # O'yin sahifalari
│       │   └── Classrooms/            # Sinfxona sahifalari
│       ├── Components/                # Vue komponentlar
│       │   ├── AiVideo/               # Video komponentlar
│       │   └── GameRenderers/         # 20+ o'yin rendererlari
│       ├── Layouts/                   # Layout komponentlar
│       └── stores/                    # Pinia store
├── routes/
│   ├── web.php                        # Web routelar
│   └── api.php                        # API routelar
└── public/build/                      # Compiled assets
```

---

## 🚀 O'rnatish va Ishga Tushirish

### Talablar

- **PHP** 8.2 yoki undan yuqori
- **Composer** 2.x
- **Node.js** 18+ va **npm**
- **MySQL** yoki **MariaDB** (XAMPP tavsiya etiladi)
- **Git**

### 1-qadam: Loyihani klonlash

```bash
git clone https://github.com/sizning-username/eduzona.git
cd eduzona
```

### 2-qadam: PHP bog'liqliklarni o'rnatish

```bash
composer install
```

### 3-qadam: Muhit faylini sozlash

```bash
cp .env.example .env
php artisan key:generate
```

`.env` faylini oching va quyidagi qiymatlarni to'ldiring:

```env
APP_NAME=EDUZONA
APP_URL=http://localhost:8000

# Ma'lumotlar bazasi
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=eduzona
DB_USERNAME=root
DB_PASSWORD=

# Google OAuth (https://console.cloud.google.com)
GOOGLE_CLIENT_ID=your-google-client-id
GOOGLE_CLIENT_SECRET=your-google-client-secret
GOOGLE_REDIRECT_URI=http://localhost:8000/auth/google/callback

# OpenAI (ChatGPT) — https://platform.openai.com/api-keys
OPENAI_API_KEY=sk-...
OPENAI_DEFAULT_MODEL=gpt-4o-mini

# xAI Grok (Video generatsiya) — https://console.x.ai
XAI_API_KEY=xai-...
XAI_API_URL=https://api.x.ai/v1

# Laravel Reverb (Real-time)
REVERB_APP_ID=your-app-id
REVERB_APP_KEY=your-app-key
REVERB_APP_SECRET=your-app-secret
```

### 4-qadam: Ma'lumotlar bazasini yaratish va migratsiya

```bash
# MySQL da "eduzona" nomli DB yarating, so'ng:
php artisan migrate

# Boshlang'ich ma'lumotlarni yuklash (ixtiyoriy)
php artisan db:seed
```

### 5-qadam: Frontend bog'liqliklarni o'rnatish va build

```bash
npm install
npm run build
```

### 6-qadam: Serverni ishga tushirish

```bash
# Barcha servislarni bir vaqtda ishga tushirish
composer dev
```

Bu quyidagilarni bir vaqtda ishga tushiradi:
- `php artisan serve` — Laravel server (port 8000)
- `php artisan queue:listen` — Navbat ishchi
- `php artisan pail` — Log monitoring
- `npm run dev` — Vite dev server

Yoki alohida-alohida:

```bash
# 1-terminal: Laravel server
php artisan serve

# 2-terminal: Queue worker (AI so'rovlar uchun)
php artisan queue:listen --tries=1 --timeout=0

# 3-terminal: Vite (frontend)
npm run dev
```

Brauzerda oching: **http://localhost:8000**

### XAMPP bilan ishlatish (Windows)

1. XAMPP Control Panel da Apache va MySQL ni ishga tushiring
2. `c:\xampp\htdocs\eduzona` papkasiga loyihani joylang
3. phpMyAdmin da `eduzona` nomli DB yarating
4. `.env` da `DB_HOST=127.0.0.1`, `DB_USERNAME=root`, `DB_PASSWORD=` ni sozlang
5. `php artisan migrate` ni ishga tushiring
6. `npm run build` ni bajarib olish
7. Brauzerda: **http://localhost/eduzona/public**

---

## 👤 Admin Panelga Kirish

Admin foydalanuvchi yaratish:

```bash
php artisan tinker
```

```php
\App\Models\User::create([
    'name'     => 'Admin',
    'email'    => 'admin@eduzona.uz',
    'password' => bcrypt('parol123'),
    'role'     => 'admin',
    'status'   => 'active',
]);
```

Admin panel: **http://localhost:8000/admin**

Yoki alohida admin login sahifasi orqali: **http://localhost:8000/admin/login**

---

## 🔌 API Integratsiyalar

### OpenAI (ChatGPT)

O'yin kontenti generatsiyasi va masalalar yechimi uchun ishlatiladi.

```
API: https://api.openai.com/v1/chat/completions
Model: gpt-4o-mini (sukut bo'yicha, admin paneldan o'zgartiriladi)
```

API kaliti olish: [https://platform.openai.com/api-keys](https://platform.openai.com/api-keys)

### xAI Grok (Video)

Ta'limiy video kliplar generatsiyasi uchun ishlatiladi.

```
API: https://api.x.ai/v1/videos/generations
Model: grok-imagine-video
Davomiylik: 8–30 soniya (admin paneldan sozlanadi)
```

API kaliti olish: [https://console.x.ai](https://console.x.ai)

### Google OAuth

Foydalanuvchilar Google akkounti orqali tizimga kirishi uchun.

OAuth app yaratish: [https://console.cloud.google.com](https://console.cloud.google.com)

---

## 📱 PWA (Progressive Web App)

EDUZONA mobil qurilmalarga o'rnatilishi mumkin:

- Android: Brauzerda "Uy ekraniga qo'shish" tugmasi
- iOS Safari: "Share → Add to Home Screen"

PWA xususiyatlari:
- Ilovani offline ishlashi (asosiy sahifalar)
- Push notification qo'llab-quvvatlash
- Tez yuklash (service worker kesh)

---

## 🗄️ Ma'lumotlar Bazasi Sxemasi

| Jadval | Maqsad |
|--------|--------|
| `users` | Foydalanuvchilar (o'qituvchilar va adminlar) |
| `games` | AI tomonidan yaratilgan o'yinlar |
| `game_templates` | O'yin shablonlari (20+ tur) |
| `game_sessions` | Jonli o'yin sessiyalari |
| `session_results` | O'quvchilar natijalari |
| `categories` | Fan kategoriyalari |
| `classrooms` | Sinfxonalar |
| `classroom_students` | Sinf-o'quvchi bog'liqligi |
| `prompt_versions` | AI prompt versiyalari |
| `ai_settings` | AI sozlamalar (kalit-qiymat) |
| `ai_video_requests` | AI video so'rovlar va natijalar |
| `audit_logs` | Tizim audit jurnali |
| `jobs` / `failed_jobs` | Queue job jadvallari |

---

## 🛡️ Xavfsizlik

- **Laravel Sanctum** — API tokenlar orqali autentifikatsiya
- **CSRF** himoyasi barcha form so'rovlarida
- **Rate Limiting** — API so'rovlarga limit (o'yin: 10/min, video: 10/min, sessiya: 5/min)
- **Role-based access** — `user` va `admin` rollari
- **Status tekshiruvi** — bloklangan foydalanuvchilar kirisha olmaydi
- **Input validation** — barcha API so'rovlarda server tomonida tekshirish

---

## 🔧 Foydali Buyruqlar

```bash
# Keshni tozalash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Queue holatini tekshirish
php artisan queue:status

# Xato bo'lgan joblarni qayta ishga tushirish
php artisan queue:retry all

# Ma'lumotlar bazasini qayta yaratish (dev uchun)
php artisan migrate:fresh --seed

# Vite production build
npm run build

# Loglarni kuzatish
php artisan pail
```

---

## 👨‍💻 Ishlab Chiqaruvchi

| | |
|--|--|
| **Ism** | Jumayev Ilhom | @ilhomjumayev96
| **Loyiha** | EDUZONA — O'zbekiston o'qituvchilari uchun AI ta'lim platformasi |
| **Boshlangan sana** | 30 mart 2026 |
| **Til** | O'zbek tili asosiy, ko'p tilli qo'llab-quvvatlash |

---

## 📄 Litsenziya

Ushbu loyiha shaxsiy foydalanish uchun ishlab chiqilgan.  
Barcha huquqlar himoyalangan © 2026 EDUZONA

---

<div align="center">

**⭐ Ushbu loyiha muallifga tegishli.  
Muallif ruxsatisiz foydalanish, nusxalash yoki tarqatish taqiqlanadi.**

Made with ❤️ for Uzbek teachers <a herf="https://t.me/ilhomjumayev">Jumayev Ilhom</a>


</div>
