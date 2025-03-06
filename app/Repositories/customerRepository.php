<?php


namespace app\Repositories;

use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    public function getCustomers()
    {
        $result = DB::select('select c.nome,
                                           c.sobrenome,
                                           c.cpf,
                                           c.email,
                                           c.telefone
                                      from agendamento.clientes c');
        return response()->json($result);
    }
}
