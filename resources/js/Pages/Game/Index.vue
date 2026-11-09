<script setup>
import { computed, onMounted, ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';

const props = defineProps({
    game: { type: Object, default: null },
    categories: { type: Array, default: () => [] },
    errors: { type: Object, default: () => ({}) },
});

const playerCount = ref(5);
const language = ref('both');
const category = ref('all');
const imposterMode = ref('hidden');
const settingsOpen = ref(false);
const preferencesLoaded = ref(false);
const processing = ref(false);
const confirmEnd = ref(false);

const validPhases = ['handoff', 'revealed', 'ready', 'result'];
const phase = computed(() => {
    if (!props.game) return 'setup';
    return validPhases.includes(props.game.phase) ? props.game.phase : 'invalid';
});
const progress = computed(() => {
    if (!props.game) return 0;
    return Math.round((props.game.currentPlayer / props.game.playerCount) * 100);
});
const gameModes = [
    { value: 'hidden', name: 'Different Word', description: 'One player gets a different word. Nobody knows their role.' },
    { value: 'revealed', name: 'Imposter + Hint', description: 'The imposter knows their role and gets a clue instead of a word.' },
];

onMounted(() => {
    try {
        const saved = JSON.parse(localStorage.getItem('imposter_settings') || '{}');
        if (Number.isInteger(saved.playerCount) && saved.playerCount >= 3 && saved.playerCount <= 20) {
            playerCount.value = saved.playerCount;
        }
        if (['both', 'en', 'ms'].includes(saved.language)) language.value = saved.language;
        if (['hidden', 'revealed'].includes(saved.imposterMode)) imposterMode.value = saved.imposterMode;
        if (saved.category === 'all' || props.categories.includes(saved.category)) category.value = saved.category;
    } catch {
        // Browser settings are optional.
    }
    preferencesLoaded.value = true;
});

watch([playerCount, language, category, imposterMode], () => {
    if (!preferencesLoaded.value) return;
    try {
        localStorage.setItem('imposter_settings', JSON.stringify({
            playerCount: playerCount.value,
            language: language.value,
            category: category.value,
            imposterMode: imposterMode.value,
        }));
    } catch {
        // Private browsing may disable storage.
    }
});

function post(url, data = {}) {
    if (processing.value) return;
    processing.value = true;
    router.post(url, data, {
        preserveScroll: true,
        onFinish: () => { processing.value = false; },
    });
}
function currentSettings() {
    return { language: language.value, category: category.value, imposter_mode: imposterMode.value };
}
function start() {
    post('/game/start', { player_count: playerCount.value, ...currentSettings() });
}
function nextGame() {
    post('/game/next-game', currentSettings());
}
function endSession() {
    confirmEnd.value = false;
    settingsOpen.value = false;
    post('/game/end-session');
}
function openSettings() {
    if (phase.value === 'revealed') return;
    settingsOpen.value = true;
}
</script>

<template>
    <Head title="Imposter Game" />
    <div class="min-h-screen palette-bg px-4 py-8 text-[#1E293B] sm:py-5 flex flex-col justify-between selection:bg-[#93C5FD] selection:text-[#19325C]">
        
        <!-- Decorative background mesh elements -->
        <div class="fixed inset-0 overflow-hidden pointer-events-none z-0">
            <div class="absolute -top-40 -left-40 w-96 h-96 rounded-full bg-[#9FB7D9]/20 blur-3xl"></div>
            <div class="absolute top-1/3 -right-32 w-80 h-80 rounded-full bg-[#E8B4A6]/15 blur-3xl"></div>
            <div class="absolute -bottom-32 left-1/4 w-96 h-96 rounded-full bg-[#8CA4C8]/15 blur-3xl"></div>
        </div>

        <main class="mx-auto w-full max-w-[440px] relative z-10 flex flex-col">
            <!-- Header -->
            <header class="mb-7 flex items-center justify-between gap-3 px-1">
                <div class="flex items-center gap-3.5">
                    
                    <div
                        class="flex h-12 w-12 shrink-0 items-center
                            justify-center rounded-2xl
                            bg-white shadow-sm"
                    >
                        <img
                            src="../../../../public/fitymi.svg"
                            alt="Imposter Game"
                            class="h-10 w-10 object-contain"
                        />
                    </div>

                    <div>
                        <h1 class="text-[18px] sm:text-[20px] font-extrabold tracking-tight leading-tight text-[#19325C]">
                            Imposter <span class="text-[#E8B4A6] drop-shadow-sm">Game</span>
                        </h1>
                        <p class="text-xs font-medium text-[#617FA8] tracking-wide">The secret word party game</p>
                    </div>
                </div>
                
                <button
                    type="button"
                    aria-label="Game settings"
                    title="Game settings"
                    :disabled="phase === 'revealed'"
                    @click="openSettings"
                    class="game-interactive flex h-12 w-12 shrink-0
                        items-center justify-center rounded-2xl
                        border border-[#9FB7D9]/50 bg-white/90
                        text-xl shadow-sm backdrop-blur-md
                        transition-all duration-200
                        hover:border-[#617FA8]
                        hover:bg-[#E5EFFF]
                        hover:shadow-md
                        active:scale-95
                        disabled:cursor-not-allowed
                        disabled:opacity-40"
                >
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        width="22"
                        height="22"
                        viewBox="0 0 24 24"
                        fill="currentColor"
                        aria-hidden="true"
                    >
                        <path
                            d="M19.14 12.94a7.9 7.9 0 0 0 .05-1.88l2.03-1.58-2-3.46-2.39.96a7.9 7.9 0 0 0-1.63-.94L14.84 3h-4l-.36 3.04a7.9 7.9 0 0 0-1.63.94l-2.39-.96-2 3.46 2.03 1.58a7.9 7.9 0 0 0 0 1.88l-2.03 1.58 2 3.46 2.39-.96a7.9 7.9 0 0 0 1.63.94L10.84 21h4l.36-3.04a7.9 7.9 0 0 0 1.63-.94l2.39.96 2-3.46-2.08-1.58ZM12.84 15.5a3.5 3.5 0 1 1 0-7 3.5 3.5 0 0 1 0 7Z"
                            transform="translate(-0.84)"
                            fill-rule="evenodd"
                        />
                    </svg>
                </button>

            </header>

            <!-- Main Card Container -->
            <section
                class="flex h-[560px] flex-col rounded-[32px]
                    border border-white/80 bg-white/90
                    p-6 shadow-[0_20px_50px_rgba(25,50,92,0.12)]
                    transition-all duration-300
                    sm:h-[580px] sm:p-8"
            >
                <Transition name="screen" mode="out-in">
                
                <!-- Setup Screen -->
                <div v-if="phase === 'setup'" class="flex h-full min-h-0 flex-col text-center">
                    <div class="mx-auto mb-5 flex h-18 w-18 items-center justify-center rounded-3xl bg-[#D9E3F0] text-3xl shadow-inner text-[#19325C]"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M5 3h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2Zm2 3a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm10 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm-5 4.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3ZM7 15a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Zm10 0a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3Z" /></svg></div>
                    <span class="self-center inline-flex items-center justify-center mb-2 px-3 py-1 rounded-full bg-[#D9E3F0]/60 text-[11px] font-bold uppercase tracking-[0.18em] text-[#3D5A80]">New Round</span>
                    <h2 class="text-2xl font-extrabold tracking-tight text-[#19325C]">How many players?</h2>
                    <p class="mt-1.5 text-sm text-[#617FA8] font-medium">Everyone will take turns using one phone.</p>
                    
                    <div class="mt-8 flex items-center justify-between rounded-2xl border border-[#D9E3F0] bg-[#F4F7FB] p-4 shadow-sm">
                        <button type="button" aria-label="Remove player" :disabled="playerCount <= 3" @click="playerCount--"
                            class="game-interactive flex h-12 w-12 items-center justify-center rounded-xl border border-[#B8CCE4]/60 bg-white text-2xl font-bold text-[#19325C] transition hover:bg-[#EAF0F8] disabled:opacity-30 shadow-2xs">−</button>
                        <div>
                            <div class="text-5xl font-black tabular-nums text-[#19325C] tracking-tight">{{ playerCount }}</div>
                            <p class="mt-0.5 text-xs font-semibold text-[#617FA8] uppercase tracking-wider">Players</p>
                        </div>
                        <button type="button" aria-label="Add player" :disabled="playerCount >= 20" @click="playerCount++"
                            class="game-interactive flex h-12 w-12 items-center justify-center rounded-xl border border-[#B8CCE4]/60 bg-white text-2xl font-bold text-[#19325C] transition hover:bg-[#EAF0F8] disabled:opacity-30 shadow-2xs">+</button>
                    </div>

                    <p v-if="Object.keys(errors).length" class="mt-4 text-sm font-medium text-[#D97706]" role="alert">{{ Object.values(errors)[0] }}</p>
                    
                    <button type="button" :disabled="processing || !preferencesLoaded" @click="start"
                        class="game-interactive mt-auto w-full rounded-2xl bg-gradient-to-r from-[#E8B4A6] to-[#D89F91] px-5 py-4 text-base font-bold text-[#19325C] shadow-lg shadow-[#E8B4A6]/30 transition hover:brightness-105 disabled:opacity-50">
                        Start Game <span aria-hidden="true" class="ml-1"></span>
                    </button>
                    
                    <p class="mt-4 text-xs font-medium text-[#617FA8]">To change word options, open Settings above.</p>
                </div>

                <!-- Handoff Screen -->
                <div v-else-if="phase === 'handoff'" class="flex h-full min-h-0 flex-col text-center">
                    <div class="mb-3 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-[#617FA8]">
                        <span>Pass the phone</span>
                        <span class="text-[#19325C] bg-[#D9E3F0] px-2.5 py-1 rounded-full">{{ game.currentPlayer }} / {{ game.playerCount }}</span>
                    </div>
                    <div class="mb-8 h-2.5 overflow-hidden rounded-full bg-[#D9E3F0]/60 p-0.5 shadow-inner">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#617FA8] to-[#3D5A80] transition-[width] duration-500 ease-out" :style="{ width: progress + '%' }"></div>
                    </div>
                    
                    <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-3xl bg-[#D9E3F0] text-4xl shadow-inner"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 2h10a2 2 0 0 1 2 2v16a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2Zm0 3v13h10V5H7Zm5 14a1 1 0 1 0 0 2 1 1 0 0 0 0-2Z" /></svg></div>
                    <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#617FA8]">It's time for</p>
                    <h2 class="mt-1 text-3xl font-extrabold text-[#19325C]">Player {{ game.currentPlayer }}</h2>
                    <p class="mt-4 rounded-2xl bg-[#F4F7FB] border border-[#D9E3F0]/60 px-5 py-4 text-sm leading-relaxed text-[#617FA8] font-medium">Make sure no one else is looking at the screen before revealing your secret.</p>
                    
                    <button type="button" :disabled="processing" @click="post('/game/reveal')"
                        class="game-interactive mt-auto w-full rounded-2xl bg-gradient-to-r from-[#E8B4A6] to-[#D89F91] px-5 py-4 text-base font-bold text-[#19325C] shadow-lg shadow-[#E8B4A6]/30 transition hover:brightness-105 disabled:opacity-50">Reveal My Secret</button>
                </div>

                <!-- Revealed Screen -->
                <div v-else-if="phase === 'revealed'" class="flex h-full min-h-0 flex-col text-center">
                    <div class="mb-3 flex items-center justify-between text-xs font-bold uppercase tracking-wider text-[#617FA8]">
                        <span>Private card</span>
                        <span class="text-[#19325C] bg-[#D9E3F0] px-2.5 py-1 rounded-full">Player {{ game.currentPlayer }} / {{ game.playerCount }}</span>
                    </div>
                    <div class="mb-6 h-2.5 overflow-hidden rounded-full bg-[#D9E3F0]/60 p-0.5 shadow-inner">
                        <div class="h-full rounded-full bg-gradient-to-r from-[#617FA8] to-[#3D5A80]" :style="{ width: progress + '%' }"></div>
                    </div>
                    
                    <div class="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-3xl bg-[#D9E3F0] text-3xl shadow-inner"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M17 9V7a5 5 0 0 0-10 0v2H5a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2h-2ZM9 7a3 3 0 0 1 6 0v2H9V7Zm3 5a2 2 0 0 1 1 3.73V18h-2v-2.27A2 2 0 0 1 12 12Z" /></svg></div>
                    <h2 class="text-2xl font-extrabold text-[#19325C]">Your Secret</h2>
                    <p class="mt-1 text-xs font-medium text-[#617FA8]">Only you should read what's below.</p>
                    
                    <!-- SAME SECRET CARD FOR ALL PLAYERS -->
                    <div
                        class="mt-6 flex h-[230px] shrink-0 flex-col
                            items-center justify-center overflow-y-auto
                            rounded-3xl border border-[#B8CCE4]/60
                            bg-gradient-to-b from-[#F4F7FB] to-[#E5EFFF]
                            px-5 py-6 shadow-sm"
                    >
                        <!-- SAME LABEL FOR EVERYONE -->
                        <span
                            class="mb-4 inline-flex shrink-0 items-center
                                justify-center rounded-full
                                bg-[#D9E3F0] px-3 py-1
                                text-[11px] font-bold uppercase
                                tracking-[0.18em] text-[#3D5A80]"
                        >
                            Private Information
                        </span>

                        <!-- IMPOSTER CONTENT -->
                        <template v-if="game.isImposter">
                            <h3
                                class="text-2xl font-black leading-tight
                                    text-[#19325C]"
                            >
                                You're the Imposter
                            </h3>

                            <p
                                class="mt-4 max-w-xs text-sm font-medium
                                    leading-relaxed text-[#617FA8]"
                            >
                                {{ game.hint }}
                            </p>
                        </template>

                        <!-- NORMAL PLAYER CONTENT -->
                        <template v-else>
                            <h3
                                class="break-words text-2xl font-black
                                    leading-tight text-[#19325C]"
                            >
                                {{ game.word }}
                            </h3>
                        </template>
                    </div>

                    <p class="mt-5 text-xs font-medium text-[#617FA8]">Remember your secret and keep it to yourself.</p>
                    
                    <button type="button" :disabled="processing" @click="post('/game/next')"
                        class="game-interactive mt-auto w-full shrink-0 rounded-2xl bg-gradient-to-r from-[#617FA8] to-[#3D5A80] px-5 py-4 text-base font-bold text-white shadow-lg shadow-[#3D5A80]/20 transition hover:brightness-105 disabled:opacity-50">
                        {{ game.currentPlayer === game.playerCount ? 'Finish Dealing' : 'Hide & Continue' }} <span aria-hidden="true" class="ml-1"></span>
                    </button>
                </div>

                <!-- Ready Screen -->
                <div v-else-if="phase === 'ready'" class="flex h-full min-h-0 flex-col text-center">
                    <div class="mx-auto mb-5 flex h-20 w-20 items-center justify-center rounded-3xl bg-[#D9E3F0] text-4xl shadow-inner"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1.2 14.8-4.3-4.3 1.4-1.4 2.9 2.9 5.9-5.9 1.4 1.4-7.3 7.3Z" /></svg></div>
                    <span class="self-center inline-flex items-center justify-center mb-2 px-3 py-1 rounded-full bg-[#D9E3F0]/60 text-[11px] font-bold uppercase tracking-[0.18em] text-[#3D5A80]">Round Ready</span>
                    <h2 class="text-2xl font-extrabold text-[#19325C]">Everyone's Ready!</h2>
                    <p class="mt-2 text-sm leading-relaxed text-[#617FA8] font-medium">All {{ game.playerCount }} players have received their secrets. Give clues, discuss, and find the imposter together.</p>
                    
                    <div class="mt-6 rounded-2xl bg-[#F4F7FB] border border-[#D9E3F0] p-4 text-xs font-semibold text-[#3D5A80]">Remember: don't say the secret word directly.</div>
                    
                    <p v-if="Object.keys(errors).length" class="mt-4 text-sm font-medium text-[#D97706]" role="alert">{{ Object.values(errors)[0] }}</p>
                    
                    <!-- BOTTOM ACTIONS -->
                    <div class="mt-auto space-y-3 pt-6">
                        <button
                            type="button"
                            :disabled="processing || !preferencesLoaded"
                            @click="nextGame"
                            class="game-interactive w-full rounded-2xl
                                bg-gradient-to-r from-[#617FA8] to-[#3D5A80]
                                px-5 py-4 font-bold text-white
                                transition-all duration-200
                                hover:brightness-110 active:scale-[0.98]
                                disabled:opacity-50"
                        >
                            Next Game
                        </button>

                        <button
                            type="button"
                            :disabled="processing"
                            @click="post('/game/result')"
                            class="game-interactive w-full rounded-2xl
                                border border-[#B8CCE4] bg-white
                                px-5 py-4 font-bold text-[#19325C]
                                transition-all duration-200
                                hover:bg-[#F4F7FB] active:scale-[0.98]"
                        >
                            Reveal Imposter
                        </button>
                    </div>

                </div>

                <!-- Result Screen -->
                <div v-else-if="phase === 'result'" class="flex h-full min-h-0 flex-col text-center">
                    <div class="mx-auto mb-4 flex h-18 w-18 items-center justify-center rounded-3xl bg-[#D9E3F0] text-3xl shadow-inner"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M4 3h16v11a8 8 0 0 1-7 7.94V23h-2v-1.06A8 8 0 0 1 4 14V3Zm3 4v2h3V7H7Zm7 0v2h3V7h-3Zm-6 6a4 4 0 0 0 8 0H8Z" /></svg></div>
                    <span class="self-center inline-flex items-center justify-center mb-2 px-3 py-1 rounded-full bg-[#E8B4A6]/30 text-[11px] font-bold uppercase tracking-[0.18em] text-[#9A5342]">Round Results</span>
                    <h2 class="text-2xl font-extrabold text-[#19325C]">The Imposter Was...</h2>
                    
                    <div class="mt-5 rounded-3xl border border-[#E8B4A6]/60 bg-gradient-to-br from-[#FDF6F4] to-[#FCEBE6] p-5 shadow-sm">
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-[#9A5342]">Imposter</p>
                        <p class="mt-1 text-3xl font-black text-[#19325C]">Player {{ game.result.imposterPlayer }}</p>
                    </div>
                    
                    <div class="mt-3 rounded-2xl border border-[#D9E3F0] bg-white p-4 shadow-2xs">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#617FA8]">Normal players' word</p>
                        <p class="mt-1 break-words text-lg font-bold text-[#19325C]">{{ game.result.civilianWord }}</p>
                    </div>
                    
                    <div class="mt-3 rounded-2xl border border-[#D9E3F0] bg-white p-4 shadow-2xs">
                        <p class="text-xs font-bold uppercase tracking-wider text-[#617FA8]">{{ game.result.imposterMode === 'revealed' ? 'Imposter hint' : 'Imposter word' }}</p>
                        <p class="mt-1 break-words text-sm font-bold leading-relaxed text-[#19325C]">{{ game.result.imposterMode === 'revealed' ? game.result.hint : game.result.imposterWord }}</p>
                    </div>
                    
                    <p v-if="Object.keys(errors).length" class="mt-4 text-sm font-medium text-[#D97706]" role="alert">{{ Object.values(errors)[0] }}</p>
                    
                    <button type="button" :disabled="processing || !preferencesLoaded" @click="nextGame"
                        class="game-interactive mt-6 w-full rounded-2xl bg-gradient-to-r from-[#E8B4A6] to-[#D89F91] px-5 py-4 font-bold text-[#19325C] shadow-lg shadow-[#E8B4A6]/30 transition hover:brightness-105 disabled:opacity-50">Next Game <span aria-hidden="true" class="ml-1"></span></button>
                    
                </div>

                <!-- Invalid Phase Fallback -->
                <div v-else class="py-8 text-center">
                    <h2 class="text-xl font-bold text-[#19325C]">This round needs a reset</h2>
                    <p class="mt-2 text-sm text-[#617FA8] font-medium">Your game state comes from an older version.</p>
                    <button type="button" :disabled="processing" @click="endSession" class="mt-6 rounded-xl bg-[#3D5A80] px-6 py-3 font-bold text-white shadow-md">Return to Start</button>
                </div>
                </Transition>
            </section>

            <!-- END SESSION - AVAILABLE THROUGHOUT THE GAME -->
            <div
                v-if="game"
                class="mt-5 flex justify-center"
            >
                <button
                    type="button"
                    :disabled="processing"
                    @click="confirmEnd = true"
                    class="game-interactive flex w-full items-center
                        justify-center gap-2 rounded-2xl
                        border border-[#D9E3F0] bg-white/90
                        px-5 py-3.5 text-sm font-bold
                        text-[#617FA8] shadow-sm
                        transition-all duration-200
                        hover:border-[#3D5A80]
                        hover:bg-[#E5EFFF]
                        hover:text-[#19325C]
                        active:scale-[0.98]
                        disabled:opacity-50"
                >
                    <span aria-hidden="true" class="inline-flex"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M11 2h2v11h-2V2Zm6.66 3.34 1.42-1.42A10 10 0 1 1 4.92 3.92l1.42 1.42a8 8 0 1 0 11.32 0Z" /></svg></span>
                    End Session
                </button>
            </div>

            <footer class="mt-6 text-center text-xs font-semibold text-[#617FA8] tracking-wider">
                Imposter Game · Pass & Play
            </footer>
        </main>

        <!-- Settings Modal -->
        <Transition name="overlay">
            <div v-if="settingsOpen" class="fixed inset-0 z-50 flex items-end justify-center bg-[#19325C]/40 backdrop-blur-sm p-3 sm:items-center sm:p-5" @click.self="settingsOpen = false">
                <section role="dialog" aria-modal="true" aria-label="Game settings" class="flex max-h-[90dvh] w-full max-w-[440px] flex-col overflow-hidden rounded-[32px] bg-white text-[#19325C] shadow-2xl border border-white">
                    <header class="flex items-center justify-between border-b border-[#D9E3F0] p-5 bg-[#F4F7FB]/50">
                        <div>
                            <h2 class="text-lg font-extrabold text-[#19325C]">Game Settings</h2>
                            <p class="mt-0.5 text-xs font-medium text-[#617FA8]">Choose how you want to play</p>
                        </div>
                        <button type="button" aria-label="Close settings" @click="settingsOpen = false" class="game-interactive flex h-10 w-10 items-center justify-center rounded-xl bg-[#D9E3F0]/60 text-xl font-bold text-[#19325C] hover:bg-[#D9E3F0]">×</button>
                    </header>
                    
                    <div class="space-y-6 overflow-y-auto p-5">
                        <fieldset>
                            <legend class="mb-2.5 text-xs font-bold uppercase tracking-wider text-[#617FA8]">Word language</legend>
                            <div class="grid grid-cols-3 gap-2">
                                <button v-for="item in [{ value:'both', label:'Both' }, { value:'en', label:'English' }, { value:'ms', label:'BM' }]" :key="item.value" type="button"
                                    :aria-pressed="language === item.value" @click="language = item.value"
                                    class="game-interactive rounded-xl border px-2 py-3 text-sm font-bold transition shadow-2xs"
                                    :class="language === item.value ? 'border-[#3D5A80] bg-[#D9E3F0] text-[#19325C] ring-2 ring-[#3D5A80]/20' : 'border-[#D9E3F0] bg-white text-[#617FA8] hover:bg-[#F4F7FB]'">{{ item.label }}</button>
                            </div>
                        </fieldset>

                        <div>
                            <label for="category-select" class="mb-2.5 block text-xs font-bold uppercase tracking-wider text-[#617FA8]">Word category</label>
                            <select id="category-select" v-model="category" class="w-full rounded-xl border border-[#D9E3F0] bg-[#F4F7FB] p-3 text-sm font-semibold text-[#19325C] focus:border-[#3D5A80] focus:outline-none focus:ring-2 focus:ring-[#3D5A80]/20 transition">
                                <option value="all">All categories</option>
                                <option v-for="item in categories" :key="item" :value="item">{{ item }}</option>
                            </select>
                        </div>

                        <fieldset>
                            <legend class="mb-2.5 text-xs font-bold uppercase tracking-wider text-[#617FA8]">Imposter mode</legend>
                            <div class="space-y-2.5">
                                <button v-for="mode in gameModes" :key="mode.value" type="button" :aria-pressed="imposterMode === mode.value"
                                    @click="imposterMode = mode.value"
                                    class="game-interactive flex w-full items-start gap-3 rounded-2xl border p-3.5 text-left transition shadow-2xs"
                                    :class="imposterMode === mode.value ? 'border-[#3D5A80] bg-[#D9E3F0]/60 ring-2 ring-[#3D5A80]/20' : 'border-[#D9E3F0] bg-white hover:bg-[#F4F7FB]'">
                                    <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition" :class="imposterMode === mode.value ? 'border-[#3D5A80] bg-[#3D5A80]' : 'border-[#B8CCE4]'">
                                        <span v-if="imposterMode === mode.value" class="h-2 w-2 rounded-full bg-white"></span>
                                    </span>
                                    <span class="flex-1"><span class="block text-sm font-bold text-[#19325C]">{{ mode.name }}</span><span class="mt-0.5 block text-xs leading-4 text-[#617FA8] font-medium">{{ mode.description }}</span></span>
                                </button>
                            </div>
                        </fieldset>

                        <p v-if="game" class="rounded-xl bg-[#F4F7FB] border border-[#D9E3F0] p-3 text-xs leading-relaxed text-[#617FA8] font-medium"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2a10 10 0 1 0 0 20 10 10 0 0 0 0-20Zm-1 4h2v2h-2V6Zm0 4h2v8h-2v-8Z" /></svg> Setting changes will apply to the next round. They won't affect secrets already distributed.</p>
                    </div>

                    <div class="border-t border-[#D9E3F0] bg-[#F4F7FB]/50 p-5">
                        <button type="button" @click="settingsOpen = false" class="game-interactive w-full rounded-2xl bg-[#3D5A80] py-3.5 font-bold text-white shadow-md hover:bg-[#19325C] transition">Done</button>
                    </div>
                </section>
            </div>
        </Transition>

        <!-- Confirmation Modal -->
        <Transition name="overlay">
            <div v-if="confirmEnd" class="fixed inset-0 z-[60] flex items-center justify-center bg-[#19325C]/40 backdrop-blur-sm p-4" @click.self="confirmEnd = false">
                <section role="alertdialog" aria-modal="true" aria-label="End session confirmation" class="w-full max-w-sm rounded-[32px] bg-white p-6 text-[#19325C] shadow-2xl border border-white text-center">
                    <div class="mx-auto mb-3 flex h-14 w-14 items-center justify-center rounded-2xl bg-[#E8B4A6]/30 text-2xl"><svg xmlns="http://www.w3.org/2000/svg" width="30" height="30" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 1 21h22L12 2Zm-1 7h2v6h-2V9Zm0 8h2v2h-2v-2Z" /></svg></div>
                    <h2 class="text-xl font-extrabold text-[#19325C]">End this session?</h2>
                    <p class="mt-2 text-sm leading-relaxed text-[#617FA8] font-medium">This will end the current game and return to the player selection screen.</p>
                    <div class="mt-6 grid grid-cols-2 gap-3">
                        <button type="button" @click="confirmEnd = false" class="game-interactive rounded-2xl border border-[#D9E3F0] bg-white py-3 text-sm font-bold text-[#617FA8] hover:bg-[#F4F7FB] transition">Cancel</button>
                        <button type="button" :disabled="processing" @click="endSession" class="game-interactive rounded-2xl bg-[#19325C] py-3 text-sm font-bold text-white shadow-md hover:bg-[#2b4c7e] transition disabled:opacity-50">End Session</button>
                    </div>
                </section>
            </div>
        </Transition>
    </div>
</template>