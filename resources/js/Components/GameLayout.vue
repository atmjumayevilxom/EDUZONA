<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { useGameAudio } from '@/composables/useGameAudio';

const props = defineProps({
    title:         { type: String, default: "O'yin" },
    subtitle:      { type: String, default: '' },
    templateIcon:  { type: String, default: '🎮' },
    templateColor: { type: String, default: 'from-indigo-500 to-purple-600' },
    backHref:      { type: String, default: null },
    showRestart:   { type: Boolean, default: false },
});

const emit = defineEmits(['restart']);
const audio = useGameAudio();

const isFullscreen  = ref(false);
const focusMode     = ref(false);
const wrapper        = ref(null);
const confettiCanvas = ref(null);

// ── Theme color from Tailwind gradient class ──────────────────────────────
const bubbleColor = computed(() => {
    const c = props.templateColor ?? '';
    if (c.includes('indigo'))            return '#818cf8';
    if (c.includes('pink') || c.includes('rose')) return '#f472b6';
    if (c.includes('amber'))             return '#fbbf24';
    if (c.includes('yellow'))            return '#fde047';
    if (c.includes('orange'))            return '#fb923c';
    if (c.includes('green') || c.includes('emerald')) return '#34d399';
    if (c.includes('teal'))              return '#2dd4bf';
    if (c.includes('cyan'))              return '#22d3ee';
    if (c.includes('sky'))               return '#38bdf8';
    if (c.includes('blue'))              return '#60a5fa';
    if (c.includes('purple') || c.includes('violet')) return '#c084fc';
    if (c.includes('fuchsia'))           return '#e879f9';
    if (c.includes('red'))               return '#f87171';
    if (c.includes('lime'))              return '#a3e635';
    if (c.includes('gray') || c.includes('slate')) return '#94a3b8';
    return '#818cf8';
});

// ── Subtle background tint ─────────────────────────────────────────────────
const bgStyle = computed(() => ({
    background: `linear-gradient(145deg, #f8fafc 0%, ${bubbleColor.value}0d 100%)`,
}));

// ── Floating bubbles (deterministic, no Math.random in template) ──────────
const bubbles = computed(() => [
    { id:0,  size:28, left:8,  dur:11, delay:0   },
    { id:1,  size:18, left:18, dur:14, delay:2.5 },
    { id:2,  size:38, left:30, dur:9,  delay:1   },
    { id:3,  size:22, left:42, dur:16, delay:4   },
    { id:4,  size:14, left:55, dur:12, delay:0.5 },
    { id:5,  size:32, left:66, dur:10, delay:3   },
    { id:6,  size:20, left:75, dur:15, delay:6   },
    { id:7,  size:24, left:85, dur:13, delay:1.5 },
    { id:8,  size:16, left:92, dur:11, delay:5   },
    { id:9,  size:30, left:3,  dur:17, delay:7   },
    { id:10, size:12, left:48, dur:8,  delay:3.5 },
    { id:11, size:26, left:60, dur:13, delay:8   },
]);

// ── Confetti ───────────────────────────────────────────────────────────────
function launchConfetti() {
    const canvas = confettiCanvas.value;
    if (!canvas) return;
    const ctx = canvas.getContext('2d');
    canvas.width  = window.innerWidth;
    canvas.height = window.innerHeight;

    const COLORS = ['#6366f1','#10b981','#f59e0b','#ef4444','#8b5cf6','#06b6d4','#f97316','#ec4899','#84cc16'];
    const pieces = Array.from({ length: 160 }, () => ({
        x: Math.random() * canvas.width,
        y: -Math.random() * canvas.height * 0.4,
        vx: (Math.random() - 0.5) * 5,
        vy: Math.random() * 3 + 2,
        w: 5 + Math.random() * 9,
        h: 3 + Math.random() * 5,
        color: COLORS[Math.floor(Math.random() * COLORS.length)],
        rot: Math.random() * 360,
        rs: (Math.random() - 0.5) * 9,
        grav: 0.04 + Math.random() * 0.06,
    }));

    let raf;
    const end = Date.now() + 3800;

    function draw() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        let active = 0;
        for (const p of pieces) {
            p.x += p.vx;
            p.y += p.vy;
            p.rot += p.rs;
            p.vy += p.grav;
            if (p.y < canvas.height + 20) {
                active++;
                ctx.save();
                ctx.translate(p.x, p.y);
                ctx.rotate(p.rot * Math.PI / 180);
                ctx.fillStyle = p.color;
                ctx.fillRect(-p.w / 2, -p.h / 2, p.w, p.h);
                ctx.restore();
            }
        }
        if (active > 0 && Date.now() < end) {
            raf = requestAnimationFrame(draw);
        } else {
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        }
    }
    raf = requestAnimationFrame(draw);
    setTimeout(() => { cancelAnimationFrame(raf); ctx.clearRect(0, 0, canvas.width, canvas.height); }, 4200);
}

// ── Fullscreen ─────────────────────────────────────────────────────────────
function toggleFullscreen() {
    if (!document.fullscreenElement) {
        wrapper.value?.requestFullscreen?.();
    } else {
        document.exitFullscreen?.();
    }
}
function onFsChange() { isFullscreen.value = !!document.fullscreenElement; }

// ── Lifecycle ──────────────────────────────────────────────────────────────
const focusHref = computed(() => {
    if (typeof window === 'undefined') return '#';
    const url = new URL(window.location.href);
    url.searchParams.set('mode', 'focus');
    return url.pathname + url.search;
});

function exitFocusMode() {
    const url = new URL(window.location.href);
    url.searchParams.delete('mode');
    window.history.replaceState({}, '', url);
    focusMode.value = false;
}

onMounted(() => {
    focusMode.value = new URLSearchParams(window.location.search).get('mode') === 'focus';
    document.addEventListener('fullscreenchange', onFsChange);
    document.addEventListener('game-complete', launchConfetti);
});
onUnmounted(() => {
    document.removeEventListener('fullscreenchange', onFsChange);
    document.removeEventListener('game-complete', launchConfetti);
});
</script>

<template>
    <div ref="wrapper" class="min-h-screen flex flex-col relative" :style="bgStyle">

        <!-- Confetti canvas (fixed, always on top) -->
        <canvas ref="confettiCanvas" class="fixed inset-0 pointer-events-none z-[200]" />

        <!-- ══════════ FOCUS MODE MINI-BAR ══════════ -->
        <div v-if="focusMode" class="fixed top-3 right-3 z-50 flex items-center gap-2 bg-black/60 backdrop-blur-md rounded-2xl px-3 py-2 shadow-xl">
            <button @click="audio.toggleMute()" :title="audio.muted.value ? 'Musiqani yoqish' : 'Musiqani o\'chirish'"
                class="w-8 h-8 flex items-center justify-center text-white/80 hover:text-white rounded-xl hover:bg-white/10 transition">
                <svg v-if="!audio.muted.value" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.536 8.464a5 5 0 010 7.072M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                </svg>
                <svg v-else class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                </svg>
            </button>
            <button v-if="showRestart" @click="emit('restart')" title="Qayta boshlash"
                class="w-8 h-8 flex items-center justify-center text-white/80 hover:text-white rounded-xl hover:bg-white/10 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
            </button>
            <button @click="exitFocusMode" title="Focus rejimdan chiqish"
                class="w-8 h-8 flex items-center justify-center text-white/60 hover:text-white rounded-xl hover:bg-white/10 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <!-- ══════════ TOP BAR ══════════ -->
        <header v-if="!focusMode" class="sticky top-0 z-50 bg-white/95 backdrop-blur-sm border-b border-slate-200 shadow-sm shrink-0">
            <!-- Thin color accent line at top -->
            <div class="h-0.5 absolute top-0 left-0 right-0" :style="{ background: `linear-gradient(to right, ${bubbleColor}, ${bubbleColor}44, transparent)` }"></div>

            <div class="h-16 px-4 sm:px-6 flex items-center gap-3">

                <!-- Back button -->
                <a v-if="backHref && !isFullscreen" :href="backHref"
                    class="shrink-0 flex items-center gap-1.5 text-slate-500 hover:text-slate-800 font-semibold transition-colors px-3 py-2 rounded-xl hover:bg-slate-100 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="hidden sm:inline">Orqaga</span>
                </a>
                <div v-if="backHref && !isFullscreen" class="h-6 w-px bg-slate-200 shrink-0"></div>

                <!-- Template icon + game info -->
                <div class="flex items-center gap-3 min-w-0 mr-auto">
                    <div :class="['shrink-0 w-11 h-11 rounded-2xl bg-gradient-to-br flex items-center justify-center text-xl shadow-md', templateColor]">
                        {{ templateIcon }}
                    </div>
                    <div class="min-w-0">
                        <div class="font-black text-slate-800 text-base sm:text-lg leading-tight truncate max-w-[200px] sm:max-w-sm lg:max-w-lg">
                            {{ title }}
                        </div>
                        <div v-if="subtitle" class="text-sm text-slate-400 leading-tight font-medium truncate">{{ subtitle }}</div>
                    </div>
                </div>

                <!-- Center progress slot -->
                <div class="hidden lg:block shrink-0 w-56 xl:w-72">
                    <slot name="progress" />
                </div>

                <!-- Right actions -->
                <div class="flex items-center gap-1 shrink-0">
                    <slot name="actions" />
                    <div class="h-6 w-px bg-slate-200 mx-0.5"></div>

                    <!-- 🔊 Music toggle -->
                    <button @click="audio.toggleMute()"
                        :title="audio.muted.value ? 'Musiqani yoqish' : 'Musiqani o\'chirish'"
                        :class="['flex items-center gap-1.5 px-3 py-2 rounded-xl transition-colors text-sm font-medium',
                            audio.muted.value ? 'text-slate-300 hover:bg-slate-100' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-100']">
                        <svg v-if="!audio.muted.value" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15.536 8.464a5 5 0 010 7.072M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M5.586 15H4a1 1 0 01-1-1v-4a1 1 0 011-1h1.586l4.707-4.707C10.923 3.663 12 4.109 12 5v14c0 .891-1.077 1.337-1.707.707L5.586 15z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2"/>
                        </svg>
                        <span class="hidden sm:inline text-xs">{{ audio.muted.value ? 'Ses off' : 'Ses' }}</span>
                    </button>

                    <div class="h-6 w-px bg-slate-200 mx-0.5"></div>

                    <!-- Restart -->
                    <button v-if="showRestart" @click="emit('restart')" title="Qayta boshlash"
                        class="flex items-center gap-1.5 px-3 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                        </svg>
                        <span class="hidden sm:inline">Qayta</span>
                    </button>

                    <!-- Focus mode -->
                    <a :href="`${focusHref}`" title="Proyektor rejimi"
                        class="flex items-center gap-1.5 px-3 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <span class="hidden md:inline">Proyektor</span>
                    </a>
                    <div class="h-6 w-px bg-slate-200 mx-0.5"></div>

                    <!-- Fullscreen -->
                    <button @click="toggleFullscreen" :title="isFullscreen ? 'Kichraytirish' : 'To\'liq ekran'"
                        class="flex items-center gap-1.5 px-3 py-2 text-slate-500 hover:text-slate-700 hover:bg-slate-100 rounded-xl transition-colors text-sm font-medium">
                        <svg v-if="!isFullscreen" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4"/>
                        </svg>
                        <svg v-else class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 9V4.5M9 9H4.5M9 15v4.5M9 15H4.5M15 9h4.5M15 9V4.5M15 15h4.5M15 15v4.5"/>
                        </svg>
                        <span class="hidden md:inline">{{ isFullscreen ? 'Kichraytirish' : 'To\'liq ekran' }}</span>
                    </button>
                </div>
            </div>

            <!-- Mobile progress -->
            <div v-if="$slots.progress" class="lg:hidden border-t border-slate-100 px-4 py-2">
                <slot name="progress" />
            </div>
        </header>

        <!-- ══════════ BODY ══════════ -->
        <div class="flex-1 flex relative">

            <!-- Floating bubbles (behind content) -->
            <div class="absolute inset-0 overflow-hidden pointer-events-none select-none">
                <div
                    v-for="b in bubbles" :key="b.id"
                    class="bubble absolute bottom-0 rounded-full opacity-[0.13]"
                    :style="{
                        width:  `${b.size}px`,
                        height: `${b.size}px`,
                        left:   `${b.left}%`,
                        background: bubbleColor,
                        animationDuration: `${b.dur}s`,
                        animationDelay:    `${b.delay}s`,
                    }"
                />
            </div>

            <!-- Main game canvas -->
            <main class="flex-1 min-w-0 relative z-10">
                <div :class="['w-full', focusMode ? 'p-4 sm:p-6' : 'p-4 sm:p-6 lg:p-8 xl:p-10']">
                    <slot />
                </div>
            </main>

            <!-- Optional side panel -->
            <aside v-if="$slots.panel"
                class="hidden lg:flex flex-col w-80 xl:w-96 shrink-0 border-l border-slate-200 bg-white/80 backdrop-blur-sm relative z-10">
                <div class="p-6">
                    <slot name="panel" />
                </div>
            </aside>
        </div>
    </div>
</template>

<style scoped>
@keyframes floatUp {
    0%   { transform: translateY(0px) scale(1);    opacity: 0;    }
    8%   { opacity: 0.13; }
    85%  { opacity: 0.08; }
    100% { transform: translateY(-105vh) scale(0.6); opacity: 0; }
}
.bubble { animation: floatUp linear infinite; }
</style>
