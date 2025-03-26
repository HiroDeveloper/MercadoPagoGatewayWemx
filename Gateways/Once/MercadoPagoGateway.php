<?php

namespace Modules\MercadoPago\Gateways\Once;

use App\Models\Gateways\Gateway;
use App\Models\Gateways\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Modules\MercadoPago\Traits\HelperGateway;

class MercadoPagoGateway implements PaymentGatewayInterface
{
    use HelperGateway;

    public static string $endpoint = 'mercadopago-argentina';
    public static string $type = 'once';
    public static bool $refund_support = false;
    private static string $apiUrl = 'https://api.mercadopago.com';

    public static function getConfigMerge(): array
    {
        return [
            'access_token' => '',
            'public_key' => '',
            'test_mode' => true,
        ];
    }

    public static function processGateway(Gateway $gateway, Payment $payment)
    {
        try {
            // Obtener tasa de cambio USD a ARS
            $exchangeRate = self::getExchangeRate();
            $amountARS = round($payment->amount * $exchangeRate, 2);

            // Crear preferencia de pago
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $gateway->config['access_token'],
                'Content-Type' => 'application/json',
            ])->post(self::$apiUrl . '/checkout/preferences', [
                'items' => [
                    [
                        'title' => 'Pago #' . $payment->id,
                        'quantity' => 1,
                        'unit_price' => $amountARS,
                        'currency_id' => 'ARS'
                    ]
                ],
                'payer' => [
                    'email' => $payment->user->email
                ],
                'payment_methods' => [
                    'excluded_payment_types' => [
                        ['id' => 'atm']
                    ]
                ],
                'auto_return' => 'all',
                'back_urls' => [
                    'success' => self::getSucceedUrl($payment),
                    'pending' => self::getCancelUrl($payment),
                    'failure' => self::getCancelUrl($payment)
                ],
                'notification_url' => self::getReturnUrl(),
                'external_reference' => $payment->id
            ]);

            if ($response->failed()) {
                self::log('Error MercadoPago: ' . $response->body(), 'error');
                return self::errorRedirect('Error al crear el pago');
            }

            $paymentData = $response->json();
            $payment->update(['reference' => $paymentData['id']]);

            return redirect()->away($paymentData['init_point']);

        } catch (\Exception $e) {
            self::log('Excepción: ' . $e->getMessage(), 'critical');
            return self::errorRedirect('Error interno del gateway');
        }
    }

    public static function returnGateway(Request $request)
    {
        $paymentId = $request->input('payment_id');
        $gateway = self::getGatewayByEndpoint();

        // Verificar estado del pago
        $response = Http::withToken($gateway->config['access_token'])
            ->get(self::$apiUrl . "/v1/payments/{$paymentId}");

        if (!$response->successful()) {
            return self::errorRedirect('Error verificando el pago');
        }

        $mpPayment = $response->json();
        $payment = Payment::find($mpPayment['external_reference']);

        if ($mpPayment['status'] === 'approved') {
            $payment->markAsPaid();
            return redirect(self::getSucceedUrl($payment));
        }

        $payment->markAsFailed();
        return redirect(self::getCancelUrl($payment));
    }

    private static function getExchangeRate(): float
    {
        $response = Http::get('https://dolarapi.com/v1/dolares/blue');
        
        if (!$response->successful()) {
            throw new \Exception('Error obteniendo tasa de cambio');
        }

        $data = $response->json();
        return (float)$data['venta'];
    }

    public static function drivers(): array
    {
        return [
            'MercadoPagoGateway' => [
                'driver' => 'MercadoPagoGateway',
                'type' => self::$type,
                'class' => self::class,
                'endpoint' => self::$endpoint,
                'refund_support' => self::$refund_support,
                'blade_edit_path' => 'gateways.mercadopago'
            ]
        ];
    }

    public static function endpoint(): string
    {
        return self::$endpoint;
    }
}