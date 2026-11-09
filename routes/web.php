<?php

use App\Http\Controllers\GameController;
use Illuminate\Support\Facades\Route;

Route::get('/', [GameController::class, 'index'])->name('game.index');

Route::post('/game/start', [GameController::class, 'start'])->name('game.start');

Route::post('/game/reveal', [GameController::class, 'reveal'])->name('game.reveal');

Route::post('/game/next', [GameController::class, 'next'])->name('game.next');

Route::post('/game/result', [GameController::class, 'showResult'])->name('game.result');

Route::post('/game/next-game', [GameController::class, 'nextGame'])->name('game.next-game');

Route::post('/game/end-session', [GameController::class, 'endSession'])->name('game.end-session');
