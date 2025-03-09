<?php

namespace App\Http\Controllers\Payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Jobs\VerificarPagamentosJob;

use App\Models\gestorBarbearia\Barbearia;
use Illuminate\Validation\Rule;

class PaymentController extends Controller
{
    public function verificarPagamentos()
    {
        (new VerificarPagamentosJob())->handle(); // Executa o Job imediatamente
        return response()->json(['message' => 'Job executado com sucesso']);
    }

    public function criarPagamentoPix(Request $request)
    {
        // Validação dos dados de entrada
        $request->validate([
            'email' => 'required|email',
            'transaction_amount' => 'required|numeric|min:1',  // Valor do pagamento
            'barbearia_id' => [
                'required',
                Rule::exists(Barbearia::class, 'id') // Validação usando o model
            ],
        ]);

        // Configurações do pagamento
        $url = "https://api.mercadopago.com/v1/payments";
        $accessToken = env('MERCADO_PAGO_ACCESS_TOKEN');

        // Gerando um idempotency key único para a requisição
        $idempotencyKey = (string) Str::uuid();

        // Dados do pagamento via PIX
        $pagamento = [
            "transaction_amount" => $request->transaction_amount, // Valor da transação
            "description" => "Pagamento mensal Barbearia",
            "payment_method_id" => "pix", // Método de pagamento via PIX
            "payer" => [
                "email" => $request->email
            ],
            "external_reference" => $request->barbearia_id, // ID da barbearia
        ];

        // Faz a requisição para gerar o pagamento via PIX
        try {
            $response = Http::withToken($accessToken)
                ->withHeaders(['X-Idempotency-Key' => $idempotencyKey])
                ->post($url, $pagamento);

            // Obtemos os dados da resposta
            $paymentData = $response->json();

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Erro ao criar pagamento via PIX no Mercado Pago',
                    'details' => $paymentData
                ], $response->status());
            }

            // Verificamos se as chaves existem antes de acessá-las
            $pointOfInteraction = $paymentData['point_of_interaction'] ?? null;
            $transactionData = $pointOfInteraction['transaction_data'] ?? null;

            $qr_code = $transactionData['qr_code'] ?? null;
            $qr_code_base64 = $transactionData['qr_code_base64'] ?? null;
            $ticket_url = $transactionData['ticket_url'] ?? null;

            // Se o Mercado Pago não retornou um QR Code, lançamos um erro
            if (!$qr_code || !$qr_code_base64) {
                return response()->json([
                    'error' => 'Erro ao gerar pagamento PIX',
                    'message' => 'QR Code não disponível. Verifique se o pagamento foi criado corretamente.',
                    'payment_data' => $paymentData // Adicionando os dados completos para depuração
                ], 400);
            }

            return response()->json([
                'payment_id' => $paymentData['id'], // ID do pagamento para consultar o status
                'qr_code' => $qr_code,
                'qr_code_base64' => $qr_code_base64,
                'ticket_url' => $ticket_url
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro na comunicação com o Mercado Pago',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // WebHook
    public function webhook(Request $request)
    {
        // Dados recebidos do Mercado Pago
        $dados = $request->all();

        \Log::info('Webhook recebido:', $dados);

        // Verifica se o evento é de atualização de pagamento
        if (isset($dados['action']) && $dados['action'] === 'payment.updated') {
            // Obtém o ID do pagamento
            $paymentId = $dados['data']['id'] ?? null;

            if ($paymentId) {
                // Busca os detalhes do pagamento na API do Mercado Pago
                $accessToken = env('MERCADO_PAGO_ACCESS_TOKEN');
                $response = Http::withToken($accessToken)
                    ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

                if ($response->successful()) {
                    $paymentData = $response->json();

                    // Verifica se o pagamento foi aprovado e se tem external_reference
                    if ($paymentData['status'] === 'approved' && isset($paymentData['external_reference'])) {
                        $externalReference = $paymentData['external_reference']; // ID da barbearia
                        $barbearia = Barbearia::find($externalReference);

                        if ($barbearia) {
                            // Atualiza o status da barbearia
                            $barbearia->update([
                                'status' => 'ativo',
                                'data_vencimento' => now()->addMonth(),
                            ]);

                            \Log::info("Barbearia ID {$barbearia->id} atualizada para ATIVO via webhook.");
                        } else {
                            \Log::warning("Barbearia com ID {$externalReference} não encontrada.");
                        }
                    } else {
                        \Log::warning('Pagamento não aprovado ou external_reference não encontrado:', $paymentData);
                    }
                } else {
                    \Log::error('Erro ao buscar detalhes do pagamento no Mercado Pago', [
                        'response' => $response->json()
                    ]);
                }
            }
        }

        return response()->json(['message' => 'Webhook recebido com sucesso'], 200);
    }


    /**
     * Verifica o status de um pagamento no Mercado Pago.
     *
     * @param string $paymentId ID do pagamento no Mercado Pago.
     * @return \Illuminate\Http\JsonResponse
     */
    public function verificarStatusPagamento($paymentId)
    {
        $accessToken = env('MERCADO_PAGO_ACCESS_TOKEN');

        try {
            // Faz a requisição para buscar os detalhes do pagamento
            $response = Http::withToken($accessToken)
                ->get("https://api.mercadopago.com/v1/payments/{$paymentId}");

            if ($response->failed()) {
                return response()->json([
                    'error' => 'Erro ao buscar status do pagamento no Mercado Pago',
                    'details' => $response->json()
                ], $response->status());
            }

            // Obtém os dados da resposta
            $paymentData = $response->json();

            // Retorna o status e outras informações relevantes
            return response()->json([
                'status' => $paymentData['status'], // Status do pagamento (ex: approved, pending, rejected)
                'external_reference' => $paymentData['external_reference'] ?? null, // ID da barbearia
                'date_approved' => $paymentData['date_approved'] ?? null, // Data de aprovação
                'payer_email' => $paymentData['payer']['email'] ?? null, // E-mail do pagador
                'transaction_amount' => $paymentData['transaction_amount'] ?? null, // Valor do pagamento
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Erro na comunicação com o Mercado Pago',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}
