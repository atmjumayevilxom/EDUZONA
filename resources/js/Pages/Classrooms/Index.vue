<script setup>
import { ref, onMounted } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import axios from 'axios';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';

const classrooms   = ref([]);
const loading      = ref(true);
const showCreate   = ref(false);
const creating     = ref(false);
const newName      = ref('');
const newSubject   = ref('');
const formError    = ref('');
const activeClass  = ref(null);   // selected classroom for student list
const students     = ref([]);
const studentsLoading = ref(false);
const copiedCode      = ref(null);
const deletingId      = ref(null);
const confirmDeleteId = ref(null);

onMounted(fetchClassrooms);

async function fetchClassrooms() {
    loading.value = true;
    try {
        const res = await axios.get('/api/classrooms');
        classrooms.value = res.data.data;
    } finally {
        loading.value = false;
    }
}

async function createClassroom() {
    if (!newName.value.trim()) return;
    creating.value = true;
    formError.value = '';
    try {
        const res = await axios.post('/api/classrooms', {
            name: newName.value.trim(),
            subject: newSubject.value.trim() || null,
        });
        classrooms.value.unshift(res.data.data);
        showCreate.value = false;
        newName.value = '';
        newSubject.value = '';
    } catch (e) {
        formError.value = e.response?.data?.message ?? 'Xato yuz berdi.';
    } finally {
        creating.value = false;
    }
}

async function deleteClassroom(id) {
    deletingId.value = id;
    try {
        await axios.delete(`/api/classrooms/${id}`);
        classrooms.value = classrooms.value.filter(c => c.id !== id);
        if (activeClass.value?.id === id) { activeClass.value = null; students.value = []; }
    } finally {
        deletingId.value = null;
        confirmDeleteId.value = null;
    }
}

async function openStudents(classroom) {
    if (activeClass.value?.id === classroom.id) {
        activeClass.value = null; students.value = []; return;
    }
    activeClass.value = classroom;
    studentsLoading.value = true;
    try {
        const res = await axios.get(`/api/classrooms/${classroom.id}/students`);
        students.value = res.data.data.students;
    } finally {
        studentsLoading.value = false;
    }
}

async function removeStudent(studentId) {
    if (!activeClass.value) return;
    await axios.delete(`/api/classrooms/${activeClass.value.id}/students/${studentId}`);
    students.value = students.value.filter(s => s.id !== studentId);
    const cls = classrooms.value.find(c => c.id === activeClass.value.id);
    if (cls) cls.students_count = Math.max(0, (cls.students_count ?? 1) - 1);
}

async function copyJoinLink(classroom) {
    const url = `${window.location.origin}/join/${classroom.join_code}`;
    await navigator.clipboard.writeText(url);
    copiedCode.value = classroom.id;
    setTimeout(() => { copiedCode.value = null; }, 2000);
}

function joinUrl(code) {
    return `${window.location.origin}/join/${code}`;
}
</script>

<template>
    <Head title="Sinflarim" />
    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-base font-semibold text-slate-800">Sinflar boshqaruvi</h2>
        </template>

        <div class="max-w-4xl mx-auto space-y-5">

            <!-- Header row -->
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">O'quvchilarni sinflarga birlashtiring va sessiyalarni kuzating</p>
                </div>
                <button @click="showCreate = !showCreate"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-4 py-2.5 rounded-xl text-sm transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Yangi sinf
                </button>
            </div>

            <!-- Create form -->
            <Transition enter-active-class="transition-all duration-300" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0">
                <div v-if="showCreate" class="bg-white border border-indigo-100 rounded-2xl p-5 shadow-sm">
                    <h3 class="font-bold text-slate-800 text-sm mb-4">Yangi sinf yaratish</h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 mb-4">
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Sinf nomi *</label>
                            <input v-model="newName" type="text" placeholder="Masalan: 5A sinf"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        </div>
                        <div>
                            <label class="text-xs font-medium text-slate-600 block mb-1">Fan (ixtiyoriy)</label>
                            <input v-model="newSubject" type="text" placeholder="Masalan: Matematika"
                                class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400" />
                        </div>
                    </div>
                    <p v-if="formError" class="text-red-500 text-xs mb-3">{{ formError }}</p>
                    <div class="flex gap-2">
                        <button @click="createClassroom" :disabled="!newName.trim() || creating"
                            class="bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 text-white font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                            {{ creating ? 'Yaratilmoqda...' : 'Yaratish' }}
                        </button>
                        <button @click="showCreate = false; formError = ''"
                            class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-semibold px-5 py-2.5 rounded-xl text-sm transition">
                            Bekor
                        </button>
                    </div>
                </div>
            </Transition>

            <!-- Loading -->
            <div v-if="loading" class="flex justify-center py-16">
                <div class="w-8 h-8 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin"></div>
            </div>

            <!-- Empty state -->
            <div v-else-if="!classrooms.length" class="bg-white rounded-2xl border border-dashed border-slate-200 p-12 text-center">
                <div class="text-5xl mb-4">🎓</div>
                <h3 class="font-bold text-slate-700 mb-1">Hali sinf yo'q</h3>
                <p class="text-sm text-slate-400 mb-5">Yangi sinf yarating va o'quvchilarni qo'shing</p>
                <button @click="showCreate = true"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-semibold px-6 py-2.5 rounded-xl text-sm transition">
                    Birinchi sinfni yaratish →
                </button>
            </div>

            <!-- Classrooms list -->
            <div v-else class="space-y-3">
                <div v-for="(cls, ci) in classrooms" :key="cls.id"
                    :style="`animation-delay: ${ci * 60}ms`"
                    class="bg-white border border-slate-100 rounded-2xl overflow-hidden hover:shadow-sm transition cls-appear">

                    <!-- Classroom header -->
                    <div class="px-5 py-4 flex items-center gap-4">
                        <div class="w-10 h-10 bg-indigo-100 rounded-xl flex items-center justify-center text-xl shrink-0">🎓</div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-bold text-slate-800 text-sm truncate">{{ cls.name }}</h3>
                            <p class="text-xs text-slate-400">
                                {{ cls.subject ? cls.subject + ' · ' : '' }}{{ cls.students_count ?? 0 }} ta o'quvchi
                            </p>
                        </div>

                        <!-- Join code badge -->
                        <div class="flex items-center gap-2 shrink-0">
                            <span class="font-mono font-bold text-indigo-700 bg-indigo-50 px-3 py-1.5 rounded-lg text-sm tracking-widest">
                                {{ cls.join_code }}
                            </span>
                            <button @click="copyJoinLink(cls)"
                                :class="['text-xs font-semibold px-3 py-1.5 rounded-lg transition', copiedCode === cls.id ? 'bg-green-100 text-green-700' : 'bg-slate-100 hover:bg-slate-200 text-slate-600']">
                                {{ copiedCode === cls.id ? '✓ Nusxalandi' : 'Link' }}
                            </button>
                        </div>

                        <!-- Actions -->
                        <div class="flex items-center gap-1 shrink-0">
                            <button @click="openStudents(cls)"
                                :class="['p-2 rounded-lg transition text-sm font-medium', activeClass?.id === cls.id ? 'bg-indigo-100 text-indigo-700' : 'hover:bg-slate-100 text-slate-500']">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                                </svg>
                            </button>
                            <!-- Delete — inline confirm -->
                            <template v-if="confirmDeleteId === cls.id">
                                <button @click="deleteClassroom(cls.id)" :disabled="deletingId === cls.id"
                                    class="text-xs bg-red-500 hover:bg-red-600 disabled:opacity-60 text-white font-bold px-2.5 py-1.5 rounded-lg transition">
                                    {{ deletingId === cls.id ? '...' : "Ha, o'chir" }}
                                </button>
                                <button @click="confirmDeleteId = null"
                                    class="text-xs text-slate-400 hover:text-slate-600 px-2 py-1.5 rounded-lg hover:bg-slate-100 transition">
                                    Bekor
                                </button>
                            </template>
                            <button v-else @click="confirmDeleteId = cls.id"
                                class="p-2 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <!-- Students panel -->
                    <div v-if="activeClass?.id === cls.id" class="border-t border-slate-100 bg-slate-50">

                        <!-- Join link info -->
                        <div class="px-5 py-3 bg-indigo-50 border-b border-indigo-100 flex items-center gap-3">
                            <svg class="w-4 h-4 text-indigo-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/>
                            </svg>
                            <span class="text-xs text-indigo-700 font-medium flex-1 truncate">
                                O'quvchilar uchun havola: <strong>{{ joinUrl(cls.join_code) }}</strong>
                            </span>
                            <button @click="copyJoinLink(cls)"
                                class="text-xs text-indigo-600 hover:text-indigo-800 font-semibold shrink-0">
                                {{ copiedCode === cls.id ? '✓ Nusxalandi' : 'Nusxalash' }}
                            </button>
                        </div>

                        <!-- Students loading -->
                        <div v-if="studentsLoading" class="p-5 text-center">
                            <div class="w-5 h-5 border-2 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mx-auto"></div>
                        </div>

                        <!-- Students list -->
                        <div v-else-if="students.length" class="divide-y divide-slate-100">
                            <div v-for="(student, si) in students" :key="student.id"
                                :style="`animation-delay: ${si * 40}ms`"
                                class="px-5 py-2.5 flex items-center gap-3 stu-appear">
                                <div class="w-7 h-7 bg-white border border-slate-200 rounded-full flex items-center justify-center text-xs font-bold text-slate-600 shrink-0">
                                    {{ student.name.charAt(0).toUpperCase() }}
                                </div>
                                <span class="flex-1 text-sm text-slate-700 font-medium">{{ student.name }}</span>
                                <span class="text-xs text-slate-400">
                                    {{ new Date(student.joined_at).toLocaleDateString('uz-Latn-UZ') }}
                                </span>
                                <button @click="removeStudent(student.id)"
                                    class="w-6 h-6 flex items-center justify-center rounded-lg hover:bg-red-50 text-slate-300 hover:text-red-400 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <!-- Empty students -->
                        <div v-else class="p-8 text-center">
                            <p class="text-sm text-slate-400">Hali o'quvchi yo'q</p>
                            <p class="text-xs text-slate-300 mt-1">Yuqoridagi havolani o'quvchilarga yuboring</p>
                        </div>

                    </div>
                </div>
            </div>

            <!-- Info box -->
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5">
                <h4 class="font-bold text-blue-800 text-sm mb-2">Qanday ishlaydi?</h4>
                <ol class="text-xs text-blue-700 space-y-1 list-decimal list-inside leading-relaxed">
                    <li>Sinf yarating (5A, 6B, va h.k.)</li>
                    <li>Havola yoki kodni o'quvchilarga yuboring</li>
                    <li>O'quvchilar <strong>/join/KOD</strong> sahifasida ismlarini kiritib ro'yxatga qo'shiladi</li>
                    <li>Sessiya yaratganda o'quvchilar natijalarini kuzating</li>
                </ol>
            </div>

        </div>
    </AuthenticatedLayout>
</template>

<style scoped>
.cls-appear { animation: clsSlideIn 0.35s ease both; }
@keyframes clsSlideIn {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}
.stu-appear { animation: stuFadeIn 0.25s ease both; }
@keyframes stuFadeIn {
    from { opacity: 0; transform: translateX(-6px); }
    to   { opacity: 1; transform: translateX(0); }
}
</style>
