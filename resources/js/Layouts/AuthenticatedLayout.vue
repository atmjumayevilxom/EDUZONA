<script setup>
import { ref, watch, computed } from 'vue';
import { Link, router, usePage } from '@inertiajs/vue3';
import { useAuthStore } from '@/stores/auth';

const auth = useAuthStore();
const page = usePage();
const sidebarOpen = ref(false);

// --- Flash toast ---
const toasts = ref([]);
let toastId = 0;

function addToast(message, type = 'success') {
    const id = ++toastId;
    toasts.value.push({ id, message, type });
    setTimeout(() => removeToast(id), 4000);
}
function removeToast(id) {
    toasts.value = toasts.value.filter(t => t.id !== id);
}

watch(() => page.props.flash?.success, (msg) => { if (msg) addToast(msg, 'success'); });
watch(() => page.props.flash?.error,   (msg) => { if (msg) addToast(msg, 'error'); });

function logout() {
    router.post(route('logout'));
}

const navItems = [
    {
        label: "O'yinlarim",
        href: '/dashboard',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        match: (url) => url === '/dashboard',
    },
    {
        label: 'Yaratish',
        href: '/create',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>`,
        match: (url) => url.startsWith('/create') || url.startsWith('/games/create'),
        badge: 'AI',
    },
    {
        label: 'Darslar',
        href: '/lessons',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>`,
        match: (url) => url.startsWith('/lessons'),
        soon: true,
    },
    {
        label: 'Kutubxona',
        href: '/library',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 14v3m4-3v3m4-3v3M3 21h18M3 10h18M3 7l9-4 9 4M4 10h16v11H4V10z"/></svg>`,
        match: (url) => url.startsWith('/library'),
        soon: true,
    },
    {
        label: 'Materiallar',
        href: '/materials',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>`,
        match: (url) => url.startsWith('/materials'),
        soon: true,
    },
    {
        label: 'AI Video Yechim',
        href: '/ai-video',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 10l4.553-2.069A1 1 0 0121 8.87v6.26a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>`,
        match: (url) => url.startsWith('/ai-video'),
        badge: 'AI',
    },
    {
        label: 'Sinflar',
        href: '/classrooms',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>`,
        match: (url) => url.startsWith('/classrooms'),
    },
    {
        label: "O'quvchi kabineti",
        href: '/student',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"/></svg>`,
        match: (url) => url.startsWith('/student'),
    },
    {
        label: 'Yordam',
        href: '/help',
        icon: `<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>`,
        match: (url) => url.startsWith('/help'),
    },
];
</script>

<template>
    <div class="min-h-screen bg-slate-50 flex">

        <!-- ===== SIDEBAR ===== -->
        <aside :class="[
            'fixed inset-y-0 left-0 z-40 w-60 bg-white border-r border-slate-100 flex flex-col transition-transform duration-300 shadow-sm',
            sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'
        ]">
            <!-- Logo -->
            <div class="h-16 flex items-center px-5 border-b border-slate-100 shrink-0">
                <Link href="/dashboard" class="flex items-center gap-2.5">
                    <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-sm shadow-sm">
                        🎮
                    </div>
                    <div class="leading-none">
                        <div class="font-bold text-slate-900 text-sm">EDUZONA</div>
                        <div class="text-[10px] text-slate-400 mt-0.5">Ta'lim platformasi</div>
                    </div>
                </Link>
            </div>

            <!-- Nav -->
            <nav class="flex-1 px-3 py-4 space-y-0.5 overflow-y-auto">
                <Link
                    v-for="item in navItems" :key="item.href"
                    :href="item.href"
                    @click="sidebarOpen = false"
                    :class="[
                        'flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all group relative',
                        item.match($page.url)
                            ? 'bg-indigo-50 text-indigo-700'
                            : item.soon
                                ? 'text-slate-400 hover:bg-slate-50 hover:text-slate-500 cursor-default'
                                : 'text-slate-600 hover:bg-slate-50 hover:text-slate-900'
                    ]">
                    <!-- Icon -->
                    <span :class="['shrink-0', item.match($page.url) ? 'text-indigo-600' : item.soon ? 'text-slate-300' : 'text-slate-400 group-hover:text-slate-600']"
                        v-html="item.icon">
                    </span>

                    <span class="flex-1">{{ item.label }}</span>

                    <!-- AI badge -->
                    <span v-if="item.badge"
                        class="text-[9px] font-extrabold bg-indigo-600 text-white px-1.5 py-0.5 rounded-full leading-none">
                        {{ item.badge }}
                    </span>

                    <!-- Coming soon badge -->
                    <span v-if="item.soon"
                        class="text-[9px] font-bold bg-amber-100 text-amber-600 px-1.5 py-0.5 rounded-full leading-none">
                        Breda
                    </span>
                </Link>

                <!-- Admin section -->
                <div v-if="auth.isAdmin" class="pt-4 pb-1">
                    <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wider px-3 mb-1">Admin</div>
                </div>
                <template v-if="auth.isAdmin">
                    <Link href="/admin"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url === '/admin' ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                        Dashboard
                    </Link>
                    <Link href="/admin/users"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/users') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        Foydalanuvchilar
                    </Link>
                    <Link href="/admin/templates"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/templates') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                        Shablonlar
                    </Link>
                    <Link href="/admin/games"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/games') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                        </svg>
                        Barcha o'yinlar
                    </Link>
                    <Link href="/admin/categories"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/categories') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/>
                        </svg>
                        Kategoriyalar
                    </Link>
                    <Link href="/admin/ai-settings"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/ai-settings') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><circle cx="12" cy="12" r="3"/>
                        </svg>
                        AI Sozlamalar
                    </Link>
                    <Link href="/admin/prompts"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/prompts') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Promptlar
                    </Link>
                    <Link href="/admin/audit-logs"
                        :class="['flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all', $page.url.startsWith('/admin/audit-logs') ? 'bg-purple-50 text-purple-700' : 'text-slate-600 hover:bg-slate-50']">
                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Audit Log
                    </Link>
                </template>
            </nav>

            <!-- User section -->
            <div class="p-3 border-t border-slate-100 shrink-0">
                <Link href="/profile"
                    class="flex items-center gap-3 px-2 py-2 rounded-xl hover:bg-slate-50 transition group">
                    <img v-if="auth.user?.avatar" :src="auth.user.avatar"
                        class="w-9 h-9 rounded-full ring-2 ring-slate-100 shrink-0 group-hover:ring-indigo-200 transition" />
                    <div v-else
                        class="w-9 h-9 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-sm shrink-0">
                        {{ auth.user?.name?.[0]?.toUpperCase() }}
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-sm font-medium text-slate-800 truncate group-hover:text-indigo-700 transition">{{ auth.user?.name }}</div>
                        <div class="text-xs text-slate-400 truncate">{{ auth.isAdmin ? 'Administrator' : 'Pedagog' }}</div>
                    </div>
                    <svg class="w-3.5 h-3.5 text-slate-300 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </Link>
                <button @click="logout"
                    class="mt-1 w-full flex items-center gap-2 px-3 py-2 text-sm text-red-400 hover:bg-red-50 hover:text-red-600 rounded-xl transition">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                    Chiqish
                </button>
            </div>
        </aside>

        <!-- Mobile overlay -->
        <div v-if="sidebarOpen" @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-black/20 lg:hidden backdrop-blur-sm">
        </div>

        <!-- Main content -->
        <div class="flex-1 flex flex-col lg:pl-60 min-h-screen">
            <!-- Top bar -->
            <header class="sticky top-0 z-20 bg-white border-b border-slate-100 shadow-sm">
                <div class="flex items-center h-16 px-4 sm:px-6 gap-4">
                    <button @click="sidebarOpen = !sidebarOpen" class="lg:hidden text-slate-500 hover:text-slate-700 p-1.5 rounded-lg hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                        </svg>
                    </button>
                    <div class="flex-1">
                        <slot name="header" />
                    </div>
                </div>
            </header>

            <main class="flex-1 p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>

    <!-- Toast notifications -->
    <div class="fixed bottom-5 right-5 z-[100] flex flex-col gap-2 pointer-events-none">
        <TransitionGroup
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 translate-y-4 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0 translate-x-4"
        >
            <div v-for="toast in toasts" :key="toast.id"
                :class="[
                    'flex items-center gap-3 px-4 py-3 rounded-2xl shadow-lg border text-sm font-medium pointer-events-auto max-w-sm',
                    toast.type === 'success'
                        ? 'bg-green-50 border-green-200 text-green-800'
                        : 'bg-red-50 border-red-200 text-red-800'
                ]">
                <span class="text-lg shrink-0">{{ toast.type === 'success' ? '✅' : '❌' }}</span>
                <span class="flex-1">{{ toast.message }}</span>
                <button @click="removeToast(toast.id)"
                    :class="['shrink-0 hover:opacity-70 transition', toast.type === 'success' ? 'text-green-600' : 'text-red-600']">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </TransitionGroup>
    </div>
</template>
