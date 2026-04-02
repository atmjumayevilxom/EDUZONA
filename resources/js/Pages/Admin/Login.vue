<script setup>
import { ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    email: '',
    password: '',
});

function submit() {
    form.post('/admin/login');
}
</script>

<template>
    <Head title="Admin kirish" />
    <div class="min-h-screen bg-gray-950 flex items-center justify-center px-4">
        <div class="w-full max-w-sm">
            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="text-5xl mb-3">🎮</div>
                <h1 class="text-2xl font-bold text-white">EDUZONA</h1>
                <p class="text-gray-400 text-sm mt-1">Admin panel</p>
            </div>

            <!-- Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-2xl p-6 shadow-2xl">
                <h2 class="text-lg font-semibold text-white mb-6">Tizimga kirish</h2>

                <form @submit.prevent="submit" class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">Email</label>
                        <input
                            v-model="form.email"
                            type="email"
                            required
                            autocomplete="email"
                            placeholder="admin@games.uz"
                            class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        />
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-400 mb-1.5">Parol</label>
                        <input
                            v-model="form.password"
                            type="password"
                            required
                            autocomplete="current-password"
                            placeholder="••••••••"
                            class="w-full bg-gray-800 border border-gray-700 rounded-xl px-4 py-3 text-white text-sm placeholder-gray-600 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                        />
                    </div>

                    <!-- Xatolar -->
                    <div v-if="form.errors.email" class="bg-red-900/40 border border-red-800 text-red-400 text-sm rounded-xl px-4 py-3">
                        {{ form.errors.email }}
                    </div>

                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="w-full bg-indigo-600 hover:bg-indigo-500 disabled:bg-indigo-800 text-white font-semibold py-3 rounded-xl transition-all text-sm mt-2"
                    >
                        {{ form.processing ? 'Kirish...' : 'Kirish' }}
                    </button>
                </form>
            </div>

            <p class="text-center text-gray-600 text-xs mt-6">
                Foydalanuvchi sifatida kirish uchun
                <a href="/" class="text-gray-400 hover:text-white transition">asosiy sahifaga</a> o'ting
            </p>
        </div>
    </div>
</template>
