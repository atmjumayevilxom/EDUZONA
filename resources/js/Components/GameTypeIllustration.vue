<script setup>
defineProps({ code: { type: String, required: true } });
</script>

<template>
    <svg viewBox="0 0 100 70" fill="none" xmlns="http://www.w3.org/2000/svg" class="w-full h-full">

        <!-- ── quiz_mcq: Question + MCQ options ── -->
        <g v-if="code === 'quiz_mcq'">
            <circle cx="50" cy="20" r="13" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="50" y="26" text-anchor="middle" fill="white" font-size="16" font-weight="800" font-family="system-ui">?</text>
            <rect x="10" y="40" width="80" height="8" rx="4" fill="rgba(255,255,255,0.2)"/>
            <circle cx="18" cy="44" r="2.5" fill="white"/>
            <rect x="24" y="41.5" width="30" height="5" rx="2.5" fill="rgba(255,255,255,0.5)"/>
            <rect x="10" y="52" width="80" height="8" rx="4" fill="rgba(255,255,255,0.12)"/>
            <circle cx="18" cy="56" r="2.5" fill="rgba(255,255,255,0.5)"/>
            <rect x="24" y="53.5" width="22" height="5" rx="2.5" fill="rgba(255,255,255,0.3)"/>
            <rect x="10" y="64" width="80" height="5" rx="2.5" fill="rgba(255,255,255,0.08)"/>
        </g>

        <!-- ── anagram: Scattered letter tiles ── -->
        <g v-else-if="code === 'anagram'">
            <rect x="6"  y="8"  width="22" height="26" rx="5" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <text x="17" y="26" text-anchor="middle" fill="white" font-size="15" font-weight="800" font-family="system-ui">A</text>
            <rect x="36" y="4"  width="22" height="26" rx="5" fill="rgba(255,255,255,0.2)"  stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <text x="47" y="22" text-anchor="middle" fill="white" font-size="15" font-weight="800" font-family="system-ui">N</text>
            <rect x="66" y="12" width="22" height="26" rx="5" fill="rgba(255,255,255,0.3)"  stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <text x="77" y="30" text-anchor="middle" fill="white" font-size="15" font-weight="800" font-family="system-ui">G</text>
            <line x1="13" y1="56" x2="29" y2="56" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round"/>
            <line x1="36" y1="56" x2="52" y2="56" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round"/>
            <line x1="59" y1="56" x2="75" y2="56" stroke="rgba(255,255,255,0.7)" stroke-width="2" stroke-linecap="round"/>
            <text x="50" y="67" text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="7" font-family="system-ui">_  _  _  _</text>
        </g>

        <!-- ── true_false: Green ✓ and Red ✗ panels ── -->
        <g v-else-if="code === 'true_false'">
            <rect x="4"  y="8" width="44" height="54" rx="8" fill="rgba(255,255,255,0.2)"/>
            <text x="26" y="42" text-anchor="middle" fill="white" font-size="30" font-family="system-ui">✓</text>
            <rect x="52" y="8" width="44" height="54" rx="8" fill="rgba(255,255,255,0.12)"/>
            <text x="74" y="42" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="28" font-family="system-ui">✗</text>
        </g>

        <!-- ── flashcards: Stacked cards ── -->
        <g v-else-if="code === 'flashcards'">
            <rect x="22" y="10" width="58" height="40" rx="6" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.2)" stroke-width="1.5"/>
            <rect x="16" y="16" width="58" height="40" rx="6" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="1.5"/>
            <rect x="10" y="22" width="58" height="40" rx="6" fill="rgba(255,255,255,0.28)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="39" y="36" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="8" font-family="system-ui">SAVOL</text>
            <line x1="18" y1="45" x2="60" y2="45" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <line x1="18" y1="52" x2="50" y2="52" stroke="rgba(255,255,255,0.25)" stroke-width="1.5"/>
        </g>

        <!-- ── matching_pairs: Dots with connecting arcs ── -->
        <g v-else-if="code === 'matching_pairs'">
            <circle cx="22" cy="20" r="7" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <circle cx="22" cy="40" r="7" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <circle cx="22" cy="60" r="7" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <circle cx="78" cy="20" r="7" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <circle cx="78" cy="40" r="7" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <circle cx="78" cy="60" r="7" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <path d="M29 20 C50 20 50 40 71 40" stroke="white" stroke-width="1.5" fill="none" stroke-opacity="0.7"/>
            <path d="M29 40 C50 40 50 20 71 20" stroke="white" stroke-width="1.5" fill="none" stroke-opacity="0.4" stroke-dasharray="3,2"/>
            <path d="M29 60 C50 60 50 60 71 60" stroke="white" stroke-width="1.5" fill="none" stroke-opacity="0.6"/>
        </g>

        <!-- ── type_answer: Input field + keyboard ── -->
        <g v-else-if="code === 'type_answer'">
            <rect x="8" y="6" width="84" height="24" rx="6" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="18" y="22" fill="rgba(255,255,255,0.6)" font-size="10" font-family="system-ui">Javobingiz...</text>
            <rect x="72" y="11" width="2" height="14" rx="1" fill="white"/>
            <g fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.3)" stroke-width="0.5">
                <rect x="8"  y="36" width="11" height="9" rx="2"/>
                <rect x="21" y="36" width="11" height="9" rx="2"/>
                <rect x="34" y="36" width="11" height="9" rx="2"/>
                <rect x="47" y="36" width="11" height="9" rx="2"/>
                <rect x="60" y="36" width="11" height="9" rx="2"/>
                <rect x="73" y="36" width="11" height="9" rx="2"/>
            </g>
            <g fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.2)" stroke-width="0.5">
                <rect x="14" y="48" width="11" height="9" rx="2"/>
                <rect x="27" y="48" width="11" height="9" rx="2"/>
                <rect x="40" y="48" width="11" height="9" rx="2"/>
                <rect x="53" y="48" width="11" height="9" rx="2"/>
                <rect x="66" y="48" width="11" height="9" rx="2"/>
            </g>
            <rect x="25" y="60" width="50" height="9" rx="3" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.2)" stroke-width="0.5"/>
        </g>

        <!-- ── random_wheel: Spinning wheel ── -->
        <g v-else-if="code === 'random_wheel'">
            <circle cx="50" cy="38" r="30" fill="rgba(0,0,0,0.1)" stroke="rgba(255,255,255,0.3)" stroke-width="1.5"/>
            <path d="M50 38 L80 38 A30 30 0 0 0 65 12.3 Z"  fill="rgba(255,255,255,0.28)"/>
            <path d="M50 38 L65 12.3 A30 30 0 0 0 35 12.3 Z" fill="rgba(255,255,255,0.15)"/>
            <path d="M50 38 L35 12.3 A30 30 0 0 0 20 38 Z"  fill="rgba(255,255,255,0.28)"/>
            <path d="M50 38 L20 38 A30 30 0 0 0 35 63.7 Z"  fill="rgba(255,255,255,0.15)"/>
            <path d="M50 38 L35 63.7 A30 30 0 0 0 65 63.7 Z" fill="rgba(255,255,255,0.28)"/>
            <path d="M50 38 L65 63.7 A30 30 0 0 0 80 38 Z"  fill="rgba(255,255,255,0.15)"/>
            <line x1="50" y1="38" x2="80" y2="38"   stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <line x1="50" y1="38" x2="65" y2="12.3" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <line x1="50" y1="38" x2="35" y2="12.3" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <line x1="50" y1="38" x2="20" y2="38"   stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <line x1="50" y1="38" x2="35" y2="63.7" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <line x1="50" y1="38" x2="65" y2="63.7" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <circle cx="50" cy="38" r="7" fill="rgba(255,255,255,0.5)"/>
            <polygon points="50,3 54,13 46,13" fill="white" opacity="0.9"/>
        </g>

        <!-- ── open_box: Gift box opening ── -->
        <g v-else-if="code === 'open_box'">
            <rect x="18" y="34" width="64" height="32" rx="4" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <line x1="50" y1="34" x2="50" y2="66" stroke="rgba(255,255,255,0.5)" stroke-width="2.5"/>
            <rect x="18" y="24" width="64" height="12" rx="4" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <line x1="50" y1="24" x2="50" y2="36" stroke="rgba(255,255,255,0.5)" stroke-width="2.5"/>
            <rect x="12" y="8"  width="34" height="18" rx="4" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" transform="rotate(-15 29 17)"/>
            <text x="68" y="20" fill="white" font-size="12" text-anchor="middle">✨</text>
            <text x="25" y="14" fill="rgba(255,255,255,0.8)" font-size="10" text-anchor="middle">⭐</text>
        </g>

        <!-- ── complete_sentence: Fill-in-blank ── -->
        <g v-else-if="code === 'complete_sentence'">
            <rect x="8" y="10" width="50" height="6" rx="3" fill="rgba(255,255,255,0.4)"/>
            <rect x="62" y="10" width="30" height="6" rx="3" fill="rgba(255,255,255,0.4)"/>
            <rect x="8" y="24" width="28" height="6" rx="3" fill="rgba(255,255,255,0.35)"/>
            <line x1="40" y1="30" x2="72" y2="30" stroke="white" stroke-width="2.5" stroke-linecap="round"/>
            <rect x="76" y="24" width="16" height="6" rx="3" fill="rgba(255,255,255,0.35)"/>
            <rect x="8" y="40" width="84" height="6" rx="3" fill="rgba(255,255,255,0.2)"/>
            <rect x="8" y="54" width="60" height="6" rx="3" fill="rgba(255,255,255,0.15)"/>
            <circle cx="86" cy="28" r="6" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <text x="86" y="32" text-anchor="middle" fill="white" font-size="8">✎</text>
        </g>

        <!-- ── hangman: Gallows + stick figure ── -->
        <g v-else-if="code === 'hangman'">
            <line x1="12" y1="66" x2="60" y2="66" stroke="rgba(255,255,255,0.7)" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="28" y1="66" x2="28" y2="8"  stroke="rgba(255,255,255,0.7)" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="28" y1="8"  x2="62" y2="8"  stroke="rgba(255,255,255,0.7)" stroke-width="2.5" stroke-linecap="round"/>
            <line x1="62" y1="8"  x2="62" y2="18" stroke="rgba(255,255,255,0.7)" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="62" cy="26" r="7" fill="none" stroke="rgba(255,255,255,0.9)" stroke-width="2"/>
            <line x1="62" y1="33" x2="62" y2="50" stroke="rgba(255,255,255,0.8)" stroke-width="2" stroke-linecap="round"/>
            <line x1="62" y1="39" x2="52" y2="47" stroke="rgba(255,255,255,0.8)" stroke-width="2" stroke-linecap="round"/>
            <line x1="62" y1="39" x2="72" y2="47" stroke="rgba(255,255,255,0.8)" stroke-width="2" stroke-linecap="round"/>
            <line x1="62" y1="50" x2="54" y2="62" stroke="rgba(255,255,255,0.8)" stroke-width="2" stroke-linecap="round"/>
            <line x1="62" y1="50" x2="70" y2="62" stroke="rgba(255,255,255,0.8)" stroke-width="2" stroke-linecap="round"/>
            <line x1="73" y1="64" x2="82" y2="64" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linecap="round"/>
            <line x1="85" y1="64" x2="94" y2="64" stroke="rgba(255,255,255,0.5)" stroke-width="2" stroke-linecap="round"/>
        </g>

        <!-- ── reorder: Numbered list with arrows ── -->
        <g v-else-if="code === 'reorder'">
            <rect x="8" y="8"  width="72" height="13" rx="4" fill="rgba(255,255,255,0.18)" stroke="rgba(255,255,255,0.35)" stroke-width="1"/>
            <text x="17" y="19" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="9" font-weight="bold" font-family="system-ui">1</text>
            <rect x="24" y="11" width="34" height="5" rx="2.5" fill="rgba(255,255,255,0.35)"/>
            <rect x="8" y="27" width="72" height="13" rx="4" fill="rgba(255,255,255,0.35)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <text x="17" y="38" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="system-ui">2</text>
            <rect x="24" y="30" width="40" height="5" rx="2.5" fill="rgba(255,255,255,0.6)"/>
            <polygon points="86,30 92,38 80,38" fill="rgba(255,255,255,0.7)"/>
            <polygon points="86,41 92,33 80,33" fill="rgba(255,255,255,0.7)"/>
            <rect x="8" y="46" width="72" height="13" rx="4" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <text x="17" y="57" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="9" font-weight="bold" font-family="system-ui">3</text>
            <rect x="24" y="49" width="26" height="5" rx="2.5" fill="rgba(255,255,255,0.3)"/>
        </g>

        <!-- ── group_sort: Items sorted into buckets ── -->
        <g v-else-if="code === 'group_sort'">
            <path d="M8 28 L13 64 L44 64 L49 28 Z"  fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <rect x="6"  y="23" width="45" height="7" rx="3" fill="rgba(255,255,255,0.3)"/>
            <path d="M54 28 L59 64 L90 64 L95 28 Z" fill="rgba(255,255,255,0.18)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <rect x="52" y="23" width="45" height="7" rx="3" fill="rgba(255,255,255,0.3)"/>
            <rect x="16" y="38" width="18" height="8" rx="3" fill="rgba(255,255,255,0.4)"/>
            <rect x="16" y="50" width="16" height="8" rx="3" fill="rgba(255,255,255,0.3)"/>
            <rect x="63" y="38" width="18" height="8" rx="3" fill="rgba(255,255,255,0.4)"/>
            <rect x="36" y="8"  width="28" height="10" rx="4" fill="rgba(255,255,255,0.5)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            <line x1="50" y1="18" x2="50" y2="22" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" stroke-dasharray="2,2"/>
        </g>

        <!-- ── whack_mole: Mole + hammer ── -->
        <g v-else-if="code === 'whack_mole'">
            <ellipse cx="25" cy="57" rx="16" ry="7" fill="rgba(0,0,0,0.25)"/>
            <ellipse cx="75" cy="60" rx="14" ry="6" fill="rgba(0,0,0,0.2)"/>
            <ellipse cx="50" cy="58" rx="16" ry="7" fill="rgba(0,0,0,0.2)"/>
            <circle  cx="50" cy="42" r="15" fill="rgba(255,255,255,0.28)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <circle  cx="45" cy="39" r="2.5" fill="rgba(255,255,255,0.9)"/>
            <circle  cx="55" cy="39" r="2.5" fill="rgba(255,255,255,0.9)"/>
            <ellipse cx="50" cy="46" rx="4.5" ry="3" fill="rgba(255,255,255,0.5)"/>
            <line    x1="76" y1="16" x2="56" y2="34" stroke="rgba(255,255,255,0.85)" stroke-width="3.5" stroke-linecap="round"/>
            <rect    x="69" y="6"   width="16" height="12" rx="3" fill="rgba(255,255,255,0.7)" transform="rotate(45 77 12)"/>
        </g>

        <!-- ── word_search: Letter grid with highlights ── -->
        <g v-else-if="code === 'word_search'">
            <g fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.2)" stroke-width="0.5">
                <rect x="6"  y="6"  width="16" height="13" rx="2"/>
                <rect x="25" y="6"  width="16" height="13" rx="2"/>
                <rect x="44" y="6"  width="16" height="13" rx="2"/>
                <rect x="63" y="6"  width="16" height="13" rx="2"/>
                <rect x="82" y="6"  width="12" height="13" rx="2"/>
            </g>
            <rect x="6"  y="23" width="16" height="13" rx="2" fill="rgba(255,255,255,0.45)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            <rect x="25" y="23" width="16" height="13" rx="2" fill="rgba(255,255,255,0.45)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            <rect x="44" y="23" width="16" height="13" rx="2" fill="rgba(255,255,255,0.45)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            <rect x="63" y="23" width="16" height="13" rx="2" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.2)" stroke-width="0.5"/>
            <g fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.15)" stroke-width="0.5">
                <rect x="6"  y="40" width="16" height="13" rx="2"/>
                <rect x="25" y="40" width="16" height="13" rx="2"/>
                <rect x="44" y="40" width="16" height="13" rx="2"/>
                <rect x="63" y="40" width="16" height="13" rx="2"/>
            </g>
            <g fill="rgba(255,255,255,0.07)" stroke="rgba(255,255,255,0.1)" stroke-width="0.5">
                <rect x="6"  y="57" width="16" height="12" rx="2"/>
                <rect x="25" y="57" width="16" height="12" rx="2"/>
                <rect x="44" y="57" width="16" height="12" rx="2"/>
                <rect x="63" y="57" width="16" height="12" rx="2"/>
            </g>
            <text x="14"  y="32" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="system-ui">W</text>
            <text x="33"  y="32" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="system-ui">O</text>
            <text x="52"  y="32" text-anchor="middle" fill="white" font-size="9" font-weight="bold" font-family="system-ui">R</text>
            <text x="14"  y="15" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="8" font-family="system-ui">X</text>
            <text x="33"  y="15" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="8" font-family="system-ui">M</text>
            <text x="52"  y="15" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="8" font-family="system-ui">P</text>
            <text x="71"  y="15" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="8" font-family="system-ui">Q</text>
            <text x="71"  y="32" text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="8" font-family="system-ui">D</text>
        </g>

        <!-- ── memory_cards: 4 flip cards in 2x2 grid ── -->
        <g v-else-if="code === 'memory_cards'">
            <rect x="8"  y="8"  width="36" height="24" rx="4" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <rect x="56" y="8"  width="36" height="24" rx="4" fill="rgba(255,255,255,0.1)"  stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <rect x="8"  y="38" width="36" height="24" rx="4" fill="rgba(255,255,255,0.1)"  stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <rect x="56" y="38" width="36" height="24" rx="4" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="26" y="24" text-anchor="middle" fill="white" font-size="11" font-weight="800">A</text>
            <text x="74" y="24" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="14" font-weight="800">?</text>
            <text x="26" y="54" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="14" font-weight="800">?</text>
            <text x="74" y="54" text-anchor="middle" fill="white" font-size="11" font-weight="800">A</text>
            <path d="M44 20 Q50 8 56 20" stroke="rgba(255,255,255,0.6)" stroke-width="1.5" fill="none" stroke-dasharray="2,2"/>
        </g>

        <!-- ── game_show_quiz: TV screen + timer + stars ── -->
        <g v-else-if="code === 'game_show_quiz'">
            <rect x="12" y="8" width="76" height="44" rx="6" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <rect x="18" y="14" width="64" height="32" rx="3" fill="rgba(255,255,255,0.1)"/>
            <text x="50" y="26" text-anchor="middle" fill="white" font-size="7" font-weight="700" font-family="system-ui">SAVOL 3/10</text>
            <rect x="22" y="30" width="56" height="5" rx="2.5" fill="rgba(255,255,255,0.2)"/>
            <rect x="22" y="30" width="28" height="5" rx="2.5" fill="rgba(255,255,255,0.7)"/>
            <text x="50" y="42" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="6" font-family="system-ui">8 soniya qoldi</text>
            <polygon points="50,56 53,63 61,63 55,68 57,76 50,71 43,76 45,68 39,63 47,63" fill="rgba(255,255,255,0.7)" transform="scale(0.7) translate(22,5)"/>
            <polygon points="50,56 53,63 61,63 55,68 57,76 50,71 43,76 45,68 39,63 47,63" fill="rgba(255,255,255,0.4)" transform="scale(0.5) translate(62,20)"/>
        </g>

        <!-- ── flying_answers: colorful bubbles floating ── -->
        <g v-else-if="code === 'flying_answers'">
            <rect x="5" y="5" width="90" height="60" rx="8" fill="rgba(0,0,20,0.3)"/>
            <circle cx="25" cy="25" r="14" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <text x="25" y="30" text-anchor="middle" fill="white" font-size="8" font-weight="700">To'g'ri</text>
            <circle cx="72" cy="20" r="11" fill="rgba(255,100,100,0.4)" stroke="rgba(255,150,150,0.6)" stroke-width="1"/>
            <text x="72" y="25" text-anchor="middle" fill="white" font-size="7">Xato</text>
            <circle cx="60" cy="48" r="10" fill="rgba(255,200,50,0.4)" stroke="rgba(255,220,100,0.6)" stroke-width="1"/>
            <text x="60" y="53" text-anchor="middle" fill="white" font-size="7">???</text>
            <circle cx="22" cy="55" r="9" fill="rgba(100,200,100,0.4)" stroke="rgba(150,220,150,0.6)" stroke-width="1"/>
            <text x="22" y="60" text-anchor="middle" fill="white" font-size="7">Yaqin</text>
            <line x1="50" y1="5" x2="50" y2="65" stroke="rgba(255,255,255,0.1)" stroke-width="0.5"/>
        </g>

        <!-- ── pair_or_not: two cards + YES/NO buttons ── -->
        <g v-else-if="code === 'pair_or_not'">
            <rect x="5"  y="10" width="40" height="34" rx="5" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <rect x="55" y="10" width="40" height="34" rx="5" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="25" y="31" text-anchor="middle" fill="white" font-size="8" font-weight="700" font-family="system-ui">Cat</text>
            <text x="75" y="31" text-anchor="middle" fill="white" font-size="8" font-weight="700" font-family="system-ui">Mushuk</text>
            <text x="50" y="27" text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="10">=?</text>
            <rect x="5"  y="52" width="38" height="14" rx="7" fill="rgba(100,255,100,0.5)" stroke="rgba(150,255,150,0.7)" stroke-width="1"/>
            <text x="24" y="63" text-anchor="middle" fill="white" font-size="9" font-weight="800">✓ Ha</text>
            <rect x="57" y="52" width="38" height="14" rx="7" fill="rgba(255,100,100,0.5)" stroke="rgba(255,150,150,0.7)" stroke-width="1"/>
            <text x="76" y="63" text-anchor="middle" fill="white" font-size="9" font-weight="800">✗ Yo'q</text>
        </g>

        <!-- ── speed_sort: falling item + group buckets ── -->
        <g v-else-if="code === 'speed_sort'">
            <rect x="20" y="8" width="60" height="20" rx="5" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <text x="50" y="22" text-anchor="middle" fill="white" font-size="9" font-weight="800" font-family="system-ui">Element</text>
            <line x1="50" y1="28" x2="50" y2="38" stroke="rgba(255,255,255,0.5)" stroke-width="1.5" stroke-dasharray="2,2"/>
            <polygon points="50,40 47,36 53,36" fill="rgba(255,255,255,0.6)"/>
            <rect x="5"  y="48" width="38" height="18" rx="4" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <rect x="57" y="48" width="38" height="18" rx="4" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <text x="24" y="61" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="7.5" font-weight="700">Guruh A</text>
            <text x="76" y="61" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="7.5" font-weight="700">Guruh B</text>
            <rect x="5" y="66" width="90" height="3" rx="1.5" fill="rgba(255,255,255,0.1)"/>
            <rect x="5" y="66" width="55" height="3" rx="1.5" fill="rgba(255,255,255,0.5)"/>
        </g>

        <!-- ── spell_word: blank boxes + letter tiles ── -->
        <g v-else-if="code === 'spell_word'">
            <text x="50" y="16" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="7.5" font-family="system-ui">Izoh: The study of nature</text>
            <g fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.5)" stroke-width="1">
                <rect x="10" y="22" width="14" height="14" rx="3"/>
                <rect x="28" y="22" width="14" height="14" rx="3"/>
                <rect x="46" y="22" width="14" height="14" rx="3"/>
                <rect x="64" y="22" width="14" height="14" rx="3"/>
                <rect x="82" y="22" width="8"  height="14" rx="3"/>
            </g>
            <text x="17" y="33" text-anchor="middle" fill="white" font-size="10" font-weight="800">S</text>
            <text x="35" y="33" text-anchor="middle" fill="white" font-size="10" font-weight="800">C</text>
            <text x="53" y="33" text-anchor="middle" fill="rgba(255,255,255,0.3)" font-size="10">_</text>
            <text x="71" y="33" text-anchor="middle" fill="rgba(255,255,255,0.3)" font-size="10">_</text>
            <g fill="rgba(255,255,255,0.35)" stroke="rgba(255,255,255,0.6)" stroke-width="1">
                <rect x="6"  y="46" width="14" height="14" rx="3"/>
                <rect x="24" y="46" width="14" height="14" rx="3"/>
                <rect x="42" y="46" width="14" height="14" rx="3"/>
                <rect x="60" y="46" width="14" height="14" rx="3"/>
                <rect x="78" y="46" width="14" height="14" rx="3"/>
            </g>
            <text x="13"  y="57" text-anchor="middle" fill="white" font-size="9" font-weight="700">I</text>
            <text x="31"  y="57" text-anchor="middle" fill="white" font-size="9" font-weight="700">E</text>
            <text x="49"  y="57" text-anchor="middle" fill="white" font-size="9" font-weight="700">N</text>
            <text x="67"  y="57" text-anchor="middle" fill="white" font-size="9" font-weight="700">C</text>
            <text x="85"  y="57" text-anchor="middle" fill="white" font-size="9" font-weight="700">E</text>
            <rect x="6" y="64" width="88" height="4" rx="2" fill="rgba(255,255,255,0.1)"/>
        </g>

        <!-- ── airplane: Plane + 4 option clouds ── -->
        <g v-else-if="code === 'airplane'">
            <rect x="5" y="5" width="90" height="60" rx="7" fill="rgba(135,206,235,0.3)"/>
            <!-- clouds/options -->
            <ellipse cx="22" cy="18" rx="14" ry="9" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1"/>
            <ellipse cx="78" cy="18" rx="14" ry="9" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <ellipse cx="22" cy="52" rx="14" ry="9" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <ellipse cx="78" cy="52" rx="14" ry="9" fill="rgba(255,255,255,0.3)" stroke="rgba(255,255,255,0.6)" stroke-width="1"/>
            <!-- airplane -->
            <text x="50" y="41" text-anchor="middle" fill="white" font-size="20">✈</text>
        </g>

        <!-- ── watch_memorize: Eye + cards ── -->
        <g v-else-if="code === 'watch_memorize'">
            <!-- eye -->
            <ellipse cx="50" cy="22" rx="28" ry="14" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <circle cx="50" cy="22" r="8" fill="rgba(255,255,255,0.4)" stroke="rgba(255,255,255,0.7)" stroke-width="1"/>
            <circle cx="50" cy="22" r="4" fill="rgba(255,255,255,0.8)"/>
            <!-- cards below -->
            <rect x="10" y="42" width="36" height="22" rx="4" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <rect x="54" y="42" width="36" height="22" rx="4" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <text x="28" y="57" text-anchor="middle" fill="white" font-size="8" font-weight="700">ABC</text>
            <text x="72" y="57" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="8">???</text>
        </g>

        <!-- ── win_or_lose: Mystery gift boxes ── -->
        <g v-else-if="code === 'win_or_lose'">
            <rect x="8"  y="32" width="24" height="30" rx="3" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <rect x="38" y="26" width="24" height="36" rx="3" fill="rgba(255,255,255,0.28)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <rect x="68" y="34" width="24" height="28" rx="3" fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.4)" stroke-width="1"/>
            <text x="20"  y="52" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="14">🎁</text>
            <text x="50"  y="49" text-anchor="middle" fill="white" font-size="16">🏆</text>
            <text x="80"  y="54" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="14">🎁</text>
            <text x="50" y="14" text-anchor="middle" fill="white" font-size="8" font-weight="700">Yut yoki Yo'qot</text>
        </g>

        <!-- ── math_quiz: Equation symbols ── -->
        <g v-else-if="code === 'math_quiz'">
            <rect x="8" y="8" width="84" height="20" rx="5" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="50" y="22" text-anchor="middle" fill="white" font-size="11" font-weight="800" font-family="monospace">12 × 8 = ?</text>
            <rect x="8"  y="36" width="38" height="14" rx="4" fill="rgba(255,255,255,0.28)" stroke="rgba(255,255,255,0.6)" stroke-width="1.5"/>
            <text x="27" y="46" text-anchor="middle" fill="white" font-size="9" font-weight="800">96</text>
            <rect x="54" y="36" width="38" height="14" rx="4" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <text x="73" y="46" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="9">84</text>
            <rect x="8"  y="56" width="38" height="14" rx="4" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <text x="27" y="66" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="9">72</text>
            <rect x="54" y="56" width="38" height="14" rx="4" fill="rgba(255,255,255,0.12)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <text x="73" y="66" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="9">108</text>
        </g>

        <!-- ── millionaire: Podium + prize ladder ── -->
        <g v-else-if="code === 'millionaire'">
            <rect x="8" y="6" width="84" height="18" rx="5" fill="rgba(255,255,255,0.18)" stroke="rgba(255,255,255,0.4)" stroke-width="1.5"/>
            <text x="50" y="18" text-anchor="middle" fill="white" font-size="8" font-weight="800" font-family="system-ui">Millioner — kim bo'ladi?</text>
            <!-- prize steps -->
            <rect x="8"  y="30" width="80" height="7" rx="3" fill="rgba(255,255,255,0.08)"/>
            <rect x="8"  y="39" width="80" height="7" rx="3" fill="rgba(255,255,255,0.12)"/>
            <rect x="8"  y="48" width="80" height="7" rx="3" fill="rgba(255,255,255,0.2)"/>
            <rect x="8"  y="57" width="80" height="10" rx="4" fill="rgba(255,255,255,0.35)" stroke="rgba(255,255,255,0.6)" stroke-width="1"/>
            <text x="20" y="65" text-anchor="middle" fill="white" font-size="7">💰 1 000 000</text>
            <text x="70" y="35" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="7">100</text>
            <text x="70" y="44" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="7">1 000</text>
            <text x="70" y="53" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7">10 000</text>
        </g>

        <!-- ── spelling: Pencil + letter blanks ── -->
        <g v-else-if="code === 'spelling'">
            <text x="50" y="16" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="7.5" font-family="system-ui">So'zni to'g'ri yozing</text>
            <g fill="rgba(255,255,255,0.15)" stroke="rgba(255,255,255,0.5)" stroke-width="1.2">
                <rect x="7"  y="22" width="14" height="16" rx="3"/>
                <rect x="25" y="22" width="14" height="16" rx="3"/>
                <rect x="43" y="22" width="14" height="16" rx="3"/>
                <rect x="61" y="22" width="14" height="16" rx="3"/>
                <rect x="79" y="22" width="14" height="16" rx="3"/>
            </g>
            <text x="14"  y="34" text-anchor="middle" fill="white" font-size="10" font-weight="800">I</text>
            <text x="32"  y="34" text-anchor="middle" fill="white" font-size="10" font-weight="800">M</text>
            <text x="50"  y="34" text-anchor="middle" fill="white" font-size="10" font-weight="800">L</text>
            <text x="68"  y="34" text-anchor="middle" fill="rgba(255,255,255,0.3)" font-size="10">_</text>
            <text x="86"  y="34" text-anchor="middle" fill="rgba(255,255,255,0.3)" font-size="10">_</text>
            <!-- pencil -->
            <line x1="15" y1="48" x2="85" y2="48" stroke="rgba(255,255,255,0.2)" stroke-width="1"/>
            <text x="50" y="60" text-anchor="middle" fill="rgba(255,255,255,0.5)" font-size="7">✏ Harflarni kiriting</text>
        </g>

        <!-- ── diagram: Labeled parts with arrows ── -->
        <g v-else-if="code === 'diagram'">
            <!-- central object (circle) -->
            <circle cx="50" cy="35" r="18" fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <text x="50" y="39" text-anchor="middle" fill="white" font-size="8" font-weight="700">?</text>
            <!-- label lines -->
            <line x1="32" y1="22" x2="12" y2="12" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <rect x="2"  y="5"  width="22" height="10" rx="3" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>
            <text x="13" y="13" text-anchor="middle" fill="white" font-size="6.5">Qism 1</text>
            <line x1="68" y1="22" x2="88" y2="12" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <rect x="76" y="5"  width="22" height="10" rx="3" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>
            <text x="87" y="13" text-anchor="middle" fill="white" font-size="6.5">Qism 2</text>
            <line x1="32" y1="48" x2="12" y2="58" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <rect x="2"  y="54" width="22" height="10" rx="3" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>
            <text x="13" y="62" text-anchor="middle" fill="white" font-size="6.5">Qism 3</text>
            <line x1="68" y1="48" x2="88" y2="58" stroke="rgba(255,255,255,0.5)" stroke-width="1"/>
            <rect x="76" y="54" width="22" height="10" rx="3" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8"/>
            <text x="87" y="62" text-anchor="middle" fill="white" font-size="6.5">Qism 4</text>
        </g>

        <!-- ── zakovat: Trophy + brain icon (coming soon) ── -->
        <g v-else-if="code === 'zakovat'">
            <text x="50" y="38" text-anchor="middle" fill="white" font-size="26">🧠</text>
            <text x="50" y="58" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="8" font-weight="700">Zakovat</text>
        </g>

        <!-- ── rope_pull: Tug of war ── -->
        <g v-else-if="code === 'rope_pull'">
            <circle cx="18" cy="35" r="12" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <circle cx="82" cy="35" r="12" fill="rgba(255,255,255,0.25)" stroke="rgba(255,255,255,0.5)" stroke-width="1.5"/>
            <line x1="30" y1="35" x2="70" y2="35" stroke="rgba(255,255,255,0.8)" stroke-width="3" stroke-linecap="round"/>
            <circle cx="50" cy="35" r="5" fill="rgba(255,220,50,0.8)" stroke="white" stroke-width="1.5"/>
            <text x="50" y="58" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7.5" font-weight="700">Arqon tortish</text>
        </g>

        <!-- ── race: Track + runners ── -->
        <g v-else-if="code === 'race'">
            <rect x="5" y="15" width="90" height="40" rx="6" fill="rgba(255,255,255,0.08)" stroke="rgba(255,255,255,0.3)" stroke-width="1"/>
            <line x1="5" y1="30" x2="95" y2="30" stroke="rgba(255,255,255,0.2)" stroke-width="1" stroke-dasharray="4,3"/>
            <line x1="5" y1="44" x2="95" y2="44" stroke="rgba(255,255,255,0.2)" stroke-width="1" stroke-dasharray="4,3"/>
            <text x="25" y="27" fill="white" font-size="12">🏃</text>
            <text x="45" y="41" fill="white" font-size="12">🏃</text>
            <text x="60" y="27" fill="rgba(255,255,255,0.6)" font-size="10">🏃</text>
            <line x1="88" y1="15" x2="88" y2="55" stroke="rgba(255,255,255,0.6)" stroke-width="2"/>
            <text x="50" y="63" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7.5" font-weight="700">Poyga</text>
        </g>

        <!-- ── sleeping_bear: Bear + sound waves ── -->
        <g v-else-if="code === 'sleeping_bear'">
            <text x="50" y="36" text-anchor="middle" fill="white" font-size="24">🐻</text>
            <!-- zzz -->
            <text x="70" y="20" fill="rgba(255,255,255,0.7)" font-size="10" font-weight="800">z</text>
            <text x="78" y="13" fill="rgba(255,255,255,0.5)" font-size="12" font-weight="800">z</text>
            <text x="88" y="7"  fill="rgba(255,255,255,0.3)" font-size="14" font-weight="800">z</text>
            <text x="50" y="58" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7.5" font-weight="700">Uyqudagi Ayiq</text>
        </g>

        <!-- ── crossword: Grid with letters ── -->
        <g v-else-if="code === 'crossword'">
            <g fill="rgba(255,255,255,0.2)" stroke="rgba(255,255,255,0.4)" stroke-width="0.8">
                <rect x="18" y="5"  width="12" height="12" rx="1.5"/>
                <rect x="32" y="5"  width="12" height="12" rx="1.5"/>
                <rect x="18" y="19" width="12" height="12" rx="1.5"/>
                <rect x="32" y="19" width="12" height="12" rx="1.5"/>
                <rect x="46" y="19" width="12" height="12" rx="1.5"/>
                <rect x="32" y="33" width="12" height="12" rx="1.5"/>
                <rect x="46" y="33" width="12" height="12" rx="1.5"/>
                <rect x="60" y="33" width="12" height="12" rx="1.5"/>
            </g>
            <text x="24" y="14"  text-anchor="middle" fill="white" font-size="8" font-weight="700">A</text>
            <text x="38" y="14"  text-anchor="middle" fill="white" font-size="8" font-weight="700">B</text>
            <text x="24" y="28"  text-anchor="middle" fill="white" font-size="8" font-weight="700">C</text>
            <text x="38" y="28"  text-anchor="middle" fill="white" font-size="8" font-weight="700">D</text>
            <text x="52" y="28"  text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="8">?</text>
            <text x="38" y="42"  text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="8">?</text>
            <text x="50" y="62" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7.5" font-weight="700">Krossvord</text>
        </g>

        <!-- ── timeline: Horizontal timeline ── -->
        <g v-else-if="code === 'timeline'">
            <line x1="10" y1="35" x2="90" y2="35" stroke="rgba(255,255,255,0.6)" stroke-width="2.5" stroke-linecap="round"/>
            <circle cx="20" cy="35" r="5" fill="rgba(255,255,255,0.8)"/>
            <circle cx="40" cy="35" r="5" fill="rgba(255,255,255,0.6)"/>
            <circle cx="60" cy="35" r="5" fill="rgba(255,255,255,0.5)"/>
            <circle cx="80" cy="35" r="5" fill="rgba(255,255,255,0.35)"/>
            <text x="20" y="22" text-anchor="middle" fill="rgba(255,255,255,0.8)" font-size="6.5">1991</text>
            <text x="40" y="22" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="6.5">2000</text>
            <text x="60" y="22" text-anchor="middle" fill="rgba(255,255,255,0.6)" font-size="6.5">2010</text>
            <text x="80" y="22" text-anchor="middle" fill="rgba(255,255,255,0.4)" font-size="6.5">?</text>
            <text x="50" y="62" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7.5" font-weight="700">Vaqt chizig'i</text>
        </g>

        <!-- ── map_quiz: Simple map ── -->
        <g v-else-if="code === 'map_quiz'">
            <rect x="8" y="8" width="84" height="50" rx="6" fill="rgba(255,255,255,0.1)" stroke="rgba(255,255,255,0.3)" stroke-width="1.5"/>
            <path d="M15 40 Q25 20 40 28 Q50 15 65 25 Q78 18 88 30 L88 52 Q70 48 55 56 Q40 52 25 58 Z" fill="rgba(255,255,255,0.2)"/>
            <circle cx="40" cy="30" r="4" fill="rgba(255,100,100,0.9)"/>
            <circle cx="62" cy="26" r="3" fill="rgba(255,200,50,0.8)"/>
            <text x="50" y="63" text-anchor="middle" fill="rgba(255,255,255,0.7)" font-size="7.5" font-weight="700">Xarita testi</text>
        </g>

        <!-- ── survey, debate, team_quiz, default fallback ── -->
        <g v-else>
            <circle cx="50" cy="35" r="22" fill="rgba(255,255,255,0.2)"/>
            <text x="50" y="43" text-anchor="middle" fill="white" font-size="22">🎮</text>
        </g>
    </svg>
</template>
