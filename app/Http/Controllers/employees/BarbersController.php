<?php

namespace App\Http\Controllers\employees;

use App\Http\Controllers\Controller;
use App\Repositories\barbersRepository;
use Illuminate\Http\Request;

class BarbersController extends Controller
{
    //recupera os barbeiros cadastrados na base
    public function getBarbers()
    {
        try
        {
            $customer = new barbersRepository();
            $result = $customer->getBarbers();

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
}
