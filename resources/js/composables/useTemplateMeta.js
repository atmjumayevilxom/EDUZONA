const templateMeta = {
    quiz_mcq:          { icon: '❓', color: 'from-indigo-500 to-blue-600',   bg: 'bg-indigo-50',   desc: "Ko'p tanlovli savollar",              label: 'Viktorina' },
    anagram:           { icon: '🔤', color: 'from-pink-500 to-rose-600',      bg: 'bg-pink-50',     desc: "Tartib buzilgan so'zlar",              label: 'Anagramma' },
    true_false:        { icon: '✅', color: 'from-green-500 to-emerald-600',  bg: 'bg-green-50',    desc: "To'g'ri yoki noto'g'ri?",             label: "To'g'ri/Noto'g'ri" },
    flashcards:        { icon: '🃏', color: 'from-yellow-500 to-orange-500',  bg: 'bg-yellow-50',   desc: 'Ikki tomonlama kartalar',              label: 'Flesh-kartochkalar' },
    matching_pairs:    { icon: '🔗', color: 'from-purple-500 to-violet-600',  bg: 'bg-purple-50',   desc: 'Juftlarni moslashtirish',              label: 'Juftlashtirish' },
    type_answer:       { icon: '⌨️', color: 'from-cyan-500 to-teal-600',      bg: 'bg-cyan-50',     desc: 'Javobni yozib kiriting',               label: 'Javob yozish' },
    random_wheel:      { icon: '🎰', color: 'from-amber-500 to-yellow-600',   bg: 'bg-amber-50',    desc: 'Tasodifiy element tanlash',            label: "Tasodifiy g'ildirak" },
    word_search:       { icon: '🔍', color: 'from-blue-500 to-indigo-600',    bg: 'bg-blue-50',     desc: "So'zlarni jadvaldan topish",           label: "So'z qidirish" },
    reorder:           { icon: '↕️', color: 'from-teal-500 to-cyan-600',      bg: 'bg-teal-50',     desc: "To'g'ri tartibga soling",              label: 'Tartiblashtirish' },
    group_sort:        { icon: '🗂',  color: 'from-orange-500 to-red-500',     bg: 'bg-orange-50',   desc: 'Guruhlarga ajratish',                  label: 'Guruhlash' },
    complete_sentence: { icon: '📝', color: 'from-violet-500 to-purple-600',  bg: 'bg-violet-50',   desc: "Bo'sh joylarni to'ldirish",            label: "Gap to'ldirish" },
    open_box:          { icon: '🎁', color: 'from-rose-500 to-pink-600',      bg: 'bg-rose-50',     desc: "Qutini ochib ko'ring",                 label: 'Qutini och' },
    whack_mole:        { icon: '🔨', color: 'from-lime-500 to-green-600',     bg: 'bg-lime-50',     desc: "Tez bosish o'yini",                    label: 'Mol urish' },
    hangman:           { icon: '🪢', color: 'from-gray-600 to-slate-700',     bg: 'bg-gray-50',     desc: "Harflarni topib so'z yig'ish",         label: 'Harfni top' },
    memory_cards:      { icon: '🧠', color: 'from-sky-500 to-blue-600',       bg: 'bg-sky-50',      desc: 'Juft kartalarni eslab qoling',         label: 'Xotira kartalar' },
    game_show_quiz:    { icon: '📺', color: 'from-fuchsia-500 to-pink-600',   bg: 'bg-fuchsia-50',  desc: 'Televizion viktorina uslubida',        label: 'TV Viktorina' },
    flying_answers:    { icon: '🕊️', color: 'from-emerald-500 to-green-600',  bg: 'bg-emerald-50',  desc: 'Uchayotgan javoblarni bosing',         label: 'Uchuvchi javoblar' },
    pair_or_not:       { icon: '🔀', color: 'from-orange-500 to-amber-600',   bg: 'bg-orange-50',   desc: 'Juft yoki juft emasmi?',               label: "Juft yoki yo'q" },
    speed_sort:        { icon: '⚡', color: 'from-red-500 to-rose-600',       bg: 'bg-red-50',      desc: 'Tez guruhlarga ajrating',              label: 'Tez saralash' },
    spell_word:        { icon: '🔡', color: 'from-teal-500 to-emerald-600',   bg: 'bg-teal-50',     desc: "Harflardan so'z tuzing",               label: "So'z imlosi" },
    airplane:          { icon: '✈️', color: 'from-sky-500 to-cyan-600',       bg: 'bg-sky-50',      desc: "Uchuvchi javoblarni to'g'ri tanlang",  label: "Samolyot o'yini" },
    watch_memorize:    { icon: '👁',  color: 'from-violet-500 to-indigo-600',  bg: 'bg-violet-50',   desc: "Ko'rib eslab qoling, so'ng test",      label: "Ko'rib eslab qol" },
    win_or_lose:       { icon: '🎁', color: 'from-yellow-500 to-orange-500',  bg: 'bg-yellow-50',   desc: 'Sirli qutini oching!',                 label: 'Yut yoki Yoqot' },
    math_quiz:         { icon: '🔢', color: 'from-blue-600 to-indigo-700',    bg: 'bg-blue-50',     desc: 'Matematik masalalar',                  label: 'Matematik test' },
    millionaire:       { icon: '💰', color: 'from-yellow-600 to-amber-700',   bg: 'bg-yellow-50',   desc: 'Millioner uslubida viktorina',         label: 'Millioner' },
    spelling:          { icon: '✏️', color: 'from-fuchsia-500 to-pink-600',   bg: 'bg-fuchsia-50',  desc: "Imlo — so'zni to'g'ri yozing",        label: 'Imlo' },
    diagram:           { icon: '🗺️', color: 'from-blue-600 to-indigo-700',    bg: 'bg-blue-50',     desc: 'Diagramma qismlarini belgilang',       label: 'Diagramma' },
    zakovat:           { icon: '🧩', color: 'from-purple-600 to-fuchsia-600', bg: 'bg-purple-50',   desc: "Kategoriyali viktorina o'yini",        label: 'Zakovat' },
    race:              { icon: '🏎️', color: 'from-red-500 to-orange-500',     bg: 'bg-red-50',      desc: 'Savollar bilan poyga!',                label: 'Poyga' },
    timeline:          { icon: '📅', color: 'from-teal-600 to-cyan-600',      bg: 'bg-teal-50',     desc: 'Voqealarni tartibga soling',           label: "Vaqt chizig'i" },
    crossword:         { icon: '🔠', color: 'from-violet-600 to-purple-700',  bg: 'bg-violet-50',   desc: 'Krossword topishmoqlari',              label: 'Krossvord' },
    lesson_plan:       { icon: '📅', color: 'from-sky-500 to-blue-600',      bg: 'bg-sky-50',      desc: 'Kunlik dars rejasi tayyorlash',        label: 'Dars rejasi' },
    dtm_test:          { icon: '🎓', color: 'from-indigo-600 to-violet-700', bg: 'bg-indigo-50',   desc: 'DTM formatidagi test savollari',        label: 'DTM test' },
    rope_pull:         { icon: '💪', color: 'from-emerald-500 to-green-600', bg: 'bg-emerald-50',  desc: 'Arqon tortish — to\'g\'ri javob ber',  label: 'Arqon tortish' },
    sleeping_bear:     { icon: '🐻', color: 'from-amber-600 to-orange-600',  bg: 'bg-amber-50',    desc: 'Ayiqni uyg\'otma — 3 hayoting bor',   label: 'Uyqudagi Ayiq' },
    pisa_reading:      { icon: '🌍', color: 'from-teal-500 to-cyan-600',     bg: 'bg-teal-50',     desc: 'PISA formatida o\'qish va tahlil',      label: 'PISA o\'qish' },
    olimpiada:         { icon: '🥇', color: 'from-yellow-500 to-amber-600',  bg: 'bg-yellow-50',   desc: 'Olimpiada darajasidagi savollar',       label: 'Olimpiada' },
};

const fallback = { icon: '🎮', color: 'from-indigo-500 to-purple-600', bg: 'bg-indigo-50', desc: '', label: '' };

export function useTemplateMeta() {
    function tplMeta(code) {
        return templateMeta[code] ?? { ...fallback, label: code };
    }
    return { tplMeta, templateMeta };
}
