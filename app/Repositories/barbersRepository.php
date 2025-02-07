<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class barbersRepository
{
    public function getBarbers()
    {
        $result = DB::select('select *
                                      from agendamento.funcionarios f
                                     where f.funcionario_cargo_id = 1 ');
        return response()->json($result);
    }
}
