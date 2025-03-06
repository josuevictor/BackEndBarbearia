<?php


namespace App\Repositories\customers;

use Illuminate\Support\Facades\DB;

class CustomerRepository
{
    public function getCustomers()
    {
        $result = DB::select("select c.nome,
                                           c.sobrenome,
                                           c.cpf,
                                           c.email,
                                           c.telefone
                                      from agendamento.clientes c");
        return response()->json($result);
    }

    public function getWeekAppointments()
    {
        $result = DB::select("SELECT  c.nome AS cliente,
                                            TO_CHAR(a.data_hora, 'DD/MM/YYYY HH24:MI') AS horario,
                                            s.ds_servico AS servico,
                                            f.nome AS barbeiro,
                                            CASE
                                                WHEN a.status_agendamento = 'A' THEN 'AGENDADO'
                                                WHEN a.status_agendamento = 'C' THEN 'CANCELADO'
                                                ELSE '--'
                                            END AS status
                                        FROM agendamento.agendamentos a
                                        JOIN agendamento.clientes c ON c.cliente_id = a.agendamento_cliente_id
                                        JOIN agendamento.servicos s ON a.agendamento_servico_id = s.servico_id
                                        JOIN agendamento.funcionarios f ON f.funcionario_id = a.agendamento_funcionario_id
                                        WHERE a.data_hora >= date_trunc('week', CURRENT_DATE) -- Início da semana atual
                                          AND a.data_hora < date_trunc('week', CURRENT_DATE) + interval '1 week' -- Fim da semana atual
                                        ORDER BY a.data_hora DESC
                                        ");
        return response()->json($result);
    }

}
