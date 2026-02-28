<?php

//use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CampeonatoController;
use App\Http\Controllers\GraderiaController;
use App\Http\Controllers\AsientoController;

Route::post('/login', [App\Http\Controllers\UserController::class, 'login']);
Route::post('/register', [App\Http\Controllers\UserController::class, 'register']);
Route::get('/public/graderias/{code}', [GraderiaController::class, 'publicShowByCode']);
Route::get('/public/campeonatos/{code}', [CampeonatoController::class, 'publicShowByCode']);
Route::get('/public/deportes', [CampeonatoController::class, 'deportes']);
Route::get('/public/campeonatos/{code}/mensajes', [CampeonatoController::class, 'publicMensajes']);
Route::post('/public/campeonatos/{code}/mensajes', [CampeonatoController::class, 'publicMensajeStore']);
Route::get('/public/campeonatos/{code}/clasificacion', [CampeonatoController::class, 'clasificacionPublic']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/me/password', [App\Http\Controllers\UserController::class, 'changeMyPassword']);
    Route::post('/me/password/update', [App\Http\Controllers\UserController::class, 'changeMyPasswordDialog']);

    // Admin resetea contraseña de otro usuario
    Route::post('/users/{user}/password-reset', [App\Http\Controllers\UserController::class, 'adminResetPassword']);

    Route::get('/me', [App\Http\Controllers\UserController::class, 'me']);
    Route::post('/logout', [App\Http\Controllers\UserController::class, 'logout']);

    Route::get('/users', [App\Http\Controllers\UserController::class, 'index']);
    Route::post('/users', [App\Http\Controllers\UserController::class, 'store']);
    Route::put('/users/{user}', [App\Http\Controllers\UserController::class, 'update']);
    Route::delete('/users/{user}', [App\Http\Controllers\UserController::class, 'destroy']);

    Route::put('/updatePassword/{user}', [App\Http\Controllers\UserController::class, 'updatePassword']);
    Route::post('/{user}/avatar', [App\Http\Controllers\UserController::class, 'updateAvatar']);

    Route::get('/permissions', [App\Http\Controllers\UserController::class, 'permissions']);
    Route::get('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'userPermissions']);
    Route::put('/users/{user}/permissions', [App\Http\Controllers\UserController::class, 'updateUserPermissions']);

    Route::get('/deportes', [CampeonatoController::class, 'deportes']);
    Route::get('/campeonatos', [CampeonatoController::class, 'index']);
    Route::post('/campeonatos', [CampeonatoController::class, 'store']);
    Route::put('/campeonatos/{campeonato}', [CampeonatoController::class, 'update']);
    Route::delete('/campeonatos/{campeonato}', [CampeonatoController::class, 'destroy']);
    Route::get('/campeonatos/{campeonato}/categorias', [CampeonatoController::class, 'categorias']);
    Route::post('/campeonatos/{campeonato}/categorias', [CampeonatoController::class, 'categoriaStore']);
    Route::put('/campeonatos/{campeonato}/categorias/{categoria}', [CampeonatoController::class, 'categoriaUpdate']);
    Route::delete('/campeonatos/{campeonato}/categorias/{categoria}', [CampeonatoController::class, 'categoriaDestroy']);

    Route::get('/campeonatos/{campeonato}/grupos', [CampeonatoController::class, 'gruposIndex']);
    Route::post('/campeonatos/{campeonato}/grupos', [CampeonatoController::class, 'gruposStore']);
    Route::post('/campeonatos/{campeonato}/grupos/defaults', [CampeonatoController::class, 'gruposDefaultsStore']);
    Route::put('/campeonatos/{campeonato}/grupos/{grupo}', [CampeonatoController::class, 'gruposUpdate']);
    Route::delete('/campeonatos/{campeonato}/grupos/{grupo}', [CampeonatoController::class, 'gruposDestroy']);

    Route::get('/campeonatos/{campeonato}/equipos', [CampeonatoController::class, 'equiposIndex']);
    Route::post('/campeonatos/{campeonato}/equipos', [CampeonatoController::class, 'equiposStore']);
    Route::put('/campeonatos/{campeonato}/equipos/{equipo}', [CampeonatoController::class, 'equiposUpdate']);
    Route::delete('/campeonatos/{campeonato}/equipos/{equipo}', [CampeonatoController::class, 'equiposDestroy']);
    Route::post('/campeonatos/{campeonato}/equipos/{equipo}/jugadores', [CampeonatoController::class, 'jugadoresStore']);
    Route::put('/campeonatos/{campeonato}/equipos/{equipo}/jugadores/{jugador}', [CampeonatoController::class, 'jugadoresUpdate']);
    Route::delete('/campeonatos/{campeonato}/equipos/{equipo}/jugadores/{jugador}', [CampeonatoController::class, 'jugadoresDestroy']);

    Route::get('/campeonatos/{campeonato}/fases', [CampeonatoController::class, 'fasesIndex']);
    Route::post('/campeonatos/{campeonato}/fases', [CampeonatoController::class, 'fasesStore']);
    Route::put('/campeonatos/{campeonato}/fases/{fase}', [CampeonatoController::class, 'fasesUpdate']);
    Route::delete('/campeonatos/{campeonato}/fases/{fase}', [CampeonatoController::class, 'fasesDestroy']);
    Route::post('/campeonatos/{campeonato}/fases/{fase}/fechas', [CampeonatoController::class, 'fechasStore']);
    Route::post('/campeonatos/{campeonato}/fases/{fase}/generar-partidos', [CampeonatoController::class, 'partidosGenerar']);
    Route::post('/campeonatos/{campeonato}/fases/{fase}/partidos', [CampeonatoController::class, 'partidosStore']);
    Route::put('/campeonatos/{campeonato}/partidos/{partido}', [CampeonatoController::class, 'partidosUpdate']);
    Route::delete('/campeonatos/{campeonato}/partidos/{partido}', [CampeonatoController::class, 'partidosDestroy']);

    Route::patch('/campeonatos/{campeonato}/mensajes/{mensaje}/visible', [CampeonatoController::class, 'mensajeToggleVisible']);

    // Mis graderías (usuario logueado)
//    Route::get('mis-graderias', [GraderiaController::class, 'index']);
//    Route::post('mis-graderias', [GraderiaController::class, 'store']);
//    Route::get('mis-graderias/{graderia}', [GraderiaController::class, 'show']);
//    Route::put('mis-graderias/{graderia}', [GraderiaController::class, 'update']);
//    Route::delete('mis-graderias/{graderia}', [GraderiaController::class, 'destroy']);

    // Asientos (si quieres verlos por gradería)
    Route::get('mis-graderias', [GraderiaController::class, 'index']);
    Route::get('mis-graderias/{graderia}', [GraderiaController::class, 'show']);
    Route::post('mis-graderias', [GraderiaController::class, 'store']);
    Route::put('mis-graderias/{graderia}', [GraderiaController::class, 'update']);
    Route::delete('mis-graderias/{graderia}', [GraderiaController::class, 'destroy']);

    // ✅ BULK UPDATE asientos
    Route::get('mis-graderias/{graderia}/venta', [GraderiaController::class, 'venta']);
    Route::post('mis-graderias/{graderia}/asientos/bulk', [AsientoController::class, 'bulkUpdate']);

    Route::post('/mis-graderias/{graderia}/repair', [GraderiaController::class, 'repair']);

    Route::patch('mis-graderias/{graderia}/asientos/{asiento}', [AsientoController::class, 'updateOne']);
//    Route::post('/api/whatsapp/boletos', [WhatsAppController::class, 'sendTickets']);


});
