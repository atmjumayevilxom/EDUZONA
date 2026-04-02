<script setup>
import { ref, computed, onMounted } from 'vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const flashSuccess = computed(() => page.props.flash?.success ?? null);
const flashError   = computed(() => page.props.flash?.error   ?? null);

const isGoogleUser = computed(() => !!user.value?.google_id && !user.value?.password_set);

// ── Profile form ──
const profileForm = useForm({ name: user.value?.name ?? '' });

function updateProfile() {
    profileForm.patch('/profile', {
        preserveScroll: true,
    });
}

// ── Password form ──
const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});
const showCurrent = ref(false);
const showNew     = ref(false);
const showConfirm = ref(false);

function updatePassword() {
    passwordForm.put('/profile/password', {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
    });
}

// Avatar initials
const initials = computed(() => {
    return (user.value?.name ?? '?').split(' ').map(w => w[0]).slice(0, 2).join('').toUpperCase();
});

// Stats
const stats = ref(null);
onMounted(async () => {
    try {
        const res = await axios.get('/api/profile/stats');
        stats.value = res.data.data;
    } catch { /* ignore */ }
});

// Role / status labels
const roleLabel = computed(() => user.value?.role === 'admin' ? 'Administrator' : 'Pedagog');
const roleColor = computed(() => user.value?.role === 'admin'
    ? 'bg-purple-100 text-purple-700'
    : 'bg-indigo-100 text-indigo-700');
</script>

<template>
    <Head title="Profil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-semibold text-slate-800">Mening profilim</h2>
        </template>

        <div class="max-w-2xl mx-auto space-y-5">

            <!-- Flash messages -->
            <transition name="slide-down">
                <div v-if="flashSuccess"
                    class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 px-5 py-3 rounded-2xl text-sm font-medium">
                    <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ flashSuccess }}
                </div>
            </transition>
            <transition name="slide-down">
                <div v-if="flashError"
                    class="flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 px-5 py-3 rounded-2xl text-sm font-medium">
                    <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    {{ flashError }}
                </div>
            </transition>

            <!-- ── Account card ── -->
            <div class="bg-white rounded-2xl border border-slate-100 overflow-hidden shadow-sm card-appear" style="animation-delay: 0ms">
                <!-- Top gradient -->
                <div class="h-20 bg-gradient-to-r from-indigo-500 to-purple-600"></div>

                <div class="px-6 pb-6">
                    <!-- Avatar -->
                    <div class="-mt-10 mb-4 flex items-end justify-between">
                        <div class="relative">
                            <img v-if="user?.avatar" :src="user.avatar"
                                class="w-20 h-20 rounded-2xl ring-4 ring-white shadow-lg object-cover" />
                            <div v-else
                                class="w-20 h-20 rounded-2xl ring-4 ring-white shadow-lg bg-gradient-to-br from-indigo-400 to-purple-600 flex items-center justify-center text-white text-2xl font-bold">
                                {{ initials }}
                            </div>
                            <!-- Google badge -->
                            <div v-if="user?.google_id"
                                class="absolute -bottom-1.5 -right-1.5 w-7 h-7 bg-white rounded-full shadow flex items-center justify-center">
                                <svg class="w-4 h-4" viewBox="0 0 24 24">
                                    <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4"/>
                                    <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853"/>
                                    <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05"/>
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                            </div>
                        </div>

                        <span :class="['text-xs font-bold px-3 py-1.5 rounded-full', roleColor]">
                            {{ roleLabel }}
                        </span>
                    </div>

                    <!-- User info -->
                    <h2 class="text-xl font-bold text-slate-900">{{ user?.name }}</h2>
                    <p class="text-sm text-slate-500 mt-0.5">{{ user?.email }}</p>

                    <div class="mt-4 flex flex-wrap gap-3">
                        <div class="flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                            Ro'yxatdan o'tilgan: {{ user?.created_at ? new Date(user.created_at).toLocaleDateString('uz-Latn-UZ') : '—' }}
                        </div>
                        <div v-if="user?.last_login_at" class="flex items-center gap-1.5 text-xs text-slate-500 bg-slate-50 px-3 py-1.5 rounded-xl">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Oxirgi kirish: {{ new Date(user.last_login_at).toLocaleDateString('uz-Latn-UZ') }}
                        </div>
                        <div v-if="user?.google_id" class="flex items-center gap-1.5 text-xs text-blue-600 bg-blue-50 px-3 py-1.5 rounded-xl font-medium">
                            <svg class="w-3.5 h-3.5" viewBox="0 0 24 24" fill="currentColor">
                                <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                            Google bilan ulangan
                        </div>
                    </div>
                </div>
            </div>

            <!-- ── Edit name card ── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm card-appear" style="animation-delay: 80ms">
                <div class="px-6 py-5 border-b border-slate-50">
                    <h3 class="font-bold text-slate-800 text-sm">Profil ma'lumotlarini tahrirlash</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Ismingizni o'zgartiring</p>
                </div>
                <form @submit.prevent="updateProfile" class="p-6 space-y-4">
                    <!-- Name -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Ism va familiya</label>
                        <input
                            v-model="profileForm.name"
                            type="text"
                            placeholder="Ismingizni kiriting"
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition"
                        />
                        <p v-if="profileForm.errors.name" class="mt-1.5 text-xs text-red-500">{{ profileForm.errors.name }}</p>
                    </div>

                    <!-- Email (read-only) -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Email manzil
                            <span v-if="user?.google_id" class="ml-1 text-slate-400 font-normal">(Google orqali bog'langan, o'zgartirib bo'lmaydi)</span>
                        </label>
                        <input
                            :value="user?.email"
                            type="email"
                            disabled
                            class="w-full px-4 py-2.5 rounded-xl border border-slate-100 bg-slate-50 text-sm text-slate-400 cursor-not-allowed"
                        />
                    </div>

                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            :disabled="profileForm.processing || !profileForm.name.trim()"
                            :class="[
                                'px-6 py-2.5 rounded-xl text-sm font-semibold transition',
                                profileForm.processing || !profileForm.name.trim()
                                    ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                    : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                            ]">
                            {{ profileForm.processing ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Password change card ── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm card-appear" style="animation-delay: 160ms">
                <div class="px-6 py-5 border-b border-slate-50">
                    <h3 class="font-bold text-slate-800 text-sm">Parolni o'zgartirish</h3>
                    <p class="text-xs text-slate-500 mt-0.5">
                        <template v-if="isGoogleUser">
                            Google orqali kirgan foydalanuvchilar uchun parol mavjud emas
                        </template>
                        <template v-else>
                            Xavfsizlik uchun kuchli parol o'rnating
                        </template>
                    </p>
                </div>

                <!-- Google user notice -->
                <div v-if="isGoogleUser" class="p-6">
                    <div class="flex items-start gap-3 bg-blue-50 border border-blue-100 rounded-2xl p-4">
                        <svg class="w-5 h-5 text-blue-500 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <div>
                            <p class="text-sm font-semibold text-blue-800">Google hisobi bilan ulangan</p>
                            <p class="text-xs text-blue-600 mt-1">
                                Siz Google OAuth orqali kirmoqdasiz. Parolni o'zgartirish uchun administrator bilan bog'laning yoki Google hisobingizdan chiqib, parol bilan ro'yxatdan o'ting.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Password form (admin / non-Google users) -->
                <form v-else @submit.prevent="updatePassword" class="p-6 space-y-4">
                    <!-- Current password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Joriy parol</label>
                        <div class="relative">
                            <input
                                v-model="passwordForm.current_password"
                                :type="showCurrent ? 'text' : 'password'"
                                placeholder="Joriy parolni kiriting"
                                class="w-full px-4 py-2.5 pr-10 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition"
                            />
                            <button type="button" @click="showCurrent = !showCurrent"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="showCurrent" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.current_password" class="mt-1.5 text-xs text-red-500">{{ passwordForm.errors.current_password }}</p>
                    </div>

                    <!-- New password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Yangi parol</label>
                        <div class="relative">
                            <input
                                v-model="passwordForm.password"
                                :type="showNew ? 'text' : 'password'"
                                placeholder="Kamida 8 ta belgi, raqam va harf"
                                class="w-full px-4 py-2.5 pr-10 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition"
                            />
                            <button type="button" @click="showNew = !showNew"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="showNew" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.password" class="mt-1.5 text-xs text-red-500">{{ passwordForm.errors.password }}</p>
                    </div>

                    <!-- Confirm password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Yangi parolni takrorlang</label>
                        <div class="relative">
                            <input
                                v-model="passwordForm.password_confirmation"
                                :type="showConfirm ? 'text' : 'password'"
                                placeholder="Parolni qayta kiriting"
                                class="w-full px-4 py-2.5 pr-10 rounded-xl border border-slate-200 text-sm focus:outline-none focus:border-indigo-400 focus:ring-2 focus:ring-indigo-100 transition"
                            />
                            <button type="button" @click="showConfirm = !showConfirm"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path v-if="showConfirm" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                    <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        <p v-if="passwordForm.errors.password_confirmation" class="mt-1.5 text-xs text-red-500">{{ passwordForm.errors.password_confirmation }}</p>
                    </div>

                    <!-- Password strength hints -->
                    <div class="bg-slate-50 rounded-xl p-3 space-y-1.5">
                        <p class="text-[10px] font-bold text-slate-500 uppercase tracking-wider">Parol talablari:</p>
                        <div :class="['flex items-center gap-2 text-xs', passwordForm.password.length >= 8 ? 'text-green-600' : 'text-slate-400']">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="passwordForm.password.length >= 8 ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4'"/>
                            </svg>
                            Kamida 8 ta belgi
                        </div>
                        <div :class="['flex items-center gap-2 text-xs', /[A-Z]/.test(passwordForm.password) ? 'text-green-600' : 'text-slate-400']">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="/[A-Z]/.test(passwordForm.password) ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4'"/>
                            </svg>
                            Katta harf (A-Z)
                        </div>
                        <div :class="['flex items-center gap-2 text-xs', /[a-z]/.test(passwordForm.password) ? 'text-green-600' : 'text-slate-400']">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="/[a-z]/.test(passwordForm.password) ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4'"/>
                            </svg>
                            Kichik harf (a-z)
                        </div>
                        <div :class="['flex items-center gap-2 text-xs', /\d/.test(passwordForm.password) ? 'text-green-600' : 'text-slate-400']">
                            <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" :d="/\d/.test(passwordForm.password) ? 'M5 13l4 4L19 7' : 'M12 4v16m8-8H4'"/>
                            </svg>
                            Kamida bitta raqam (0-9)
                        </div>
                    </div>

                    <div class="flex justify-end pt-1">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            :class="[
                                'px-6 py-2.5 rounded-xl text-sm font-semibold transition',
                                passwordForm.processing
                                    ? 'bg-slate-100 text-slate-400 cursor-not-allowed'
                                    : 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-sm'
                            ]">
                            {{ passwordForm.processing ? 'Saqlanmoqda...' : 'Parolni o\'zgartirish' }}
                        </button>
                    </div>
                </form>
            </div>

            <!-- ── Teacher stats ── -->
            <div v-if="stats" class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 card-appear" style="animation-delay: 240ms">
                <h3 class="font-bold text-slate-800 text-sm mb-4">Mening statistikam</h3>
                <div class="grid grid-cols-2 sm:grid-cols-5 gap-3">
                    <div class="bg-indigo-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-indigo-600">{{ stats.totalGames }}</div>
                        <div class="text-[10px] text-indigo-500 mt-0.5 font-medium">Jami o'yin</div>
                    </div>
                    <div class="bg-green-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-green-600">{{ stats.readyGames }}</div>
                        <div class="text-[10px] text-green-500 mt-0.5 font-medium">Tayyor</div>
                    </div>
                    <div class="bg-purple-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-purple-600">{{ stats.totalSessions }}</div>
                        <div class="text-[10px] text-purple-500 mt-0.5 font-medium">Sessiya</div>
                    </div>
                    <div class="bg-amber-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-amber-600">{{ stats.totalStudents }}</div>
                        <div class="text-[10px] text-amber-500 mt-0.5 font-medium">O'quvchi</div>
                    </div>
                    <div class="bg-teal-50 rounded-xl p-3 text-center">
                        <div class="text-2xl font-extrabold text-teal-600">{{ stats.publicGames }}</div>
                        <div class="text-[10px] text-teal-500 mt-0.5 font-medium">Ommaviy</div>
                    </div>
                </div>
                <div class="mt-4 flex gap-2">
                    <Link href="/dashboard" class="text-xs text-indigo-600 hover:underline font-semibold">O'yinlarimga →</Link>
                    <span class="text-slate-200">·</span>
                    <Link href="/library" class="text-xs text-indigo-600 hover:underline font-semibold">Kutubxona →</Link>
                </div>
            </div>

            <!-- ── Account security ── -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <h3 class="font-bold text-slate-800 text-sm mb-4">Hisob xavfsizligi</h3>
                <div class="space-y-3">
                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">Hisob holati</p>
                                <p class="text-xs text-slate-400">Aktiv va himoyalangan</p>
                            </div>
                        </div>
                        <span class="text-xs font-bold bg-green-100 text-green-700 px-2.5 py-1 rounded-full">Aktiv</span>
                    </div>

                    <div class="flex items-center justify-between py-2 border-b border-slate-50">
                        <div class="flex items-center gap-3">
                            <div :class="['w-8 h-8 rounded-xl flex items-center justify-center', user?.google_id ? 'bg-blue-100' : 'bg-slate-100']">
                                <svg v-if="user?.google_id" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335"/>
                                </svg>
                                <svg v-else class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">Kirish usuli</p>
                                <p class="text-xs text-slate-400">{{ user?.google_id ? 'Google OAuth' : 'Email va parol' }}</p>
                            </div>
                        </div>
                        <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', user?.google_id ? 'bg-blue-100 text-blue-700' : 'bg-slate-100 text-slate-700']">
                            {{ user?.google_id ? 'Google' : 'Parol' }}
                        </span>
                    </div>

                    <div class="flex items-center justify-between py-2">
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-xl flex items-center justify-center">
                                <svg class="w-4 h-4 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                            </div>
                            <div>
                                <p class="text-xs font-semibold text-slate-700">Rol</p>
                                <p class="text-xs text-slate-400">Tizimda ruxsat darajasi</p>
                            </div>
                        </div>
                        <span :class="['text-xs font-bold px-2.5 py-1 rounded-full', roleColor]">{{ roleLabel }}</span>
                    </div>
                </div>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.slide-down-enter-active, .slide-down-leave-active { transition: all 0.3s ease; }
.slide-down-enter-from, .slide-down-leave-to { opacity: 0; transform: translateY(-8px); }
.card-appear { animation: cardSlide 0.4s ease both; }
@keyframes cardSlide {
    from { opacity: 0; transform: translateY(14px); }
    to   { opacity: 1; transform: translateY(0); }
}
</style>
