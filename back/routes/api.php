<?php

use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::post('/login', [UserController::class, 'login']);
Route::post('/register', [UserController::class, 'register']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [UserController::class, 'logout']);
    Route::get('/me', [UserController::class, 'me']);
    Route::get('/deportes', [CampeonatoController::class, 'deportes']);
    Route::apiResource('campeonatos', CampeonatoController::class);
    Route::get('/campeonatos/{campeonato}/categorias', [CampeonatoController::class, 'categorias']);
    Route::post('/campeonatos/{campeonato}/categorias', [CampeonatoController::class, 'storeCategoria']);
    Route::put('/categorias-campeonato/{categoria}', [CampeonatoController::class, 'updateCategoria']);
    Route::delete('/categorias-campeonato/{categoria}', [CampeonatoController::class, 'destroyCategoria']);

    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
    Route::post('/users', [UserController::class, 'store']);
    Route::put('/users/{user}', [UserController::class, 'update']);
    Route::delete('/users/{user}', [UserController::class, 'destroy']);
});
