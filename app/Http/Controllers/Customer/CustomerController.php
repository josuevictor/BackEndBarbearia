<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Repositories\customerRepository;
use App\Repositories\scheduleRepository;
use Illuminate\Support\Facades\Hash;


class CustomerController extends Controller
{

    //recupera os clientes cadastrados na base
    public function getCustomers()
    {
        try
        {
            $customer = new CustomerRepository();
            $result = $customer->getCustomers();

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'msg' => 'Nenhum registro localizado.',
                    'data' => date('d-m-Y H:i:s'),
                ],  404);
            }else return response()->json([
                'status' => 200,
                'success' => true,
                'msg' => 'Dados retornados com secesso!',
                'object' => $result,
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json([
                'status' => 500,
                'error_code' =>  $e->getCode(),
                'success' => false,
                'msg' => 'Houve um erro na requisicao!',
                'object' => 'null',
            ], 500);
        }
    }

    public function getSchedule(){

        try
        {
            $schedule = new scheduleRepository();
            $result = $schedule->getSchedule();

            if (empty($result)) {
                return response()->json([
                    'status' => 404,
                    'success' => false,
                    'msg' => 'Nenhum registro localizado.',
                    'data' => date('d-m-Y H:i:s'),
                ],  404);
            }else return response()->json([
                'status' => 200,
                'success' => true,
                'msg' => 'Dados retornados com secesso!',
                'object' => $result,
            ], 200);
        }
        catch (\Exception $e)
        {
            return response()->json([
                'status' => 500,
                'error_code' =>  $e->getCode(),
                'success' => false,
                'msg' => 'Houve um erro na requisicao!',
                'object' => 'null',
            ], 500);
        }

    }

    //cadastra os clientes na base de dados
    public function store(Request $request)
    {
        DB::beginTransaction();

        $nome      = $request->input('nome');
        $sobrenome = $request->input('sobrenome');
        $cpf       = $request->input('cpf');
        $email     = $request->input('email');
        $telefone  = $request->input('telefone');
        $senha     = Hash::make($request->input('senha'));

        try {
            $customer = new Cliente();

            $customer->nome = $nome;
            $customer->sobrenome = $sobrenome;
            $customer->cpf = $cpf;
            $customer->email = $email;
            $customer->telefone = $telefone;
            $customer->senha = $senha;

            $customer->saveOrFail();
            DB::commit();

            return response()->json([
                'status' => 201,
                'success' => true,
                'msg' => 'Cadastro realizado com sucesso!',
            ], 201);

        }catch(\Exception $e){
            DB::rollBack();

             return response()->json([
                'status' => 409,
                'success' => false,
                'msg' => 'Erro ao realizar cadastro!' . $e->getMessage(),
            ], 409);
        }
    }



}
