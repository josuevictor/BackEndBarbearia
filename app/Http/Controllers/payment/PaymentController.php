<?php

namespace App\Http\Controllers\payment;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use App\Jobs\VerificarPagamentosJob;

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
            ]
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
}
