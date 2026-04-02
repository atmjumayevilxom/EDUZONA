<script setup>
import { ref } from 'vue';

const props = defineProps({ gameData: { type: Object, required: true } });

const expanded = ref({});

function toggle(id) {
    expanded.value[id] = !expanded.value[id];
}

function print() {
    window.print();
}

const phaseColors = {
    'Kirish':         { bg: 'bg-blue-50',    border: 'border-blue-200',    text: 'text-blue-700',    icon: '🔔' },
    'Yangi mavzu':    { bg: 'bg-indigo-50',  border: 'border-indigo-200',  text: 'text-indigo-700',  icon: '📖' },
    'Mustahkamlash':  { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700', icon: '✏️' },
    'Baholash':       { bg: 'bg-amber-50',   border: 'border-amber-200',   text: 'text-amber-700',   icon: '📊' },
};

function phaseStyle(phase) {
    return phaseColors[phase] ?? { bg: 'bg-slate-50', border: 'border-slate-200', text: 'text-slate-700', icon: '📌' };
}
</script>

<template>
    <div class="min-h-screen bg-gradient-to-br from-slate-50 to-blue-50 p-4 sm:p-8 print:bg-white print:p-0">

        <!-- Header -->
        <div class="max-w-4xl mx-auto">
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 mb-5 print:shadow-none print:border-2 print:border-slate-400">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 text-xs text-slate-500 mb-2">
                            <span class="bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">Dars rejasi</span>
                            <span v-if="gameData.subject" class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">{{ gameData.subject }}</span>
                            <span v-if="gameData.duration" class="bg-slate-100 text-slate-600 px-2 py-0.5 rounded-full">⏱ {{ gameData.duration }}</span>
                        </div>
                        <h1 class="text-xl sm:text-2xl font-bold text-slate-800">{{ gameData.title }}</h1>
                    </div>
                    <button @click="print"
                            class="print:hidden shrink-0 flex items-center gap-1.5 bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium px-4 py-2 rounded-xl text-sm transition">
                        🖨️ Chop etish
                    </button>
                </div>

                <!-- Objectives -->
                <div v-if="gameData.objectives?.length" class="mt-4">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-2">Maqsadlar</h3>
                    <ul class="space-y-1">
                        <li v-for="(obj, i) in gameData.objectives" :key="i"
                            class="flex items-start gap-2 text-sm text-slate-700">
                            <span class="text-indigo-500 mt-0.5 shrink-0">✦</span>
                            {{ obj }}
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Sections -->
            <div class="space-y-3 mb-5">
                <div v-for="section in gameData.sections" :key="section.id"
                     :class="['rounded-2xl border overflow-hidden shadow-sm print:shadow-none', phaseStyle(section.phase).border]">

                    <!-- Section header -->
                    <button
                        @click="toggle(section.id)"
                        :class="[
                            'w-full flex items-center justify-between gap-3 p-4 text-left transition print:cursor-default',
                            phaseStyle(section.phase).bg
                        ]">
                        <div class="flex items-center gap-3">
                            <span class="text-xl">{{ phaseStyle(section.phase).icon }}</span>
                            <div>
                                <div :class="['font-bold text-base', phaseStyle(section.phase).text]">
                                    {{ section.phase }}
                                </div>
                                <div class="text-xs text-slate-500 mt-0.5">⏱ {{ section.duration }}</div>
                            </div>
                        </div>
                        <span class="print:hidden text-slate-400 transition-transform"
                              :class="{ 'rotate-180': expanded[section.id] }">▼</span>
                    </button>

                    <!-- Section body -->
                    <div :class="['bg-white border-t', phaseStyle(section.phase).border,
                                  expanded[section.id] ? 'block' : 'hidden print:block']">
                        <div class="p-4 grid sm:grid-cols-3 gap-4 text-sm">
                            <div>
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">O'qituvchi faoliyati</div>
                                <p class="text-slate-700 leading-relaxed">{{ section.activity }}</p>
                            </div>
                            <div>
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">O'quvchi faoliyati</div>
                                <p class="text-slate-700 leading-relaxed">{{ section.student_activity }}</p>
                            </div>
                            <div v-if="section.materials">
                                <div class="text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Jihozlar</div>
                                <p class="text-slate-700 leading-relaxed">{{ section.materials }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Assessment & Homework -->
            <div class="grid sm:grid-cols-2 gap-4">
                <div v-if="gameData.assessment"
                     class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm print:shadow-none">
                    <h3 class="font-bold text-slate-700 text-sm mb-2 flex items-center gap-1.5">
                        <span>📊</span> Baholash
                    </h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ gameData.assessment }}</p>
                </div>
                <div v-if="gameData.homework"
                     class="bg-white rounded-2xl border border-slate-200 p-4 shadow-sm print:shadow-none">
                    <h3 class="font-bold text-slate-700 text-sm mb-2 flex items-center gap-1.5">
                        <span>📚</span> Uy vazifasi
                    </h3>
                    <p class="text-sm text-slate-600 leading-relaxed">{{ gameData.homework }}</p>
                </div>
            </div>
        </div>
    </div>
</template>
