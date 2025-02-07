<?php


namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class scheduleRepository{
    public function getSchedule(){
        $result = DB::select('SELECT c.nome cliente,
                                           DATE_FORMAT(a.data_hora, "%d/%m/%Y %H:%i") horario,
                                           s.ds_servico servico,
                                           f.nome barbeiro,
                                           CASE
                                            WHEN a.status_agendamento = "A" THEN "AGENDADO"
                                            WHEN a.status_agendamento = "C" THEN "CANCELADO"
                                           ELSE "--"
                                    END AS status
                                      FROM agendamento.agendamentos a,
                                           agendamento.clientes c,
                                           agendamento.servicos s,
                                           agendamento.funcionarios f
                                     WHERE c.cliente_id = a.agendamento_cliente_id
                                       AND a.agendamento_servico_id = s.servico_id
                                       AND f.funcionario_id = a.agendamento_funcionario_id
                                       ORDER BY a.data_hora DESC');

        return response()->json($result);
    }
}
