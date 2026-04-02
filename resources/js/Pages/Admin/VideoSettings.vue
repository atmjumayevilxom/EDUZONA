<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import axios from 'axios';

const form = ref({
    video_duration:      15,
    video_prompt_style:  'blackboard',
    video_daily_limit:   5,
    video_prompt_prefix: '',
    video_prompt_suffix: 'Academic chalkboard style. 720p quality.',
});
const loading  = ref(true);
const saving   = ref(false);
const saved    = ref(false);
const testReq  = ref(null);
const testing  = ref(false);

// Oxirgi 10 ta video so'rovi
const recentRequests = ref([]);

onMounted(async () => {
    const [settingsRes, requestsRes] = await Promise.all([
        axios.get('/api/admin/video-settings'),
        axios.get('/api/admin/video-requests').catch(() => ({ data: { data: [] } })),
    ]);
    Object.assign(form.value, settingsRes.data.data);
    recentRequests.value = requestsRes.data.data ?? [];
    loading.value = false;
});

async function save() {
    saving.value = true;
    try {
        await axios.patch('/api/admin/video-settings', form.value);
        saved.value = true;
        setTimeout(() => saved.value = false, 2500);
    } finally {
        saving.value = false;
    }
}

async function testPrompt() {
    testing.value = true;
    testReq.value = null;
    try {
        const { data } = await axios.post('/api/ai-video/generate', {
            subject:            'physics',
            problem_text:       'Massa 2 kg bo\'lgan jism 10 N kuch bilan itarildi. Tezlanish qanday?',
            video_style:        form.value.video_prompt_style,
            explanation_length: 'medium',
            voice_style:        'calm',
            language:           'uz',
        });
        testReq.value = data;
    } catch (e) {
        testReq.value = { error: e.response?.data?.message ?? 'Xato' };
    } finally {
        testing.value = false;
    }
}
</script>

<template>
    <Head title="Video Sozlamalar" />
    <AdminLayout>
        <template #header>
            <div class="flex items-center gap-3">
                <div>
                    <h1 class="text-base font-semibold text-slate-800">🎬 AI Video Sozlamalar</h1>
                    <p class="text-xs text-slate-400 mt-0.5">Grok video generatsiya konfiguratsiyasi</p>
                </div>
            </div>
        </template>

        <div class="max-w-2xl space-y-5">
            <div v-if="loading" class="bg-white rounded-2xl border border-slate-200 p-8 flex justify-center">
                <div class="w-6 h-6 border-2 border-indigo-600 border-t-transparent rounded-full animate-spin"></div>
            </div>

            <template v-else>
                <!-- Info -->
                <div class="bg-violet-50 border border-violet-200 rounded-2xl px-5 py-4 flex items-start gap-3">
                    <span class="text-lg shrink-0">⚡</span>
                    <div class="text-sm text-violet-800">
                        <strong>Grok grok-imagine-video</strong> — 8–30 soniyalik ta'limiy video klip yaratadi.
                        Qora doska uslubi, formula animatsiyasi, o'zbek tili.
                    </div>
                </div>

                <form @submit.prevent="save" class="space-y-5">
                    <!-- Asosiy sozlamalar -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700">Asosiy sozlamalar</h3>
                        </div>

                        <div class="divide-y divide-slate-50">
                            <!-- Video uzunligi -->
                            <div class="px-5 py-4 flex items-center gap-4">
                                <div class="w-9 h-9 bg-indigo-100 rounded-xl flex items-center justify-center text-lg shrink-0">⏱</div>
                                <div class="flex-1">
                                    <label class="text-sm font-semibold text-slate-800">Video uzunligi (soniya)</label>
                                    <p class="text-xs text-slate-400 mt-0.5">8–30 soniya. Ko'proq = ko'proq narx.</p>
                                    <div class="flex items-center gap-3 mt-2">
                                        <input type="range" v-model.number="form.video_duration"
                                            min="8" max="30" step="1"
                                            class="flex-1 accent-indigo-600" />
                                        <span class="w-12 text-center font-bold text-indigo-600 bg-indigo-50 rounded-lg py-1 text-sm">
                                            {{ form.video_duration }}s
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Default uslub -->
                            <div class="px-5 py-4 flex items-center gap-4">
                                <div class="w-9 h-9 bg-slate-100 rounded-xl flex items-center justify-center text-lg shrink-0">🖊</div>
                                <div class="flex-1">
                                    <label class="text-sm font-semibold text-slate-800">Default video uslubi</label>
                                    <p class="text-xs text-slate-400 mt-0.5">Foydalanuvchi tanlamas a default</p>
                                    <div class="flex gap-2 mt-2">
                                        <button v-for="(label, key) in { blackboard:'🖊 Doska', animated:'✨ Animatsiyali', minimal:'⬜ Minimal' }"
                                            :key="key" type="button"
                                            @click="form.video_prompt_style = key"
                                            :class="['px-3 py-1.5 rounded-lg text-xs font-medium border transition',
                                                form.video_prompt_style === key ? 'bg-indigo-600 text-white border-indigo-600' : 'bg-white text-slate-600 border-slate-200 hover:border-indigo-300']">
                                            {{ label }}
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Kunlik limit -->
                            <div class="px-5 py-4 flex items-center gap-4">
                                <div class="w-9 h-9 bg-amber-100 rounded-xl flex items-center justify-center text-lg shrink-0">🎯</div>
                                <div class="flex-1">
                                    <label class="text-sm font-semibold text-slate-800">Kunlik video limiti (user boshiga)</label>
                                    <p class="text-xs text-slate-400 mt-0.5">Bir foydalanuvchi kunda nechta video yarata oladi</p>
                                    <input type="number" v-model.number="form.video_daily_limit"
                                        min="1" max="50"
                                        class="mt-2 w-32 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 font-mono" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Prompt qo'shimchalari -->
                    <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-slate-100 bg-slate-50">
                            <h3 class="text-sm font-bold text-slate-700">Prompt sozlamalari</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Grok ga yuboriluvchi promptning boshi va oxiri</p>
                        </div>
                        <div class="p-5 space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                    Prompt boshi (prefix)
                                </label>
                                <textarea v-model="form.video_prompt_prefix" rows="2"
                                    placeholder="Masalan: Professional educational video creator."
                                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none" />
                                <p class="text-xs text-slate-400 mt-1">Avtomatik yaratilgan promptning OLD iga qo'shiladi</p>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">
                                    Prompt oxiri (suffix)
                                </label>
                                <textarea v-model="form.video_prompt_suffix" rows="2"
                                    placeholder="Masalan: Academic chalkboard style. 720p quality."
                                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono focus:outline-none focus:ring-2 focus:ring-indigo-300 resize-none" />
                                <p class="text-xs text-slate-400 mt-1">Avtomatik yaratilgan promptning OXIR iga qo'shiladi</p>
                            </div>
                        </div>
                    </div>

                    <!-- Saqlash -->
                    <div class="flex items-center gap-3">
                        <button type="submit" :disabled="saving"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition shadow-sm">
                            {{ saving ? 'Saqlanmoqda...' : 'Saqlash' }}
                        </button>
                        <Transition enter-active-class="transition duration-200" leave-active-class="transition duration-200"
                            enter-from-class="opacity-0" leave-to-class="opacity-0">
                            <span v-if="saved" class="text-green-600 text-sm font-medium flex items-center gap-1">
                                ✓ Saqlandi!
                            </span>
                        </Transition>
                        <button type="button" @click="testPrompt" :disabled="testing"
                            class="ml-auto border border-violet-300 text-violet-700 hover:bg-violet-50 disabled:opacity-60 font-medium px-4 py-2.5 rounded-xl text-sm transition">
                            {{ testing ? '⏳ Test...' : '🧪 Test video yaratish' }}
                        </button>
                    </div>
                </form>

                <!-- Test natija -->
                <div v-if="testReq" class="bg-white rounded-2xl border border-slate-200 p-5">
                    <h4 class="text-sm font-bold text-slate-800 mb-3">Test natijasi:</h4>
                    <div v-if="testReq.error" class="text-red-600 text-sm bg-red-50 rounded-xl p-3">
                        ❌ {{ testReq.error }}
                    </div>
                    <div v-else class="space-y-3">
                        <div class="flex items-center gap-2">
                            <span class="text-xs font-bold uppercase text-slate-400">Status:</span>
                            <span :class="['text-xs font-bold px-2 py-1 rounded-full',
                                testReq.status === 'completed' ? 'bg-green-100 text-green-700' : 'bg-amber-100 text-amber-700']">
                                {{ testReq.status }}
                            </span>
                        </div>
                        <div v-if="testReq.video_url" class="bg-black rounded-xl overflow-hidden aspect-video">
                            <video :src="testReq.video_url" controls class="w-full h-full"></video>
                        </div>
                        <div v-else-if="testReq.id" class="text-xs text-slate-500 bg-slate-50 rounded-xl p-3">
                            Video ID: {{ testReq.id }} — polling boshlanmoqda...
                            <a :href="`/ai-video/history`" class="text-indigo-600 hover:underline ml-2">Tarixda ko'rish →</a>
                        </div>
                    </div>
                </div>

                <!-- So'nggi video so'rovlar -->
                <div v-if="recentRequests.length" class="bg-white rounded-2xl border border-slate-200 overflow-hidden">
                    <div class="px-5 py-3 border-b border-slate-100 bg-slate-50 flex items-center justify-between">
                        <h3 class="text-sm font-bold text-slate-700">So'nggi video so'rovlar</h3>
                        <span class="text-xs text-slate-400">Barcha foydalanuvchilar</span>
                    </div>
                    <div class="divide-y divide-slate-50">
                        <div v-for="r in recentRequests" :key="r.id"
                            class="px-5 py-3 flex items-center gap-3">
                            <div class="w-7 h-7 bg-indigo-100 rounded-lg flex items-center justify-center text-xs font-bold text-indigo-600 shrink-0">
                                {{ r.id }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-slate-800 truncate">{{ r.topic || r.subject }}</p>
                                <p class="text-xs text-slate-400">{{ r.user_email }} · {{ r.created_at }}</p>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <span v-if="r.video_url" class="text-xs bg-green-100 text-green-700 font-bold px-2 py-1 rounded-full">▶ Video</span>
                                <span v-else :class="['text-xs font-bold px-2 py-1 rounded-full',
                                    r.status === 'completed' ? 'bg-indigo-100 text-indigo-600'
                                    : r.status === 'failed' ? 'bg-red-100 text-red-600'
                                    : 'bg-amber-100 text-amber-600']">
                                    {{ r.status }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </AdminLayout>
</template>
