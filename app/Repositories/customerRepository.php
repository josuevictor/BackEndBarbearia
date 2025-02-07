<?php


namespace app\Repositories;

use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    public function getCustomers()
    {
        $result = DB::select('select * from agendamento.clientes');
        return response()->json($result);
    }
}
