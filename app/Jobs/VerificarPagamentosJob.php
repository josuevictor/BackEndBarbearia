<?php

namespace App\Jobs;

use App\Models\gestorBarbearia\Barbearia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class VerificarPagamentosJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct() {}

    public function handle()
    {
        $barbearias = Barbearia::where('status', 'ativo')->get();

        foreach ($barbearias as $barbearia) {
            if ($barbearia->data_vencimento) {
                $dias_atraso = now()->diffInDays($barbearia->data_vencimento, false);

                if ($dias_atraso >= 3) {
                    // Bloqueia a barbearia caso tenha mais de 3 dias de atraso
                    $barbearia->status = 'bloqueado';
                    $barbearia->save();
                } else {
                    // Verifica no Mercado Pago se há pagamento via PIX
                    $this->verificarPagamentoViaPix($barbearia);
                }
            }
        }
    }

    private function verificarPagamentoViaPix($barbearia)
    {
        $access_token = env('MERCADO_PAGO_ACCESS_TOKEN');

        try {
            // Requisição para verificar pagamentos PIX aprovados
            $response = Http::withToken($access_token)->get("https://api.mercadopago.com/v1/payments/search", [
                'status' => 'approved',
                'sort' => 'date_created',
                'criteria' => 'desc',
                'payment_method_id' => 'pix',  // Filtra apenas pagamentos via PIX
                'external_reference' => $barbearia->id
            ]);

            // Verifica se a resposta foi bem-sucedida
            if ($response->successful()) {
                $pagamentos = $response->json()['results'] ?? [];

                if (!empty($pagamentos)) {
                    // Pagamento via PIX encontrado, atualiza os dados da barbearia
                    $this->atualizarBarbearia($barbearia);
                }
            } else {
                // Se houver erro na API do Mercado Pago, logue o erro para análise
                \Log::error('Erro ao buscar pagamentos PIX para a barbearia ' . $barbearia->id, [
                    'response' => $response->json()
                ]);
            }
        } catch (\Exception $e) {
            // Log de erro caso ocorra algum problema na requisição
            \Log::error('Erro ao verificar pagamento PIX para a barbearia ' . $barbearia->id, [
                'error' => $e->getMessage()
            ]);
        }
    }

    private function atualizarBarbearia($barbearia)
    {
        // Atualiza a data de vencimento para o próximo mês após um pagamento via PIX
        $barbearia->data_vencimento = now()->addMonth();
        $barbearia->status = 'ativo';
        $barbearia->save();
    }
}
