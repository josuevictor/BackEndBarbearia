<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Customer\CustomerController;
use App\Http\Controllers\scheduling\scheduleServiceController;
use App\Http\Controllers\employees\BarbersController;
use App\Http\Controllers\Payment\PaymentController;
use Tymon\JWTAuth\Facades\JWTAuth;
use App\Models\User;
use App\Models\gestorBarbearia\Barbearia;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

//Rota de Login cliente

Route::post('/login', function (Request $request) {

    try {

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
    }catch (\Exception $e){
        return response()->json([$e->getMessage()]);
    }
});

//----------------------------------------------------------------------------------------------------------------------

//Rota de Login barbearia

Route::post('/loginBarbearia', function (Request $request) {
    // Validação dos dados de entrada
    $request->validate([
        'email' => 'required|email',
        'senha' => 'required',
    ]);

    // Busca o usuário pelo email
    $barbearia = Barbearia::where('email', $request->email)->first();

    // Verifica se o usuário existe e se a senha está correta
    if (!$barbearia || !password_verify($request->senha, $barbearia->senha)) {
        return response()->json(['error' => 'Credenciais inválidas'], 401);
    }

    // Gera o token JWT
    $token = JWTAuth::fromUser($barbearia);

    // Autenticação bem-sucedida
    return response()->json([
        'message' => 'Login realizado com sucesso!',
        'token' => $token,
        'cliente_id' => $barbearia->id,
        'status' => $barbearia->status,
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

//Rota de pagamento
Route::post('/pagamento/pix', [PaymentController::class, 'criarPagamentoPix']);
Route::get('/pagamento/verificarPagamentos', [PaymentController::class, 'verificarPagamentos']);

