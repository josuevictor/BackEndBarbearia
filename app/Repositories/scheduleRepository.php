<?php


namespace App\Repositories;

use Illuminate\Support\Facades\DB;

class scheduleRepository{
    public function getSchedule(){
        $result = DB::select("
                                    SELECT
                                        c.cliente_id AS cliente_id,
                                        c.nome AS cliente,
                                        c.telefone AS telefone,
                                        TO_CHAR(a.data_hora, 'DD/MM/YYYY HH24:MI') AS horario,
                                        s.ds_servico AS servico,
                                        f.nome AS barbeiro,
                                        CASE
                                            WHEN a.status_agendamento = 1 THEN 'AGENDADO'
                                            WHEN a.status_agendamento = 2 THEN 'CANCELADO'
                                            ELSE '--'
                                        END AS status
                                    FROM agendamento.agendamentos a
                                    JOIN agendamento.clientes c ON c.cliente_id = a.agendamento_cliente_id
                                    JOIN agendamento.servicos s ON a.agendamento_servico_id = s.servico_id
                                    JOIN agendamento.funcionarios f ON f.funcionario_id = a.agendamento_funcionario_id
                                    ORDER BY a.data_hora DESC
                                ");

        return response()->json($result);
    }
}
