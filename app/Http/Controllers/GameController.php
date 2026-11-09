<?php

namespace App\Http\Controllers;

use App\Models\WordPair;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class GameController extends Controller
{
    public function index(Request $request): Response
    {
        $game = $request->session()->get('imposter_game');

        // Clear game sessions from older application versions.
        $validPhases = [
            'handoff',
            'revealed',
            'ready',
            'result',
        ];

        if ($game && !in_array(
            $game['phase'] ?? null,
            $validPhases,
            true
        )) {
            $request->session()->forget('imposter_game');
            $game = null;
        }

        $categories = WordPair::query()
            ->where('is_active', true)
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        $publicGame = null;

        if ($game) {
            $publicGame = [
                'phase' => $game['phase'],
                'playerCount' => $game['player_count'],
                'currentPlayer' => $game['current_player'] + 1,
                'category' => $game['category'],
                'language' => $game['language'],
                'imposterMode' => $game['imposter_mode'] ?? 'hidden',
            ];

            if ($game['phase'] === 'revealed') {
                $isImposter =
                    $game['current_player'] ===
                    $game['imposter_index'];

                $mode = $game['imposter_mode'] ?? 'hidden';

                if ($isImposter && $mode === 'revealed') {
                    $publicGame['isImposter'] = true;
                    $publicGame['hint'] = $game['imposter_hint'];
                } else {
                    $publicGame['isImposter'] = false;

                    $publicGame['word'] = $isImposter
                        ? $game['imposter_word']
                        : $game['civilian_word'];
                }
            }

            if ($game['phase'] === 'result') {
                $publicGame['result'] = [
                    'imposterPlayer' =>
                        $game['imposter_index'] + 1,

                    'civilianWord' =>
                        $game['civilian_word'],

                    'imposterWord' =>
                        $game['imposter_word'],

                    'imposterMode' =>
                        $game['imposter_mode'] ?? 'hidden',

                    'hint' =>
                        $game['imposter_hint'] ?? null,
                ];
            }
        }

        return Inertia::render('Game/Index', [
            'game' => $publicGame,
            'categories' => $categories,
        ]);
    }

    /**
     * Validate game settings and start a new round.
     */
    private function createGame(
        Request $request,
        int $playerCount,
        array $settings
    ): RedirectResponse {
        $query = WordPair::query()
            ->where('is_active', true);

        if ($settings['language'] !== 'both') {
            $query->where('language', $settings['language']);
        }

        if ($settings['category'] !== 'all') {
            $query->where('category', $settings['category']);
        }

        if ($settings['imposter_mode'] === 'revealed') {
            $query->whereNotNull('imposter_hint')
                ->where('imposter_hint', '<>', '');
        }

        $pair = $query->inRandomOrder()->first();

        if (!$pair) {
            throw ValidationException::withMessages([
                'category' =>
                    'No words are available for these settings.',
            ]);
        }

        $request->session()->put('imposter_game', [
            'phase' => 'handoff',
            'player_count' => $playerCount,
            'current_player' => 0,

            'imposter_index' =>
                random_int(0, $playerCount - 1),

            'civilian_word' => $pair->civilian_word,
            'imposter_word' => $pair->imposter_word,
            'imposter_hint' => $pair->imposter_hint,

            'language' => $pair->language,
            'category' => $pair->category,
            'imposter_mode' => $settings['imposter_mode'],
        ]);

        return to_route('game.index');
    }

    /**
     * Start a game from the main screen.
     */
    public function start(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'player_count' => [
                'required', 'integer', 'min:3', 'max:20',
            ],
            'language' => [
                'required', 'in:en,ms,both',
            ],
            'category' => [
                'required', 'string', 'max:50',
            ],
            'imposter_mode' => [
                'required', 'in:hidden,revealed',
            ],
        ]);

        return $this->createGame(
            $request,
            (int) $data['player_count'],
            $data
        );
    }

    /**
     * Reveal current player's information.
     */
    public function reveal(Request $request): RedirectResponse
    {
        $game = $request->session()->get('imposter_game');

        if (!$game || $game['phase'] !== 'handoff') {
            return to_route('game.index');
        }

        $game['phase'] = 'revealed';

        $request->session()->put('imposter_game', $game);

        return to_route('game.index');
    }

    /**
     * Hide information and pass to the next player.
     */
    public function next(Request $request): RedirectResponse
    {
        $game = $request->session()->get('imposter_game');

        if (!$game || $game['phase'] !== 'revealed') {
            return to_route('game.index');
        }

        if (
            $game['current_player'] + 1 >=
            $game['player_count']
        ) {
            $game['phase'] = 'ready';
        } else {
            $game['current_player']++;
            $game['phase'] = 'handoff';
        }

        $request->session()->put('imposter_game', $game);

        return to_route('game.index');
    }

    /**
     * Optional answer reveal.
     */
    public function showResult(Request $request): RedirectResponse
    {
        $game = $request->session()->get('imposter_game');

        if (!$game || $game['phase'] !== 'ready') {
            return to_route('game.index');
        }

        $game['phase'] = 'result';

        $request->session()->put('imposter_game', $game);

        return to_route('game.index');
    }

    /**
     * Start another round with the same player count.
     * Latest saved settings are accepted from Vue.
     */
    public function nextGame(Request $request): RedirectResponse
    {
        $game = $request->session()->get('imposter_game');

        if (
            !$game ||
            !in_array($game['phase'], ['ready', 'result'], true)
        ) {
            return to_route('game.index');
        }

        $settings = $request->validate([
            'language' => [
                'required', 'in:en,ms,both',
            ],
            'category' => [
                'required', 'string', 'max:50',
            ],
            'imposter_mode' => [
                'required', 'in:hidden,revealed',
            ],
        ]);

        return $this->createGame(
            $request,
            (int) $game['player_count'],
            $settings
        );
    }

    /**
     * End session and return to main setup.
     */
    public function endSession(Request $request): RedirectResponse
    {
        $request->session()->forget('imposter_game');

        return to_route('game.index');
    }
}
