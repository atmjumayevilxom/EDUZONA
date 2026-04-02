<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';

const props = defineProps({ joinCode: String });

const classroom  = ref(null);
const loading    = ref(true);
const notFound   = ref(false);
const nameInput  = ref('');
const joining    = ref(false);
const errorMsg   = ref('');
const joined     = ref(false);

onMounted(async () => {
    try {
        const res = await axios.get(`/api/classrooms/join/${props.joinCode}`);
        classroom.value = res.data.data;
    } catch {
        notFound.value = true;
    } finally {
        loading.value = false;
    }
});

async function joinClassroom() {
    const name = nameInput.value.trim();
    if (name.length < 2) return;
    joining.value = true;
    errorMsg.value = '';
    try {
        await axios.post(`/api/classrooms/join/${props.joinCode}`, { name });
        joined.value = true;
    } catch (e) {
        errorMsg.value = e.response?.data?.message ?? 'Xato yuz berdi.';
    } finally {
        joining.value = false;
    }
}
</script>

<template>
    <Head :title="classroom ? `${classroom.name} — Qo'shilish` : 'Sinfga qo\'shilish'" />

    <div class="min-h-screen bg-gradient-to-br from-indigo-50 to-purple-50 flex items-center justify-center p-4">
        <div class="w-full max-w-sm">

            <!-- Logo -->
            <div class="text-center mb-8">
                <div class="w-14 h-14 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl flex items-center justify-center text-white text-2xl mx-auto mb-3 shadow-lg shadow-indigo-200">
                    🎓
                </div>
                <h1 class="text-lg font-extrabold text-slate-800">EDUZONA</h1>
                <p class="text-xs text-slate-400">Sinf ro'yxatiga qo'shilish</p>
            </div>

            <!-- Loading -->
            <div v-if="loading" class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center card-appear">
                <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto mb-3"></div>
                <p class="text-sm text-slate-400">Yuklanmoqda...</p>
            </div>

            <!-- Not found -->
            <div v-else-if="notFound" class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center card-appear">
                <div class="text-4xl mb-3">🔍</div>
                <h2 class="font-bold text-slate-800 mb-1">Sinf topilmadi</h2>
                <p class="text-sm text-slate-400 mb-5">«{{ joinCode }}» kodli sinf mavjud emas yoki o'chirilgan.</p>
                <Link href="/" class="text-sm text-indigo-600 hover:underline">Bosh sahifaga qaytish</Link>
            </div>

            <!-- Join success -->
            <div v-else-if="joined" class="bg-white rounded-3xl shadow-xl border border-slate-100 p-8 text-center card-appear">
                <div class="text-5xl mb-4">🎉</div>
                <h2 class="font-bold text-slate-800 text-lg mb-1">Tabriklaymiz!</h2>
                <p class="text-slate-600 text-sm mb-1">
                    <strong>{{ classroom?.name }}</strong> sinfiga qo'shildingiz.
                </p>
                <p class="text-xs text-slate-400 mb-6">O'qituvchingiz o'yin yaratganda sessiya kodi beriladi.</p>
                <a href="/student" class="inline-block text-sm bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl transition">
                    Natijalarimni ko'rish →
                </a>
            </div>

            <!-- Join form -->
            <div v-else class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden card-appear">

                <!-- Classroom info -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-700 p-6 text-white text-center">
                    <div class="text-3xl mb-2">🎓</div>
                    <h2 class="font-bold text-lg leading-tight">{{ classroom?.name }}</h2>
                    <p v-if="classroom?.subject" class="text-indigo-200 text-sm mt-0.5">{{ classroom.subject }}</p>
                    <div class="mt-3 inline-flex items-center gap-1.5 text-xs bg-white/20 px-3 py-1.5 rounded-full">
                        <span>{{ classroom?.students_count ?? 0 }} ta o'quvchi ro'yxatda</span>
                    </div>
                </div>

                <!-- Form -->
                <div class="p-6">
                    <h3 class="font-bold text-slate-800 text-sm mb-1 text-center">Ismingizni kiriting</h3>
                    <p class="text-xs text-slate-400 text-center mb-5">Ro'yxatga qo'shilish uchun to'liq ismingizni yozing</p>

                    <form @submit.prevent="joinClassroom" class="space-y-3">
                        <input
                            v-model="nameInput"
                            type="text"
                            placeholder="Ism Familiya"
                            minlength="2"
                            maxlength="100"
                            class="w-full border border-slate-200 rounded-xl px-4 py-3 text-sm text-center font-medium focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
                            autofocus
                        />
                        <p v-if="errorMsg" class="text-red-500 text-xs text-center">{{ errorMsg }}</p>
                        <button
                            type="submit"
                            :disabled="nameInput.trim().length < 2 || joining"
                            class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-bold py-3.5 rounded-xl transition text-sm shadow-md shadow-indigo-200">
                            {{ joining ? 'Qo\'shilmoqda...' : "Ro'yxatga qo'shilish →" }}
                        </button>
                    </form>
                </div>
            </div>

            <p class="text-center text-xs text-slate-400 mt-6">EDUZONA Platforma</p>
        </div>
    </div>
</template>

<style scoped>
.card-appear { animation: cardPop 0.45s cubic-bezier(0.34, 1.56, 0.64, 1) both; }
@keyframes cardPop {
    from { opacity: 0; transform: scale(0.92) translateY(10px); }
    to   { opacity: 1; transform: scale(1) translateY(0); }
}
</style>
