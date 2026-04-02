<script setup>
import { ref, computed } from 'vue';
import { Head } from '@inertiajs/vue3';

const studentName = ref('');
const gameTopic   = ref('');
const score       = ref('');
const total       = ref('');
const teacherName = ref('');
const date        = ref(new Date().toLocaleDateString('uz-Latn-UZ', { year: 'numeric', month: 'long', day: 'numeric' }));
const generated   = ref(false);

const pct = computed(() => {
    const s = parseInt(score.value) || 0;
    const t = parseInt(total.value) || 1;
    return Math.round((s / t) * 100);
});

const grade = computed(() => {
    if (pct.value >= 90) return { text: "A'lo", color: 'text-emerald-600', bg: 'bg-emerald-50' };
    if (pct.value >= 75) return { text: "Yaxshi", color: 'text-blue-600', bg: 'bg-blue-50' };
    if (pct.value >= 60) return { text: "Qoniqarli", color: 'text-amber-600', bg: 'bg-amber-50' };
    return { text: "Qoniqarsiz", color: 'text-red-500', bg: 'bg-red-50' };
});

function generate() {
    if (!studentName.value.trim() || !gameTopic.value.trim()) return;
    generated.value = true;
}

function printCert() {
    window.print();
}
</script>

<template>
    <Head title="Sertifikat yaratish" />

    <div class="min-h-screen bg-slate-100">
        <!-- Header (no-print) -->
        <div class="no-print bg-white border-b border-slate-200 px-6 py-3 flex items-center justify-between sticky top-0 z-10 shadow-sm">
            <div class="flex items-center gap-2">
                <div class="w-8 h-8 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center text-white text-sm">🎓</div>
                <span class="font-bold text-slate-800">EDUZONA — Sertifikat</span>
            </div>
            <div class="flex items-center gap-3">
                <button v-if="generated" @click="generated = false"
                    class="text-sm text-slate-500 hover:text-slate-700 transition">
                    ← Tahrirlash
                </button>
                <button v-if="generated" @click="printCert"
                    class="flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold px-4 py-2 rounded-xl transition text-sm shadow">
                    🖨️ Chop etish / PDF
                </button>
            </div>
        </div>

        <!-- Form (no-print) -->
        <div v-if="!generated" class="no-print max-w-xl mx-auto p-6 space-y-5">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-slate-800">📋 Sertifikat ma'lumotlarini kiriting</h2>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">O'quvchi ismi *</label>
                    <input v-model="studentName" type="text" placeholder="Aliyev Bobur"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"/>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">O'yin mavzusi *</label>
                    <input v-model="gameTopic" type="text" placeholder="O'zbekiston tarixi"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"/>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Ball</label>
                        <input v-model="score" type="number" min="0" placeholder="8"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"/>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-1">Jami savol</label>
                        <input v-model="total" type="number" min="1" placeholder="10"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"/>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">O'qituvchi ismi</label>
                    <input v-model="teacherName" type="text" placeholder="Karimova Dilnoza"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"/>
                </div>

                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1">Sana</label>
                    <input v-model="date" type="text"
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 transition"/>
                </div>

                <button @click="generate" :disabled="!studentName.trim() || !gameTopic.trim()"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 disabled:bg-slate-200 disabled:text-slate-400 disabled:cursor-not-allowed text-white font-bold py-3 rounded-xl transition text-sm">
                    🎓 Sertifikat yaratish
                </button>
            </div>
        </div>

        <!-- Certificate (printable) -->
        <div v-if="generated" class="cert-wrapper">
            <div class="certificate">
                <!-- Border decoration -->
                <div class="cert-border"></div>

                <!-- Header -->
                <div class="cert-header">
                    <div class="cert-logo">🎓</div>
                    <div class="cert-brand">EDUZONA</div>
                    <div class="cert-subtitle">AI Ta'lim Platformasi</div>
                </div>

                <!-- Title -->
                <div class="cert-title-line">
                    <div class="cert-divider"></div>
                    <span class="cert-title-text">SERTIFIKAT</span>
                    <div class="cert-divider"></div>
                </div>

                <!-- Body -->
                <div class="cert-body">
                    <p class="cert-given-to">Ushbu sertifikat</p>
                    <div class="cert-name">{{ studentName }}</div>
                    <p class="cert-desc">
                        ga <strong>"{{ gameTopic }}"</strong> mavzusidagi<br/>
                        o'quv o'yinini muvaffaqiyatli tugatganligi uchun beriladi
                    </p>

                    <!-- Score badge -->
                    <div v-if="score && total" class="cert-score-section">
                        <div class="cert-score-badge">
                            <div class="cert-score-value">{{ score }}/{{ total }}</div>
                            <div class="cert-score-pct">{{ pct }}%</div>
                        </div>
                        <div :class="['cert-grade', grade.color]">{{ grade.text }}</div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="cert-footer">
                    <div class="cert-footer-item">
                        <div class="cert-footer-line"></div>
                        <div class="cert-footer-label">{{ teacherName || "O'qituvchi" }}</div>
                        <div class="cert-footer-sublabel">O'qituvchi</div>
                    </div>
                    <div class="cert-seal">
                        <div class="cert-seal-inner">✓</div>
                    </div>
                    <div class="cert-footer-item">
                        <div class="cert-footer-line"></div>
                        <div class="cert-footer-label">{{ date }}</div>
                        <div class="cert-footer-sublabel">Sana</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
/* ── Wrapper ── */
.cert-wrapper {
    display: flex;
    justify-content: center;
    align-items: flex-start;
    padding: 2rem 1rem 3rem;
    min-height: calc(100vh - 60px);
    background: #f1f5f9;
}

/* ── Certificate card ── */
.certificate {
    width: 760px;
    max-width: 100%;
    background: #fff;
    border-radius: 1.5rem;
    padding: 3rem 3.5rem;
    position: relative;
    box-shadow: 0 20px 60px rgba(0,0,0,0.12);
    font-family: 'Georgia', serif;
}

.cert-border {
    position: absolute;
    inset: 10px;
    border: 3px solid transparent;
    border-radius: 1.2rem;
    background: linear-gradient(135deg, #6366f1, #8b5cf6, #6366f1) border-box;
    -webkit-mask: linear-gradient(#fff 0 0) padding-box, linear-gradient(#fff 0 0);
    -webkit-mask-composite: destination-out;
    mask-composite: exclude;
    pointer-events: none;
}

/* ── Header ── */
.cert-header { text-align: center; margin-bottom: 1.5rem; }
.cert-logo {
    width: 64px; height: 64px;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    border-radius: 1.2rem;
    display: flex; align-items: center; justify-content: center;
    font-size: 2rem; margin: 0 auto 0.75rem;
    box-shadow: 0 4px 20px rgba(99,102,241,0.3);
}
.cert-brand { font-size: 1.75rem; font-weight: 900; color: #312e81; letter-spacing: 0.1em; }
.cert-subtitle { font-size: 0.75rem; color: #6366f1; font-family: sans-serif; letter-spacing: 0.2em; text-transform: uppercase; margin-top: 0.25rem; }

/* ── Title ── */
.cert-title-line {
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 2rem;
}
.cert-divider { flex: 1; height: 1px; background: linear-gradient(90deg, transparent, #6366f1, transparent); }
.cert-title-text { font-size: 1.1rem; font-weight: 700; color: #4338ca; letter-spacing: 0.3em; font-family: sans-serif; white-space: nowrap; }

/* ── Body ── */
.cert-body { text-align: center; margin-bottom: 2.5rem; }
.cert-given-to { font-size: 0.9rem; color: #64748b; margin-bottom: 0.5rem; font-family: sans-serif; }
.cert-name {
    font-size: 2.25rem; font-weight: 700; color: #1e1b4b;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e0e7ff;
    display: inline-block;
    margin-bottom: 1rem;
}
.cert-desc { font-size: 0.95rem; color: #475569; line-height: 1.7; font-family: sans-serif; }

/* ── Score ── */
.cert-score-section { display: flex; align-items: center; justify-content: center; gap: 1rem; margin-top: 1.5rem; }
.cert-score-badge {
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    color: white; border-radius: 1rem;
    padding: 0.5rem 1.25rem; text-align: center;
}
.cert-score-value { font-size: 1.25rem; font-weight: 900; font-family: sans-serif; }
.cert-score-pct { font-size: 0.75rem; opacity: 0.85; font-family: sans-serif; }
.cert-grade { font-size: 1.1rem; font-weight: 700; font-family: sans-serif; }

/* ── Footer ── */
.cert-footer { display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
.cert-footer-item { text-align: center; flex: 1; }
.cert-footer-line { height: 1px; background: #c7d2fe; margin-bottom: 0.5rem; }
.cert-footer-label { font-size: 0.85rem; font-weight: 600; color: #312e81; font-family: sans-serif; }
.cert-footer-sublabel { font-size: 0.7rem; color: #94a3b8; font-family: sans-serif; }
.cert-seal {
    width: 64px; height: 64px; border-radius: 50%;
    background: linear-gradient(135deg, #6366f1, #8b5cf6);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 0 0 4px white, 0 0 0 6px #6366f1;
}
.cert-seal-inner { color: white; font-size: 1.5rem; font-weight: 900; }

/* ── Print ── */
@media print {
    .no-print { display: none !important; }
    body { margin: 0; }
    .cert-wrapper { padding: 0; background: white; min-height: auto; }
    .certificate {
        width: 100%; box-shadow: none; border-radius: 0;
        page-break-inside: avoid;
    }
}
</style>
