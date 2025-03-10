<?php

namespace App\Http\Controllers\scheduling;

use App\Http\Controllers\Controller;
use App\Models\Agendamento\Agendamento;
use http\Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use function PHPUnit\TestFixture\func;

class scheduleServiceController extends Controller
{
    public function addAppointment()
    {

        $data = DB::select('SELECT * FROM agendamento.agendamentos');

        return response()->json($data);

    }

    //função responsável pelo agendamento dos servicos.
    public function scheduleService(Request $request)
    {
        try
        {
            /*
                1)
                ANTES DE REALIZAR O AGENDAMENTO, DEVERÁ SER VERIFICADO SE JÁ
                EXISTE ALGUEM NA FILA COM A HORA E BARBEIRO RESERVADOS, PARA EVITAR
                CONFLITOS DE HORARIOS E PROFISSIONAIS ESCOLHIDOS.

                2)
                SE O BARBEIRO ESTIVER NA TABELA DE AGENDAMENTO E COM O STATUS DE AGENDAMENTO CONFIRMADO,
                ELE FICA BLOQUEADO PARA SER REQUSITADO NAQUELE MESMO HORÁRIO, ATÉ QUE O STATUS DELE MUDE
                PARA CONCLUÍDO.

                RODAR UM SELECT NA TABERLA DE AGENDAMENTO, E VERIFICAR SE O HORARIO E DIA ENVIADO DO FRONT
                JÁ ESTÁ SETADO.

                VERIFICAR TBM SE O CLIENTE ESTÁ FAZENDO MAIS DE 1 AGENDAMENTO NO DIA.

            */

            date_default_timezone_set('America/Sao_Paulo');
            $date       = date("Y-m-d");

            $servico     = $request->input('servico');
            $cliente_id  = $request->input('cliente_id');
            $cpf_cliente = $request->input('cpf_cliente');
            $dataHora    = $request->input('data_hora');
            $barbeiro    = $request->input('barbeiro');

            if($this->checkAvailability($barbeiro, $dataHora, $cliente_id))
            {
                return response()->json(['response'=>'error',
                                        'error_code'=>409,
                                        'message'=>'Barbeiro indisponível neste horário']);
            }

            DB::beginTransaction();

            $agendamento = new agendamento();
            $agendamento->agendamento_servico_id     = $servico;
            $agendamento->agendamento_cliente_id     = $cliente_id;
            $agendamento->cpf                        = $cpf_cliente;
            $agendamento->data_hora                  = $dataHora;
            $agendamento->agendamento_funcionario_id = $barbeiro;
            $agendamento->status_agendamento         = 'A';
            $agendamento->saveOrFail();

            DB::commit();

            return response()->json(['response'=>'success', 'status_code'=>200], 200);
        }
        catch (\Exception $e)
        {
            DB::rollBack();
            return response()->json(['response'=>'error', 'error_code'=>$e->getCode()], 500);
        }

    }

    //Verifica o conflito de horários e barbeiros
    private function checkAvailability($barbeiro, $dataHora)
    {
        return agendamento::where('agendamento_funcionario_id', $barbeiro)
            ->where('data_hora', $dataHora)
            ->where('status_agendamento', 'A')
            ->exists();
    }

    //funcao responsalvel por alterar o status de um agendamento
    public function removeAppointment(Request $request)
    {
        $cliente_id = $request->input('cliente_id');
        $dataHora = $request->input('data_hora');

        try {
            // Verificar se o agendamento existe
            $agendamento = Agendamento::where('agendamento_cliente_id', $cliente_id)
                ->where(DB::raw('DATE(data_hora)'), $dataHora)
                ->first();

            if (!$agendamento) {
                return response()->json(['response' => 'Agendamento não encontrado', 'status_code' => 404], 404);
            }

            // Atualiza o status do agendamento para cancelado
            DB::table('agendamento.agendamentos')
                ->where('agendamento_cliente_id', $cliente_id)
                ->where(DB::raw('DATE(data_hora)'), $dataHora)
                ->update(['status_agendamento' => 2]);

            return response()->json(['response' => 'Agendamento cancelado', 'status_code' => 200], 200);
        } catch (\Exception $e) {
            //dd($agendamento);
            return response()->json(['response' => 'Erro ao cancelar agendamento', 'error_code' => $e->getCode()], 500);
        }
    }

    public function horariosDisponiveis(Request $request)
    {
        $data = $request->query('data'); // Ex: "2025-04-17"
        $barbeiroId = $request->query('barbeiro_id'); // ID do barbeiro

        // Lista de horários disponíveis
        $horarios = [
            '09:00', '09:30', '10:00', '10:30',
            '11:00', '11:30', '12:00', '12:30',
            '13:00', '13:30', '14:00', '14:30',
            '15:00', '15:30', '16:00', '16:30',
            '17:00', '17:30', '18:00', '18:30'
        ];

        // Buscar horários já agendados no banco
        $agendados = Agendamento::where('agendamento_funcionario_id', $barbeiroId)
            ->whereDate('data_hora', $data) // Filtra apenas pela data
            ->pluck('data_hora')
            ->map(function ($item) {
                return Carbon::parse($item)->format('H:i'); // Extrai somente a hora
            })
            ->toArray();

        // Filtrar horários disponíveis
        $disponiveis = array_diff($horarios, $agendados);

        return response()->json(array_values($disponiveis));
    }




}



