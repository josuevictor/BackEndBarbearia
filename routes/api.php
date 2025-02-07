<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\scheduling\scheduleServiceController;
use App\Http\Controllers\employees\BarbersController;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Rota de Login

Route::post('/login', function (Request $request) {
    // Validação dos dados de entrada
    $request->validate([
        'email' => 'required|email',
        'senha' => 'required',
    ]);



    // Busca o usuário pelo email
    $user = User::where('email', $request->email)->first();


    // Verifica se o usuário existe e se a senha está correta
    if (!$user || !password_verify($request->senha, $user->senha)) {
        return response()->json(['error' => 'Credenciais inválidas'], 401);
    }




    // Gera o token JWT
    $token = JWTAuth::fromUser($user);

    // Autenticação bem-sucedida
    return response()->json([
        'message' => 'Login realizado com sucesso!',
        'token' => $token,
        'cliente_id' => $user->cliente_id,
    ]);
});

//----------------------------------------------------------------------------------------------------------------------

//Clientes
Route::get('/clientes', [CustomerController::class, 'getCustomers']);
Route::get('/agendamento', [CustomerController::class, 'getSchedule']);
Route::post('/CadastrarCliente', [CustomerController::class, 'store']);

//----------------------------------------------------------------------------------------------------------------------

//Agendamentos
Route::post('/agendar', [scheduleServiceController::class, 'scheduleService']);
Route::patch('/cancelarAgendamento', [scheduleServiceController::class, 'removeAppointment']);

//----------------------------------------------------------------------------------------------------------------------

//Profissionais
Route::get('/barbeiro', [BarbersController::class, 'getBarbers']);

